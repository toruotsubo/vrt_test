// サーバー設定
export const DEV_BASE = 'https://conf:u3znt1qp@conf.webmasters.co.jp/wms-vrt_test/';
export const PROD_BASE = 'https://www.webmasters.co.jp';

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

export interface Action {
	type: 'click' | 'hover' | 'wait';
	selector?: string;
	ms?: number;
	waitMs?: number;
}

export interface Interaction {
	deviceName: string;
	actions: Action[];
}

export interface PageConfig {
	path: string;
	interactions?: Interaction[];
}

// 比較対象ページと操作の設定
export const pages: PageConfig[] = [
	{
		path: '/',
		interactions: [
			{
				deviceName: 'sp',
				actions: [
					{ type: 'click', selector: '#spMenu', waitMs: 500 }
				]
			}
		]
	},
	{ path: '/philosophy/' },
	{ path: '/service/' },
	{ path: '/works/' },
	{ path: '/column/' },
	{ path: '/company/' },
	{ path: '/specialty/movabletype.html' },
	{ path: '/specialty/writing.html' },
	{ path: '/specialty/running.html' },
	{
		path: '/choice/',
		interactions: [
			{
				deviceName: 'pc',
				actions: [
					{ type: 'click', selector: '.mainSection .btn', waitMs: 500 }
				]
			}
		]
	}
];
