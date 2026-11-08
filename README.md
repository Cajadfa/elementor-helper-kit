# Elementor Helper Kit

A lightweight WordPress helper plugin for Elementor development and day-to-day administration.

**Current version:** `1.1.18`
**Requires WordPress:** 6.0+  
**Requires PHP:** 7.4+  
**Update source:** public GitHub repository (`main` branch)

- Classic WooCommerce shop sorting now uses a compact SVG chevron instead of the browser-native dropdown arrow, with RTL/LTR-aware positioning and a 50px minimal control.

### WooCommerce Admin Test Gateway (optional)

- **Off by default.** When WooCommerce is active, enable it in **Settings → Elementor Helper Kit → WooCommerce Admin Test Gateway**.
- In **classic shortcode/PHP checkout**, only logged-in WordPress site administrators (`manage_options`) see **درگاه تست (فقط مدیر)**.
- Select that payment method and click **ثبت سفارش**. A dialog offers **پرداخت موفق**, **پرداخت ناموفق**, and **انصراف و بازگشت**. WooCommerce continues to validate and submit the real checkout form.
- Simulated success calls WooCommerce `payment_complete()`, redirects to the normal order confirmation, and can affect stock, notification emails, and sales metrics. Simulated failure marks the order **failed**, and shows an error notice. No external payment is made.
- The backend enforces the admin permission and enabled setting even for direct POST requests; ordinary customers do not see or use the test gateway.
- **Important:** The result is a real WooCommerce test **order**, not a fake preview; prefer staging / test products. Checkout Blocks are not supported by this classic gateway.

## Features

### 1. Elementor JSON Editor

- Adds **ویرایش JSON المنتور** to posts and pages that contain Elementor data.
- Opens `_elementor_data` in WordPress CodeMirror.
- Supports formatting and minifying JSON.
- Validates JSON before saving.
- Supports `Ctrl+S` / `Cmd+S` saving.
- Keeps up to 5 backups using the existing `_eje_backups` meta key.
- Provides shortcuts back to the page and Elementor editor.
- Clears relevant Elementor post/CSS/cache data after a successful save.

### 2. Animation Effect / Full Motion Override

Enabled by default.

- Makes JavaScript `prefers-reduced-motion` checks behave as if reduced motion is not requested.
- Removes accessible same-origin CSS media rules that target `prefers-reduced-motion: reduce`.
- Watches stylesheets and CSS injected after page load.
- Does not edit Elementor core, theme files, or generated files directly.
- Can be disabled from **Settings > Elementor Helper Kit**.

Developer emergency switch:

```php
define( 'EHK_DISABLE_FULL_MOTION', true );
```

> Accessibility note: this option intentionally overrides a visitor's Reduced Motion preference. Enable it only when that behavior is an intentional site decision.

### 3. Elementor CSS Priority

Enabled by default.

- Moves Elementor's base frontend stylesheet (`frontend.min.css` / `elementor-frontend`) before active-theme styles at print time.
- Lets later theme CSS win when selectors have equal specificity.
- Does not edit Elementor or theme files.
- More-specific Elementor selectors or declarations using `!important` can still win normally according to the CSS cascade.
- Can be disabled from **Settings > Elementor Helper Kit**.

### 4. Hide Frontend Admin Bar

Enabled by default.

- Applies the same behavior as `show_admin_bar( false );` on the public frontend.
- Keeps the toolbar inside `wp-admin` available.
- Can be disabled from **Settings > Elementor Helper Kit**.

### 5. Elementor Cache Toolbar Button

Enabled by default.

- Adds an **Elementor Cache** shortcut to the top `wp-admin` toolbar.
- Runs Elementor's files/data cache clear through Elementor's files manager.
- Available only to administrators with `manage_options`.
- Protected by a WordPress nonce and capability check.
- Can be hidden from **Settings > Elementor Helper Kit**.

### 6. WooCommerce Minimal Styles

Disabled by default, including when upgrading an existing installation.

