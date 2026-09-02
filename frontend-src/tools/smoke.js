// Headless smoke test of the built app through Microsoft Edge (Chrome DevTools Protocol, no npm deps).
// Usage: node tools/smoke.js <baseUrl> <token> <outDir> [routes...]
// Prints console/runtime errors per route and saves a screenshot per route into outDir.
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');
const http = require('http');

const [baseUrl, token, outDir, ...routeArgs] = process.argv.slice(2);
if (!baseUrl || !token || !outDir) {
  console.error('usage: node tools/smoke.js <baseUrl> <token> <outDir> [routes...]');
  process.exit(2);
}
const routes = routeArgs.length ? routeArgs : ['/', '/analytics', '/pivot', '/analyze/distributors'];
const PORT = 9333;
const EDGE = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
fs.mkdirSync(outDir, { recursive: true });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const getJson = (url) => new Promise((resolve, reject) => {
  http.get(url, (res) => { let b = ''; res.on('data', (d) => (b += d)); res.on('end', () => { try { resolve(JSON.parse(b)); } catch (e) { reject(e); } }); }).on('error', reject);
});

(async () => {
  const profile = path.join(process.env.TEMP || '.', 'edge-smoke-profile');
  const edge = spawn(EDGE, ['--headless=new', `--remote-debugging-port=${PORT}`, `--user-data-dir=${profile}`, '--window-size=1440,1000', '--no-first-run', '--no-default-browser-check', '--disable-gpu', 'about:blank'], { stdio: 'ignore' });
  let targets = null;
  for (let i = 0; i < 40 && !targets; i++) { await sleep(500); try { targets = await getJson(`http://127.0.0.1:${PORT}/json/list`); } catch (e) { /* not up yet */ } }
  if (!targets) { console.error('Edge did not start'); edge.kill(); process.exit(1); }
  const page = targets.find((t) => t.type === 'page');
  const ws = new WebSocket(page.webSocketDebuggerUrl);
  await new Promise((r) => (ws.onopen = r));
  let id = 0;
  const pending = new Map();
  const errors = [];
  ws.onmessage = (ev) => {
    const msg = JSON.parse(ev.data);
    if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); return; }
    if (msg.method === 'Runtime.exceptionThrown') errors.push('EXCEPTION: ' + (msg.params.exceptionDetails.exception?.description || msg.params.exceptionDetails.text || '').split('\n').slice(0, 3).join(' | '));
    if (msg.method === 'Runtime.consoleAPICalled' && (msg.params.type === 'error' || msg.params.type === 'warning')) {
      const text = msg.params.args.map((a) => a.value ?? a.description ?? '').join(' ').slice(0, 300);
      if (msg.params.type === 'error') errors.push('CONSOLE.ERROR: ' + text); else if (/Warning: (Each child|Failed prop|Cannot update|Can't perform)/.test(text)) errors.push('REACT.WARN: ' + text.slice(0, 200));
    }
    if (msg.method === 'Log.entryAdded' && msg.params.entry.level === 'error') errors.push('LOG: ' + msg.params.entry.text.slice(0, 200) + (msg.params.entry.url ? ' @ ' + msg.params.entry.url.slice(0, 120) : ''));
  };
  const send = (method, params = {}) => new Promise((resolve) => { const i = ++id; pending.set(i, resolve); ws.send(JSON.stringify({ id: i, method, params })); });
  const evaluate = async (expr) => { const r = await send('Runtime.evaluate', { expression: expr, returnByValue: true, awaitPromise: true }); return r.result && r.result.result ? r.result.result.value : undefined; };

  await send('Runtime.enable'); await send('Page.enable'); await send('Log.enable');
  await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });

  // login by token
  await send('Page.navigate', { url: baseUrl + '/login' });
  await sleep(2500);
  await evaluate(`sessionStorage.setItem('token', ${JSON.stringify(token)}); 'ok'`);

  const report = [];
  for (const route of routes) {
    errors.length = 0;
    await send('Page.navigate', { url: baseUrl + route });
    await sleep(9000);
    if (route === '/pivot') {
      await evaluate(`(() => { const b = [...document.querySelectorAll('.an-toolbar .btn-primary')].find(x => !x.disabled); if (b) { b.click(); return 'clicked'; } return 'no button'; })()`);
      await sleep(7000);
    }
    if (route === '/analytics') {
      // open the drill-down for the first top row, then close it
      await evaluate(`(() => { const r = document.querySelector('.an-table tr.clickable'); if (r) { r.click(); return 'row clicked'; } return 'no row'; })()`);
      await sleep(4000);
      await send('Page.captureScreenshot', { format: 'png' }).then((r) => fs.writeFileSync(path.join(outDir, 'analytics-drill.png'), Buffer.from(r.result.data, 'base64')));
      await evaluate(`(() => { const b = document.querySelector('.modal .btn-close'); if (b) b.click(); return 'closed'; })()`);
      await sleep(800);
      // select a region on the map through the table
      await evaluate(`(() => { const rows = document.querySelectorAll('.an-geo-grid .an-table tr.clickable'); if (rows[0]) { rows[0].click(); return 'region clicked'; } return 'no region row'; })()`);
      await sleep(5000);
    }
    const info = await evaluate(`JSON.stringify({ title: document.title, path: location.pathname, text: document.body.innerText.length, loaders: document.querySelectorAll('.loading').length, cards: document.querySelectorAll('.an-card, .card').length, svg: document.querySelectorAll('svg.highcharts-root').length, tables: document.querySelectorAll('table').length })`);
    const metrics = await send('Page.getLayoutMetrics');
    const height = Math.min(4000, Math.ceil(metrics.result.cssContentSize ? metrics.result.cssContentSize.height : metrics.result.contentSize.height));
    await send('Emulation.setDeviceMetricsOverride', { width: 1440, height, deviceScaleFactor: 1, mobile: false });
    await sleep(500);
    const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
    const file = path.join(outDir, route.replace(/[^a-z0-9]+/gi, '_').replace(/^_|_$/g, '') || 'home') + '.png';
    fs.writeFileSync(file, Buffer.from(shot.result.data, 'base64'));
    await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });
    report.push({ route, info: JSON.parse(info), errors: [...errors], file });
  }
  ws.close();
  edge.kill();
  for (const r of report) {
    console.log(`\n=== ${r.route} -> ${r.file}`);
    console.log('   ', JSON.stringify(r.info));
    if (r.errors.length) r.errors.slice(0, 12).forEach((e) => console.log('   !', e)); else console.log('    no errors');
  }
  process.exit(0);
})().catch((e) => { console.error(e); process.exit(1); });
