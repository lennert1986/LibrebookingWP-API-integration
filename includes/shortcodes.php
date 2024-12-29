<?php
session_start();
// Shortcode-funktion til at vise ressourcer
function librebooking_resources_shortcode() {
    if (!isset($_SESSION['X-Booked-SessionToken'])) {
        return 'You must be logged in to view resources.';
    }

    $resources = librebooking_get_resources();
    if (is_array($resources) && isset($resources['resources'])) {
        ob_start();
        echo '<h2>Resources</h2>';
        echo '<ul>';
        foreach ($resources['resources'] as $resource) {
            echo '<li>';
            echo '<h3>' . esc_html($resource['name']) . '</h3>';
            echo '<p><strong>Location:</strong> ' . esc_html($resource['location']) . '</p>';
            echo '<p><strong>Contact:</strong> ' . esc_html($resource['contact']) . '</p>';
            echo '<p><strong>Description:</strong> ' . nl2br(esc_html($resource['description'])) . '</p>';
            echo '<p><strong>Max Participants:</strong> ' . intval($resource['maxParticipants']) . '</p>';
            echo '<p><strong>Requires Approval:</strong> ' . ($resource['requiresApproval'] ? 'Yes' : 'No') . '</p>';

            // Hent og vis custom attributes
            if (isset($resource['customAttributes']) && is_array($resource['customAttributes'])) {
                echo '<h4>Custom Attributes</h4>';
                echo '<ul>';
                foreach ($resource['customAttributes'] as $attribute) {
                    echo '<li><strong>' . esc_html($attribute['label']) . ':</strong> ' . esc_html($attribute['value']) . '</li>';
                }
                echo '</ul>';
            }

            // Hent og vis resource details via get_resource link
            if (isset($resource['links'])) {
                foreach ($resource['links'] as $link) {
                    if ($link['title'] === 'get_resource') {
                        $resource_details = librebooking_get_resource_details($link['href']);
                        if ($resource_details) {
                            echo '<h4>Resource Details</h4>';
                            echo '<p>' . esc_html($resource_details) . '</p>';
                        }
                    }
                }
            }

            echo '</li>';
        }
        echo '</ul>';
        return ob_get_clean();
    } else {
        return 'No resources found or an error occurred.';
    }
}
add_shortcode('librebooking_resources', 'librebooking_resources_shortcode');

// Shortcode-funktion til at vise ICS feeds
function librebooking_ics_feeds_shortcode() {
    if (!isset($_SESSION['X-Booked-SessionToken'])) {
        return 'You must be logged in to view ICS feeds.';
    }

    $ics_feeds = librebooking_get_ics_feeds();
    if (is_array($ics_feeds)) {
        ob_start();
        echo '<ul>';
        foreach ($ics_feeds as $feed) {
            if (isset($feed['name'])) {
                echo '<li>' . esc_html($feed['name']) . '</li>';
            }
        }
        echo '</ul>';
        return ob_get_clean();
    } else {
        return 'No ICS feeds found or an error occurred.';
    }
}
add_shortcode('librebooking_ics_feeds', 'librebooking_ics_feeds_shortcode');

// Shortcode-funktion til login
function librebooking_login_shortcode($atts) {
    if (isset($_SESSION['X-Booked-SessionToken'])) {
        return '<p class="success">You are already logged in.</p>';
    }

    if (isset($_POST['librebooking_login'])) {
        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);

        if (librebooking_login($username, $password)) {
            return;
        } else {
            return '<p class="error">Login failed. Please check your credentials.</p>';
        }
    }

    ob_start();
    ?>
    <form method="post">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>
        <input type="submit" name="librebooking_login" value="Login">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('librebooking_login', 'librebooking_login_shortcode');

