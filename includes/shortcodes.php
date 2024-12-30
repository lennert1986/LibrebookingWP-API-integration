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
        return;
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

// Shortcode-funktion til at vise alle skemaer
function librebooking_schedules_shortcode() {
    // Kontrollér, om brugeren er logget ind
    if (!isset($_SESSION['X-Booked-SessionToken'])) {
        return 'You must be logged in to view schedules.';
    }

    $schedules = librebooking_get_all_schedules();

    if (is_string($schedules)) {
        return '<p>Error: ' . esc_html($schedules) . '</p>';
    }

    if (empty($schedules)) {
        return '<p>No schedules found.</p>';
    }

    ob_start();
    echo '<ul>';
    foreach ($schedules as $schedule) {
        echo '<li>';
        echo '<strong>' . esc_html($schedule['name']) . '</strong><br>';
        echo 'Timezone: ' . esc_html($schedule['timezone']) . '<br>';
        echo 'Days Visible: ' . esc_html($schedule['daysVisible']) . '<br>';
        echo 'Availability Begin: ' . esc_html($schedule['availabilityBegin']) . '<br>';
        echo 'Availability End: ' . esc_html($schedule['availabilityEnd']) . '<br>';
        echo 'Max Resources Per Reservation: ' . esc_html($schedule['maxResourcesPerReservation']) . '<br>';
        echo 'Total Concurrent Reservations Allowed: ' . esc_html($schedule['totalConcurrentReservationsAllowed']) . '<br>';
        echo '</li>';
    }
    echo '</ul>';
    return ob_get_clean();
}
add_shortcode('librebooking_schedules', 'librebooking_schedules_shortcode');



