<?php
require(__DIR__ . '/config.php');
require(__DIR__ . '/classes/Main.php');
require(__DIR__ . '/classes/Event.php');
require(__DIR__ . '/classes/CommitEvent.php');
require(__DIR__ . '/classes/CreatePREvent.php');
require(__DIR__ . '/classes/ClosePREvent.php');
require(__DIR__ . '/classes/CommentPREvent.php');

new Main();