// Shortcode-funktion til logout
function librebooking_auth_shortcode($atts) {
    // Hvis sessionen ikke er aktiv, er brugeren ikke logget ind
    if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['X-Booked-SessionToken']) || librebooking_is_session_token_expired()) {
        if (isset($_POST['librebooking_login'])) {
            $username = sanitize_text_field($_POST['username']);
            $password = sanitize_text_field($_POST['password']);
            
            if (librebooking_login($username, $password)) {
                ob_start();
                echo 'Login successful.';
                ?>
                <form method="post">
                    <input type="submit" name="librebooking_logout" value="Logout">
                </form>
                <?php
                return ob_get_clean();
            } else {
                return 'Login failed. Please check your credentials.';
            }
        }

        ob_start();
        ?>
        <form method="post">
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username" required><br><br>
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>
            <input type="submit" name="librebooking_login" value="Login">
        </form>
        <?php
        return ob_get_clean();
    }

    // Hvis brugeren allerede er logget ind
    if (isset($_POST['librebooking_logout'])) {
        if (librebooking_logout()) {
            ob_start();
            echo 'You have been logged out.';
            ?>
            <form method="post">
                <label for="username">Username:</label><br>
                <input type="text" id="username" name="username" required><br><br>
                <label for="password">Password:</label><br>
                <input type="password" id="password" name="password" required><br><br>
                <input type="submit" name="librebooking_login" value="Login">
            </form>
            <?php
            return ob_get_clean();
        } else {
            return 'Logout failed. Please try again.';
        }
    }

    ob_start();
    echo 'Login successful.';
    ?>
    <form method="post">
        <input type="submit" name="librebooking_logout" value="Logout">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('librebooking_auth', 'librebooking_auth_shortcode');


// Funktion til at hente ressourcer og generere HTML for dropdown
function librebooking_get_resources_dropdown() {
    $resources_data = librebooking_get_resources();
    $resources_options = '';
    $resource_count = 0;
    $resourceId = null;

    if (isset($resources_data['resources']) && is_array($resources_data['resources'])) {
        $resource_count = count($resources_data['resources']);

        foreach ($resources_data['resources'] as $resource) {
            $resourceId = esc_attr($resource['resourceId']);
            $resourceName = esc_html($resource['name']);
            $resources_options .= "<option value=\"$resourceId\">$resourceName</option>";
        }
    } else {
        // Håndter tilfælde hvor ressourcer ikke er tilgængelige
        return ['error' => 'No resources available or an error occurred.'];
    }

    return [
        'options' => $resources_options,
        'count' => $resource_count,
        'resourceId' => $resourceId
    ];
}

