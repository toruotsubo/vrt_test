<?php
error_reporting(E_ALL | E_STRICT);
ini_set( 'display_errors', 1 );

  echo "メール送信するよ。";


mb_language("Japanese");
mb_internal_encoding("UTF-8");

if (mb_send_mail("nakayama@webmasters.co.jp", "テストメール", "これはテストです。", "From: form@wms202305.sakura.ne.jp")) {
  echo "メールが送信されました。";
} else {
  echo "メールの送信に失敗しました。";
}

?>
