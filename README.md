# これは何？  
  
Github へのコミットやプルリクエストを、
Backlog の関連課題にコメントとして登録するための Webhook エンドポイントです。  
  
複数リポジトリからのWebhookに対応しています。  
設定ファイルで指定した Backlog プロジェクト（複数可）の課題のみが対象です。  
  
以下のイベントをBacklogにコメントとして投稿します。  
  
- コミットメッセージに課題キーを含むコミットのPush
- 件名または本文に課題キーを含むプルリクエストの作成、マージ、クローズ
- 上記プルリクエストへのコメントの投稿
  
# 必要なもの  
  
SSL 化された Webサーバ。  
  
Backlog アカウント (APIが使えるプラン)  
-> APIトークン を発行しておく  
  
# 設置手順  
  
config.example.php を参考に、config.php を作成。  
  
index.php と config.php をサーバに設置。  
※ index.php は公開領域に。  
※ config.php は非公開領域に置くか、HTTPアクセスを制限すること。  
※ index.php を設置したURL (HTTPS) を、ChatWork の Webhook URL として登録する。  

log/ フォルダのパーミッションを 666 等、書き込み可能にする。  
  
# Github の設定  
  
Github のプロジェクト Settings -> Webhooks -> Add webhook より、  
Webhook を作成してください。  

- Payload URL : 本ソースを設置したURLを設定。
- Content type : application/json
- Secret : config.php に指定した GITHUB_WEBHOOK_SECRET を入力してください（複数リポジトリにWebhookを設定する場合、Secretは全て同じにする必要があります）
- Which events would you like to trigger this webhook? : Send me everything.（Just the push event. を選択した場合、プルリクエストは反映されず、コミットのみがコメントに反映されるようになります）。

