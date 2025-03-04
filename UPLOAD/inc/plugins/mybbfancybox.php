<?php

/**
 * MyBB FancyBox - plugin for MyBB 1.8.x forum software
 *
 * @package MyBB Plugin
 * @author MyBB Group - Eldenroot & Wildcard & effone & Laird - <eldenroot@gmail.com>
 * @copyright 2021 MyBB Group <http://mybb.group>
 * @link <https://github.com/mybbgroup/MyBB_Fancybox>
 * @license GPL-3.0
 *
 */

 /**
  * 3rd party JavaScript library is used - FancyBox - http://fancyapps.com/fancybox/3/ created by Jānis Skarnelis
  * FancyBox is licenced under GPLv3 licence and is free for all non-commercial applications, for commercial applications the paid licence is required!
  * Visit official website https://fancyapps.com/fancybox/3/ or GitHub project site https://github.com/fancyapps/fancybox for more information
 */

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 * If not, see <http://www.gnu.org/licenses/>.
 */

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.");
}

/** Installation & info **/

/**
 * Plugin information
 *
 * @return array
 */
function mybbfancybox_info()
{
	global $lang;

	if (!isset($lang->mybbfancybox)) {
		$lang->load('mybbfancybox');
	}

	return array(
		"name"			=> $lang->mybbfancybox,
		"description"	=> $lang->mybbfancybox_description . '<a href=\'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amount=0&business=eldenroot%40gmail.com&item_name=MyBB+Plugin+Development&no_note=1&no_shipping=1&currency_code=USD\' target=\'_blank\'><img style=\'float: right; margin-top: 5px;\' src=\'https://www.paypalobjects.com/webstatic/en_US/btn/btn_donate_pp_142x27.png\' border=\'0\' alt=\'PayPal Donate\' /></a>',
		"website"		=> "https://github.com/mybbgroup/MyBB_Fancybox",
		"author"		=> "MyBB Group (Eldenroot & Wildcard & effone & Laird)",
		"authorsite"	=> "https://github.com/mybbgroup/MyBB_Fancybox",
		"version"		=> "1.1.2",
		"codename"		=> "mybbfancybox",
		"compatibility" => "18*"
	);
}

/**
 * Detect plugin installation status
 *
 * @return bool
 */
function mybbfancybox_is_installed()
{
	global $db;

	$query = $db->simple_select('themestylesheets', 'sid', "name='mybbfancybox.css'");
	return ($db->num_rows($query) > 0);
}

/**
 * Plugin installation
 *
 * @return void
 */
