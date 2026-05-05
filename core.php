<?php
/**
 * Plugin Name:     پلاگین مدیریت جلسات اداری
 * Plugin URI:
 * Description:     این پلاگین برای مدیریت بهتر جلسات اداری انجمن های دوازده قدمی ساخته شده است.
 * Author:          یونس عباسی
 * Author URI:
 * Text Domain:     meeting-scheduler
 * Domain Path:
 * Version:         1.0.0
 * Requires at least: 6.0
 * Requires PHP:    7.4
 * License:
 * License URI:
 */



// PREVENTING OF DIRECT ACCESS
// ----------------------------------------
defined('ABSPATH') or die('No direct access');


// PREVENTING OF DIRECT ACCESS
// ----------------------------------------
define( 'WPMS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );



// INCLUDES FILES
// ----------------------------------------
include_once WPMS_PLUGIN_PATH . 'admin/activation.php';
include_once WPMS_PLUGIN_PATH . 'admin/wpms_add_menu.php';
include_once WPMS_PLUGIN_PATH . 'admin/wpms_render_settings_page.php';
include_once WPMS_PLUGIN_PATH . 'admin/wpms_render_main_page.php';
include_once WPMS_PLUGIN_PATH . 'admin/wpms_jquery_ui_enqueue.php';
include_once WPMS_PLUGIN_PATH . 'admin/wpms_ajax_handler_sort_ui.php';
include_once WPMS_PLUGIN_PATH . 'front/wpms_task_shortcode.php';
include_once WPMS_PLUGIN_PATH . 'helpers/format_duration.php';


// ACTIVATE PLUGIN AND CREATE TABLE IN DATABASE
// ----------------------------------------
register_activation_hook(__FILE__, 'wpms_activate_plugin');

// ADD MENU PAGE
// ----------------------------------------

