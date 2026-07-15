// tests/vrt.spec.ts
import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { pages, devices, DEV_BASE, PROD_BASE, PageConfig, Action } from './vrt.config';

const { default: pixelmatch } = require('pixelmatch');
const { PNG } = require('pngjs');

const screenshotPath = (name: string) =>
	path.join(process.cwd(), 'screenshots', name);

const toSelectorName = (selector: string) =>
	selector
		.replace(/[^a-zA-Z0-9]+/g, '_')
		.replace(/^_+|_+$/g, '')
		.replace(/_+/g, '_');

const ensureScreenshotDir = (filePath: string) => {
	fs.mkdirSync(path.dirname(filePath), { recursive: true });
};

const gotoAndWait = async (page: any, url: string) => {
	await page.goto(url, { waitUntil: 'domcontentloaded' });
	await page.waitForLoadState('load');
	await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => undefined);
};

const runInteractions = async (page: any, actions: Action[]) => {
	const actionHandlers: Record<string, (page: any, action: Action) => Promise<void>> = {
		click: async (targetPage, action) => {
			if (!action.selector) throw new Error('Click action requires a selector');
			await targetPage.click(action.selector);
		},
		hover: async (targetPage, action) => {
			if (!action.selector) throw new Error('Hover action requires a selector');
			await targetPage.hover(action.selector);
		},
		wait: async (_targetPage, action) => {
			await new Promise((resolve) => setTimeout(resolve, action.ms ?? 0));
		}
	};

	for (const action of actions) {
		const handler = actionHandlers[action.type];
		if (!handler) {
			throw new Error(`Unsupported interaction type: ${action.type}`);
		}

		await handler(page, action);

		if (action.waitMs) {
			await page.waitForTimeout(action.waitMs);
		}
	}
};

interface ScreenshotResult {
	normalPath: string;
	interactionPath?: string;
	interactionLabel?: string;
}

const captureSite = async (
	page: any,
	baseUrl: string,
	pageConfig: PageConfig,
	device: any,
	siteLabel: 'dev' | 'prod'
): Promise<ScreenshotResult> => {
	const url = `${baseUrl}${pageConfig.path}`;
	const safeName = pageConfig.path.replace(/\//g, '_');

	await page.setViewportSize(device.viewport);
	await gotoAndWait(page, url);
	await page.waitForLoadState('load');

	const actualViewportSize = await page.evaluate(() => {
		return {
			width: document.scrollingElement?.scrollWidth ?? 0,
			height: document.scrollingElement?.scrollHeight ?? 0,
		};
	});
	await page.setViewportSize(actualViewportSize);
	await page.reload();
	await page.waitForLoadState('load');

	const normalPath = screenshotPath(`${safeName}_${device.name}_${siteLabel}.png`);
	ensureScreenshotDir(normalPath);
	await page.screenshot({
		path: normalPath,
		fullPage: true
	});

	const interaction = pageConfig.interactions?.find(i => i.deviceName === device.name);
	let interactionPath: string | undefined;
	let interactionLabel: string | undefined;

	if (interaction && interaction.actions.length > 0) {
		await runInteractions(page, interaction.actions);
		const selectorName = toSelectorName(
			interaction.actions.find(action => action.selector)?.selector ?? ''
		);
		interactionLabel = selectorName || 'interaction';
		interactionPath = screenshotPath(`${safeName}_${device.name}_${interactionLabel}_${siteLabel}.png`);
		ensureScreenshotDir(interactionPath);
		await page.screenshot({
			path: interactionPath,
			fullPage: true
		});
	}

	return { normalPath, interactionPath, interactionLabel };
};

const compareImages = async (
	img1Path: string,
	img2Path: string,
	diffPath: string,
	testInfo: any,
) => {
	const img1 = PNG.sync.read(fs.readFileSync(img1Path));
	const img2 = PNG.sync.read(fs.readFileSync(img2Path));

	const { width, height } = img1;
	const diff = new PNG({ width, height });
	const mismatch = pixelmatch(
		img1.data,
		img2.data,
		diff.data,
		width,
		height,
		{ threshold: 0.1 }
	);

	if (mismatch > 0) {
		ensureScreenshotDir(diffPath);
		const diffBuffer = PNG.sync.write(diff);
		fs.writeFileSync(diffPath, diffBuffer);
		await testInfo.attach('Screenshot', {
			path: diffPath,
			contentType: 'image/png'
		});
	}
	expect(mismatch, `差分ピクセル数: ${mismatch}`).toBe(0);
};

test.describe('VRT: 本番・開発表示差分検査', () => {
	for (const pageConfig of pages) {
		for (const device of devices) {
			test(`compare ${pageConfig.path} on ${device.name}`, async ({ page }, testInfo) => {
				test.setTimeout(120000);

				const safeName = pageConfig.path.replace(/\//g, '_');

				// 1. Capture Dev site
				const devResult = await captureSite(page, DEV_BASE, pageConfig, device, 'dev');

				// 2. Capture Prod site
				const prodResult = await captureSite(page, PROD_BASE, pageConfig, device, 'prod');

				// 3. Compare normal screenshots
				const diffFilePath = screenshotPath(`${safeName}_${device.name}_diff.png`);
				await compareImages(
					devResult.normalPath,
					prodResult.normalPath,
					diffFilePath,
					testInfo,
				);

				// 4. Compare interaction screenshots if present
				if (devResult.interactionPath && prodResult.interactionPath) {
					const label = devResult.interactionLabel || 'interaction';
					const interactionDiffFilePath = screenshotPath(`${safeName}_${device.name}_${label}_diff.png`);
					await compareImages(
						devResult.interactionPath,
						prodResult.interactionPath,
						interactionDiffFilePath,
						testInfo,
					);
				}
			});
		}
	}
});