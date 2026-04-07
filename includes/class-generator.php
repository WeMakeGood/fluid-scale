<?php
/**
 * Fluid Scale Generator
 *
 * Pure PHP implementation of the Utopia fluid design system.
 * No WordPress dependencies — accepts a settings array, returns a CSS string.
 *
 * Math reference: docs/utopia-math.md
 * Credit: Utopia (https://utopia.fyi) by James Gilyead and Trys Mudford.
 *
 * @package FluidScale
 */

namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the complete fluid scale CSS string from a settings array.
 */
class Generator {

	/**
	 * Space step multipliers relative to space-s (1×).
	 * Source: docs/utopia-math.md — Space Scale section.
	 */
	const SPACE_MULTIPLIERS = [
		'3xs' => 0.25,
		'2xs' => 0.5,
		'xs'  => 0.75,
		's'   => 1.0,
		'm'   => 1.5,
		'l'   => 2.0,
		'xl'  => 3.0,
		'2xl' => 4.0,
		'3xl' => 6.0,
	];

	/**
	 * One-up pairs: consecutive space steps, always generated.
	 * Each entry is [ from, to ].
	 */
	const ONE_UP_PAIRS = [
		[ '3xs', '2xs' ],
		[ '2xs', 'xs' ],
		[ 'xs',  's'   ],
		[ 's',   'm'   ],
		[ 'm',   'l'   ],
		[ 'l',   'xl'  ],
		[ 'xl',  '2xl' ],
		[ '2xl', '3xl' ],
	];

	/** @var array Validated settings. */
	private array $settings;

	/**
	 * @param array $settings {
	 *     @type int   $min_viewport    Minimum viewport width in px.
	 *     @type int   $max_viewport    Maximum viewport width in px.
	 *     @type float $min_base        Base font size in px at min viewport.
	 *     @type float $max_base        Base font size in px at max viewport.
	 *     @type float $ratio           Scale ratio.
	 *     @type int   $negative_steps  Number of steps below base.
	 *     @type int   $positive_steps  Number of steps above base.
	 *     @type array $custom_pairs    [ [ 'from' => 's', 'to' => 'l' ], ... ]
	 *     @type int   $grid_max_width  Container max-width in px.
	 *     @type int   $grid_columns    Number of grid columns.
	 *     @type string $grid_gutter_pair  Space pair key to use as gutter (e.g. 's-l').
	 * }
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Generate the complete CSS string.
	 *
	 * @return string Full CSS output for the :root block and utility classes.
	 */
	public function generate(): string {
		$parts = [];

		$parts[] = $this->generate_type_scale();
		$parts[] = $this->generate_space_scale();
		$parts[] = $this->generate_grid();

		return implode( "\n\n", array_filter( $parts ) );
	}

	// -------------------------------------------------------------------------
	// Type Scale
	// -------------------------------------------------------------------------

	/**
	 * Generate the fluid type scale CSS block.
	 */
	private function generate_type_scale(): string {
		$s               = $this->settings;
		$negative_steps  = (int) $s['negative_steps'];
		$positive_steps  = (int) $s['positive_steps'];

		$lines = [];

		// Negative steps (step--1, step--2, ...)
		for ( $n = -1; $n >= -$negative_steps; $n-- ) {
			$prop    = '--step-' . $n; // e.g. --step--1 (double hyphen from CSS prop + negative number)
			$lines[] = "\t{$prop}: {$this->type_clamp( $n )};";
		}

		// Base and positive steps
		for ( $n = 0; $n <= $positive_steps; $n++ ) {
			$prop    = '--step-' . $n;
			$lines[] = "\t{$prop}: {$this->type_clamp( $n )};";
		}

		// Semantic aliases
		$lines[] = '';
		$lines   = array_merge( $lines, $this->semantic_type_aliases( $negative_steps, $positive_steps ) );

		return "/* === Fluid Scale: Type === */\n:root {\n" . implode( "\n", $lines ) . "\n}";
	}

