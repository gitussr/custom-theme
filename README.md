# Custom Theme

A lightweight, dependency-free WordPress custom theme boilerplate. It is a
standalone theme (not a child theme, not built on Astra/GeneratePress/Kadence/
Twenty Twenty-Four), intended as a clean starting point for client projects.

Philosophy: **WordPress-native + lightweight + secure + accessible +
performant + easy to extend.** Every file and class in this theme exists
because it's genuinely needed - there is no framework here, and nothing to
delete before you start building.

---

## Requirements

- WordPress 6.4+ (latest stable recommended)
- PHP 8.2+
- A modern browser (last two versions of Chrome, Firefox, Safari, Edge)
- WooCommerce is optional - the theme works fully without it

---

## File Structure

```text
custom-theme/
├── style.css          Theme header (metadata only, no design rules)
├── functions.php      Bootstrap - requires and initializes inc/ classes
├── index.php          Final fallback template
├── front-page.php     Static front page template
├── page.php           Static Page template
├── single.php          Single Post template
├── archive.php         Category/tag/date/author/taxonomy archives
├── search.php          Search results
├── searchform.php       Search form markup (used by get_search_form())
├── comments.php         Comment list + comment form
├── 404.php              Not Found template
├── header.php            <head>, skip link, site header, primary nav
├── footer.php            Site footer, wp_footer(), closing tags
├── screenshot.png        Theme screenshot (1200x900)
├── README.md             This file
│
├── inc/
│   ├── class-theme.php     Theme supports, nav menu registration, WooCommerce flag
│   ├── class-assets.php    CSS/JS enqueueing + filemtime() cache busting
│   └── class-cleanup.php   Removes unnecessary <head> output
│
└── assets/
    ├── css/main.css    The theme's only stylesheet
    ├── js/main.js       The theme's only script (mobile nav toggle)
    └── fonts/           Drop local .woff2 files here (empty by default)
```

No `author.php`, `category.php`, `tag.php`, `taxonomy.php`, `date.php`, or
`page-{slug}.php` are included. `archive.php` already covers every archive
type; add a more specific template only when a project genuinely needs
different markup for it.

### Template hierarchy notes

- **`front-page.php`** is used only when Settings > Reading has a static
  page set as the homepage. If the homepage is set to show latest posts,
  WordPress falls through to `home.php` (not present here) and then
  `index.php`.
- **`page.php`** handles every static Page that doesn't have a
  `page-{slug}.php` or `page-{id}.php` override.
- **`single.php`** handles every single blog Post.
- **`archive.php`** is the catch-all for category, tag, author, date, and
  custom taxonomy archives.
- **`search.php`** is used for `?s=` search result pages.
- **`404.php`** is used when no other template matches the request.
- **`index.php`** is the template of last resort - WordPress falls back to
  it if a more specific template doesn't exist. It's what makes the theme
  work even with zero other templates present.

---

## Theme Architecture (Light OOP)

```text
functions.php
     |
     v
CustomTheme\Theme     -> theme supports, nav menu, WooCommerce flag
CustomTheme\Assets    -> CSS/JS enqueueing, cache busting
CustomTheme\Cleanup   -> removes unnecessary <head> output
```

Three classes, one namespace (`CustomTheme`), no autoloader, no DI
container, no Composer. `functions.php` requires each class file and calls
`init()` on it. OOP is used to keep responsibilities separated and testable
- not because every line of WordPress code needs to be a class. Template
files stay plain, readable, WordPress-native PHP.

### `functions.php`

Defines three constants (`CUSTOM_THEME_VERSION`, `CUSTOM_THEME_DIR`,
`CUSTOM_THEME_URI`), requires the three class files, and instantiates each
one, calling `init()`. Nothing else lives here - if you need new theme
functionality, add a method to an existing class or, if it's a distinct
concern, add a new `inc/class-*.php` and one more `require_once` +
`init()` call.

### `CustomTheme\Theme` (`inc/class-theme.php`)

- `setup()` - registers `title-tag`, `post-thumbnails`, `custom-logo`,
  `responsive-embeds`, HTML5 markup support, and Block Editor support
  (`align-wide`, `editor-styles`, frontend styles loaded into the editor).
