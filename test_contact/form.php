<?php
//
// 汎用フォームphp版
//
//
// 2016/10/22 K.Nakayama v1.0
//
//----------------------------------------
// 2017/08/20 K.Nakayama v1.1 送信先選択問い合わせフォーム化 (キャンセル・メール登録機能未使用のため不完全）
// 2017/09/18 K.Nakayama v1.2 メールタイトル・連絡先電話番号対応
// 2017/10/28 K.Nakayama v1.3 送信先指定なし対応・入力パラメータ30件対応 初回ログ取得場所変更
// 2017/10/29 K.Nakayama v1.4 添付ファイル対応・Replyメール修正
// 2018/01/25 K.Nakayama v1.5 複数フォーム対応
// 2018/02/09 K.Nakayama v1.51 不具合修正
// 2018/03/05 K.Nakayama v1.52 POST送信ではないとき対応
// 2018/04/29 K.Nakayama v1.6 複数リファラ対応（MAなどで他サーバへの送受信対応
// 2018/04/29 K.Nakayama v1.61 Return-Path対応
// 2019/04/16 K.Nakayama v1.62 返信メールなし対応
// 2020/11/20 K.Nakayama v1.63 宛先フォーム入力対応
// 2021/07/05 K.Nakayama v1.7  パスワード入力対応
// 2023/06/13 K.Nakayama v2.0  複数ファイル対応
// 2023/06/13 K.Nakayama v2.01 複数ファイル対応 ファイルサイズオーバーの時のエラー表示変更
// 2023/12/15 K.Nakayama v2.02 PHP7.4対応
//                             返信メール対応
//                             DBのupload_extがfalseのときは不許可リストとして使用
//
//
//エラー出力(debug)
/*
error_reporting(E_ALL | E_STRICT);
ini_set( 'display_errors', 1 );
*/
if (version_compare(PHP_VERSION, '5.1.0', '>=')) {//PHP5.1.0以上の場合のみタイムゾーンを定義
	date_default_timezone_set('Asia/Tokyo');//タイムゾーンの設定（日本以外の場合には適宜設定ください）
}
//初期設定
//require_once('data/config.php');
//define("LIB",$_SERVER['DOCUMENT_ROOT'] . "");
define("LIB", "");
require_once(LIB . 'util.php');
require_once(LIB . 'html.php');
require_once(LIB . 'replaceText.php');
if(file_exists(LIB . "maillist.php")){
	require_once(LIB . 'maillist.php');
}

$config = array(); // フォーム各設定
$items = array(); // フォーム入力項目リスト
$counters = array(); // フォーム入力項目リスト
$data = array(); // 出力値データ
$err = array() ; // エラーデータ
$logdata = array();
$GLOBALS{'@mode'} = -1;

/*
header("Content-Type:text/html;charset=utf-8");//dbg
print_r($_POST);
echo("<br>");
print_r($_FILES);
*/

if($_SERVER["REQUEST_METHOD"] !== "POST"){
	die("エラー：送信データがありません");
}
if(isset($_POST)) {
	if(file_exists(LIB . "data/config.php")){
		require_once(LIB . 'data/config.php');
		config($config); // 2018/02/11 複数フォーム対応

		if(isset($_POST['formname'])) {
			$confname = 'data/conf_' . h($_POST['formname']) . '.php';
			if(file_exists($confname)){
				require_once($confname);
				conf_customize($config);
			}
		// 個別設定  2018/02/11
		}else{
			if(empty($config['errmsg']['err_fsize'])){
				die('エラー：formname不明');
			}else{
				errDie($config['errmsg']['err_fsize'],1);
			}
		}
	}else{
		die('エラー：config不明');
	}
}else{
	die('エラー：送信データがありません');
}
	//入力値をログにセット 2017/10/28 取得タイミング変更
	formlogWrite(); 

//フォーム初期設定取得処理
formInit($config,$items,$data,$counters);
//echo('<pre>session_on session = ' . var_dump($_SESSION) . '</pre>'); //dbg

Init($config);
//echo "form start " . $_SESSION['msgid']."<br>";
//formlogWrite(); //入力値をログにセット 2017/10/28 取得タイミング変更
//echo('<pre>' . "mode = " . $GLOBALS{'@mode'} . '</pre>');
/*
header("Content-Type:text/html;charset=utf-8");//dbg
print_r($_POST);
echo("<br>");
*/
switch($GLOBALS{'@mode'}) {
	case 0:
		$ret = mailRegist($config,$counters);
		break;
	case 1:
		$ret = Check($items,$config,$data,$counters) ;
		break;
	case 2:
	//確認完了→キャンセル処理
		$ret = Cancel($items,$config,$data,$counters) ;
		break;
	case 3:
	//確認完了→登録処理
		$ret = Accept($items,$config,$data,$counters) ;
		break;
	default:
		break;
}

if($ret == -1) {
	// 受付終了
	$data['html_overmsg'] = $config['html_overmsg'];
	htmlPrint('over',$data);
}
//echo "form end " . $_SESSION['msgid']."<br>";//debug

exit;

