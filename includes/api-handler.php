<?php

add_action('plugins_loaded', function () {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        error_log('Session started globally on plugins_loaded');
    }
});

if (get_option('librebooking_debug_mode')) {
    error_log("Debug mode is enabled.");
}

function librebooking_update_session($user_id, $session_token) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['X-Booked-UserId'] = $user_id;
    $_SESSION['X-Booked-SessionToken'] = $session_token;
    $_SESSION['sessionExpires'] = time() + 3600; // 1 time

    error_log('Session updated: User ID - ' . $user_id);
}

// Funktion til at hente API-URL'er fra plugin-indstillingerne
function librebooking_get_api_urls() {
    $base_url = get_option('librebooking_base_url', '');
if (empty($base_url)) {
    error_log('Base URL is not configured. Please set it in the LibreBooking settings.');
    return [];
}
    $default_urls = [
        'login' => $base_url . 'Web/Services/index.php/Authentication/Authenticate',
        'logout' => $base_url . 'Web/Services/index.php/Authentication/SignOut',
        'bookings' => $base_url . 'Web/Services/index.php/Bookings/',
        'customers' => $base_url . 'Web/Services/index.php/Customers/',
        'groups' => $base_url . 'Web/Services/index.php/Groups/',
        'reservations' => $base_url . 'Web/Services/index.php/Reservations/',
        'attributes' => $base_url . 'Web/Services/index.php/Attributes/Category/',
        'accessories' => $base_url . 'Web/Services/index.php/Accessories/',
        'resources' => $base_url . 'Web/Services/index.php/Resources/',
        'schedules' => $base_url . 'Web/Services/index.php/Schedules/',
        'scheduleAvailability' => $base_url . 'Web/Services/index.php/ScheduleAvailability/',
        'icsFeeds' => $base_url . 'Web/Services/index.php/IcsFeeds/',
        'account' => $base_url . 'Web/Services/index.php/Accounts/',
    ];
    return $default_urls;
}

// Funktion til at tjekke, om session-token er udløbet
function librebooking_is_session_token_expired() {
    if (!isset($_SESSION['sessionExpires'])) {
        return true;
    }

    $expires = new DateTime($_SESSION['sessionExpires']);
    $now = new DateTime();

    return $now >= $expires;
}

function librebooking_ensure_session_token() {
    if (!isset($_SESSION['X-Booked-SessionToken']) || librebooking_is_session_token_expired()) {
        $username = get_option('librebooking_username');
        $password = get_option('librebooking_password');

        if (!$username || !$password) {
            return 'Login credentials missing';
        }

        return librebooking_login($username, $password);
    }
    return true;
}

// Funktion til at lave API-foresp�rgsler
function librebooking_api_request($endpoint, $method = 'GET', $body = [], $category_id = null) {
    $result = librebooking_ensure_session_token();
    if ($result !== true) {
        return ['error' => 'Session token issue: ' . $result];
    }

    $api_urls = librebooking_get_api_urls();
    $url = $api_urls[$endpoint] ?? '';

    if ($category_id) {
        $url .= $category_id;
    }

    $headers = [
        'origin' => home_url(),
        'x-requested-with' => 'XMLHttpRequest',
        'content-type' => 'application/json',
        'x-booked-sessiontoken' => $_SESSION['X-Booked-SessionToken'] ?? '',
        'x-booked-userid' => $_SESSION['X-Booked-UserId'] ?? '',
    ];

    $args = [
        'method'  => $method,
        'headers' => $headers,
    ];

    if ($body) {
        $args['body'] = json_encode($body);
    }

    $debug_html = '';

    if (get_option('librebooking_debug_mode')) {
        $debug_html .= "<pre>";
        $debug_html .= "API Request to: $url\n";
        $debug_html .= "Headers: " . htmlspecialchars(print_r($headers, true)) . "\n";
        if (!empty($body)) {
            $debug_html .= "Body: " . htmlspecialchars(print_r($body, true)) . "\n";
        }
        $debug_html .= "</pre>";
        echo $debug_html; // Output debug info to HTML
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        error_log('API Request Error: ' . $response->get_error_message());
        return ['error' => $response->get_error_message()];
    }

    $response_body = wp_remote_retrieve_body($response);

    if (get_option('librebooking_debug_mode')) {
        $debug_html .= "<pre>Raw API Response: " . htmlspecialchars($response_body) . "</pre>";
        echo $debug_html; // Output debug info to HTML
    }

    $decoded_response = json_decode($response_body, true);

    if (isset($decoded_response['links']) && is_array($decoded_response['links'])) {
        $parsed_links = [];
        foreach ($decoded_response['links'] as $link) {
            if (isset($link['title'], $link['href'])) {
                $parsed_links[$link['title']] = $link['href'];
            }
        }
        $decoded_response['parsed_links'] = $parsed_links;
    }

    if (get_option('librebooking_debug_mode')) {
        $debug_html .= "<pre>Decoded API Response: " . htmlspecialchars(print_r($decoded_response, true)) . "</pre>";
        echo $debug_html; // Output debug info to HTML
    }

    return $decoded_response;
}


