<?php
/**
 * CRON ENTRY POINT – BIBLE READING REMINDER
 * Spouští hosting cron (21:00).
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

if ( ! function_exists( 'jw_bible_run_cron' ) ) {
    error_log( "JW BIBLE CRON ERROR: jw_bible_run_cron() not found – plugin zřejmě není aktivní." );
    die( "ERROR: Function jw_bible_run_cron() not found\n" );
}

try {
    jw_bible_run_cron();
    echo "OK\n";
} catch ( Exception $e ) {
    error_log( "JW BIBLE CRON ERROR: " . $e->getMessage() );
    echo "ERROR: " . $e->getMessage() . "\n";
}
