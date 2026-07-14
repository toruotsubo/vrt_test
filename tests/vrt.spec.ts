// tests/vrt.spec.js
import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { pages, devices, DEV_BASE, PROD_BASE, interactionConfigs } from './vrt.config';

const { default: pixelmatch } = require('pixelmatch');
const { PNG } = require('pngjs');

const screenshotPath = (name: string) =>
	path.join(process.cwd(), 'screenshots', name);

const ensureScreenshotDir = (filePath: string) => {
	fs.mkdirSync(path.dirname(filePath), { recursive: true });
};

const gotoAndWait = async (page: any, url: string) => {
	await page.goto(url, { waitUntil: 'domcontentloaded' });
	await page.waitForLoadState('load');
	await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => undefined);
};

const runConfiguredInteractions = async (page: any, pagePath: string, deviceName: string) => {
	const actionHandlers: Record<string, (page: any, action: any) => Promise<void>> = {
		click: async (targetPage, action) => {
			await targetPage.click(action.selector);
		},
		hover: async (targetPage, action) => {
			await targetPage.hover(action.selector);
		},
		wait: async (_targetPage, action) => {
			await new Promise((resolve) => setTimeout(resolve, action.ms ?? 0));
		}
	};

	for (const config of interactionConfigs) {
		if (config.pagePath !== pagePath || config.deviceName !== deviceName) {
			continue;
		}

		for (const action of config.actions) {
			const handler = actionHandlers[action.type];
			if (!handler) {
				throw new Error(`Unsupported interaction type: ${action.type}`);
			}

			await handler(page, action);

			if (action.waitMs) {
				await page.waitForTimeout(action.waitMs);
			}
		}
	}
};

test.describe('VRT: multiple pages + devices', () => {
	for (const pagePath of pages) {
		for (const device of devices) {
			test(`compare ${pagePath} on ${device.name}`, async ({ page }) => {
				test.setTimeout(120000);

				const devUrl = `${DEV_BASE}${pagePath}`;
				const prodUrl = `${PROD_BASE}${pagePath}`;
				const safeName = pagePath.replace(/\//g, '_');
				let menuDevScreenshotPath = '';
				let menuProdScreenshotPath = '';

				await page.setViewportSize(device.viewport);

				// dev
				await gotoAndWait(page, devUrl);
				await page.waitForLoadState('load');
				const dev_actualViewportSize = await page.evaluate(() => {
					return {
						width: document.scrollingElement?.scrollWidth ?? 0,
						height: document.scrollingElement?.scrollHeight ?? 0,
					};
				});
				await page.setViewportSize(dev_actualViewportSize);
				await page.reload();
				await page.waitForLoadState('load');
				const devScreenshotPath = screenshotPath(`${safeName}_${device.name}_dev.png`);
				ensureScreenshotDir(devScreenshotPath);
				await page.screenshot({
					path: devScreenshotPath,
					fullPage: true
				});

				const devInteractions = interactionConfigs.filter((config) => config.pagePath === pagePath && config.deviceName === device.name);
				if (devInteractions.length > 0) {
					await runConfiguredInteractions(page, pagePath, device.name);
					menuDevScreenshotPath = screenshotPath(`${safeName}_${device.name}_menu_dev.png`);
					ensureScreenshotDir(menuDevScreenshotPath);
					await page.screenshot({
						path: menuDevScreenshotPath,
						fullPage: true
					});
				}

				await page.setViewportSize(device.viewport);

				// prod
				await gotoAndWait(page, prodUrl);
				await page.waitForLoadState('load');
				const prod_actualViewportSize = await page.evaluate(() => {
					return {
						width: document.scrollingElement?.scrollWidth ?? 0,
						height: document.scrollingElement?.scrollHeight ?? 0,
					};
				});
				await page.setViewportSize(prod_actualViewportSize);
				await page.reload();
				await page.waitForLoadState('load');
				const prodScreenshotPath = screenshotPath(`${safeName}_${device.name}_prod.png`);
				ensureScreenshotDir(prodScreenshotPath);
				await page.screenshot({
					path: prodScreenshotPath,
					fullPage: true
				});

				const prodInteractions = interactionConfigs.filter((config) => config.pagePath === pagePath && config.deviceName === device.name);
				if (prodInteractions.length > 0) {
					await runConfiguredInteractions(page, pagePath, device.name);
					menuProdScreenshotPath = screenshotPath(`${safeName}_${device.name}_menu_prod.png`);
					ensureScreenshotDir(menuProdScreenshotPath);
					await page.screenshot({
						path: menuProdScreenshotPath,
						fullPage: true
					});
				}

				await page.setViewportSize(device.viewport);

				// 画像読み込み
				const devImg = PNG.sync.read(
					fs.readFileSync(devScreenshotPath)
				);
				const prodImg = PNG.sync.read(
					fs.readFileSync(prodScreenshotPath)
				);

				const { width, height } = devImg;
				const diff = new PNG({ width, height });

				const mismatch = pixelmatch(
					devImg.data,
					prodImg.data,
					diff.data,
					width,
					height,
					{ threshold: 0.1 }
				);

				const diffFilePath = screenshotPath(`${safeName}_${device.name}_diff.png`);
				ensureScreenshotDir(diffFilePath);
				fs.writeFileSync(
					diffFilePath,
					PNG.sync.write(diff)
				);

				expect(mismatch, `差分ピクセル数: ${mismatch}`).toBe(0);

				if (devInteractions.length > 0) {
					const devImg = PNG.sync.read(
						fs.readFileSync(menuDevScreenshotPath)
					);
					const prodImg = PNG.sync.read(
						fs.readFileSync(menuProdScreenshotPath)
					);

					const { width, height } = devImg;
					const diff = new PNG({ width, height });

					const mismatch = pixelmatch(
						devImg.data,
						prodImg.data,
						diff.data,
						width,
						height,
						{ threshold: 0.1 }
					);

					const menuDiffFilePath = screenshotPath(`${safeName}_${device.name}_menu_diff.png`);
					ensureScreenshotDir(menuDiffFilePath);
					fs.writeFileSync(
						menuDiffFilePath,
						PNG.sync.write(diff)
					);

					expect(mismatch, `差分ピクセル数: ${mismatch}`).toBe(0);
				}
			});
		}
	}
});