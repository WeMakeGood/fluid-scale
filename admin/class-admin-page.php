<?php
/**
 * Admin Settings Page
 *
 * Registers Settings > Fluid Scale, handles form submission, and enqueues
 * admin assets only on the plugin's own page.
 *
 * @package FluidScale
 */

namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminPage {

	const PAGE_SLUG = 'fluid-scale';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu',            [ $this, 'register_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_fluid_scale_save', [ $this, 'handle_save' ] );
	}

	/**
	 * Add Settings > Fluid Scale submenu.
	 */
	public function register_page(): void {
		add_options_page(
			__( 'Fluid Scale Settings', 'fluid-scale' ),
			__( 'Fluid Scale', 'fluid-scale' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Enqueue admin JS and CSS only on the Fluid Scale settings page.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'fluid-scale-admin',
			FLUID_SCALE_URL . 'assets/css/admin.css',
			[],
			FLUID_SCALE_VERSION
		);

		wp_enqueue_script(
			'fluid-scale-admin',
			FLUID_SCALE_URL . 'assets/js/admin.js',
			[],
			FLUID_SCALE_VERSION,
			true // Load in footer
		);

		// Pass PHP data to JS for the live preview.
		wp_localize_script(
			'fluid-scale-admin',
			'fluidScaleAdmin',
			[
				'spaceSteps'   => Generator::space_step_names(),
				'defaultPairs' => $this->get_one_up_pair_keys(),
				'nonce'        => wp_create_nonce( 'fluid_scale_preview' ),
			]
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings  = Settings::get();
		$builders  = BuilderDetector::get_active_builders();
		$file_exists = FileWriter::exists();
		$messages  = $this->get_admin_notices();

		include FLUID_SCALE_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Handle settings form submission via admin-post.php.
	 */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'fluid-scale' ) );
		}

		check_admin_referer( 'fluid_scale_save_settings', 'fluid_scale_nonce' );

		$raw = $_POST['fluid_scale'] ?? [];
		if ( ! is_array( $raw ) ) {
			$raw = [];
		}

		// Parse custom pairs from the paired arrays posted by the form
		$raw['custom_pairs'] = $this->parse_custom_pairs_from_post( $_POST );

		$settings = Settings::save( $raw );

		// Regenerate CSS
		$generator = new Generator( $settings );
		$css       = $generator->generate();

		// Append builder mapping if applicable
		$mapping_setting = $settings['builder_mapping'] ?? 'auto';
		$active_builders = BuilderDetector::get_active_builders();

		$builders_to_map = [];
		if ( 'auto' === $mapping_setting ) {
			$builders_to_map = $active_builders;
		} elseif ( in_array( $mapping_setting, [ 'divi5', 'bricks' ], true ) ) {
			$builders_to_map = [ $mapping_setting ];
		}

		foreach ( $builders_to_map as $builder ) {
			$mapping = BuilderMappings::get_mapping_css( $builder );
			if ( $mapping ) {
				$css .= "\n\n" . $mapping;
			}
		}

		$result = FileWriter::write( $css );

		if ( is_wp_error( $result ) ) {
			set_transient( 'fluid_scale_notice', [ 'type' => 'error', 'message' => $result->get_error_message() ], 60 );
		} else {
			set_transient( 'fluid_scale_notice', [ 'type' => 'success', 'message' => __( 'Settings saved and CSS file regenerated.', 'fluid-scale' ) ], 60 );
		}

		wp_safe_redirect(
			add_query_arg(
				[ 'page' => self::PAGE_SLUG ],
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Parse custom pairs from flat POST arrays into [ ['from'=>..., 'to'=>...] ].
	 * The form posts fluid_scale_pair_from[] and fluid_scale_pair_to[] in parallel.
	 */
	private function parse_custom_pairs_from_post( array $post ): array {
		$froms = $post['fluid_scale_pair_from'] ?? [];
		$tos   = $post['fluid_scale_pair_to']   ?? [];

		if ( ! is_array( $froms ) || ! is_array( $tos ) ) {
			return [];
		}

		$pairs = [];
		$count = min( count( $froms ), count( $tos ) );
		for ( $i = 0; $i < $count; $i++ ) {
			$from = sanitize_text_field( $froms[ $i ] );
			$to   = sanitize_text_field( $tos[ $i ] );
			if ( $from && $to ) {
				$pairs[] = [ 'from' => $from, 'to' => $to ];
			}
		}
		return $pairs;
	}

	/**
	 * Retrieve and clear any queued admin notices.
	 */
	private function get_admin_notices(): array {
		$notice = get_transient( 'fluid_scale_notice' );
		if ( $notice ) {
			delete_transient( 'fluid_scale_notice' );
			return [ $notice ];
		}
		return [];
	}

	/**
	 * Return one-up pair keys for JS (e.g. ['3xs-2xs', '2xs-xs', ...]).
	 */
	private function get_one_up_pair_keys(): array {
		return array_map(
			fn( $pair ) => $pair[0] . '-' . $pair[1],
			Generator::ONE_UP_PAIRS
		);
	}
}
