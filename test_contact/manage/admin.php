<?php
// 汎用フォーム管理者用画面
//
// 複数フォーム対応 2018/02/12
//
//初期設定
ini_set( 'display_errors', 1 );
if (version_compare(PHP_VERSION, '5.1.0', '>=')) {//PHP5.1.0以上の場合のみタイムゾーンを定義
	date_default_timezone_set('Asia/Tokyo');//タイムゾーンの設定（日本以外の場合には適宜設定ください）
}

include_once "../data/config.php";
include_once "../util.php";
require_once('../html.php');
require_once('manageutil.php');

$config = array();
$items = array();
$data = array();
$counters = array();

$ary_user = array();
$flg_admin = 0; // adminチェック
//初期処理
config($config);
mng_define();
//ログ出力
logWrite('"admin : start"');

if(strlen($config['formname']) > 0){
// formnameありのとき（１フォーム管理）
	formInit($config,$items,$data,$counters);

	$sql = "SELECT id,db_manage_user.formno,case when db_manage_user.formno = 0 then 'ALL' else db_form_setting.form_name end as form_name,user,password " .
	       "FROM db_manage_user LEFT OUTER JOIN db_form_setting ON db_manage_user.formno = db_form_setting.formno where db_manage_user.formno in (0,?);";
}else{
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
	
/* 複数フォーム対応
	$sql = "SELECT id,db_manage_user.formno,case when db_manage_user.formno = 0 then 'ALL' else db_form_setting.form_name end as form_name,user,password " .
	       "FROM db_manage_user LEFT OUTER JOIN db_form_setting ON db_manage_user.formno = db_form_setting.formno;";
*/
	$sql = "select * from db_manage_user where formno=?";

}

//ログ出力
logWrite('"admin : ' . $_SESSION['user'] . '"');
if(!isset($_SESSION['user']) || ($_SESSION['user'] <> 'admin')){
// admin以外のユーザ
	header("Location:index.html");
}

$user = $_SESSION['user'];
if(isset($_POST['change'])&&($_POST['change'] == 1)){
	$errmsg = (isset($_SESSION['errmsg']))? $_SESSION['errmsg'] : "" ;
}

$GLOBALS['@formname'] = $config['formname'];
$formname = $config['formname'];

$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;
//ログ出力
logWrite('"db : ' . $sql . '"');

try {
	$dbh = new PDO($db, $config['dbuser'], $config['dbpasswd']);
	//$dbh->query('SET NAMES utf8');
	//print('接続に成功しました。<br>');
	// フォーム設定取得
	$stmt = $dbh->prepare($sql);
	$formno = (strlen($config['formname']) > 0)? $config['formno'] : null ;
	$stmt->bindParam(1, $formno);
	$stmt->execute();
	while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
		if(($result['user'] == 'admin')&&(isset($result['password']))){
			$flg_admin = 1;
		}
		$result['password'] = strDecrypt($result['password']);
		$ary_user[] = $result;
	}
	$jsonData = json_encode($ary_user);
}catch(PDOException $e){
//		errDie('form_init MySql:'.$e->getMessage());
	echo('<pre>' . var_dump($e->getMessage()) .'</pre>');
	die;
}

if($flg_admin <> 1){
	$errmsg = "adminパスワードが設定されていません。";
}

$submit = isset($_POST['submit'])? $_POST['submit'] : null;