// Funktion til at hente bookings
function librebooking_get_bookings() {
    return librebooking_api_request('bookings');
}

// Funktion til at hente kunder
function librebooking_get_customers() {
    return librebooking_api_request('customers');
}

// Funktion til at hente grupper
function librebooking_get_groups() {
    return librebooking_api_request('groups');
}

// Funktion til at hente reservationer
function librebooking_get_reservations() {
    return librebooking_api_request('reservations');
}

// Funktion til at hente attributter efter kategori
function librebooking_get_attributes($category_id) {
    return librebooking_api_request('attributes', 'GET', [], $category_id);
}

// Funktion til at hente accessories
function librebooking_get_accessories() {
    return librebooking_api_request('accessories');
}

// Funktion til at hente ressourcer
function librebooking_get_resources() {
    return librebooking_api_request('resources');
}

// Funktion til at hente schedules baseret p� scheduleId
function librebooking_get_schedule_periods($scheduleId) {
    return librebooking_api_request('schedules', 'GET', [], $scheduleId);
}

// Funktion til at hente alle schedules
function librebooking_get_schedules() {
    return librebooking_api_request('schedules');
}

// Funktion til at hente ICS feeds
function librebooking_get_ics_feeds() {
    return librebooking_api_request('icsFeeds');
}

// Funktion til at hente konto-oplysninger
function librebooking_get_account_info() {
    // Kontrollér, om bruger-ID er tilgængeligt
    $user_id = isset($_SESSION['X-Booked-UserId']) ? $_SESSION['X-Booked-UserId'] : '';
    if (empty($user_id)) {
        return 'User ID is missing.';
    }

    // Kald API-anmodning med bruger-ID som kategori
    $response = librebooking_api_request('account', 'GET', [], $user_id);

    // Håndtering af fejl i API-anmodning
    if (isset($response['error'])) {
        return $response['error'];
    }

    // Returnér API-respons
    return $response;
}




