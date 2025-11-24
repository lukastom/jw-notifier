<?php
/**
 * Plugin Name: JW Notifier (fake loader)
 * Description: Vnitřní skripty jw-daily-text-notifier a jw-bible-reading-notifier. Žádné UI, jen interní endpointy.
 * Version:     0.1
 * Author:      Lukáš
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'JW_NOTIFIER_DIR', plugin_dir_path( __FILE__ ) );

// Načteme jednotlivé "skripty"
require_once JW_NOTIFIER_DIR . 'jw-daily-text-notifier.php';
require_once JW_NOTIFIER_DIR . 'jw-bible-reading-notifier.php';
