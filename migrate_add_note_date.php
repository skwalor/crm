<?php
/**
 * Migration: Add note_date column to all notes tables
 * Run this script once to add the note_date column.
 *
 * Usage: php migrate_add_note_date.php
 */

require_once 'db_connect.php';

$tables = [
    'contact_notes',
    'opportunity_notes',
    'proposal_notes',
    'event_notes'
];

foreach ($tables as $table) {
    // Check if column already exists
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'note_date'");
    if ($result && $result->num_rows > 0) {
        echo "Column 'note_date' already exists in $table - skipping.\n";
        continue;
    }

    $sql = "ALTER TABLE `$table` ADD COLUMN `note_date` DATE NULL DEFAULT NULL AFTER `user_id`";
    if ($conn->query($sql)) {
        echo "Added 'note_date' column to $table.\n";
    } else {
        echo "ERROR adding 'note_date' to $table: " . $conn->error . "\n";
    }
}

echo "\nMigration complete.\n";
$conn->close();
