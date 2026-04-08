<?php
/**
 * Settings page view template — Alpine.js driven.
 *
 * Variables injected by AdminPage::render_page():
 *   $settings    array
 *   $builders    array
 *   $file_exists bool
 *   $messages    array
 *
 * @package FluidScale
 */

namespace FluidScale;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This file is included inside AdminPage::render_page() — all variables below
// are template-scoped locals, not globals. The sniff fires because the file has
// no function/class wrapper; suppress it for the whole template.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$space_steps = Generator::space_step_names();

// Encode settings for Alpine x-data initialisation
$alpine_init = wp_json_encode( [
	'minVp'          => (int)    $settings['min_viewport'],
	'maxVp'          => (int)    $settings['max_viewport'],
	'minBase'        => (float)  $settings['min_base'],
	'maxBase'        => (float)  $settings['max_base'],
	'minRatio'       => number_format( (float) $settings['min_ratio'], 3, '.', '' ),
	'maxRatio'       => number_format( (float) $settings['max_ratio'], 3, '.', '' ),
	'negSteps'       => (int)    $settings['negative_steps'],
	'posSteps'       => (int)    $settings['positive_steps'],
	'gridMaxWidth'   => (int)    $settings['grid_max_width'],
	'gridColumns'    => (int)    $settings['grid_columns'],
	'gridGutter'     => $settings['grid_gutter_pair'],
	'builderMapping' => $settings['builder_mapping'],
	'customPairs'    => $settings['custom_pairs'],
	'diviMapping'    => [
		'sectionPadding'     => $settings['divi_mapping']['section_padding']     ?? 'xl',
		'sectionGutter'      => $settings['divi_mapping']['section_gutter']      ?? 'xl',
		'rowGutterVertical'  => $settings['divi_mapping']['row_gutter_vertical'] ?? 'l',
		'moduleGutter'       => $settings['divi_mapping']['module_gutter']       ?? 'm',
	],
] );
?>
<!-- Pass settings to Alpine via a JS global — avoids esc_attr() corrupting JSON quotes -->
<script>window.fluidScaleInit = <?php echo $alpine_init; // phpcs:ignore WordPress.Security.EscapeOutput ?>;</script>
<div class="wrap fs-wrap" x-data="fluidScale( window.fluidScaleInit )">

	<?php foreach ( $messages as $notice ) : ?>
	<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
		<p><?php echo esc_html( $notice['message'] ); ?></p>
	</div>
	<?php endforeach; ?>

	<h1><?php esc_html_e( 'Fluid Scale', 'fluid-scale' ); ?></h1>
	<p class="fs-page-desc">
		<?php esc_html_e( 'Configure your fluid type, space, and grid system. The preview updates live — save when it feels right.', 'fluid-scale' ); ?>
		<?php
		/* translators: %s: URL to utopia.fyi */
		$fs_credit = __( 'Math based on the <a href="%s" target="_blank" rel="noopener noreferrer">Utopia</a> fluid design system by James Gilyead and Trys Mudford.', 'fluid-scale' );
		printf( wp_kses( $fs_credit, [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] ), 'https://utopia.fyi' );
		?>
	</p>

	<?php if ( $file_exists ) : ?>
	<span class="fs-status fs-status--ok">
		<span class="fs-status__dot"></span>
		<?php
		$last = (int) $settings['last_generated'];
		printf(
			/* translators: %s: human-readable date */
			esc_html__( 'Generated %s', 'fluid-scale' ),
			$last ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last ) ) : esc_html__( 'previously', 'fluid-scale' )
		);
		?>
		&nbsp;·&nbsp;
		<a href="<?php echo esc_url( FileWriter::get_url() ); ?>" target="_blank" rel="noopener" style="color:inherit">
			<?php esc_html_e( 'View file ↗', 'fluid-scale' ); ?>
		</a>
	</span>
	<?php else : ?>
	<span class="fs-status fs-status--warn">
		<span class="fs-status__dot"></span>
		<?php esc_html_e( 'No CSS file yet — save to generate', 'fluid-scale' ); ?>
	</span>
	<?php endif; ?>

	<!-- Inject live CSS vars into page head so mockup can use var() -->
	<style id="fs-live-vars" x-effect="$el.textContent = mockupVars"></style>

	<div class="fs-layout">

		<!-- ============================================================ -->
		<!-- LEFT: SETTINGS FORM                                           -->
		<!-- ============================================================ -->
		<div class="fs-layout-form">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="fluid-scale-form">
				<input type="hidden" name="action" value="fluid_scale_save">
				<?php wp_nonce_field( 'fluid_scale_save_settings', 'fluid_scale_nonce' ); ?>

				<!-- Sync Alpine state back to hidden inputs for form submission -->
				<input type="hidden" name="fluid_scale[min_viewport]"    :value="minVp">
				<input type="hidden" name="fluid_scale[max_viewport]"    :value="maxVp">
				<input type="hidden" name="fluid_scale[min_base]"        :value="minBase">
				<input type="hidden" name="fluid_scale[max_base]"        :value="maxBase">
				<input type="hidden" name="fluid_scale[min_ratio]"      :value="minRatio">
				<input type="hidden" name="fluid_scale[max_ratio]"      :value="maxRatio">
				<input type="hidden" name="fluid_scale[negative_steps]"  :value="negSteps">
				<input type="hidden" name="fluid_scale[positive_steps]"  :value="posSteps">
				<input type="hidden" name="fluid_scale[grid_max_width]"  :value="gridMaxWidth">
				<input type="hidden" name="fluid_scale[grid_columns]"    :value="gridColumns">
				<input type="hidden" name="fluid_scale[grid_gutter_pair]" :value="gridGutter">
				<input type="hidden" name="fluid_scale[builder_mapping]" :value="builderMapping">
				<!-- Custom pairs submitted as parallel arrays via PHP loop below -->

				<!-- ==================================================== -->
				<!-- Save (top of form)                                     -->
				<!-- ==================================================== -->
				<div class="fs-save-row fs-save-row--top">
					<button type="submit" class="fs-btn fs-btn--primary fs-btn--wp">
						<?php esc_html_e( 'Save & Regenerate', 'fluid-scale' ); ?>
					</button>
				</div>

				<!-- ==================================================== -->
				<!-- Viewport Range                                         -->
				<!-- ==================================================== -->
				<div class="fs-panel">
					<div class="fs-panel-header">
						<h2 class="fs-panel-title"><?php esc_html_e( 'Viewport Range', 'fluid-scale' ); ?></h2>
						<p class="fs-panel-desc"><?php esc_html_e( 'The screen widths your scale interpolates between', 'fluid-scale' ); ?></p>
					</div>
					<div class="fs-panel-body">
						<div class="fs-field-row">
							<div class="fs-field">
								<label class="fs-label" for="fs-min-vp"><?php esc_html_e( 'Smallest screen', 'fluid-scale' ); ?></label>
								<div class="fs-input-row">
									<input type="number" id="fs-min-vp" class="fs-input fs-input--short"
										x-model.number="minVp" min="1" max="2000" step="1">
									<span class="fs-input-unit">px</span>
								</div>
								<p class="fs-help"><?php esc_html_e( 'Minimum values apply here. 320px is a typical mobile floor.', 'fluid-scale' ); ?></p>
							</div>
							<div class="fs-field">
								<label class="fs-label" for="fs-max-vp"><?php esc_html_e( 'Largest screen', 'fluid-scale' ); ?></label>
								<div class="fs-input-row">
									<input type="number" id="fs-max-vp" class="fs-input fs-input--short"
										x-model.number="maxVp" min="1" max="5000" step="1">
									<span class="fs-input-unit">px</span>
								</div>
								<p class="fs-help"><?php esc_html_e( 'Maximum values apply here and wider. 1240–1440px is typical.', 'fluid-scale' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- ==================================================== -->
				<!-- Body Text Size                                         -->
				<!-- ==================================================== -->
				<div class="fs-panel">
					<div class="fs-panel-header">
						<h2 class="fs-panel-title"><?php esc_html_e( 'Body Text Size', 'fluid-scale' ); ?></h2>
						<p class="fs-panel-desc"><?php esc_html_e( 'All other sizes scale from this', 'fluid-scale' ); ?></p>
					</div>
					<div class="fs-panel-body">
						<div class="fs-field-row">
							<div class="fs-field">
								<label class="fs-label" for="fs-min-base"><?php esc_html_e( 'On mobile', 'fluid-scale' ); ?></label>
								<div class="fs-input-row">
									<input type="number" id="fs-min-base" class="fs-input fs-input--short"
										x-model.number="minBase" min="8" max="32" step="0.5">
									<span class="fs-input-unit">px</span>
								</div>
								<p class="fs-help"><?php esc_html_e( 'Paragraph text at your smallest screen. 15–16px is typical.', 'fluid-scale' ); ?></p>
							</div>
							<div class="fs-field">
								<label class="fs-label" for="fs-max-base"><?php esc_html_e( 'On desktop', 'fluid-scale' ); ?></label>
								<div class="fs-input-row">
									<input type="number" id="fs-max-base" class="fs-input fs-input--short"
										x-model.number="maxBase" min="8" max="40" step="0.5">
									<span class="fs-input-unit">px</span>
								</div>
								<p class="fs-help"><?php esc_html_e( 'Slightly larger gives text room to breathe. 18–20px is typical.', 'fluid-scale' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- ==================================================== -->
				<!-- Scale Ratio                                             -->
				<!-- ==================================================== -->
				<div class="fs-panel">
					<div class="fs-panel-header">
						<h2 class="fs-panel-title"><?php esc_html_e( 'Scale Ratio', 'fluid-scale' ); ?></h2>
						<p class="fs-panel-desc"><?php esc_html_e( 'How much bigger each heading step gets — fluid between viewports', 'fluid-scale' ); ?></p>
					</div>
					<div class="fs-panel-body">
						<?php
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
						<div class="fs-field-row">
							<div class="fs-field">
								<label class="fs-label" for="fs-min-ratio"><?php esc_html_e( 'Mobile ratio', 'fluid-scale' ); ?></label>
								<div class="fs-select-wrap">
									<select id="fs-min-ratio" class="fs-select" x-model="minRatio">
										<?php foreach ( $ratio_options as $val => $label ) : ?>
										<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<div class="fs-field">
								<label class="fs-label" for="fs-max-ratio"><?php esc_html_e( 'Desktop ratio', 'fluid-scale' ); ?></label>
								<div class="fs-select-wrap">
									<select id="fs-max-ratio" class="fs-select" x-model="maxRatio">
										<?php foreach ( $ratio_options as $val => $label ) : ?>
										<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
						</div>
						<p class="fs-help"><?php esc_html_e( 'A smaller mobile ratio keeps heading sizes closer together on small screens. The scale interpolates fluidly between the two — no breakpoints needed.', 'fluid-scale' ); ?></p>
					</div>
				</div>

				<!-- ==================================================== -->
				<!-- Steps                                                   -->
				<!-- ==================================================== -->
				<div class="fs-panel">
					<div class="fs-panel-header">
						<h2 class="fs-panel-title"><?php esc_html_e( 'Steps', 'fluid-scale' ); ?></h2>
						<p class="fs-panel-desc"><?php esc_html_e( 'Sizes above and below body text', 'fluid-scale' ); ?></p>
					</div>
					<div class="fs-panel-body">
						<div class="fs-field-row">
							<div class="fs-field">
								<label class="fs-label" for="fs-neg-steps"><?php esc_html_e( 'Below body text', 'fluid-scale' ); ?></label>
								<input type="number" id="fs-neg-steps" class="fs-input fs-input--short"
									x-model.number="negSteps" min="1" max="5" step="1">
								<p class="fs-help"><?php esc_html_e( '--step--1, --step--2… captions and labels. 2 is usually enough.', 'fluid-scale' ); ?></p>
							</div>
							<div class="fs-field">
								<label class="fs-label" for="fs-pos-steps"><?php esc_html_e( 'Above body text', 'fluid-scale' ); ?></label>
								<input type="number" id="fs-pos-steps" class="fs-input fs-input--short"
									x-model.number="posSteps" min="1" max="10" step="1">
								<p class="fs-help"><?php esc_html_e( '--step-1 through --step-N. --step-5 is your largest heading. 5 covers most layouts.', 'fluid-scale' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- ==================================================== -->
				<!-- Grid                                                    -->
				<!-- ==================================================== -->
				<div class="fs-panel">
					<div class="fs-panel-header">
						<h2 class="fs-panel-title"><?php esc_html_e( 'Grid', 'fluid-scale' ); ?></h2>
						<p class="fs-panel-desc"><?php esc_html_e( '--grid-max-width, --grid-columns, --grid-gutter', 'fluid-scale' ); ?></p>
					</div>
					<div class="fs-panel-body">
						<div class="fs-field-row">
							<div class="fs-field">
								<label class="fs-label" for="fs-grid-width"><?php esc_html_e( 'Container width', 'fluid-scale' ); ?></label>
								<div class="fs-input-row">
									<input type="number" id="fs-grid-width" class="fs-input fs-input--short"
										x-model.number="gridMaxWidth" min="100" max="5000" step="1">
									<span class="fs-input-unit">px</span>
								</div>
								<p class="fs-help"><?php esc_html_e( 'Match your theme\'s max content width.', 'fluid-scale' ); ?></p>
							</div>
							<div class="fs-field">
								<label class="fs-label" for="fs-grid-cols"><?php esc_html_e( 'Columns', 'fluid-scale' ); ?></label>
								<input type="number" id="fs-grid-cols" class="fs-input fs-input--short"
									x-model.number="gridColumns" min="1" max="24" step="1">
								<p class="fs-help"><?php esc_html_e( '12 is standard.', 'fluid-scale' ); ?></p>
							</div>
						</div>
						<div class="fs-field">
							<label class="fs-label" for="fs-grid-gutter"><?php esc_html_e( 'Gutter (column gap)', 'fluid-scale' ); ?></label>
							<div class="fs-select-wrap">
								<select id="fs-grid-gutter" class="fs-select" x-model="gridGutter">
									<?php
									// One-up pairs plus common wider pairs for gutter use
									$gutter_pairs = array_map(
										fn( $p ) => [ $p[0], $p[1] ],
										Generator::ONE_UP_PAIRS
									);
									// Add common non-consecutive pairs used as gutters
									$gutter_pairs[] = [ 'xs', 'm' ];
									$gutter_pairs[] = [ 's', 'l' ];
									$gutter_pairs[] = [ 's', 'xl' ];
									$gutter_pairs[] = [ 'm', 'xl' ];
									foreach ( $gutter_pairs as [ $from, $to ] ) :
										$val = "{$from}-{$to}"; ?>
									<option value="<?php echo esc_attr( $val ); ?>">
										<?php echo esc_html( "--space-{$val}" ); ?>
									</option>
									<?php endforeach; ?>
								</select>
							</div>
							<p class="fs-help"><?php esc_html_e( 'A space pair — the gutter grows from the first size on mobile to the second on desktop. --space-s-l is a good default.', 'fluid-scale' ); ?></p>
						</div>
					</div>
				</div>

				<!-- ==================================================== -->
				<!-- Custom Space Pairs                                      -->
				<!-- ==================================================== -->
				<div class="fs-panel">
					<div class="fs-panel-header">
						<h2 class="fs-panel-title"><?php esc_html_e( 'Custom Space Pairs', 'fluid-scale' ); ?></h2>
						<p class="fs-panel-desc"><?php esc_html_e( 'Spacing that grows across a wider range between screens', 'fluid-scale' ); ?></p>
					</div>
					<div class="fs-panel-body">
						<p class="fs-help" style="margin-bottom:12px"><?php esc_html_e( 'Pairs start at one space size on mobile and end at another on desktop. Use them for section padding or hero margins where you want dramatic growth. One-up pairs (s→m, m→l, etc.) are already generated automatically.', 'fluid-scale' ); ?></p>

						<!-- Rendered pairs (for form submission) -->
						<template x-for="(pair, idx) in customPairs" :key="idx">
							<div class="fs-pair-row">
								<div class="fs-select-wrap">
									<select class="fs-select" x-model="pair.from"
										:name="'fluid_scale_pair_from[]'">
										<?php foreach ( $space_steps as $step ) : ?>
										<option value="<?php echo esc_attr( $step ); ?>">
											<?php echo esc_html( "--space-{$step}" ); ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
								<span class="fs-pair-arrow">→</span>
								<div class="fs-select-wrap">
									<select class="fs-select" x-model="pair.to"
										:name="'fluid_scale_pair_to[]'">
										<?php foreach ( $space_steps as $step ) : ?>
										<option value="<?php echo esc_attr( $step ); ?>">
											<?php echo esc_html( "--space-{$step}" ); ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
								<button type="button" class="fs-pair-remove" @click="removePair(idx)" aria-label="<?php esc_attr_e( 'Remove pair', 'fluid-scale' ); ?>">✕</button>
							</div>
						</template>

						<button type="button" class="fs-add-pair" @click="addPair">
							+ <?php esc_html_e( 'Add pair', 'fluid-scale' ); ?>
						</button>
					</div>
				</div>

				<!-- ==================================================== -->
				<!-- Builder Mapping                                         -->
				<!-- ==================================================== -->
				<?php if ( ! empty( $builders ) ) : ?>
				<div class="fs-panel">
					<div class="fs-panel-header">
						<h2 class="fs-panel-title"><?php esc_html_e( 'Builder Mapping', 'fluid-scale' ); ?></h2>
						<p class="fs-panel-desc"><?php esc_html_e( 'Connect scale variables to your builder\'s variable names', 'fluid-scale' ); ?></p>
					</div>
					<div class="fs-panel-body">
						<div class="fs-radio-group">
							<label class="fs-radio-label">
								<input type="radio" x-model="builderMapping" value="auto">
								<span><?php esc_html_e( 'Auto — enable for detected builders', 'fluid-scale' ); ?></span>
							</label>
							<?php foreach ( $builders as $builder ) : ?>
							<label class="fs-radio-label">
								<input type="radio" x-model="builderMapping" value="<?php echo esc_attr( $builder ); ?>">
								<span><?php echo esc_html( BuilderMappings::get_label( $builder ) . ' ' . __( 'only', 'fluid-scale' ) ); ?></span>
							</label>
							<?php endforeach; ?>
							<label class="fs-radio-label">
								<input type="radio" x-model="builderMapping" value="none">
								<span><?php esc_html_e( 'Disabled', 'fluid-scale' ); ?></span>
							</label>
						</div>

						<?php if ( in_array( 'divi5', $builders, true ) ) : ?>
						<div class="fs-divi-mapping" x-show="builderMapping === 'auto' || builderMapping === 'divi5'">
							<p class="fs-help" style="margin: 16px 0 12px;">
								<?php esc_html_e( 'Map Divi\'s layout variables to your fluid space scale. Content width and row gutter are fixed to your grid settings.', 'fluid-scale' ); ?>
							</p>
							<div class="fs-field-row">
								<?php
								$divi_fields = [
									[ 'key' => 'section_padding',     'alpine' => 'sectionPadding',    'label' => __( 'Section padding', 'fluid-scale' ) ],
									[ 'key' => 'section_gutter',      'alpine' => 'sectionGutter',     'label' => __( 'Section gutter', 'fluid-scale' ) ],
									[ 'key' => 'row_gutter_vertical', 'alpine' => 'rowGutterVertical', 'label' => __( 'Row gutter (vertical)', 'fluid-scale' ) ],
									[ 'key' => 'module_gutter',       'alpine' => 'moduleGutter',      'label' => __( 'Module gutter', 'fluid-scale' ) ],
								];
								foreach ( $divi_fields as $field ) :
									$current = $settings['divi_mapping'][ $field['key'] ] ?? '';
								?>
								<div class="fs-field">
									<label class="fs-label" for="fs-divi-<?php echo esc_attr( $field['key'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
									<div class="fs-select-wrap">
										<select id="fs-divi-<?php echo esc_attr( $field['key'] ); ?>"
											class="fs-select"
											name="fluid_scale_divi_mapping[<?php echo esc_attr( $field['key'] ); ?>]"
											x-model="diviMapping.<?php echo esc_attr( $field['alpine'] ); ?>">
											<?php foreach ( $space_steps as $step ) : ?>
											<option value="<?php echo esc_attr( $step ); ?>"
												<?php selected( $current, $step ); ?>>
												<?php echo esc_html( '--space-' . $step ); ?>
											</option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
								<?php endforeach; ?>
							</div>
						</div>
						<?php endif; ?>

					</div>
				</div>
				<?php endif; ?>

			</form>
		</div><!-- .fs-layout-form -->

		<!-- ============================================================ -->
		<!-- RIGHT: LIVE PREVIEW                                           -->
		<!-- ============================================================ -->
		<div class="fs-preview-col">
			<div class="fs-preview-shell" :class="previewDark ? 'fs-preview-shell--dark' : 'fs-preview-shell--light'">

				<!-- Tab bar -->
				<div class="fs-preview-tabs" role="tablist">
					<button role="tab" class="fs-preview-tab"
						:aria-selected="previewTab === 'mockup'"
						:class="{ 'fs-preview-tab--active': previewTab === 'mockup' }"
						@click="previewTab = 'mockup'; inspected = null">
						<?php esc_html_e( 'Page Mockup', 'fluid-scale' ); ?>
					</button>
					<button role="tab" class="fs-preview-tab"
						:class="{ 'fs-preview-tab--active': previewTab === 'type' }"
						@click="previewTab = 'type'; inspected = null">
						<?php esc_html_e( 'Type Scale', 'fluid-scale' ); ?>
					</button>
					<button role="tab" class="fs-preview-tab"
						:class="{ 'fs-preview-tab--active': previewTab === 'space' }"
						@click="previewTab = 'space'; inspected = null">
						<?php esc_html_e( 'Space Scale', 'fluid-scale' ); ?>
					</button>
					<div class="fs-preview-tabs-spacer"></div>
					<button type="button" class="fs-preview-mode-toggle"
						@click="previewDark = !previewDark"
						:title="previewDark ? '<?php esc_attr_e( 'Switch to light preview', 'fluid-scale' ); ?>' : '<?php esc_attr_e( 'Switch to dark preview', 'fluid-scale' ); ?>'">
						<span x-text="previewDark ? '☀' : '◑'"></span>
					</button>
				</div>

				<p class="fs-preview-vp-note"><?php esc_html_e( 'Values shown at 1024px viewport · click any element to inspect', 'fluid-scale' ); ?></p>

				<!-- ==================================================== -->
				<!-- Tab content area (scrollable, grows to fill shell)     -->
				<!-- ==================================================== -->
				<div class="fs-preview-content">

				<!-- ==================================================== -->
				<!-- PAGE MOCKUP TAB                                        -->
				<!-- All sizing uses CSS custom properties from the live    -->
				<!-- <style> tag injected by x-effect="mockupVars".         -->
				<!-- ==================================================== -->
				<div x-show="previewTab === 'mockup'" x-cloak class="fs-mockup">

					<!-- Hero -->
					<div class="fs-mock-hero">
						<p class="fs-mock-kicker">
							<?php esc_html_e( 'Annual Report 2024', 'fluid-scale' ); ?>
						</p>
						<h1 class="fs-token fs-mock-h1"
							:class="{ 'fs-token--active': inspected && inspected.variable === '--step-5' }"
							@click="inspect('H1 — hero heading', '--step-5', stepClamp(5))">
							<?php esc_html_e( 'Building Stronger Communities Through Collective Action', 'fluid-scale' ); ?>
						</h1>
						<p class="fs-token fs-mock-lead"
							:class="{ 'fs-token--active': inspected && inspected.variable === '--step-1' }"
							@click="inspect('Lead / subheading', '--step-1', stepClamp(1))">
							<?php esc_html_e( 'Our programs reached 14,000 individuals across 23 counties. Here\'s what we accomplished together.', 'fluid-scale' ); ?>
						</p>
						<div class="fs-mock-cta-row">
							<span class="fs-mock-btn-primary"><?php esc_html_e( 'Read the Report', 'fluid-scale' ); ?></span>
							<span class="fs-mock-btn-ghost"><?php esc_html_e( 'Watch the Story', 'fluid-scale' ); ?></span>
						</div>
					</div>

					<!-- Section heading -->
					<h2 class="fs-token fs-mock-h2"
						:class="{ 'fs-token--active': inspected && inspected.variable === '--step-3' }"
						@click="inspect('H2 — section heading', '--step-3', stepClamp(3))">
						<?php esc_html_e( 'Program Areas', 'fluid-scale' ); ?>
					</h2>

					<!-- Cards -->
					<div class="fs-mock-cards">
						<div class="fs-mock-card fs-token"
							:class="{ 'fs-token--active': inspected && inspected.variable === '--space-m' }"
							@click="inspect('Card padding', '--space-m', spaceClamp(1.5))">
							<div class="fs-mock-card-img"><span><?php esc_html_e( '16:9 image', 'fluid-scale' ); ?></span></div>
							<div class="fs-mock-card-body">
								<h3 class="fs-mock-h3"><?php esc_html_e( 'Housing Stability', 'fluid-scale' ); ?></h3>
								<p class="fs-mock-small"><?php esc_html_e( 'Preventing evictions and connecting families to emergency rental assistance across the region.', 'fluid-scale' ); ?></p>
							</div>
						</div>
						<div class="fs-mock-card fs-token"
							:class="{ 'fs-token--active': inspected && inspected.variable === '--space-m' }"
							@click="inspect('Card padding', '--space-m', spaceClamp(1.5))">
							<div class="fs-mock-card-img"><span><?php esc_html_e( '16:9 image', 'fluid-scale' ); ?></span></div>
							<div class="fs-mock-card-body">
								<h3 class="fs-mock-h3"><?php esc_html_e( 'Workforce Development', 'fluid-scale' ); ?></h3>
								<p class="fs-mock-small"><?php esc_html_e( 'Job training and placement programs with a 78% 90-day employment retention rate.', 'fluid-scale' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Body copy -->
					<div class="fs-mock-body-block">
						<h3 class="fs-token fs-mock-h3-large"
							:class="{ 'fs-token--active': inspected && inspected.variable === '--step-2' }"
							@click="inspect('H3 — article subheading', '--step-2', stepClamp(2))">
							<?php esc_html_e( 'A Message From Our Executive Director', 'fluid-scale' ); ?>
						</h3>
						<p class="fs-token fs-mock-body"
							:class="{ 'fs-token--active': inspected && inspected.variable === '--step-0' }"
							@click="inspect('Body text', '--step-0', stepClamp(0))">
							<?php esc_html_e( 'This year tested our resilience in ways we did not anticipate. Rising costs, increased demand for services, and a shifting funding landscape required us to adapt quickly. And we did — because of you.', 'fluid-scale' ); ?>
						</p>
						<p class="fs-mock-body"><?php esc_html_e( 'The numbers in this report represent real people. Families who stayed in their homes. Individuals who found stable employment. Children whose futures look brighter because this community showed up.', 'fluid-scale' ); ?></p>
						<p class="fs-token fs-mock-caption"
							:class="{ 'fs-token--active': inspected && inspected.variable === '--step--1' }"
							@click="inspect('Caption / small text', '--step--1', stepClamp(-1))">
							<?php esc_html_e( '— Dr. Maria Santos, Executive Director. Photography by James Okafor.', 'fluid-scale' ); ?>
						</p>
					</div>

				</div><!-- /mockup -->

				<!-- ==================================================== -->
				<!-- TYPE SCALE TAB                                         -->
				<!-- ==================================================== -->
				<div x-show="previewTab === 'type'" x-cloak>
					<div class="fs-type-list">
						<template x-for="step in typeSteps" :key="step.n">
							<div class="fs-type-row"
								:class="{ 'fs-type-row--active': inspected && inspected.variable === step.name }"
								@click="inspect(step.name, step.name, step.clamp)">
								<div class="fs-type-meta">
									<code class="fs-type-name" x-text="step.name"></code>
								</div>
								<div class="fs-type-sample"
									:style="{ fontSize: 'var(' + step.name + ')' }">
									The quick brown fox
								</div>
							</div>
						</template>
					</div>
				</div>

				<!-- ==================================================== -->
				<!-- SPACE SCALE TAB                                        -->
				<!-- ==================================================== -->
				<div x-show="previewTab === 'space'" x-cloak>
					<div class="fs-space-list">
						<template x-for="step in spaceSteps" :key="step.key">
							<div class="fs-space-row"
								:class="{ 'fs-space-row--active': inspected && inspected.variable === step.name }"
								@click="inspect(step.name, step.name, step.clamp)">
								<div class="fs-space-meta">
									<code class="fs-space-name" x-text="step.name"></code>
									<span class="fs-space-px" x-text="step.pxMin + '–' + step.pxMax + 'px'"></span>
								</div>
								<div class="fs-space-bar-wrap">
									<div class="fs-space-bar">
										<div class="fs-space-bar-fill" :style="{ width: step.barPct + '%' }"></div>
									</div>
								</div>
							</div>
						</template>
					</div>
				</div>

				</div><!-- .fs-preview-content -->

				<!-- ==================================================== -->
				<!-- INSPECTOR (pinned to bottom of preview shell)         -->
				<!-- ==================================================== -->
				<div class="fs-inspector" x-show="inspected" x-cloak>
					<p class="fs-inspector-label"><?php esc_html_e( 'Inspector', 'fluid-scale' ); ?></p>
					<p class="fs-inspector-var" x-text="inspected && inspected.variable"></p>
					<p class="fs-inspector-clamp" x-text="inspected && inspected.clamp"></p>
				</div>

			</div><!-- .fs-preview-shell -->

			<!-- ==================================================== -->
			<!-- Generated CSS (right column, below preview)          -->
			<!-- ==================================================== -->
			<div class="fs-panel fs-generated-css-panel">
				<div class="fs-panel-header">
					<h2 class="fs-panel-title"><?php esc_html_e( 'Generated CSS', 'fluid-scale' ); ?></h2>
					<p class="fs-panel-desc"><?php esc_html_e( 'Read-only — updates on save', 'fluid-scale' ); ?></p>
				</div>
				<textarea class="fs-css-output" rows="14" readonly
					aria-label="<?php esc_attr_e( 'Generated CSS', 'fluid-scale' ); ?>"><?php
					if ( $file_exists ) {
						// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
						echo esc_textarea( file_get_contents( FileWriter::get_dir() . '/fluid-scale.css' ) );
					} else {
						echo esc_textarea( __( 'No CSS file yet. Save your settings to generate it.', 'fluid-scale' ) );
					}
				?></textarea>
			</div>

		</div><!-- .fs-preview-col -->

	</div><!-- .fs-layout -->

</div><!-- .fs-wrap -->
