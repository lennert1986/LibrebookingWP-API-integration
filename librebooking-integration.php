<?php
/**
 * Plugin Name: LibreBooking Integration
 * Description: A WordPress plugin to integrate LibreBooking with your site.
 * Version: 0.2
 * Author: Nicklas Brander
 */

// Inkluderer de n�dvendige filer

/**add_action('init', function () {
*    if (session_status() === PHP_SESSION_NONE) {
*        session_start();
*        error_log('Session started');
*    }
*});
*/
function librebooking_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'librebooking_start_session');

require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/api-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';

// Aktiveringshook
function librebooking_activate() {
    // Kode til at k�re, n�r pluginet aktiveres
}
register_activation_hook(__FILE__, 'librebooking_activate');

// Deaktiveringshook
function librebooking_deactivate() {
    // Kode til at k�re, n�r pluginet deaktiveres
}
register_deactivation_hook(__FILE__, 'librebooking_deactivate');

add_action('init', function () {
    load_plugin_textdomain('librebooking', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

add_action('init', function () {
    if (session_status() === PHP_SESSION_NONE) {
        error_log('Starting session');
        session_start();
    } else {
        error_log('Session already started');
    }
});

add_action('wp_loaded', function () {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
        error_log('Session started on wp_loaded');
    }
});