<?php
// 汎用フォーム管理画面html
//
// 送信先別CSV出力対応 2019/01/16
//
//初期設定 出力CSVコード
$csvcode = 0; // 0:sjis-win(cp932) / 1:utf-8

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
config($config); // 2017/10/28
mng_define();

//ログ出力
logWrite('"manage : start"');

if(strlen($config['formname']) > 0){
// formnameありのとき（１フォーム管理）
	formInit($config,$items,$data,$counters);
/* 複数フォーム対応　未使用
}else{
	// 複数フォーム対応
	$ary_formname = array();
	$ary_formname = getFormNameTable($config);

	// 個別設定
	if(isset($_REQUEST['formno'])) {
		$formno = $_REQUEST['formno'];
		$confname = '../data/conf_' . $ary_formname["$formno"] . '.php';
	}
	
	if(file_exists($confname)){
		require_once($confname);
		conf_customize($config);
	}
	formInit($config,$items,$data,$counters);
*/
}

//ログ出力
logWrite('"manage : ' . $_SESSION['user'] . '"');

if(!isset($_SESSION['user']) || !isset($_SESSION['usertype'])){
	logout();
	header('Location:./');
}

$user = $_SESSION['user'];
$usertype = $_SESSION['usertype'];

$GLOBALS['@formname'] = $config['formname'];
//print_r($config);

$mailid = (isset($_REQUEST['mailid']))? $_REQUEST['mailid'] : 'ALL' ;

if($usertype == 0){
	if($user != $mailid){
		// 送信先不正
		logout();
		header('Location:./');
	}
}

//各データ取得 カウンタにあるのは次に使う番号なので全部-1する

$formname = $config['form_title'];
/*
$acpt = $counters['acpt'] - 1; //累積受付人数（同伴者含む・キャンセルは除いてない）
$cancel = $counters['cancel'] - 1; //キャンセル人数
$wait = $counters['wait'] - 1; //キャンセル待ち数（同伴者数含む）
$total = $acpt - $cancel; //実受付人数
*/

// 受付数取得 2019/01/16
$data = array();
$param = array();

if($mailid == 'ALL') {
	$sql = "SELECT count(*) as count FROM db_form_input_data" . 
		" WHERE formno = :formno" ;
	$param[':formno'] = $config['formno'] ;

}else{

	// メールアドレスデータ読み込み
	$sql = "SELECT no,id,email,facility,case when password is null then '' else password end as password,0 as del FROM db_maillist" . 
			" WHERE formno = :formno and id = :mailid and del_date is null";

	$data = array();
	$param = array();
	$param[':formno'] = $config['formno'] ;
	$param[':mailid'] = $mailid ;

	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;
	dbSelectTable($config,$sql,$param,$data);
	$facility = $data[0]['facility'];
	$email = $data[0]['email'];

	$data = array();
	$param = array();
	$sql = "SELECT count(*) as count FROM db_form_input_data" . 
		" WHERE formno = :formno and in006 = :mailid";
	$param[':formno'] = $config['formno'] ;
	$param[':mailid'] = $mailid ;
}
dbSelectTable($config,$sql,$param,$data);
$total = $data[0]['count'];


//受付終了日時
$close_date = isset($config['close_date'])? $config['close_date'] : "";
$close_ymd = isset($config['close_date'])? date('Y/m/d',strtotime($config['close_date'])) : date('Y/m/d');
$close_hh = isset($config['close_date'])? date('H',strtotime($close_date)) : "00";
$close_ii = isset($config['close_date'])? date('i',strtotime($close_date)) : "00";
$no_close = isset($config['close_date'])? "false" :"true"; // 期限日時が入っているときは期限無しのチェックオフ

//最大数
$cnt_max = isset($config['cnt_max'])? $config['cnt_max'] : "";
$no_max = (isset($config['flg_count']) && $config['flg_count'] == 1)? "false" : "true" ;

// 日付の設定
$today = date('Y/m/d H:i:s');

$submit = isset($_POST['submit'])? $_POST['submit'] : null;
$errmsg1 = null;
$errmsg2 = null;

