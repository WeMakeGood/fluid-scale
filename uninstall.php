<?php
/**
 * Fluid Scale Uninstall
 *
 * Runs when the plugin is deleted from the WordPress admin.
 * Removes all plugin data: wp_options entry and generated CSS file.
 *
 * @package FluidScale
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load only what's needed for cleanup — no full plugin bootstrap.
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-file-writer.php';

FluidScale\Settings::delete();
FluidScale\FileWriter::delete();
