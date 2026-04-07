<?php
/**
 * Builder Detector
 *
 * Detects which page builders or themes are active.
 * Detection only — no side effects.
 *
 * See docs/builder-mappings.md for full detection logic and known limitations.
 *
 * @package FluidScale
 */

namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BuilderDetector {

	/**
	 * Return slugs of all detected active builders.
	 *
	 * @return string[] e.g. ['divi5'], ['bricks'], or []
	 */
	public static function get_active_builders(): array {
		$detected = [];

		if ( self::is_divi5() ) {
			$detected[] = 'divi5';
		}

		if ( self::is_bricks() ) {
			$detected[] = 'bricks';
		}

		return $detected;
	}

	/**
	 * Check whether any supported builder is active.
	 */
	public static function has_any(): bool {
		return ! empty( self::get_active_builders() );
	}

	/**
	 * Detect Divi 5.
	 *
	 * ET_CORE_VERSION is defined by the Divi theme/plugin core.
	 * Version 5.0+ indicates Divi 5's new architecture.
	 */
	public static function is_divi5(): bool {
		return defined( 'ET_CORE_VERSION' )
			&& version_compare( constant( 'ET_CORE_VERSION' ), '5.0', '>=' )
			&& ( get_template() === 'Divi' || class_exists( 'ET_Builder_Plugin' ) );
	}

	/**
	 * Detect Bricks Builder.
	 *
	 * BRICKS_VERSION is defined when Bricks is active as a theme or plugin.
	 */
	public static function is_bricks(): bool {
		return defined( 'BRICKS_VERSION' )
			|| get_template() === 'bricks';
	}
}
