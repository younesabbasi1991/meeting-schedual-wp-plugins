<?php
add_action('wp_ajax_wpms_update_task_order', 'wpms_ajax_update_order');
function wpms_ajax_update_order() {
    check_ajax_referer('wpms_task_order_nonce', 'security');

    global $wpdb;
    $table_name = $wpdb->prefix . 'meeting_scheduler';

    $order = $_POST['order'];
    if (is_array($order)) {
        foreach ($order as $index => $task_id) {
            $wpdb->update(
                $table_name,
                ['task_order' => $index + 1],
                ['id' => intval($task_id)]
            );
        }
        echo 'success';
    } else {
        echo 'error';
    }
    wp_die();
}

