# これは何？  
  
Github へのコミットやプルリクエストを、
Backlog の関連課題にコメントとして登録するための Webhook エンドポイントです。  
  
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
  