- Loads a small RTL-friendly stylesheet for **classic WooCommerce** PHP templates and shortcodes.
- When enabled and WooCommerce is active, replaces the three classic WooCommerce core stylesheets with `assets/css/woocommerce-minimal.css`.
- Loads CSS globally on the frontend to support product shortcodes and widgets on non-shop pages.
- Also reads WooCommerce's configured Shop, Cart, Checkout, My Account and Terms page IDs through `wc_get_page_id()`, and re-enqueues the stylesheet immediately before head styles print if a theme or optimization removed it. This applies to custom pages assigned in WooCommerce settings and their endpoints.
- Toggling OFF restores default WooCommerce styling; no template overrides or optional quantity script remain active.
- Provides accessible + / − controls for native classic quantity fields (without altering WooCommerce templates), and Material-style outlined form inputs.
- Lets administrators pick the WooCommerce primary button color and a separate **Light / Dark WooCommerce UI** mode alongside it. Both are scoped to the optional Woo UI and never change the theme header/footer. Disabled cart actions remain dark and table headings white in light mode.
- Classic checkout labels float inside native inputs, selects, SelectWoo/Select2 and textareas; fields share a consistent 58px height with a modern chevron for dropdowns. The Place Order button has higher-specificity sizing to withstand typical classic WooCommerce theme overrides.
- Classic Checkout: material-style floating labels for plain text fields, taller order notes, and bolder primary buttons. Prefilled values and updated-checkout fragments retain the floating state.
- Classic Cart: the coupon field is hidden here, leaving coupon entry at Checkout only; the Update Cart control is hidden while disabled and reappears after changing quantities.
- Classic Checkout coupon: coupon field and submit button share one row on desktop, with the original WooCommerce expand/collapse behavior preserved. The float-containing clear fix in 1.1.8 prevents the coupon panel from collapsing the checkout columns when opened or submitted.
- Classic Checkout order review: review table and payment box stretch to the same sidebar width, and SelectWoo uses centered, RTL-safe dropdown chevrons.
- Classic Checkout finishing details: clearer floating-label/value separation in Select2 and native dropdowns, zero top margin on billing headings, compact error messages for invalid coupons, and extra space beneath the expanded coupon form.
- RTL SelectWoo selected values: remove unwanted right padding while keeping left-side space for the dropdown arrow, without changing LTR styling.
- Optional address-line and custom Checkout inputs with visually hidden WooCommerce labels now receive matching material floating labels, and non-enhanced text/select fields retain the 58px height.
- Classic single-product pages: the buy button fills the summary width under the quantity control, WooCommerce tab links have neither underlines nor active-tab bottom borders, review stars render as accessible clickable glyphs without extra icon fonts, and the review-submit button is comfortably sized.
- Classic cart remove links (×) have no text decoration or theme-provided bottom border.
- Success notices vertically center their message alongside the View Cart action, single-product Add to Cart shows a lightweight shopping bag icon, and the optional **Custom Add to Cart Button Text** setting (default off) replaces only the native single-product label via a WooCommerce filter.
- Classic Cart Proceed to Checkout CTA uses the same prominent sizing as the Checkout Place Order button.
- Also styles the WooCommerce Cart/Checkout **Blocks** commonly used on pages assigned in **WooCommerce > Settings > Advanced**. Their native styles, floating labels, validation, layouts, and JavaScript remain intact; only visual details are harmonized.
- The same toggle controls both classic output and the optional Block polish. Third-party extension styles are not replaced.
- Has no effect if WooCommerce is disabled or the bundled CSS is unreadable.

### 7. Native GitHub Updates

- Uses WordPress' native **Update URI** system.
- Checks the `Version:` header of `elementor-helper-kit.php` on the public GitHub `main` branch.
- Shows the normal WordPress update notice when the GitHub version is newer than the installed version.
- Downloads the update ZIP directly from this public repository.
- Normalizes GitHub's extracted archive directory so the plugin remains installed as `elementor-helper-kit`.
- Requires **no GitHub token** while the repository is public.
- Works with WordPress' normal **Enable auto-updates** option if automatic installation is desired.

## Settings

