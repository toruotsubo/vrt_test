<?php
function mng_define(){
define ("BTN_LOGIN",'ログイン');
define ("BTN_LOGOUT",'ログアウト');
define ("BTN_FORMSEL",'フォームを選択');

define ("BTN_DL",'ダウンロード');
define ("BTN_UD",'修　正');
define ("BTN_USERUD",'データ更新');

return;
}
function csvDownload($config,$items,$csvcode,$csvstart,$csvend,&$r_errmsg){
	// 取得範囲入力チェック
//ログ出力
logWrite('"csvdl : start"');
	$err = 0;
	if(!empty($csvstart)) {
		if(preg_match('/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/',trim($csvstart))){
			$arrdate = array();
			$arrdate = explode('/',$csvstart);
			if(!checkdate($arrdate[1],$arrdate[2],$arrdate[0])){
				$err = 1;
			}
		}else{
			$err = 1;
		}
	}
	if(!empty($csvend)) {
		if(preg_match('/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/',trim($csvend))){
			$arrdate = array();
			$arrdate = explode('/',$csvend);
			if(!checkdate($arrdate[1],$arrdate[2],$arrdate[0])){
				$err = 1;
			}
		}else{
			$err = 1;
		}
	}
	if($err == 1){
		$r_errmsg = '日時を正しく入力してください。';
		return false;
	}

	//ログ出力
	logWrite('"csvdl : ' . $csvstart . ' - ' . $csvend . '"');

	//submitボタンでダウンロード
	mb_http_output("pass");
	header("Cache-Control: public");
	header("Pragma: public");
//    header("Content-Type: application/octet-stream");
//debug    $filename = 'form' . $config['formname'] . '_' . date("Ymd_His") . '.csv';
    $filename = 'form' . $config['formname'] . '.csv';
    header("Content-Disposition: attachment; filename=$filename");
	//取得データから入力データを取得
	$sql = "select * from db_form_input_data where formno=:formno";
	$params = array();
	$params[':formno'] = $config['formno'];
	if(!empty($csvstart)) {
		$sql = $sql . ' and date >= :csvstart' ;
		$params[':csvstart'] = $csvstart;
	}
	if(!empty($csvend)) {
		$sql = $sql . ' and date < :csvend' ;
		$params[':csvend'] = date("Y/m/d", strtotime($csvend . " 1 day"));
	}
//	echo "sql = $sql\n";
	$sql = $sql . " order by countno" ;
	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;

//	$buf = '"No","キャンセル","キャンセル待ち",';
//	$buf = '"No","MSGID","送信時間","問い合わせ先","問い合わせ先メールアドレス","';
	$buf = '"No","MSGID","送信時間","キャンセル","キャンセル受付日時","';
//	$buf = '"No","MSGID","送信時間","キャンセル","キャンセル受付日時","支払額","決済日時","';
	foreach($items as $item) {
		if($item['name'] == 'Eメールアドレス2'){
			continue;
		}
		if (isset($item['multi_sel_items'])&&(!empty($item['multi_sel_items']))){
			//複数選択のときは順番通りに
	//echo "multi_sel_items" . $item['multi_sel_items'] . "<br>\n";
			$list = array();
			$list = explode(',',$item['multi_sel_items']); // 選択項目リストを配列に
			foreach($list as $dd){
				$buf .= $item['name'] . ":$dd" . '","' ;
			}
		}else{
			$buf .= $item['name'] . '","' ;
		}
	}
	$buf .= "\"\n";
	//ログ出力
	logWrite('"db : ' . $sql . '"');

	// MySQLに接続
	try {
		$dbh = new PDO($db, $config['dbuser'], $config['dbpasswd']);
#		$dbh->query('SET NAMES utf8');
		//print('接続に成功しました。<br>');
		$stmt = $dbh->prepare($sql);
		$stmt->execute($params);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	        $buf .= "\"" . 
	        	$row["countno"] . '","'  . 
	        	$row["id"] . '","'  . 
	        	$row["date"] . '","'  .
//	        	$row["date"] . '","'  . 
//	        	$row["mailtoname"] . '","'  . 
//	        	$row["mailtoadr"] . '","'  
	        	($row["cancel"]? 'キャンセル受付' : ($row["canceldate"]? 'キャンセル済:' . " 受付No." . $row["cancelno"] : "")) . '","'  . 
	        	($row["canceldate"]? $row["canceldate"] : "") . '","' ;
//	        	($row["canceldate"]? 'キャンセル済:' .date("Y/m/d H:i",$row["canceldate"]) . " 受付No.:" . $row["cancelno"] : "") . '","' ; 
//	        	($row["waitno"]? 'キャンセル待ち:$row["waitno"]' : "") . '","'  ;
//	        	($row["payment"]? $row["payment"] : "") . '","' .
//	        	($row["pay_date"]? $row["pay_date"] : "") . '","' ; 
        	foreach($items as $item) {
				$name = $item['input_name'];
				$type = $item['item_type'];
				if($item['name'] == 'Eメールアドレス2'){
					continue;
				}
				$no = 'in' . sprintf('%03d',$item['input_no']);
				//echo "$name $type $no <br>";
				if (isset($row["$no"])){
				//echo "$name $type $no " . $row["$no"] . "<br>\n";
					//入力データあり
					if (isset($item['multi_sel_items'])&&(!empty($item['multi_sel_items']))){
						//複数選択のときは順番通りに
				//echo "multi_sel_items" . $item['multi_sel_items'] . "<br>\n";
						$list = array();
						$list = explode(',',$item['multi_sel_items']); // 選択項目リストを配列に
						$indata = array();
						$indata = explode(DELIMITER,$row["$no"]);// 入力値リストを配列に
						foreach($list as $item){
							$ck = 0;
							foreach($indata as $dd){
				//echo "item = $item  indata = $indata<br>\n";
								if($dd == $item) {
									$buf .= $dd . '","' ;
									$ck = 1;
								}
							}
							if($ck == 0){
								$buf .= '","' ;
							}
						}
					}else{
				//echo "ふつう" . $item['multi_sel_items'] . "<br>\n";
						//通常入力項目
						$buf .= $row["$no"] . '","' ;
					}
				}else{
					//入力なし
					if (isset($item['multi_sel_items'])&&(!empty($item['multi_sel_items']))){
						//複数選択のときは順番通りに
				//echo "multi_sel_items" . $item['multi_sel_items'] . "<br>\n";
						$list = array();
						$list = explode(',',$item['multi_sel_items']); // 選択項目リストを配列に
						foreach($list as $item){
							$buf .= '","' ;
						}
					}else{
						$buf .= '","' ;
					}
				}
			}
	        $buf .= "\"\n";
		}

	}catch(PDOException $e){
		echo('<pre>' . var_dump($e->getMessage()) .'</pre>');
		exit;
	}
	
    print ($csvcode)? $buf : mb_convert_encoding($buf, 'sjis-win','utf8');
	return true;
}

