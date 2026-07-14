<?php
# 汎用フォームメール初期設定
function config(&$r_cnf) { 
	$r_cnf = array(
	//一般設定 必ず設定すること
		//フォーム名
		'formname' => 'wmsform01',
		//DB設定
		'dbname' => 'wms202305_wmsinquiry',	// DB名
		'dbhost'   => 'mysql57.wms202305.sakura.ne.jp',	//ホスト名
		'dbuser'   => 'wms202305',	//ユーザ名
		'dbpasswd' => 'dbg2uj-w5q-y59d32dxw',	//パスワード
//		'dbpasswd' => 'password',	//パスワード
//		'dbname' => 'd02183bqdb1',	// DB名
//		'dbhost'   => 'localhost',	//ホスト名
//		'dbuser'   => 'd02183bq',	//ユーザ名
//		'dbpasswd' => 'dRqr8Kr29FuKnVjK',	//パスワード
		//入力項目数
		'items_max' => 20, //最大項目数（db_form_input_dataのin_XXXの最大数：必要に応じて増やす）
		//ログファイル
		'log' => 'log/form.log',
		//メール出力ログファイル
		'maillog' => 'log/mail.log',
		//ファイルアップロード一時ディレクトリ 2017/10/29
		'tempdir' => 'temp',
		//ファイルアップロード許可拡張子 クライアント個別はDBのupload_ext_listで 2017/11/29
		'def_ext_list' => 'txt,csv,doc,docx,xls,xlsx,pdf,png,jpg,jpeg,zip,ppt,pptx,bmp,gif',
		//ウィルスチェックコマンド 2017/12/04
		'viruscmd' => '',
		//ウィルスチェックエラーメッセージ
		'virusmsg' => "※ウィルス感染ファイルと診断されたためリンクしません※",
		//ウィルス感染ファイル移動先
		'virusdir' => "virus",
		//テキスト入力可能最大文字数 2017/12/04
		'textmax' => 10000,
		//入力エンコード（基本的に変更不可）
		'encode' => 'UTF-8',
		//チェック画面モードパラメータ文言（基本的に変更不可）
		'confmode' => 'mode',
		//チェック画面submitボタン名（基本的に変更不可）
		'modename' => 'action',
		//入力エラーメッセージ 日本語以外のとき変更する
		'errmsg' => array(
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
	         ),
	//表示html
		'html' => array(
			'index' => 'mf.html', 
			'check' => 'confirm.html',
			'ccheck' => 'check_c.html', //キャンセルチェック
			'err'   => 'error.html',
			'cerr'   => 'error_c.html', // キャンセルエラー
			'thanks' => 'thanks.html',
			'cthanks' => 'thanks_c.html', // キャンセルthanks
			'template' => 'template.html', // 汎用テンプレート %body%に適宜入れる
			'wait'   => 'thanks_w.html', // キャンセル待ちhtml
			'mthanks' => 'thanks_m.html', // メール受付thanks
			'over'    => 'over.html' // 受付終了html
			),
	//メールフォーマットファイル
		'mail' => array(
			'dir' => 'mail/',
			'mail' => 'mail.txt',
			'reply' => 'reply.txt',
			'cancel' => 'cancel.txt',
			'wait' => 'wait.txt' // キャンセル待ち
			)
    );
    //入力形式定義
	define ("ANY", 0); // 任意
	define ("NEED", 1); // 必須
	define ("MAILNEED", 2); // 必須メールアドレス
	define ("MAILANY", 3); // 任意メールアドレス
	define ("FILENEED", 4); // 必須ファイルアップロード
	define ("FILEANY", 5); // 任意ファイルアップロード
	define ("PARTY", 6); // 同行者
	define ("MAILTO", 7); // 送信先（送信先選択のとき）
	define ("ACPTNO", 8); // 登録番号（キャンセル用）
	define ("PASSWD", 9); // パスワード
	define ("MAILCC", 10); // 同報先
	define ("HONEYPOT", 99); // ハニーポット用 
	
	//MD5キー
	define ("MD5KEY",'QFeykOUzOtBK');

	//複数選択区切り文字
	define ("DELIMITER",'|');
	define ("REP_DLMT",'｜');

    return true;
}

// 設定ここまで
?>