Open:

**WordPress Admin > Settings > Elementor Helper Kit**

The following switches are enabled by default:

| Setting | Default | Purpose |
| --- | --- | --- |
| Animation Effect | ON | Overrides Reduced Motion behavior on the frontend |
| Elementor CSS Priority | ON | Prints Elementor base CSS before active-theme CSS |
| Hide Frontend Admin Bar | ON | Hides the frontend WordPress toolbar |
| Elementor Cache Toolbar Button | ON | Adds the quick cache action to the wp-admin toolbar |
| WooCommerce Minimal Styles | **OFF** | Optional minimal classic WooCommerce CSS |

The Elementor JSON Editor is always available for posts/pages that contain Elementor data.

## Installation

1. Download or clone this repository.
2. Make sure the plugin directory is named `elementor-helper-kit`.
3. Place it in `wp-content/plugins/` or upload the ZIP through WordPress.
4. Activate **Elementor Helper Kit**.
5. Review the options under **Settings > Elementor Helper Kit**.

If older standalone versions of **Elementor JSON Editor** or **Force Full Motion** are installed, deactivate them to avoid duplicate behavior.

## Publishing a New Version

The updater follows the `main` branch, so no GitHub Release or tag is required.

For every update:

1. Change the plugin header in `elementor-helper-kit.php`:

   ```text
   Version: 1.0.1
   ```

2. Change the matching constant:

   ```php
   define( 'EHK_VERSION', '1.0.1' );
   ```

3. Update `Stable tag` in `readme.txt` and add the changelog entry.
4. Commit and push the changes to `main`.
5. WordPress will detect the newer version during its normal plugin update check. **Dashboard > Updates > Check Again** can be used to request a fresh check.

Do not increase the remote version until the `main` branch is ready to be installed, because the updater downloads the current `main` branch ZIP.

## Update Architecture

- Repository: `https://github.com/Cajadfa/elementor-helper-kit`
- Version file: `https://raw.githubusercontent.com/Cajadfa/elementor-helper-kit/main/elementor-helper-kit.php`
- Package: `https://github.com/Cajadfa/elementor-helper-kit/archive/refs/heads/main.zip`

If the repository is made private in the future, unauthenticated WordPress sites will no longer be able to read/download these files and the updater will need authenticated access or a separate update endpoint.

## Migration / Compatibility

The JSON editor intentionally keeps the legacy `_eje_backups` meta key, so backups created by the earlier standalone Elementor JSON Editor remain available.

## License

GPL-2.0-or-later



## WooCommerce currency and QQ Account orders (1.1.17)

Two independent, default-ON controls are available under **Settings → Elementor Helper Kit**. Neither requires Elementor or WooCommerce Minimal Styles.

| Setting | Visible and effective when | Behavior |
| --- | --- | --- |
| اصلاح مکان تومان یا ریال در ووکامرس | WooCommerce is active and store currency is `IRT` or `IRR` | `woocommerce_price_format` outputs amount, nonbreaking space, currency. Scoped RTL price CSS keeps the currency visually to the left. No conversion or saved WooCommerce setting changes. |
| نمایش سفارش در QQ Account | WooCommerce and QQ Account are active | Adds the owner-only `orders` section through `qqa_account_sections`, 15 orders per page, inline details, purchased-item metadata, Jalali dates and Persian visible digits. |

Existing installations also default to ON when these keys have never been saved. An explicitly saved OFF remains OFF. Saving while a dependency is unavailable preserves the hidden option's previous choice. Other currencies retain the configured WooCommerce format; disabling orders removes the tab, scripts and paid-order redirect.

Orders use QQ Account's configured `qqa_account_page_id` permalink (including subdirectory installations), falling back to `/account/`. Successful `processing`/`completed` orders redirect only for their logged-in owner with a matching WooCommerce order key. Guests, unpaid/failed orders and other customers' orders remain on the native receipt. The existing one-time confirmation markers are preserved during migration. Access uses WooCommerce order APIs, compatible with HPOS.

