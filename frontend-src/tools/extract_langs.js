// One-off helper: recover src/langs/*.json dictionaries from the old production bundle.
// CRA inlines large JSON modules as JSON.parse('...') calls inside the bundle.
// Usage: node tools/extract_langs.js ../frontend/static/js/main.2ce27324.js
const fs = require('fs');
const path = require('path');

const bundle = fs.readFileSync(process.argv[2], 'utf8');
const re = /JSON\.parse\('((?:[^'\\]|\\.)*)'\)/g;
const found = [];
let m;
while ((m = re.exec(bundle))) {
  if (m[1].includes('sidebar')) found.push(m[1]);
}
console.log('json modules containing "sidebar":', found.length);

const outDir = path.join(__dirname, '..', 'src', 'langs');
fs.mkdirSync(outDir, { recursive: true });

found.forEach((raw, i) => {
  const txt = raw.replace(/\\'/g, "'").replace(/\\\\/g, '\\');
  const obj = JSON.parse(txt);
  const home = (obj.sidebar && obj.sidebar.Home) || '';
  let lang = 'dict' + i;
  if (home === 'Home') lang = 'en';
  else if (/Главная/i.test(home)) lang = 'ru';
  else if (/Бош|Асосий/i.test(home)) lang = 'uz';
  console.log(i, 'sidebar.Home =', JSON.stringify(home), '=>', lang, 'top-level keys:', Object.keys(obj).length);
  fs.writeFileSync(path.join(outDir, lang + '.json'), JSON.stringify(obj, null, 2) + '\n');
});
