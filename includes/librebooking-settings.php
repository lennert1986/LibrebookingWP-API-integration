<?php

// Funktion til at vise indstillingsformularen
function librebooking_settings_form() {
    if ($_POST['librebooking_settings']) {
        $base_url = trailingslashit(esc_url_raw($_POST['base_url']));
        update_option('librebooking_base_url', $base_url);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $base_url = get_option('librebooking_base_url', 'https://booking.9700-paintball.dk/');

    ?>
    <form method="post">
        <label for="base_url">Base URL:</label><br>
        <input type="text" id="base_url" name="base_url" value="<?php echo esc_url($base_url); ?>" required><br><br>
        <input type="submit" name="librebooking_settings" value="Save Settings">
    </form>
    <?php
}