	/**
	 * Compute the clamp() value for a given type step.
	 *
	 * Formula (from docs/utopia-math.md):
	 *   min_rem  = (min_base × ratio^n) / 16
	 *   max_rem  = (max_base × ratio^n) / 16
	 *   slope    = (max_rem - min_rem) / (max_vp - min_vp)
	 *   intercept = min_rem - slope × min_vp
	 *   preferred = {intercept}rem + {slope × 100}vw
	 *
	 * @param int $step Positive or negative step index.
	 * @return string  e.g. clamp(1.0000rem, 0.9130rem + 0.2174vw, 1.2500rem)
	 */
	private function type_clamp( int $step ): string {
		$s      = $this->settings;
		$ratio  = (float) $s['ratio'];
		$min_vp = (int) $s['min_viewport'];
		$max_vp = (int) $s['max_viewport'];

		$min_rem = round( ( (float) $s['min_base'] * ( $ratio ** $step ) ) / 16, 4 );
		$max_rem = round( ( (float) $s['max_base'] * ( $ratio ** $step ) ) / 16, 4 );

		return $this->build_clamp( $min_rem, $max_rem, $min_vp, $max_vp );
	}

	/**
	 * Semantic aliases derived from the step variables.
	 *
	 * @return string[]
	 */
	private function semantic_type_aliases( int $negative_steps, int $positive_steps ): array {
		// Map alias name => step number. Steps outside the configured range
		// fall back to the nearest available step.
		$step = function( int $n ) use ( $negative_steps, $positive_steps ): int {
			return max( -$negative_steps, min( $positive_steps, $n ) );
		};

		$aliases = [
			'--fs-xs'   => '--step-' . $step( -2 ),
			'--fs-sm'   => '--step-' . $step( -1 ),
			'--fs-base' => '--step-0',
			'--fs-md'   => '--step-' . $step( 1 ),
			'--fs-lg'   => '--step-' . $step( 2 ),
			'--fs-xl'   => '--step-' . $step( 3 ),
			'--fs-2xl'  => '--step-' . $step( 4 ),
			'--fs-3xl'  => '--step-' . $step( 5 ),
			'--fs-body' => '--step-0',
			'--fs-h6'   => '--step-' . $step( 1 ),
			'--fs-h5'   => '--step-' . $step( 1 ),
			'--fs-h4'   => '--step-' . $step( 2 ),
			'--fs-h3'   => '--step-' . $step( 3 ),
			'--fs-h2'   => '--step-' . $step( 4 ),
			'--fs-h1'   => '--step-' . $step( 5 ),
		];

		$lines = [ "\t/* Semantic aliases */" ];
		foreach ( $aliases as $alias => $ref ) {
			$lines[] = "\t{$alias}: var({$ref});";
		}

		return $lines;
	}

	// -------------------------------------------------------------------------
	// Space Scale
	// -------------------------------------------------------------------------

	/**
	 * Generate the fluid space scale CSS block.
	 */
	private function generate_space_scale(): string {
		$lines = [];

		// Individual steps
		$lines[] = "\t/* Individual space steps */";
		foreach ( self::SPACE_MULTIPLIERS as $name => $multiplier ) {
			$lines[] = "\t--space-{$name}: {$this->space_clamp( $multiplier )};";
		}

		// One-up pairs
		$lines[] = '';
		$lines[] = "\t/* One-up pairs */";
		foreach ( self::ONE_UP_PAIRS as [ $from, $to ] ) {
			$lines[] = "\t--space-{$from}-{$to}: {$this->space_pair_clamp( $from, $to )};";
		}

		// Custom pairs
		$custom_pairs = $this->settings['custom_pairs'] ?? [];
		if ( ! empty( $custom_pairs ) ) {
			$lines[] = '';
			$lines[] = "\t/* Custom pairs */";
			foreach ( $custom_pairs as $pair ) {
				$from = $pair['from'] ?? '';
				$to   = $pair['to']   ?? '';
				if ( $from && $to && isset( self::SPACE_MULTIPLIERS[ $from ], self::SPACE_MULTIPLIERS[ $to ] ) ) {
					$lines[] = "\t--space-{$from}-{$to}: {$this->space_pair_clamp( $from, $to )};";
				}
			}
		}

		return "/* === Fluid Scale: Space === */\n:root {\n" . implode( "\n", $lines ) . "\n}";
	}

