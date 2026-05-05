<?php
function wpms_render_main_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'meeting_scheduler';

    // -------------------------------------------------
    // 1. پردازش حذف تسک
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $wpdb->delete($table_name, ['id' => $id]);
        echo '<div class="notice notice-success"><p>تسک با موفقیت حذف شد.</p></div>';
    }

    // 2. پردازش افزودن / ویرایش تسک (وقتی فرم ارسال شود)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpms_save_task'])) {
        // بررسی nonce برای امنیت
        if (!isset($_POST['wpms_task_nonce']) || !wp_verify_nonce($_POST['wpms_task_nonce'], 'wpms_save_task')) {
            wp_die('خطای امنیتی');
        }

        $edit_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
        $task_name = sanitize_text_field($_POST['task_name']);
        $short_description = sanitize_textarea_field($_POST['short_description']);
        $presentation_time = !empty($_POST['presentation_time']) ? sanitize_text_field($_POST['presentation_time']) : null;
        $task_status = sanitize_text_field($_POST['task_status']);
        $additional_details = wp_kses_post($_POST['additional_details']);
        $audio_url = esc_url_raw($_POST['audio_url']);

        $elapsed_seconds = !empty($presentation_time) ? intval($presentation_time) * 60 : 0;

        $data = [
            'task_name' => $task_name,
            'short_description' => $short_description,
            'presentation_time' => $presentation_time,
            'task_status' => $task_status,
            'additional_details' => $additional_details,
            'audio_url' => $audio_url,
        ];



        if ($edit_id > 0) {
            // ویرایش - بدون تغییر elapsed_seconds (یا در صورت نیاز می‌توانید شرط بگذارید)
            $wpdb->update($table_name, $data, ['id' => $edit_id]);
            $data['elapsed_seconds'] = $elapsed_seconds; // به‌روزرسانی مقدار زمان باقیمانده
            echo '<div class="notice notice-success"><p>تسک بروزرسانی شد.</p></div>';
        } else {
            // افزودن جدید
            $max_order = $wpdb->get_var("SELECT MAX(task_order) FROM $table_name");
            $data['task_order'] = $max_order ? $max_order + 1 : 1;
            $data['elapsed_seconds'] = $elapsed_seconds;  // ← اضافه کردن این خط
            $wpdb->insert($table_name, $data);
            echo '<div class="notice notice-success"><p>تسک جدید اضافه شد.</p></div>';
        }
    }

    // بازیابی اطلاعات تسک برای حالت ویرایش (اگر edit=id در URL باشد)
    $edit_task = null;
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $edit_id = intval($_GET['edit']);
        $edit_task = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
    }

    // دریافت همه تسک‌ها به ترتیب
    $tasks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY task_order ASC");


    // -------------------------------------------------
    // نمایش HTML صفحه
    ?>
    <div class="wrap">
        <h1>مدیریت تسک‌های جلسات</h1>

        <!-- فرم افزودن/ویرایش تسک -->
        <h2><?php echo $edit_task ? 'ویرایش تسک' : 'افزودن تسک جدید'; ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('wpms_save_task', 'wpms_task_nonce'); ?>
            <input type="hidden" name="task_id" value="<?php echo $edit_task ? $edit_task->id : 0; ?>">

            <table class="form-table">
                <tr>
                    <th><label for="task_name">نام تسک *</label></th>
                    <td><input type="text" name="task_name" id="task_name" class="regular-text" required
                               value="<?php echo $edit_task ? esc_attr($edit_task->task_name) : ''; ?>"></td>
                </tr>
                <tr>
                    <th><label for="short_description">توضیح کوتاه</label></th>
                    <td><textarea name="short_description" id="short_description" rows="3"
                                  class="large-text"><?php echo $edit_task ? esc_textarea($edit_task->short_description) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="presentation_time">زمان ارائه</label></th>
                    <td><input type="number" name="presentation_time" id="presentation_time"
                               value="<?php echo $edit_task ? $edit_task->presentation_time : ''; ?>"></td>
                </tr>
                <tr>
                    <th><label for="task_status">وضعیت</label></th>
                    <td>
                        <select name="task_status">
                            <option value="pending" <?php selected($edit_task && $edit_task->task_status == 'pending'); ?>>
                                در انتظار
                            </option>
                            <option value="in_progress" <?php selected($edit_task && $edit_task->task_status == 'in_progress'); ?>>
                                در حال انجام
                            </option>
                            <option value="done" <?php selected($edit_task && $edit_task->task_status == 'done'); ?>>
                                انجام شده
                            </option>
                        </select>
                    </td>
                </tr>
