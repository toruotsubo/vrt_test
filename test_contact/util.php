<?php 
// 2017/09/18 K.Nakayama v1.2 メールタイトル・連絡先電話番号対応
// 2017/10/27 K.Nakayama v1.3 メールタイトル・連絡先電話番号対応
// 2017/10/28 K.Nakayama v1.3 送信先指定なし対応・入力パラメータ30件対応 初回ログ取得場所変更
// 2017/10/29 K.Nakayama v1.4 ファイルアップロード対応・エラー表示変更
// 2018/02/09 K.Nakayama v1.5 htmlPrintでテンプレートファイルのPHPコードを実行できるように変更
//                            select option 日付生成追加 アイエスガステム用
// 2018/04/29 K.Nakayama v1.6 複数リファラ対応
// 2018/10/20 K.Nakayama v1.61 戻るボタンで戻ったときの入力データ復元で、送信先は復元しないよう修正
// 2019/04/12 K.Nakayama v1.62 warning修正
// 2021/12/02 K.Nakayama v1.7 JSエスケープXSS対策
// 2023/06/13 K.Nakayama v2.0  複数ファイル対応
// 2023/12/15 K.Nakayama v2.01 「0」入力における不具合対応
// 2023/12/15 K.Nakayama v2.02 PHP7.4対応
//                             DBのupload_extがfalseのときは不許可リストとして使用

// session_on() line.467 セッションチェック部分適宜変更すること

if(file_exists("Crypt/Blowfish.php")){
	require_once ("Crypt/Blowfish.php");
}

// 初期設定処理
function formInit(&$r_config,&$r_items,&$r_data,&$r_counters){
// $r_config : フォーム設定リファレンス
// $r_items : input nameリストリファレンス
// $r_data : 入力値リストリファレンス

	$cnf = array();
	$cnf = $r_config;
	
	if (empty($_POST) && $_SERVER["REQUEST_METHOD"] === "POST") {
	// エラー処理
		errDie($cnf['errmsg']['err_fsize'],1);
		exit;
	}

	if(isset($_GET)) $_GET = sanitize($_GET);//NULLバイト除去//
	if(isset($_POST)) $_POST = sanitize($_POST);//NULLバイト除去//
	if(isset($_COOKIE)) $_COOKIE = sanitize($_COOKIE);//NULLバイト除去//

//	if($encode == 'SJIS') $_POST = sjisReplace($_POST,$encode);//Shift-JISの場合に誤変換文字の置換実行
    //microtimeを.で分割
    $arrtime = array();
    $arrtime = explode('.',microtime(true));
    //ミリ秒を返す
	$GLOBALS['@msgid'] = date('Ymd_His', $arrtime[0]) . '_'.$arrtime[1];
	//msgidをグローバル変数に

	$db = "mysql:host=" . $cnf['dbhost'] . ";dbname=" . $cnf['dbname'] . ";charset=utf8" ;
	// MySQLに接続
	try {
		$dbh = new PDO($db, $cnf['dbuser'], $cnf['dbpasswd']);
		//$dbh->query('SET NAMES utf8');
		//print('接続に成功しました。<br>');
		// フォーム設定取得
		$sql = "select * from db_form_setting where form_name=?";
		$stmt = $dbh->prepare($sql);
		$stmt->bindParam(1, $formname);
		$formname = $cnf['formname'];
		$stmt->execute();
		//$r_config = $stmt->fetch(PDO::FETCH_ASSOC);  2017/10/28
		$data = $stmt->fetch(PDO::FETCH_ASSOC);
		//	echo('<pre>' . var_dump($cnf) . '</pre>');
		$r_config = array_merge($data,$r_config);
//		$r_config = array_merge($r_config,$cnf); 2017/10/28
	}catch(PDOException $e){
//		errDie('form_init MySql:'.$e->getMessage());
		echo('<pre>' . var_dump($e->getMessage()) .'</pre>');
		die;
	}
		
	try {
		$dbh = new PDO($db, $cnf['dbuser'], $cnf['dbpasswd']);
		//入力項目設定取得
		$sql = "select * from db_form_items where formno=? order by input_no";
		$stmt = $dbh->prepare($sql);
		$stmt->bindParam(1, $formno);
		$formno = $r_config['formno'] ;
		$stmt->execute();
		while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
			$r_items[] = $result;
		}
		//echo('<pre>' . var_dump($r_items) . '</pre>');
	}catch(PDOException $e){
//		errDie('form_init MySql:'.$e->getMessage());
		echo('<pre>' . var_dump($e->getMessage()) .'</pre>');
		die;
	}

	try {
		$dbh = new PDO($db, $cnf['dbuser'], $cnf['dbpasswd']);
		//入力項目設定取得
		$sql = "select * from db_form_counters where formno=?";
		$stmt = $dbh->prepare($sql);
		$stmt->bindParam(1, $formno);
		$formno = $r_config['formno'] ;
		$stmt->execute();
		$r_counters = $stmt->fetch(PDO::FETCH_ASSOC);
		//echo('<pre>' . var_dump($r_counters) . '</pre>');
	}catch(PDOException $e){
//		errDie('form_init MySql:'.$e->getMessage());
		echo('<pre>' . var_dump($e->getMessage()) .'</pre>');
		die;
	}

	sessionStart();
	// セッション開始

	// 入力値をセッションデータがあればそこから取得
	foreach($r_items as $item) {
		$name = $item['input_name'];
		$type = $item['item_type'];
		if (isset($_SESSION["data_$name"])){
			foreach($_SESSION as $name => $value){
				$dataname = str_replace("data_","",$name) ;
				$r_data["$dataname"] = $_SESSION["$name"] ;
			}
		}
	}
	return(true);
}

