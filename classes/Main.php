<?php
declare(strict_types=1);

class Main
{
    private Object $payload;

    /**
     * @var Event[]
     */
    private array $events;

    public function __construct()
    {
        // エラーハンドリング
        $this->handleError();

        // リクエスト内容のチェック
        $this->verifyRequest();

        // Webhook の内容からイベントを判定
        $this->parseRequest();

        if (count($this->events) === 0) {
            $this->kill('no events to notify');
        }

        foreach ($this->events as $event) {
            $this->log($event->summary());

            // 課題キーを抽出
            $issue_keys = $event->getIssueKeys(BACKLOG_PROJECTS);

            if (count($issue_keys) === 0) {
                $this->log('no issue keys');
                continue;
            }

            // 各課題へコメントを登録
            foreach ($issue_keys as $issue_key) {
                $this->log("comment to {$issue_key}");
                try {
                    $this->postIssueComment($event, $issue_key);
                    $this->log('success');
                } catch (Throwable $e) {
                    $this->log('error: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * リクエストを検証して、OKならペイロードを取得
     *
     * @return Object
     */
    private function verifyRequest(): void
    {
        $request_body = file_get_contents('php://input');

        if ($request_body === null) {
            $this->kill('no request body');
        }

        if (DEBUG) {
            $headers = getallheaders();
            $this->log("-- request header --");
            $this->log($headers);
            $this->log("-- request body --");
            $this->log($request_body);
        }

        if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE_256'])) {
            $this->kill('no signature');
        }
        if (!$this->verifySignature($request_body, $_SERVER['HTTP_X_HUB_SIGNATURE_256'])) {
            $this->kill('invalid signature: ' . $_SERVER['HTTP_X_HUB_SIGNATURE_256']);
        }

        $this->payload = json_decode($request_body);

        if ($this->payload === null) {
            $this->log("request body: ");
            $this->log($request_body);
            $this->kill('failed to parse request body');
        }
    }

    /**
     * 署名を検証
     */
    private function verifySignature(string $request_body, string $signature): bool
    {
        $signature_parts = explode("=", $signature);
        if ($signature_parts !== 2) {
            return false;
        }

        list($algo, $digest) = $signature_parts;
        if (!in_array($algo, hash_algos(), true)) {
            $this->kill('unregistered signature algorism: ' . $algo);
        }

        $expected = hash_hmac($algo, $request_body, GITHUB_WEBHOOK_SECRET);

        return ($expected === $digest);
    }

    /**
     * Webhook イベントの種類を判定し、必要要素をパース
     */
    private function parseRequest(): void
    {
        if (isset($this->payload->commits)) {
            foreach ($this->payload->commits as $commit) {
                $event = new CommitEvent();
                $event->author = $commit->author->name ?? null;
                $event->commit_id = $commit->id ?? null;
                $event->commit_message = $commit->message ?? null;
                $event->commit_url = $commit->url ?? null;
                $event->branch = str_replace("refs/heads/", "", ($this->payload->ref ?? ""));
                $event->repository = $this->payload->repository->full_name ?? null;
                $this->events[] = $event;
            }
        } elseif (
            isset($this->payload->action)
            && $this->payload->action === "opened"
            && isset($this->payload->pull_request)
        ) {
            $event = new CreatePREvent();
            $event->author = $this->payload->sender->login ?? null;
            $event->pr_title = $this->payload->pull_request->title ?? null;
            $event->pr_comment = $this->payload->pull_request->body ?? null;
            $event->pr_url = $this->payload->pull_request->html_url ?? null;
            $event->repository = $this->payload->repository->full_name ?? null;
            $this->events[] = $event;
        } elseif (
            isset($this->payload->action)
            && $this->payload->action === "closed"
            && isset($this->payload->pull_request)
        ) {
            $event = new ClosePREvent();
            $event->author = $this->payload->sender->login ?? null;
            $event->is_merged = ($this->payload->pull_request->merged_at !== null);
            $event->pr_title = $this->payload->pull_request->title ?? null;
            $event->pr_comment = $this->payload->pull_request->body ?? null;
            $event->pr_url = $this->payload->pull_request->html_url ?? null;
            $event->repository = $this->payload->repository->full_name ?? null;
            $this->events[] = $event;
        } elseif (
            isset($this->payload->action)
            && $this->payload->action === "created"
            && isset($this->payload->comment)
            && isset($this->payload->issue->pull_request)
        ) {
            $event = new CommentPREvent();
            $event->author = $this->payload->sender->login ?? null;
            $event->pr_title = $this->payload->issue->title ?? null;
            $event->pr_comment = $this->payload->issue->body ?? null;
            $event->pr_url = $this->payload->issue->html_url ?? null;
            $event->comment = $this->payload->comment->body ?? null;
            $event->repository = $this->payload->repository->full_name ?? null;
            $this->events[] = $event;
        }
    }

    private function postIssueComment(Event $event, string $issue_key): void
    {
        $params = [
            'content' => $event->message(),
        ];

        $this->callApi("POST", "/api/v2/issues/{$issue_key}/comments", $params);
    }

    private function callApi(string $method, string $endpoint, array $params = [])
    {
		if (!in_array($method, ['GET', 'POST'])) {
			throw new Exception('invalid requeset method');
		}

        $url = BACKLOG_SPACE_URL . $endpoint . '?apiKey=' . BACKLOG_API_KEY;

		if ($method === 'GET' && $params) {
			$url .= '&' . http_build_query($params);
		}

		$curl = curl_init($url);
		if ($method == 'POST') {
			curl_setopt($curl, CURLOPT_POST, TRUE);
			if ($params) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
			}
		} else {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		}
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

		$response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($status < 400) {
            return json_decode($response);
        } else {
            $data = json_decode($response);
            $this->log("Backlog API Error");
            $this->log($endpoint);
            $this->log($status);
            $this->log(json_decode($data));
            throw new Exception("Error {$status}");
        }
    }

    private function kill($str)
    {
        $this->log($str);
        die($str);
    }

    private function log($str)
    {
        if (!is_string($str)) {
            $str = json_encode($str);
        }

        $dir = __DIR__ . "/.." . LOG_DIR;
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
            chmod($dir, 0777);
        }

        $str = sprintf("[%s] %s \n", date('Y-m-d H:i:s'), $str);
        $file = $dir . date('Y-m-d') . '.log';
        error_log($str, 3, $file);
    }

    private function handleError()
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            $debug = sprintf(
                "[Error %s] %s (in %s line %s)",
                $errno,
                $errstr,
                $errfile,
                $errline
            );
            $this->log($debug);
        });

        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null) {
                $debug = sprintf(
                    "[Error %s] %s (in %s line %s)",
                    $error['type'],
                    $error['message'],
                    $error['file'],
                    $error['line'],
                );
                $this->log($debug);
            }
        });
    }
}
