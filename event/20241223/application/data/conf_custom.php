<?php
# 汎用フォームメール初期設定
# 2018/07/14 jsn-event個別カスタマイズ用
function conf_customize(&$r_config) {
	$r_config['formname'] = '20241223'; 
	//受付番号名称
	$r_config['num_name'] ='申込No.';
	//ログファイル
	$r_config['log'] = './log/form.log';
	//メール出力ログファイル
	$r_config['maillog'] ='./log/mail.log';
/* 英文用エラーメッセージにするときはここに
	$r_config['errmsg'] = array();
	$r_config['errmsg'] = array(
			 'err_need' => 'この項目は必ずご記入ください。',
		     'err_mail' => 'Ｅメールアドレスは正確に入力してください。',
		     'err_tsize' => '入力文字数が上限を超えています。',
		     'err_fsize' => 'ファイルサイズが上限を超えています。',
	         'err_fext' => 'ファイルの種類が正しくありません。',
	         'err_fname' => 'ファイルに使用できる文字は[0-9A-Za-z.-_]のみです。',
	         'err_num' => '数字で正しく入力してください。',
	         'err_over' => '本受付は終了いたしました。',
	         'err_mailto' => '送信先が不明です。',  // 未使用
	         'err_dummy' => '配列末ダミーデータ、不使用'
	         );
	         */
	//表示html
// 別ファイル名にするときはここに
	$r_config['html']['index'] = 'inquiry.html'; 
	$r_config['html']['cancel'] = 'cancel.html'; 
	$r_config['html']['check'] = 'confirm.html'; 
	$r_config['html']['ccheck'] = 'c_confirm.html'; 
	$r_config['html']['thanks'] = 'thanks.html'; 
	$r_config['html']['cthanks'] = 'c_thanks.html'; 

    return true;
}

// 設定ここまで
?>