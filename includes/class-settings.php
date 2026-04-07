<?php
/**
 * Settings persistence and sanitization.
 *
 * Responsible for reading and writing fluid_scale_settings in wp_options.
 * All values are sanitized to their expected types on save.
 *
 * @package FluidScale
 */

namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	const OPTION_KEY = 'fluid_scale_settings';

	/**
	 * Default settings. Produces a good result for a typical WordPress site
	 * without any changes (Story 1 acceptance criteria).
	 */
	public static function defaults(): array {
		return [
			'min_viewport'     => 320,
			'max_viewport'     => 1240,
			'min_base'         => 16.0,
			'max_base'         => 20.0,
			'ratio'            => 1.333,
			'negative_steps'   => 2,
			'positive_steps'   => 5,
			'custom_pairs'     => [],
			'grid_max_width'   => 1240,
			'grid_columns'     => 12,
			'grid_gutter_pair' => 's-l',
			'builder_mapping'  => 'auto',
			'divi_mapping'     => [
				'section_padding'      => 'xl',
				'section_gutter'       => 'xl',
				'row_gutter_vertical'  => 'l',
				'module_gutter'        => 'm',
			],
			'last_generated'   => 0,
		];
	}

	/**
	 * Get current settings, merged with defaults for any missing keys.
	 */
	public static function get(): array {
		$saved = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		return array_merge( self::defaults(), $saved );
	}

	/**
	 * Sanitize raw POST input and save to wp_options.
	 * Fires 'fluid_scale_settings_saved' action with the clean settings array.
	 *
	 * @param array $raw Raw $_POST data (unsanitized).
	 * @return array The sanitized settings that were saved.
	 */
	public static function save( array $raw ): array {
		$clean = self::sanitize( $raw );
		$clean['last_generated'] = time();
		update_option( self::OPTION_KEY, $clean, false );

		/**
		 * Fires after settings are saved.
		 * Used by FileWriter to regenerate the CSS file.
		 *
		 * @param array $clean Sanitized settings.
		 */
		do_action( 'fluid_scale_settings_saved', $clean );

		return $clean;
	}

	/**
	 * Sanitize a raw settings array.
	 * Each field is sanitized according to its expected type and valid range.
	 *
	 * @param array $raw Unsanitized input.
	 * @return array Sanitized settings (complete, with defaults for missing fields).
	 */
	public static function sanitize( array $raw ): array {
		$defaults = self::defaults();

		// Viewport widths: positive integers, min < max
		$min_vp = absint( $raw['min_viewport'] ?? $defaults['min_viewport'] );
		$max_vp = absint( $raw['max_viewport'] ?? $defaults['max_viewport'] );
		if ( $min_vp < 1 )   { $min_vp = $defaults['min_viewport']; }
		if ( $max_vp < 1 )   { $max_vp = $defaults['max_viewport']; }
		if ( $min_vp >= $max_vp ) {
			$min_vp = $defaults['min_viewport'];
			$max_vp = $defaults['max_viewport'];
		}

		// Base sizes: positive floats
		$min_base = (float) ( $raw['min_base'] ?? $defaults['min_base'] );
		$max_base = (float) ( $raw['max_base'] ?? $defaults['max_base'] );
		if ( $min_base <= 0 ) { $min_base = $defaults['min_base']; }
		if ( $max_base <= 0 ) { $max_base = $defaults['max_base']; }

		// Ratio: float, must be a known valid ratio or within a safe range
		$ratio = (float) ( $raw['ratio'] ?? $defaults['ratio'] );
		if ( $ratio < 1.001 || $ratio > 2.0 ) {
			$ratio = $defaults['ratio'];
		}

		// Steps: positive integers, reasonable caps
		$negative_steps = absint( $raw['negative_steps'] ?? $defaults['negative_steps'] );
		$positive_steps = absint( $raw['positive_steps'] ?? $defaults['positive_steps'] );
		$negative_steps = max( 1, min( 5, $negative_steps ) );
		$positive_steps = max( 1, min( 10, $positive_steps ) );

		// Custom pairs: array of [ from, to ] where both are valid space step names
		$valid_steps  = array_keys( Generator::space_step_names() );
		// space_step_names() returns keys already; use the array directly
		$valid_steps  = Generator::space_step_names();
		$custom_pairs = [];
		if ( ! empty( $raw['custom_pairs'] ) && is_array( $raw['custom_pairs'] ) ) {
			foreach ( $raw['custom_pairs'] as $pair ) {
				if ( ! is_array( $pair ) ) {
					continue;
				}
				$from = sanitize_text_field( $pair['from'] ?? '' );
				$to   = sanitize_text_field( $pair['to']   ?? '' );
				if ( in_array( $from, $valid_steps, true ) && in_array( $to, $valid_steps, true ) && $from !== $to ) {
					$custom_pairs[] = [ 'from' => $from, 'to' => $to ];
				}
			}
			// Deduplicate
			$seen         = [];
			$unique_pairs = [];
			foreach ( $custom_pairs as $pair ) {
				$key = $pair['from'] . '-' . $pair['to'];
				if ( ! isset( $seen[ $key ] ) ) {
					$seen[ $key ]   = true;
					$unique_pairs[] = $pair;
				}
			}
			$custom_pairs = $unique_pairs;
		}

		// Grid
		$grid_max_width = absint( $raw['grid_max_width'] ?? $defaults['grid_max_width'] );
		if ( $grid_max_width < 100 ) { $grid_max_width = $defaults['grid_max_width']; }

		$grid_columns = absint( $raw['grid_columns'] ?? $defaults['grid_columns'] );
		if ( $grid_columns < 1 || $grid_columns > 24 ) { $grid_columns = $defaults['grid_columns']; }

		// Gutter pair: must be a valid step-step key
		$grid_gutter_pair = sanitize_text_field( $raw['grid_gutter_pair'] ?? $defaults['grid_gutter_pair'] );
		$pair_parts       = explode( '-', $grid_gutter_pair, 2 );
		if ( count( $pair_parts ) !== 2 ||
			 ! in_array( $pair_parts[0], $valid_steps, true ) ||
			 ! in_array( $pair_parts[1], $valid_steps, true ) ) {
			$grid_gutter_pair = $defaults['grid_gutter_pair'];
		}

		// Builder mapping
		$allowed_mappings = [ 'auto', 'divi5', 'bricks', 'none' ];
		$builder_mapping  = sanitize_text_field( $raw['builder_mapping'] ?? $defaults['builder_mapping'] );
		if ( ! in_array( $builder_mapping, $allowed_mappings, true ) ) {
			$builder_mapping = $defaults['builder_mapping'];
		}

		// Divi mapping: four space-step selects
		$divi_defaults  = $defaults['divi_mapping'];
		$divi_raw       = is_array( $raw['divi_mapping'] ?? null ) ? $raw['divi_mapping'] : [];
		$divi_map_keys  = [ 'section_padding', 'section_gutter', 'row_gutter_vertical', 'module_gutter' ];
		$divi_mapping   = [];
		foreach ( $divi_map_keys as $key ) {
			$val = sanitize_text_field( $divi_raw[ $key ] ?? $divi_defaults[ $key ] );
			$divi_mapping[ $key ] = in_array( $val, $valid_steps, true ) ? $val : $divi_defaults[ $key ];
		}

		return [
			'min_viewport'     => $min_vp,
			'max_viewport'     => $max_vp,
			'min_base'         => $min_base,
			'max_base'         => $max_base,
			'ratio'            => $ratio,
			'negative_steps'   => $negative_steps,
			'positive_steps'   => $positive_steps,
			'custom_pairs'     => $custom_pairs,
			'grid_max_width'   => $grid_max_width,
			'grid_columns'     => $grid_columns,
			'grid_gutter_pair' => $grid_gutter_pair,
			'builder_mapping'  => $builder_mapping,
			'divi_mapping'     => $divi_mapping,
			'last_generated'   => (int) ( $raw['last_generated'] ?? 0 ),
		];
	}

	/**
	 * Delete settings from wp_options. Called by uninstall.php.
	 */
	public static function delete(): void {
		delete_option( self::OPTION_KEY );
	}
}
