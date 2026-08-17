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
│   ├── class-config.php     Feature-flag switchboard (e.g. the WooCommerce module switch)
│   ├── class-theme.php      Theme supports, nav menu registration, baseline WooCommerce compat
│   ├── class-assets.php     CSS/JS enqueueing + filemtime() cache busting
│   ├── class-cleanup.php    Removes unnecessary <head> output
│   │
│   └── woocommerce/         Optional WooCommerce customization module - see "WooCommerce Module"
│       ├── class-woocommerce.php  Module bootstrap: asset loading, submodule loading
│       ├── class-product.php      Product card (Quick View button via WC hooks)
│       ├── class-cart.php         AJAX Add to Cart, Mini Cart
│       ├── class-quick-view.php   AJAX Quick View
│       └── class-filters.php      Shop filters (category/attribute/stock) + AJAX filtering
│
└── assets/
    ├── css/main.css          The core theme's only stylesheet
    ├── css/woocommerce.css   WooCommerce module stylesheet (loaded only on WC pages)
    ├── js/main.js             The core theme's only script (mobile nav toggle)
    ├── js/woocommerce.js       WooCommerce module script (loaded only on WC pages)
    └── fonts/                  Drop local .woff2 files here (empty by default)
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

## WooCommerce Module

WooCommerce support has two layers:

1. **Baseline compatibility** (`CustomTheme\Theme::woocommerce_support()`
   in `inc/class-theme.php`) - always active whenever WooCommerce is
   installed, regardless of the module switch below. It declares
   `woocommerce`, `wc-product-gallery-zoom`, `wc-product-gallery-lightbox`,
   and `wc-product-gallery-slider` theme support, and hooks
   `woocommerce_before_main_content` / `woocommerce_after_main_content` to
   print the theme's `<main id="primary" class="site-main">` wrapper
   (WooCommerce skips its own default wrapper once `woocommerce` theme
   support is declared, so this is required for correct markup, not
   optional). This layer keeps the site rendering correctly with
   WooCommerce active even if the module below is switched off.

2. **The optional customization module** (`inc/woocommerce/`) - product
   cards, AJAX Add to Cart, Quick View, shop filters, and the Mini Cart.
   This is what the switch controls.

### Enabling / disabling

One switch, in `inc/class-config.php`:

```php
private const FEATURES = [
    'woocommerce' => true,
];
```

Set it to `false` to disable:

```php
private const FEATURES = [
    'woocommerce' => false,
];
```

This does **not** install, activate, deactivate, or otherwise touch the
WooCommerce plugin - it only controls whether `inc/woocommerce/*.php` and
`assets/{css,js}/woocommerce.css|js` are loaded. `functions.php` is the
single place this is checked:

```php
if ( \CustomTheme\Config::is_enabled( 'woocommerce' ) && class_exists( '\WooCommerce' ) ) {
    // require + init the module
}
```

There is deliberately no admin UI toggle (`Appearance > Theme Settings`)
- the switch is developer-controlled, adds no database option, and adds
no settings infrastructure.

### WooCommerce detection

The module is only ever `require`d after confirming `class_exists('\WooCommerce')`
in `functions.php` (WordPress loads active plugins before the theme's
`functions.php`, so this check is reliable). No file in `inc/woocommerce/`
is loaded, and no WooCommerce class/function is referenced anywhere, when
WooCommerce is absent or inactive - the theme cannot fatal-error on a
missing WooCommerce.

### Product card

`CustomTheme\WooCommerce\Product` adds a Quick View button via the
`woocommerce_after_shop_loop_item` hook (priority 15, after the default
Add to Cart button at priority 10). **No template override was used** -
`woocommerce/content-product.php` (which already outputs the image, sale
badge, title, rating, price, and Add to Cart via WooCommerce's own
`woocommerce_before_shop_loop_item` / `woocommerce_shop_loop_item_title` /
`woocommerce_after_shop_loop_item` hooks) is left completely untouched.
Because that template is what WooCommerce reuses on the shop, every
product category/tag archive, search results, related products, and
upsells, the Quick View button - and any future card change - is
automatically consistent everywhere without duplicating markup per
context.

### AJAX Add to Cart

Two distinct paths, matching how WooCommerce itself already works:

- **Loop / archive pages** (shop, category, search results): WooCommerce
  core already supports AJAX add-to-cart here natively (WooCommerce
  Settings > Products > "Enable AJAX add to cart buttons on archives").
  The theme does not add any custom code for this path - it works as
  long as the card markup isn't overridden, which it isn't.
- **Single product page**: WooCommerce does not provide AJAX here by
  default; this is what `CustomTheme\WooCommerce\Cart::ajax_add_to_cart()`
  adds. `assets/js/woocommerce.js` intercepts `form.cart` submissions,
  but only when the form contains neither a `.variations` table nor a
  `.woocommerce-grouped-product-list` table - i.e. only for simple
  products. Variable and grouped products are left to submit the form
  normally, which WooCommerce handles correctly on its own. External/
  affiliate products render a plain link outside `form.cart` and are
  never intercepted at all.

