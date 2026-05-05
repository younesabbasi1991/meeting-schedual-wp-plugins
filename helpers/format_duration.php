<?php
function wpms_format_duration($minutes) {
    $total_seconds = intval($minutes) * 60;
    $hours = floor($total_seconds / 3600);
    $minutes = floor(($total_seconds % 3600) / 60);
    $seconds = $total_seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}