//受付制限チェック
function limitCheck($config,$counters){
	$cancel = isset($config['flg_cancel'])? $config['flg_cancel'] : 0;
	$count_max = isset($config['cnt_max'])? $config['cnt_max'] : 99999999;
	$count = (isset($counters['acpt']) && $config['flg_count'] == 1)? $counters['acpt'] : 0;
	$now = date('Y/m/d H:i:s');
	$strnow = strtotime($now);
	$closedate = isset($config['close_date']) ? strtotime($config['close_date']) : "" ;
	
	if((strlen($closedate) > 1) && ($strnow > $closedate) ||
	   (($cancel == 0)&&($count_max < $count))){
	//受付制限チェック
		logWrite("now : $now / 受付期限 : {$config['close_date']} |  count : $count / 人数制限 : $count_max");
		return(true);
	}
	return(false);
}

//キャンセル待ち状態チェック
function waitCheck($config,$counters){
	
	$cancel = isset($config['flg_cancel'])? $config['flg_cancel'] : 0;
	$count_max = isset($config['cnt_max'])? $config['cnt_max'] : 99999999;
	$count = (isset($counters['acpt']) && $config['flg_count'] == 1)? $counters['acpt'] : 0;
	
	if($cancel == 1){
	//キャンセル待ちありの時
		if($count_max < $count){
		//キャンセル待ちチェック
			return(true);
		}
	}
	return(false);
}

//入力フォーム初期処理　メール登録・実登録共通
function inputInit(&$r_items,$data,$mailid,$mailadr,$mode=0){
	global $config;
	$GLOBALS['@formname'] = $config['formname'];
	$GLOBALS['@mode'] = "";
	$jsbuf = "var ename;\n";
	$jsalert = "";

//前回入力値をデフォルト値にセットするJS生成
	foreach($r_items as $item) {
		$mailneed = 0;
		$name = $item['input_name'];
		$type = $item['item_type'];
		$cancel = $item['cancel_in'];
		if(($mode == 0 && $cancel <= 1) || ($mode == 1 && $cancel >= 1)){
			// 2018/10/20 送信先は復元しない
			if($type == MAILTO){
				continue;
			}
			if(($type == MAILNEED)&&(isset($mailadr))){
				if(!empty($mailadr)){
					$value = $mailadr ;
				}else if(isset($data["$name"]) || !empty($data["$name"])){
					$value = $data["$name"];
				}else{
					$value = "" ;
				}
				$mailneed = 1;
			}else if(($type == FILEANY) || ($type == FILENEED)) {
			// ファイルアップロード
				if (isset($data["$name"]) && (empty($jsalert))) {
				// 入力値があるときalert
					$jsalert = "\nalert(\"添付ファイルがあるときは再度指定してください。\");\n" ;
					// アップロードデータクリア 2023/06/16
					$_FILES["$name"] = array(); 
					$data["$name"] = ""; 
					$data["$name" . "_fname"] = array();
					$data["$name" . "_fsize"] = array();
				}
				continue;
				
			}else{
				$value = (isset($data["$name"]) || !empty($data["$name"]))? $data["$name"] : "" ;
				$value = preg_replace("/\r?\n/","\\n",$value);
				//$value = addslashes($value); //JSエスケープ対策 2017/11/23
				$value = htmlspecialchars($value); //JSエスケープ対策 2021/06/07
			}
/*			if(isset($item['multi_sel_items'])){
			// 複数選択
				$name = $name . "[]" ;
			} 2018/07/14 不要 */
			// JSメタ文字エスケープ・改行コードのアンエスケープ処理追加 2017/11/23
			$jsbuf .= <<<__JSBUF__
ename = document.getElementsByName("{$name}");
var str = "{$value}";
//preg_quote(str);
str = unhtmlspecialchars(str);
str = str.replace(/\\\\n/g,'\\n');
if (ename[0].type === "radio" || ename[0].type === "checkbox") {
//複数選択
 checkvalue('{$name}',str);
} else {
 setvalue('{$name}',str);
}

__JSBUF__;
			if($mailneed) {
				$jsbuf .= <<<__JSBUF2__
				ename.readonly = true;
__JSBUF2__;
			}
		}
	}
	$jsbuf = $jsbuf . $jsalert ;
	return $jsbuf;

}


// メールIDチェック 正しければ該当するメールアドレスを取得
function mailIdCheck($config,$mailid) {
	$maillist = array();
	$count = 0;
	// SQL・パラメータセット
	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8" ;
	$sql = "select * from db_form_mail_data where formno=:formno and id=:id";
	$param = array();
	$param[':formno'] = $config['formno'] ;
	$param[':id'] = $mailid ;
	// MySQLに接続
	try {
		$dbh = new PDO($db, $config['dbuser'], $config['dbpasswd']);
#		$dbh->query('SET NAMES utf8');
		//print('接続に成功しました。<br>');
		// フォーム設定取得
		$stmt = $dbh->prepare($sql);
		$stmt->execute($param);
		$count = $stmt->fetchColumn();
		$stmt->execute($param);
		$maillist = $stmt->fetch(PDO::FETCH_ASSOC);

	}catch(PDOException $e){
		errDie('form_init MySql:'.$e->getMessage());
		//echo('<pre>' . var_dump($e->getMessage()) .'</pre>');
	}
	if ($count < 1) {
		errDie('未登録のメールアドレスです。');
	}else if ($count > 1) {
		errDie('メールIDエラー：複数登録');
	}
	return($maillist['mailadr']);
}