// Shortcode-funktion til at vise reservationsformularen
function librebooking_reservation_form_shortcode($atts) {
    // Tjek om brugeren er logget ind
    if (!isset($_SESSION['X-Booked-SessionToken'])) {
        return 'You must be logged in to make a reservation.';
    }

    // Hvis formularen er indsendt
    if (isset($_POST['librebooking_reservation_submit'])) {
        $response = librebooking_create_reservation($_POST);
        
        // Tjek for fejl i svaret
        if (is_array($response) && isset($response['errors'])) {
            ob_start();
            echo '<ul class="librebooking-errors">';
            foreach ($response['errors'] as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            return ob_get_clean();
        }
        
        if (is_array($response) && isset($response['referenceNumber'])) {
            return 'Reservation successful! Reference Number: ' . esc_html($response['referenceNumber']);
        } else {
            return 'Reservation failed: ' . esc_html($response);
        }
    }

    // Hent ressourcerne til dropdown
    $resources_data = librebooking_get_resources_dropdown();
    if (isset($resources_data['error'])) {
        return $resources_data['error'];
    }

    $resources_options = $resources_data['options'];
    $resource_count = $resources_data['count'];
    $resourceId = $resources_data['resourceId'];

    // Skjul dropdown, hvis der kun er én ressource
    $hide_resource_field = $resource_count === 1;

    // Hent accessories felterne
    $accessories_fields = librebooking_get_accessories_fields();

    // Hent attributfelterne for en given kategori (f.eks. kategori 1)
    $category_id = 1; // Du kan ændre dette til den ønskede kategori
    $attributes_fields = librebooking_get_attributes_fields($resourceId, $category_id);

    // Vis formularen
    ob_start();
    ?>
    <form method="post" id="librebooking-reservation-form">
        <label for="description">Description:</label><br>
        <input type="text" id="description" name="description" required><br><br>

        <label for="startDateTime">Start Date and Time:</label><br>
        <input type="datetime-local" id="startDateTime" name="startDateTime" required><br><br>

        <label for="endDateTime">End Date and Time:</label><br>
        <input type="datetime-local" id="endDateTime" name="endDateTime" required><br><br>

        <label for="title">Title:</label><br>
        <input type="text" id="title" name="title" required><br><br>

        <?php if (!$hide_resource_field): ?>
        <label for="resourceId">Resource:</label><br>
        <select id="resourceId" name="resourceId" required>
            <?php echo $resources_options; ?>
        </select><br><br>
        <?php else: ?>
            <input type="hidden" name="resourceId" value="<?php echo esc_attr($resources_data['resourceId']); ?>">
        <?php endif; ?>

        <div id="attributes-fields">
            <?php echo $attributes_fields; ?>
        </div>

        <div id="accessories-fields">
            <h3>Accessories</h3>
            <?php echo $accessories_fields; ?>
        </div>

        <input type="submit" name="librebooking_reservation_submit" value="Submit Reservation">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('librebooking_reservation_form', 'librebooking_reservation_form_shortcode');

function librebooking_schedules_shortcode() {
    if (!isset($_SESSION['X-Booked-SessionToken'])) {
        return 'You must be logged in to view schedules.';
    }

    $schedules = librebooking_get_schedules();
    if (is_array($schedules)) {
        ob_start();
        echo '<ul>';
        foreach ($schedules as $schedule) {
            if (isset($schedule['name'])) {
                echo '<li>' . esc_html($schedule['name']) . '</li>';
            }
        }
        echo '</ul>';
        return ob_get_clean();
    } else {
        return 'No schedules found or an error occurred.';
    }
}
add_shortcode('librebooking_schedules', 'librebooking_schedules_shortcode');

// Shortcode-funktion til at vise konto-oplysninger
// Shortcode-funktion til at vise konto-oplysninger
function librebooking_account_info_shortcode() {
    // Hent konto-oplysninger via API
    $account_info = librebooking_get_account_info();

    // Kontrollér, om der er opstået en fejl
    if (is_string($account_info)) {
        return '<p>Fejl: ' . esc_html($account_info) . '</p>';
    }

    if (!is_array($account_info)) {
        return '<p>Kunne ikke hente konto-oplysninger. Kontroller API-responsen.</p>';
    }

    // Håndter formularindsendelse for at ændre kodeord
    if (isset($_POST['librebooking_change_password_submit'])) {
        $current_password = sanitize_text_field($_POST['currentPassword']);
        $new_password = sanitize_text_field($_POST['newPassword']);
        $user_id = isset($_SESSION['X-Booked-UserId']) ? $_SESSION['X-Booked-UserId'] : '';

        $response = librebooking_change_password($user_id, $current_password, $new_password);

        if (is_wp_error($response)) {
            echo '<p>Fejl: ' . esc_html($response->get_error_message()) . '</p>';
        } else {
            echo '<p>Kodeord ændret succesfuldt.</p>';
        }
    }

    // Håndter formularindsendelse for at opdatere konto-oplysninger
    if (isset($_POST['librebooking_update_account_submit'])) {
        $user_id = isset($_SESSION['X-Booked-UserId']) ? $_SESSION['X-Booked-UserId'] : '';
        $update_url = '';
        foreach ($account_info['parsed_links'] as $link) {
            if ($link['title'] === 'update_user_account') {
                $update_url = str_replace(':userId', $user_id, $link['href']);
                break;
            }
        }

        $update_data = [
            'firstName' => sanitize_text_field($_POST['firstName']),
            'lastName' => sanitize_text_field($_POST['lastName']),
            'emailAddress' => sanitize_email($_POST['emailAddress']),
            'userName' => sanitize_text_field($_POST['userName']),
            'language' => sanitize_text_field($_POST['language']),
            'timezone' => sanitize_text_field($_POST['timezone']),
            'phone' => sanitize_text_field($_POST['phone']),
            'organization' => sanitize_text_field($_POST['organization']),
            'position' => sanitize_text_field($_POST['position']),
            'customAttributes' => []
        ];

        foreach ($_POST['customAttributes'] as $attributeId => $attributeValue) {
            $update_data['customAttributes'][] = [
                'attributeId' => intval($attributeId),
                'attributeValue' => sanitize_text_field($attributeValue)
            ];
        }

        $response = librebooking_update_account_info($update_url, $update_data);

        if (is_wp_error($response)) {
            echo '<p>Fejl: ' . esc_html($response->get_error_message()) . '</p>';
        } else {
            echo '<p>Konto-oplysninger opdateret succesfuldt.</p>';
        }
    }

    // Hent customAttributes
    $custom_attributes = isset($account_info['customAttributes']) ? $account_info['customAttributes'] : [];

    // Hent attributfelterne for en given kategori (f.eks. kategori 2)
    $category_id = 2; // Du kan ændre dette til den ønskede kategori
    $resourceId = isset($_SESSION['X-Booked-UserId']) ? $_SESSION['X-Booked-UserId'] : '';
    $attributes_fields = librebooking_get_attributes_fields($resourceId, $category_id, $custom_attributes);

    // Generér HTML for at vise konto-oplysninger
    ob_start();
    ?>
    <h2>Kontooplysninger</h2>
    <ul>
    <?php
    foreach ($account_info as $key => $value) {
        if ($key === 'parsed_links' && is_array($value)) {
            echo '<li>Links:<ul>';
            foreach ($value as $title => $href) {
                // Erstat :userId med det korrekte bruger-ID fra sessionen
                if (strpos($href, ':userId') !== false) {
                    $user_id = isset($_SESSION['X-Booked-UserId']) ? $_SESSION['X-Booked-UserId'] : '';
                    $href = str_replace(':userId', $user_id, $href);
                }
                echo '<li>' . esc_html($title) . ': <a href="' . esc_url($href) . '" target="_blank">' . esc_html($href) . '</a></li>';
            }
            echo '</ul></li>';
        } elseif ($key === 'customAttributes' && is_array($value)) {
            echo '<li>Custom Attributes:<ul>';
            foreach ($value as $attribute) {
                if (isset($attribute['id'])) {
                    $attribute_label = esc_html($attribute['label']);
                    $attribute_value = esc_html($attribute['value']);
                    echo '<li>' . $attribute_label . ': ' . $attribute_value . '</li>';
                }
            }
            echo '</ul></li>';
        } else {
            echo '<li>' . esc_html($key) . ': ' . esc_html(is_array($value) ? json_encode($value) : $value) . '</li>';
        }
    }
    ?>
    </ul>



    <form method="post" id="librebooking-update-account-form">
        <label for="firstName">First Name:</label><br>
        <input type="text" id="firstName" name="firstName" value="<?php echo esc_attr($account_info['firstName']); ?>" required><br><br>

        <label for="lastName">Last Name:</label><br>
        <input type="text" id="lastName" name="lastName" value="<?php echo esc_attr($account_info['lastName']); ?>" required><br><br>

        <label for="emailAddress">Email Address:</label><br>
        <input type="email" id="emailAddress" name="emailAddress" value="<?php echo esc_attr($account_info['emailAddress']); ?>" required><br><br>

        <label for="userName">Username:</label><br>
        <input type="text" id="userName" name="userName" value="<?php echo esc_attr($account_info['userName']); ?>" required><br><br>

        <label for="language">Language:</label><br>
        <input type="text" id="language" name="language" value="<?php echo esc_attr($account_info['language']); ?>" required><br><br>

        <label for="timezone">Timezone:</label><br>
        <input type="text" id="timezone" name="timezone" value="<?php echo esc_attr($account_info['timezone']); ?>" required><br><br>

        <label for="phone">Phone:</label><br>
        <input type="text" id="phone" name="phone" value="<?php echo esc_attr($account_info['phone']); ?>" required><br><br>

        <label for="organization">Organization:</label><br>
        <input type="text" id="organization" name="organization" value="<?php echo esc_attr($account_info['organization']); ?>" required><br><br>

        <label for="position">Position:</label><br>
        <input type="text" id="position" name="position" value="<?php echo esc_attr($account_info['position']); ?>" required><br><br>

        <?php foreach ($custom_attributes as $attribute): ?>
            <label for="customAttribute_<?php echo esc_attr($attribute['id']); ?>"><?php echo esc_html($attribute['label']); ?>:</label><br>
            <input type="text" id="customAttribute_<?php echo esc_attr($attribute['id']); ?>" name="customAttributes[<?php echo esc_attr($attribute['id']); ?>]" value="<?php echo esc_attr($attribute['value']); ?>"><br><br>
        <?php endforeach; ?>

        <input type="submit" name="librebooking_update_account_submit" value="Opdater Konto">
    </form>

    <button id="change-password-button">Ændre kodeord</button>
    <form id="change-password-form" method="post" style="display:none;">
        <label for="currentPassword">Nuværende kodeord:</label><br>
        <input type="password" id="currentPassword" name="currentPassword" required><br><br>

        <label for="newPassword">Nyt kodeord:</label><br>
        <input type="password" id="newPassword" name="newPassword" required><br><br>

        <input type="submit" name="librebooking_change_password_submit" value="Ændre kodeord">
    </form>

    <script>
    document.getElementById('change-password-button').addEventListener('click', function() {
        var form = document.getElementById('change-password-form');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    });
    </script>
    <?php

    // Debugging: Vis API-respons i HTML
    if (get_option('librebooking_debug_mode')) {
        echo '<pre>Debug API Response: ' . esc_html(print_r($account_info, true)) . '</pre>';
    }

    return ob_get_clean();
}
add_shortcode('librebooking_account_info', 'librebooking_account_info_shortcode');

// Funktion til at opdatere konto-oplysninger
function librebooking_update_account_info($url, $data) {
    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($data),
    ]);

    if (is_wp_error($response)) {
        return $response;
    }

    $response_body = wp_remote_retrieve_body($response);
    return json_decode($response_body, true);
}