- `register_menus()` - registers a single `primary` nav menu location.
- `woocommerce_support()` - see [WooCommerce](#woocommerce) below.

### `CustomTheme\Assets` (`inc/class-assets.php`)

Enqueues `assets/css/main.css` and `assets/js/main.js` on the frontend
only (never on `wp-admin`), with `filemtime()`-based cache-busted
versions. See [Assets](#assets) below.

### `CustomTheme\Cleanup` (`inc/class-cleanup.php`)

Removes specific `<head>` output. See
[WordPress Cleanup](#wordpress-cleanup) below.

---

## Assets

### CSS: why `assets/css/main.css`, not `style.css`

WordPress requires `style.css` to exist so it can read the theme header
comment (Theme Name, Version, etc.) - it does **not** automatically
enqueue `style.css` as a stylesheet. Keeping `style.css` as metadata-only
and treating `assets/css/main.css` as the real, enqueued stylesheet means:

- the theme header stays clean and untouched by day-to-day CSS edits,
- the actual stylesheet gets a normal `wp_enqueue_style()` call with a
  real dependency array and a cache-busted version,
- there's exactly one CSS file to look in, matching "minimum files,
  maximum clarity."

### JS: `assets/js/main.js`

Vanilla JavaScript, no jQuery. Enqueued with the `defer` loading strategy
via the 5th-argument array of `wp_enqueue_script()` (WordPress 6.3+), and
only on the frontend. Currently it powers the accessible mobile
navigation toggle; add more code to this file rather than adding new
`<script>` handles unless a project genuinely needs a second file.

### Cache busting

`CustomTheme\Assets::get_asset_version()` returns `filemtime()` of the
asset file as the version string passed to `wp_enqueue_style()` /
`wp_enqueue_script()`. Editing `main.css` or `main.js` changes its
modification time, which changes the `?ver=` query string WordPress
appends to the enqueued URL, which busts the browser cache automatically.
No manual version bumps, ever. If the file is somehow missing, it falls
back to the theme's `Version` header as a last resort.

---

## Fonts

No fonts are bundled by default - the theme uses the system font stack
(`-apple-system, "Segoe UI", Roboto, ...`) via the `--font-body` CSS
custom property in `assets/css/main.css`. Google Fonts (or any external
font host) is intentionally not used, for performance and privacy.

To add a local font:

1. Convert your font to **WOFF2** (smallest, universally supported by
   modern browsers) and place the file(s) in `assets/fonts/`.
2. Add an `@font-face` rule in `assets/css/main.css` (a commented example
   is at the top of that file) - one rule per weight/style combination.
3. Point `--font-body` and/or `--font-heading` at your new font family.

`font-display: swap` is used in the example so text stays visible using a
fallback font while the custom font file downloads, instead of being
invisible during the load (avoids "flash of invisible text").

---

## WordPress Cleanup

`CustomTheme\Cleanup` removes frontend `<head>` output that leaks
information or costs an unnecessary request, **without disabling the
underlying WordPress feature**:

| Removed | What it does NOT do |
|---|---|
| `wp_generator` (generator meta tag) | Doesn't affect anything else |
| RSD link | REST API and XML-RPC keep working |
| Windows Live Writer manifest | XML-RPC itself is left enabled |
| Shortlink | `wp_get_shortlink()` still works if called directly |
| REST API discovery `<link>` | The REST API itself is untouched |
| oEmbed discovery links | Embeds still work; only the head hint is gone |
| Extra feed discovery links (`feed_links_extra`) | The main site feed link is left in place, so feeds keep working |
| Emoji detection script/style, TinyMCE emoji plugin, emoji DNS-prefetch hint | Native emoji rendering in modern browsers makes this payload unnecessary |
| "WordPress X.Y.Z" from the RSS generator tag | RSS feeds still work |

Nothing here disables Gutenberg, the REST API, WooCommerce, login, feeds,
or XML-RPC. If a project has a specific reason to disable one of those
(e.g. XML-RPC hardening), do it explicitly and document why - it does not
belong in this general-purpose cleanup class.

---

## Gutenberg / Block Editor

Fully supported. `add_theme_support('align-wide')` enables wide/full block
alignment, `add_theme_support('editor-styles')` +
`add_editor_style('assets/css/main.css')` load the theme's real stylesheet
into the editor so what you see there matches the frontend, and
`add_theme_support('responsive-embeds')` makes embedded video/oEmbed
content fluid. No custom blocks are included - the theme supports the
blocks WordPress ships with.

---

## Classic Editor

Content created with the Classic Editor renders through the exact same
templates (`the_content()` in `page.php` / `single.php` / `front-page.php`)
as Gutenberg content - there is no Gutenberg-only markup or dependency
anywhere in the templates, so a Page/Post looks correct regardless of
which editor created it.

---

## WooCommerce

Basic compatibility only, and it costs nothing when WooCommerce isn't
active:

- `CustomTheme\Theme::woocommerce_support()` (in `inc/class-theme.php`)
  checks `class_exists('\WooCommerce')` and, only if true, declares
  `woocommerce`, `wc-product-gallery-zoom`, `wc-product-gallery-lightbox`,
  and `wc-product-gallery-slider` theme support so WooCommerce's own
  default templates render without layout conflicts.
- No `woocommerce/` template override directory is included. The theme
  relies on WooCommerce's own templates and hooks rather than
  reimplementing them.

**If a project will never use WooCommerce**, delete the
`woocommerce_support()` method (and its `add_action` line in `init()`) in
`inc/class-theme.php`. Nothing else in the theme references WooCommerce,
so removal is a two-line change.

---

## Accessibility

- Skip link (`.skip-link`) jumps to `#primary`, visually hidden until
  focused.
- Semantic landmarks: `<header>`, `<nav>`, `<main id="primary">`,
  `<footer>`.
- Correct heading hierarchy: one `<h1>` per page (post/page title or
  archive/search title), `<h2>` for individual entries in loops.
- Visible focus states via `:focus-visible` in `main.css`.
- The primary menu uses `wp_nav_menu()`, which outputs `aria-current="page"`
  on the current item automatically - no manual ARIA wiring needed.
- The mobile nav toggle button uses `aria-controls` and `aria-expanded`,
  updated by `assets/js/main.js`, and closes on <kbd>Escape</kbd> or a
  click outside the nav.
- The search form has a `<label>` (screen-reader-only) properly associated
  with its `<input>` via `for`/`id`.
- Real `<button>` elements are used for actions (search submit, menu
  toggle) and real `<a>` elements for navigation - never one styled to
  look like the other.

---

## Security

- **Output escaping**: `esc_html()`, `esc_attr()`, `esc_url()`, and
  `wp_kses_post()` (for the small amount of HTML returned by
  `get_the_category_list()` / `get_the_tag_list()`) are used throughout
  the templates.
- **URLs**: `home_url()`, `get_permalink()`, `get_template_directory_uri()`
  are used everywhere instead of hardcoded paths.
- **Forms**: `comment_form()` and `wp_nav_menu()`/`get_search_form()` rely
  on WordPress core's own nonce and sanitization handling; no custom
  form-processing code is included in this boilerplate.
- **Input**: the theme does not read `$_GET`/`$_POST`/`$_REQUEST`
  directly anywhere - all user input handling is delegated to WordPress
  core APIs (search query via `get_search_query()`, comments via
  `comment_form()`).
- **No hardcoded credentials, keys, or tokens** anywhere in the theme.
- Every PHP file starts with `if ( ! defined( 'ABSPATH' ) ) { exit; }` to
  prevent direct file access.

---

## Development

- Set these in your local `wp-config.php` (never in the theme itself -
  theme code must not modify `wp-config.php`):

  ```php
  define( 'WP_DEBUG', true );
  define( 'WP_DEBUG_LOG', true );
  define( 'WP_DEBUG_DISPLAY', false );
  ```

  `WP_DEBUG_DISPLAY => false` with `WP_DEBUG_LOG => true` logs PHP
  warnings/notices to `wp-content/debug.log` without exposing them on the
  live page - use this even in local development to catch issues without
  breaking the page for anyone previewing it.

- **Browser cache during development**: because CSS/JS versions are tied
  to `filemtime()`, a hard refresh is rarely needed - simply saving
  `main.css` or `main.js` changes its `?ver=` and busts the cache on the
  next page load.

- No build step, no `npm install`, no Composer required. Edit PHP/CSS/JS
  directly and reload.

---

## Extending the Theme

- **New CSS**: add to `assets/css/main.css`. Only split into a second file
  if a genuinely separate concern demands it (e.g. print styles), and
  enqueue it via a new call to the existing `enqueue_style()` helper in
  `CustomTheme\Assets`.
- **New JavaScript**: add to `assets/js/main.js`, or enqueue an additional
  file the same way through `CustomTheme\Assets::enqueue_frontend_assets()`.
- **New theme functionality**: add a method to the relevant existing class
  (`Theme`, `Assets`, `Cleanup`), or create a new `inc/class-*.php` in the
  `CustomTheme` namespace for a genuinely distinct concern, then
  `require_once` and instantiate it in `functions.php`.
- **New template files**: add them at the theme root following WordPress's
  template hierarchy (e.g. `page-contact.php`, `category-news.php`) only
  when a real project requirement needs different markup than
  `page.php`/`archive.php` already provide.
- **New navigation locations**: add another key to the array passed to
  `register_nav_menus()` in `CustomTheme\Theme::register_menus()`, then
  call `wp_nav_menu( [ 'theme_location' => 'your-location' ] )` wherever
  it should render.

---

## Testing performed

- **Code review**: every template file and class was written and
  reviewed against the WordPress template hierarchy and current core
  APIs; no deprecated functions are used.
- **Static checks**: `functions.php` and `inc/*.php` were reviewed for
  correct namespacing, escaping, and hook usage.

This boilerplate has **not** been activated against a running WordPress
installation as part of producing these files - do the following before
shipping a project built on it:

1. Activate the theme and confirm no PHP fatal errors/warnings appear
   with `WP_DEBUG` on.
2. Check the homepage, a Page, a Post, an archive, a search results page,
   and the 404 page.
3. Create content with both the Block Editor and Classic Editor plugin
   and confirm both render correctly.
4. Confirm `assets/css/main.css` and `assets/js/main.js` load with a
   `?ver=` matching their current `filemtime()`.
5. Test the mobile nav toggle with mouse, keyboard (Tab/Enter/Escape),
   and a screen reader.
6. If using WooCommerce, install it and check the shop, cart, and a
   single product page for layout conflicts.