function mybbfancybox_install()
{
	global $db, $config, $lang;

	if (!isset($lang->mybbfancybox)) {
		$lang->load('mybbfancybox');
	}

	// Add stylesheet to the master template so it becomes inherited
	$stylesheet = @file_get_contents(MYBB_ROOT.'inc/plugins/mybbfancybox/mybbfancybox.css');
	$attachedto = '';

	$name = 'mybbfancybox.css';
	$thisStyleSheet = array(
		'name' => $name,
		'tid' => 1,
		'attachedto' => $db->escape_string($attachedto),
		'stylesheet' => $db->escape_string($stylesheet),
		'cachefile' => $name,
		'lastmodified' => TIME_NOW,
	);

	// Update any children theme
	$db->update_query('themestylesheets', array(
		"attachedto" => $attachedto
	), "name='{$name}'");

	// Now update/insert the master stylesheet
	$query = $db->simple_select('themestylesheets', 'sid', "tid='1' AND name='{$name}'");
	$sid = (int) $db->fetch_field($query, 'sid');

	if ($sid) {
		$db->update_query('themestylesheets', $thisStyleSheet, "sid='{$sid}'");
	} else {
		$sid = $db->insert_query('themestylesheets', $thisStyleSheet);
		$thisStyleSheet['sid'] = (int) $sid;
	}

	// Now cache the actual files
	require_once MYBB_ROOT . "{$config['admin_dir']}/inc/functions_themes.php";

	if (!cache_stylesheet(1, $thisStyleSheet['cachefile'], $stylesheet))
	{
		$db->update_query("themestylesheets", array('cachefile' => "css.php?stylesheet={$sid}"), "sid='{$sid}'", 1);
	}

	// And update the CSS file list
	update_theme_stylesheet_list(1, false, true);

	// Add plugin settings into ACP
	// Add plugin settings group
	$query = $db->simple_select('settinggroups', 'MAX(disporder) AS disporder');
	$disporder = (int)$db->fetch_field($query, 'disporder');

	$setting_group = array(
		'name' => 'mybbfancybox',
		"title" => $db->escape_string($lang->setting_group_mybbfancybox),
		"description" => $db->escape_string($lang->setting_group_mybbfancybox_desc),
		'isdefault' => 0
	);

	$setting_group['disporder'] = ++$disporder;

	$gid = (int)$db->insert_query('settinggroups', $setting_group);

	$buttonSetting = <<<EOF
php
<select multiple name=\"upsetting[mybbfancybox_buttons][]\" size=\"7\">
	<option value=\"slideShow\" ".(is_array(unserialize(\$setting['value'])) ? (\$setting['value'] != "" && in_array("slideShow", unserialize(\$setting['value'])) ? "selected=\"selected\"":""):"").">{$lang->setting_mybbfancybox_buttons_slideshow}</option>
	<option value=\"fullScreen\" ".(is_array(unserialize(\$setting['value'])) ? (\$setting['value'] != "" && in_array("fullScreen", unserialize(\$setting['value'])) ? "selected=\"selected\"":""):"").">{$lang->setting_mybbfancybox_buttons_fullscreen}</option>
	<option value=\"thumbs\" ".(is_array(unserialize(\$setting['value'])) ? (\$setting['value'] != "" && in_array("thumbs", unserialize(\$setting['value'])) ? "selected=\"selected\"":""):"").">{$lang->setting_mybbfancybox_buttons_thumbs}</option>
	<option value=\"share\" ".(is_array(unserialize(\$setting['value'])) ? (\$setting['value'] != "" && in_array("share", unserialize(\$setting['value'])) ? "selected=\"selected\"":""):"").">{$lang->setting_mybbfancybox_buttons_share}</option>
	<option value=\"download\" ".(is_array(unserialize(\$setting['value'])) ? (\$setting['value'] != "" && in_array("download", unserialize(\$setting['value'])) ? "selected=\"selected\"":""):"").">{$lang->setting_mybbfancybox_buttons_download}</option>
	<option value=\"zoom\" ".(is_array(unserialize(\$setting['value'])) ? (\$setting['value'] != "" && in_array("zoom", unserialize(\$setting['value'])) ? "selected=\"selected\"":""):"").">{$lang->setting_mybbfancybox_buttons_zoom}</option>
	<option value=\"close\" ".(is_array(unserialize(\$setting['value'])) ? (\$setting['value'] != "" && in_array("close", unserialize(\$setting['value'])) ? "selected=\"selected\"":""):"").">{$lang->setting_mybbfancybox_buttons_close}</option>
</select>

EOF;

	// Open image URLs settings
	$settings = array(
		'open_image_urls' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 1
		),
		'allowed_extensions' => array(
			'optionscode'	=> 'text',
			'value'			=> ''
		),
		'include_images_from_urls_into_gallery' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 1
		),
		'protect_images' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 0
		),
		'watermark' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 0
		),
		'watermark_image' => array(
			'optionscode'	=> 'text',
			'value'			=> 'images/mybbfancybox/watermark.png'
		),
		'watermark_low_resolution_images' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 1
		),
		'watermark_resolutions' => array(
			'optionscode'	=> 'text',
			'value'			=> '300|300'
		),
		'per_post_gallery' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 1
		),
		'loop' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 1
		),
		'infobar' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 1
		),
		'arrows' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 1
		),
		'rotate' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 1
		),
		'thumbs' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 0
		),
		'minimize' => array(
			'optionscode'	=> 'yesno',
			'value'			=> 1
		),
		'buttons' => array(
			'optionscode'	=> $db->escape_string($buttonSetting),
			'value'			=> $db->escape_string(serialize(array('slideShow', 'fullScreen', 'thumbs', 'share', 'download', 'zoom', 'close')))
		)
	);

	$disporder = 0;

	foreach ($settings as $name => $setting)
	{
		$name = "mybbfancybox_{$name}";

		$setting['name'] = $db->escape_string($name);

		$lang_var_title = "setting_{$name}";
		$lang_var_description = "setting_{$name}_desc";

		$setting['title'] = $db->escape_string($lang->{$lang_var_title});
		$setting['description'] = $db->escape_string($lang->{$lang_var_description});
		$setting['disporder'] = $disporder;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
		++$disporder;
	}

	rebuild_settings();
}

