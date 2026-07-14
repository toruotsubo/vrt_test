$(function () {
	"use strict";

	const $window = $(window);
	const $body = $('body');
	const $header = $('#header');

	const hostname = window.location.hostname;

	if (hostname === 'www-webmasters-co-jp.translate.goog' || hostname === 'wms202305-sakura-ne-jp.translate.goog') {
		$body.addClass('translated');
	}

	/* ---------- header関連 ---------- */

	$window.on('scroll', function () {
		if ($window.scrollTop() <= 100) {
			$header.addClass('onTop');
		} else {
			$header.removeClass('onTop');
		}
	}).trigger('scroll');

	$('#spMenu').on('click', function (ev) {
		ev.preventDefault();
		$('#gNav').toggleClass('active');
	});

	/* ---------- スライダー ---------- */

	if ($('#topMvBanner').length > 0) {
		$('#topMvBanner').slick({
			arrows: false,
			autoplay: true,
			dots: true
		});
	}

	if ($('#topic .list li').length > 1) {
		$('#topic .list ul').slick({
			arrows: false,
			autoplay: true,
			vertical: true
		});
	}

	/*$('#works .slider ul').bxSlider({
		slideMargin: 32,
		pager: false,
		nextText: '',
		prevText: '',
		nextSelector: '#works .btNext',
		prevSelector: '#works .btPrev',
		minSlides: 3,
		maxSlides: 3,
		slideWidth: 312
	});*/

	//$('a[href^=#]').smoothScroll();

	/*$('a[data-origial-href]').each(function () {
		const $this = $(this);
		const origialHref = $this.attr('data-origial-href');
		$this.attr('href', origialHref);
	});*/

	/* ---------- 自動翻訳制御 ---------- */

	// 適用URL

	const inclusion = [
		'https://www.webmasters.co.jp/'
	];

	// 除外URL

	const exclusion = [
		'https://www.webmasters.co.jp/contact/mf.html?to=clients',
		'https://www.webmasters.co.jp/contact/mf.html?to=staffs',
		'https://www.webmasters.co.jp/contact/mf.html?to=others',
		'https://www.webmasters.co.jp/test_contact/mf.html?to=clients',
		'https://www.webmasters.co.jp/test_contact/mf.html?to=staffs',
		'https://www.webmasters.co.jp/test_contact/mf.html?to=others'
	];

	// 翻訳ページ判定
	// falseの場合は何もしない
	if (hostname.match(/^.+\.translate\.goog$/)) {

		$('a').each((i, e) => {
			const $target = $(e);
			const href = $target.attr('href');
			let writeback = ""; // 書き戻しurl

			// 外部リンク判定
			// trueの場合、元のurlで書き戻す
			if (href.match(/^https:\/\/translate\.google\.com\//)) {
				// 元のurlを取得
				writeback = href.match(/u=http.+$/)[0];
				writeback = writeback.replace(/^u=/, '');

				// google翻訳向けの場合、htmlエンコードを戻す
				if (writeback.match(/^https:.+\.translate\.goog\//)) {
					writeback = writeback.replace(/%3D/g, '=');
					writeback = writeback.replace(/%26/g, '&');
				}
			} else {
				// リンク復元
				let href01 = /^(.+\.translate\.goog)/.exec(href)[1]; // protocol+host
				let href02 = /^.+\.translate\.goog\/(.*)$/.exec(href)[1]; // pathname+search
				href01 = href01.replace(/\.translate\.goog/, '');
				href01 = href01.replace(/-/g, '.');
				href01 = href01.replace(/\.\./g, '-');
				href02 = href02.replace(/[\?&]_x_tr_sl.+$/, '');
				const currenthref = href01 + '/' + href02;
				//console.log(currenthref);

				// 適用URL外判定
				// trueの場合、復元URLで書き戻す
				let isInclude = false;
				let isHttp = false;

				for (let i = 0; i < inclusion.length; ++i) {
					let testhref = inclusion[i];

					if (testhref.match(/^http:\/\//)) {
						testhref = testhref.replace(/^http:/, 'https:');
						isHttp = true;
					}

					if (currenthref.indexOf(testhref) >= 0) {
						isInclude = true;
						break;
					}
				}

				if (!isInclude) {
					if (isHttp) {
						writeback = currenthref.replace(/^https:/, 'http:');
					} else {
						writeback = currenthref;
					}
				}

				// 除外URL判定
				// trueの場合、除外URLで書き戻す
				for (let i = 0; i < exclusion.length; ++i) {
					let testhref = exclusion[i];

					if (testhref.match(/^http:\/\//)) {
						testhref = testhref.replace(/^http:/, 'https:');
					}

					if (currenthref === testhref) {
						writeback = exclusion[i];
						break;
					}
				}
			}

			// 書き戻し
			if (writeback) {
				//console.log(writeback);
				$target.attr('href', writeback);
			}
		});
	}
});