// 初期設定処理
function Init($config){
// $r_config : フォーム設定リファレンス
//var_dump($_SESSION);
	// 処理モードセット　0:メールアドレス受付処理 1:確認処理 2:キャンセル処理 3:完了処理
	$m = $config['confmode'];
//	echo "mode = " . $_POST["$m"] ."<br>";
//	echo "mailtonum = " . $_SESSION['data_mailtonum'] ."<br>";
	$GLOBALS['@cancel'] = isset($_SESSION["cancel"]) ? $_SESSION["cancel"] : null;

	if (isset($_POST["$m"])) {
		if($_POST["$m"] == "MAIL") {
			if($_POST["action"] == "submit"){
				$GLOBALS{'@mode'} = 0 ;
				//メール登録処理
			}else{
				$location = $config['form_regist_url'] ;//.'?' .session_name()."=".session_id();
    			header("Location: $location");
				exit;// 入力ページへ
			}
		}else if($_POST["$m"] == "CANCEL") {
			if($_POST["action"] == "submit"){
				$GLOBALS{'@mode'} = 2 ;
				//キャンセル入力チェック処理
			}else{
				$buf = null;
				if(isset($_SESSION['mailid'])){
					// idあるときはURLにセット
					$buf = '?formno=' . $config['formno'] . '&id=' . $_SESSION['mailid'] ;
				}
				$location = $config['form_cancel_url'] . (isset($buf)? $buf : '') ;
    			header("Location: $location");
				exit;// 入力ページへ
			}
		}else if($_POST["$m"] == "CONFIRM") {
			if($_POST["action"] == "submit"){
				$GLOBALS{'@mode'} = 3 ;
				//終了処理
			}else{
				$buf = null;
				if(isset($_SESSION['mailid'])){
					// idあるときはURLにセット
					$buf = '?formno=' . $config['formno'] . '&id=' . $_SESSION['mailid'] ;
				}else if(isset($_SESSION['data_mailtonum'])){
					$buf = '?to=' . substr($_SESSION['data_mailtonum'],1) ;
				}

				if(isset($GLOBALS['@cancel'])){
					$location = $config['form_cancel_url'] . (isset($buf)? $buf : '') ;
				}else{
					$location = $config['form_url']  . (isset($buf)? $buf : '') ;
				}
//				echo " location : $location\n"; //debug
				header("Location: $location");
				exit();// 入力ページへ
			}
		}else{
			errDie('処理モード未定義' );
		}
	}else{
		$GLOBALS{'@mode'} = 1 ;
	}

	//フォーム名取得・チェック
	$GLOBALS['@formname'] = isset($_POST['formname']) ? $_POST['formname'] : '';
	if (!isset($_POST['formname'])){
		//フォーム名がPOSTされてない
		errDie('フォームPOSTエラー' );
		//エラー終了
	}

	if($_POST['formname'] != $config['formname']) {
		//フォーム名不一致
		errDie('フォーム名不一致エラー');
		//エラー終了
	}

	$GLOBALS['@time'] = isset($_SESSION["time"]) ? $_SESSION["time"] : date("Y/m/d H:i:s");
	//現在時間をグローバル変数に

	// リファラチェック
	// 複数リファラ対応 v1.6 2018/05/04
//	if(strpos($config['form_url'],'localhost') === false){
//		$p_url[] = parse_url($config['form_url']);
//		$hostname = parse_url($config['form_url'],PHP_URL_HOST); // 複数リファラ対応 v1.6 2018/05/04
//		$Referer_check_domain = 'https://' . $hostname . '/' ; // httpでのテストのとき動かないので修正 2018/02/09
	if(isset($config['allow_referer'])){
		$Referer_check_domain = $config['allow_referer'] ; // 複数リファラ対応 v1.6 2018/05/04
	}else{
		$Referer_check_domain = parse_url($config['form_url'],PHP_URL_HOST) ; // 複数リファラ対応 v1.6 2018/05/04
	}
	if(refererCheck($Referer_check_domain) === false) {
		errDie('リファラエラー',1);
		//エラー終了
	}
	return;

}

