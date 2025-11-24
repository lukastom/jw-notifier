<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KONFIGURACE TELEGRAMU
 */

function jw_daily_get_telegram_bot_token(): string {
    // DOPLŇ svůj bot token z BotFather
    return 'ADD_YOUR_BOT_TOKEN_HERE';
}

function jw_daily_get_telegram_chat_ids(): array {
    // DOPLŇ svá chat_id (může jich být víc)
    return [
        ADD_YOUR_CHAT_ID_HERE, // tvoje chat_id
    ];
}

/**
 * Stáhne HTML pomocí WordPress HTTP API (wp_remote_get).
 */
function jw_daily_fetch_html( string $url ): string {
    $response = wp_remote_get( $url, [
        'timeout'     => 20,
        'redirection' => 5,
        'user-agent'  => 'Mozilla/5.0 (compatible; JW-Daily-Text-Notifier/1.0; +https://mydomain.cz)',
        'sslverify'   => false,
    ] );

    if ( is_wp_error( $response ) ) {
        throw new RuntimeException( 'Chyba při stahování: ' . $response->get_error_message() );
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code < 200 || $code >= 300 ) {
        throw new RuntimeException( 'HTTP chyba: ' . $code );
    }

    $body = wp_remote_retrieve_body( $response );
    if ( ! is_string( $body ) || $body === '' ) {
        throw new RuntimeException( 'Prázdná odpověď z ' . $url );
    }

    return mb_convert_encoding( $body, 'HTML-ENTITIES', 'UTF-8' );
}

/**
 * Vrací denní text jako asociativní pole: date, theme, body, link.
 * Používá datovou URL ve tvaru /YYYY/MM/DD.
 * Kvůli offsetu na wol.jw.org používá "zítra" (today + 1 day).
 */
function jw_daily_get_daily_text(): array {
    $base = 'https://wol.jw.org';

    // "Dnes" v Europe/Prague (nebo WP timezone, pokud je k dispozici)
    if ( function_exists( 'wp_timezone' ) ) {
        $tz  = wp_timezone();
        $now = new DateTime( 'now', $tz );
    } else {
        $tz  = new DateTimeZone( 'Europe/Prague' );
        $now = new DateTime( 'now', $tz );
    }

    // POSUN O +1 DEN – wol stránka pro náš dnešek je pod zítřejší URL
    $target = clone $now;
    $target->modify( '+1 day' );

    $year  = (int) $target->format( 'Y' );
    $month = (int) $target->format( 'm' );
    $day   = (int) $target->format( 'd' );

    // např. https://wol.jw.org/cs/wol/h/r29/lp-b/2025/11/25
    $url = sprintf(
        '%s/cs/wol/h/r29/lp-b/%04d/%02d/%02d',
        rtrim( $base, '/' ),
        $year,
        $month,
        $day
    );

    $html = jw_daily_fetch_html( $url );

    $dom = new DOMDocument();
    libxml_use_internal_errors( true );
    $dom->loadHTML( $html );
    libxml_clear_errors();

    $xpath = new DOMXPath( $dom );

    $container = $xpath->query( '//div[contains(@class,"tabContent") and contains(@class,"active")]' )->item( 0 );
    if ( ! $container ) {
        $container = $xpath->query( '//div[contains(@class,"tabContent")]' )->item( 0 );
    }

    if ( ! $container ) {
        throw new RuntimeException( 'Nenalezen blok denního textu (div.tabContent[.active]).' );
    }

    $dateNode = $xpath->query( './/header/h2', $container )->item( 0 );
    $dateText = $dateNode ? trim( $dateNode->textContent ) : '';

    $themeNode = $xpath->query( './/p[contains(@class,"themeScrp")]', $container )->item( 0 );
    $themeText = $themeNode ? trim( preg_replace( '/\s+/', ' ', $themeNode->textContent ) ) : '';

    $bodyPieces = [];
    $paras      = $xpath->query( './/div[contains(@class,"bodyTxt")]/p', $container );

    foreach ( $paras as $p ) {
        $t = trim( preg_replace( '/\s+/', ' ', $p->textContent ) );
        if ( $t !== '' ) {
            $bodyPieces[] = $t;
        }
    }

    $bodyText = implode( "\n\n", $bodyPieces );

    $linkNode = $xpath->query( './/a[contains(text(), "Denně zkoumejme Písmo")]', $container )->item( 0 );
    $link     = '';

    if ( $linkNode ) {
        $href = $linkNode->getAttribute( 'href' );
        if ( $href ) {
            if ( strpos( $href, 'http' ) === 0 ) {
                $link = $href;
            } else {
                $link = rtrim( $base, '/' ) . $href;
            }
        }
    }

    return [
        'date'  => $dateText,
        'theme' => $themeText,
        'body'  => $bodyText,
        'link'  => $link,
    ];
}

/**
 * Složí text zprávy pro Telegram – s prefixem "Denní text: ".
 */
function jw_daily_build_message( array $data ): string {
    $parts = [];

    if ( ! empty( $data['date'] ) ) {
        $parts[] = 'Denní text: ' . $data['date'];
    }

    if ( ! empty( $data['theme'] ) ) {
        $parts[] = $data['theme'];
    }

    if ( ! empty( $data['body'] ) ) {
        $parts[] = $data['body'];
    }

    if ( ! empty( $data['link'] ) ) {
        $parts[] = $data['link'];
    }

    return implode( "\n\n", $parts );
}

/**
 * Pošle daný text na všechny chat_id přes Telegram Bot API.
 */
function jw_daily_send_telegram_message( string $text ): void {
    $token    = jw_daily_get_telegram_bot_token();
    $chat_ids = jw_daily_get_telegram_chat_ids();

    if ( ! $token || $token === 'PASTE_YOUR_TELEGRAM_BOT_TOKEN_HERE' ) {
        throw new RuntimeException( 'Není nastaven TELEGRAM BOT TOKEN v jw_daily_get_telegram_bot_token().' );
    }

    if ( empty( $chat_ids ) ) {
        throw new RuntimeException( 'Seznam TELEGRAM chat_id je prázdný v jw_daily_get_telegram_chat_ids().' );
    }

    $url = 'https://api.telegram.org/bot' . $token . '/sendMessage';

    foreach ( $chat_ids as $chat_id ) {
        wp_remote_post( $url, [
            'timeout' => 15,
            'body'    => [
                'chat_id' => $chat_id,
                'text'    => $text,
            ],
        ] );
    }
}

/**
 * Hlavní funkce pro cron – scrapne a odešle zprávu.
 * Tu budeme volat jak z HTTP endpointu, tak z externího cron PHP.
 */
function jw_daily_run_cron(): void {
    $data    = jw_daily_get_daily_text();
    $message = jw_daily_build_message( $data );
    jw_daily_send_telegram_message( $message );
}

/**
 * HTTP endpoint – pro ruční test:
 * https://mydomain.cz/?jw_daily_notify=1
 */
function jw_daily_notify_endpoint() {
    if ( ! isset( $_GET['jw_daily_notify'] ) ) {
        return;
    }

    header( 'Content-Type: text/plain; charset=UTF-8' );

    try {
        jw_daily_run_cron();
        echo "OK\n";

    } catch ( Exception $e ) {
        echo "ERROR: " . $e->getMessage();
    }

    exit;
}
add_action( 'init', 'jw_daily_notify_endpoint' );
