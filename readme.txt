
=== Plugin Name ===
Stable tag: trunk
Tested up to: 6.4.3
Contributors: Tkama
Tags: analytics, statistics, count clicks, counter
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Count clicks on any link all over the site. Creates beautiful file download block in post content. Has top downloads' widget.


== Description ==

Using this plugin you will have statistics on clicks on file download or any other link all over the site.

To insert file download block use `[download url="any file URL"]` shortcode.

Plugin don't have any additional instruments to uploads files. All files uploaded using standard wordpress media uploader. To create download block URL are used.

In additional, plugin has:

* Button in visual editor to fast insert file download block shortcode.
* Customizable widget, that allows output a list of "Top Downloads" or "Top link Clicks".



== Frequently Asked Questions ==

= How can I customize download block with CSS? =

Just customize CSS styles in plugin options page. Also you can add css styles into 'style.css' file of your theme.



== Screenshots ==

1. Statistics page.
2. Plugin settings page.
3. Single link edit page.
4. TinyMce visual editor downloads button.



== Changelog ==

= 4.0.0 =
* Requires PHP >= 7.0.
* Backcompat removed.
* Code refactored.
* PHP class autoloader added.
* PHP namespaces added.
* Filter `kcc_admin_access` renamed to `kcc_manage_access`.
* Filter `parce_kcc_url` renamed to `click_counter__parse_kcc_url`.
* Filter `get_url_icon` renamed to `click_counter__get_icon_url`.

= 3.6.10 =
* Minor improvemets.

= 3.6.9 =
* A little performance improvements and no jQuery dependence for base count js.

= 3.6.8.2 =
* BUG: some bug in previous version.