//入力値チェック
function CheckInputdata(&$config,$items,&$data,&$err){
	//ファイルアップロード設定チェック
	$ext_tbl = array();
	chkFileupload($config,$ext_tbl);
	//入力値チェック
	foreach($items as $item) {
		$name = $item['input_name'];
		$type = $item['item_type'];
		$cancel = $item['cancel_in'];
		if(($GLOBALS['@mode'] == 1) && ($cancel > 1)){
			// 通常入力チェックのときキャンセル時のみ入力するもの
			continue;
		}else if(($GLOBALS['@mode'] == 2) && ($cancel < 1)) {
			// キャンセル入力チェックのとき通常時のみ入力するもの
			continue;
		}
		if(!empty($_POST["$name"]) &&(!is_array($_POST["$name"]))&&
			(mb_strlen($_POST["$name"],$config['encode']) > $config['textmax'])){
		//最大文字数オーバー
			$err["$name"] =  $config['errmsg']['err_tsize'];
			continue;
		}
		if($type == NEED){
			//必須チェック
			if(strlen($_POST["$name"])<1){
//2023/12/15			if(empty($_POST["$name"])){
				$err["$name"] =  $config['errmsg']['err_need'];
			}else{
				$val = $_POST["$name"];
				if(is_array($val) && (isset($item['multi_sel_items']))){
					//複数選択時の必須チェック
					$connectEmpty = 0;
					foreach($val as $kk => $vv){
						if(is_array($vv)){
							foreach($vv as $kk02 => $vv02){
								if($vv02 == ''){
									$connectEmpty++;
								}
							}
						}
						
					}
					if($connectEmpty > 0){
						$err["$name"] =  $config['errmsg']['err_need'];
					}
				}
			}
		}elseif(($type == MAILNEED)||($type == MAILANY)){
//			if(($type == MAILNEED) && (empty($_POST["$name"]))) {
			if(($type == MAILNEED) && (strlen($_POST["$name"])<1)) {
				//必須チェック
				$err["$name"] =  $config['errmsg']['err_need'];
				
//			}elseif(isset($_POST["$name"]) && (!checkMail($_POST["$name"]))) {
			}elseif((strlen($_POST["$name"])>0) && (!checkMail($_POST["$name"]))) {
				//メールアドレスチェック
				$err["$name"] =  $config['errmsg']['err_mail'];
			}elseif($type == MAILNEED){
				//メール送信先に当たるもの←返信先にしない必須typeを設定する可能性あり
				$data['mailadr'] = isset($_POST["$name"])? $_POST["$name"] : null ;
				$data['mailid'] = isset($_POST['mailid'])? $_POST['mailid'] : null ;
			}
		}elseif(($type == PARTY)&&(!empty($_POST["$name"]))){
			//同伴者チェック
			if(preg_match('/^([1-9][0-9]*)|([１-９][０-９]*)$/',$_POST["$name"])){
				$_POST["$name"] = mb_convert_kana($_POST["$name"],"n");
			}else{
				$err["$name"] =  $config['errmsg']['err_num'];
			}
			
		}elseif($type == MAILTO){
			if(empty($_POST["$name"])){
				//送信先チェック システムエラーに
//				$err["$name"] =  $config['errmsg']['err_mailto'];
				die("送信先不明");
			}else{
				// 送信先データ取得
// 2019/01/17 DB使用				$inquiry = getMaillist($_POST["$name"],$config['file_maillist']);
				if(!(empty($config['file_maillist']))){
					if($config['file_maillist'] == 1){
						$inquiry = getMaillist_db($_POST["$name"],$config);
					}else{
						$inquiry = getMaillist($_POST["$name"],$config['file_maillist']);
					}
				}
				$data['mailtoadr'] = $inquiry['email'] ;
				$data['mailtoname'] = $inquiry['facility'] ;
				$data['mailtonum'] = $_POST["$name"] ;
				// 2017/09/18 add メールタイトル・連絡先電話番号
				$data['mailtosubj'] = isset($inquiry['subject'])? $inquiry['subject'] : null ;
				$data['mailtotel'] = isset($inquiry['tel'])? $inquiry['tel'] : null ;
			}

//		}elseif(($type == ACPTNO)&&(!empty($_POST["$name"]))){
//			//登録番号チェック
//			if(preg_match('/^([1-9][0-9]*)|([１-９][０-９]*)$/',$_POST["$name"])){
//				$_POST["$name"] = mb_convert_kana($_POST["$name"],"n");
//			}else{
//				$err["$name"] =  $config['errmsg']['err_num'];
//			}
				
		}elseif(($type == FILEANY) || ($type == FILENEED)) {
			//ファイルアップロードのとき
			if(empty($config['file_upload']) && $config['file_upload'] == 1){
			//設定有無のチェック
				errDie("ファイルアップロードの初期設定が不足しています。");
			}
			
			//アップロードエラーチェック 2017/10/28
			//複数ファイル対応 2023/06/14
			// 初期化
			$data["$name"] = "" ;
			$data["$name" . "_fname"] = array();
			$data["$name" . "_fsize"] = array();

			//if((isset($_POST["$name"])) && (isset($_FILES["$name"]))){
			if(isset($_FILES["$name"])){
				$size = 0; // 累積ファイルサイズ 2023/06/16
				
				for($i = 0; $i < count($_FILES["$name"]["name"]); $i++ ){
					if (isset($_FILES["$name"]['error'][$i]) || is_int($_FILES["$name"]['error'][$i])) {
						switch ($_FILES["$name"]['error'][$i]) {
							case UPLOAD_ERR_OK: // OK
								break;
							case UPLOAD_ERR_NO_FILE:   // ファイル未選択
								if($type == FILENEED) {
									$err["$name"] =  $config['errmsg']['err_need'];
								}else{
									$data["$name"][$i] = null ;
									$data["$name" . "_fvrschk"][$i] = null ;
								}
								break;
							case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
							case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過 (設定した場合のみ)
									$err["$name"] =  $config['errmsg']['err_fsize'];
								break;
							default:
								errDie('ファイルアップロードエラー  errcode : ' . $_FILES["$name"]['error'][$i]);
						}
					}

					//アップロードファイルチェック
					if(is_uploaded_file($_FILES["$name"]['tmp_name'][$i])) {
						$tmpext = "";
						$ext = array();
						// 複数ファイル対応　累積ファイルサイズをチェック 2023/06/16
						$size = $size + $_FILES["$name"]['size'][$i] ;
						if($size > $config['upload_file_size_max']){
//						if($_FILES["$name"]['size'][$i] > $config['upload_file_size_max']){
							//ファイルサイズチェック（偽装等でphpでの定義を突破した時用）
							$err["$name"] = $config['errmsg']['err_fsize'] . ":" . basename($_FILES["$name"]['name'][$i]) ;
						}else{
							//拡張子チェック 2017/10/28
							// 指定拡張子は $config['upload_ext']=1(true)のとき許可・0(false)の時不許可とする 2023/12/27
							$arrname = explode('.',basename($_FILES["$name"]['name'][$i])); # 2019/04/12 修正
							$tmpext = end($arrname) ;
							$ext = preg_grep("/^$tmpext$/i",$ext_tbl);
							if($config['upload_ext'] == 1) {
								//拡張子リストが許可リストのとき
								if(empty($ext)) {
									//リストに含まれていなければ拡張子エラー
									$err["$name"] .= $config['errmsg']['err_fext'] . ":" . basename($_FILES["$name"]['name'][$i]);
								}
							}else{
								//拡張子リストが不許可リストのとき
								if(!empty($ext)) {
									//リストに含まれていれば拡張子エラー
									$err["$name"] = $config['errmsg']['err_fext'] . ":" . basename($_FILES["$name"]['name'][$i]);
								}
							}
						}
						//
						//エラーでなければ一時ディレクトリ（./file）に保存
						if(empty($err["$name"])) {
							// 出力ファイル名生成
							$fname = $GLOBALS['@msgid'] .'_' . sprintf("%08d",rand(1,99999999)) .'.' . $tmpext ;
							if (move_uploaded_file($_FILES["$name"]['tmp_name'][$i], $config['tempdir'] . "/" . $fname)) {
								chmod($config['tempdir'] . "/" . $fname, 0644);
								//データにファイル名等をセット
								setlocale(LC_CTYPE, 'ja_JP.UTF-8'); // 日本語ファイル名対策にlocaleをセット
								$data["$name"] .= basename($_FILES["$name"]['name'][$i]) . "\n";
								$data["$name" . "_fname"][$i] = $fname;
								$data["$name" . "_fsize"][$i] = $_FILES["$name"]['size'][$i];
								//ウィルスチェック 2017/12/04
								if(!empty($config['viruscmd'])){
									$vrsbuf = "";
									$virus = viruscheck($config['tempdir'] . "/" . $fname,$vrsbuf,$config);
									if($virus == -1) {
										errDie('ウィルスチェックエラー：' . $vrsbuf);
									}
									list($file,$result) = explode(":",$vrsbuf,2) ;
									$data["$name" . "_fvrschk"][$i] = "Virus Check :" . $result ;
									if($virus == 1){
									// ウィルスなし　アップロードファイルセット
										continue;
									}else{
									// ウィルスあり　ファイル名セット無し
										$data["$name" . "_fname"][$i] = null ;
										continue;
									}
								}
							} else {
								errDie('ファイル保存エラー： ' . $_FILES["$name"]['name'][$i]);
								//エラー終了
							}
						}
					}
				}
			}elseif($type == FILENEED) {
				//必須ファイルアップロード無し
				$err["$name"] =  $config['errmsg']['err_fext'];
			}
		}
		
		if(isset($item['multi_sel_items']) && isset($_POST["$name"])){
		//複数選択処理 2017/12/13修正
			$select = array();
			$select = explode(',',$item['multi_sel_items']);
			// 選択項目を配列化
			$vals = array();
			if(is_array($_POST["$name"])){
			// 複数選択
				$vals = $_POST["$name"];
			}else{
			// 単数選択
				$vals[0] = $_POST["$name"];
			}
			$list = array();
			// 入力内容を項目順に並べる
			for($i=0; isset($select[$i]) ;$i++) {
				$key = $select[$i];
				foreach($vals as $val) {
					if($key == $val) {
						preg_replace("/" . DELIMITER . "/" ,REP_DLMT,$val); // デリミタ文字を別の文字に置換
						$list[$i] = $val;
					}
				}
			}
//			$data["$name"] = implode(",",$list);
			$data["$name"] = implode(DELIMITER,$list); // 「,」入力対応のためデリミタ文字に
//		}else{
		}elseif(($type != FILEANY) && ($type != FILENEED)) { //2017/10/29
//			$data["$name"] = isset($_POST["$name"])? h($_POST["$name"]) : "" ;
			$data["$name"] = isset($_POST["$name"])? ($_POST["$name"]) : "" ;
		}
	}
}

