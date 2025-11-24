<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Večerní připomínka čtení Bible.
 * Používá stejného Telegram bota a chat_id jako denní text.
 *
 * Předpoklad:
 *  - jw_daily_send_telegram_message(string $text): void
 *    je definovaná v jw-daily-text-notifier.php (stejný plugin).
 */

/**
 * Hlavní funkce pro večerní cron – pošle jednoduchou zprávu.
 */
function jw_bible_run_cron(): void {
    if ( ! function_exists( 'jw_daily_send_telegram_message' ) ) {
        throw new RuntimeException( 'Funkce jw_daily_send_telegram_message() není k dispozici. Je aktivní JW Daily Text plugin?' );
    }

    $message = 'Přečti si Bibli.';

    jw_daily_send_telegram_message( $message );
}

/**
 * HTTP endpoint – pro ruční test:
 * https://mydomain.cz/?jw_bible_notify=1
 */
function jw_bible_notify_endpoint() {
    if ( ! isset( $_GET['jw_bible_notify'] ) ) {
        return;
    }

    header( 'Content-Type: text/plain; charset=UTF-8' );

    try {
        jw_bible_run_cron();
        echo "OK\nPřečti si Bibli.\n";

    } catch ( Exception $e ) {
        echo "ERROR: " . $e->getMessage();
    }

    exit;
}
add_action( 'init', 'jw_bible_notify_endpoint' );
