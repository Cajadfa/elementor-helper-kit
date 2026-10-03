# Elementor Helper Kit

A lightweight WordPress helper plugin for Elementor development and day-to-day administration.

**Current version:** `1.0.0`  
**Requires WordPress:** 6.0+  
**Requires PHP:** 7.4+  
**Update source:** public GitHub repository (`main` branch)

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

### 6. Native GitHub Updates

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
