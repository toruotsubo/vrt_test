/*****************************************
汎用フォーム用JS：デフォルト値設定

2017/11/23 テキストに「,」が入ったとき対応→selectとtextを分ける
2021/12/02 XSS対応
******************************************/

// ラジオボタン・チェックボックス　checked=trueに
function checkvalue(name1,value1) {
	var ele_name = document.getElementsByName(name1);
	if(value1 == "") return ;
	valuelist = value1.split("|");
	for (var i = 0; i < ele_name.length; i++){
		for (var j = 0; j < valuelist.length; j++){
			if(ele_name[i].value == valuelist[j]){
				ele_name[i].checked = true;
			}
		}
	}
}

// テキスト・selectなどelement.valueに値をセット
//2017/11/23 「,」入力対応
function setvalue(name1,value1) {
	var ele_name = document.getElementsByName(name1);
	if(value1 == "") return ;
	ele_name[0].value = value1;
//	valuelist = value1.split("|");
//	valuelist = value1.split(',');
//	for (var i = 0; i < ele_name.length; i++){
//		ele_name[i].value = valuelist[i];
//	}
}

// JSエスケープ処理 2017/11/23追加
function preg_quote (str, delimiter) {
    // Quote regular expression characters plus an optional character  
    // 
    // version: 1107.2516
    // discuss at: http://phpjs.org/functions/preg_quote
    // +   original by: booeyOH
    // +   improved by: Ates Goral (http://magnetiq.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: preg_quote("$40");
    // *     returns 1: '\$40'
    // *     example 2: preg_quote("*RRRING* Hello?");
    // *     returns 2: '\*RRRING\* Hello\?'
    // *     example 3: preg_quote("\\.+*?[^]$(){}=!<>|:");
    // *     returns 3: '\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:'
    return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
}
function unhtmlspecialchars(str){
  return (str + '').replace(/&amp;/g,'&')
                   .replace(/&quot;/g,'"')
                   .replace(/&#039;/g,'\'')
                   .replace(/&lt;/g,'<')
                   .replace(/&gt;/g,'>'); 
}

