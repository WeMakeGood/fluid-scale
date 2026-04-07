/**
 * Fluid Scale Admin JS
 *
 * Mirrors the PHP Generator math for the live admin preview.
 * Must produce identical clamp() values to class-generator.php.
 *
 * See docs/utopia-math.md for the formulas this implements.
 */

/* global fluidScaleAdmin */

( function () {
	'use strict';

	// -------------------------------------------------------------------------
	// Math (mirrors class-generator.php exactly)
	// -------------------------------------------------------------------------

	const SPACE_MULTIPLIERS = {
		'3xs': 0.25,
		'2xs': 0.5,
		'xs':  0.75,
		's':   1.0,
		'm':   1.5,
		'l':   2.0,
		'xl':  3.0,
		'2xl': 4.0,
		'3xl': 6.0,
	};

	const ONE_UP_PAIRS = [
		[ '3xs', '2xs' ],
		[ '2xs', 'xs'  ],
		[ 'xs',  's'   ],
		[ 's',   'm'   ],
		[ 'm',   'l'   ],
		[ 'l',   'xl'  ],
		[ 'xl',  '2xl' ],
		[ '2xl', '3xl' ],
	];

	/**
	 * Format a number to 4 decimal places, strip trailing zeros, append unit.
	 */
	function fmt( value, unit ) {
		let str = value.toFixed( 4 );
		str = str.replace( /(\.\d*?)0+$/, '$1' ).replace( /\.$/, '' );
		return str + unit;
	}

	/**
	 * Build a clamp() string from min/max rem and viewport bounds.
	 * Formula: docs/utopia-math.md → build_clamp()
	 */
	function buildClamp( minRem, maxRem, minVp, maxVp ) {
		const slope     = ( maxRem - minRem ) / ( maxVp - minVp );
		const intercept = minRem - slope * minVp;
		const slopeVw   = slope * 100;

		return `clamp(${ fmt( minRem, 'rem' ) }, ${ fmt( intercept, 'rem' ) } + ${ fmt( slopeVw, 'vw' ) }, ${ fmt( maxRem, 'rem' ) })`;
	}

	/**
	 * Compute clamp() for a type step.
	 */
	function typeClamp( step, params ) {
		const { minBase, maxBase, ratio, minVp, maxVp } = params;
		const minRem = parseFloat( ( minBase * Math.pow( ratio, step ) / 16 ).toFixed( 4 ) );
		const maxRem = parseFloat( ( maxBase * Math.pow( ratio, step ) / 16 ).toFixed( 4 ) );
		return buildClamp( minRem, maxRem, minVp, maxVp );
	}

	/**
	 * Compute the px size of a type step at a specific viewport width.
	 * Used to show a representative size in the preview.
	 *
	 * @param {number} step
	 * @param {object} params
	 * @param {number} viewportPx - Representative viewport (default 1024)
	 * @returns {number} px value (assumes 1rem = 16px)
	 */
	function typeClampAtViewport( step, params, viewportPx ) {
		const { minBase, maxBase, ratio, minVp, maxVp } = params;
		const minRem = minBase * Math.pow( ratio, step ) / 16;
		const maxRem = maxBase * Math.pow( ratio, step ) / 16;
		const slope  = ( maxRem - minRem ) / ( maxVp - minVp );
		const inter  = minRem - slope * minVp;
		const vp     = viewportPx || 1024;
		const clampedRem = Math.min( maxRem, Math.max( minRem, inter + slope * vp ) );
		return Math.round( clampedRem * 16 * 10 ) / 10; // px, 1dp
	}

	/**
	 * Compute clamp() for a space step given a multiplier.
	 */
	function spaceClamp( multiplier, params ) {
		const { minBase, maxBase, minVp, maxVp } = params;
		const minRem = parseFloat( ( minBase / 16 * multiplier ).toFixed( 4 ) );
		const maxRem = parseFloat( ( maxBase / 16 * multiplier ).toFixed( 4 ) );
		return buildClamp( minRem, maxRem, minVp, maxVp );
	}

	/**
	 * Compute clamp() for a space pair.
	 * min boundary from 'from' step, max boundary from 'to' step.
	 */
	function spacePairClamp( from, to, params ) {
		const { minBase, maxBase, minVp, maxVp } = params;
		const minRem = parseFloat( ( minBase / 16 * SPACE_MULTIPLIERS[ from ] ).toFixed( 4 ) );
		const maxRem = parseFloat( ( maxBase / 16 * SPACE_MULTIPLIERS[ to   ] ).toFixed( 4 ) );
		return buildClamp( minRem, maxRem, minVp, maxVp );
	}

	// -------------------------------------------------------------------------
	// Read form values
	// -------------------------------------------------------------------------

	function getParams() {
		const form = document.getElementById( 'fluid-scale-form' );
		if ( ! form ) { return null; }

		return {
			minVp:          parseFloat( form.querySelector( '[name="fluid_scale[min_viewport]"]' )?.value ) || 320,
			maxVp:          parseFloat( form.querySelector( '[name="fluid_scale[max_viewport]"]' )?.value ) || 1240,
			minBase:        parseFloat( form.querySelector( '[name="fluid_scale[min_base]"]' )?.value )     || 16,
			maxBase:        parseFloat( form.querySelector( '[name="fluid_scale[max_base]"]' )?.value )     || 20,
			ratio:          parseFloat( form.querySelector( '[name="fluid_scale[ratio]"]' )?.value )        || 1.333,
			negativeSteps:  parseInt(   form.querySelector( '[name="fluid_scale[negative_steps]"]' )?.value ) || 2,
			positiveSteps:  parseInt(   form.querySelector( '[name="fluid_scale[positive_steps]"]' )?.value ) || 5,
		};
	}

	// -------------------------------------------------------------------------
	// Render preview
	// -------------------------------------------------------------------------

	const PREVIEW_VIEWPORT = 1024;

	function renderTypePreview( params ) {
		const el = document.getElementById( 'fs-type-specimen' );
		if ( ! el ) { return; }

		const rows = [];
		const steps = [];

		for ( let n = -params.negativeSteps; n <= params.positiveSteps; n++ ) {
			steps.push( n );
		}

		for ( const step of steps ) {
			const clampVal = typeClamp( step, params );
			const px       = typeClampAtViewport( step, params, PREVIEW_VIEWPORT );
			const name     = step < 0 ? `--step-${ step }` : `--step-${ step }`;
			const label    = step < 0 ? `step-${step}` : `step-${step}`;

			rows.push( `
				<div class="fs-specimen-row">
					<div class="fs-specimen-meta">
						<code class="fs-specimen-name">${ escHtml( name ) }</code>
						<span class="fs-specimen-px">${ px }px @ ${ PREVIEW_VIEWPORT }px viewport</span>
						<code class="fs-specimen-clamp">${ escHtml( clampVal ) }</code>
					</div>
					<div class="fs-specimen-text" style="font-size: ${ escHtml( clampVal ) }">Aa</div>
				</div>
			` );
		}

		el.innerHTML = rows.join( '' );
	}

	function renderSpacePreview( params ) {
		const el = document.getElementById( 'fs-space-specimen' );
		if ( ! el ) { return; }

		const rows = [];

		// Find largest multiplier for proportional bar widths
		const maxMult = Math.max( ...Object.values( SPACE_MULTIPLIERS ) );

		for ( const [ name, mult ] of Object.entries( SPACE_MULTIPLIERS ) ) {
			const pxMin  = Math.round( params.minBase * mult * 10 ) / 10;
			const pxMax  = Math.round( params.maxBase * mult * 10 ) / 10;
			const barPct = Math.round( ( mult / maxMult ) * 100 );

			rows.push( `
				<div class="fs-space-row">
					<div class="fs-space-meta">
						<code class="fs-specimen-name">--space-${ escHtml( name ) }</code>
						<span class="fs-specimen-px">${ pxMin }–${ pxMax }px</span>
					</div>
					<div class="fs-space-bar-wrap">
						<div class="fs-space-bar" style="width: ${ barPct }%"></div>
					</div>
				</div>
			` );
		}

		el.innerHTML = rows.join( '' );
	}

	function renderPreview() {
		const params = getParams();
		if ( ! params ) { return; }
		renderTypePreview( params );
		renderSpacePreview( params );
	}

	// -------------------------------------------------------------------------
	// Tab switching
	// -------------------------------------------------------------------------

	function initTabs() {
		const tabs = document.querySelectorAll( '.fs-tab' );
		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				tabs.forEach( function ( t ) {
					t.classList.remove( 'fs-tab--active' );
					t.setAttribute( 'aria-selected', 'false' );
					const panel = document.getElementById( t.getAttribute( 'aria-controls' ) );
					if ( panel ) { panel.hidden = true; }
				} );
				tab.classList.add( 'fs-tab--active' );
				tab.setAttribute( 'aria-selected', 'true' );
				const activePanel = document.getElementById( tab.getAttribute( 'aria-controls' ) );
				if ( activePanel ) { activePanel.hidden = false; }
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// Custom pairs UI
	// -------------------------------------------------------------------------

	function initPairsUI() {
		const addBtn   = document.getElementById( 'fs-add-pair' );
		const container = document.getElementById( 'fs-custom-pairs' );
		const template = document.getElementById( 'fs-pair-template' );

		if ( ! addBtn || ! container || ! template ) { return; }

		addBtn.addEventListener( 'click', function () {
			const clone = template.content.cloneNode( true );
			container.appendChild( clone );
			// Default the 'to' select to next step up from 'from'
			const row    = container.lastElementChild;
			const toSel  = row.querySelector( '.fs-pair-to' );
			if ( toSel && toSel.options.length > 1 ) {
				toSel.selectedIndex = Math.min( 1, toSel.options.length - 1 );
			}
		} );

		container.addEventListener( 'click', function ( e ) {
			if ( e.target.classList.contains( 'fs-pair-remove' ) ) {
				e.target.closest( '.fs-pair-row' )?.remove();
			}
		} );
	}

	// -------------------------------------------------------------------------
	// Utilities
	// -------------------------------------------------------------------------

	function escHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	// -------------------------------------------------------------------------
	// Init
	// -------------------------------------------------------------------------

	function init() {
		// Render preview on any .fs-param change (debounced)
		let debounceTimer;
		document.querySelectorAll( '.fs-param' ).forEach( function ( el ) {
			el.addEventListener( 'change', function () {
				clearTimeout( debounceTimer );
				debounceTimer = setTimeout( renderPreview, 150 );
			} );
			el.addEventListener( 'input', function () {
				clearTimeout( debounceTimer );
				debounceTimer = setTimeout( renderPreview, 300 );
			} );
		} );

		initTabs();
		initPairsUI();
		renderPreview(); // Initial render on page load
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

}() );
