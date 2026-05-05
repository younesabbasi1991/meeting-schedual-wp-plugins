<?php
add_action('admin_enqueue_scripts', 'wpms_admin_enqueue');
function wpms_admin_enqueue($hook) {
    // فقط در صفحاتی که slug آنها wpms-tasks یا wpms-settings است
    if (strpos($hook, 'wpms-tasks') === false && strpos($hook, 'wpms-settings') === false) {
        return;
    }
    wp_enqueue_script('jquery-ui-sortable');
//    wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
    wp_enqueue_style('wp-jquery-ui-dialog'); // یک استایل پایه برای jQuery UI
}