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
require_once plugin_dir_path(__FILE__) . 'includes/api-json-handler.php';
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

function librebooking_register_menus() {
    register_nav_menus(
        array(
            'librebooking-menu' => __('LibreBooking Menu'),
        )
    );
}
add_action('init', 'librebooking_register_menus');

// Tilføj menuen til frontend
function librebooking_display_menu() {
    wp_nav_menu(
        array(
            'theme_location' => 'librebooking-menu',
            'menu_id'        => 'librebooking-menu',
            'container'      => 'nav',
            'container_class'=> 'librebooking-menu-container',
        )
    );
}

// Tilføj en shortcode til at vise menuen
function librebooking_menu_shortcode() {
    error_log('LibreBooking menu shortcode called');
    ob_start();
    librebooking_display_menu();
    return ob_get_clean();
}
add_shortcode('librebooking_menu', 'librebooking_menu_shortcode');

// Tilføj CSS for at style menuen
function librebooking_menu_styles() {
    ?>
    <style>
        .librebooking-menu-container {
            background-color: #f8f8f8;
            padding: 10px;
        }

        #librebooking-menu {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: space-around;
        }

        #librebooking-menu li {
            margin: 0 10px;
        }

        #librebooking-menu li a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }

        #librebooking-menu li a:hover {
            color: #0073aa;
        }
    </style>
    <?php
}
add_action('wp_head', 'librebooking_menu_styles');

function librebooking_create_custom_pages() {
    // Definer en liste over sider med deres respektive titler, indhold og skabeloner
    $pages = [
        [
            'title' => 'Librebooking Reservation',
            'content' => '<!-- wp:shortcode -->[librebooking_custom_menu][librebooking_reservations][librebooking_auth]<!-- /wp:shortcode -->',
            'template' => 'templates/booking.php',
        ],
        [
            'title' => 'Librebooking Profile',
            'content' => '<!-- wp:shortcode -->[librebooking_custom_menu][librebooking_account_info][librebooking_auth]<!-- /wp:shortcode -->',
            'template' => 'templates/reservation.php',
        ],
        [
            'title' => 'Librebooking Reservation Formular',
            'content' => '<!-- wp:shortcode -->[librebooking_custom_menu][librebooking_reservation_form][librebooking_auth]<!-- /wp:shortcode -->',
            'template' => 'templates/reservation.php',
        ],
        [
            'title' => 'Librebooking User Registration',
            'content' => '<!-- wp:shortcode -->[librebooking_custom_menu][librebooking_user_registration]<!-- /wp:shortcode -->',
            'template' => 'templates/reservation.php',
        ],
        // Tilføj flere sider her
    ];

    // Iterer gennem listen og opret hver side
    foreach ($pages as $page) {
        $page_check = get_page_by_title($page['title']);
        if (!isset($page_check->ID)) {
            // Opret siden
            wp_insert_post(array(
                'post_title'    => $page['title'],
                'post_content'  => $page['content'],
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'meta_input'    => array(
                    '_wp_page_template' => $page['template'],
                ),
            ));
        }
    }
}
register_activation_hook(__FILE__, 'librebooking_create_custom_pages');

// Kald funktionen direkte ved init
add_action('init', 'librebooking_create_custom_pages');