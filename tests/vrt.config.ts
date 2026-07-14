// サーバー設定
export const DEV_BASE = 'https://conf:u3znt1qp@conf.webmasters.co.jp/wms/';
export const PROD_BASE = 'https://www.webmasters.co.jp';

// 比較対象ページ
export const pages = [
	'/',
	'/philosophy/',
	'/service/',
	'/works/',
	'/column/',
	'/company/',
	'/specialty/movabletype.html',
	'/specialty/writing.html',
	'/specialty/running.html',
	'/choice/',
];

// レスポンシブ設定
export const devices = [
	{
		name: 'pc',
		viewport: { width: 1280, height: 800 }
	},
	{
		name: 'sp',
		viewport: { width: 375, height: 812 } // iPhone 12 相当
	}
];

// 特定ページ・デバイスで実行する操作
// 追加したい操作があれば、type を増やすだけで対応できます。
export const interactionConfigs = [
	{
		pagePath: '/',
		deviceName: 'sp',
		actions: [
			{ type: 'click', selector: '#spMenu', waitMs: 500 }
		]
	},
	{
		pagePath: '/choice/',
		deviceName: 'pc',
		actions: [
			{ type: 'click', selector: '.mainSection .btn', waitMs: 500 }
		]
	},
];
