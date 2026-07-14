/* global platform */

jQuery(function ($) {
	"use strict";

	const $body = $('body');
	const $gNav = $('#gHeader .navigation');
	const $btMenu = $('.btMenu', $gNav);

	/* IE/mobile check */

	if (platform.name === 'IE') { // IE
		$body.addClass('ie');
	} else if (platform.os.family === 'iOS' || platform.os.family === 'Android') { // mobile device
		$('html').addClass('mobile');
	} else if (platform.os.family === 'OS X' && typeof document.ontouchstart !== 'undefined') { // iPad
		$('html').addClass('mobile');
	}

	// gNav
	
	$btMenu.on('click', function (ev) {
		ev.preventDefault();
		$gNav.toggleClass('active');
	});
	
	$('nav .hasChild', $gNav).on('click', function (ev) {
		if ($btMenu.is(':visible')) {
			ev.preventDefault();
			$(this).toggleClass('active').siblings('ul').stop().slideToggle();
		}
	}).parent().on('mouseenter', function () {
		if (!$btMenu.is(':visible')) {
			$(this).find('ul').stop().slideDown();
		}
	}).on('mouseleave', function () {
		if (!$btMenu.is(':visible')) {
			$(this).find('ul').stop().slideUp();
		}
	});

	// top

	$('#columns .btExpand').on('click', function (ev) {
		ev.preventDefault();
		$('#columns .table').toggleClass('showAdd');
		$('#columns .add').stop().slideToggle(500);
	});

	// misc

	$('a[href^="#"]').smoothScroll({
		beforeScroll: function () {
			if ($gNav.hasClass('active')) {
				$('.btMenu', $gNav).trigger('click');
			}
		}
	});
	
	$('a[href*="#"]', $gNav).not('.hasChild').smoothScroll({
		beforeScroll: function () {
			if ($gNav.hasClass('active')) {
				$('.btMenu', $gNav).trigger('click');
			}
		}
	});
});