<!--                <tr>-->
<!--                    <th><label for="additional_details">جزئیات تکمیلی</label></th>-->
<!--                    <td><textarea name="additional_details" id="additional_details" rows="5"-->
<!--                                  class="large-text">--><?php //echo $edit_task ? esc_textarea($edit_task->additional_details) : ''; ?><!--</textarea>-->
<!--                    </td>-->
<!--                </tr>-->
                <tr>
                    <th><label for="additional_details">جزئیات تکمیلی</label></th>
                    <td>
                        <?php
                        $editor_content = $edit_task ? $edit_task->additional_details : '';
                        $editor_id = 'additional_details';
                        $settings = array(
                            'textarea_name' => 'additional_details',
                            'textarea_rows' => 10,
                            'media_buttons' => true,
                            'teeny' => false,
                            'quicktags' => true,
                        );
                        wp_editor($editor_content, $editor_id, $settings);
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="audio_url">لینک فایل صوتی</label></th>
                    <td><input type="url" name="audio_url" id="audio_url" class="regular-text"
                               value="<?php echo $edit_task ? esc_attr($edit_task->audio_url) : ''; ?>"></td>
                </tr>
            </table>
            <?php submit_button($edit_task ? 'بروزرسانی تسک' : 'ذخیره تسک', 'primary', 'wpms_save_task'); ?>
            <?php if ($edit_task): ?>
                <a href="?page=wpms-tasks" class="button">لغو و بازگشت</a>
            <?php endif; ?>
        </form>

        <hr>

        <!-- لیست تسک‌ها با قابلیت drag & drop -->
        <h2>لیست تسک‌ها (برای تغییر ترتیب، بکشید و رها کنید)</h2>
        <table class="wp-list-table widefat fixed striped" id="sortable-tasks">
            <thead>
            <tr>
                <th style="width: 40px;">ترتیب</th>
                <th>نام تسک</th>
                <th>توضیح کوتاه</th>
                <th>زمان ارائه</th>
                <th>وضعیت</th>
                <th style="width: 100px;">عملیات</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($tasks): ?>
                <?php foreach ($tasks as $task): ?>
                    <tr data-id="<?php echo $task->id; ?>">
                        <td class="drag-handle">☰</td>
                        <td><?php echo esc_html($task->task_name); ?></td>
                        <td><?php echo esc_html($task->short_description); ?></td>
                        <td><?php echo esc_html($task->presentation_time); ?></td>
                        <td><?php echo esc_html($task->task_status); ?></td>
                        <td>
                            <a href="?page=wpms-tasks&edit=<?php echo $task->id; ?>">ویرایش</a> |
                            <a href="?page=wpms-tasks&delete=<?php echo $task->id; ?>"
                               onclick="return confirm('آیا از حذف این تسک اطمینان دارید؟');">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">هیچ تسکی یافت نشد. اولین تسک را اضافه کنید.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <style>
        #sortable-tasks tbody tr {
            cursor: move;
        }

        .drag-handle {
            cursor: grab;
            font-size: 18px;
            text-align: center;
        }
    </style>


    <script>
        jQuery(document).ready(function($) {
            $("#sortable-tasks tbody").sortable({
                handle: ".drag-handle",
                update: function(event, ui) {
                    var order = [];
                    $("#sortable-tasks tbody tr").each(function() {
                        order.push($(this).data("id"));
                    });
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "wpms_update_task_order",
                            order: order,
                            security: "<?php echo wp_create_nonce('wpms_task_order_nonce'); ?>"
                        },
                        success: function(response) {
                            if (response === 'success') {
                                console.log('ترتیب ذخیره شد.');
                            }
                        },
                        error: function() {
                            alert('خطا در ذخیره ترتیب.');
                        }
                    });
                }
            });
        });
    </script>
    <?php
}

;