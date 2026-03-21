<?php
/**
 * AIF Status Helper
 * Centralized status definitions with i18n support.
 * Internal values (DB/logic) remain in English.
 * Display labels use __() for future translation support.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIF_Status
{
    // Internal status constants (DB values - NEVER change these)
    const TODO = 'To do';
    const CONTENT_UPDATED = 'Content updated';
    const DONE = 'Done';
    const POSTED = 'Posted successfully';
    const QUEUED = 'Queued';
    const CONNECTED = 'Connected';
    const FAILED = 'Failed';

    /**
     * Get the translated display label for a status.
     */
    public static function label($status)
    {
        $labels = self::labels();
        // Handle variations of 'failed'
        if (stripos($status, 'failed') !== false) {
            return $labels[self::FAILED];
        }
        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Get all status labels (translated).
     */
    public static function labels()
    {
        return [
            self::TODO => __('Chờ xử lý', 'ai-fanpage'),
            self::CONTENT_UPDATED => __('Cập nhật nội dung', 'ai-fanpage'),
            self::DONE => __('Đã duyệt', 'ai-fanpage'),
            self::POSTED => __('Đã đăng', 'ai-fanpage'),
            self::QUEUED => __('Đang chờ đăng', 'ai-fanpage'),
            self::CONNECTED => __('Đã kết nối', 'ai-fanpage'),
            self::FAILED => __('Lỗi đăng bài', 'ai-fanpage'),
        ];
    }

    /**
     * Get CSS badge class for a status.
     */
    public static function badge_class($status)
    {
        $classes = self::js_badge_classes();
        // Handle variations of 'failed'
        if (stripos($status, 'failed') !== false) {
            return $classes[self::FAILED];
        }
        return isset($classes[$status]) ? $classes[$status] : 'status-pending';
    }

    /**
     * Get allowed statuses for validation.
     */
    public static function allowed()
    {
        return [self::TODO, self::CONTENT_UPDATED, self::DONE, self::POSTED];
    }

    /**
     * Get labels array for JavaScript localization.
     * Returns [internal_value => translated_label]
     */
    public static function js_labels()
    {
        return self::labels();
    }

    /**
     * Get badge classes array for JavaScript localization.
     * Returns [internal_value => css_class]
     */
    public static function js_badge_classes()
    {
        return [
            self::TODO => 'status-pending',
            self::CONTENT_UPDATED => 'status-processing', // Blue based on CSS swap
            self::DONE => 'status-future',      // Amber based on CSS swap
            self::POSTED => 'status-publish',     // Green
            self::QUEUED => 'status-future',
            self::FAILED => 'status-error',       // Red
        ];
    }
}