function viruscheck($file,&$vrsbuf,$config){
//echo("viruscheck $file <br>\n");
	logWrite("viruscheck : $file");
	if(!empty($config['viruscmd'])){
		//コマンドが設定されているときチェック 2017/12/04
		$vrsret = array();
		$vrscmd = $config['viruscmd'] . $file;
//		echo $vrscmd . "<br>\n";
		exec($vrscmd,$vrsret) ;
//		var_dump($vrsret);
		//1行目チェック
		if(strlen($vrsret[0]) < 1) {
		// ウィルスチェックシステムエラー
			$ret = -1 ;
		}else if(preg_match('/OK$/',$vrsret[0])) {
			$ret = 1 ;
		}else if(preg_match('/FOUND$/',$vrsret[0])){
			$ret = 0 ;
		}else{
			$ret = -1 ;
		}
		$vrsbuf = implode("\n",$vrsret);
		return($ret);
	}else{
	//チェックしない
		return(1);
	}
}

function sanitize($arr){
	if(is_array($arr)){
		return array_map('sanitize',$arr);
	}
	return str_replace("\0","",$arr);
}

// フォーム入力値ログ出力
function formlogWrite(){
	$data = array();
	ksort($_POST); // post入力値をソート
	
	foreach($_POST as $name => $value){
		if(is_array($value)){
			$valbuf = implode(",",$value);
			array_push($data,$valbuf) ;
		}else{
			array_push($data,$value) ;
		}
	}
	logWrite('"getdata : ' . implode(',',$data) . '"');
	return;
}

