# EstateHub (local preview)

This small static site demonstrates the EstateHub UI. The repository uses `css/style.css` for styling and `js/script.js` for UI behaviors (mobile menu, modals, password toggles).

## Quick preview (local)

From the project root (`/home/swaynel/Documents/projects/ESTATE`) run a simple HTTP server and open the site in your browser:

```bash
# Python 3 built-in server (serves on port 8000)
python3 -m http.server 8000

# then open http://localhost:8000
```

## Primary CSS classes

These are the main classes used across pages:

- `.navbar` — top navigation bar container
- `.nav-links` — list of navigation links; gets `.active` when mobile menu is open
- `.menu-toggle` — mobile menu button (☰)
- `.hero` — homepage hero section
- `.features`, `.feature-cards`, `.card` — feature section and cards
- `.form-section`, `.form-container`, `.form-group`, `.form-footer` — login/register forms
- `.password-container`, `.toggle-password` — password input + visibility toggle
- `.btn` — primary button
- `.btn.cancel-btn` / `.btn.secondary` — secondary/cancel buttons
- `.dashboard`, `.sidebar`, `.main-content`, `.cards`, `.card` — dashboard layout and cards
- `.table-section`, `table` — table styling
- `.modal`, `.modal-content` — modal dialogs; `.modal.active` shows the modal

## JS helpers (global functions)

`js/script.js` exposes two small functions for convenience (attached to `window`):

- `openModal(id)` — adds `.active` to the element with the given id
- `closeModal(id)` — removes `.active` from the element with the given id

It also automatically wires up:
- `.menu-toggle` to toggle nearest `.nav-links`
- `.toggle-password` elements to toggle visibility
- Property modal behavior (if `#addPropertyBtn`, `#propertyPopup`, `#cancelPropertyBtn` exist)

## Visual checks / screenshots

I couldn't capture screenshots from this environment. To create screenshots locally you can use Chrome headless or Puppeteer. Example (Chrome headless):

```bash
# Using Google Chrome/Chromium
google-chrome --headless --disable-gpu --window-size=1280,800 --screenshot=homepage.png http://localhost:8000/index.html
```

Or with Puppeteer (nodejs):

```js
const puppeteer = require('puppeteer');
(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  await page.goto('http://localhost:8000/index.html');
  await page.screenshot({ path: 'index.png', fullPage: true });
  await browser.close();
})();
```

## Next steps I can take for you

- Extract additional modals to be auto-wired using `data-` attributes.
- Improve theme colors/spacings and provide a short design system.
- Run visual regression or produce screenshots here (if you permit a tool that can run headless browser).

If you want me to proceed with any of the above, tell me which.
