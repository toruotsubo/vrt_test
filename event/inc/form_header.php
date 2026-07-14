<?php
//
if(!isset($_SESSION)) {
// クッキーチェック用クッキーセット
//jsnevent用
setcookie("ON", 1);
}

// タイムゾーンの設定
date_default_timezone_set('Asia/Tokyo');

require_once('../../lib/config.php');
require_once('util.php');
require_once('html.php');
// 個別設定
require_once('data/conf_custom.php');

$config = array();
$items = array();
$data = array();
$counters = array();
$mailadr ="";
$mailid ="";
$inquiry = array();

/*初期処理*/
config($config);
conf_customize($config);
formInit($config,$items,$data,$counters);
// メールIDが送られてきたときメールアドレス取得
if(isset($_REQUEST['id'])) {
	$mailadr = mailIdCheck($config,$_REQUEST['id']);
	$mailid = $_REQUEST['id'];
}elseif(isset($data['mailid'])){
	$mailid = $data['mailid'];
}

//print_r($config);
$_SESSION['cancel'] = null; // キャンセル画面→登録画面対応  2019/07/26

// 入力初期処理　規定値表示JS生成
$jsbuf = inputInit($items,$data,$mailid,$mailadr);


// 日付の設定
$today = date('Y/m/d H:i:s');
$day = $config['close_date'];
$delete = (isset($config['form_delete']))? 1 : 0 ;

// キャンセル待ちチェック
$wait = waitCheck($config,$counters);
$limit = limitCheck($config,$counters);
?>