// メールログ出力
function maillogWrite($ret,$to,$subject,$mailbody,$header){
	global $config ;
	
	$mailtext = $GLOBALS['@time'] . " --- " . $GLOBALS['@msgid'] . "--- $ret ------------------\n" . 
				"To: " . $to . "\n" . $header . "\nSubject:" . $subject . "\n" . $mailbody . "\n\n" ; 

	$fp = fopen($config['maillog'], 'a+');
	if ($fp){
		if (flock($fp, LOCK_EX)){
			if (fwrite($fp,	 $mailtext) === FALSE){
				die('ログファイル書き込みに失敗しました : ' . $config['maillog'] . "\n");
			}

			flock($fp, LOCK_UN);
		}else{
			die('ファイルロックエラー : ' . $config['maillog'] . "\n");
		}
	}
	chmod($config['maillog'],0666);
	fclose($fp);
	return;
}

// セッション開始処理
function sessionStart(){

    global $config;

    if((isset($_POST['formname']))&&(empty($_COOKIE['ON']))) {	// ブラウザがクッキー拒否なら
		errDie('ブラウザのクッキーを設定してください。',1);
    }

	$sname = 'SID' . $config['formname'];
	// セッション名セット
	session_name($sname);
	$sid = isset($_REQUEST[session_name()]) ? $_REQUEST[session_name()] : '';
	$sid = session_id();
	if(((!isset($GLOBALS['@mode']) || $GLOBALS['@mode'] <> 3) && empty($sid)) ||
	    file_exists(session_save_path() . DIRECTORY_SEPARATOR . 'sess_' . $sid)){ 
	// 完了処理以外でセッションIDが空か
	// セッションIDに対するセッションファイルがあればセッション開始/更新
		session_on();
	}else{
		errDie('セッションエラー 1',1);
	}

	return;
}
// セッションＩＤを発行または更新
// 2017/10/5 大幅修正
function session_on() {

	session_start();	// セッション開始
//		echo('<pre>session_start session = ' . var_dump($_SESSION) . '</pre>'); //dbg
	$sid = session_id();
//    ini_set('session.use_trans_sid', '0');
//    session_set_cookie_params(0, "/" . $config['formname'] . "/");
    if (empty($sid)) {	// 初回なら
        session_id(md5(uniqid(rand(), 1))); // セッションID設定
        $_SESSION['msgid'] = $GLOBALS['@msgid'];
        //現在のmsgidをセッション値に
		// echo('<pre>session_on1 session = ' . var_dump($_SESSION) . '</pre>'); //dbg

    } else {	// セッション継続 2017/10/28 送信先選択無しのとき対応
		if (empty($_REQUEST['msgid']) && (!empty($config['file_maillist']) && empty($_REQUEST['to']))) return false;
//	echo('<pre>session_on session = ' . var_dump($_SESSION) . '</pre>'); //dbg
        if(isset($_SESSION['msgid'])) {
        	$GLOBALS['@msgid'] = $_SESSION['msgid'];
        }
        //セッション値のmsgidをグローバル変数に
 		session_regenerate_id(true);  // セッションID更新 2021/07/10
/*       $tmp = $_SESSION; // 現在のセッション変数を退避
       $_SESSION = array(); // 初期化
        session_destroy(); // 破棄
        session_id(md5(uniqid(rand(), 1))); // セッションID更新
        session_start(); // セッション再開
        $_SESSION = $tmp; // セッション変数引き継ぎ
*/
//	echo '<pre>session_restart session = ' . var_dump($_SESSION) . '</pre>';
   }
   
    return true;
}

//セッションＩＤチェック
function exist_sid() {
    $sid = req(session_name());
    return(!empty($sid) && file_exists(session_save_path()
         . DIRECTORY_SEPARATOR . 'sess_' . $sid) ? true : false);
}

// ログアウト処理
function session_off() {
	global $config;
	$_SESSION = array();
    setcookie(session_name(), "", time()-42000);	// クッキーを消す
    $_SESSION = array();	// セッション変数を消す
    session_destroy();	// セッションファイルを消す
	$sid = isset($_REQUEST[session_name()]) ? $_REQUEST[session_name()] : '';
    return;
}
// リクエストデータ取得
function req($key) {
    return(isset($_REQUEST[$key]) ? $_REQUEST[$key] : '');
}

