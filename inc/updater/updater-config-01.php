<?php
/**
 * Bootscore Theme Update Configuration
 * Registers ONLY the theme for updates
 */

defined('ABSPATH') || exit;

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