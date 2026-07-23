/* global jQuery */
( function ( $ ) {
	'use strict';

	$( function () {
		// Show the Pick n Mix data panel and hide inapplicable options when the
		// "Pick n Mix box" product type is selected.
		function toggle() {
			var type = $( '#product-type' ).val();

			if ( 'pick_n_mix' === type ) {
				// Our tab is flagged with show_if_pick_n_mix; WooCommerce core
				// handles the class-based toggling, but we also make sure the
				// general pricing fields don't confuse the shopkeeper.
				$( '.pricing' ).addClass( 'hidden' );
			}
		}

		$( 'body' ).on( 'woocommerce-product-type-change', toggle );
		$( '#product-type' ).on( 'change', toggle );
		toggle();
	} );
} )( jQuery );