//メールチェック
// 2018/07/24 ドメインも大文字対応する
function checkMail($str){
	$mailaddress_array = explode('@',$str);
	if(preg_match("/^[\.!#%&\-_0-9a-zA-Z\?\/\+]+\@[!#%&\-_0-9a-zA-Z]+(\.[!#%&\-_0-9a-zA-Z]+)+$/", "$str") && count($mailaddress_array) ==2){
		return true;
	}else{
		return false;
	}
}

//リファラチェック
// 2018/04/29 複数ホスト名対応
function refererCheck($Referer_check_domain){
//	echo("HTTP_REFERER = " . $_SERVER['HTTP_REFERER'] ."<br>\n");
//	echo("Referer_check_domain = " . $Referer_check_domain ."<br>\n");
	if(!empty($Referer_check_domain)){
		$domain_array = explode(',',$Referer_check_domain);
		foreach($domain_array as $domain){
//	echo("Referer_check_domain = " . $domain ."<br>\n");
//	echo(strpos($_SERVER['HTTP_REFERER'],$domain) ."<br>\n");
			if(strpos($_SERVER['HTTP_REFERER'],$domain) !== false){
				return(true);
			}
		}
//		if(strpos($_SERVER['HTTP_REFERER'],$Referer_check_domain) === false){
//			return(false);
//		}
	}else{
		return(true);
	}
	return(false);
//	return(true);
}


// DB実行（select以外）
function dbExec($sql,$params,$config){
	//ログ出力
	logWrite('"dbExec : ' . $sql . '"');

	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;
	// MySQLに接続
	try {
		$dbh = new PDO($db, $config['dbuser'], $config['dbpasswd']);
#		$dbh->query('SET NAMES utf8');
		//print('接続に成功しました。<br>');
		$stmt = $dbh->prepare($sql);
		$stmt->execute($params);

	}catch(PDOException $e){
		errDie('dbExec MySql:'.$e->getMessage(),2);
		//echo('<pre>' . var_dump($e->getMessage()) .'</pre>');
	}
	//ログ出力
	logWrite('"dbExec : OK"');
}

// DB実行（1件select）
function dbSelectOne($config,$sql,$params,$r_data){
	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8" ;
//		var_dump($params);
//		echo $sql;
	//ログ出力
	logWrite('"dbSelectOne : ' . $sql . '"');
	// MySQLに接続
	try {
		$dbh = new PDO($db, $config['dbuser'], $config['dbpasswd']);
		//$dbh->query('SET NAMES utf8');
		//print('接続に成功しました。<br>');
		// フォーム設定取得
		$stmt = $dbh->prepare($sql);
		$stmt->execute($params);
		$r_data = $stmt->fetch(PDO::FETCH_ASSOC);
		//	echo('<pre>' . var_dump($r_config) . '</pre>');
	}catch(PDOException $e){
		errDie('dbSelectOne MySql:'.$e->getMessage());
		//echo('<pre>' . var_dump($e->getMessage()) .'</pre>');
	}
}

// DB実行（複数件select）
//      return : 取得件数
function dbSelectTable($config,$sql,$params,&$r_data){
	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8" ;
	//ログ出力
	logWrite('"dbSelectTable : ' . $sql . '"');
	// MySQLに接続
	try {
		$dbh = new PDO($db, $config['dbuser'], $config['dbpasswd']);
		//$dbh->query('SET NAMES utf8');
		//print('接続に成功しました。<br>');
		// フォーム設定取得
		$stmt = $dbh->prepare($sql);
		$stmt->execute($params);
		$count = $stmt->fetchColumn();
		$stmt->execute($params);
		while($result = $stmt->fetch(PDO::FETCH_ASSOC)){
			$r_data[] = $result;
		}
		//echo('<pre>' . var_dump($r_data) . '</pre>');
	}catch(PDOException $e){
		errDie('dbSelectTable MySql:'.$e->getMessage());
		//echo('<pre>' . var_dump($e->getMessage()) .'</pre>');
	}
	
	return($count);
}

// データ件数取得処理
function inputDbCount($sql,$params,$config){
	//ログ出力
	logWrite('"inputDbCount : ' . $sql . '"');
	// MySQLに接続
	$db = "mysql:host=" . $config['dbhost'] . ";dbname=" . $config['dbname'] . ";charset=utf8"  ;
	try {
		$dbh = new PDO($db, $config['dbuser'], $config['dbpasswd']);
#		$dbh->query('SET NAMES utf8');
		//print('接続に成功しました。<br>');
		// フォーム設定取得
		$stmt = $dbh->prepare($sql);
		$stmt->execute($params);
		$count = $stmt->fetchColumn();

	}catch(PDOException $e){
		errDie('inputDbCount MySql:'.$e->getMessage(),2);
		//echo('<pre>' . var_dump($e->getMessage()) .'</pre>');
	}
	
	//ログ出力
	logWrite('"inputDbCount : OK"');
	return($count);
}

// 暗号化(OpenSSL or Crypt_Blowfish使用)
function strCrypt($str){
	//暗号化＆復号化キー
    $key = md5(MD5KEY);
	// 2022/03/17 openssl_encrypt 仕様変更 ivを追加
/*	if(function_exists('openssl_encrypt')){*/
		$enc_str = openssl_encrypt($str, 'AES-128-ECB', $key ,0 ,"");	// 2022/03/17 openssl_encrypt 仕様変更 ivを追加

/*	}else{
	    //暗号化モジュール使用開始
	    $blowfish = new Crypt_Blowfish($key);
	    //データを暗号化
	    $encode_data = $blowfish->encrypt($str);
		//暗号化したデータはバイナリなのでbase64_encodeでテキスト化
		$enc_str = base64_encode($encode_data);
	}*/
    return($iv . $enc_str); // 2022/03/17 暗号化文字列にivを追加
}

