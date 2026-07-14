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
/*
if(file_exists("../lib/Crypt/Blowfish.php")){
	require_once ("Crypt/Blowfish.php");
}*/

$config = array();
$items = array();
$data = array();
$tmp_data = array();
$counters = array();
$mailadr ="";
$mailid ="";
$inquiry = array();

/*初期処理*/
config($config);
conf_customize($config);

formInit($config,$items,$data,$counters);
// 引数にmsgIDがあるとき入力値表示
//	echo "formInit end \n";
if(isset($_REQUEST['mid'])) {
	$tempid = strDecrypt($_REQUEST['mid']);
	$list = array();
	$list = explode('-',$tempid);
	$formno = $list[0];
	$countno = $list[1];
	$msgid = $list[2];
//	echo "$formno $countno $msgid";
//	exit;
	//取得データから入力データを取得
	$sql = "select * from db_form_input_data where formno=:formno and id=:id and countno=:countno";
	$params[':formno'] = $formno;
	$params[':id'] = $msgid;
	$params[':countno'] = $countno;
	
	dbSelectTable($config,$sql,$params,$tmp_data);

	foreach($items as $item) {
		$cancel = $item['cancel_in'];
		$name = $item['input_name'];
		$type = $item['item_type'];
		$input_no = "in" . sprintf("%03d",$item['input_no']);
		if($cancel){
			if($type == ENTRYNO){
				$data["$name"] = $countno;
				// 受付番号をセット
			}else{
				if(empty($tmp_data[0]["$input_no"])){
					$data["$name"] = "";
				}else{
					$data["$name"] = $tmp_data[0]["$input_no"];
				}
			}
		}
	}
}

//print_r($data);

$_SESSION['id'] = isset($_REQUEST['id'])? $_REQUEST['id'] : null;

// 入力初期処理　規定値表示JS生成
$jsbuf = inputInit($items,$data,$mailid,$mailadr,1);

// 日付の設定
$today = date('Y/m/d H:i:s');
$delete = (isset($config['form_delete']))? 1 : 0 ;
$day = $config['close_date']; // 2019/11/11

//			echo('<pre>' . var_dump($_REQUEST) . '</pre>');

// キャンセル待ちチェック 2019/11/11 キャンセルにも追加
$wait = waitCheck($config,$counters);
$limit = limitCheck($config,$counters);


?>