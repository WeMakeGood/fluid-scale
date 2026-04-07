/**
 * Fluid Scale Admin — Alpine.js application
 *
 * All scale math mirrors class-generator.php exactly.
 * See docs/utopia-math.md for formulas.
 */

/* global Alpine */

( function () {
	'use strict';

	// -------------------------------------------------------------------------
	// Scale math (mirrors class-generator.php)
	// -------------------------------------------------------------------------

	const SPACE_MULTIPLIERS = {
		'3xs': 0.25, '2xs': 0.5, 'xs': 0.75,
		's': 1.0, 'm': 1.5, 'l': 2.0,
		'xl': 3.0, '2xl': 4.0, '3xl': 6.0,
	};

	function fmt( value, unit, decimals = 4 ) {
		let s = value.toFixed( decimals );
		s = s.replace( /(\.\d*?)0+$/, '$1' ).replace( /\.$/, '' );
		return s + unit;
	}

	function buildClamp( minRem, maxRem, minVp, maxVp ) {
		const minPx     = minRem * 16;
		const maxPx     = maxRem * 16;
		const slope     = ( maxPx - minPx ) / ( maxVp - minVp );
		const intercept = ( minPx - slope * minVp ) / 16;
		return `clamp(${ fmt( minRem, 'rem' ) }, ${ fmt( intercept, 'rem' ) } + ${ fmt( slope * 100, 'vw' ) }, ${ fmt( maxRem, 'rem' ) })`;
	}

	function typeMinRem( step, p ) {
		return parseFloat( ( p.minBase * Math.pow( p.ratio, step ) / 16 ).toFixed( 4 ) );
	}

	function typeMaxRem( step, p ) {
		return parseFloat( ( p.maxBase * Math.pow( p.ratio, step ) / 16 ).toFixed( 4 ) );
	}

	function typeClamp( step, p ) {
		return buildClamp( typeMinRem( step, p ), typeMaxRem( step, p ), p.minVp, p.maxVp );
	}

	function spaceMinRem( mult, p ) {
		return parseFloat( ( p.minBase / 16 * mult ).toFixed( 4 ) );
	}

	function spaceMaxRem( mult, p ) {
		return parseFloat( ( p.maxBase / 16 * mult ).toFixed( 4 ) );
	}

	function spaceClamp( mult, p ) {
		return buildClamp( spaceMinRem( mult, p ), spaceMaxRem( mult, p ), p.minVp, p.maxVp );
	}

	// Resolve what a clamp() value equals at a specific viewport width (in px).
	// Returns a rem string so CSS inherits correctly.
	function resolveAt( minRem, maxRem, minVp, maxVp, vp ) {
		const slope = ( maxRem - minRem ) / ( maxVp - minVp );
		const inter = minRem - slope * minVp;
		const rem   = Math.min( maxRem, Math.max( minRem, inter + slope * vp ) );
		return fmt( rem, 'rem' );
	}

	// -------------------------------------------------------------------------
	// Alpine component
	// -------------------------------------------------------------------------

	function fluidScaleApp( initial ) {
		return {

			// Form values (bound via x-model)
			minVp:         initial.minVp,
			maxVp:         initial.maxVp,
			minBase:       initial.minBase,
			maxBase:       initial.maxBase,
			ratio:         initial.ratio,
			negSteps:      initial.negSteps,
			posSteps:      initial.posSteps,
			gridMaxWidth:  initial.gridMaxWidth,
			gridColumns:   initial.gridColumns,
			gridGutter:    initial.gridGutter,
			builderMapping: initial.builderMapping,
			customPairs:   initial.customPairs,
			diviMapping:   initial.diviMapping,

			// UI state
			previewTab:  'mockup',  // 'mockup' | 'type' | 'space'
			previewDark: false,     // toggled by ◑/☀ button, handled via CSS class
			inspected:   null,      // { label, variable, clamp } | null
			previewVp:   1024,      // simulated viewport width for the preview panel

			// ----------------------------------------------------------------
			// Derived params object (shorthand for math functions)
			// ----------------------------------------------------------------
			get p() {
				return {
					minVp:   parseFloat( this.minVp )   || 320,
					maxVp:   parseFloat( this.maxVp )   || 1240,
					minBase: parseFloat( this.minBase ) || 16,
					maxBase: parseFloat( this.maxBase ) || 20,
					ratio:   parseFloat( this.ratio )   || 1.333,
				};
			},

			// ----------------------------------------------------------------
			// Type scale computed values
			// ----------------------------------------------------------------
			get typeSteps() {
				const steps = [];
				const neg   = parseInt( this.negSteps ) || 2;
				const pos   = parseInt( this.posSteps ) || 5;
				for ( let n = -neg; n <= pos; n++ ) {
					steps.push( {
						n,
						name:  `--step-${ n }`,
						clamp: typeClamp( n, this.p ),
					} );
				}
				return steps;
			},

			// ----------------------------------------------------------------
			// Space scale computed values
			// ----------------------------------------------------------------
			get spaceSteps() {
				return Object.entries( SPACE_MULTIPLIERS ).map( ( [ name, mult ] ) => ( {
					name:   `--space-${ name }`,
					key:    name,
					clamp:  spaceClamp( mult, this.p ),
					pxMin:  Math.round( this.p.minBase * mult * 10 ) / 10,
					pxMax:  Math.round( this.p.maxBase * mult * 10 ) / 10,
					barPct: Math.round( ( mult / 6 ) * 100 ), // 6 = max multiplier (3xl)
				} ) );
			},

			// ----------------------------------------------------------------
			// Clamp string helpers — used in inspect() calls in the template
			// ----------------------------------------------------------------
			stepClamp( n ) {
				return typeClamp( n, this.p );
			},

			spaceClamp( mult ) {
				return spaceClamp( mult, this.p );
			},

			inspect( label, variable, clamp ) {
				if ( this.inspected && this.inspected.variable === variable ) {
					this.inspected = null;
				} else {
					this.inspected = { label, variable, clamp };
				}
			},

			// ----------------------------------------------------------------
			// Custom pairs
			// ----------------------------------------------------------------
			addPair() {
				this.customPairs.push( { from: 's', to: 'l' } );
			},

			removePair( idx ) {
				this.customPairs.splice( idx, 1 );
			},

			spaceStepNames() {
				return Object.keys( SPACE_MULTIPLIERS );
			},

			// ----------------------------------------------------------------
			// CSS variables as real clamp() values — the browser resolves them.
			// The mockup scaler sets the inner frame to previewVp px wide so vw
			// units resolve against that width, not the real browser viewport.
			// ----------------------------------------------------------------
			get mockupVars() {
				const p   = this.p;
				const neg = parseInt( this.negSteps ) || 2;
				const pos = parseInt( this.posSteps ) || 5;
				let css   = ':root{';

				for ( let n = -neg; n <= pos; n++ ) {
					css += `--step-${ n }:${ typeClamp( n, p ) };`;
				}

				Object.entries( SPACE_MULTIPLIERS ).forEach( ( [ name, mult ] ) => {
					css += `--space-${ name }:${ spaceClamp( mult, p ) };`;
				} );

				const gw = parseFloat( this.gridMaxWidth ) || 1240;
				css += `--grid-max-width:${ ( gw / 16 ).toFixed( 4 ) }rem;`;
				css += '}';

				return css;
			},
		};
	}

	// -------------------------------------------------------------------------
	// Register with Alpine when it's ready
	// -------------------------------------------------------------------------
	document.addEventListener( 'alpine:init', function () {
		Alpine.data( 'fluidScale', fluidScaleApp );
	} );

}() );
