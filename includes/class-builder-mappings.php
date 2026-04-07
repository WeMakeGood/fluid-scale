<?php
/**
 * Builder Mappings
 *
 * Returns CSS mapping blocks that map the plugin's canonical variables
 * to builder-specific variable names. Appended after the main scale block.
 *
 * IMPORTANT: Variable names for Divi 5 and Bricks are marked TODO where
 * unverified. Do not remove TODO markers without testing against a live
 * instance of the respective builder. See docs/builder-mappings.md.
 *
 * @package FluidScale
 */

namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BuilderMappings {

	/**
	 * Generate the complete mapping CSS block for a given builder.
	 * Returns an empty string if the builder has no verified mapping.
	 *
	 * @param string $builder Builder slug: 'divi5' | 'bricks'
	 * @return string CSS string (may be empty).
	 */
	public static function get_mapping_css( string $builder ): string {
		return match ( $builder ) {
			'divi5'  => '',  // Divi 5 mapping is output via wp_head (late priority) to win cascade — see AdminPage
			'bricks' => self::bricks_mapping(),
			default  => '',
		};
	}

	/**
	 * Generate the Divi 5 :root override block for wp_head output.
	 *
	 * Must run at wp_head priority 99 to land after Divi's critical inline CSS
	 * (which writes these vars at index ~18 in the stylesheet cascade).
	 *
	 * Fixed mappings:
	 *   --content-max-width   → var(--grid-max-width)   — keeps content width in sync
	 *   --row-gutter-horizontal → var(--grid-gutter)    — replaces Divi's arbitrary 5.5%
	 *
	 * Configurable mappings (space steps chosen by user in settings):
	 *   --section-padding, --section-gutter, --row-gutter-vertical, --module-gutter
	 *
	 * @param array $divi_mapping User-configured space step selections.
	 */
	public static function divi5_head_css( array $divi_mapping ): string {
		$sp  = $divi_mapping['section_padding']     ?? 'xl';
		$sg  = $divi_mapping['section_gutter']      ?? 'xl';
		$rgv = $divi_mapping['row_gutter_vertical']  ?? 'l';
		$mg  = $divi_mapping['module_gutter']        ?? 'm';

		return implode( "\n", [
			'<style id="fluid-scale-divi-mapping">',
			':root {',
			"\t--content-max-width:      var(--grid-max-width);",
			"\t--row-gutter-horizontal:  var(--grid-gutter);",
			"\t--section-padding:        var(--space-{$sp});",
			"\t--section-gutter:         var(--space-{$sg});",
			"\t--row-gutter-vertical:    var(--space-{$rgv});",
			"\t--module-gutter:          var(--space-{$mg});",
			'}',
			'</style>',
		] );
	}

	/**
	 * Return a human-readable label for a builder slug.
	 *
	 * @param string $builder
	 * @return string
	 */
	public static function get_label( string $builder ): string {
		return match ( $builder ) {
			'divi5'  => __( 'Divi 5', 'fluid-scale' ),
			'bricks' => __( 'Bricks Builder', 'fluid-scale' ),
			default  => $builder,
		};
	}

	/**
	 * Return a description for a builder mapping, shown in the settings UI.
	 *
	 * @param string $builder
	 * @return string
	 */
	public static function get_description( string $builder ): string {
		return match ( $builder ) {
			'divi5'  => __(
				'Maps Fluid Scale variables to Divi 5 design token names. Use these variables in Divi\'s Theme Options > Custom CSS or any module\'s Advanced > Custom CSS field.',
				'fluid-scale'
			),
			'bricks' => __(
				'Maps Fluid Scale variables to Bricks Builder global style names.',
				'fluid-scale'
			),
			default  => '',
		};
	}

	// -------------------------------------------------------------------------
	// Divi 5
	// -------------------------------------------------------------------------

	/**
	 * Divi 5 mapping block.
	 *
	 * STATUS: Partially verified. Divi 5 generates its font-size variables
	 * dynamically from the visual builder — static variable names vary by
	 * configuration. The mapping below provides the canonical Fluid Scale
	 * variables as a reference, intended for use in Divi's Custom CSS fields.
	 *
	 * TODO: Verify whether Divi 5 Design Variables write named :root custom
	 * properties that can be overridden upstream. If yes, populate the
	 * --divi-* variable names here and update docs/builder-mappings.md.
	 *
	 * @see docs/builder-mappings.md
	 */
	private static function divi5_mapping(): string {
		// Divi 5 consumes CSS custom properties defined in :root. The block below
		// documents the Fluid Scale variable names so they can be referenced in
		// Divi's custom CSS interface. Until Divi 5's own design token variable
		// names are verified, no --divi-* overrides are output.
		return <<<CSS
/* === Builder Mapping: Divi 5 === */
/*
 * Fluid Scale variables are available globally in Divi 5.
 * Use them in Theme Options > Custom CSS or any module's Custom CSS field:
 *
 * Type:  var(--step-0) through var(--step-5), var(--step--1), var(--step--2)
 *        var(--fs-body), var(--fs-h1) ... var(--fs-h6)
 * Space: var(--space-s), var(--space-m), var(--space-l) etc.
 * Grid:  var(--grid-max-width), var(--grid-gutter), var(--grid-columns)
 *
 * TODO: Add --divi-* overrides here once Design Variable names are verified
 * against a live Divi 5 instance. See docs/builder-mappings.md.
 */
CSS;
	}

	// -------------------------------------------------------------------------
	// Bricks Builder
	// -------------------------------------------------------------------------

	/**
	 * Bricks Builder mapping block.
	 *
	 * STATUS: Unverified. Variable names below are based on known Bricks
	 * architecture but must be confirmed against a live Bricks install.
	 *
	 * TODO: Install Bricks on the dev environment, inspect :root output,
	 * confirm variable names, and remove this TODO block.
	 *
	 * @see docs/builder-mappings.md
	 */
	private static function bricks_mapping(): string {
		// TODO: Verify these variable names against a live Bricks Builder instance.
		// The names below are placeholders based on documented Bricks architecture.
		// Uncomment and verify each line before enabling.
		return <<<CSS
/* === Builder Mapping: Bricks Builder === */
/*
 * TODO: Uncomment and verify against a live Bricks install before enabling.
 *
 * :root {
 *   --bricks-font-size-base: var(--step-0);
 *   --bricks-font-size-s:    var(--step--1);
 *   --bricks-font-size-xs:   var(--step--2);
 *   --bricks-font-size-m:    var(--step-1);
 *   --bricks-font-size-l:    var(--step-2);
 *   --bricks-font-size-xl:   var(--step-3);
 *   --bricks-font-size-2xl:  var(--step-4);
 *   --bricks-font-size-3xl:  var(--step-5);
 * }
 *
 * See docs/builder-mappings.md for full verification checklist.
 */
CSS;
	}
}
