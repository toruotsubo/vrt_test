<?php
// 汎用フォーム管理者用画面
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
$str_maillist = "";
$flg_admin = 0; // adminチェック
//初期処理
config($config);
mng_define();

//ログ出力
logWrite('"maillist : start"');

formInit($config,$items,$data,$counters);
//ログ出力
logWrite('"maillist : ' . $_SESSION['user'] . '"');

//print_r($_SESSION);

/*
if(!isset($_SESSION['user']) || ($_SESSION['user'] <> 'admin')){
// admin以外のユーザ
header("Location:index.html");
}
*/

$user = $_SESSION['user'];
if(isset($_POST['change'])&&($_POST['change'] == 1)){
	$errmsg = (isset($_SESSION['errmsg']))? $_SESSION['errmsg'] : "" ;
}


$GLOBALS['@formname'] = $config['formname'];
$formname = $config['form_title'];

// メールアドレスファイル読み込み

$filename = "../" . $config['file_maillist'];

//ログ出力
logWrite('"maillist : ' . $filename . '"');


$submit = isset($_POST['submit'])? $_POST['submit'] : null;

if($submit == BTN_LOGOUT){
	logout();
}

if (isset($_POST['StatusF']) && $_POST['StatusF'] !="") {
//更新
	$arr_id = array();
	$i = 0;
	while($i < $_POST['count']){
		if((isset($_POST["del".$i]))&&(($_POST["del".$i]) == "1")){
			//削除
			$i++;
			continue;
		}elseif((empty($_POST["id".$i]))||(empty($_POST["email".$i]))||(empty($_POST["facility".$i]))){
			$errmsg = "データを正しく入力してください。";
			break ;
		}elseif(array_search($_POST["id".$i],$arr_id) !== false){
			$errmsg = "ID（" . $_POST["id".$i] . "）が重複しています。";
			break ;
		}else{
			$str_maillist .= $_POST["id".$i] . ',' . $_POST["email".$i] . ',' . $_POST["facility".$i]  . ",\r\n" ;
			$errmsg = "" ;
		}
		$arr_id[] = $_POST["id".$i];
		$i++;
	}
	
	if(file_put_contents($filename, $str_maillist) === false){
		errDie("$filename 書き込みエラー");
	}

	$_SESSION['errmsg'] = (isset($errmsg)? $errmsg : "") ;
	
//	header("Location:mail.php");
	
}

//更新以外
	$submit = isset($_POST['submit'])? $_POST['submit'] : null;

	if($submit == BTN_LOGOUT){
		logout();
	}
	
	if (file_exists($filename)){ 
		if (($file = fopen($filename,"r")) == NULL) {
			die("ファイル読み込みエラー");
		}
	} else {
	 die("ファイルが存在しません。");
	}
	
	$bomchk = 1 ; // BOMチェックフラグ

	while(!feof($file)){
		$array = array();
		$line = rtrim(fgets($file));
		if($bomchk){
			// 1行目はBOMチェック
			if(preg_match('/^[\x0x\xef][\x0x\xbb][\x0x\xbf]/', $line)) {
		    	// BOM付きのときはBOM無しへ
		    	$line = substr($line, 3);
		    }
			$bomchk = 0 ;
		}
		$array = explode(",", $line);
		if(isset($array[0]) && strlen($array[0]) > 0) {
			$ary_maillist[] = array(
					'id'=>$array[0],
					'email'=>$array[1],
					'facility'=>$array[2],
					'del' => '0'
				);
		}

	}
	fclose($file);

	$jsonData = json_encode($ary_maillist);


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
<img src="/assets/imgs/logo01.svg" />
</div>
<!-- container -->
<div id="container">
	<h1>[<?php echo $formname; ?>]管理画面</h1>
    <form id="form01" action="mail.php" method="POST">
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
<!--                        <form method="post" action="mail.php" id="form01"> -->
                            <?php
                            $i = 0;
                            foreach($ary_maillist as $value) { ?>
                                <input type="hidden" name="id<?php echo $i; ?>" id="id<?php echo $i; ?>" value="<?php echo $value['id']; ?>">
                                <input type="hidden" name="email<?php echo $i; ?>" id="email<?php echo $i; ?>" value="<?php echo $value['email']; ?>">
                                <input type="hidden" name="facility<?php echo $i; ?>" id="facility<?php echo $i; ?>" value="<?php echo $value['facility']; ?>">
                                <input type="hidden" name="del<?php echo $i; ?>" id="del<?php echo $i; ?>" value="<?php echo $value['del']; ?>">
                            <?php
                            $i++;
                            } ?>
                            <button type="submit" class="submit" id="update" name="StatusF" value="データ更新">データ更新</button>
                            <input type="hidden" name="count" id="count"  value="<?php echo(count($ary_maillist)); ?>">
                            <input type="hidden" id="change"  value="0">
                        </form>
					</div>
					<br />
                    <div class="row">
                    <div id="userTBL"></div>
                    <script>
                      var data=JSON.parse('<?php echo $jsonData; ?>');
                      $("#userTBL").handsontable({
                        rowHeaders: false,
                        colHeaders: true,
                        colHeaders: ['送信先ID', 'メールアドレス','送信先名称','削除'],
                        columns: [
                          { data: 'id' },
                          { data: 'email' },
                          { data: 'facility' },
                          { data: 'del',
                          	type: 'checkbox',
                          	checkedTemplate: "1",
                            uncheckedTemplate: "0" }
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