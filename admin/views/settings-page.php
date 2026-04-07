<?php
/**
 * Settings page view template.
 *
 * Variables available from AdminPage::render_page():
 *   $settings    array  Current settings from Settings::get()
 *   $builders    array  Active builder slugs from BuilderDetector
 *   $file_exists bool   Whether the generated CSS file exists
 *   $messages    array  Admin notices [ ['type'=>'success|error', 'message'=>'...'] ]
 *
 * @package FluidScale
 */

namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$space_steps  = Generator::space_step_names();
$ratio_options = [
	'1.067' => __( 'Minor Second (1.067)', 'fluid-scale' ),
	'1.125' => __( 'Major Second (1.125)', 'fluid-scale' ),
	'1.200' => __( 'Minor Third (1.200)', 'fluid-scale' ),
	'1.250' => __( 'Major Third (1.250)', 'fluid-scale' ),
	'1.333' => __( 'Perfect Fourth (1.333)', 'fluid-scale' ),
	'1.414' => __( 'Augmented Fourth (1.414)', 'fluid-scale' ),
	'1.500' => __( 'Perfect Fifth (1.500)', 'fluid-scale' ),
	'1.618' => __( 'Golden Ratio (1.618)', 'fluid-scale' ),
];
?>
<div class="wrap fluid-scale-wrap">
	<h1><?php esc_html_e( 'Fluid Scale', 'fluid-scale' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Configure your fluid design system. Changes take effect after saving — the generated CSS file is served to every page on your site.', 'fluid-scale' ); ?>
	</p>

	<?php foreach ( $messages as $notice ) : ?>
	<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
		<p><?php echo esc_html( $notice['message'] ); ?></p>
	</div>
	<?php endforeach; ?>

	<?php if ( $file_exists ) : ?>
	<p class="fluid-scale-status fluid-scale-status--ok">
		<?php
		$last = (int) $settings['last_generated'];
		printf(
			/* translators: %s: human-readable date */
			esc_html__( 'CSS file generated %s.', 'fluid-scale' ),
			$last ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last ) ) : esc_html__( 'previously', 'fluid-scale' )
		);
		?>
	</p>
	<?php else : ?>
	<p class="fluid-scale-status fluid-scale-status--warn">
		<?php esc_html_e( 'No CSS file found. Save your settings to generate it.', 'fluid-scale' ); ?>
	</p>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="fluid-scale-form">
		<input type="hidden" name="action" value="fluid_scale_save">
		<?php wp_nonce_field( 'fluid_scale_save_settings', 'fluid_scale_nonce' ); ?>

		<!-- ================================================================ -->
		<!-- SCALE PARAMETERS                                                  -->
		<!-- ================================================================ -->
		<h2><?php esc_html_e( 'Scale Parameters', 'fluid-scale' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Define the viewport range and base sizes. These settings apply to both the type and space scales.', 'fluid-scale' ); ?></p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="fs-min-viewport"><?php esc_html_e( 'Min Viewport', 'fluid-scale' ); ?></label></th>
				<td>
					<input type="number" id="fs-min-viewport" name="fluid_scale[min_viewport]"
						value="<?php echo esc_attr( $settings['min_viewport'] ); ?>"
						min="1" max="2000" step="1" class="small-text fs-param">
					<span class="description">px</span>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fs-max-viewport"><?php esc_html_e( 'Max Viewport', 'fluid-scale' ); ?></label></th>
				<td>
					<input type="number" id="fs-max-viewport" name="fluid_scale[max_viewport]"
						value="<?php echo esc_attr( $settings['max_viewport'] ); ?>"
						min="1" max="5000" step="1" class="small-text fs-param">
					<span class="description">px</span>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fs-min-base"><?php esc_html_e( 'Min Base Size', 'fluid-scale' ); ?></label></th>
				<td>
					<input type="number" id="fs-min-base" name="fluid_scale[min_base]"
						value="<?php echo esc_attr( $settings['min_base'] ); ?>"
						min="8" max="32" step="0.5" class="small-text fs-param">
					<span class="description">px — <?php esc_html_e( 'base (step-0) at min viewport', 'fluid-scale' ); ?></span>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fs-max-base"><?php esc_html_e( 'Max Base Size', 'fluid-scale' ); ?></label></th>
				<td>
					<input type="number" id="fs-max-base" name="fluid_scale[max_base]"
						value="<?php echo esc_attr( $settings['max_base'] ); ?>"
						min="8" max="40" step="0.5" class="small-text fs-param">
					<span class="description">px — <?php esc_html_e( 'base (step-0) at max viewport', 'fluid-scale' ); ?></span>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fs-ratio"><?php esc_html_e( 'Scale Ratio', 'fluid-scale' ); ?></label></th>
				<td>
					<select id="fs-ratio" name="fluid_scale[ratio]" class="fs-param">
						<?php foreach ( $ratio_options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) $settings['ratio'], $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fs-negative-steps"><?php esc_html_e( 'Steps Below Base', 'fluid-scale' ); ?></label></th>
				<td>
					<input type="number" id="fs-negative-steps" name="fluid_scale[negative_steps]"
						value="<?php echo esc_attr( $settings['negative_steps'] ); ?>"
						min="1" max="5" step="1" class="small-text fs-param">
					<span class="description"><?php esc_html_e( 'generates step--1 through step--N', 'fluid-scale' ); ?></span>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fs-positive-steps"><?php esc_html_e( 'Steps Above Base', 'fluid-scale' ); ?></label></th>
				<td>
					<input type="number" id="fs-positive-steps" name="fluid_scale[positive_steps]"
						value="<?php echo esc_attr( $settings['positive_steps'] ); ?>"
						min="1" max="10" step="1" class="small-text fs-param">
					<span class="description"><?php esc_html_e( 'generates step-1 through step-N', 'fluid-scale' ); ?></span>
				</td>
			</tr>
		</table>

		<!-- ================================================================ -->
		<!-- GRID                                                              -->
		<!-- ================================================================ -->
		<h2><?php esc_html_e( 'Grid', 'fluid-scale' ); ?></h2>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="fs-grid-max-width"><?php esc_html_e( 'Container Max Width', 'fluid-scale' ); ?></label></th>
				<td>
					<input type="number" id="fs-grid-max-width" name="fluid_scale[grid_max_width]"
						value="<?php echo esc_attr( $settings['grid_max_width'] ); ?>"
						min="100" max="5000" step="1" class="small-text fs-param">
					<span class="description">px — <code>--grid-max-width</code></span>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fs-grid-columns"><?php esc_html_e( 'Grid Columns', 'fluid-scale' ); ?></label></th>
				<td>
					<input type="number" id="fs-grid-columns" name="fluid_scale[grid_columns]"
						value="<?php echo esc_attr( $settings['grid_columns'] ); ?>"
						min="1" max="24" step="1" class="small-text fs-param">
					<span class="description"><code>--grid-columns</code></span>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fs-grid-gutter"><?php esc_html_e( 'Gutter Space Pair', 'fluid-scale' ); ?></label></th>
				<td>
					<select id="fs-grid-gutter" name="fluid_scale[grid_gutter_pair]" class="fs-param">
						<?php foreach ( Generator::ONE_UP_PAIRS as [ $from, $to ] ) :
							$val = "{$from}-{$to}"; ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $settings['grid_gutter_pair'], $val ); ?>>
							<?php echo esc_html( "--space-{$val}" ); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<span class="description"><code>--grid-gutter</code></span>
				</td>
			</tr>
		</table>

		<!-- ================================================================ -->
		<!-- CUSTOM SPACE PAIRS                                                -->
		<!-- ================================================================ -->
		<h2><?php esc_html_e( 'Custom Space Pairs', 'fluid-scale' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Add pairs to generate additional --space-{from}-{to} variables. Useful for cases where you want a value that starts at one step size and ends at another.', 'fluid-scale' ); ?>
		</p>

		<div id="fs-custom-pairs">
			<?php foreach ( $settings['custom_pairs'] as $pair ) : ?>
			<div class="fs-pair-row">
				<select name="fluid_scale_pair_from[]" class="fs-pair-from">
					<?php foreach ( $space_steps as $step ) : ?>
					<option value="<?php echo esc_attr( $step ); ?>" <?php selected( $pair['from'], $step ); ?>>
						<?php echo esc_html( "--space-{$step}" ); ?>
					</option>
					<?php endforeach; ?>
				</select>
				<span class="fs-pair-arrow" aria-hidden="true">→</span>
				<select name="fluid_scale_pair_to[]" class="fs-pair-to">
					<?php foreach ( $space_steps as $step ) : ?>
					<option value="<?php echo esc_attr( $step ); ?>" <?php selected( $pair['to'], $step ); ?>>
						<?php echo esc_html( "--space-{$step}" ); ?>
					</option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button fs-pair-remove"><?php esc_html_e( 'Remove', 'fluid-scale' ); ?></button>
			</div>
			<?php endforeach; ?>
		</div>

		<button type="button" class="button" id="fs-add-pair">
			<?php esc_html_e( '+ Add Pair', 'fluid-scale' ); ?>
		</button>

		<!-- Hidden template for JS to clone -->
		<template id="fs-pair-template">
			<div class="fs-pair-row">
				<select name="fluid_scale_pair_from[]" class="fs-pair-from">
					<?php foreach ( $space_steps as $step ) : ?>
					<option value="<?php echo esc_attr( $step ); ?>"><?php echo esc_html( "--space-{$step}" ); ?></option>
					<?php endforeach; ?>
				</select>
				<span class="fs-pair-arrow" aria-hidden="true">→</span>
				<select name="fluid_scale_pair_to[]" class="fs-pair-to">
					<?php foreach ( $space_steps as $step ) : ?>
					<option value="<?php echo esc_attr( $step ); ?>"><?php echo esc_html( "--space-{$step}" ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button fs-pair-remove"><?php esc_html_e( 'Remove', 'fluid-scale' ); ?></button>
			</div>
		</template>

		<!-- ================================================================ -->
		<!-- BUILDER MAPPING                                                   -->
		<!-- ================================================================ -->
		<?php if ( ! empty( $builders ) ) : ?>
		<h2><?php esc_html_e( 'Builder Mapping', 'fluid-scale' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'A supported builder was detected. Enable mapping to add a variable mapping block to the generated CSS.', 'fluid-scale' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Variable Mapping', 'fluid-scale' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="fluid_scale[builder_mapping]" value="auto"
								<?php checked( $settings['builder_mapping'], 'auto' ); ?>>
							<?php esc_html_e( 'Auto (enable for detected builders)', 'fluid-scale' ); ?>
						</label><br>
						<?php foreach ( $builders as $builder ) : ?>
						<label>
							<input type="radio" name="fluid_scale[builder_mapping]" value="<?php echo esc_attr( $builder ); ?>"
								<?php checked( $settings['builder_mapping'], $builder ); ?>>
							<?php printf(
								/* translators: %s: builder name */
								esc_html__( '%s only', 'fluid-scale' ),
								esc_html( BuilderMappings::get_label( $builder ) )
							); ?>
						</label><br>
						<?php endforeach; ?>
						<label>
							<input type="radio" name="fluid_scale[builder_mapping]" value="none"
								<?php checked( $settings['builder_mapping'], 'none' ); ?>>
							<?php esc_html_e( 'Disabled', 'fluid-scale' ); ?>
						</label>
						<?php foreach ( $builders as $builder ) :
							$desc = BuilderMappings::get_description( $builder );
							if ( $desc ) : ?>
						<p class="description"><?php echo esc_html( $desc ); ?></p>
						<?php endif; endforeach; ?>
					</fieldset>
				</td>
			</tr>
		</table>
		<?php endif; ?>

		<?php submit_button( __( 'Save &amp; Regenerate', 'fluid-scale' ) ); ?>
	</form>

	<!-- ================================================================== -->
	<!-- PREVIEW                                                              -->
	<!-- ================================================================== -->
	<hr>
	<h2><?php esc_html_e( 'Preview', 'fluid-scale' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Live preview updates as you change parameters above. Values shown at a representative 1024px viewport.', 'fluid-scale' ); ?>
	</p>

	<div id="fs-preview">
		<div id="fs-preview-type">
			<h3><?php esc_html_e( 'Type Scale', 'fluid-scale' ); ?></h3>
			<div id="fs-type-specimen">
				<!-- Populated by admin.js -->
			</div>
		</div>
		<div id="fs-preview-space">
			<h3><?php esc_html_e( 'Space Scale', 'fluid-scale' ); ?></h3>
			<div id="fs-space-specimen">
				<!-- Populated by admin.js -->
			</div>
		</div>
	</div>

	<!-- ================================================================== -->
	<!-- GENERATED CSS OUTPUT (readonly)                                      -->
	<!-- ================================================================== -->
	<hr>
	<h2><?php esc_html_e( 'Generated CSS', 'fluid-scale' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Read-only. Updates after saving.', 'fluid-scale' ); ?>
		<?php if ( $file_exists ) : ?>
		<a href="<?php echo esc_url( FileWriter::get_url() ); ?>" target="_blank" rel="noopener">
			<?php esc_html_e( 'View file ↗', 'fluid-scale' ); ?>
		</a>
		<?php endif; ?>
	</p>
	<textarea id="fs-css-output" class="large-text code" rows="20" readonly aria-label="<?php esc_attr_e( 'Generated CSS', 'fluid-scale' ); ?>">
		<?php
		if ( $file_exists ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			echo esc_textarea( file_get_contents( FileWriter::get_dir() . '/fluid-scale.css' ) );
		} else {
			echo esc_textarea( __( 'No CSS file yet. Save your settings to generate it.', 'fluid-scale' ) );
		}
		?>
	</textarea>

</div><!-- .fluid-scale-wrap -->
