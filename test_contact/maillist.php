<?php
// メール送信先リスト取得（DB使用）
//
// 2019/06/11 送信先不明のときの対応追加

function getMaillist_db($fa,$config){

	// メールアドレスデータ読み込み 2019/01/17 DB使用
	$sql = "SELECT no,id,email,facility FROM db_maillist" . 
			" WHERE formno = :formno and id = :id and del_date is null";
	$param = array();
	$param[':formno'] = $config['formno'];
	$param[':id'] = $fa;

	$temp = array();
	dbSelectTable($config,$sql,$param,$temp);
	$inquiry = (isset($temp[0]))? $temp[0] : null ;
	return($inquiry);
}

?>