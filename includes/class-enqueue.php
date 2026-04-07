<?php
/**
 * Front-end stylesheet enqueue.
 *
 * Enqueues the generated CSS file at priority 1 on wp_enqueue_scripts,
 * before any theme or builder styles load.
 *
 * @package FluidScale
 */

namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Enqueue {

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_stylesheet' ], 1 );
	}

	/**
	 * Enqueue the generated CSS file.
	 *
	 * Uses last_generated timestamp as the version string so CDNs pick up
	 * changes when settings are saved (Story 4).
	 *
	 * Falls back gracefully if the file doesn't exist — no error, no output.
	 */
	public function enqueue_stylesheet(): void {
		if ( ! FileWriter::exists() ) {
			return;
		}

		$settings = Settings::get();
		$version  = (string) ( $settings['last_generated'] ?: FLUID_SCALE_VERSION );

		wp_enqueue_style(
			'fluid-scale',
			FileWriter::get_url(),
			[],
			$version
		);
	}
}
