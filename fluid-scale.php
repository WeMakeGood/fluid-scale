<?php
/**
 * Plugin Name:       Fluid Scale
 * Plugin URI:        https://github.com/WeMakeGood/fluid-scale
 * Description:       Injects a complete Utopia fluid design system — type, space, and grid — as CSS custom properties available to any theme or page builder.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Make Good
 * Author URI:        https://wemakegood.org
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fluid-scale
 * Domain Path:       /languages
 *
 * @package FluidScale
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLUID_SCALE_VERSION', '1.0.0' );
define( 'FLUID_SCALE_FILE',    __FILE__ );
define( 'FLUID_SCALE_DIR',     plugin_dir_path( __FILE__ ) );
define( 'FLUID_SCALE_URL',     plugin_dir_url( __FILE__ ) );

/**
 * Autoload plugin classes.
 * All classes live in includes/ or admin/ under the FluidScale namespace.
 */
spl_autoload_register( function ( string $class ): void {
	if ( strpos( $class, 'FluidScale\\' ) !== 0 ) {
		return;
	}

	$relative = substr( $class, strlen( 'FluidScale\\' ) );
	$filename = 'class-' . strtolower( str_replace( [ '\\', '_' ], [ '/', '-' ], $relative ) ) . '.php';

	$locations = [
		FLUID_SCALE_DIR . 'includes/' . $filename,
		FLUID_SCALE_DIR . 'admin/'    . $filename,
	];

	foreach ( $locations as $path ) {
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}
	}
} );

/**
 * Bootstrap the plugin.
 */
function fluid_scale_init(): void {
	// Load translations.
	load_plugin_textdomain(
		'fluid-scale',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	// Front end: enqueue the generated stylesheet.
	( new FluidScale\Enqueue() )->init();

	// Admin: settings page and form handling.
	if ( is_admin() ) {
		( new FluidScale\AdminPage() )->init();
	}
}
add_action( 'init', 'fluid_scale_init' );
