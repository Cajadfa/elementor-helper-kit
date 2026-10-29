=== Elementor Helper Kit ===
Contributors: sajad
Tags: elementor, json, editor, animation, transition, reduced motion, css, admin bar, cache, github updater
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.1.15
License: GPLv2 or later

A development and administration helper kit for Elementor sites with native public-GitHub updates.

== Features ==

1. Elementor JSON Editor
- Adds "ویرایش JSON المنتور" to Elementor-built posts/pages.
- Opens _elementor_data in WordPress CodeMirror.
- Supports formatting, minifying, Ctrl/Cmd+S saving, and JSON validation.
- Keeps up to 5 backups using the existing _eje_backups meta key.
- Clears relevant Elementor cache data after saving.

2. Animation Effect / Full Motion override
- Enabled by default on first activation.
- Can be disabled from Settings > Elementor Helper Kit.
- Makes JavaScript reduced-motion checks behave like no preference.
- Removes accessible same-origin CSS @media rules targeting prefers-reduced-motion: reduce.
- Watches for CSS injected after page load.
- Does not modify Elementor core files.

3. Elementor CSS Priority
- Enabled by default.
- Moves Elementor's base frontend stylesheet (frontend.min.css / elementor-frontend) before active-theme styles at print time.
- Lets later theme CSS win when selectors have equal specificity.
- Does not edit Elementor or theme files.
- When disabled, WordPress/Elementor keep their normal stylesheet order.

4. Hide Frontend Admin Bar
- Enabled by default.
- Applies the same behavior as show_admin_bar( false ); on the public frontend.
- Does not hide the wp-admin toolbar.
- Can be disabled from Settings > Elementor Helper Kit.

5. Elementor Cache Toolbar Button
- Enabled by default.
- Adds a one-click Elementor Cache action to the top wp-admin toolbar for administrators.
- Runs Elementor's files/data cache clear through its files manager.
- Uses a WordPress nonce and capability check.
- Can be hidden from Settings > Elementor Helper Kit.

6. WooCommerce Minimal Styles
- Disabled by default, including after upgrades.
- Loads a minimal, RTL-friendly stylesheet for classic WooCommerce templates and shortcodes.
- Explicitly checks the WooCommerce-assigned Cart, Checkout, My Account, Shop and Terms page IDs for late stylesheet enqueue recovery.
- Replaces WooCommerce's three classic default styles only while enabled.
- Also adds scoped visual polish to Cart/Checkout Blocks on WooCommerce-assigned pages, while keeping their native layout, behavior, and CSS.
- Does not modify third-party plugin CSS.

7. Native GitHub Updates
- Uses the WordPress Update URI mechanism.
- Reads the remote Version header from elementor-helper-kit.php on the public GitHub main branch.
- Shows the normal WordPress plugin update when the GitHub version is newer.
- Downloads the update ZIP directly from the public repository.
- Normalizes the extracted GitHub archive folder back to elementor-helper-kit.
- Does not require a GitHub token while the repository is public.
- Compatible with WordPress' normal Enable auto-updates option.

== Settings ==

Go to Settings > Elementor Helper Kit.

Animation Effect ON (default):
The plugin attempts to ignore the operating system Reduced Motion preference on the public site.

Animation Effect OFF:
No motion override is injected, so the site/browser respects the visitor's operating-system preference normally.

Elementor CSS Priority ON (default):
Elementor frontend.min.css is printed before the first detected active-theme stylesheet so theme styles have the later cascade position.

Elementor CSS Priority OFF:
No stylesheet queue reordering is performed.

Hide Frontend Admin Bar ON (default):
The frontend toolbar is hidden for logged-in users while the wp-admin toolbar remains available.

Hide Frontend Admin Bar OFF:
WordPress/theme behavior controls frontend toolbar visibility normally.

Elementor Cache Toolbar Button ON (default):
Shows a quick Elementor Cache action in the top wp-admin toolbar.

Elementor Cache Toolbar Button OFF:
Hides only the helper shortcut; Elementor's own Tools page remains unchanged.

WooCommerce Minimal Styles OFF (default):
The helper does not load or remove WooCommerce styles.

