<?php

function wpms_render_setting_page() {
    // ذخیره آپشن در صورت ارسال فرم
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpms_save_settings'])) {
        check_admin_referer('wpms_settings_nonce');
        $new_time = sanitize_text_field($_POST['wpms_first_task_start_time']);
        update_option('wpms_first_task_start_time', $new_time);
        echo '<div class="notice notice-success"><p>تنظیمات ذخیره شد.</p></div>';
    }
    $current_time = get_option('wpms_first_task_start_time', '09:00');
    ?>
    <div class="wrap">
        <h1>تنظیمات پلاگین مدیریت تسک</h1>
        <form method="post">
            <?php wp_nonce_field('wpms_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="wpms_first_task_start_time">زمان شروع اولین تسک در روز (ساعت:دقیقه)</label></th>
                    <td>
                        <input type="time" name="wpms_first_task_start_time" id="wpms_first_task_start_time" value="<?php echo esc_attr($current_time); ?>" step="60">
                        <p class="description">فرمت 24 ساعته مانند 09:00 یا 14:30</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('ذخیره تنظیمات', 'primary', 'wpms_save_settings'); ?>
        </form>
    </div>
    <?php
}