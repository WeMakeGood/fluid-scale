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
 * Layout: two-column. Left = form. Right = sticky preview panel.
 *
 * @package FluidScale
 */

namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$space_steps   = Generator::space_step_names();
$ratio_options = [
	'1.067' => __( 'Minor Second — 1.067 — Very subtle. Steps are close together. Good for dense UI.', 'fluid-scale' ),
	'1.125' => __( 'Major Second — 1.125 — Gentle contrast. Good for content-heavy sites.', 'fluid-scale' ),
	'1.200' => __( 'Minor Third — 1.200 — Balanced. A safe default for most sites.', 'fluid-scale' ),
	'1.250' => __( 'Major Third — 1.250 — Clear hierarchy without being dramatic.', 'fluid-scale' ),
	'1.333' => __( 'Perfect Fourth — 1.333 — Strong contrast. Good for editorial sites.', 'fluid-scale' ),
	'1.414' => __( 'Augmented Fourth — 1.414 — Bold jumps between steps.', 'fluid-scale' ),
	'1.500' => __( 'Perfect Fifth — 1.500 — Very dramatic. Use with restraint.', 'fluid-scale' ),
	'1.618' => __( 'Golden Ratio — 1.618 — Maximum drama. Best for hero/display contexts.', 'fluid-scale' ),
];
?>
<div class="wrap fluid-scale-wrap">
	<h1><?php esc_html_e( 'Fluid Scale', 'fluid-scale' ); ?></h1>

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

	<!-- How this works intro -->
	<div class="fs-intro">
		<h2 class="fs-intro-title"><?php esc_html_e( 'How this works', 'fluid-scale' ); ?></h2>
		<p><?php esc_html_e( 'Fluid Scale generates a set of CSS custom properties — variables like --step-0, --space-m, --grid-gutter — and injects them into every page on your site before any theme or builder CSS loads. You use those variables anywhere CSS is accepted.', 'fluid-scale' ); ?></p>
		<p><?php esc_html_e( 'The core idea: instead of defining font sizes and spacing at fixed pixel values, you define two versions of your scale — one for small screens, one for large screens — and let the browser interpolate smoothly between them. No breakpoints needed.', 'fluid-scale' ); ?></p>
		<p><?php esc_html_e( 'The settings below define those two versions. The live preview on the right shows what the type scale looks like at a 1024px viewport. Save when you\'re happy — the preview updates instantly as you adjust values.', 'fluid-scale' ); ?></p>
	</div>

	<!-- Two-column layout: form left, preview right -->
	<div class="fs-layout">

		<!-- ============================================================ -->
		<!-- LEFT: SETTINGS FORM                                           -->
		<!-- ============================================================ -->
		<div class="fs-layout-form">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="fluid-scale-form">
				<input type="hidden" name="action" value="fluid_scale_save">
				<?php wp_nonce_field( 'fluid_scale_save_settings', 'fluid_scale_nonce' ); ?>

				<!-- ================================================== -->
				<!-- VIEWPORT RANGE                                        -->
				<!-- ================================================== -->
				<h2><?php esc_html_e( 'Viewport Range', 'fluid-scale' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'The smallest and largest screen widths you\'re designing for. Your scale will interpolate smoothly between these two points. Outside this range, the scale stays fixed at the min or max values.', 'fluid-scale' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fs-min-viewport"><?php esc_html_e( 'Smallest screen', 'fluid-scale' ); ?></label></th>
						<td>
							<input type="number" id="fs-min-viewport" name="fluid_scale[min_viewport]"
								value="<?php echo esc_attr( $settings['min_viewport'] ); ?>"
								min="1" max="2000" step="1" class="small-text fs-param"> px
							<p class="description"><?php esc_html_e( '320px is a common mobile floor. This is where your minimum font sizes and spacing will be used.', 'fluid-scale' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fs-max-viewport"><?php esc_html_e( 'Largest screen', 'fluid-scale' ); ?></label></th>
						<td>
							<input type="number" id="fs-max-viewport" name="fluid_scale[max_viewport]"
								value="<?php echo esc_attr( $settings['max_viewport'] ); ?>"
								min="1" max="5000" step="1" class="small-text fs-param"> px
							<p class="description"><?php esc_html_e( '1240–1440px covers most desktop layouts. At this width and wider, your maximum font sizes and spacing kick in.', 'fluid-scale' ); ?></p>
						</td>
					</tr>
				</table>

				<!-- ================================================== -->
				<!-- BASE FONT SIZE                                        -->
				<!-- ================================================== -->
				<h2><?php esc_html_e( 'Body Text Size', 'fluid-scale' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Your base (body) font size at the smallest and largest screens. Every other step in the scale — headings, captions, spacing — is calculated relative to this. All other sizes grow and shrink proportionally when you change these.', 'fluid-scale' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fs-min-base"><?php esc_html_e( 'Body size on mobile', 'fluid-scale' ); ?></label></th>
						<td>
							<input type="number" id="fs-min-base" name="fluid_scale[min_base]"
								value="<?php echo esc_attr( $settings['min_base'] ); ?>"
								min="8" max="32" step="0.5" class="small-text fs-param"> px
							<p class="description"><?php esc_html_e( 'The size of --step-0 (body text) at your smallest screen. 15–16px is typical.', 'fluid-scale' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fs-max-base"><?php esc_html_e( 'Body size on desktop', 'fluid-scale' ); ?></label></th>
						<td>
							<input type="number" id="fs-max-base" name="fluid_scale[max_base]"
								value="<?php echo esc_attr( $settings['max_base'] ); ?>"
								min="8" max="40" step="0.5" class="small-text fs-param"> px
							<p class="description"><?php esc_html_e( 'The size of --step-0 (body text) at your largest screen. Setting this a few px larger than mobile gives text room to breathe on wide displays. 18–20px is typical.', 'fluid-scale' ); ?></p>
						</td>
					</tr>
				</table>

				<!-- ================================================== -->
				<!-- SCALE RATIO                                           -->
				<!-- ================================================== -->
				<h2><?php esc_html_e( 'Scale Ratio', 'fluid-scale' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Controls how much bigger each step gets relative to the one below it. A smaller ratio means steps are closer together — subtle hierarchy. A larger ratio creates dramatic size jumps. Watch the preview as you change this.', 'fluid-scale' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fs-ratio"><?php esc_html_e( 'Ratio', 'fluid-scale' ); ?></label></th>
						<td>
							<select id="fs-ratio" name="fluid_scale[ratio]" class="fs-param fs-ratio-select">
								<?php foreach ( $ratio_options as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) $settings['ratio'], $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<!-- ================================================== -->
				<!-- NUMBER OF STEPS                                       -->
				<!-- ================================================== -->
				<h2><?php esc_html_e( 'Number of Steps', 'fluid-scale' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'How many font size steps to generate above and below body text. Most sites need 2 steps below (for captions and small labels) and 5 steps above (for subheadings through hero text). The preview shows all generated steps.', 'fluid-scale' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fs-negative-steps"><?php esc_html_e( 'Steps below body text', 'fluid-scale' ); ?></label></th>
						<td>
							<input type="number" id="fs-negative-steps" name="fluid_scale[negative_steps]"
								value="<?php echo esc_attr( $settings['negative_steps'] ); ?>"
								min="1" max="5" step="1" class="small-text fs-param">
							<p class="description"><?php esc_html_e( 'Generates --step--1, --step--2, etc. Use these for captions, labels, fine print. 2 is usually enough.', 'fluid-scale' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fs-positive-steps"><?php esc_html_e( 'Steps above body text', 'fluid-scale' ); ?></label></th>
						<td>
							<input type="number" id="fs-positive-steps" name="fluid_scale[positive_steps]"
								value="<?php echo esc_attr( $settings['positive_steps'] ); ?>"
								min="1" max="10" step="1" class="small-text fs-param">
							<p class="description"><?php esc_html_e( 'Generates --step-1 through --step-N. --step-5 becomes your largest heading or hero text. 5 covers most layouts.', 'fluid-scale' ); ?></p>
						</td>
					</tr>
				</table>

				<!-- ================================================== -->
				<!-- GRID                                                  -->
				<!-- ================================================== -->
				<h2><?php esc_html_e( 'Grid', 'fluid-scale' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Generates three variables: --grid-max-width (your container width), --grid-columns (column count), and --grid-gutter (the gap between columns, which uses a fluid space value so it grows with the screen). Also outputs .u-container and .u-grid utility classes you can apply directly in your builder.', 'fluid-scale' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fs-grid-max-width"><?php esc_html_e( 'Container max width', 'fluid-scale' ); ?></label></th>
						<td>
							<input type="number" id="fs-grid-max-width" name="fluid_scale[grid_max_width]"
								value="<?php echo esc_attr( $settings['grid_max_width'] ); ?>"
								min="100" max="5000" step="1" class="small-text fs-param"> px
							<p class="description"><?php esc_html_e( 'The maximum width of your main content area. Match this to your theme or builder\'s container setting. Used as --grid-max-width.', 'fluid-scale' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fs-grid-columns"><?php esc_html_e( 'Columns', 'fluid-scale' ); ?></label></th>
						<td>
							<input type="number" id="fs-grid-columns" name="fluid_scale[grid_columns]"
								value="<?php echo esc_attr( $settings['grid_columns'] ); ?>"
								min="1" max="24" step="1" class="small-text fs-param">
							<p class="description"><?php esc_html_e( 'Number of grid columns. 12 is standard. Used as --grid-columns.', 'fluid-scale' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fs-grid-gutter"><?php esc_html_e( 'Gutter size', 'fluid-scale' ); ?></label></th>
						<td>
							<select id="fs-grid-gutter" name="fluid_scale[grid_gutter_pair]" class="fs-param">
								<?php foreach ( Generator::ONE_UP_PAIRS as [ $from, $to ] ) :
									$val = "{$from}-{$to}"; ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $settings['grid_gutter_pair'], $val ); ?>>
									<?php echo esc_html( "--space-{$val}" ); ?>
								</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'The space between grid columns. Uses a fluid space pair — the gutter grows between the two selected sizes as the screen widens. --space-s-l (small on mobile, large on desktop) is a good default. Used as --grid-gutter.', 'fluid-scale' ); ?></p>
						</td>
					</tr>
				</table>

				<!-- ================================================== -->
				<!-- CUSTOM SPACE PAIRS                                    -->
				<!-- ================================================== -->
				<h2><?php esc_html_e( 'Custom Space Pairs', 'fluid-scale' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Space pairs generate a spacing value that starts at one size on mobile and ends at a different size on desktop — a bigger range than any single step alone. Useful for section padding, hero margins, or any spacing that needs to change dramatically between screen sizes.', 'fluid-scale' ); ?>
					<br><br>
					<?php esc_html_e( 'Example: --space-s-xl starts at "small" spacing on mobile and grows to "extra-large" on desktop. The one-up pairs (like --space-s-m) are already generated automatically. Add custom pairs here for non-adjacent combinations.', 'fluid-scale' ); ?>
				</p>

				<div id="fs-custom-pairs">
					<?php foreach ( $settings['custom_pairs'] as $pair ) : ?>
					<div class="fs-pair-row">
						<label class="screen-reader-text"><?php esc_html_e( 'From', 'fluid-scale' ); ?></label>
						<select name="fluid_scale_pair_from[]" class="fs-pair-from">
							<?php foreach ( $space_steps as $step ) : ?>
							<option value="<?php echo esc_attr( $step ); ?>" <?php selected( $pair['from'], $step ); ?>>
								<?php echo esc_html( "--space-{$step}" ); ?>
							</option>
							<?php endforeach; ?>
						</select>
						<span class="fs-pair-arrow" aria-hidden="true">→</span>
						<label class="screen-reader-text"><?php esc_html_e( 'To', 'fluid-scale' ); ?></label>
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

				<template id="fs-pair-template">
					<div class="fs-pair-row">
						<label class="screen-reader-text"><?php esc_html_e( 'From', 'fluid-scale' ); ?></label>
						<select name="fluid_scale_pair_from[]" class="fs-pair-from">
							<?php foreach ( $space_steps as $step ) : ?>
							<option value="<?php echo esc_attr( $step ); ?>"><?php echo esc_html( "--space-{$step}" ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="fs-pair-arrow" aria-hidden="true">→</span>
						<label class="screen-reader-text"><?php esc_html_e( 'To', 'fluid-scale' ); ?></label>
						<select name="fluid_scale_pair_to[]" class="fs-pair-to">
							<?php foreach ( $space_steps as $step ) : ?>
							<option value="<?php echo esc_attr( $step ); ?>"><?php echo esc_html( "--space-{$step}" ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="button fs-pair-remove"><?php esc_html_e( 'Remove', 'fluid-scale' ); ?></button>
					</div>
				</template>

				<!-- ================================================== -->
				<!-- BUILDER MAPPING                                       -->
				<!-- ================================================== -->
				<?php if ( ! empty( $builders ) ) : ?>
				<h2><?php esc_html_e( 'Builder Mapping', 'fluid-scale' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'A supported page builder was detected. Enabling mapping appends a block to the generated CSS that connects Fluid Scale variables to your builder\'s own variable names — so builder controls that reference those names automatically use your fluid scale values.', 'fluid-scale' ); ?>
				</p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Variable Mapping', 'fluid-scale' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="radio" name="fluid_scale[builder_mapping]" value="auto"
										<?php checked( $settings['builder_mapping'], 'auto' ); ?>>
									<?php esc_html_e( 'Auto — enable for detected builders', 'fluid-scale' ); ?>
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

				<!-- Generated CSS -->
				<h2><?php esc_html_e( 'Generated CSS', 'fluid-scale' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Read-only. This is the file served to every page on your site. It updates when you save.', 'fluid-scale' ); ?>
					<?php if ( $file_exists ) : ?>
					<a href="<?php echo esc_url( FileWriter::get_url() ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'View file ↗', 'fluid-scale' ); ?>
					</a>
					<?php endif; ?>
				</p>
				<textarea id="fs-css-output" class="large-text code" rows="16" readonly
					aria-label="<?php esc_attr_e( 'Generated CSS', 'fluid-scale' ); ?>"><?php
					if ( $file_exists ) {
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
						echo esc_textarea( file_get_contents( FileWriter::get_dir() . '/fluid-scale.css' ) );
					} else {
						echo esc_textarea( __( 'No CSS file yet. Save your settings to generate it.', 'fluid-scale' ) );
					}
				?></textarea>

			</form>
		</div><!-- .fs-layout-form -->

		<!-- ============================================================ -->
		<!-- RIGHT: STICKY PREVIEW PANEL                                   -->
		<!-- ============================================================ -->
		<div class="fs-layout-preview">
			<div class="fs-preview-panel" id="fs-preview">
				<div class="fs-preview-tabs" role="tablist">
					<button role="tab" class="fs-tab fs-tab--active" aria-controls="fs-preview-type" aria-selected="true" id="fs-tab-type">
						<?php esc_html_e( 'Type', 'fluid-scale' ); ?>
					</button>
					<button role="tab" class="fs-tab" aria-controls="fs-preview-space" aria-selected="false" id="fs-tab-space">
						<?php esc_html_e( 'Space', 'fluid-scale' ); ?>
					</button>
				</div>

				<p class="fs-preview-viewport-note">
					<?php esc_html_e( 'Shown at 1024px viewport', 'fluid-scale' ); ?>
				</p>

				<div id="fs-preview-type" role="tabpanel" aria-labelledby="fs-tab-type">
					<div id="fs-type-specimen"><!-- populated by admin.js --></div>
				</div>

				<div id="fs-preview-space" role="tabpanel" aria-labelledby="fs-tab-space" hidden>
					<div id="fs-space-specimen"><!-- populated by admin.js --></div>
				</div>
			</div>
		</div><!-- .fs-layout-preview -->

	</div><!-- .fs-layout -->

</div><!-- .fluid-scale-wrap -->
