=== Elementor Helper Kit ===
Contributors: sajad
Tags: elementor, json, editor, animation, transition, reduced motion, css, admin bar, cache, github updater
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.0
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

6. Native GitHub Updates
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

= 1.0.0 =
- Initial public release of the combined Elementor Helper Kit.
- Elementor JSON editor with validation, backups, formatting/minifying, and cache clearing.
- Default-on Full Motion override.
- Default-on Elementor CSS Priority option.
- Default-on frontend Admin Bar switch.
- Default-on Elementor Cache shortcut in the wp-admin toolbar.
- Added native WordPress update checks and package downloads from the public GitHub main branch.