= 3.6.8.1 =
* BUG: previously, the Protocol for external links was removed (leaved //).
* FIX: compatibility with PHP 7.4

= 3.6.8 =
* BUG: wrong count of URL with query parameters. Code improved!
* BUG: in widget loop.
* FIX: other minor fixes.

= 3.6.7.3 =
* FIX: wrong `<title>` parsing in some cases.

= 3.6.7 =
* FIX: bug with wrong counting when 'hide link under id' option is enadled.
* FIX: minor code fixes


= 3.6.6 =
* FIX: access_role option not saved.
* ADD: desc attr to shortcode.

= 3.6.5 =
* FIX: because of missing http protocol, filesize was parsed incorrect sometimes (not parsed).

= 3.6.4.2 =
* CHG: Download block HTML markup and css styles changed a little - nothing important...

= 3.6.4 =
* ADD: urldecode for incoming URLs writing to DB. Thank to Mark Carson!
* NEW: Exclude url counting filter. See options page.

= 3.6.3 =
* FIX: esc_url for wp_redirect() to avoid spaces deletion. Thank to Mark Carson!

= 3.6.2 =
* ADD: 'in_post' field on edit link admin page. It allow change ID of the post where link is...
* ADD: sanitize data on edit link POST request
* NEW: now all url in database saves as no protocol url - //site.ru/foo. So click on url 'http://site.ru/foo' and click on 'https://site.ru/foo' will be counted in one place.
* FIX: search in admin list worked incorrectly if we begun search from pagination page...
* FIX: correct detection of urls without protocol - //site.ru/foo
* FIX: correct title detection of urls without protocol - //site.ru/foo. Now uses WP HTTP API to retrive external html of link...
* FIX: some minor bug fixes

= 3.6.1 =
* ADD: 'title' attribute to [download] shortcode. Ex: [download url="URL" title="my file title"]
* ADD: improve tinymce button insert shortcode modal window - now you can find files in media library.
* FIX: It just counted the clicks done with the left-click-mouse-button and not counted clicks with the mouse-wheel and not with "open link..." from context menu opened with right-mouse-click.


= 3.6.0 =
* CHG: class name 'KCClick' changed to 'KCCounter'. If you have external code for this plugin, change in it all 'KCClick::' or 'KCC::' to 'KCCounter::'!!!
* CHG: Icon in Tinymce visual editor

= 3.5.1 =
* CHG: move localisation to translate.wordpress.org
* FIX: minor code fix

= 3.5.0 =
* FIX: XSS valneruble
* CHG: Change class name 'KCC' to 'KCClick'
* CHG: Translate PHP code to english. Now Russian is localization file...

= 3.4.9 =
* FIX: Remove link from Admin-bar for Roles who has no plugin access

= 3.4.8 =
* ADD: "click per day" data to edit link screen

= 3.4.7 - 3.4.7.3 =
* FIX: table structure to work fine with 'utf8mb4_unicode_ci' charset

= 3.4.6 =
* ADD: 'get_url_icon' filter to manage icons.

= 3.4.5 =
* ADD: Administrator option to set access to plugin to other WP roles.
* ADD: Option to add link to KCC Stat in admin bar.
* DEL: no HTTP_REFERER block on direct kcc url use.

= 3.4.4 =
* CHANGE: is_file extention check method for url.
* ADD: 'kcc_is_file' filter
* ADD: widget option to set link to post instead of link to file
* REMOVED: 'kcc_file_ext' filter

= 3.4.3 =
* ADD hooks: 'parce_kcc_url', 'kcc_count_before', 'kcc_count_after'.
* ADD: second parametr '$args' to 'kcc_insert_link_data' filter.
* ADD: punycode support. Now links filter in admin table trying to find keyword in 'link_name' db column too, not only in 'link_url'.
* FIX: It just count the clicks done with the left-click mouse button. Doesn't count clicks done with the mouse wheel, which opens in new tab. Also doesn't count clicks from mobile browsers. left click, mouse wheel, ctrl + left click, touch clicks (I test it in iphone – chrome and safari)

= 3.4.2 =
* ADD: 'kcc_admin_access' filter. For possibility to change access capability.
* FIX: redirect protection fix.

= 3.4.1 =
* FIX: parse kcc url fix.

= 3.4.0 =
* ADD: Hide url in download block option. See the options page.
* ADD: 'link_url' column index in DB for faster plugin work.
* ADD: 'get_kcc_url', 'kcc_redefine_redirect', 'kcc_file_ext', 'kcc_insert_link_data' hooks.
* ADD: Now plugin replace its ugly URL with original URL, when link hover.
* ADD: Replace 'edit link' text for download block to icon. It's more convenient.
* FIX: Correct updates of existing URLs. In some cases there appeared duplicates, when link contain '%' symbol (it could be cyrillic url or so on...)
* FIX: XSS attack protection.
* FIX: Many structure fix in code.


= 3.3.2 =
* FIX: php notice

= 3.3.1 =
* ADD: de_DE l10n, thanks to Volker Typke.

= 3.3.0 =
* ADD: l10n on plugin page.
* ADD: menu to admin page.
* FIX: antivirus wrongly says that file infected.

= 3.2.34 =
* FIX: Some admin css change

= 3.2.3.3 =
* ADD: jQuery links become hidden. All jQuery affected links have #kcc anchor and onclick attr with countclick url
* FIX: error with parse_url part. If url had "=" it was exploded...

= 3.2.3.2 =
* FIX: didn't correctly redirected to url with " " character
* ADD: round "clicks per day" on admin statistics page to one decimal digit

= 3.2.3.1 =
* FIX: "back to stat" link on "edit link" admin page

= 3.2.3 =
* FIX: redirects to https doesn't worked correctly
* FIX: PHP less than 5.3 support
* FIX: go back button on "edit link" admin page
* FIX: localization

= 3.2.2 =
* ADD: "go back" button on "edit link" admin page

= 3.2.1 =
Set autoreplace old shortcodes to new in DB during update: [download=""] [download url=""]

= 3.2 =
Widget has been added
