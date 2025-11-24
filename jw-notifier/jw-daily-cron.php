<?php
/**
 * CRON ENTRY POINT – DAILY TEXT
 * Spouští hosting cron.
 */

// ÚPLNĚ JEDNODUŠE – WordPress je přímo ve /www
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

if (!function_exists('jw_daily_run_cron')) {
    error_log("JW CRON ERROR: jw_daily_run_cron() not found – plugin zřejmě není aktivní.");
    die("ERROR: Function jw_daily_run_cron() not found\n");
}

try {
    jw_daily_run_cron();
    echo "OK\n";
} catch (Exception $e) {
    error_log("JW CRON ERROR: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
}