// Funktion til login
function librebooking_login($username, $password) {
    $response = wp_remote_post(librebooking_get_api_urls()['login'], [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode(['username' => $username, 'password' => $password]),
    ]);

    if (is_wp_error($response)) {
        error_log('Login error: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($body['sessionToken'], $body['userId'])) {
        librebooking_update_session($body['userId'], $body['sessionToken']);
        error_log('Login successful. User ID: ' . $body['userId']);
        return true;
    }

    error_log('Login failed. Response: ' . print_r($body, true));
    return false;
}

// Funktion til logout
function librebooking_logout() {
    $response = wp_remote_post(librebooking_get_api_urls()['logout'], [
        'body' => json_encode([
            'userId' => $_SESSION['X-Booked-UserId'] ?? '',
            'sessionToken' => $_SESSION['X-Booked-SessionToken'] ?? '',
        ]),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    if (!is_wp_error($response)) {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        return true;
    }

    error_log('Logout failed: ' . $response->get_error_message());
    return false;
}

// Funktion til at hente scheduleId baseret p� resourceId
function librebooking_get_schedule_id_by_resource($resourceId) {
    $resources_data = librebooking_get_resources();

    foreach ($resources_data['resources'] as $resource) {
        if ($resource['resourceId'] == $resourceId) {
            return $resource['scheduleId'];
        }
    }

    return null; // Returner null hvis ingen matchende resourceId findes
}

// Funktion til at generere dropdown-muligheder for tidspunkter baseret p� perioder
function librebooking_generate_time_options($periods) {
    $options = '';
    foreach ($periods as $period) {
        $startTime = esc_attr($period['startTime']);
        $endTime = esc_attr($period['endTime']);
        $options .= "<option value=\"$startTime\">$startTime</option>";
        $options .= "<option value=\"$endTime\">$endTime</option>";
    }
    return $options;
}

// Funktion til at hente accessories og returnere som formularfelter
function librebooking_get_accessories_fields() {
    $accessories_data = librebooking_get_accessories();

    if (!isset($accessories_data['accessories'])) {
        return 'No accessories available';
    }

    $fields = '';
    foreach ($accessories_data['accessories'] as $accessory) {
        $accessoryId = esc_attr($accessory['id']);
        $accessoryName = esc_html($accessory['name']);
        $associatedResourceCount = isset($accessory['associatedResourceCount']) ? intval($accessory['associatedResourceCount']) : 1;

        $fields .= "<label for=\"accessory_$accessoryId\">$accessoryName:</label><br>";
        $fields .= "<input type=\"number\" id=\"accessory_$accessoryId\" name=\"accessories[$accessoryId][quantity]\" value=\"0\" min=\"0\" max=\"$associatedResourceCount\" required><br><br>";
    }

    return $fields;
}

// Funktion til at hente resource details
function librebooking_get_resource_details($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching resource details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $resource_details = json_decode($response_body, true);

    if (isset($resource_details['description'])) {
        return $resource_details['description'];
    }

    return 'No details available.';
}

// Funktion til at hente reservation details
function librebooking_get_reservation_details($url) {
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
function librebooking_get_user_details($url) {
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
function librebooking_get_schedule_details($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return 'Error fetching schedule details.';
    }

    $response_body = wp_remote_retrieve_body($response);
    $schedule_details = json_decode($response_body, true);

    if (isset($schedule_details['name'])) {
        return $schedule_details['name'];
    }

    return 'No details available.';
}
// Funktion til at hente attributter og returnere som formularfelter
function librebooking_get_attributes_fields($resourceId, $category_id, $custom_attributes = []) {
    $attributes_data = librebooking_get_attributes($category_id); // Brug category_id parameteren

    if (!isset($attributes_data['attributes'])) {
        return 'No attributes available';
    }

    $fields = '';
    foreach ($attributes_data['attributes'] as $attribute) {
        $attributeId = esc_attr($attribute['id']);
        $attributeLabel = esc_html($attribute['label']);
        $attributeType = $attribute['type'];
        $attributeValue = isset($attribute['value']) ? esc_attr($attribute['value']) : '';
        $appliesToIds = $attribute['appliesToIds'];

        // Tjek om der er en værdi i custom_attributes
        foreach ($custom_attributes as $custom_attribute) {
            if ($custom_attribute['id'] == $attributeId) {
                $attributeValue = isset($custom_attribute['value']) ? esc_attr($custom_attribute['value']) : '';
                break;
            }
        }

        if (empty($appliesToIds) || in_array($resourceId, $appliesToIds)) {
            $fields .= "<label for=\"attribute_$attributeId\">$attributeLabel:</label><br>";

            switch ($attributeType) {
                case 1: // Text input
                    $fields .= "<input type=\"text\" id=\"attribute_$attributeId\" name=\"attributes[$attributeId]\" value=\"$attributeValue\" required><br><br>";
                    break;
                case 2: // Textarea
                    $fields .= "<textarea id=\"attribute_$attributeId\" name=\"attributes[$attributeId]\" required>$attributeValue</textarea><br><br>";
                    break;
                case 3: // Select dropdown
                    $fields .= "<select id=\"attribute_$attributeId\" name=\"attributes[$attributeId]\" required>";
                    foreach ($attribute['possibleValues'] as $value) {
                        $selected = ($value == $attributeValue) ? 'selected' : '';
                        $fields .= "<option value=\"" . esc_attr($value) . "\" $selected>" . esc_html($value) . "</option>";
                    }
                    $fields .= "</select><br><br>";
                    break;
                case 4: // Checkbox
                    $checked = ($attributeValue == '1') ? 'checked' : '';
                    $fields .= "<input type=\"checkbox\" id=\"attribute_$attributeId\" name=\"attributes[$attributeId]\" value=\"1\" $checked><br><br>";
                    break;
                default:
                    break;
            }
        }
    }

    return $fields;
}

// Funktion til at hente ressourcer og returnere som dropdown-muligheder
function librebooking_get_resources_options() {
    $resources_data = librebooking_get_resources();

    if (!isset($resources_data['resources'])) {
        return '<option value="">No resources available</option>';
    }

    $options = '';
    foreach ($resources_data['resources'] as $resource) {
        $resourceId = esc_attr($resource['resourceId']);
        $resourceName = esc_html($resource['name']);
        $options .= "<option value=\"$resourceId\">$resourceName</option>";
    }

    return $options;
}

// Funktion til at ændre kodeord
function librebooking_change_password($user_id, $current_password, $new_password) {
    $url = librebooking_get_api_urls()['account'] . $user_id . '/Password';
    $body = json_encode([
        'currentPassword' => $current_password,
        'newPassword' => $new_password,
    ]);

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => $body,
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $response_body = wp_remote_retrieve_body($response);
    return json_decode($response_body, true);
}

function librebooking_create_reservation($formData) {
    // Tjek om resourceId er sat og gyldig
    if (!isset($formData['resourceId']) || empty($formData['resourceId'])) {
        return ['errors' => ['Missing or invalid resourceId']];
    }

    $resourceId = intval($formData['resourceId']);

    // Konverterer attributfelter fra formdata
    $customAttributes = [];
    if (isset($formData['attributes'])) {
        foreach ($formData['attributes'] as $attributeId => $attributeValue) {
            $customAttributes[] = [
                'attributeId' => intval($attributeId),
                'attributeValue' => sanitize_text_field($attributeValue)
            ];
        }
    }

    // Konverterer accessories fra formdata og ekskluderer dem med quantityRequested = 0
    $accessories = [];
    if (isset($formData['accessories'])) {
        foreach ($formData['accessories'] as $accessoryId => $accessoryData) {
            $quantityRequested = intval($accessoryData['quantity']);
            if ($quantityRequested > 0) {
                $accessories[] = [
                    'accessoryId' => intval($accessoryId),
                    'quantityRequested' => $quantityRequested,
                ];
            }
        }
    }

    $reservation_data = [
        "accessories" => $accessories,
        "customAttributes" => $customAttributes,
        "description" => sanitize_text_field($formData['description']),
        "endDateTime" => sanitize_text_field($formData['endDateTime']),
        "invitees" => isset($formData['invitees']) ? array_map('intval', explode(',', $formData['invitees'])) : [],
        "participants" => isset($formData['participants']) ? array_map('intval', explode(',', $formData['participants'])) : [],
        "participatingGuests" => isset($formData['participatingGuests']) ? array_map('sanitize_email', explode(',', $formData['participatingGuests'])) : [],
        "invitedGuests" => isset($formData['invitedGuests']) ? array_map('sanitize_email', explode(',', $formData['invitedGuests'])) : [],
        "recurrenceRule" => [
            "type" => sanitize_text_field($formData['recurrenceType']),
            "interval" => intval($formData['recurrenceInterval']),
            "monthlyType" => sanitize_text_field($formData['recurrenceMonthlyType']),
            "weekdays" => isset($formData['weekdays']) ? array_map('intval', explode(',', $formData['weekdays'])) : [],
            "repeatTerminationDate" => sanitize_text_field($formData['repeatTerminationDate']),
        ],
        "resourceId" => $resourceId,
        "resources" => [$resourceId],
        "startDateTime" => sanitize_text_field($formData['startDateTime']),
        "title" => sanitize_text_field($formData['title']),
        "userId" => intval($formData['userId']),
        "startReminder" => [
            "value" => intval($formData['startReminderValue']),
            "interval" => sanitize_text_field($formData['startReminderInterval']),
        ],
        "endReminder" => null,
        "allowParticipation" => isset($formData['allowParticipation']) ? true : false,
        "retryParameters" => [],
        "termsAccepted" => isset($formData['termsAccepted']) ? true : false
    ];

    return librebooking_api_request('reservations', 'POST', $reservation_data);
}
