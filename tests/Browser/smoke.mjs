import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { relative, resolve, sep } from 'node:path';

const baseUrl = process.env.FINTRACK_BASE_URL ?? 'http://127.0.0.1:8765';
const chromePath = process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const outputDir = resolve(process.env.FINTRACK_BROWSER_OUTPUT ?? 'storage/app/stage5-browser');
mkdirSync(outputDir, { recursive: true });
const profileDir = mkdtempSync(resolve(outputDir, 'profile-'));

const browser = spawn(chromePath, [
    '--headless=new', '--no-sandbox', '--disable-gpu', '--disable-extensions',
    '--no-first-run', '--no-default-browser-check', '--remote-allow-origins=*',
    '--remote-debugging-port=0', `--user-data-dir=${profileDir}`, 'about:blank',
], { stdio: 'ignore', windowsHide: true });

const sleep = (ms) => new Promise((done) => setTimeout(done, ms));
async function waitFor(check, label, timeout = 15000) {
    const started = Date.now();
    while (Date.now() - started < timeout) {
        const value = await check();
        if (value) return value;
        await sleep(150);
    }
    throw new Error(`Waktu habis: ${label}`);
}

let socket;
try {
    const portFile = resolve(profileDir, 'DevToolsActivePort');
    const port = await waitFor(() => existsSync(portFile) && Number(readFileSync(portFile, 'utf8').split('\n')[0]), 'Chrome DevTools');
    const target = await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, { method: 'PUT' }).then((response) => response.json());
    socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((done, reject) => { socket.addEventListener('open', done, { once: true }); socket.addEventListener('error', reject, { once: true }); });

    let nextId = 0;
    const pending = new Map();
    const exceptions = [];
    socket.addEventListener('message', ({ data }) => {
        const message = JSON.parse(data);
        if (message.method === 'Runtime.exceptionThrown') exceptions.push(message.params.exceptionDetails.exception?.description ?? 'JavaScript error');
        if (!message.id || !pending.has(message.id)) return;
        const { done, reject } = pending.get(message.id);
        pending.delete(message.id);
        if (message.error) reject(new Error(message.error.message));
        else done(message.result);
    });
    function send(method, params = {}) {
        return new Promise((done, reject) => {
            const id = ++nextId;
            pending.set(id, { done, reject });
            socket.send(JSON.stringify({ id, method, params }));
        });
    }
    async function evaluate(expression) {
        const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (result.exceptionDetails) throw new Error(result.exceptionDetails.text);
        return result.result.value;
    }
    async function visit(path) {
        await send('Page.navigate', { url: new URL(path, baseUrl).toString() });
        await waitFor(() => evaluate(`location.pathname === ${JSON.stringify(new URL(path, baseUrl).pathname)} && Boolean(document.querySelector('h1'))`), `halaman ${path}`);
        await sleep(350);
    }
    async function fill(selector, value) {
        await evaluate(`(() => { const input = document.querySelector(${JSON.stringify(selector)}); if (!input) throw Error('Input tidak ada: ${selector}'); const setter = Object.getOwnPropertyDescriptor(input.tagName === 'TEXTAREA' ? HTMLTextAreaElement.prototype : HTMLInputElement.prototype, 'value').set; setter.call(input, ${JSON.stringify(value)}); input.dispatchEvent(new Event('input', { bubbles: true })); input.dispatchEvent(new Event('change', { bubbles: true })); })()`);
    }
    async function viewport(width, height, mobile) {
        await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: mobile ? 3 : 1, mobile });
        await send('Emulation.setTouchEmulationEnabled', { enabled: mobile });
    }
    function assert(condition, message) { if (!condition) throw new Error(message); }

    await send('Page.enable');
    await send('Runtime.enable');
    await viewport(390, 844, true);
    await visit('/register');
    assert(await evaluate(`document.querySelector('h1')?.textContent.includes('Buat akun')`), 'Halaman daftar gagal dirender');
    await fill('#name', 'Uji Browser');
    await fill('#email', `browser-${Date.now()}@example.test`);
    await fill('#password', 'Browser123!');
    await fill('#password_confirmation', 'Browser123!');
    await evaluate(`document.querySelector('button[type="submit"]').click()`);
    await waitFor(() => evaluate(`location.pathname === '/dashboard'`), 'daftar dan masuk');

    const pages = ['/dashboard', '/transactions', '/wallets', '/budgets', '/analytics', '/reports', '/profile', '/wallets/create', '/transactions/create', '/budgets/create'];
    const results = [];
    for (const size of [{ width: 320, height: 700, mobile: true }, { width: 390, height: 844, mobile: true }, { width: 1280, height: 900, mobile: false }]) {
        await viewport(size.width, size.height, size.mobile);
        for (const path of pages) {
            await visit(path);
            const result = await evaluate(`(() => { const nav = document.querySelector('nav[aria-label="Navigasi ponsel"]'); const sidebar = document.querySelector('aside'); return { path: location.pathname, heading: document.querySelector('h1')?.textContent?.trim(), viewport: innerWidth, width: document.documentElement.scrollWidth, main: !!document.querySelector('#main-content'), navVisible: nav && getComputedStyle(nav).display !== 'none', sidebarVisible: sidebar && getComputedStyle(sidebar).display !== 'none', navTargets: [...(nav?.querySelectorAll('a') ?? [])].map((link) => Math.round(link.getBoundingClientRect().height)) }; })()`);
            assert(result.width <= result.viewport + 1, `${path} overflow pada ${size.width}px: ${result.width}px`);
            assert(result.heading && result.main, `${path} tidak punya heading atau main`);
            assert(size.mobile ? result.navVisible && result.navTargets.every((height) => height >= 44) : result.sidebarVisible, `${path} navigasi tidak sesuai viewport ${size.width}px`);
            results.push({ size: size.width, ...result });
            if (size.width === 390 && path === '/dashboard') {
                const screenshot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
                writeFileSync(resolve(outputDir, 'dashboard-mobile.png'), Buffer.from(screenshot.data, 'base64'));
            }
            if (size.width === 1280 && path === '/analytics') {
                const screenshot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
                writeFileSync(resolve(outputDir, 'analytics-desktop.png'), Buffer.from(screenshot.data, 'base64'));
            }
        }
    }

    await viewport(390, 844, true);
    assert(exceptions.length === 0, `Kesalahan JavaScript: ${exceptions.join('; ')}`);
    await visit('/profile');
    await send('Network.enable');
    await send('Network.emulateNetworkConditions', { offline: true, latency: 0, downloadThroughput: 0, uploadThroughput: 0 });
    await evaluate(`document.querySelector('a[href="/reports"]').click()`);
    await waitFor(() => evaluate(`document.querySelector('[role="alert"]')?.textContent.includes('Koneksi terputus')`), 'pesan koneksi terputus');
    assert(exceptions.every((error) => error.includes('HttpNetworkError')), `Kesalahan JavaScript saat jaringan terputus: ${exceptions.join('; ')}`);
    exceptions.length = 0;
    await send('Network.emulateNetworkConditions', { offline: false, latency: 0, downloadThroughput: -1, uploadThroughput: -1 });
    await visit('/wallets/create');
    await fill('#wallet-name', 'Dompet browser');
    await fill('#wallet-balance', '100000');
    await evaluate(`document.querySelector('button[type="submit"]').click()`);
    await waitFor(() => evaluate(`location.pathname === '/wallets' && document.body.textContent.includes('Dompet browser')`), 'menyimpan dompet');
    await visit('/transactions/create');
    assert(await evaluate(`!document.querySelector('button[type="submit"]').disabled`), 'Form transaksi tetap nonaktif');
    assert(await evaluate(`document.querySelector('a[href="#main-content"]')?.textContent.includes('Langsung')`), 'Skip link tidak tersedia');
    assert(exceptions.every((error) => error.includes('HttpNetworkError')), `Kesalahan JavaScript: ${exceptions.join('; ')}`);
    writeFileSync(resolve(outputDir, 'results.json'), JSON.stringify({ passed: results.length, results }, null, 2));
    console.log(`PASS: ${results.length} halaman/viewport; daftar akun, simpan dompet, form transaksi, dan pesan jaringan; tanpa overflow atau error JavaScript.`);
} finally {
    socket?.close();
    browser.kill();
    const relativeProfile = relative(outputDir, profileDir);
    if (relativeProfile && !relativeProfile.startsWith('..') && !relativeProfile.includes(sep)) {
        try { rmSync(profileDir, { recursive: true, force: true }); } catch { /* Chrome may still be releasing its profile. */ }
    }
}
