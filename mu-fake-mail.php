<?php
/**
 * Plugin Name: Gravity Local Bridge
 * Description: Налаштування ЛОКАЛЬНОГО стенду (Docker) для звʼязки WordPress → n8n:
 *   1) короткозамикає wp_mail() на "успіх" (немає SMTP), щоб CF7 ставив mail_sent
 *      і спрацьовував webhook;
 *   2) для запиту на внутрішній n8n вимикає reject_unsafe_urls — інакше WordPress
 *      блокує URL через приватний Docker-IP і нестандартний порт 5678
 *      (wp_http_validate_url дозволяє лише порти 80/443/8080).
 * ТІЛЬКИ для локального тесту. На проді не використовувати.
 */

// 1) Локально немає SMTP — вважаємо лист «надісланим».
add_filter( 'pre_wp_mail', '__return_true' );

// 2) Дозволити внутрішній виклик WordPress → n8n (приватний IP + порт 5678).
add_filter( 'http_request_args', function ( $args, $url ) {
    if ( false !== strpos( $url, '//n8n:' ) || false !== strpos( $url, '//gravity-n8n:' ) ) {
        $args['reject_unsafe_urls'] = false;
    }
    return $args;
}, 10, 2 );
