<?php
add_shortcode('wpms_tasks_list', 'wpms_frontend_tasks_list');

function wpms_frontend_tasks_list()
{

    global $wpdb;
    $table_name = $wpdb->prefix . 'meeting_scheduler';

    //-------------------  REGISTERED STYLE AND SCRIPTS
    global $wpms_shortcode_rendered;
    $wpms_shortcode_rendered = true;

    if (isset($wpms_shortcode_rendered) && $wpms_shortcode_rendered === true) {
        wp_enqueue_style('wpms-fronted-style', plugin_dir_url(__FILE__) . '../assets/css/wpms-style.css', '', '1.0.0');
        wp_enqueue_script('wpms-fronted-js', plugin_dir_url(__FILE__) . '../assets/js/wpms-js.js', ['jquery'], '1.0.0', true);
    }

    ob_start();
    //-------------------  GET ALL TASKS
    $tasks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY task_order ASC");
    if (empty($tasks)) {
        return '<p>هیچ تسکی یافت نشد.</p>';
    }

    // ------------------- FIRST TIME START FROM OPTIONS
    $start_time_str = get_option('wpms_first_task_start_time', '09:00');
    $base_time = DateTime::createFromFormat('H:i', $start_time_str);
    if (!$base_time) {
        $base_time = new DateTime('09:00');
    }

    $current_time = clone $base_time;
    foreach ($tasks as $task) {
        $duration_min = intval($task->presentation_time); // استفاده از ستون موجود
        $task->start_time = clone $current_time;
        $task->end_time = clone $current_time;
        $task->end_time->modify("+{$duration_min} minutes");
        $task->duration_min = $duration_min;
        // زمان جاری را برای تسک بعدی به روز می‌کنیم
        $current_time = clone $task->end_time;
    }

    ?>

    <?php foreach ($tasks as $task): ?>
    <?php
    $start_str = $task->start_time->format('H:i');
    $end_str = $task->end_time->format('H:i');
    $duration_sec = $task->duration_min * 60;

    $remaining_seconds = intval($task->elapsed_seconds);
    if ($remaining_seconds <= 0 && $task->presentation_time) {
        // اگر به هر دلیل elapsed_seconds صفر یا منفی است، از مقدار کامل استفاده کن
        $remaining_seconds = intval($task->presentation_time) * 60;
    }
    $status = $task->task_status; // pending, in_progress, done
    ?>
    <div class="task-box">

        <div class="control-btn-area">
            <button class="run-task" data-task-id="<?php echo $task->id; ?>">فعال</button>
            <button class="end-task" data-task-id="<?php echo $task->id; ?>">پایان</button>
        </div>
        <?php if (!empty($task->audio_url)): ?>
            <div class="task-box__top-row">
                <audio controls src="<?php echo esc_url($task->audio_url); ?>"></audio>
            </div>
        <?php endif; ?>

        <div class="task-box__middle-row">
            <div class="task-box__number-area">
                <p><?php echo $task->task_order ?></p>
            </div>
            <div class="task-box__title-area">
                <h3 class="task-box__title"><?php echo $task->task_name ?></h3>
                <span class="task-box__subtitle"><?php echo $task->short_description ?></span>
            </div>
            <div class="task-box__time-area">
                <div class="task-box__start-time time-box time-box-sm">
                    <span class="time-box__title">زمان شروع</span>
                    <span><?php echo $start_str ?></span>
                </div>
                <div class="task-box__end-time time-box time-box-sm">
                    <span class="time-box__title">زمان پایان</span>
                    <span><?php echo $end_str ?></span>
                </div>
                <!--                <div class="task-box__duration-time time-box time-box-lg">-->
                <!--                    <span class="time-box__title">مدت ارائه</span>-->
                <!--                    <span>10:00</span>-->
                <!--                </div>-->
                <div class="task-box__duration-time time-box time-box-lg">
                    <!--                    <span class="time-box__title">مدت ارائه</span>-->
                    <span class="task-timer-display"
                          data-seconds="<?php echo intval($task->presentation_time) * 60; ?>">
        <?php echo wpms_format_duration($task->presentation_time); ?>
    </span>
                </div>
            </div>
        </div>
        <div class="task-box__bottom-row">
            <div class="wpms-read-more-container">
                <div class="wpms-read-more-content"><?php echo $task->additional_details ?></div>
                <button class="wpms-toggle-btn">مشاده محتوا</button>
            </div>
        </div>

    </div>
<?php endforeach; ?>

    <?php

    return ob_get_clean();
}