The AJAX handler re-validates everything server-side (never trusts the
client): product exists, is a simple product, `is_purchasable()`,
`is_in_stock()`, and a valid positive quantity via `wc_stock_amount()`.
It adds to the cart via `WC()->cart->add_to_cart()` - WooCommerce's own
cart/session, never a custom one - and returns
`apply_filters( 'woocommerce_add_to_cart_fragments', [] )`, the exact
same filter WooCommerce's native loop AJAX add-to-cart uses. The theme's
own `.cart-count` fragment is registered through that same filter, so
both paths update the same element consistently.

### Quick View

`CustomTheme\WooCommerce\Quick_View` returns a small HTML fragment (image,
title, rating, price, short description, and either an Add to Cart button
or a "View full details" link) - never a whole page. Simple, purchasable,
in-stock products get the inline Add to Cart button, which reuses the
exact same `.ajax-add-to-cart` flow as the single product page. Every
other product type gets the permalink instead of a partial or incorrect
variation interface, per the project's explicit "gracefully fall back"
requirement for product types the AJAX flow can't safely support.

The modal (built client-side in `assets/js/woocommerce.js`) uses
`role="dialog"` / `aria-modal="true"` / `aria-labelledby`, moves focus to
the close button on open, restores focus to the triggering Quick View
button on close, closes on <kbd>Escape</kbd> or a backdrop click, and
traps <kbd>Tab</kbd>/<kbd>Shift+Tab</kbd> within the modal's focusable
elements.

### Shop filters

`CustomTheme\WooCommerce\Filters` renders category, per-attribute, price,
and stock-status controls - built only from taxonomies/terms that
actually exist and actually have products (`get_terms()` /
`wc_get_attribute_taxonomies()`, `hide_empty` true). Two things are
deliberately **not** reimplemented:

- **Price filtering** - WooCommerce's own `WC_Query` already applies
  `min_price`/`max_price` query args to the main product query natively.
- **Sorting** - WooCommerce's own `woocommerce_catalog_ordering()`
  dropdown is already hooked on `woocommerce_before_shop_loop` by
  default.

Category/attribute/stock filtering is applied via `pre_get_posts` on the
main query (`Filters::apply_query_filters()`), scoped to
`is_shop() || is_product_taxonomy()` main queries only. The filter panel
is a plain `<form method="get">`, so it works with JavaScript disabled as
a normal page reload with a filtered query string, and is progressively
enhanced by `assets/js/woocommerce.js` into an AJAX request (checkbox
`change` events and number-input `change`/blur, not per-keystroke, to
avoid unnecessary requests) that swaps in a freshly rendered product grid
and pagination without a full reload, and updates the URL via
`history.pushState()` so the state stays shareable and works with
back/forward.

**One source of truth**: both the `pre_get_posts` path (normal/reloaded
page) and the AJAX path build their filters through the same private
`apply_request_filters()` method, so a filtered URL loaded directly and
the same filters applied via AJAX always produce identical results.

**Pagination**: the AJAX handler builds pagination with `paginate_links()`
using the secondary `WP_Query`'s `max_num_pages`, and pagination clicks
are intercepted by the same filter form's AJAX handler (carrying the
active filters along with the requested page) - so filtering and
pagination always compose correctly.

**SEO**: pages with two or more active filters get a `noindex,follow`
robots directive via the core `wp_robots` filter, so search engines don't
attempt to index every possible filter combination while still being
able to crawl through to the canonical, unfiltered category/shop page.
Filtered URLs remain plain, shareable query strings rather than hash
fragments or POST-only state.

### Mini Cart

