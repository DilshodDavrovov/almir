# ALMIR STATISTICS — frontend source

This is the React application source, recovered from the production bundle's
source map (`frontend/static/js/main.2ce27324.js.map`) on 2026-09-02, plus the
new **Аналитика продаж** (`/analytics`) and **Сводный отчёт** (`/pivot`)
sections and a visual refresh (`src/jsx/theme.css`).

The repository previously shipped only the compiled output in `../frontend/`
(no source). That folder is still what the app actually serves — this folder
is the buildable source for it.

## Develop

```
npm install
npm start          # dev server on :3000, proxies API calls per .env.local
```

`.env.local` (gitignored) sets `REACT_APP_API_URL=http://localhost:8000` for
local development against the stack in `D:\almir_stack`. Without it the app
falls back to the production API (`https://api2.almir.uz`), baked in at build
time — see `src/services/AxiosInstance.js`.

## Build & deploy to `../frontend/`

```
npm run build
```

Then copy the new hashed files into `../frontend/` and update the two entry
files, replacing the previous hashes:

```
../frontend/index.html
../frontend/asset-manifest.json
../frontend/static/js/main.<hash>.js(.map)
../frontend/static/css/main.<hash>.css(.map)
```

Media assets (`static/media/*`) are unchanged from the original bundle and
don't need to be re-copied unless you add new images/fonts.

**For a production deploy**, build with the real API origin so it's baked
into the bundle instead of `localhost:8000`:

```
REACT_APP_API_URL=https://api2.almir.uz npm run build
```

(or delete `.env.local` before building, which falls back to the same
default).

## Notes on the recovery

- `src/langs/{ru,en,uz}.json` were extracted from `JSON.parse('...')` calls
  inlined in the old bundle (`tools/extract_langs.js`). Keys used by the new
  Analytics/Pivot sections were added on top (`tools/add_translations.js`).
- A handful of files that only exist in `node_modules` in the map
  (`highcharts-react.min.js`, webpack runtime chunks) were skipped; the
  matching npm packages are declared in `package.json` instead.
- `craco.config.js` relaxes webpack 5's "fully specified" ESM resolution,
  needed for `react-chartjs-2` (`react/jsx-runtime`) under React 17.


## Design layer (theme)

The visual design lives in `src/jsx/theme.css` and is imported **last** from `src/index.js`
so it always wins the cascade over the vendor template (`src/css/style.css`, imported first in
`src/App.js`). Do not move these imports: the vendor file is 29k lines of attribute-selector
themes and will override anything that lands before it in the bundle.

- Tokens (`--primary`, `--ink`, `--line`, radii, shadows, Inter font) are declared on
  `:root, body, body[data-primary]` so the vendor `data-primary="color_N"` variants cannot
  override them. `src/context/ThemeContext.js` sets all template attributes to neutral values
  (`color_1`, `inter`, `static`).
- Layout: dark brand bar (`.header` + `.nav-header`), sticky white navigation (`.dlabnav`),
  content, footer. Profile / language / help / contact live in `layouts/nav/Header.js`;
  `layouts/nav/SideBar.js` only renders the menu.
- Page-specific styles: `pages/home.css` (dashboard), `components/Analytics/analytics.css`
  (analytics + pivot), `components/DataTypeSwitch/dataTypeSwitch.css`.
- FontAwesome comes from a **FA5** kit: FA6 icon names render nothing.
- Legacy hacks in `src/jsx/filtering.css` that pinned the header (`position: fixed !important`)
  were removed; keep it that way or the sticky navigation disappears under the header.