function librebooking_reservations_shortcode() {
    if (!isset($_SESSION['X-Booked-SessionToken'])) {
        return 'You must be logged in to view reservations.';
    }

    // Hent WordPress tidszoneindstillinger
    $wp_timezone = get_option('timezone_string');
    if (!$wp_timezone) {
        $wp_timezone = 'UTC';
    }

    $reservations = librebooking_get_reservations();
    if (is_array($reservations) && isset($reservations['reservations'])) {
        ob_start();
        echo '<h2>Reservations</h2>';
        echo '<ul>';
        foreach ($reservations['reservations'] as $reservation) {
            echo '<li>';
            echo '<h3>' . esc_html($reservation['title']) . '</h3>';
            echo '<p><strong>Reference Number:</strong> ' . esc_html($reservation['referenceNumber']) . '</p>';

            // Konverter start- og slutdato til lokal tid
            $startDate = new DateTime($reservation['startDate'], new DateTimeZone('UTC'));
            $endDate = new DateTime($reservation['endDate'], new DateTimeZone('UTC'));
            $timezone = new DateTimeZone($wp_timezone);
            $startDate->setTimezone($timezone);
            $endDate->setTimezone($timezone);

            echo '<p><strong>Start Date:</strong> ' . esc_html($startDate->format('d-m-Y H:i:s')) . '</p>';
            echo '<p><strong>End Date:</strong> ' . esc_html($endDate->format('d-m-Y H:i:s')) . '</p>';
            echo '<p><strong>First Name:</strong> ' . esc_html($reservation['firstName']) . '</p>';
            echo '<p><strong>Last Name:</strong> ' . esc_html($reservation['lastName']) . '</p>';
            echo '<p><strong>Resource Name:</strong> ' . esc_html($reservation['resourceName']) . '</p>';
            echo '<p><strong>Description:</strong> ' . esc_html($reservation['description']) . '</p>';
            echo '<p><strong>Duration:</strong> ' . esc_html($reservation['duration']) . '</p>';

            // Hent og vis detaljer via links
            if (isset($reservation['links'])) {
                foreach ($reservation['links'] as $link) {
                    if ($link['title'] === 'get_resource') {
                        $resource_details = librebooking_get_resource_details($link['href']);
                        if ($resource_details) {
                            echo '<h4>Resource Details</h4>';
                            echo '<p>' . esc_html($resource_details) . '</p>';
                        }
                    } elseif ($link['title'] === 'get_reservation') {
                        $reservation_details = librebooking_get_reservation_details($link['href']);
                        if ($reservation_details) {
                            echo '<h4>Reservation Details</h4>';
                            echo '<p>' . esc_html($reservation_details) . '</p>';
                        }
                    } elseif ($link['title'] === 'get_user') {
                        $user_details = librebooking_get_user_details($link['href']);
                        if ($user_details) {
                            echo '<h4>User Details</h4>';
                            echo '<p>' . esc_html($user_details) . '</p>';
                        }
                    } elseif ($link['title'] === 'get_schedule') {
                        $schedule_details = librebooking_get_schedule_details($link['href']);
                        if ($schedule_details) {
                            echo '<h4>Schedule Details</h4>';
                            echo '<p>' . esc_html($schedule_details) . '</p>';
                        }
                    }
                }
            }

            echo '</li>';
        }
        echo '</ul>';
        return ob_get_clean();
    } else {
        return 'No reservations found or an error occurred.';
    }
}
add_shortcode('librebooking_reservations', 'librebooking_reservations_shortcode');

