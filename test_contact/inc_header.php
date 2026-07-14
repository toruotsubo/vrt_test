<?php
if(!isset($_SESSION)) {
/* クッキーチェック用クッキーセット */
setcookie("ON", 1);
}
error_reporting(E_ALL | E_STRICT);
ini_set( 'display_errors', 1 );

/* メール送信先リストCSV */
/*$filename = "mail/maillist.txt";*/
/* タイムゾーンの設定 */
date_default_timezone_set('Asia/Tokyo');

require_once('data/config.php');
require_once('util.php');
require_once('html.php');
require_once('maillist.php');

$config = array();
$items = array();
$data = array();
$counters = array();
$mailadr ="";
$mailid ="";
$inquiry = array();

/*初期処理*/
config($config);

formInit($config,$items,$data,$counters);
/* メールIDが送られてきたときメールアドレス取得 */
if(isset($_REQUEST['id'])) {
	$mailadr = mailIdCheck($config,$_REQUEST['id']);
	$mailid = $_REQUEST['id'];
}elseif(isset($data['mailid'])){
	$mailid = $data['mailid'];
}

/* 入力初期処理　規定値表示JS生成 */
$jsbuf = inputInit($items,$data,$mailid,$mailadr);

/*print_r($config);*/

/* 日付の設定 */
$today = date('Y/m/d H:i:s');
/*			echo('<pre>' . var_dump($_REQUEST) . '</pre>'); */

// 引数から宛先取得 2019/04/16
if(isset($_GET['to']) && strlen($_GET['to']) > 0) {
	$fa = "M" . $_GET['to'];
}elseif(isset($data['item00'])) {
	$fa = $data['item00'];
}else{
	errDie("送信先不明",1);
// エラーメッセージ変更 2021/03/27
//	die("送信先不明");
}

// メールアドレスデータ読み込み 2019/01/17 DB使用
$inquiry = getMaillist_db($fa,$config);

if(!isset($inquiry)){
	errDie("送信先不明",0);
//	die("送信先不明");
}

//ファイルアップロード設定チェック
if(!(empty($config['file_upload'])) && $config['file_upload'] == 1){
	$ext_tbl = array();
	chkFileupload($config,$ext_tbl);
	$extlist = implode(',',$ext_tbl); // 許可拡張子一覧
	$max_f_size = cnvFilesize($config['upload_file_size_max']);
}
?>