WooCommerce Minimal Styles ON:
When WooCommerce is active, loads the minimal stylesheet on frontend pages. Classic WooCommerce CSS is replaced; Cart/Checkout Blocks keep their native CSS and behavior while receiving complementary, scoped styles.

== Publishing updates ==

The updater tracks the public GitHub main branch. To publish an update:
1. Increase Version in the plugin header.
2. Increase EHK_VERSION to exactly the same version.
3. Update Stable tag in this readme and add the changelog entry.
4. Commit/push the ready-to-install version to main.

No GitHub Release, tag, or token is required while the repository remains public.

== CSS cascade note ==

Loading the theme stylesheet later resolves conflicts when CSS specificity is equal. A more specific Elementor selector or an Elementor declaration using !important can still override a less-specific theme rule and should be handled in the theme CSS itself.

== Important accessibility note ==

Ignoring Reduced Motion overrides an accessibility preference selected by the visitor. Use the option only when this behavior is an intentional site decision.

== Developer emergency switch ==

To disable only the motion override from wp-config.php without changing the saved WordPress setting:

define( 'EHK_DISABLE_FULL_MOTION', true );

== Migration ==

The JSON editor intentionally keeps using the previous _eje_backups meta key, so backups created by the standalone Elementor JSON Editor remain available.

After installing Elementor Helper Kit, deactivate the old standalone "Elementor JSON Editor" and "Force Full Motion" plugins to avoid duplicate behavior.

== Changelog ==

= 1.1.15 =
- Replace the default browser arrow on the native classic WooCommerce shop sorting dropdown with a lightweight modern SVG chevron matching the Minimal UI Kit.
- Respect RTL/LTR chevron placement and reserve correct inline padding; preserve WooCommerce's original sorting select, option values, and change behavior.
- Give the shop sort selector a consistent 50px control height, subtle radius, and visible keyboard-focus state.

= 1.1.14 =
- Vertically center classic WooCommerce success-notice text beside its native View Cart button while keeping right-to-left layout and a compact responsive gap.
- Add a lightweight CSS-only shopping-bag/plus icon to the main classic single-product Add to Cart button, inheriting the configured button text color without extra icon fonts or JavaScript.
- Add a default-off settings toggle and sanitized editable text for the native single-product Add to Cart label, using WooCommerce's official product text filter. Disabled state preserves theme/WooCommerce labels and purchase behavior.

= 1.1.13 =
- Remove the actual 2px active-tab border in classic single-product WooCommerce tabs (text-decoration alone was insufficient).
- Prevent theme-provided link borders/underlines and pseudo-element rules from restoring active tab underlines, while preserving active tab typography.
- Remove text underlines and bottom borders from the cart item remove (×) link in normal, hover and focus states without changing removal behavior.

= 1.1.12 =
- Make the primary classic single-product Add to Cart button full-width beneath quantity controls, matching the established 64px checkout CTA and supporting variation purchase forms.
- Remove unwanted text underlines on single-product WooCommerce tab links while keeping the active-tab indicator.
- Render clickable review rating stars with native Unicode glyphs (no icon-font dependency), supporting hover, selected values, and keyboard focus while preserving WooCommerce's existing anchor handlers.
- Give the single-product review submission button an accessible, proportionate minimum 160px by 52px size with stronger typography.
- All changes remain limited to classic single-product pages while WooCommerce Minimal Styles is enabled.

= 1.1.11 =
- Show WooCommerce's existing screen-reader-only labels as material floating labels for optional classic checkout inputs such as address line 2, preserving the native label/input association.
- For checkout text controls truly missing labels, add a native associated label only when a usable placeholder or aria-label exists; safely skip controls with no label text.
- Keep unenhanced text/select controls at the same 58px height when JavaScript is unavailable, and reinitialize labels after WooCommerce checkout AJAX updates.

= 1.1.10 =
- Fix remaining right-side padding on RTL SelectWoo/Select2 selected values in classic WooCommerce checkout.
- Explicitly zero the right/inline-start padding and reserve left-side space for the dropdown chevron without changing LTR layouts or control height.

