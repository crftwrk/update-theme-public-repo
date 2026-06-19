<?php
/**
 * Bootscore Theme Update Configuration
 * Registers ONLY the theme for updates
 */

defined('ABSPATH') || exit;

/*
global $bootscore_updater;

// Initialize the updater
$bootscore_updater = new Bootscore_Update_Checker(
    'https://files.bootscore.me/8SNK-BTTX-JH2P-GJZ4/',
    12 * HOUR_IN_SECONDS
);

// Register ONLY the theme
$theme = wp_get_theme('bootscore');
$bootscore_updater->register_product(array(
    'type' => 'theme',
    'slug' => 'bootscore',
    'current_version' => $theme->get('Version'),
    'file' => 'bootscore',
    'server_path' => 'themes/bootscore',
    'name' => 'Bootscore Theme',
));
*/



// Initialize the updater (only once)
global $bootscore_updater;

if (!isset($bootscore_updater)) {
    $bootscore_updater = new Bootscore_Update_Checker(
        'https://files.bootscore.me/your-token/', // Your custom server URL for private plugins
        12 * HOUR_IN_SECONDS
    );
}

// --- REGISTER THEME FROM GITHUB PUBLIC REPO ---
$theme = wp_get_theme('update-theme-public-repo'); // Theme slug (folder name)

$bootscore_updater->register_product(array(
    'type' => 'theme',
    'slug' => 'update-theme-public-repo', // Matches theme folder name
    'current_version' => $theme->get('Version') ?? '1.0.0', // Reads from style.css
    'file' => 'update-theme-public-repo', // Theme slug for WordPress updates
    'source' => 'github', // 👈 Use GitHub public API
    'github_repo' => 'crftwrk/update-theme-public-repo', // 👈 Your public repo
    'name' => 'Update Theme Public Repo', // Display name
));

// --- OPTIONAL: Register any private plugins from your custom server ---
// $bootscore_updater->register_product(array(
//     'type' => 'plugin',
//     'slug' => 'my-private-plugin',
//     'current_version' => '1.0',
//     'file' => 'my-private-plugin/main.php',
//     'source' => 'custom',
//     'server_path' => 'plugins/my-private-plugin',
//     'name' => 'My Private Plugin',
// ));