	/**
	 * Compute the clamp() value for a space step given its multiplier.
	 *
	 * space-s (multiplier=1) equals step-0's min/max base sizes.
	 * All other steps scale proportionally from that same base.
	 *
	 * @param float $multiplier Multiplier relative to space-s.
	 */
	private function space_clamp( float $multiplier ): string {
		$s      = $this->settings;
		$min_vp = (int) $s['min_viewport'];
		$max_vp = (int) $s['max_viewport'];

		$min_rem = round( ( (float) $s['min_base'] / 16 ) * $multiplier, 4 );
		$max_rem = round( ( (float) $s['max_base'] / 16 ) * $multiplier, 4 );

		return $this->build_clamp( $min_rem, $max_rem, $min_vp, $max_vp );
	}

	/**
	 * Compute the clamp() value for a space pair.
	 *
	 * Pair formula: min value from the 'from' step, max value from the 'to' step.
	 * The slope and intercept are derived from those two boundary values.
	 *
	 * @param string $from Space step name (e.g. 's').
	 * @param string $to   Space step name (e.g. 'l').
	 */
	private function space_pair_clamp( string $from, string $to ): string {
		$s          = $this->settings;
		$min_vp     = (int) $s['min_viewport'];
		$max_vp     = (int) $s['max_viewport'];
		$min_base   = (float) $s['min_base'];
		$max_base   = (float) $s['max_base'];

		$from_mult = self::SPACE_MULTIPLIERS[ $from ];
		$to_mult   = self::SPACE_MULTIPLIERS[ $to ];

		// Min boundary: 'from' step at min viewport base
		$min_rem = round( ( $min_base / 16 ) * $from_mult, 4 );
		// Max boundary: 'to' step at max viewport base
		$max_rem = round( ( $max_base / 16 ) * $to_mult, 4 );

		return $this->build_clamp( $min_rem, $max_rem, $min_vp, $max_vp );
	}

	// -------------------------------------------------------------------------
	// Grid
	// -------------------------------------------------------------------------

	/**
	 * Generate grid variables and utility classes.
	 */
	private function generate_grid(): string {
		$s            = $this->settings;
		$max_width_px = (int) ( $s['grid_max_width'] ?? 1240 );
		$columns      = (int) ( $s['grid_columns']   ?? 12 );
		$gutter_pair  = $s['grid_gutter_pair']        ?? 's-l';
		$max_width_rem = round( $max_width_px / 16, 4 );

		// Validate gutter pair exists — fall back to s-l
		$pair_parts = explode( '-', $gutter_pair, 2 );
		if ( count( $pair_parts ) !== 2 ||
			 ! isset( self::SPACE_MULTIPLIERS[ $pair_parts[0] ], self::SPACE_MULTIPLIERS[ $pair_parts[1] ] ) ) {
			$gutter_pair  = 's-l';
			$pair_parts   = [ 's', 'l' ];
		}

		// The gutter var() references the space pair; fallback is its raw clamp value.
		$gutter_fallback = $this->space_pair_clamp( $pair_parts[0], $pair_parts[1] );

		$root_lines = [
			"\t--grid-max-width: {$max_width_rem}rem;",
			"\t--grid-gutter: var(--space-{$gutter_pair}, {$gutter_fallback});",
			"\t--grid-columns: {$columns};",
		];

		$root_block = "/* === Fluid Scale: Grid === */\n:root {\n" . implode( "\n", $root_lines ) . "\n}";

		$utility_classes = <<<CSS

.u-container {
	max-width: var(--grid-max-width);
	padding-inline: var(--grid-gutter);
	margin-inline: auto;
}

.u-grid {
	display: grid;
	gap: var(--grid-gutter);
}
CSS;

		return $root_block . $utility_classes;
	}

