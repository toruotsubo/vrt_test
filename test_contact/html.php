<?php
# 汎用フォームメールhtml設定
// 2017/10/28 K.Nakayama v1.3 送信先指定なし対応
// 2017/10/29 K.Nakayama v1.4 エラー表示変更

function htmlSet($typ) {
	global $config;
	$buf = "";
	
	switch ($typ) {
		case "mthanks" :
			$buf = mthanks_html($config);
			break;
		case "check" :
			$buf = check_html($config);
			break;
		case "thanks" :
			$buf = thanks_html($config);
			break;
		case "over" :
			$buf = over_html($config);
			break;
		case "wait" :
			$buf = wait_html($config);
			break;
		case "cthanks" :
			$buf = cthanks_html($config);
			break;
		case "err" :
			$buf = inputerr_html($config);
			break;
		default :
			$buf = syserr_html($config);
			break;
		
	}

	return $buf ;
}

function mthanks_html($config) {

	$body = <<<__MTHK__
<!-- form.php set start -->
<table border="0" cellpadding="10" cellspacing="10" > 
 <td colspan="2" align="left" valign="top" class="text"><span class="title5">■ご登録ありがとうございました。</span><br /><br />
  追ってご登録のメールアドレスに登録フォームURLをお送りいたします。<br />
  念のため、この画面を控えとして保存してください。<br />
  登録後３時間経過してもメールが届かないようでしたら、お手数ですが下記にお問い合わせをおねがいいたします。 <br />
    <br />
    %html_mailmsg%
</td>
</tr>
</table>
<!-- form.php set end -->
__MTHK__;

return($body);

} 

function check_html($config) {

	$body = <<<__CHK__
<!-- form.php set start -->
<form id="FormName" action="{$_SERVER['SCRIPT_NAME']}" method="POST">
<table border="0" cellpadding="10" cellspacing="10" > 
  <tr>
    <td colspan="2" align="left" valign="top" class="text"><span class="title5">■確認画面</span><br /><br />
	ご入力内容をご確認のうえ、まちがいがなければ「送信」ボタンを押してください。<br />
	修正する場合は「修正」ボタンで前画面に戻ってください。<br />
	<br />
	<span class="text">※ブラウザの「戻る」ボタンは使用しないでください。</span><br />
	%html_wait%
	</td>
    </tr>
<!-- RoopFormat Start -->
  <tr>
    <th align="left" valign="top">%name%</th>
    <td align="left" class="table-l2">
	%value%</td>
  </tr>
<!-- RoopFormat End -->  <tr>
</table>	
	  <p>&nbsp;</p>
      <p>
		<button type="submit" name="action" value="submit">送　信</button>
  		<button type="submit" name="action" value="retry">修　正</button>
<input type="hidden" name="mode" value="%mode%">
<input type="hidden" name="formname" value="%formname%">
<input type="hidden" name="msgid" value="%msgid%">
</form>
<!-- form.php set end -->
__CHK__;

return($body);


}


function inputerr_html($config) {

	$body = <<<__ERR__
<!-- form.php set start -->
<form id="FormName" action="{$_SERVER['SCRIPT_NAME']}" method="POST">
<table border="0" cellpadding="10" cellspacing="10" > 
  <tr>
    <td colspan="2" align="left" valign="top" class="text"><span class="title5">■入力エラー</span><br /><br />
	 以下の記入事項をご確認ください。<br />
	「修正」ボタンで前画面に戻って修正してください。
	<br /><br />
	※ブラウザの「戻る」ボタンは使用しないでください。</td>
    </tr>
  <tr>
	<td align="left" nowrap="nowrap" class="text-red">■項目名</td>
	<td align="left" class="text-red">■エラーの原因</td>
  </tr>
<!-- RoopFormat Start -->
  <tr>
    <th align="left" valign="top">%name%</th>
    <td align="left" class="table-l2">
	%value%</td>
  </tr>
<!-- RoopFormat End -->  <tr>
</table>	
	  <p>&nbsp;</p>
      <p>
  		<button type="submit" name="action" value="retry">修　正</button>
<input type="hidden" name="mode" value="%mode%">
<input type="hidden" name="formname" value="%formname%">
<input type="hidden" name="msgid" value="%msgid%">
</form>
<!-- form.php set end -->
__ERR__;

return($body);


}

function thanks_html($config) {

	$body = <<<__THK__
<!-- form.php set start -->
<table border="0" cellpadding="10" cellspacing="10" > 
<td align="left"><span class="title5">■ご登録ありがとうございました。</span><br /><br />
  <p class="title5">登録No ：　%cnt_id%　で受付いたしました。</p>
  <br />
  <p>この画面を控えとして保存してください。</p>
    <br />
    %html_thanksmsg%
</td>
</tr>
</table>
<!-- form.php set end -->
__THK__;

return($body);

} 

function cthanks_html($config) {

	$body = <<<__CTHK__
<!-- form.php set start -->
<table border="0" cellpadding="10" cellspacing="10" > 
<td align="left"><span class="title5">■キャンセル手続きが完了しました。</span><br /><br />
  <p class="title5">登録No ：　%cnt_id%　でキャンセル受付いたしました。</p>
  <br />
  <p>この画面を控えとして保存してください。</p>
    <br />
    %html_thanksmsg%
</td>
</tr>
</table>
<!-- form.php set end -->
__CTHK__;

return($body);

} 

function wait_html($config) {

	$body = <<<__THK__
<!-- form.php set start -->
<table border="0" cellpadding="10" cellspacing="10" > 
<td align="left"><span class="title5">■ご登録ありがとうございました。</span><br /><br />
  <p class="title5">登録No ：　%cnt_id%　でキャンセル待ちを受付いたしました。</p>
  <br />
  <p>この画面を控えとして保存してください。</p>
    <br />
    %html_thanksmsg%
</td>
</tr>
</table>
<!-- form.php set end -->
__THK__;

return($body);

} 


function over_html($config) {

	$body = <<<__OVR__
<!-- form.php set start -->
<form id="FormName" action="{$_SERVER['SCRIPT_NAME']}" method="POST">
<table border="0" cellpadding="10" cellspacing="10" > 
  <tr>
	<td align="left"><span class="title5">■この募集は終了しました。</span><br /><br />
    <br />
    %html_overmsg%
    </tr>
</table>
<!-- form.php set end -->
__OVR__;

return($body);

} 


function syserr_html($config) {
// 2017/10/28 送信先指定なし対応
// 2019/06/14 「戻る」 文言修正
// 2021/03/27 「戻る」 リンク先修正
//	$returl = $config["form_url"] . ((empty($config['file_maillist']))? "" : "?to=%to%") ; // 2017/10/28

	$body = <<<__SYSERR__
<!-- form.php set start -->
<span class="title5">■%errtitle%</span><br /><br />
	 %errmsg%<br><br>
	 <a href="/contact/" target="_parent"><!--入力画面に-->戻る</a><br />
	 <br /><br />
	 %html_errmsg%
<!-- form.php set end -->
__SYSERR__;

return($body);

}
?>
