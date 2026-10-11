# RDSCo Fresh Template

Lightweight WordPress theme for the Dorsa website (Sharif Digital Solutions). No page builder, no plugins, no external libraries.

- Mega-menu header and mobile drawer built from **Appearance → Menus**
- Products (`rds_product`) with product families, and Solutions (`rds_solution`) post types
- Content built with core blocks plus theme block styles and shortcodes (`[rds_products]`, `[rds_solutions]`, `[rds_product_families]`, `[rds_documents]`, `[rds_contact_form]`, `[rds_contact_card]`, `[rds_slab_inspection]`)
- Contact form with nonce, honeypot and rate limit; messages are stored under **Messages** and emailed
- Company details, header button and footer call-to-action in **Appearance → Customize → Theme options**
- Fonts are self-hosted: IBM Plex Sans for English, Vazirmatn for RTL languages (switches automatically on `dir="rtl"`)

## Showcase home page (1.1)

The front page is built from core blocks with these classes (all editable in the block editor):

| Class on a Group block | Section |
|---|---|
| `rds-showcase rds-deep` | dark hero; holds `[rds_slab_inspection variant="hero"]` |
| `rds-proof rds-deep` | customer names (`rds-proof__names` list) and `rds-stat` figures |
| `rds-band` (+ `rds-band--surface`, `rds-deep`) | section with large spacing; `rds-head` = chip + title on the left, lead on the right |
| `rds-how__grid` › `rds-how__steps` › `rds-how__step` | how-it-works steps; each step's image is shown in a sticky panel on desktop |
| `rds-about`, `rds-milestones` › `rds-milestone` | about text with milestones |
| `rds-metrics` › `rds-metric` | reference cards |
| `rds-kpis` › `rds-kpi` | outcome figures |
| `rds-duo`, `rds-svc` › `rds-svc__item` | services list next to `[rds_documents]` |
| `rds-bigcta rds-deep` | closing call to action (the footer band is hidden on that page) |

Data-driven parts: `[rds_product_families style="bento"]` (uses each family's **Tagline**, set under Products → Product families) and `[rds_solutions style="preview" featured="steel"]`. When the front page starts with `rds-showcase`, the header sits transparent over the hero.

## Updates from GitHub

`style.css` declares `Update URI: https://github.com/masoodvahid/RDSCO-WP-Dorsa-template`. The theme reads the latest GitHub release and offers it in **Dashboard → Updates** (cached 6 hours; "Check again" refreshes it).

To publish an update, bump `Version:` in `style.css` and push to `main`:

```bash
git commit -am "Release 1.0.1"   # after changing Version: 1.0.1 in style.css
git push origin main
```

The `Release theme` workflow runs on every push to `main`. When no release exists yet for the version in `style.css`, it lints PHP, builds `RDSCo_fresh_template.zip` (from `git archive`, so files marked `export-ignore` are left out) and publishes release `v1.0.1`. Pushes that don't change the version publish nothing.

For a private repository or to avoid GitHub API rate limits, add to `wp-config.php`:

```php
define( 'RDSCO_GITHUB_TOKEN', 'github_pat_...' ); // fine-grained token, read-only "Contents"
```

## Menu classes

Enable **CSS Classes** in the Menus screen options:

| Class | On | Effect |
|---|---|---|
| `mega` | top-level item | opens a full-width panel; children become columns |
| `mega-wide` | column | spans two tracks |
| `mega-steps` | column | numbers its links |
| `mega-feature` | column linked to a post | image card with the post's featured image |

## Folder layout

```
assets/       css, js, fonts, img
inc/          setup, enqueue, post types, meta, customizer, menus, shortcodes, contact form, updater, seed
templates/    page templates ("Sections (full width)")
template-parts/
```
