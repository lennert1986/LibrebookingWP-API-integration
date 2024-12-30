<?php
// Funktion til at hente user account details
function JSON_get_user_account($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching user account details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $user_account_details = json_decode($response_body, true);

    if (isset($user_account_details['firstName']) && isset($user_account_details['lastName'])) {
        return $user_account_details['firstName'] . ' ' . $user_account_details['lastName'];
    }

    return 'No details available.';
}

// Funktion til at opdatere user account
function JSON_update_user_account($url, $data) {
    $response = wp_remote_post($url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return 'Error updating user account.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at opdatere password
function JSON_update_password($url, $data) {
    $response = wp_remote_post($url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return 'Error updating password.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at hente custom attribute details
function JSON_get_custom_attribute($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching custom attribute details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $custom_attribute_details = json_decode($response_body, true);

    if (isset($custom_attribute_details['label'])) {
        return $custom_attribute_details['label'] . ': ' . $custom_attribute_details['value'];
    }

    return 'No details available.';
}

// Funktion til at hente alle custom attributes
function JSON_all_custom_attributes($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching all custom attributes.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at opdatere custom attribute
function JSON_update_custom_attribute($url, $data) {
    $response = wp_remote_post($url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return 'Error updating custom attribute.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at slette custom attribute
function JSON_delete_custom_attribute($url) {
    $response = wp_remote_request($url, [
        'method' => 'DELETE',
    ]);

    if (is_wp_error($response)) {
        return 'Error deleting custom attribute.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at hente resource details
function JSON_get_resource($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching resource details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $resource_details = json_decode($response_body, true);

    if (isset($resource_details['name'])) {
        return $resource_details['name'];
    }

    return 'No details available.';
}

// Funktion til at hente reservation details
function JSON_get_reservation($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching reservation details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $reservation_details = json_decode($response_body, true);

    if (isset($reservation_details['description'])) {
        return $reservation_details['description'];
    }

    return 'No details available.';
}

// Funktion til at hente user details
function JSON_get_user($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching user details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $user_details = json_decode($response_body, true);

    if (isset($user_details['firstName']) && isset($user_details['lastName'])) {
        return $user_details['firstName'] . ' ' . $user_details['lastName'];
    }

    return 'No details available.';
}

// Funktion til at hente schedule details
function JSON_get_schedule($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching schedule details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $schedule_details = json_decode($response_body, true);

    if (isset($schedule_details['name'])) {
        return $schedule_details['name'];
    }

    // Tilføj flere detaljer om nødvendigt
    $details = 'Name: ' . $schedule_details['name'] . '<br>';
    $details .= 'Timezone: ' . $schedule_details['timezone'] . '<br>';
    $details .= 'Days Visible: ' . $schedule_details['daysVisible'] . '<br>';
    $details .= 'Availability Start: ' . $schedule_details['availabilityStart'] . '<br>';
    $details .= 'Availability End: ' . $schedule_details['availabilityEnd'] . '<br>';
    $details .= 'Max Resources Per Reservation: ' . $schedule_details['maxResourcesPerReservation'] . '<br>';
    $details .= 'Total Concurrent Reservations Allowed: ' . $schedule_details['totalConcurrentReservationsAllowed'] . '<br>';

    return $details;
}

// Funktion til at hente accessory details
function JSON_get_accessory($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching accessory details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $accessory_details = json_decode($response_body, true);

    if (isset($accessory_details['name'])) {
        return $accessory_details['name'];
    }

    return 'No details available.';
}

// Funktion til at opdatere accessory
function JSON_update_accessory($url, $data) {
    $response = wp_remote_post($url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return 'Error updating accessory.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at slette accessory
function JSON_delete_accessory($url) {
    $response = wp_remote_request($url, [
        'method' => 'DELETE',
    ]);

    if (is_wp_error($response)) {
        return 'Error deleting accessory.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at hente ICS feed details
function JSON_get_ics_feed($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching ICS feed details.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at hente status details
function JSON_get_status($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching status details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $status_details = json_decode($response_body, true);

    if (isset($status_details['status'])) {
        return $status_details['status'];
    }

    return 'No details available.';
}

// Funktion til at hente group details
function JSON_get_group($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching group details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $group_details = json_decode($response_body, true);

    if (isset($group_details['name'])) {
        return $group_details['name'];
    }

    return 'No details available.';
}

// Funktion til at hente booking details
function JSON_get_booking($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching booking details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $booking_details = json_decode($response_body, true);

    if (isset($booking_details['description'])) {
        return $booking_details['description'];
    }

    return 'No details available.';
}

// Funktion til at hente category details
function JSON_get_category($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching category details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $category_details = json_decode($response_body, true);

    if (isset($category_details['name'])) {
        return $category_details['name'];
    }

    return 'No details available.';
}

// Funktion til at hente attribute details
function JSON_get_attribute($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching attribute details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $attribute_details = json_decode($response_body, true);

    if (isset($attribute_details['label'])) {
        return $attribute_details['label'];
    }

    return 'No details available.';
}

// Funktion til at oprette attribute
function JSON_create_attribute($url, $data) {
    $response = wp_remote_post($url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return 'Error creating attribute.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at opdatere attribute
function JSON_update_attribute($url, $data) {
    $response = wp_remote_post($url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return 'Error updating attribute.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at slette attribute
function JSON_delete_attribute($url) {
    $response = wp_remote_request($url, [
        'method' => 'DELETE',
    ]);

    if (is_wp_error($response)) {
        return 'Error deleting attribute.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at hente available schedules
function JSON_get_available_schedules($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching available schedules.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at hente invited guest details
function JSON_get_invited_guest($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching invited guest details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $invited_guest_details = json_decode($response_body, true);

    if (isset($invited_guest_details['name'])) {
        return $invited_guest_details['name'];
    }

    return 'No details available.';
}

// Funktion til at opdatere invited guest
function JSON_update_invited_guest($url, $data) {
    $response = wp_remote_post($url, [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return 'Error updating invited guest.';
    }

    return wp_remote_retrieve_body($response);
}

// Funktion til at hente participant details
function JSON_get_participant($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching participant details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $participant_details = json_decode($response_body, true);

    if (isset($participant_details['name'])) {
        return $participant_details['name'];
    }

    return 'No details available.';
}

// Funktion til at hente retry parameters
function JSON_get_retry_parameters($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching retry parameters.';
    }

    return wp_remote_retrieve_body($response);
}