if (isset($_POST['StatusF']) && $_POST['StatusF'] !="") {
	$ary_form = array();
	$ary_form = getFormNoTable($config);
	$i = 0;
	while($i < $_POST['count']){
		// PDOパラメータセット
		$param = array();
		if((isset($_POST["id".$i]))&&(empty($_POST["user".$i]))&&(empty($_POST["password".$i]))){
			$param[':id'] = $_POST["id".$i];
			$sql="delete from `db_manage_user` where id = :id";
		}elseif((empty($_POST["user".$i]))||(empty($_POST["password".$i]))){
			$errmsg = 'ユーザ名とパスワードを正しく入力してください。';
			$i++;
			continue;
		}else{
			$param[':user'] = $_POST["user".$i] ;
			$param[':password'] = strCrypt($_POST["password".$i]);
			if(empty($ary_user)||empty($ary_user[$i])){
				$param[':formno'] = $config['formno'];
//				$param[':formno'] = ($_POST["user".$i] == 'admin')? 0 : $config['formno'];
//				$param[':formno'] = $ary_form[$_POST["form_name".$i]]; // admin以外の共通ユーザ対応 20180219
				$sql="insert into `db_manage_user`(`formno`, `user`, `password`) values ( :formno , :user , :password )";
			}else{
				$param[':id'] = $ary_user[$i]['id'];
				$sql="update db_manage_user set user = :user, password = :password where id = :id";
			}
		}
		dbExec($sql,$param,$config);
		$i++;
	}
	$_SESSION['errmsg'] = (isset($errmsg))? $errmsg : null ;
	
	header('Location:admin.php');
/*
	if(!empty($_REQUEST['formno'])){
		header('Location:admin.php?formno=' . $_REQUEST['formno']);

	}else{
		header('Location:admin.php');
	}
*/
}
function setOption($config){
//フォーム名・フォームタイトル呼び出し
	$data = array();
	$param = array();
	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;
	$sql = "SELECT `form_name` FROM `db_form_setting` where `form_delete` is null order by `formno`";
	dbSelectTable($config,$sql,$param,$data);
	// option値生成
	$buf = "'ALL'";
	foreach($data as $form) {
		$name = $form['form_name'];
		$buf = $buf . ",'" .$name . "'";
	}
	
	return($buf);

}

?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<!-- title -->
  <title>問い合わせフォーム管理画面</title>
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
	<h1>問い合わせフォーム管理画面</h1>
    <?php include("header.html"); ?>
    <?php include("menu.html"); ?>
    <!-- content -->
    <form id="form01" action="admin.php" method="POST">
    <input type="hidden" name="formno" value="<?php echo $_REQUEST['formno']; ?>">
    <div id="content">
        <div class="item">
            <div class="itemtop">ユーザ管理画面</div>
	            <div class="item-main">
                    <div id="notice">
                      <p>項目を変更したときは忘れずに「データ更新」のボタンを押してください。</p>
                      <p>ユーザ・パスワード両方を削除したときはデータ削除となります。</p>
                    </div>
                	<div id="list">
<!--                        <form method="post" action="admin.php" id="form01"> -->
                            <?php
                            $i = 0;
                            foreach($ary_user as $value) { ?>
                                <input type="hidden" name="id<?php echo $i; ?>" id="id<?php echo $i; ?>" value="<?php echo $value['id']; ?>">
                                <input type="hidden" name="user<?php echo $i; ?>" id="user<?php echo $i; ?>" value="<?php echo $value['user']; ?>">
                                <input type="hidden" name="password<?php echo $i; ?>" id="password<?php echo $i; ?>" value="<?php echo $value['password']; ?>">
                            <?php
                            $i++;
                            } ?>
                            <button type="submit" class="submit" id="update" name="StatusF" value="データ更新">データ更新</button>
                            <input type="hidden" name="count" id="count"  value="<?php echo(count($ary_user)); ?>">
                            <input type="hidden" id="change"  value="0">
                    <span class="errmsg" id="errmsg"><?php if(isset($errmsg)){echo($errmsg);}?></span>
                        </form>
					</div>
                    <div class="row">
                    <div id="userTBL"></div>
                    <script>
                      var data=JSON.parse('<?php echo $jsonData; ?>');
                      $("#userTBL").handsontable({
                        rowHeaders: false,
                        colHeaders: true,
                        colHeaders: ['ユーザ名', 'パスワード'],
                        columns: [
                          { data: 'user' },
                          { data: 'password' }
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
				</div>
			</div>
		</div>
<?php include("footer.html"); ?>
</div><!--/container -->
<!-- /content -->
</body>
</html>