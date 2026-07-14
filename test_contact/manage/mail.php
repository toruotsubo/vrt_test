<?php
// 汎用フォーム管理者用画面
//
// メール送信先毎管理画面対応 2019/01/13
//
//初期設定
ini_set( 'display_errors', 1 );
if (version_compare(PHP_VERSION, '5.1.0', '>=')) {//PHP5.1.0以上の場合のみタイムゾーンを定義
	date_default_timezone_set('Asia/Tokyo');//タイムゾーンの設定（日本以外の場合には適宜設定ください）
}

include_once "../data/config.php";
include_once "../util.php";
require_once('manageutil.php');

$config = array();
$items = array();
$data = array();
$counters = array();

$ary_maillist = array();

$list_max = 20;

//初期処理
config($config);
mng_define();
$flg_admin = 0; // adminチェック

//ログ出力
logWrite('"mail : start"');

formInit($config,$items,$data,$counters);

//ログ出力
logWrite('"mail : ' . $_SESSION['user'] . '"');
/*
if(!isset($_SESSION['user']) || ($_SESSION['usertype'] != 1)){
// admin属性以外のユーザ
	header("Location:index.html");
}
*/

$user = $_SESSION['user'];
if(isset($_POST['change'])&&($_POST['change'] == 1)){
	$errmsg = (isset($_SESSION['errmsg']))? $_SESSION['errmsg'] : "" ;
}
	$errmsg = (isset($_SESSION['errmsg']))? $_SESSION['errmsg'] : "" ;

$GLOBALS['@formname'] = $config['formname'];
$formname = $config['form_title'];

// メールアドレス数取得
$sql = "SELECT count(*) as count " .
	"FROM `db_maillist` " .
	"WHERE formno = :formno and del_date is null ;";
$param[':formno'] = $config['formno'];

$mailcnt = inputDbCount($sql,$param,$config);

// 最大ページ数取得
$pagemax = floor($mailcnt / $list_max + (($mailcnt % $list_max > 0)? 1 : 0));
$pagemax = ($pagemax == 0)? 1 : $pagemax;
// 現在のページ取得
if(isset($_GET['page'])){
	$page = $_GET['page'];
	if(($page > 1) && ($page > $pagemax)){
	// 不正な値のとき
		$page = 1;
	}
}else{
	$page = 1 ;
}
//print_r($_POST);
// 表示ページ位置取得
$offset = ($page == 1)? 0 : ($page-1) * $list_max ;
$row_count = $list_max ;

// メールアドレスデータ読み込み
$sql = "SELECT no,id,email,facility,case when password is null then '' else password end as password,0 as del FROM db_maillist" . 
//		" WHERE formno = " . $config['formno'] . " and del_date is null order by `id`" . // IDランダム文字列対応
		" WHERE formno = " . $config['formno'] . " and del_date is null order by `no`" . 
		" LIMIT " . $offset . ',' . $row_count ;

$data = array();
$param = array();
$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;
dbSelectTable($config,$sql,$param,$data);

// パスワードデコード
for($i=0 ; $i < count($data) ;$i++){
	if(strlen($data[$i]['password']) > 0) {
		$data[$i]['password'] = strDecrypt($data[$i]['password']);
	}
}


$submit = isset($_POST['submit'])? $_POST['submit'] : null;

if($submit == BTN_LOGOUT){
	logout();
}

