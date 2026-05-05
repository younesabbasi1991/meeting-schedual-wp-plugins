<?php
function wpms_activate_plugin()
{

    global $wpdb;

    $table_name = $wpdb->prefix . 'meeting_scheduler';

    $charset_collate = $wpdb->get_charset_collate();


    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    task_order int(11) NOT NULL DEFAULT 0,
    task_name varchar(255) NOT NULL,
    short_description text,
    presentation_time int DEFAULT NULL,
    task_status varchar(50) DEFAULT 'pending',
    additional_details longtext,
    audio_url varchar(255) DEFAULT NULL,
    elapsed_seconds int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY task_order (task_order)
) $charset_collate;";

    // بارگذاری فایل required برای dbDelta
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);


    if ( get_option( 'wpms_first_task_start_time' ) === false ) {
        update_option( 'wpms_first_task_start_time', '09:00' );
    }
}