// Shortcode-funktion til at vise brugerregistreringsformularen
function librebooking_user_registration_shortcode() {
    // Hvis brugeren allerede er logget ind
    if (isset($_SESSION['X-Booked-SessionToken'])) {
        return 'You are already logged in.';
    }

    // Hvis formularen er indsendt
    if (isset($_POST['librebooking_user_registration_submit'])) {
        $response = librebooking_create_user($_POST);

        // Tjek for fejl i svaret
        if (is_array($response) && isset($response['errors'])) {
            ob_start();
            echo '<ul class="librebooking-errors">';
            foreach ($response['errors'] as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            return ob_get_clean();
        }

        if (is_array($response) && isset($response['accountId'])) {
            return 'Registration successful! Your account ID: ' . esc_html($response['accountId']);
        } else {
            return 'Registration failed: ' . esc_html($response);
        }
    }

    // Vis formularen
    ob_start();
    ?>
    <form method="post" id="librebooking-user-registration-form">
        <label for="firstName">First Name:</label><br>
        <input type="text" id="firstName" name="firstName" required><br><br>

        <label for="lastName">Last Name:</label><br>
        <input type="text" id="lastName" name="lastName" required><br><br>

        <label for="emailAddress">Email Address:</label><br>
        <input type="email" id="emailAddress" name="emailAddress" required><br><br>

        <label for="userName">Username:</label><br>
        <input type="text" id="userName" name="userName" required><br><br>

        <label for="language">Language:</label><br>
        <input type="text" id="language" name="language" value="da_da" required><br><br>

        <label for="timezone">Timezone:</label><br>
        <input type="text" id="timezone" name="timezone" value="Europe/Copenhagen" required><br><br>

        <label for="phone">Phone:</label><br>
        <input type="text" id="phone" name="phone"><br><br>

        <label for="organization">Organization:</label><br>
        <input type="text" id="organization" name="organization"><br><br>

        <label for="position">Position:</label><br>
        <input type="text" id="position" name="position"><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <label for="acceptTermsOfService">Accept Terms of Service:</label><br>
        <input type="checkbox" id="acceptTermsOfService" name="acceptTermsOfService" value="true" required><br><br>

        <input type="submit" name="librebooking_user_registration_submit" value="Register">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('librebooking_user_registration', 'librebooking_user_registration_shortcode');
