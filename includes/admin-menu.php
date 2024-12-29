<?php

function librebooking_admin_menu_styles() {
    echo '
    <style>
        #adminmenu .toplevel_page_librebooking-settings img {
            width: 20px;
            height: 20px;
        }
    </style>
    ';
}
add_action('admin_head', 'librebooking_admin_menu_styles');

// Funktion til at oprette admin-menuen
function librebooking_create_admin_menu() {
    $icon_url = plugins_url('../images/favicon.png', __FILE__);
    add_menu_page(
        'LibreBooking Settings', 
        'LibreBooking', 
        'manage_options', 
        'librebooking-settings', 
        'librebooking_settings_page', 
        $icon_url
    );
}
add_action('admin_menu', 'librebooking_create_admin_menu');

// Funktion til at vise indstillingsside
function librebooking_settings_page() {
    ?>
    <div class="wrap">
        <h1>LibreBooking Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('librebooking_settings_group');
            do_settings_sections('librebooking-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Funktion til at registrere indstillinger
function librebooking_register_settings() {
    register_setting('librebooking_settings_group', 'librebooking_base_url');
    register_setting('librebooking_settings_group', 'librebooking_debug_mode');
    
    add_settings_section(
        'librebooking_settings_section', 
        'API Settings', 
        'librebooking_settings_section_callback', 
        'librebooking-settings'
    );

    add_settings_field(
        'librebooking_base_url', 
        'Base URL', 
        'librebooking_base_url_callback', 
        'librebooking-settings', 
        'librebooking_settings_section'
    );

    add_settings_field(
        'librebooking_debug_mode', 
        'Debug Mode', 
        'librebooking_debug_mode_callback', 
        'librebooking-settings', 
        'librebooking_settings_section'
    );

    // Indstillinger til at skjule felter
    $fields_to_hide = [
        'hide_invitees_field' => 'Hide Invitees Field',
        'hide_participants_field' => 'Hide Participants Field',
        'hide_participating_guests_field' => 'Hide Participating Guests Field',
        'hide_invited_guests_field' => 'Hide Invited Guests Field',
        'hide_recurrence_type_field' => 'Hide Recurrence Type Field',
        'hide_recurrence_interval_field' => 'Hide Recurrence Interval Field',
        'hide_recurrence_monthly_type_field' => 'Hide Recurrence Monthly Type Field',
        'hide_weekdays_field' => 'Hide Weekdays Field',
        'hide_repeat_termination_date_field' => 'Hide Repeat Termination Date Field',
        'hide_start_reminder_value_field' => 'Hide Start Reminder Value Field',
        'hide_start_reminder_interval_field' => 'Hide Start Reminder Interval Field',
        'hide_retry_parameters_field' => 'Hide Retry Parameters Field',
        'hide_description_field' => 'Hide Description Field',
        'hide_end_datetime_field' => 'Hide End DateTime Field',
        'hide_title_field' => 'Hide Title Field'
    ];

    foreach ($fields_to_hide as $option_name => $label) {
        register_setting('librebooking_settings_group', $option_name);
        add_settings_field(
            $option_name, 
            $label, 
            'librebooking_checkbox_callback', 
            'librebooking-settings', 
            'librebooking_settings_section', 
            ['name' => $option_name]
        );

        // Tilf�j ogs� felt for default value, hvis feltet skjules
        $default_value_name = $option_name . '_default_value';
        register_setting('librebooking_settings_group', $default_value_name);
        add_settings_field(
            $default_value_name,
            "$label Default Value",
            'librebooking_text_callback',
            'librebooking-settings',
            'librebooking_settings_section',
            ['name' => $default_value_name]
        );
    }
}
add_action('admin_init', 'librebooking_register_settings');

function librebooking_settings_section_callback() {
    echo 'Enter the settings for the LibreBooking API.';
}

function librebooking_base_url_callback() {
    $base_url = esc_attr(get_option('librebooking_base_url', ''));
    echo '<input type="url" name="librebooking_base_url" value="' . $base_url . '" class="regular-text" placeholder="https://your-base-url.com">';
    echo '<p class="description">Enter the base URL for the LibreBooking API.</p>';
}

function librebooking_debug_mode_callback() {
    $debug_mode = get_option('librebooking_debug_mode', false);
    echo '<input type="checkbox" name="librebooking_debug_mode" value="1"' . checked(1, $debug_mode, false) . '>';
    echo '<p class="description">Enable or disable debug mode. <strong>Warning:</strong> Enabling debug mode will display API request and response data on the website.</p>';
}

function librebooking_checkbox_callback($args) {
    $option = get_option($args['name']);
    echo '<input type="checkbox" name="' . $args['name'] . '" value="1"' . checked(1, $option, false) . '>';
}

function librebooking_text_callback($args) {
    $option = get_option($args['name'], '');
    echo '<input type="text" name="' . $args['name'] . '" value="' . esc_attr($option) . '" class="regular-text">';
}