	// -------------------------------------------------------------------------
	// Shared clamp builder
	// -------------------------------------------------------------------------

	/**
	 * Build a CSS clamp() value from min/max rem values and viewport bounds.
	 *
	 * Formula (from docs/utopia-math.md):
	 *   slope     = (max_rem - min_rem) / (max_vp - min_vp)
	 *   intercept = min_rem - slope × min_vp
	 *   preferred = {intercept}rem + {slope × 100}vw
	 *
	 * All values rounded to 4 decimal places.
	 *
	 * @param float $min_rem  Minimum value in rem.
	 * @param float $max_rem  Maximum value in rem.
	 * @param int   $min_vp   Minimum viewport in px.
	 * @param int   $max_vp   Maximum viewport in px.
	 * @return string  e.g. clamp(1.0000rem, 0.9130rem + 0.2174vw, 1.2500rem)
	 */
	private function build_clamp( float $min_rem, float $max_rem, int $min_vp, int $max_vp ): string {
		// Slope is dimensionless (px/px), computed from px values so vw conversion is correct.
		// intercept is then converted back to rem. This matches Utopia's formula exactly.
		$min_px    = $min_rem * 16;
		$max_px    = $max_rem * 16;
		$slope     = ( $max_px - $min_px ) / ( $max_vp - $min_vp );
		$intercept = ( $min_px - $slope * $min_vp ) / 16;

		$slope_vw  = round( $slope * 100, 4 );
		$intercept = round( $intercept, 4 );

		// Format: drop trailing zeros but keep at least one decimal place for clarity.
		$min_str   = $this->format_rem( $min_rem );
		$max_str   = $this->format_rem( $max_rem );
		$int_str   = $this->format_rem( $intercept );
		$slope_str = $this->format_vw( $slope_vw );

		// Handle negative intercept: clamp(Xrem, -Yrem + Zvw, Wrem)
		$preferred = "{$int_str} + {$slope_str}";
		if ( $intercept < 0 ) {
			// Already negative, the + operator still works in CSS
			$preferred = "{$int_str} + {$slope_str}";
		}

		return "clamp({$min_str}, {$preferred}, {$max_str})";
	}

	/**
	 * Format a rem value: 4 decimal places, strip unnecessary trailing zeros,
	 * always show at least one decimal, append 'rem'.
	 */
	private function format_rem( float $value ): string {
		return rtrim( rtrim( number_format( $value, 4, '.', '' ), '0' ), '.' ) . 'rem';
	}

	/**
	 * Format a vw value: 4 decimal places, strip trailing zeros, append 'vw'.
	 */
	private function format_vw( float $value ): string {
		return rtrim( rtrim( number_format( $value, 4, '.', '' ), '0' ), '.' ) . 'vw';
	}

	// -------------------------------------------------------------------------
	// Static helpers (for JS preview parity checks and testing)
	// -------------------------------------------------------------------------

	/**
	 * Return all space step names in order (useful for building UI dropdowns).
	 *
	 * @return string[]
	 */
	public static function space_step_names(): array {
		return array_keys( self::SPACE_MULTIPLIERS );
	}

	/**
	 * Compute a single type clamp value from raw parameters.
	 * Exposed statically for unit testing without instantiation.
	 */
	public static function compute_type_clamp(
		int $step,
		float $min_base,
		float $max_base,
		float $ratio,
		int $min_vp,
		int $max_vp
	): string {
		$instance = new self( [
			'min_viewport'   => $min_vp,
			'max_viewport'   => $max_vp,
			'min_base'       => $min_base,
			'max_base'       => $max_base,
			'ratio'          => $ratio,
			'negative_steps' => 2,
			'positive_steps' => 5,
			'custom_pairs'   => [],
		] );
		return $instance->type_clamp( $step );
	}
}