/**
 * Plugin removal
 *
 * @return void
 */
function mybbfancybox_uninstall()
{
	global $db;

	$where = "name='mybbfancybox.css'";

	// Find the master and any children
	$query = $db->simple_select('themestylesheets', 'tid,name', $where);

	// Delete them all from the server
	while ($styleSheet = $db->fetch_array($query)) {
		@unlink(MYBB_ROOT."cache/themes/{$styleSheet['tid']}_{$styleSheet['name']}");
		@unlink(MYBB_ROOT."cache/themes/theme{$styleSheet['tid']}/{$styleSheet['name']}");
	}

	// Then delete them from the database
	$db->delete_query('themestylesheets', $where);

	// Now remove them from the CSS file list
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	update_theme_stylesheet_list(1, false, true);

	// Delete plugin settings in ACP
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settings WHERE name LIKE 'mybbfancybox\\_%'");
	$db->write_query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name = 'mybbfancybox'");

	// Rebuild settings
	rebuild_settings();
}

function mybbfancybox_activate()
{
	require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";
	find_replace_templatesets('portal_announcement', "#" . preg_quote('<p>') . "#i", '<p class="post_body scaleimages" id="pid_{$announcement[\'pid\']}">');
}

function mybbfancybox_deactivate()
{
	require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";
	find_replace_templatesets('portal_announcement', "#" . preg_quote('<p class="post_body scaleimages" id="pid_{$announcement[\'pid\']}">') . "#i", '<p>');
}

	/** Forum **/

mybbfancybox_init();

/**
 * Add hooks when appropriate
 *
 * @return void
 */
function mybbfancybox_init()
{
	global $mybb, $plugins;

	if (defined('IN_ADMINCP')) {
		$plugins->add_hook('admin_config_settings_begin', 'mybbfancybox_admin_config_settings');
		$plugins->add_hook('admin_config_settings_change', 'mybbfancybox_admin_config_settings_change');
		$plugins->add_hook('admin_settings_print_peekers', 'mybbfancybox_print_peekers');
		return;
	}

	// Open image URL link in posts
	// Check ACP settings
	if ($mybb->settings['mybbfancybox_open_image_urls'] == '1') {
		// Add hook
		$plugins->add_hook("parse_message_end","mybbfancybox_post");
	}

	if (defined('THIS_SCRIPT') && THIS_SCRIPT == 'showthread.php') {
		// Add hook
		$plugins->add_hook('showthread_start', 'mybbfancybox_start');
	}

	if (defined('THIS_SCRIPT') && THIS_SCRIPT == 'portal.php') {
		// Add hook
		$plugins->add_hook('portal_start', 'mybbfancybox_start');
	}
}

/**
 * Modify templates to include data for FancyBox and configure JavaScript
 *
 * @return void
 */