function limitUpdate($r_config,&$r_errmsg1,&$r_errmsg2){
	
	//ログ出力
	logWrite('"limitupdate : start"');

	$closeday = !empty($_POST['closeday'])? $_POST['closeday']    : null ;
	$closetime = !empty($_POST['closetime'])? $_POST['closetime'] : null ;
	$nolimit = !empty($_POST['nolimit'])? $_POST['nolimit'] : null ;
	$max =     !empty($_POST['max'])?     $_POST['max']     : null ;
	$nomax =   !empty($_POST['nomax'])?   $_POST['nomax']   : null ;
	
	$err = 0 ;
	$errmsg = null;
	if(!empty($closeday)){
		if($nolimit == 1){
			$closeday = null;
		}else{
			if(preg_match('/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/',trim($closeday))){
				$arrdate = array();
				$arrdate = explode('/',$closeday);
				if(checkdate($arrdate[1],$arrdate[2],$arrdate[0])){
					if(isset($closetime)) {
						if(preg_match('/^([01][0-9]|2[0-3])(\s*:\s*)([0-5][0-9])$/',trim($closetime))){
							$arrtime = array();
							$arrtime = explode(':',$closetime);
							if(((int)$arrtime[0] < 0)&&((int)$arrtime[0] > 23)||
							   ((int)$arrtime[1] < 0)&&((int)$arrtime[1] > 59)){
								$err = 1;
							}else{
								$closetime = sprintf('%02d',(int)$arrtime[0]) . ':' . sprintf('%02d',(int)$arrtime[1]);
							}
						}else{
							$err = 1;
						}
					}else{
						$closetime = '00:00';
					}
				}else{
					$err = 1;
				}
			}else{
				$err = 1;
			}
			if($err) {
				$r_errmsg1 = '日時を正しく入力してください。';
			}else{
				$close_date = date('Y-m-d',strtotime($closeday)) .' ' . $closetime . ':00' ;
			}
		}
	}else{
		$nolimit = 1;
	}
	
	if(isset($max)){
		if($nomax == 1) {
			$max = null;
		}elseif(preg_match('/^[0-9]+$/',trim($max))){
			$max = trim($max);
		}else{
			$r_errmsg2 = '定員を正しく入力してください。';
		}
	}else{
		$nomax = 1;
	}
	if(isset($r_errmsg1) || isset($r_errmsg2)) {
		return false;
	}
	// PDOパラメータセット
	$param = array();
	if(!isset($r_errmsg1)) {
		$param[':close_date'] = isset($close_date)? $close_date : null ;
	}else{
		$param[':close_date'] = $r_config['close_date'];
	}
	if(!isset($r_errmsg2)) {
		$param[':flg_count'] = ($nomax)? 0 : 1;
		$param[':cnt_max'] = isset($max)? $max : null ;
	}else{
		$param[':flg_count'] = $r_config['flg_count'];
		$param[':cnt_max'] = $r_config['cnt_max'];
	}
	// sql生成
	$sql = "update db_form_setting set " . 
			"flg_count = :flg_count,".
			"cnt_max = :cnt_max,".
			"close_date = :close_date".
			" where formno = " . $r_config['formno'] ;
	//ログ出力
	logWrite('"db : ' . $sql . '"');

	//SQL実行
	dbExec($sql,$param,$r_config);

	//各データ再取得
	$sql = "select * from db_form_setting where formno=:formno";
	$params = array();
	$params[':formno'] = $r_config['formno'];
	dbSelectOne($r_config,$sql,$params,$r_config);

	return(true);
}

