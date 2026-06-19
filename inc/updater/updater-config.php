<?php
/**
 * Bootscore Theme Update Configuration
 * Registers ONLY the theme for updates from GitHub
 */

defined('ABSPATH') || exit;

// Initialize the updater (no URL needed for GitHub-only setup)
global $bootscore_updater;

if (!isset($bootscore_updater)) {
    $bootscore_updater = new Bootscore_Update_Checker('', 12 * HOUR_IN_SECONDS);
}

// --- REGISTER THEME FROM GITHUB PUBLIC REPO ---
$theme = wp_get_theme('update-theme-public-repo');

$bootscore_updater->register_product(array(
    'type' => 'theme',
    'slug' => 'update-theme-public-repo',
    'current_version' => $theme->get('Version') ?? '1.0.0',
    'file' => 'update-theme-public-repo',
    'source' => 'github',
    'github_repo' => 'crftwrk/update-theme-public-repo',
    'name' => 'Update Theme Public Repo',
));