`CustomTheme\WooCommerce\Cart::mini_cart_trigger()` hooks into a generic
`custom_theme_header_actions` action that `header.php` fires (see
[Extending the Theme](#extending-the-theme)) - the core theme file itself
has no WooCommerce-specific code. The panel wraps WooCommerce's own
`woocommerce_mini_cart()` (i.e. `cart/mini-cart.php` - no template
override) in a `widget_shopping_cart_content` wrapper, the same class
WooCommerce's built-in Cart widget uses. WooCommerce's own
`wc-cart-fragments` script already recognizes that class and AJAX-handles
"remove item" clicks inside it automatically, so no custom remove-item
code was needed.

### Cart & Checkout pages

Left entirely default. No cart or checkout templates are overridden, and
WooCommerce remains fully responsible for cart contents, coupons,
shipping, totals, tax, payment, and order creation. The module's CSS only
adjusts spacing/typography to match the rest of the theme - see
`assets/css/woocommerce.css`.

### Cart/Checkout Block compatibility

Because no cart/checkout templates are overridden, this module makes no
assumption about whether a site uses the classic shortcode-based Cart/
Checkout or the block-based Cart/Checkout - both continue to work exactly
as WooCommerce ships them.

### Template overrides

**None.** Every feature above uses WooCommerce hooks/filters or
WooCommerce's own template functions (`wc_get_template_part()`,
`woocommerce_mini_cart()`) rather than a copied/overridden template file.

### Asset loading

`assets/css/woocommerce.css` and `assets/js/woocommerce.js` are enqueued
only when `is_woocommerce() || is_cart() || is_checkout() || is_account_page()`
is true (`Woocommerce::enqueue_assets()`) - never on a normal page, post,
or when WooCommerce is inactive/absent. Versioning reuses
`CustomTheme\Assets::get_version()` (the same `filemtime()`-based
cache-busting the core theme uses), exposed as a public static method
specifically so this module isn't duplicating that logic.

### Security

- Every AJAX handler starts with `check_ajax_referer( 'custom_theme_woocommerce', 'nonce', false )`
  and returns a clean JSON error (not a `wp_die()` screen) on failure.
- Product IDs, variation state, and quantities are never trusted from the
  client: product IDs are validated with `wc_get_product()`, quantities
  sanitized with `wc_stock_amount()` and checked `> 0`, and product
  type/purchasability/stock re-checked server-side even though the JS
  already tries to avoid sending invalid requests.
- Prices, totals, and taxes are never computed or trusted client-side -
  every response reflects `WC_Cart`'s own calculation.
- AJAX responses return only what the frontend needs to update (message,
  count, fragment HTML) - never a full page.

### Accessibility

- Product card: the Quick View button is a real `<button>`; the card's
  existing links/buttons are untouched.
- Filters: every control has a visible `<label>`/`<legend>`, is fully
  keyboard operable, and reflects selection state via the checkbox's own
  native `checked` state.
- Quick View: full focus management (see above), `role="dialog"`,
  `aria-modal`, `aria-labelledby`.
- AJAX: success/error feedback is announced through a
  `role="status" aria-live="polite"` region (`#custom-theme-notice`); no
  unexpected focus jumps occur on add-to-cart or filter updates.
- Mini Cart: the trigger button uses `aria-controls`/`aria-expanded`,
  closes on <kbd>Escape</kbd> and outside click, and its contents inherit
  WooCommerce's own accessible cart markup.

### Supported product types & fallback behavior

| Product type | AJAX Add to Cart (single page) | Quick View |
|---|---|---|
| Simple | Yes | Inline Add to Cart |
| Variable | No - normal form submit (WooCommerce handles variations) | "View full details" link |
| Grouped | No - normal form submit (WooCommerce handles the group table) | "View full details" link |
| External/Affiliate | N/A - not inside `form.cart` | "View full details" link |
| Out of stock | Add to Cart control withheld entirely | No Add to Cart control shown |

### Extending the module

- **New card element**: hook another `woocommerce_before_shop_loop_item` /
  `woocommerce_after_shop_loop_item_title` / `woocommerce_after_shop_loop_item`
  callback in `CustomTheme\WooCommerce\Product` - avoid overriding
  `content-product.php` unless a hook genuinely can't do it.
  Reuse `CustomTheme\Assets::get_version()`.
- **New AJAX endpoint**: add a small, single-purpose method to the
  relevant class (or a new `inc/woocommerce/class-*.php` for a genuinely
  distinct concern), registered in `functions.php`'s existing module
  block, following the same nonce-check → validate → WooCommerce-API →
  minimal-JSON-response pattern used throughout this module.

**If a project will never use WooCommerce**, either set
`'woocommerce' => false` in `inc/class-config.php` (keeps the code in the
repo but never loads it) or delete `inc/woocommerce/`,
`assets/css/woocommerce.css`, `assets/js/woocommerce.js`, and the
`Config`-gated block in `functions.php` entirely. Baseline compatibility
in `inc/class-theme.php` can stay - it's harmless and free when
WooCommerce isn't installed.

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
- **New header content**: `header.php` fires a generic
  `do_action( 'custom_theme_header_actions' )` right before `</header>`,
  used by the optional WooCommerce module's Mini Cart trigger. Hook
  anything else that belongs in the header there instead of editing
  `header.php` directly.

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
6. If using WooCommerce, test all three module states: WooCommerce absent,
   WooCommerce active with the module disabled (`'woocommerce' => false`),
   and WooCommerce active with the module enabled - shop, a product
   category, search, a simple product (AJAX add-to-cart, Quick View), a
   variable product (falls back to normal add-to-cart, Quick View links
   to the product page), an out-of-stock product, shop filters with and
   without JavaScript, the Mini Cart, Cart, and Checkout.
