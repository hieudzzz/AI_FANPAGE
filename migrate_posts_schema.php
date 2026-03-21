<?php
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

global $wpdb;
$table_name = $wpdb->prefix . 'aif_posts';

echo "Starting migration for $table_name...<br>";

// 1. Add wp_author_id if it doesn't exist
$row = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
if (!isset($row->wp_author_id)) {
    $wpdb->query("ALTER TABLE $table_name ADD wp_author_id bigint(20) DEFAULT 0");
    echo "Added wp_author_id column.<br>";
} else {
    echo "wp_author_id column already exists.<br>";
}

// 2. Remove stt column if it exists
if (isset($row->stt)) {
    $wpdb->query("ALTER TABLE $table_name DROP COLUMN stt");
    echo "Dropped stt column.<br>";
} else {
    echo "stt column already dropped or doesn't exist.<br>";
}

echo "Migration completed successfully.";
