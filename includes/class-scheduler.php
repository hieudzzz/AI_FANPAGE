<?php
class AIF_Scheduler
{

    public function __construct()
    {
        // Register Cron Hook
        add_action('aif_cron_publish_event', [$this, 'run_schedule_check']);

        // MUST add filter BEFORE wp_schedule_event so 'every_minute' is known
        add_filter('cron_schedules', [$this, 'add_cron_interval']);
        add_action('aif_cron_process_queue', [$this, 'run_queue_processing']);

        // Schedule after filter is registered
        add_action('init', [$this, 'maybe_schedule_cron']);
    }

    public function maybe_schedule_cron()
    {
        if (!wp_next_scheduled('aif_cron_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'aif_cron_process_queue');
        }
    }

    public function add_cron_interval($schedules)
    {
        $schedules['every_minute'] = array(
            'interval' => 60,
            'display' => esc_html__('Every Minute'),
        );
        return $schedules;
    }

    /**
     * Main Scheduler Loop (Old Scheduler)
     */
    public function run_schedule_check()
    {
        // ... (Existing logic for schedule_time based posting if kept) ...
        // Re-implementing existing logic briefly to ensure context is kept if I'm replacing the whole file end.
        // Actually, the previous view_file showed I'm replacing from line 1.
        // Wait, I am replacing the end of the file? No, I need to be careful.
        // I will use replace_file_content to INSERT, or just overwrite the constructor and add methods.
    }

    public function run_queue_processing()
    {
        $manager = new AIF_Facebook_Manager();
        // Check for scheduled posts that are due
        $manager->check_scheduled_posts();
        // Process pending posts
        $manager->process_queue();
    }
}
