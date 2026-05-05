<?php

function wpms_admin_menu() {
    add_menu_page(
        'مدیریت تسک ها',
        'تسک ها',
        'manage_options',
        'wpms-tasks',
        'wpms_render_main_page',
        'dashicons-list-view',
        25
    );
    add_submenu_page(
        'wpms-tasks',
        'تنظیمات',
        'تنظیمات',
        'manage_options',
        'wpms-settings',
        'wpms_render_setting_page'
    );
}

add_action('admin_menu', 'wpms_admin_menu');