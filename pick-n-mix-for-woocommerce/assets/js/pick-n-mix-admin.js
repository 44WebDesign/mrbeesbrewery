/* global jQuery */
( function ( $ ) {
	'use strict';

	$( function () {
		// Show the Pick n Mix data panel and hide inapplicable options when the
		// "Pick n Mix box" product type is selected.
		function toggle() {
			var type = $( '#product-type' ).val();

			if ( 'pick_n_mix' === type ) {
				$( '.pricing' ).addClass( 'hidden' );
			}
		}

		$( 'body' ).on( 'woocommerce-product-type-change', toggle );
		$( '#product-type' ).on( 'change', toggle );
		toggle();

		/**
		 * Rebuild the "price per box size" rows so there is exactly one row for
		 * each item count between the minimum and maximum. Values already typed
		 * (or previously saved) are preserved.
		 */
		function itemLabel( count ) {
			return count + ' ' + ( 1 === count ? 'item' : 'items' );
		}

		function buildPriceRows() {
			var $table = $( '.pnm-wc-prices-table' );
			if ( ! $table.length ) {
				return;
			}

			var min = parseInt( $( '#_pnm_min' ).val(), 10 ) || 1;
			var max = parseInt( $( '#_pnm_max' ).val(), 10 ) || min;
			if ( max < min ) {
				max = min;
			}

			// Saved values (from PHP) plus whatever is currently typed in.
			var saved   = $table.data( 'prices' ) || {};
			var current = {};
			$table.find( 'input[name^="_pnm_prices"]' ).each( function () {
				var m = this.name.match( /\[(\d+)\]/ );
				if ( m ) {
					current[ m[ 1 ] ] = this.value;
				}
			} );

			var $tbody = $table.find( 'tbody' ).empty();
			for ( var c = min; c <= max; c++ ) {
				var val = '';
				if ( undefined !== current[ c ] && '' !== current[ c ] ) {
					val = current[ c ];
				} else if ( undefined !== saved[ c ] ) {
					val = saved[ c ];
				}

				var $row = $(
					'<tr><td></td><td>' +
						'<input type="text" class="wc_input_price pnm-wc-price-input" ' +
						'name="_pnm_prices[' + c + ']" placeholder="0.00" />' +
					'</td></tr>'
				);
				$row.find( 'td' ).eq( 0 ).text( itemLabel( c ) );
				$row.find( 'input' ).val( val );
				$tbody.append( $row );
			}
		}

		$( '#_pnm_min, #_pnm_max' ).on( 'change keyup', buildPriceRows );
		// Rebuild once on load so a stale server-rendered range is corrected.
		buildPriceRows();
	} );
} )( jQuery );
