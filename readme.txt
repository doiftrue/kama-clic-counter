=== Kama Click Counter ===
Stable tag: trunk
Tested up to: 6.8.0
Contributors: Tkama
Tags: analytics, statistics, count clicks, counter
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Count clicks on any link across the site. Creates a beautiful file download block in post content. Includes a widget for top downloads.



== Description ==

With this plugin, you can gather statistics on clicks for file downloads or any other link across the site.

To insert a file download block, use the `[download url="any file URL"]` shortcode.

The plugin does not include additional tools for uploading files. All files must be uploaded using the standard WordPress media uploader. The URLs are then used to create the download block.

Additionally, the plugin includes:

* A button in the visual editor for quickly inserting the file download block shortcode.
* A customizable widget that allows you to display a list of "Top Downloads" or "Top Link Clicks."



== Frequently Asked Questions ==

= How can I customize the download block with CSS? =

You can customize CSS styles on the plugin options page. Alternatively, you can add CSS styles to the `style.css` file of your theme.



== Screenshots ==

1. Statistics page.
2. Plugin settings page.
3. Single link edit page.
4. TinyMCE visual editor downloads button.



== Changelog ==

= 4.0.3 =
- FIX: Bugfix the counter not worked after the last updates for the count click of Download block. And more:
- IMP: Minor improvements.

= 4.0.2 =
- CHG: Min PHP version 7.0 >> 7.1.
- FIX: Plugin `init` moved to `after_setup_theme` hook to avoid some conflicts.
- IMP: minor improvements.
- UPD: Tested up to: WP 6.8.0

= 4.0.1 =
* FIX: Bug in `counter.js` script.

= 4.0.0 =
* CHG: Requires PHP >= 7.0.
* DEL: Removed backcompat code.
* IMP: Code refactored.
* ADD: PHP class autoloader.
* ADD: PHP namespaces.
* CHG: Filter `kcc_admin_access` renamed to `kcc_manage_access`.
* CHG: Filter `parce_kcc_url` renamed to `click_counter__parse_kcc_url`.
* CHG: Filter `get_url_icon` renamed to `click_counter__get_icon_url`.

= 3.6.10 =
* IMP: Minor improvements.

= 3.6.9 =
* IMP: Performance improvements; no jQuery dependency for base count JS.

= 3.6.8.2 =
* FIX: Bug in previous version.

= 3.6.8.1 =
* FIX: Protocol for external links issue (leaving `//`).
* FIX: Compatibility with PHP 7.4.

= 3.6.8 =
* FIX: Wrong URL count with query parameters.
* FIX: Bug in widget loop.
* FIX: Other minor fixes.

= 3.6.7.3 =
* FIX: `<title>` parsing issue.

= 3.6.7 =
* FIX: Wrong counting with "hide link under id" option enabled.
* FIX: Minor code fixes.

= 3.6.6 =
* FIX: `access_role` option not saved.
* ADD: `desc` attribute to shortcode.

= 3.6.5 =
* FIX: Filesize parsing issue due to missing HTTP protocol.

= 3.6.4.2 =
* CHG: Minor changes to download block HTML markup and CSS styles.

= 3.6.4 =
* ADD: `urldecode` for incoming URLs when saving to DB. Thanks to Mark Carson.
* NEW: Exclude URL counting filter added (see options page).

= 3.6.3 =
* FIX: `esc_url()` for `wp_redirect()` to avoid spaces deletion. Thanks to Mark Carson.

= 3.6.2 =
* ADD: `in_post` field on edit link admin page to change associated post ID.
* ADD: Sanitize data on edit link POST request.
* NEW: Save URLs without protocol (`//site.ru/foo`).
* FIX: Admin list search starting from pagination page.
* FIX: Detection of URLs without protocol.
* FIX: Title detection for protocol-less URLs using WP HTTP API.
* FIX: Minor bug fixes.

= 3.6.1 =
* ADD: `title` attribute for `[download]` shortcode.
* ADD: Improved TinyMCE button modal window (browse media library).
* FIX: Count clicks from mouse wheel and context menu.

= 3.6.0 =
* CHG: Class name `KCClick` changed to `KCCounter`.
* CHG: Icon in TinyMCE visual editor updated.

= 3.5.1 =
* CHG: Move localization to translate.wordpress.org.
* FIX: Minor code fix.

= 3.5.0 =
* FIX: XSS vulnerability fixed.
* CHG: Class name `KCC` changed to `KCClick`.
* CHG: Translate PHP code to English (Russian moved to localization file).

= 3.4.9 =
* FIX: Remove admin-bar link for roles without plugin access.

= 3.4.8 =
* ADD: "Clicks per day" data on edit link screen.

= 3.4.7 - 3.4.7.3 =
* FIX: Table structure to support `utf8mb4_unicode_ci` charset.

= 3.4.6 =
* ADD: `get_url_icon` filter to manage icons.

= 3.4.5 =
* ADD: Administrator option to assign plugin access to other WP roles.
* ADD: Option to add KCC Stats link to admin bar.
* DEL: Removed `HTTP_REFERER` block on direct KCC URL use.

= 3.4.4 =
* CHG: `is_file` extension check method for URL.
* ADD: `kcc_is_file` filter.
* ADD: Widget option to set link to post instead of file.
* DEL: Removed `kcc_file_ext` filter.

= 3.4.3 =
* ADD: Hooks `parce_kcc_url`, `kcc_count_before`, `kcc_count_after`.
* ADD: Second parameter `$args` to `kcc_insert_link_data` filter.
* ADD: Punycode support for link filtering.
* FIX: Count clicks from mouse wheel, touch, and ctrl+click.

= 3.4.2 =
* ADD: `kcc_admin_access` filter to change access capability.
* FIX: Redirect protection fix.

= 3.4.1 =
* FIX: KCC URL parsing issue.

= 3.4.0 =
* ADD: Option to hide URL in download block.
* ADD: `link_url` column index in DB for performance.
* ADD: Hooks `get_kcc_url`, `kcc_redefine_redirect`, `kcc_file_ext`, `kcc_insert_link_data`.
* ADD: Replace ugly URL with original URL on hover.
* ADD: Replace "edit link" text in download block with icon.
* FIX: Duplicate URL updates (e.g., containing `%` symbol).
* FIX: XSS protection added.
* FIX: Code structure fixes.

= 3.3.2 =
* FIX: PHP notice.

= 3.3.1 =
* ADD: `de_DE` localization. Thanks to Volker Typke.

= 3.3.0 =
* ADD: Localization on plugin page.
* ADD: Menu to admin page.
* FIX: Antivirus false positive detection.

= 3.2.34 =
* FIX: Admin CSS changes.

= 3.2.3.3 =
* ADD: jQuery links now hidden with `#kcc` anchor and `onclick` attribute.
* FIX: `parse_url` bug when URL contained "=" character.

= 3.2.3.2 =
* FIX: Redirect to URL with space (`" "`) character.
* ADD: Round "clicks per day" value to one decimal on admin stats page.

= 3.2.3.1 =
* FIX: "Back to stat" link on "edit link" admin page.

= 3.2.3 =
* FIX: Redirects to HTTPS were not working correctly.
* FIX: PHP < 5.3 support.
* FIX: "Go back" button on "edit link" admin page.
* FIX: Localization issues.

= 3.2.2 =
* ADD: "Go back" button on "edit link" admin page.

= 3.2.1 =
* CHG: Auto-replace old shortcodes `[download=""]` with `[download url=""]` in DB during update.

= 3.2 =
* ADD: Widget feature.