// メール登録処理処理
function mailRegist($config,$counters){
// $config : フォーム設定

	$err = array();
	$mailadr = '' ;
	$cancel = 0 ;
	$data = array(); // メール・html出力データ

	if(isset($_POST['cancel']) && $_POST['cancel'] = 'cancel'){
		// キャンセルのとき
		$cancel = 1 ;
	}

	if(($cancel == 0)&&(limitCheck($config,$counters) === true)) {
	// 受付制限チェック
		return(-1);
	}

	//入力値チェック
	if(!isset($_POST['mail'])) {
	// メールアドレス入力無し
		$err["メールアドレス"] =  $config['errmsg']['err_mail'];
	}else if(!checkMail($_POST['mail'])) {
	//メールアドレスチェック
		$err["メールアドレス"] =  $config['errmsg']['err_mail'];
	}else{
		$mailadr = $_POST['mail'];
	
		// メールアドレス存在チェック
		$sql = "select count(*) from db_form_input_data where formno = :formno and mailadr = :mailadr";
		$param = array();
		$param[':formno'] = $config['formno'] ;
		$param[':mailadr'] = $mailadr;
		
		$count = inputDbCount($sql,$param,$config);
		if(!isset($count) && $cancel == 1) {
		// キャンセルかつメールアドレスが無いとき
			$err["メールアドレス"] = "登録時のメールアドレスを入力してください。";
		}else{
			// メール登録ID生成
			$id = md5(uniqid(rand(), 1));
			// メールアドレス登録データ作成
			$param = array();
			$param[':formno'] = $data['formno'] = $config['formno'] ;
			$param[':id'] = $id ;
			$param[':msgid'] =  $data['id'] = $GLOBALS['@msgid'] ;
			$param[':mailadr'] = $mailadr ;
			$param[':cancel'] = $cancel ;
			$param[':env_referer'] = $_SERVER['HTTP_REFERER'];
			$param[':env_req_uri'] = $_SERVER['REQUEST_URI'];
			$param[':env_remote_addr'] = $_SERVER['REMOTE_ADDR'];
			$param[':env_remote_host'] = isset($_SERVER['REMOTE_HOST'])? $_SERVER['REMOTE_HOST'] : null;
			$param[':env_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

			// sql生成
			$sql = "insert into db_form_mail_data (" . 
			"formno,".
			"id,".
			"msgid,".
			"mailadr,".
			"cancel,".
			"env_referer,".
			"env_req_uri,".
			"env_remote_addr,".
			"env_remote_host,".
			"env_user_agent" .
			") values (" .
			":formno,".
			":id,".
			":msgid,".
			":mailadr,".
			":cancel,".
			":env_referer,".
			":env_req_uri,".
			":env_remote_addr,".
			":env_remote_host,".
			":env_user_agent);" ;
//			echo $sql;
			// メールアドレス登録
			dbExec($sql,$param,$config);
			// 受付URL生成
			if($cancel){
			 //   $cryptid = strCrypt($config['formno'] . "-" . $counters['id'] ."-" . $GLOBALS['@msgid']);
			//	$cancelurl = $config['form_cancel_url'] . "?id=" . $cryptid;
				$url = $config['form_cancel_url'] . "?formno=" . $config['formno'] . "&mailid=" . $id ;
				$data['url'] = $url ;
			}else{
				$url = $config['form_url'] . "?formno=" . $config['formno'] . "&id=" . $id ;
				$data['url'] = $url ;
			}
			$data['form_title'] = $config['form_title'] ;
			$data['html_mailmsg'] = $config['html_mailmsg'] ;
			
			// メールアドレスの入力がないときはCCメール先（管理者）に送る
			$to = $mailadr ;
			// メール送信
			mailSend($data,$config['mail_regist'],$config['mail_regist_subj'],$to,$config);
			
			//ログ出力
			logWrite('"mail : ' . $config['formno'] . ",$mailadr,$id,$url" .'"');

			if(empty($err)) {
			//正常終了なら確認画面表示
				htmlPrint('mthanks',$data);
				return(1);
			}
		}

	}
		
	if($err) {
		$err['mode'] = 'MAIL';
		htmlPrint('err',$err);
		return(0);
	}
}

// 入力チェック処理
function Check($items,$config,$data,$counters){
// $items : input nameリスト
// $config : フォーム設定
// $data : 入力値リスト
	$out = array();
	$logdata = array();
    $err = array();

	if(limitCheck($config,$counters) === true) {
	// 受付制限チェック
		return(-1);
	}
	if(waitCheck($config,$counters) === true) {
	// キャンセル待ちチェック
		$out["html_wait"] = $config['html_wait'];
		//キャンセル待ちメッセージセット
	}else{
		$out["html_wait"] = "" ;
	}
	
	CheckInputdata($config,$items,$data,$err);

//	if (isset($data['mailadr'])){
//		// メールアドレス存在チェック
//		$sql = "select count(*) from db_form_input_data where formno = :formno and mailadr = :mailadr";
//		$param = array();
//		$param[':formno'] = $config['formno'] ;
//		$param[':mailadr'] = $data['mailadr'];
//		
//		$count = inputDbCount($sql,$param,$config);
//		if(isset($count) && $count > 0) {
//		// メールアドレスが存在するとき
//			$err["メールアドレス"] = "すでに登録されているメールアドレスです。";
//		}
//	}

	//ログ出力
	foreach($data as $name => $value){
		if(is_array($value) !== true){
		// 複数ファイル対応 配列でなければ文字列化
			if(mb_strlen($value, 'UTF8') <= 3 || preg_match('/time|msgid|mailadr|mailid/',$name)){
				$logdata["$name"] = $value ;
			}else{
//				$logdata["$name"] =  mb_substr($value, 0, 3, 'UTF8') . str_repeat('*', mb_strlen($value, 'UTF8') - 3) ; // v1.4
			}
		}
	}
	$log = implode(",",$logdata);
	logWrite('"check : ' . $log . '"');

	// 入力値をセッションデータにセット
	foreach($data as $name => $value){
		$_SESSION["data_$name"] = $data["$name"] ;
	}

	// 時間をセッションデータにセット
	$_SESSION['time'] = $GLOBALS{'@time'} ;
	$_SESSION['msgid'] = $GLOBALS['@msgid'] ;

	$out["formname"] = $config['formname'] ;
	$out["msgid"] =  $GLOBALS['@msgid'] ;
	$out["mode"] =  'CONFIRM' ;
	$out["to"] = isset($data['mailtonum'])? $data['mailtonum'] : "" ;
	
	if(empty($err)) {
	//正常終了なら確認画面表示
		$out = array_merge($out,$data);
		htmlPrint('check',$out,$items);
		return(1);
	}else{
		$err["mode"] =  'CONFIRM' ;
		$err["formname"] = $config['formname'] ;
		$err["msgid"] =  $GLOBALS['@msgid'] ;
		$err["mailtoname"] =  isset($data['mailtonum'])? $data['mailtonum'] : "" ;
		htmlPrint('err',$err,$items);
		return(0);
	}
}

// キャンセル入力チェック処理
function Cancel($items,$config,$data,$counters){
// $items : input nameリスト
// $config : フォーム設定
// $data : 入力値リスト

	$out = array();
	$data = array();
	//
	CheckInputdata($config,$items,$data,$err);
	
	// メールアドレス存在チェック
	$sql = "select count(*) from db_form_input_data where formno = :formno and mailadr = :mailadr";
	$param = array();
	$param[':formno'] = $config['formno'] ;
	$param[':mailadr'] = isset($data['mailadr'])? $data['mailadr'] : null ;
	
	$count = inputDbCount($sql,$param,$config);
	if(!isset($count)) {
	// メールアドレスが無いとき
		$err["メールアドレス"] = "登録時のメールアドレスを入力してください。";
	}

	//ログ出力
	foreach($data as $name => $value){
		$value = preg_replace("/" . DELIMITER . "/",",",$data["$name"]) ;
		if(mb_strlen($value, 'UTF8') <= 3 || preg_match('/time|msgid|mailadr|mailid/',$name)){
			$logdata["$name"] = $value ;
		}else{
			$logdata["$name"] =  mb_substr($value, 0, 3, 'UTF8') . str_repeat('*', mb_strlen($value, 'UTF8') - 3) ; // v1.4
		}
	}
	$log = implode(",",$logdata);
	logWrite('"cancel : ' . $log . '"');

	// 入力値をセッションデータにセット
	foreach($data as $name => $value){
		$_SESSION["data_$name"] = $data["$name"] ;
	}

	// 時間をセッションデータにセット
	$_SESSION['time'] = $GLOBALS{'@time'} ;
	$_SESSION['msgid'] = $GLOBALS['@msgid'] ;

	// キャンセルフラグをセッションにセット
	$_SESSION['cancel'] = "1" ;

	$out["formname"] = $config['formname'] ;
	$out["msgid"] =  $GLOBALS['@msgid'] ;
	$out["mode"] =  'CANCEL' ;
	$out["html_wait"] = "" ;
//var_dump($_SESSION);
	
	if(!isset($err)) {
	//正常終了なら確認画面表示
		$out = array_merge($out,$data);
		htmlPrint('check',$out,$items);
		return(1);
	}else{
		$err["formname"] = $config['formname'] ;
		$err["msgid"] =  $GLOBALS['@msgid'] ;
		$err["mode"] =  'CANCEL' ;
		htmlPrint('err',$err,$items);
		return(0);
	}
}

// 入力完了・登録・メール送信処理
function Accept($items,$config,$data,$counters){
// $items : input nameリスト
// $config : フォーム設定
// $data : 入力値リスト

	if((!isset($GLOBALS['@cancel']))&&($config['check_over'] == 1)&&(limitCheck($config) === false)) {
	// 受付制限チェックありのときチェック
		return(-1);
	}

	if(!(isset($_POST['msgid']))||($GLOBALS['@msgid'] !== $_POST['msgid'])) {
		errDie('セッションエラー',1);
//2018/12/05修正	}else if(isset($_POST['time'])){
//		if (strtotime($GLOBALS['@time']) > strtotime($_POST['time'] . " +1 hour")){
	}else{
		if (strtotime($GLOBALS['@time'] . " +1 hour") < strtotime(date("Y/m/d H:i:s"))){
		// 最初の送信から1時間を過ぎたとき 2018/12/05
			errDie('入力タイムアウト',1);
		}
	}	
	
	$inputdata = array();
	
	//入力値をDBにセット
	foreach($items as $item) {
		$name = $item['input_name'];
		$type = $item['item_type'];
		$val = isset($data["$name"])?$data["$name"] : null ; 
		// echo ("$name : $type : $val <br>");

		$in_no = 'in' . sprintf("%03d",$item['input_no']); // 入力項目番号から入力データテーブル項目名生成

		if($type == PASSWD) {
		// パスワードの時は暗号化して保存
		 $data["$name"] = strCrypt($data["$name"]);
		}
		$val = $data["$name"] ;
		$inputdata["$in_no"] = hd($val);
		
		if(($type == FILEANY) || ($type == FILENEED)) {
			//ファイルアップロードのとき 2017/11/03
			// DB格納データにファイルURL、ファイルサイズをセット
			// 複数ファイル対応 2023/06/15
			$vals = array();
			$vals = explode("\n",$val) ;
			if(isset($data["$name"])) {
// 2023/06/15			if(!empty($data["$name"])) {
				$data["$name" . "_file"] = null;
				$inputdata["$in_no"] = null;
				for($i = 0; $i < count($data["$name" . "_fname"]); $i++ ){
					// サイズ単位表記変更 2017/11/23
					$fsize = cnvFilesize($data["$name" . "_fsize"][$i]);
					if(!isset($data["$name" . "_fname"][$i])){
						// メール・DB出力データ
						$data["$name" . "_file"] .= $config['virusmsg'] . "\n ($vals[$i] : $fsize)\n" ;
// 2023/06/15						$data["$name" . "_file"] .= $config['virusmsg'] . "\n ($val : $fsize)\n" ; 
						$inputdata["$in_no"] = "" ;
					}else{
						$fname = $data["$name" . "_fname"][$i] ;
						if (rename($config['tempdir'] . "/" . $fname,$config['upload_file_dir'] . "/" . $fname)) {
							//アップロードファイルを一時ディレクトリから保存先へ移動
							chmod($config['upload_file_dir'] . "/" . $fname, 0644);
						} else {
							errDie('ファイル保存エラー： ' . $fname);
							//エラー終了
						}
						// メール・DB出力データ
		//				$data["$name" . "_file"] = $config['upload_file_url'] . '/' . $fname . "\n ($val : " . ($data["$name" . "_fsize"] / 1024) . 'KB)' ;
// 2023/06/15						$data["$name" . "_file"] .= $config['upload_file_url'] . '/' . $fname . "\n ($val : $fsize)\n" ;
						$data["$name" . "_file"] .= $config['upload_file_url'] . '/' . $fname . "\n ($vals[$i] : $fsize)\n" ;
						$inputdata["$in_no"] .= $config['upload_file_url'] . '/' . $fname . "\n ($vals[$i] : $fsize)\n" ;
					}
				}
			}else{
				// メール・DB出力データ
				$data["$name" . "_file"] = "" ;
				$inputdata["$in_no"] = null ;
			}
			
		}elseif(($type == MAILNEED)||($type == MAILANY)){
			$inputdata['mailadr'] = isset($data["$name"])? $data["$name"] : "" ;
			$inputdata['mailid'] = isset($data['mailid'])? $data['mailid'] : null ;
		}
		if($type == MAILTO) {
			// 送信先選択のときDB格納データに送信先名、送信先メールアドレスをセット
			$inputdata['mailtoname'] = isset($data['mailtoname'])? $data['mailtoname'] : "" ;
			$inputdata['mailtoadr'] = isset($data['mailtoadr'])? $data['mailtoadr'] : "" ;
			// 2017/09/18 add メールタイトル・連絡先電話番号
			$inputdata['mailtosubj'] = isset($data['mailtosubj'])? $data['mailtosubj'] : "" ;
			$inputdata['mailtotel'] = isset($data['mailtotel'])? $data['mailtotel'] : "" ;
		}
		// 2022/04/19 同報先入力対応
		if($type == MAILCC) {
		 $data['mailcc'] = $data["$name"];
		}
	}
	
	$inputdata['formno'] = $config['formno'];
	$inputdata['id'] = $GLOBALS['@msgid'];
	$inputdata['countno'] = $counters['id'];
	$inputdata['cnt_id'] = $data['cnt_id'] = $counters['id'];
	$inputdata['date'] = $data['date'] = date("Y-m-d H:i:s");

	// キャンセルのとき既存データにキャンセルフラグセット
	if(isset($GLOBAL['@cancel']) && ($GLOBAL['@cancel'])) {
		//キャンセル用パラメータ
		$inputdata['cancel'] = 1;
		$inputdata['canceldate'] = date("Y-m-d H:i:s");
		$inputdata['cancelno'] = $counters['cancel'];
	}
	
	// フォーム入力データテーブルにセット
	inputDbInsert($inputdata,$config);
	
	// 出力したいconfig設定値はここへ　2018/07/14
	$data["form_title"] = $config['form_title'] ;
	$data["form_name"] = $config['form_name'] ;
	$data["form_url"] = $config['form_url'] ;
/*
	// ユーザ・パスワードテーブルにセット 2021/07/06 pwc
	userDbInsert($data,$config);
	passwdDbInsert($data,$config);
*/
	// メール生成
	if(isset($GLOBALS['@cancel'])){
	//キャンセルのとき
		if (file_exists($config['mail']['dir'] . $config['mail']['check'])) {
		}else{
			$body = $config['mail_cancel'];
		}
		// 送信先へメール送信
		mailSend($data,$body,$config['mail_subj'],$to,$inputdata['mailadr'],$config);
		// Replyメール送信
		$body = $config{'mail_replymsg'};
		mailSend($data,$body,$config['mail_replysubj'],$reply_to,$inputdata['mailtoadr'],$config);

	}else{
	//キャンセル以外のとき
		if(waitCheck($config,$counters) === true) {
		// キャンセル待ちチェック
			$replybody = $config{'mail_wait'};
			//キャンセル待ちメッセージセット
		}else{
			$body = $config{'mail_msg'};
			$replybody = $config{'mail_replymsg'};
		}
		// 送信先アドレスをセット 2017/10/27 送信先指定無しのとき対応
		$to = empty($config['file_maillist'])? $config['mail_to'] : $data['mailtoadr'] ;
		// 返信先をセット 返信メールがセットされているとき 2019/04/16
		if(!(empty($config['mail_replymsg']))){
			// 送信者リスト無しのときはmail_replytoに指定された宛先を返信先にする（無ければ設定しない） 2017/10/28
			if(empty($config['file_maillist'])){
				$reply_to = isset($config['mail_replyto'])? $data['mailadr'] : null ;
			}else{
				// 送信者リストありのとき 2017/10/28
				// 送信者メールアドレスの入力がないときはTOメール先（管理者）に送る
				$reply_to = isset($data['mailadr'])? $data['mailadr'] : $config['mail_to'] ;
			}
		}
		if($config['flg_cancel']) {
			// キャンセル用URL生成して出力データに
		//	echo($config['formno'] . "-" . $counters['id'] ."-" . $GLOBALS['@msgid']);
		    $cryptid = strCrypt($config['formno'] . "-" . $counters['id'] ."-" . $GLOBALS['@msgid']);
			$cancelurl = $config['form_cancel_url'] . "?id=" . $cryptid;
			$inputdata['cancelurl'] =  $data['cancelurl'] = $cancelurl;
		}
		//2023/11/30 WMSは対応無し $reply_to_cc = $reply_to . (isset($data['mailcc'])? ',' . $data['mailcc']  : "");
		// Cc:は送信者のみするよう変更　2022/01/21
		// 送信先へメール送信
		mailSend($data,$body,$config['mail_subj'],$to,$inputdata['mailadr'],$config);
		// 返信するとき
		if(isset($reply_to)) {
			//Replyメールの返信先指定 2017/11/19
			$rep_replyto = empty($config['file_maillist'])? $config['mail_replyto'] : $data['mailtoadr'] ;
			// Replyメール送信
			//$cc = empty($data['mailcc'])? NULL : $data['mailcc'] ;
			mailSend($data,$replybody,$config['mail_replysubj'],$reply_to,$rep_replyto,$config);
		}
	}

	// カウンタアップデート
	counterDbUpdate($config,$counters,$data,$items);
	
	//ログ出力
	// 複数ファイル対応
	$tmpdata = array();
	foreach($data as $key => $value) {
		if(is_array($value) !== true){
		// 複数ファイル対応 配列でなければtmpに保存
			$tmpdata[$key] = $value;
		}
	}
	
	$log = implode(",",$tmpdata);
//	$log = implode(",",$data);
	logWrite('"complete : ' . $log . '"');

	if(!isset($err)) {
	//正常終了なら確認画面表示
		$data['html_thanksmsg'] = $config['html_thanksmsg'];
		session_off();//セッションオフ
//	var_dump($data);
		if(isset($GLOBALS['@cancel'])){
			htmlPrint('cthanks',$data,$items);
		}else{
		// 正常終了ならユーザ名をクッキーに入れthanks＋ログイン画面へ
			htmlPrint('thanks',$data,$items);
		}
		return(1);
	}else{
		return(0);
	}
}

  
function mailSend($data,$body,$subj,$to,$replyto,$config){

	// メールエンコード設定
	$mailencode = (isset($config['mail_code']) && $config['mail_code'] == 1)? "utf-8" : "iso-2022-jp" ;
	$encode_to = (isset($config['mail_code']) && $config['mail_code'] == 1)? "UTF-8" : "JIS" ;
	mb_language("Japanese");
	mb_internal_encoding($encode_to);
	
	// メール生成
	$mailbody = mailBody($data,$body,$config,$encode_to);
	$header = mailHeader($data,$config,$replyto,$mailencode);
	$subject = mailSubj($subj,$data,$config,$mailencode,$encode_to) ;
	
	// メール送信
	if(strpos($config['form_url'],'localhost') === false){ 
// Return-Path対応　2018/09/10
//	if(mb_send_mail($to,$subject,$mailbody,$header) === false){
	if(mb_send_mail($to,$subject,$mailbody,$header, '-f' . $config['mail_from']) === false){
		// メールログ出力
		maillogWrite("err",$to,$subject,$mailbody,$header);
		errDie('メール送信エラー',2);
	}
	}
/* 2018/10/20 localでダミー送信出来るように
	// メール送信
// Return-Path対応　2018/09/07
//	if(mb_send_mail($to,$subject,$mailbody,$header) === false){
	if(mb_send_mail($to,$subject,$mailbody,$header, '-f' . $config['mail_from']) === false){
		// メールログ出力
		maillogWrite("err",$to,$subject,$mailbody,$header);
		errDie('メール送信エラー',2);
	}
*/
	// メールログ出力
	maillogWrite("ok",$to,$subject,$mailbody,$header);
	
	 
	return ;

}
function mailBody($data,$template,$config,$encode_to){
	
	//出力変数と値のリスト作成
	foreach($data as $key => $value) {
		if(is_array($value) !== true){
		// 複数ファイル対応 配列のときは対象外
			$keys[] = "%$key%";
		// 複数入力区切りの入力値の\0を|に 2017/11/23
			$values[] = str_replace(DELIMITER,",",$value);
		}
	}
	
	$body = str_replace($keys,$values,$template);
	if($encode_to != 'UTF-8') {
		$body = replaceText($body); // 変換できない文字を別の文字に変換
	}

	return mb_convert_encoding($body,$encode_to,"UTF-8");
}

function mailSubj($subj,$data,$config,$mailencode,$encode_to){
//	foreach($data as $key => $value) {
//		$pattern = '/%' . $key . '%/';
//		$replace = $value ;
//		$subj = preg_replace ($pattern, $replace ,$subj );
//	}
	//出力変数と値のリスト作成
	foreach($data as $key => $value) {
		if(is_array($value) !== true){
		// 複数ファイル対応 配列のときは対象外
			$keys[] = "%$key%";
			$values[] = $value;
		}
	}
	$subj = str_replace($keys,$values,$subj);
	if($encode_to != 'UTF-8') {
		$subj = replaceText($subj); // 変換できない文字を別の文字に変換
	}

	return mb_convert_encoding($subj,$encode_to,$config['encode']);
//	return "=?" . $mailencode . "?B?".base64_encode(mb_convert_encoding($subj,$encode_to,$config['encode']))."?=";
}

//ユーザ宛送信メールヘッダ
function mailHeader($data,$config,$replyto,$mailencode){
//	function mailHeader($data,$config,$cc,$replyto,$mailencode){
	$encode = $config['encode'] ;
	if(isset($config['mail_from'])){
		$from = $config['mail_from'];
	}else{
		errDie("Fromアドレス不明");
	}
	
	$header = "From: ";

	$regs = array();
	if(preg_match ('/<.+>/' , $from , $regs ) === true) {
	 // fromアドレス部分の抽出
		$from_adr =  $regs[0];
	}else{
		$from_adr =  $from;
	}
	
	if(preg_match ('/(?!<.+>)/' , $from , $regs ) === true) {
	 // fromアドレス部分以外の抽出
		$from_name = $regs[0];
		$default_internal_encode = mb_internal_encoding();
		if($default_internal_encode != $encode){
			mb_internal_encoding($encode);
		}
		$header .= mb_encode_mimeheader($from_name)." <".$from_adr.">\n" ;
	}else{
		$header .= $from_adr."\n";
	}
	if(isset($replyto)) {
	// replytoを可変にする（管理者宛・Reply宛）
		$header .= "Reply-To: ". $replyto . "\n";
	}
/*	if(isset($cc)) {
		$header .= "Cc: ". $cc . "\n";
	}*/
	if(isset($config['mail_cc'])) {
		$header .= "Cc: ". $config['mail_cc'] . "\n";
	}
	if(isset($config['mail_bcc'])) {
		$header .= "Bcc: ". $config['mail_bcc'] . "\n";
	}
	
	$header .= "Content-Type: text/plain;charset=" . $mailencode . "\n" ;
	$header .= "Content-Transfer-Encoding: " . (($mailencode == 'JIS')? "7bit" : "8bit") . "\n" ;
	$header .= "X-Mailer: PHP/".phpversion();

	if($encode != 'UTF-8') {
		replaceText($header); // 変換できない文字を別の文字に変換
	}
	return $header;
}
// カウントアップ
function counterDbUpdate($config,$counters,$data,$items){

	// 同伴者数の入力があれば取得
	$party = 0;
	foreach($items as $item) {
		$name = $item['input_name'];
		$type = $item['item_type'];
		if($type == PARTY){
			$party = $data["$name"];
			break;
		}
	}
	$id = $counters['id'] + 1;
	$acpt = ($GLOBALS['@cancel'])? $counters['acpt'] : $counters['acpt'] + $party + 1 ;// 当人含める
	$wait = ($GLOBALS['@cancel'])? $counters['wait'] : (waitCheck($config,$counters))? $counters['wait'] + $party + 1 : $counters['wait'] ;
	$cancel = ($GLOBALS['@cancel'])? $counters['cancel'] + $party + 1 : $counters['cancel'] ;// 当人含める

	// PDOパラメータセット
	$param = array();
	$param[':id'] = $id;
	$param[':acpt'] = $acpt;
	$param[':wait'] = $wait;
	$param[':cancel'] = $cancel;
	
	// sql生成
	$sql = "update db_form_counters set " . 
			"id = :id,".
			"acpt = :acpt,".
			"cancel = :cancel,".
			"wait = :wait".
			" where formno = " . $config['formno'] ;

	dbExec($sql,$param,$config);
	return;
}
function inputDbInsert($data,$config){

	// PDOパラメータセット
	$param = array();
	paramSet($data,$param,$config['items_max']);
	//var_dump($param);
	// sql生成
	$sql = "insert into db_form_input_data (" . 
			"formno,".
			"id,".
			"date,".
			"mailid,".
			"mailadr,".
			"mailtoname,".
			"mailtoadr,".
			"countno,".
			"cancel,".
			"canceldate,".
			"waitno,".
			"cancelno,".
			"env_referer,".
			"env_req_uri,".
			"env_remote_addr,".
			"env_remote_host,".
			"env_user_agent," ;

	for($i=1 ; $i<$config['items_max'] ; $i++){
		$sql .= 'in' . sprintf("%03d",$i) . ',' ;
	}
	$sql .= 'in' . sprintf("%03d",$i) . ')' ;
			
	$sql .= " values (" .
			":formno,".
			":id,".
			":date,".
			":mailid,".
			":mailadr,".
			":mailtoname,".
			":mailtoadr,".
			":countno,".
			":cancel,".
			":canceldate,".
			":waitno,".
			":cancelno,".
			":env_referer,".
			":env_req_uri,".
			":env_remote_addr,".
			":env_remote_host,".
			":env_user_agent," ;

	for($i=1 ; $i<$config['items_max'] ; $i++){
		$sql .= ':in' . sprintf("%03d",$i) .',' ;
	}
	$sql .= ':in' . sprintf("%03d",$i) .')' ;

	dbExec($sql,$param,$config);
	
	return;

}

// キャンセル用入力データDB更新処理
function cancelDbUpdate($data,$config){

	// PDOパラメータセット
	$param = array();
	paramSet($data,$param,$config['items_max']);

	// sql生成
	$sql = "update into db_form_input_data set " . 
			"cancel = :cancel,".
			"canceldate = :canceldate,".
			"cancelno = :cancelno" ;
			
	if(isset($data['countno'])) {
		$sql .= "where formno = :formno and mailadr = :mailadr and countno = :countno" ;
	}else{
		$sql .= "where formno = :formno and mailadr = :mailadr" ;
	}

	dbExec($sql,$param,$config);
	
	return;

}

// フォーム入力データ（db_form_input_data）PDOパラメータセット
function paramSet($data,&$r_param,$items_max){
	
	$r_param[':formno'] = $data['formno'];
	$r_param[':id'] = $data['id'];
	$r_param[':date'] = $data['date'];
	$r_param[':mailid'] = isset($data['mailid'])? $data['mailid'] : null ;
	$r_param[':mailadr'] = isset($data['mailadr'])? $data['mailadr'] : null ;
	$r_param[':mailtoname'] = isset($data['mailtoname'])? $data['mailtoname'] : null ;
	$r_param[':mailtoadr'] = isset($data['mailtoadr'])? $data['mailtoadr'] : null ;
	$r_param[':countno'] = isset($data['countno'])? $data['countno'] : null ;
	$r_param[':cancel'] = isset($data['cancel'])? $data['cancel'] : null ;
	$r_param[':canceldate'] = isset($data['canceldate'])? $data['canceldate'] : null ;
	$r_param[':waitno'] = isset($data['waitno'])? $data['waitno'] : null ;
	$r_param[':cancelno'] = isset($data['cancelno'])? $data['cancelno'] : null ;
	$r_param[':env_referer'] = $_SERVER['HTTP_REFERER'];
	$r_param[':env_req_uri'] = $_SERVER['REQUEST_URI'];
	$r_param[':env_remote_addr'] = $_SERVER['REMOTE_ADDR'];
	$r_param[':env_remote_host'] = empty($_SERVER['REMOTE_HOST'])? (gethostbyaddr($_SERVER['REMOTE_ADDR'])) : $_SERVER['REMOTE_HOST'];
	$r_param[':env_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

	for($i=1 ; $i<=$items_max ; $i++){
		$name = 'in' . sprintf("%03d",$i);
		$r_param[":$name"] = isset($data["$name"])? $data["$name"] : null;
	}
	return;

}
/*
function userDbInsert($data,$config){

	// PDOパラメータセット
	$param = array();
	$param[':name'] = empty($data['item04'])?  null : $data['item04'] ;
	$param[':email'] = empty($data['item01'])?  null : $data['item01'] ;
	$param[':industry'] = empty($data['item07'])?  null : $data['item07'] ;
	$param[':type'] = empty($data['item08'])?  null : $data['item08'] ;
	$param[':company_name'] = empty($data['item05'])?  null : $data['item05'] ;
	$param[':pref'] = empty($data['item09'])?  null : $data['item09'] ;
	$param[':address'] = empty($data['item06'])?  null : $data['item06'] ;
	$param[':company_size'] = empty($data['item10'])?  null : $data['item10'] ;
	$param[':date'] = date('Y-m-d H:i:s');
	
	// sql生成
	$sql = "insert into db_user (" . 
			"name,".
			"email,".
			"industry,".
			"type,".
			"company_name,".
			"pref,".
			"address,".
			"company_size,".
			"date)";

	$sql .= " values (" .
			":name,".
			":email,".
			":industry,".
			":type,".
			":company_name,".
			":pref,".
			":address,".
			":company_size," .
			":date)" ;

	dbExec($sql,$param,$config);
	
	return;

}

function passwdDbInsert($data,$config){

	// PDOパラメータセット
	$param = array();
	$param[':email'] = empty($data['item01'])?  null : $data['item01'] ;
	$param[':password'] = empty($data['item02'])?  null : $data['item02'] ;
	
	// sql生成
	$sql = "insert into db_passwd (" . 
			"email,".
			"password)";

	$sql .= " values (" .
			":email,".
			":password)" ;

	dbExec($sql,$param,$config);
	
	return;

}
*/

?>