function mybbfancybox_start()
{
	global $mybb, $templates, $headerinclude, $lang;

	if (!isset($lang->mybbfancybox)) {
		$lang->load('mybbfancybox');
	}

	if (defined('THIS_SCRIPT') && THIS_SCRIPT == 'portal.php') {
		$gallerystr = $mybb->settings['mybbfancybox_per_post_gallery'] ? "data-{\$announcement['pid']}" : 'gallery';
	} else {
		$gallerystr = $mybb->settings['mybbfancybox_per_post_gallery'] ? "data-{\$post['pid']}" : 'gallery';
	}

	// Apply required changes in postbit_attachments_thumbnails_thumbnail template (replace all content)
	$templates->cache['postbit_attachments_thumbnails_thumbnail'] = '<a href="attachment.php?aid={$attachment[\'aid\']}" data-fancybox="'.$gallerystr.'" data-type="image" data-caption="<b>{$lang->postbit_attachment_filename}</b> {$attachment[\'filename\']} - <b>{$lang->postbit_attachment_size}</b> {$attachment[\'filesize\']} - <b>{$lang->mybbfancybox_uploaded}</b> {$attachdate} - <b>{$lang->mybbfancybox_views}</b> {$attachment[\'downloads\']}{$lang->mybbfancybox_views_symbol_after}"><img src="attachment.php?thumbnail={$attachment[\'aid\']}" class="attachment" alt="" title="{$lang->postbit_attachment_filename} {$attachment[\'filename\']}&#13{$lang->postbit_attachment_size} {$attachment[\'filesize\']}&#13{$lang->mybbfancybox_uploaded} {$attachdate}&#13{$lang->mybbfancybox_views} {$attachment[\'downloads\']}{$lang->mybbfancybox_views_symbol_after}" /></a>&nbsp;&nbsp;&nbsp;';

	// Apply required changes in postbit_attachments_images_image template (replace all content)
	$templates->cache['postbit_attachments_images_image'] = '<a href="attachment.php?aid={$attachment[\'aid\']}" target="_blank" data-fancybox="'.$gallerystr.'" data-type="image" ><img src="attachment.php?aid={$attachment[\'aid\']}" class="attachment" alt="" title="{$lang->postbit_attachment_filename} {$attachment[\'filename\']}&#13{$lang->postbit_attachment_size} {$attachment[\'filesize\']}&#13{$lang->mybbfancybox_uploaded} {$attachdate}&#13{$lang->mybbfancybox_views} {$attachment[\'downloads\']}{$lang->mybbfancybox_views_symbol_after}" /></a>&nbsp;&nbsp;&nbsp;';

	$buttonArray = (array) unserialize($mybb->settings['mybbfancybox_buttons']);

	// Minimize button - load JS code only when enabled in ACP
	$minimize = '';

	if ($mybb->settings['mybbfancybox_minimize'] == 1) {
		array_splice($buttonArray, count($buttonArray)-1, 0, 'minimize');
	}
	foreach (array(
		'mybbfancybox_protect_images' => 'protect',
		'mybbfancybox_loop' => 'loop',
		'mybbfancybox_infobar' => 'infobar',
		'mybbfancybox_arrows' => 'arrows',
		'mybbfancybox_rotate' => 'rotate',
		'mybbfancybox_thumbs' => 'thumbs',
		'mybbfancybox_per_post_gallery' => 'perpostgallery',
	) as $key => $var) {
		$$var = $mybb->settings[$key] ? 'true' : 'false';
	}

	foreach (array(
		'mybbfancybox_watermark' => 'watermark',
		'mybbfancybox_watermark_low_resolution_images' => 'watermarkLoRes',
	) as $key => $var) {
		$$var = $mybb->settings[$key] ? true : false;
	}

	$afterLoadScript = $watermarkStyleSheet = '';
	if ($protect &&
		$watermark) {
		$watermark = '';

		$afterLoadScript = <<<EOF

		afterLoad: function(instance, current) {
			current.\$slide.addClass('watermark');
		},
EOF;
		if (!$watermarkLoRes) {
			$pieces = explode('|', $mybb->settings['mybbfancybox_watermark_resolutions']);

			list($w, $h) = array_map('intval', $pieces);

			if ($w > 0 &&
				$h > 0) {
				$afterLoadScript = <<<EOF

		afterLoad: function(instance, current) {
			if (current.width > {$w} && current.height > {$h} ) {
				current.\$slide.addClass('watermark');
			}
		},
EOF;
			}
		}

		if(!empty($mybb->settings['mybbfancybox_watermark_image']) &&
			@getimagesize($mybb->settings['mybbfancybox_watermark_image'])) {

			$watermarkimage = $mybb->settings['mybbfancybox_watermark_image'];

			$watermarkStyleSheet = <<<EOF

	<style type="text/css">
		.fancybox-slide.watermark .fancybox-spaceball {
			background-image: url('{$watermarkimage}');
		}
	</style>
EOF;
		}
	}

	if (!empty($buttonArray) &&
		count($buttonArray) > 0) {
		$buttons = "'".implode("','", $buttonArray)."'";
	}

	$buttons = "\n\t\tbuttons: [ {$buttons} ],";

	$headerinclude .= <<<EOF


	<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/fancybox/jquery.fancybox.min.css" type="text/css" media="screen" />
	<script type="text/javascript" src="{$mybb->asset_url}/jscripts/fancybox/jquery.fancybox.min.js"></script>
	<script type="text/javascript" src="{$mybb->asset_url}/jscripts/mybbfancybox.js"></script>
	<script type="text/javascript">
	<!--
	MyBBFancyBox.setup({
		clickToEnlarge: "{$lang->mybbfancybox_click_to_enlarge}",
		CLOSE: "{$lang->mybbfancybox_close}",
		NEXT: "{$lang->mybbfancybox_next}",
		PREV: "{$lang->mybbfancybox_prev}",
		ERROR: "{$lang->mybbfancybox_error}",
		PLAY_START: "{$lang->mybbfancybox_play_start}",
		PLAY_STOP: "{$lang->mybbfancybox_play_stop}",
		FULL_SCREEN: "{$lang->mybbfancybox_full_screen}",
		THUMBS: "{$lang->mybbfancybox_thumbs}",
		DOWNLOAD: "{$lang->mybbfancybox_download}",
		SHARE: "{$lang->mybbfancybox_share}",
		ZOOM: "{$lang->mybbfancybox_zoom}",
		MINIMIZE: "{$lang->mybbfancybox_minimize}",
	}, {
		perpostgallery: {$perpostgallery},
		protect: {$protect},
		loop: {$loop},
		infobar: {$infobar},
		arrows: {$arrows},
		rotate: {$rotate},
		thumbs: {
			autoStart: {$thumbs},
			hideOnClose: true
		},{$buttons}{$afterLoadScript}
		btnTpl: {
			minimize:
			'<button data-fancybox-minimize class="fancybox-button fancybox-button--minimise" title="{{MINIMIZE}}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 445 445"><g fill="#010002"><path d="M440.3 4.7a15.9 15.9 0 0 0-22.5 0L286 136.5V47.7a16 16 0 0 0-31.7 0V175l1.2 6 3.3 5 .1.2h.2l5 3.4 6 1.2h127.2a16 16 0 0 0 0-31.8h-88.8L440.3 27.2a16 16 0 0 0 0-22.5zM180.9 255.5l-6-1.2H47.6a16 16 0 0 0 0 31.8h88.7L4.7 417.8A15.9 15.9 0 1 0 27 440.3L159 308.5v88.8a16 16 0 0 0 31.8 0V270.2l-1.2-6a16 16 0 0 0-8.6-8.7z"/></g></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 381.4 381.4"><path d="M380.1 9.8c-1.6-3.9-4.7-7-8.5-8.6L365.5 0h-159a16 16 0 0 0 0 31.8h120.6L31.8 327V206.6a15.9 15.9 0 0 0-31.8 0v159l1.2 6 3.3 5 .1.1.2.1 5 3.4 6 1.2h159a16 16 0 0 0 0-31.8H54.3L349.6 54.3v120.5a16 16 0 0 0 31.8 0v-159l-1.3-6z" fill="#010002"/></svg></button>'
		}
	});
	// -->
	</script>
	{$watermarkStyleSheet}
EOF;

}