if (isset($_POST['StatusF']) && $_POST['StatusF'] !="") {
//更新
	// 追加したときの新しい番号取得
	$param1 = array();
	$temp = array();
	$sql = "SELECT max(`no`)+1 as nextno FROM `db_maillist`";
	dbSelectTable($config,$sql,$param1,$temp);
	$nextno = $temp[0]['nextno'];

	$arr_id = array();
	$i = 0;
	while($i < $_POST['count']){
		$param = array();
		$sql = null;
		$sql2 = null;
		$del = 0;
		$add = 0;
		if((isset($_POST["del".$i]))&&(($_POST["del".$i]) == "1")){
			//削除
			$param[':no'] = $_POST["no".$i];
			$sql="UPDATE `db_maillist` SET `del_date`=now() WHERE `no`= :no";
			dbExec($sql,$param,$config);
//		}elseif((empty($_POST["id".$i]))||(empty($_POST["email".$i]))||(empty($_POST["facility".$i]))||(empty($_POST["password".$i]))){
		}elseif((empty($_POST["id".$i]))||(empty($_POST["email".$i]))||(empty($_POST["facility".$i]))){
			$errmsg = $_POST["id".$i] . " : メールアドレス・送信先が入力されていません。";
			break ;
		}elseif(preg_match('/^M[0-9A-Za-z]{4,9}$/',$_POST["id".$i]) == 0){ 
			$errmsg = "IDは「M」から始まる5文字以上10文字以内の文字列です。（" . $_POST["id".$i] . "）";
			break ;
		}elseif(array_search($_POST["id".$i],$arr_id) !== false){
			$errmsg = "ID（" . $_POST["id".$i] . "）が重複しています。";
			break ;
		}elseif(mb_strlen($_POST["facility".$i], "UTF-8") > 80){
			$errmsg = "送信先名称（" . $_POST["id".$i] . "）は80文字までです。";
			break ;
		
		}elseif(!(empty($_POST["facility".$i])) && (mb_strlen($_POST["password".$i], "UTF-8") > 15)){
			$errmsg = "パスワード（" . $_POST["id".$i] . "）は15文字までです。";
			break ;
		
		}else{
		// 入力OK
			$param[':id'] = $_POST["id".$i] ;
			$param[':email'] = $_POST["email".$i] ;
			$param[':facility'] = $_POST["facility".$i] ;
			if(isset($_POST["password".$i])){
				$param[':password'] = strCrypt($_POST["password".$i]) ;
			}else{
				$param[':password'] = null ;
			}
			
			$errmsg = "" ;
//			print_r($data[$i]);
			if(empty($data)||empty($data[$i])){
			//追加
				$add = $nextno;
				$param[':no'] = $add ;
				$param[':formno'] = $config['formno'] ;
				$sql="INSERT INTO `db_maillist`(`no`, `formno`, `id`, `email`, `facility`, `password`)" . 
					" VALUES (:no,:formno,:id,:email,:facility,:password)";
			}else{
				$param[':no'] = $_POST["no".$i] ;
				// データ更新
				$sql = "UPDATE `db_maillist` SET `id`=:id,`email`=:email,`facility`=:facility,`password`=:password WHERE `no`=:no";
			}
//		echo($sql . "<br>\n");
//		print_r($param);
		dbExec($sql,$param,$config);
		}
		$arr_id[] = $_POST["id".$i];
		if($add){
			$nextno++;
		}
		$i++;
	}
	
	$_SESSION['errmsg'] = (isset($errmsg))? $errmsg : null ;
	header("Location:mail.php?page=$page");
	
}

//更新以外
$submit = isset($_POST['submit'])? $_POST['submit'] : null;

if($submit == BTN_LOGOUT){
	logout();
}

	
$jsonData = json_encode($data);


?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<!-- title -->
  <title>[<?php echo $formname; ?>]管理画面</title>
    <link href="css/handsontable.full.css" rel="stylesheet" type="text/css">
    <script src="js/jquery-1.11.2.min.js"></script>
    <script src="js/handsontable.full.js"></script>
	<link rel="stylesheet" href="css/style.css">
    <script>
        $(window).on('beforeunload', function(event) {
        	if($("#change").val() == 1){
    			return '更新せずに画面移動しますか？';
    		}
    		return;
	    });
    </script>
  </head>
<body>
<!-- header -->
<div id="header">
<img src="imgs/hd_logo.png" />
</div>
<!-- container -->
<div id="container">
	<h1>[<?php echo $formname; ?>]管理画面</h1>
    <form id="form01" action="mail.php?page=<?php echo($page); ?>" method="POST">
    <div id="logout"><input type="submit"  class="submit" name="submit" id="btn_dl" value="<?php echo BTN_LOGOUT; ?>"></div>
<!--    </form> -->
    <?php include("menu.html"); ?>
    <!-- content -->
    <div id="content">
        <div class="item">
            <div class="itemtop">メール送信先管理画面</div>
	            <div class="item-main">
                    <div id="notice">
                      <p>項目を変更したときは忘れずに「データ更新」のボタンを押してください。
                    <div class="errmsg" id="errmsg"><?php if(isset($errmsg)){echo($errmsg);}?></div></p>
                    </div>
                	<div id="list">