// セッション開始処理
function mng_sessionStart(){

    global $config;

    if((isset($_POST['formname']))&&(empty($_COOKIE['ON']))) {	// ブラウザがクッキー拒否なら
		errDie('ブラウザのクッキーを設定してください。',1);
    }

	$sname = 'SID' . $config['formname'];
	// セッション名セット
	session_name($sname);
	$sid = isset($_REQUEST[session_name()]) ? $_REQUEST[session_name()] : '';
	$sid = session_id();
	if(((!isset($GLOBALS{'@user'})) && empty($sid)) ||
	    file_exists(session_save_path() . DIRECTORY_SEPARATOR . 'sess_' . $sid)){ 
	// 完了処理以外でセッションIDが空か
	// セッションIDに対するセッションファイルがあればセッション開始/更新
		mng_session_on();
	}else{
		die('セッションエラー 1');
	}

	return;
}

function logout(){
	session_off();
	//ログ出力
	logWrite('"logout : ' . $_POST['user'] . '"');
//	echo("session name = " . session_name() .  "<br>\nSESSION = " . print_r($_SESSION) . "<br>\nexist_sid = " . exist_sid());
	
	header("Location:./");
}


function selFormOption($config){
//フォーム名・フォームタイトル呼び出し
	$data = array();
	$param = array();
	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;
	$sql = "SELECT `form_name`,`form_title` FROM `db_form_setting` where `form_delete` is null order by `formno`";
	dbSelectTable($config,$sql,$param,$data);
	// option値生成
	$buf = null;
	foreach($data as $form) {
		$name = $form['form_name'];
		$title = $form['form_title'];
		$selected = ($config['formname'] == $name)? 'selected="selected"' : null ;
		$buf = $buf . "<option value=\"$name\" $selected >$title</option>\n";
	}
	
	return($buf);

}

function getFormNoTable($config){
//formno・formnameリスト取得
	$result = array();
	$data =array();
	$formlist =array();
	$param = array();
	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;
	$sql = "SELECT `form_name`,`formno` FROM `db_form_setting` where `form_delete` is null order by `formno`";
	dbSelectTable($config,$sql,$param,$result);
	foreach($result as $data){
		$key = $data['form_name'];
		$value = $data['formno'];
		$formlist["$key"] = $value;
	}
	$formlist['ALL'] = 0;
	return($formlist);

}

function getFormNameTable($config){
//formno・formnameリスト取得
	$result = array();
	$data =array();
	$formlist =array();
	$param = array();
	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;
	$sql = "SELECT `form_name`,`formno` FROM `db_form_setting` where `form_delete` is null order by `formno`";
	dbSelectTable($config,$sql,$param,$result);
	foreach($result as $data){
		$key = $data['formno'];
		$value = $data['form_name'];
		$formlist["$key"] = $value;
	}
//	$formlist['ALL'] = 0;
	return($formlist);

}

function getFormUserTable($config,$username){
//formno・formnameリスト取得
	$result = array();
	$data =array();
	$ret = null;
	$param = array();
	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;
	$param[":user"] = $username ;
	$sql = "SELECT `formno` FROM `db_manage_user` where user=:user";
	dbSelectTable($config,$sql,$param,$result);
	foreach($result as $data){
		$ret = $data['formno'];
	}
//	$formlist['ALL'] = 0;
	return($ret);

}

?>