// If enabled, then make a black magic
// ...muahahaha... -wc

/**
 * Detect image urls in posts and add data for FancyBox
 *
 * @param  string
 * @return string
 */
function mybbfancybox_post($message)
{
	// Only parse allowed extensions once
	static $allowedExtensions = null;

	global $mybb, $post;

	if (empty($post)) return $message;

	// If null, then it has not yet been built
	if ($allowedExtensions === null) {
		// Set to an empty array so we don't try to build it again if setting is blank/errored
		$allowedExtensions = array();

		// Get all of the allowed image extensions from the plugin setting
		$userExts = explode(',', $mybb->settings['mybbfancybox_allowed_extensions']);

		// Remove all empty array elements (eg. 'jpg,,png')
		$userExts = array_filter($userExts);

		// Trim all array elements (eg. 'jpg, png, gif ,')
		$allowedExtensions = array_map('trim', $userExts);
	}

	// Grab the allowed extensions
	$exts = $allowedExtensions;
	$regx = '';

	// If the setting value isn't empty, use it to build a custom regular expression
	if (is_array($exts) && !empty($exts)) {
		// No separator for the first extension
		$sep = '';
		foreach ($exts as $ext) {
			// Special case for APNG
			if ($ext === 'apng') {
				$regx .= $sep.'apng:\/\/[^ ]+';
				continue;
			}

			// Add this extension to the list w/separator (if applicable)
			$regx .= $sep.$ext;

			// Add a separator after the first extension
			$sep = '|';
		}

		// Just in case admin inputs illegal characters
		$regx = preg_quote($regx);
	}

	// Default
	if (!$regx) {
		$regx = 'png|gif|jpeg|bmp|jpg|apng://[^ ]+';
	}

	// Search for image extension in URL link
	$find = '(<a\\s+([^>]*)(?<=\\b)href="([^"]*\\.(?:'.$regx.'))"([^>]*)>(.*?)</a>)s';

	$gallerystr = $mybb->settings['mybbfancybox_per_post_gallery'] ? "data-{$post['pid']}" : 'gallery';

	if (!$mybb->settings['mybbfancybox_include_images_from_urls_into_gallery']) {
		$gallerystr .= '-post-url';
	}

	// For safety, ensure that if something goes wrong and we end up with
	// an empty message, then we restore the original one.
	$message_old = $message;

	// Open image URL link in MyBB FancyBox modal window
	$message = preg_replace_callback($find, function ($matches) use($gallerystr) {
		return '<a '.$matches[1].'href="'.$matches[2].'"'.$matches[3].' data-fancybox="'.$gallerystr.'" data-type="image" data-caption="'.htmlspecialchars_uni($matches[4]).'">'.$matches[4].'</a>';
	}, $message);

	return $message ? $message : $message_old;
}

	/** ACP **/