// Shortcode-funktion til at vise konto-oplysninger
function librebooking_account_info_shortcode() {
    // Kontrollér om brugeren er logget ind
    if (!isset($_SESSION['X-Booked-SessionToken'])) {
        return 'You must be logged in to view profile.';
    }

    // Hent konto-oplysninger via API
    $account_info = librebooking_get_account_info();

    // Kontrollér, om der er opstået en fejl
    if (is_string($account_info)) {
        return '<p>Fejl: ' . esc_html($account_info) . '</p>';
    }

    if (!is_array($account_info)) {
        return '<p>Kunne ikke hente konto-oplysninger. Kontroller API-responsen.</p>';
    }

    // Læs tidszoner fra CSV-fil
    $timezones = [];
    if (($handle = fopen(dirname(__FILE__) . '/../locale/time_zone.csv', 'r')) !== false) {
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $timezone = $data[0];
            if (!in_array($timezone, $timezones)) {
                $timezones[] = $timezone;
            }
        }
        fclose($handle);
    }

    // Håndter formularindsendelse for at ændre kodeord
    if (isset($_POST['librebooking_change_password_submit'])) {
        $current_password = sanitize_text_field($_POST['currentPassword']);
        $new_password = sanitize_text_field($_POST['newPassword']);
        $user_id = isset($_SESSION['X-Booked-UserId']) ? $_SESSION['X-Booked-UserId'] : '';

        // Hent URL til at ændre kodeord
        $change_password_url = '';
        if (isset($account_info['parsed_links']['update_password'])) {
            $change_password_url = $account_info['parsed_links']['update_password'];
        }

        // Ret URL'en hvis nødvendigt
        if (strpos($change_password_url, 'index.php/Users/:userId/Password') !== false) {
            $change_password_url = str_replace('index.php/Users/:userId/Password', 'index.php/Accounts/:userId/Password', $change_password_url);
        }

        // Erstat :userId med det korrekte bruger-ID fra sessionen
        $change_password_url = str_replace(':userId', $user_id, $change_password_url);

        // Debug log for URL
        error_log('Change Password URL: ' . $change_password_url);

        $response = librebooking_change_password($change_password_url, $current_password, $new_password);

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

        // Debug log for parsed_links
        error_log('Parsed Links: ' . print_r($account_info['parsed_links'], true));

        if (isset($account_info['parsed_links']['update_user_account'])) {
            $update_url = str_replace(':userId', $user_id, $account_info['parsed_links']['update_user_account']);
        }

        // Debug log for URL
        error_log('Update URL: ' . $update_url);

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

        // Debug log for data
        error_log('Update Data: ' . json_encode($update_data));

        $response = librebooking_update_account_info($update_url, $update_data);

        if (is_wp_error($response)) {
            echo '<p>Fejl: ' . esc_html($response->get_error_message()) . '</p>';
        } else {
            echo '<p>Konto-oplysninger opdateret succesfuldt.</p>';
        }
    }

    // Hent customAttributes
    $custom_attributes = isset($account_info['customAttributes']) ? $account_info['customAttributes'] : [];

    // Generér HTML for at vise konto-oplysninger
    ob_start();
    ?>
    <h2>Kontooplysninger</h2>

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
        <select id="timezone" name="timezone" required>
            <?php foreach ($timezones as $timezone): ?>
                <option value="<?php echo esc_attr($timezone); ?>" <?php selected($account_info['timezone'], $timezone); ?>>
                    <?php echo esc_html($timezone); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

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

        <input type="submit" name="librebooking_update_account_submit" value="Opdater Konto" class="button">
    </form>

    <form id="change-password-form" method="post">
        <button id="change-password-button" class="button">Ændre kodeord</button>
        <div id="change-password-fields" style="display:none;">
            <label for="currentPassword">Nuværende kodeord:</label><br>
            <input type="password" id="currentPassword" name="currentPassword" required><br><br>

            <label for="newPassword">Nyt kodeord:</label><br>
            <input type="password" id="newPassword" name="newPassword" required><br><br>

            <input type="submit" name="librebooking_change_password_submit" value="Opdater kodeord" class="button">
        </div>
    </form>

    <script>
    document.getElementById('change-password-button').addEventListener('click', function(event) {
        event.preventDefault();
        var fields = document.getElementById('change-password-fields');
        fields.style.display = fields.style.display === 'none' ? 'block' : 'none';
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

function librebooking_reservations_shortcode() {
    if (!isset($_SESSION['X-Booked-SessionToken'])) {
        return;
    }

    // Hent WordPress tidszoneindstillinger
    $wp_timezone = get_option('timezone_string');
    if (!$wp_timezone) {
        $wp_timezone = 'UTC';
    }

    $reservations = librebooking_get_reservations();
    if (is_array($reservations) && isset($reservations['reservations'])) {
        ob_start();
        ?>
        <style>
            .librebooking-reservations-container {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
            }
            .librebooking-reservation {
                flex: 1 1 calc(50% - 20px);
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                padding: 20px;
                border-radius: 5px;
                background-color: #fff;
            }
            @media (max-width: 768px) {
                .librebooking-reservation {
                    flex: 1 1 100%;
                }
            }
        </style>
        <h2>Reservations</h2>
        <div class="librebooking-reservations-container">
        <?php
        foreach ($reservations['reservations'] as $reservation) {
            ?>
            <div class="librebooking-reservation">
                <h3><?php echo esc_html($reservation['title']); ?></h3>
                <p><strong>Reference Number:</strong> <?php echo esc_html($reservation['referenceNumber']); ?></p>
                <?php
                // Konverter start- og slutdato til lokal tid
                $startDate = new DateTime($reservation['startDate'], new DateTimeZone('UTC'));
                $endDate = new DateTime($reservation['endDate'], new DateTimeZone('UTC'));
                $timezone = new DateTimeZone($wp_timezone);
                $startDate->setTimezone($timezone);
                $endDate->setTimezone($timezone);
                ?>
                <p><strong>Start Date:</strong> <?php echo esc_html($startDate->format('d-m-Y H:i:s')); ?></p>
                <p><strong>End Date:</strong> <?php echo esc_html($endDate->format('d-m-Y H:i:s')); ?></p>
                <p><strong>First Name:</strong> <?php echo esc_html($reservation['firstName']); ?></p>
                <p><strong>Last Name:</strong> <?php echo esc_html($reservation['lastName']); ?></p>
                <p><strong>Resource Name:</strong> <?php echo esc_html($reservation['resourceName']); ?></p>
                <p><strong>Description:</strong> <?php echo esc_html($reservation['description']); ?></p>
                <p><strong>Duration:</strong> <?php echo esc_html($reservation['duration']); ?></p>

                <?php
                // Hent og vis detaljer via links
                if (isset($reservation['links'])) {
                    foreach ($reservation['links'] as $link) {
                        if ($link['title'] === 'get_resource') {
                            $resource_details = JSON_get_resource($link['href']);
                            if ($resource_details) {
                                echo '<h4>Resource Details</h4>';
                                echo '<p>' . esc_html($resource_details) . '</p>';
                            }
                        } elseif ($link['title'] === 'get_reservation') {
                            $reservation_details = JSON_get_reservation($link['href']);
                            if ($reservation_details) {
                                echo '<h4>Reservation Details</h4>';
                                echo '<p>' . esc_html($reservation_details) . '</p>';
                            }
                        } elseif ($link['title'] === 'get_user') {
                            $user_details = JSON_get_user($link['href']);
                            if ($user_details) {
                                echo '<h4>User Details</h4>';
                                echo '<p>' . esc_html($user_details) . '</p>';
                            }
                        } elseif ($link['title'] === 'get_schedule') {
                            $schedule_details = JSON_get_schedule($link['href']);
                            if ($schedule_details) {
                                echo '<h4>Schedule Details</h4>';
                                echo '<p>' . esc_html($schedule_details) . '</p>';
                            }
                        }
                    }
                }
                ?>
            </div>
            <?php
        }
        ?>
        </div>
        <?php
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
        return;
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


// Shortcode-funktion til login
function librebooking_login_shortcode() {
    $login_failed = false;

    // Hent URL til registreringssiden
    $registration_page = get_page_by_title('Librebooking User Registration');
    $registration_url = $registration_page ? get_permalink($registration_page->ID) : '#';

    // Hent URL til reservationssiden
    $reservation_page = get_page_by_title('Librebooking Reservation Formular');
    $reservation_url = $reservation_page ? get_permalink($reservation_page->ID) : '#';

    // Hvis sessionen ikke er aktiv, er brugeren ikke logget ind
    if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['X-Booked-SessionToken']) || librebooking_is_session_token_expired()) {
        if (isset($_POST['librebooking_login'])) {
            $username = sanitize_text_field($_POST['username']);
            $password = sanitize_text_field($_POST['password']);
            
            if (librebooking_login($username, $password)) {
                // Omdiriger til reservationssiden
                wp_redirect($reservation_url);
                exit;
            } else {
                $login_failed = true;
            }
        }

        ob_start();
        if ($login_failed) {
            echo '<p>Login failed. Please check your credentials.</p>';
        }
        ?>
        <form method="post">
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username" required><br><br>
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>
            <input type="submit" name="librebooking_login" value="Login" class="button">
            <a href="<?php echo esc_url($registration_url); ?>" class="button">Registrer</a>
        </form>
        <?php
        return ob_get_clean();
    }

    // Hvis brugeren allerede er logget ind
    ob_start();
    ?>
    <p>You are already logged in. <a href="<?php echo esc_url($reservation_url); ?>">Go to Reservations</a></p>
    <?php
    return ob_get_clean();
}
add_shortcode('librebooking_login', 'librebooking_login_shortcode');

// Shortcode-funktion til logout
function librebooking_auth_shortcode($atts) {
    $login_failed = false;

    // Hent URL til registreringssiden
    $registration_page = get_page_by_title('Librebooking User Registration');
    $registration_url = $registration_page ? get_permalink($registration_page->ID) : '#';

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
                    <input type="submit" name="librebooking_logout" value="Logout" class="button">
                </form>
                <?php
                return ob_get_clean();
            } else {
                $login_failed = true;
            }
        }

        ob_start();
        if ($login_failed) {
            echo '<p>Login failed. Please check your credentials.</p>';
        }
        ?>
        <form method="post">
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username" required><br><br>
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>
            <input type="submit" name="librebooking_login" value="Login" class="button">
            <a href="<?php echo esc_url($registration_url); ?>" class="button">Registrer</a>
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
                <input type="submit" name="librebooking_login" value="Login" class="button">
                <a href="<?php echo esc_url($registration_url); ?>" class="button">Registrer</a>
            </form>
            <?php
            return ob_get_clean();
        } else {
            return 'Logout failed. Please try again.';
        }
    }

    ob_start();
    ?>
    <form method="post">
        <input type="submit" name="librebooking_logout" value="Logout" class="button">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('librebooking_auth', 'librebooking_auth_shortcode');

// Funktion til at generere en menu med links til brugerdefinerede sider
function librebooking_custom_pages_menu_shortcode() {
    // Definer en liste over sider med deres respektive titler og de navne, der skal vises i menuen
    $pages = [];

    // Tilføj sider kun hvis der er en aktiv session
    if (isset($_SESSION['X-Booked-UserId']) && isset($_SESSION['X-Booked-SessionToken'])) {
        $pages['Librebooking Reservation'] = 'Reservationer';
        $pages['Librebooking Profile'] = 'Profil';
        $pages['Librebooking Reservation Formular'] = 'Reservationsformular';
    }

    // Start output buffering
    ob_start();

    // Generer menuen
    echo '<nav class="librebooking-custom-menu"><ul>';
    foreach ($pages as $page_title => $menu_name) {
        $page = get_page_by_title($page_title);
        if ($page) {
            $page_url = get_permalink($page->ID);
            echo '<li><a href="' . esc_url($page_url) . '">' . esc_html($menu_name) . '</a></li>';
        }
    }
    echo '</ul></nav>';

    // Returner output
    return ob_get_clean();
}
add_shortcode('librebooking_custom_menu', 'librebooking_custom_pages_menu_shortcode');