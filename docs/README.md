# Docs — how this folder works

Every guide page has **exactly one file**: `docs/guide/<slug>.md`.

There used to be a second, hand-maintained `docs/guide/<slug>.html`
copy of every page for GitHub Pages. That's gone now — it caused the
.md and .html to drift out of sync. The HTML site is generated
automatically instead.

## Editing a guide

Just edit `docs/guide/<slug>.md`. That's it — nothing else to touch.

- To add a **new** guide page, create `docs/guide/<new-slug>.md` and
  add it to `docs/nav.yaml` (this controls sidebar placement, the
  page title, and prev/next pagination order).
- Link to other guide pages the normal GitHub-flavoured-Markdown way,
  e.g. `[Configuration](configuration.md)`. The build rewrites these
  to `.html` automatically, so links still work both when browsing
  the raw Markdown on GitHub *and* on the built site.
- Fenced code blocks should specify a language (` ```php `, ` ```bash `,
  ` ```yaml `, ...) for syntax highlighting.

## Building the site locally

```bash
pip install -r docs/requirements.txt
python3 docs/build.py --out site
```

This renders `docs/guide/*.md` + `docs/nav.yaml` into a full static
site under `site/` (sidebar, theme toggle, pagination, and all —
see `docs/build.py`), copies `docs/index.html` (the marketing landing
page, which isn't duplicated so it needs no build step) and
`docs/assets/` through unchanged. Open `site/index.html` in a browser
to preview.

`site/` is a build artifact — it's git-ignored and never committed.

## Publishing

`.github/workflows/docs.yml` runs `docs/build.py` and deploys the
result to GitHub Pages on every push to `main`/`master` that touches
`docs/**`. One-time setup: in the repo's **Settings → Pages**, set
**Source** to **GitHub Actions**.