// 復号化
function strDecrypt($str){
	 //暗号化＆復号化キー
    $key = md5(MD5KEY);
	// 2022/03/17 openssl_encrypt 仕様変更 ivを追加
/*	if(extension_loaded('openssl')){*/
	$dec_str = openssl_decrypt($str, 'AES-128-ECB', $key, 0, "");	// 2022/03/17 openssl_decrypt 仕様変更 引数を追加
/*	}else{
		//復号化
		$str = base64_decode($str);
		$blowfish = new Crypt_Blowfish($key);
		$decode_data = $blowfish->decrypt($str);
		//rtrimで「\0」を取り除く
		$dec_str =  rtrim($decode_data, "\0");
	}*/
    return($dec_str);
}

// ログファイル出力
function logWrite($msg) {

	global $config;
	
	$logmsg = date( "Y/m/d-H:i:s", time() ) . ",";
	$logmsg .= (isset($GLOBALS['@formname'])? $GLOBALS['@formname'] : "")  . "," ;
	$logmsg .= (isset($GLOBALS['@msgid'])? $GLOBALS['@msgid'] : "") . "," ;
	$logmsg .= (isset($GLOBALS['@mode'])? $GLOBALS['@mode'] : "") . "," ;
	$logmsg .= $msg . "," ;
	//$logmsg .= (isset($_SERVER['HTTP_REFERER'])? $_SERVER['HTTP_REFERER'] : "") ."," ;
	$logmsg .= (isset($_SERVER['REQUEST_URI'])? $_SERVER['REQUEST_URI'] : "") ."," ;
	$logmsg .= (isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR'] : "") .",";
	//$logmsg .= (isset($_SERVER['REMOTE_HOST'])? $_SERVER['REMOTE_HOST'] : "") .",";
	$logmsg .= (isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : "") ;
	$logmsg .= "\n";
	
	$fp = fopen($config['log'], 'a+');
	if ($fp){
		if (flock($fp, LOCK_EX)){
			if (fwrite($fp, $logmsg) === FALSE){
				die('ログファイル書き込みに失敗しました : ' . $config['log'] . "\n");
			}

			flock($fp, LOCK_UN);
		}else{
			die('ファイルロックエラー : ' . $config['log'] . "\n");
		}
		fclose($fp);
		chmod($config['log'],0666);
	}

}

//html出力
//現状、<!-- RoopFormat Start --><!-- RoopFormat End --> 内ループはitem関連のみ
// 2018/03/05 config指定のファイルが無いときエラーにする
function htmlPrint($typ,&$data,$items = null) {

	global $config;
	
	$in = array(); // 出力用テンプレート
	$out = array(); // 出力データ
	$loop = 0;
	$loopline = array();
	$keys = array();
	$values = array();
	
	$filename = isset($config['html']["$typ"])? $config['html']["$typ"] : null ;
//	if (($filename)&&(file_exists($filename))){
	if ($filename){
		if(!(file_exists($filename)) || (($in = file($filename)) === false)){
			errDie("ファイル($filename) エラー");
		}
	}else{
		$buf = htmlSet($typ);
		$in_temp = array();
		$in_temp = file($config['html']['template']);
		$key = "%body%";
		// 汎用テンプレートの%body%にセット
		foreach($in_temp as $line) {
			if(strstr($line,"%body%")) {
				$temp = str_replace("%body%",$buf,$line);
				$temp_ary = array();
				$temp_ary = preg_split('/\n/',str_replace("%body%",$buf,$line));
				$in = array_merge($in,$temp_ary);
			}else{
				$in[] = $line;
			}
		}
	}

	//出力変数と値のリスト作成
	foreach($data as $key => $value) {
		if(is_array($value) !== true){
		// 複数ファイル対応 配列でなければ文字列化
			//echo "key = $key <br>\n";//
			//echo "value = $value <br>\n";//
			$value = h($value);//htmlエンコード
			$value = str_replace(DELIMITER , "," , $value); // 複数選択区切り文字を「,」に 2017/12/18
			$value = nl2br($value);//改行を<br>に
			$value = (strlen($value)<1)? '&nbsp;' : $value; //値がないときは&nbsp;
			$keys[] = "%$key%";
			$values[] = $value;
		}
	}

	foreach($in as $line) {
		if(strpos($line,'<!-- RoopFormat Start -->')!== false) {
		// ループ挿入開始
			$loop = 1;
			$lines1 = array();
			$lines2 = array();
			continue;
		}elseif(strpos($line,'<!-- RoopFormat End -->')!== false) {
		// ループ挿入終了
			$loop = 2;
		}
		if($loop == 1){
			$lines1[] = $line;
		}elseif(($loop == 2)&&(isset($items))){
			// ループエリアはitem順に出力
	//echo "items : " . print_r($items) ." <br>";
			foreach($items as $item) {
				$name = $item['name'];
				$in_name = $item['input_name'];
				$temp = array("%name%","%value%","%in_name%");
				if(isset($data["$in_name"])){
					$text = h($data["$in_name"]);//htmlエンコード
					$text = nl2br($text);//改行を<br>に
					$val = array($name,$text,$in_name);
//					$val = array($name,h($data["$in_name"]));
					foreach($lines1 as $l_line){
		//echo "lline : " . h($l_line) . "<br>";
						$templine = str_replace($temp,$val,$l_line);
						$lines2[] = $templine;
		//echo "str_replace : " . h($templine) . "<br>";
					}
				}
				
			}
			
			$out = array_merge($out,$lines2);
			$loop = 0;
		}else{
			$out[] = str_replace($keys,$values,$line);
		}
	}
	
	// PHP実行できるように一旦ファイルにしてinclude 2018/02/09
/*	foreach($out as $buf)	{
		echo $buf . "\n";
		$contents = ob_get_contents();  
	}
*/
	$filename = $config['tempdir'] . '/' . $GLOBALS['@msgid'] . '.php' ;
	file_put_contents($filename,$out);
	include($filename);
	unlink($filename);

	return(true);
}

//ファイルアップロード設定チェック 2017/12/02
function chkFileupload($config,&$ext_tbl){
	if(!empty($config['file_upload']) && $config['file_upload'] = 1){
		if(empty($config['upload_file_dir'])){
			errDie("ファイルアップロード：アップロード先upload_file_dirが指定されていません");
		}
		if(empty($config['tempdir'])){
			errDie("ファイルアップロード：一時ディレクトリtempdirが指定されていません");
		}
		if(file_exists($config['upload_file_dir']) === false){
			errDie("ファイルアップロード：アップロード先 [" . $config['upload_file_dir'] . "] が存在しません");
		}	
		if(file_exists($config['tempdir']) === false){
			errDie("ファイルアップロード：一時ディレクトリ [" . $config['tempdir'] . "] が存在しません");
		}	
		//ファイルアップロード拡張子チェックリスト
		// 2023/12 指定拡張子リストは $config['upload_ext']=1(true)のとき許可・0(false)の時不許可とする
		//         ここでは設定チェック後拡張子のconfigとDBの設定を合わせる
		$tmp_ext = array();
		if(!empty($config['def_ext_list'])) {
		//デフォルト許可拡張子
			$ext_tbl = explode(',',$config['def_ext_list']);
		}
		if(!is_null($config['upload_ext'])&&(!empty($config['upload_ext_list']))){
			$tmp_ext = explode(',',$config['upload_ext_list']);
			$ext_tbl = array_values(array_unique(array_merge($ext_tbl,$tmp_ext)));
			//結合→重複削除→キー振り直し
			
/* 2023/12/27		if($config['upload_ext'] == 1) {
			//追加許可拡張子
				$tmp_ext = explode(',',$config['upload_ext_list']);
				$ext_tbl = array_values(array_unique(array_merge($ext_tbl,$tmp_ext)));
				//結合→重複削除→キー振り直し
			}else{
			//許可しない拡張子：デフォルトから削除
				$tmp_ext = explode(',',$config['upload_ext_list']);
				$ext_tbl = array_diff($ext_tbl,$tmp_ext);
			}
*/
		}
		if(empty($ext_tbl)){
			errDie("ファイルアップロード：許可拡張子が設定されていません。");
		}
	}
	return(true);
}

// ファイルサイズ単位表記変換 2017/11/23
function cnvFilesize($f_bytes){
	if(($f_bytes / 1024) < 1){
		$fsize = round($f_bytes) . 'B';
	}else if(($f_bytes / 1024 / 1024) < 1){
		$fsize = round($f_bytes / 1024) . 'KB';
	}else{
		$fsize = round($f_bytes / 1024 / 1024 , 1) . 'MB' ;
	}
	return($fsize);
}

//エラー処理
function errDie($msg,$errtyp=0,$to=null) {
	global $config;
	//echo $msg; //debug
	$errmsg = array();
	$errbuf = "";
	if(isset($errtyp) && $errtyp == 1) { 
		$errbuf .= 'お手数ですが最初から入力をやり直してください。' . "\n";
	}else{
		$errbuf .= 'お手数ですがお問い合わせください。' . "\n";
	}
	$errmsg["errtitle"] = $msg;
	$errmsg["errmsg"] = $errbuf;
	$errmsg["html_errmsg"] = empty($config["html_errmsg"])? "" : ($config["html_errmsg"]) ;
	//POSTデータが無いときのページ戻り対策 2017/11/21
//	if(isset($_SESSION['data_mailtonum'])){
//		$errmsg["to"] = substr($_SESSION['data_mailtonum'],1) ;
//	}else if(isset($_REQUEST['to'])){
//		$errmsg["to"] = $_REQUEST['to'] ;
//	}
	// 2019/06/11 遷移先指定対応 2021/03/29
	if(isset($to)){
		$errmsg["to"] = $to ;
	}else if(isset($_SESSION['data_mailtonum'])){
		$errmsg["to"] = $config["form_url"] . ((empty($config['file_maillist']))? "" : "?to=") . substr($_SESSION['data_mailtonum'],1)  ;
	}else if(isset($_REQUEST['to']) || strlen($_GET['to']) > 0){ //2019/06/11 2021/03/29
		$errmsg["to"] = $config["form_url"] . "?to=" . $_REQUEST['to'] ;
	}else if(empty($config['file_maillist'])){
		$errmsg["to"] = $config["form_url"] ;
	}else{
		$errmsg["to"] = "/" ;
	}
	htmlPrint('syserr',$errmsg,"");
	
	logWrite($msg);
	session_off();
	die;
}

//htmlエンコード
function h($str) {
	global $config;
    return htmlspecialchars($str, ENT_QUOTES, $config['encode']);
}
//htmlデコード
function hd($str) {
	global $config;
    return htmlspecialchars_decode($str, ENT_QUOTES);
}

//JSONエンコード
function j($str) {
	return json_encode($str, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}


// アイエスガステム用 日付オプションの生成 2018/02/09
function makeDateOpt(){
	$optbuf = "";
	for($i = 5 ; $i < 5+60 ; $i++) {
		$date = date('Y/m/d',strtotime("$i day"));
		$optbuf = $optbuf . '<option value="' . $date . '">' . $date . '</option>' . "\n";
	}
	return($optbuf); 
}

?>