= 1.1.9 =
- Add comfortable spacing below the expanded classic checkout coupon form without changing its WooCommerce display toggle.
- Normalize the empty/invalid coupon error notice typography to 14px and restore compact, accessible error styling.
- Remove excess top margin from .woocommerce-billing-fields h3.
- Increase the visual gap between floating labels and selected values in native selects and SelectWoo/Select2, preserving the consistent 58px control height.

= 1.1.8 =
- Fix a classic checkout regression when expanding/applying coupons: floated coupon form rows leaked outside their zero-height parent and collapsed the following checkout grid.
- Clear coupon floats without overriding WooCommerce's native slideToggle visibility, submission, or AJAX behavior.
- Explicitly lay out the standard WooCommerce checkout markup's direct children (.col2-set, #order_review_heading, #order_review) in a two-column grid.
- Prevent order heading vertical text and excessive heading-to-review gaps; preserve full order summary widths and stack checkout cleanly at tablet/mobile breakpoints.

= 1.1.7 =
- Display the classic checkout coupon input and Apply Coupon button on the same row at desktop widths while preserving WooCommerce's native coupon toggle visibility and submission behavior.
- Align order review heading, review table and payment panel at full sidebar width.
- Use a visually centered RTL/LTR-safe SVG chevron for SelectWoo/Select2 fields, avoiding sideways arrows caused by logical border directions.
- Match the classic cart Proceed to Checkout button to the 64px/19px prominent Place Order control, including hover and responsive sizing.

= 1.1.6 =
- Add a WooCommerce Light/Dark skin selector alongside the primary-color setting; both are off when the optional minimal-style feature is disabled.
- Extend classic checkout material labels to native selects, SelectWoo/Select2 and textareas, preserving entered values and WooCommerce Ajax refreshes.
- Normalize input/native-select/SelectWoo heights to 58px and replace legacy dropdown arrows with compact chevrons.
- Move labels inside controls with more top breathing room; add explicit styles for focus and prefilled values.
- Strengthen the Checkout Place Order button with targeted high-specificity 64px height and 19px bold typography overrides.

= 1.1.5 =
- Add compact, accessible floating labels for standard classic checkout text inputs, including prefilled and Ajax-refreshed values.
- Increase classic checkout order-notes textarea height and strengthen primary action button typography.
- Hide the coupon control on the classic cart page (leave checkout coupons available), hide disabled cart-update actions and collapse their empty row.
- Reveal the native Update Cart button when quantities change, retaining WooCommerce's normal form submission.

= 1.1.4 =
- Add a saved WooCommerce primary-color picker beneath the minimal-styles toggle, with sanitized hex values, hover shading, and readable button text.
- Restore material-inspired outlined inputs and focus states, white cart-table headings, consistent prominent primary buttons, and dark disabled cart actions.
- Add compact accessible plus/minus quantity controls for classic WooCommerce forms, with min/max/step handling and cart change events; skip controls provided by themes/plugins.
- Keep all changes optional and leave WooCommerce Cart/Checkout Blocks untouched.

= 1.1.3 =
- Normalize classic checkout typography for form controls, placeholders, SelectWoo, notices, payment messages, privacy copy, and the order button using scoped selectors.
- Add customizable checkout font-size variables; keep WooCommerce Blocks and unrelated theme elements untouched.

= 1.1.2 =
- Explicitly detect WooCommerce-configured page IDs for Cart, Checkout, My Account, Shop and Terms, restoring the minimal stylesheet just before head styles print if needed.
- Keep shortcode support, the default-off setting, and the existing frontend enqueue behavior.

= 1.1.1 =
- Added lightweight Cart/Checkout Block styling for WooCommerce-configured pages, without replacing native block styles or JavaScript.
- Preserved default-off toggle and classic shortcode support.

= 1.1.0 =
- Added default-off WooCommerce Minimal Styles toggle and bundled classic CSS.
- Preserved Cart/Checkout Block and third-party extension styling.

= 1.0.0 =
- Initial public release of the combined Elementor Helper Kit.
- Elementor JSON editor with validation, backups, formatting/minifying, and cache clearing.
- Default-on Full Motion override.
- Default-on Elementor CSS Priority option.
- Default-on frontend Admin Bar switch.
- Default-on Elementor Cache shortcut in the wp-admin toolbar.
- Added native WordPress update checks and package downloads from the public GitHub main branch.