switch($submit) {
	case BTN_DL :
		if(isset($_POST['csvstart'])) {
			$csvstart = $_POST['csvstart'];
		}
		if(isset($_POST['csvend']))
		{
			$csvend = $_POST['csvend'];
		}
		if(csvDownload($config,$items,$csvcode,$csvstart,$csvend,$errmsg,$mailid)){
			exit;
		}
		break;
	case BTN_UD :
		if(limitUpdate($config,$errmsg1,$errmsg2)) {
			$close_date = isset($config['close_date'])? $config['close_date'] : "";
			$cnt_max = isset($config['cnt_max'])? $config['cnt_max'] : "";
			$no_max = (isset($config['flg_count']) && $config['flg_count'] == 1)? "" :  'checked="checked"' ;
			header("Location: " . $_SERVER['SCRIPT_NAME']);
		}
		break;
	default :
		break;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<!-- title -->
  <title>[<?php echo $formname; ?>]管理画面</title>
<!-- meta -->
<meta name="Description" content="" />
<meta name="Keywords" content="" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<script type="text/javascript" src="js/jquery-1.11.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery-ui/datepicker-ja.js"></script>
<script type="text/javascript" src="js/jquery-ui/wickedpicker.js"></script>
<link rel="stylesheet" href="js/jquery-ui/jquery-ui.css" >
<link rel="stylesheet" href="js/jquery-ui/wickedpicker.css" >
<link rel="stylesheet" href="css/style.css">
<script>
  $(function() {
//  $("#datepicker").datepicker();
//	$("#datepicker").datepicker("setDate", "<?php echo $close_ymd; ?>");
//	$("#datepicker").datepicker("option", "showOn");

    $("#datepicker1").datepicker();
	$("#datepicker1").datepicker("setDate", "");
	$("#datepicker1").datepicker("option", "showOn");
    $("#datepicker2").datepicker();
	$("#datepicker2").datepicker("setDate", "");
	$("#datepicker2").datepicker("option", "showOn");
/**
	$("#timepicker").wickedpicker({
		now: "<?php echo $close_hh; ?>:<?php echo $close_ii; ?>",
		twentyFour: true,
		title : ''
	});
	disable_closedate(<?php echo $no_close; ?> );
	disable_max(<?php echo $no_max; ?>);
**/
  });
/**  function disable_closedate(ischecked){
	  document.getElementById('nolimit').checked = ischecked;
	  if(ischecked == true) {
		  document.getElementById('datepicker').disabled = true;
		  document.getElementById('timepicker').disabled = true;
		  document.getElementById('datepicker').value = '';
		  document.getElementById('timepicker').value = '';
	  }else{
		  document.getElementById('datepicker').disabled = false;
		  document.getElementById('timepicker').disabled = false;
	  }
  }
  function disable_max(ischecked){
	  document.getElementById('nomax').checked = ischecked;
	  if(ischecked == true) {
		  document.getElementById('max').disabled = true;
		  document.getElementById('max').value = '';
	  }else{
		  document.getElementById('max').disabled = false;
	  }
  }
 **/
</script>
</head>
<body>
<!-- header -->
<div id="header">
<img src="imgs/hd_logo.png" />
</div>
<!-- container -->
<div id="container">
	<h1>[<?php echo $formname ; ?>]管理画面</h1>

<?php include("header.html"); ?>
<?php include("menu.html"); ?>
<!-- content -->
<form id="FormName" action="manage.php" method="POST">
    <input type="hidden" name="formno" value="<?php echo $config['formno']; ?>">
<div id="content">
<div class="item">
<div class="itemtop">受信データダウンロード（CSV形式）</div>
<div class="item-main">
<div class="manage-item">
<p>受信メールアドレス&nbsp;&nbsp;：&nbsp;&nbsp;
    <?php if(isset($user) && $usertype == 1) {
      // adminユーザのとき ?>
      <select name="mailid" class="select">
      	<?php echo(selFormOption($config,$mailid,$usertype)); ?>
      </select>
    <?php }else{ 
    	echo($facility . " （" . $email . "）"); ?>
    	<input type="hidden" name="mailid" value="<?php echo($mailid); ?>">
   <?php  } ?>
    </p>
<p>期日指定したいとき&nbsp;&nbsp;：&nbsp;&nbsp;
  <input type="text" id="datepicker1" name="csvstart" > ～ <input type="text" id="datepicker2" name="csvend" ></p>
<p>  <input type="submit" name="submit" class="submit" id="btn_dl" value="<?php echo BTN_DL; ?>">　※一旦「ファイルを保存」してから開いてください</p>
  <span class="errmsg"><?php echo isset($errmsg)? $errmsg : '' ; ?></span>
</div>
</div>
</div> 
<div class="itemtop">問い合わせ概要</div>
<div class="item-main">
<p>問い合わせ受付件数：<?php echo $total; ?>件</p>
<!--（累積受付人数：<?php echo $acpt; ?>名・・・キャンセル分を含む）<br>
（累積キャンセル数：<?php echo $cancel; ?>名）</p>
<p>累積キャンセル待ち数：<?php echo $wait; ?>名</p>
<table width="500" border="0" cellspacing="0" cellpadding="0">
  <tbody>
    <tr>
      <th width="182" height="30" scope="row"><div align="left">受付最終期限日時</div></th>
      <td width="318">
      <input type="text" id="datepicker" name="closeday" >
      </td>
    </tr>
    <tr>
      <th height="26" scope="row"><div align="left"></div></th>
      <td><div align="left">
	    <input type="text" id="timepicker" name="closetime" value="<?php echo $close_hh . ":" . $close_ii; ?>" width="10"/>
        <input type="checkbox" name="nolimit" id="nolimit" value="1" onclick="disable_closedate(this.checked);" >
        <label for="nolimit">期限無し</label>
      </div>
      <span style="color:red"><?php echo isset($errmsg1)? $errmsg1 : '' ; ?></span>
</td>
    </tr>
    <tr>
      <th height="26" scope="row"><div align="left">定員</div></th>
      <td><div align="left">
        <input type="text" name="max" id="max" size="10" value="<?php echo $cnt_max; ?>">
        <label for="max">人</label>
        <input type="checkbox" name="nomax" id="nomax" value="1"  onclick="disable_max(this.checked);">
        <label for="nolimit">定員無し</label>
      </div>
            <span style="color:red"><?php echo isset($errmsg2)? $errmsg2 : '' ; ?></span>
</td>
    </tr>
    <tr>
      <th height="42" colspan="2" scope="row"><div align="center">
        <input type="submit"  class="submit"  name="submit" id="btn_update" value="<?php echo BTN_UD; ?>">
      </div>        
      <div align="left"></div></th>
    </tr>
  </tbody>
</table>-->
        <input type="hidden" name="user" value="<?php echo $user ?>">
        <input type="hidden" name="msgid" value="<?php echo $msgid ?>">
</form>
</div>
</div>
<!-- /content -->
<?php include("footer.html"); ?>
</div><!--/container -->
</body>
</html>