<!--                        <form method="post" action="mail.php?page=<?php echo(($page-1)); ?>" id="form01"> -->
                            <?php
                            $i = 0;
                            foreach($data as $value) { ?>
                                <input type="hidden" name="no<?php echo $i; ?>" id="no<?php echo $i; ?>" value="<?php echo $value['no']; ?>">
                                <input type="hidden" name="id<?php echo $i; ?>" id="id<?php echo $i; ?>" value="<?php echo $value['id']; ?>">
                                <input type="hidden" name="email<?php echo $i; ?>" id="email<?php echo $i; ?>" value="<?php echo $value['email']; ?>">
                                <input type="hidden" name="facility<?php echo $i; ?>" id="facility<?php echo $i; ?>" value="<?php echo $value['facility']; ?>">
                                <input type="hidden" name="password<?php echo $i; ?>" id="facility<?php echo $i; ?>" value="<?php echo $value['password']; ?>">
                                <input type="hidden" name="del<?php echo $i; ?>" id="del<?php echo $i; ?>" value="<?php echo $value['del']; ?>">
                            <?php
                            $i++;
                            } ?>
                            <button type="submit" class="submit" id="update" name="StatusF" value="データ更新">データ更新</button>
                            <input type="hidden" name="count" id="count"  value="<?php echo(count($ary_maillist)); ?>">
                            <input type="hidden" id="change"  value="0">
                        </form>
					</div>
                    <div class="row">
						<?php if($page != 1){ ?>
						<a href="./mail.php?page=<?php echo(($page-1)); ?>" class="link_before">前</a>
						<?php } ?>
						<?php for($i=1 ; $i <= $pagemax ; $i++){
								if($i == $page){ ?>
						<span class="current_page"><?php echo($page); ?></span>
						<?php 	}else{ ?>
						<a href="./mail.php?page=<?php echo($i); ?>" class="link_page"><?php echo($i); ?></a>
						<?php 	} ?>
						<?php } ?>
						<?php if($page != $pagemax){ ?>
						<a href="./mail.php?page=<?php echo(($page+1)); ?>" class="link_next">次</a>
						<?php } ?>
						&nbsp;
                    <div id="userTBL"></div>
                    <script>
                      var data=JSON.parse('<?php echo $jsonData; ?>');
                      $("#userTBL").handsontable({
                        rowHeaders: false,
                        colHeaders: true,
                        colHeaders: ['送信先ID', 'メールアドレス','送信先名称（80文字まで）','パスワード（15文字まで）','削除'],
                        columns: [
                          { data: 'id' },
                          { data: 'email' },
                          { data: 'facility', colWidths: 300 },
                          { data: 'password' },
                          { data: 'del',
                          	type: 'checkbox',
                          	checkedTemplate: "1",
                            uncheckedTemplate: "0" ,
                            colWidths: 50 }
                        ],
                        minSpareRows: 1,
                        fillHandle: true, //possible values: true, false, "horizontal", "vertical"
                        onChange: function(changes, source){
                          if (source==='edit'){
                            $("#change").val(1);
							if(document.getElementById(changes[0][1]+changes[0][0]) != null){
                                $("#"+changes[0][1]+changes[0][0]).val(changes[0][3]);
                            }else{
                                $("<input>",{
                                    type: 'hidden',
                                    id: changes[0][1]+changes[0][0],
                                    name: changes[0][1]+changes[0][0],
                                    value: changes[0][3]
                                }).appendTo('#form01');
                            }
							$("#errmsg").text("「データ更新」ボタンを押してください");
                         }
                        }
                      });
                      $("#userTBL").handsontable("loadData", data);
                      $("#form01").submit(function(){
                        $(window).off('beforeunload');
                          countrows = $("#userTBL").handsontable('countRows') - 1;
                          $("#count").val(countrows);
                      });
                    </script>
                    </div>
                    <div id="note">
					<p>送信先IDは「M」から始まる5文字以上10文字以内の英数字です。<br>
						フォームURLのパラメータは先頭「M」を除いた部分となります。<br>
						パスワードで使用可能な文字は英数字・記号（-_[]!#$%&()）です。</p>
                    </div>
				</div>
			</div>
		</div>
<?php include("footer.html"); ?>
</div><!--/container -->
<!-- /content -->
</body>
</html>