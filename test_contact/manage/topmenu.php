<?php
// 汎用フォーム管理画面メニュー処理
//
// 送信先毎対応　2019/01/15
//
//初期設定
//ini_set( 'display_errors', 1 );
if (version_compare(PHP_VERSION, '5.1.0', '>=')) {//PHP5.1.0以上の場合のみタイムゾーンを定義
	date_default_timezone_set('Asia/Tokyo');//タイムゾーンの設定（日本以外の場合には適宜設定ください）
}


require_once('../data/config.php');
require_once('../util.php');
require_once('../html.php');
require_once('manageutil.php');

$config = array();
$items = array();
$data = array();
$counters = array();

//初期処理
config($config);
mng_define();

//ログ出力
logWrite('"topmenu : start"');

$submit = isset($_POST['submit'])? $_POST['submit'] : null;
$errmsg1 = null;
$errmsg2 = null;

switch($submit) {
	case BTN_LOGOUT :
		// ログアウト
		$formname = $_POST['newmailid'];
		$ary_formname = array();
		$ary_formname = getFormNameTable($config);
		// 一旦セッションON
		formInit($config,$items,$data,$counters);
		logout();
		break;
		
	case BTN_FORMSEL :
		// 送信先選択
		if(!empty($_POST['newmailid']) && !empty($_POST['user'])){
			$user = $_POST['user'];
			$mailid = $_POST['newmailid'];
			// 個別設定
			formInit($config,$items,$data,$counters);

			$GLOBALS['@formname'] = $config['formname'];

			$_SESSION['user'] = $user;
			$_SESSION['errmsg'] = null;
			logWrite('"topmenu : go to $mailid"');
			header('Location:manage.php?mailid=' . $mailid);
		}else{
			header('Location:./');
		}
		break;
	default :
		break;
}

exit;

?>