/**
 * Language support
 *
 * @return void
 */
function mybbfancybox_admin_config_settings()
{
    global $lang;

	if (!isset($lang->mybbfancybox)) {
		$lang->load('mybbfancybox');
	}
}

/**
 * Serialize button setting when our settings group is updated
 *
 * @return void
 */
function mybbfancybox_admin_config_settings_change()
{
    global $mybb;

	// Only serialize if our settings are being updated
    if (isset($mybb->input['upsetting']['mybbfancybox_open_image_urls'])) {
		$mybb->input['upsetting']['mybbfancybox_buttons'] = serialize($mybb->input['upsetting']['mybbfancybox_buttons']);
	}
}

/**
 * Add settings Peekers
 *
 * @param  array
 * @return array
 */
function mybbfancybox_print_peekers($peekers)
{
	global $mybb;

	// Protect controls: watermark and watermark for low resolution images
	$peekers[] = 'new Peeker($(".setting_mybbfancybox_protect_images"), $("#row_setting_mybbfancybox_watermark, #row_setting_mybbfancybox_watermark_image, #row_setting_mybbfancybox_watermark_low_resolution_images, #row_setting_mybbfancybox_watermark_resolutions"), 1, true)';

	// Watermark controls: watermark low resolution dimensions
	$peekers[] = 'new Peeker($(".setting_mybbfancybox_watermark"), $("#row_setting_mybbfancybox_watermark_image, #row_setting_mybbfancybox_watermark_low_resolution_images, #row_setting_mybbfancybox_watermark_resolutions"), 1, true)';

	// Watermark controls: watermark low resolution images
	$peekers[] = 'new Peeker($(".setting_mybbfancybox_watermark_low_resolution_images"), $("#row_setting_mybbfancybox_watermark_resolutions"), 1, true)';

	return $peekers;
}
