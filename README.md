# RDSCo Fresh Template

Lightweight WordPress theme for the Dorsa website (Sharif Digital Solutions). No page builder, no plugins, no external libraries.

- Mega-menu header and mobile drawer built from **Appearance → Menus**
- Products (`rds_product`) with product families, and Solutions (`rds_solution`) post types
- Content built with core blocks plus theme block styles and shortcodes (`[rds_products]`, `[rds_solutions]`, `[rds_product_families]`, `[rds_documents]`, `[rds_contact_form]`, `[rds_contact_card]`)
- Contact form with nonce, honeypot and rate limit; messages are stored under **Messages** and emailed
- Company details, header button and footer call-to-action in **Appearance → Customize → Theme options**
- Fonts are self-hosted: IBM Plex Sans for English, Vazirmatn for RTL languages (switches automatically on `dir="rtl"`)

## Updates from GitHub

`style.css` declares `Update URI: https://github.com/masoodvahid/RDSCO-WP-Dorsa-template`. The theme reads the latest GitHub release and offers it in **Dashboard → Updates** (cached 6 hours; "Check again" refreshes it).

To publish an update:

```bash
# 1. bump "Version:" in style.css, e.g. 1.0.1
git commit -am "Release 1.0.1"
git tag v1.0.1
git push origin main --tags
```

The `Release theme` workflow checks that the tag matches `style.css`, lints PHP, builds `RDSCo_fresh_template.zip` and attaches it to the release.

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