Implementation: `includes/class-ehk-commerce.php`, `includes/commerce/`, `assets/js/account-orders.js`, `assets/css/account-orders.css`, and `assets/css/iranian-currency.css`. PHP helpers use the `ehk_` prefix. No Prince theme functions or Perfume Suite plugin are required. Saved perfume metadata remains readable when present; ordinary orders show a quantity summary.

### Migrating from Prince Elementor

Update **Elementor Helper Kit to 1.1.17 first**, then **Prince Elementor to 1.10.10**. The theme no longer owns the two integrations. The helper also retires the old Prince hook callbacks while an older theme version is still installed, preventing duplicate registration. The Perfume Suite bottle-price markup and CSS are unchanged. Native WooCommerce receipt styling/date presentation remain in the theme.

Focused checks (no WordPress installation required):

```sh
php tests/commerce-settings-contract.php
php tests/account-orders-contract.php
php tests/order-received-redirect-contract.php
node tests/account-orders-ui.test.js
```


## Recreate classic WooCommerce pages (1.1.18)

With WooCommerce active, **Settings → Elementor Helper Kit** includes a separate **ایجاد مجدد برگه‌های فروشگاه** button. This is an explicit administrator POST action protected by a nonce. It does not run during installation, plugin updates or ordinary settings saves.

| New page slug | Page content | WooCommerce option |
| --- | --- | --- |
| `cart` | `[woocommerce_cart]` | `woocommerce_cart_page_id` |
| `checkout` | `[woocommerce_checkout]` | `woocommerce_checkout_page_id` |
| `my-account` | `[woocommerce_my_account]` | `woocommerce_myaccount_page_id` |

Each new page is published at the site root using the default WordPress page template and the classic shortcode, with no Cart/Checkout Blocks or Elementor template assignment. These shortcodes let WooCommerce render the appropriate classic content; its own internal shortcode templates and hooks remain intact.

The old **currently assigned** three pages are moved to Trash with their existing content retained, even if normal WordPress Trash is disabled. Their canonical slugs are released before the replacements are published and selected in WooCommerce. The main Shop page, products, orders and QQ Account page are not replaced. A shortcode page is a new page ID: custom menus or third-party links stored by old page ID may need to be reselected, while links using the three canonical URLs remain valid.

The tool first checks permissions, protected/shared pages and unrelated pages occupying the requested slugs. It prepares all new drafts before retiring any old page, prevents concurrent rebuilds, verifies exact final slugs and WooCommerce assignments, and rolls back fresh pages/old statuses/slugs/settings if an operation fails. It reports whether rollback completed. If a requested slug belongs to an unrelated page, or a WooCommerce assignment shares a page with QQ Account, the home/blog/privacy/terms or the Shop page, resolve the overlap before retrying. After success it clears WooCommerce's excluded-URI cache and softly refreshes rewrite rules.

This action has its own form and does not save unsaved switches in the settings form above it.

## Optional My Account → QQ Account redirect

**انتقال مای اکانت ووکامرس به QQ Account** is **OFF by default** and shown only while both WooCommerce and QQ Account are active. Its saved key is `woocommerce_qq_myaccount_redirect` in `ehk_settings`. A hidden option retains its previous choice.

When enabled, a frontend GET to WooCommerce's assigned My Account page redirects with HTTP 302 to the published `qqa_account_page_id` permalink. If that assignment is invalid, a published `/account/` page is used when present. No valid target means no redirect. Site subdirectories are retained. QQ Account handles its own login requirements for guests. The feature is independent of the orders integration switch and never overwrites the WooCommerce My Account assignment.

The QQ page itself is never redirected, preventing loops. Account form POSTs and WooCommerce `lost-password` / `customer-logout` endpoints keep their normal behavior. If the helper orders integration is enabled, WooCommerce `orders` and `view-order` links map to the QQ orders section; order details still enforce customer ownership on the server.

Implementation: `includes/class-ehk-woocommerce-pages.php`. Focused check: `php tests/woocommerce-pages-contract.php`; append `no-trash` to verify the Trash-disabled fallback.
