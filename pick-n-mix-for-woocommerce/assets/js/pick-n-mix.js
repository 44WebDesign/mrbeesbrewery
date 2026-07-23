/* global jQuery, pnmWc */
( function ( $ ) {
	'use strict';

	function sprintf( template, value ) {
		return template.replace( '%d', value );
	}

	$( function () {
		var $form = $( '.pnm-wc-form' );
		if ( ! $form.length ) {
			return;
		}

		var min = parseInt( $form.data( 'min' ), 10 ) || 1;
		var max = parseInt( $form.data( 'max' ), 10 ) || 1;

		var $current  = $form.find( '.pnm-wc-count-current' );
		var $message  = $form.find( '.pnm-wc-message' );
		var $progress = $form.find( '.pnm-wc-progress-bar' );
		var $submit   = $form.find( '.pnm-wc-submit' );

		function total() {
			var sum = 0;
			$form.find( '.pnm-wc-qty-input' ).each( function () {
				sum += parseInt( this.value, 10 ) || 0;
			} );
			return sum;
		}

		function refresh() {
			var count     = total();
			var remaining = max - count;

			$current.text( count );

			// Progress bar (relative to max).
			var pct = max > 0 ? Math.min( 100, Math.round( ( count / max ) * 100 ) ) : 0;
			$progress.css( 'width', pct + '%' );

			// Disable "+" buttons once the box is full.
			$form.find( '.pnm-wc-plus' ).prop( 'disabled', count >= max );

			// Enforce per-item stock maximums even when the box is not full.
			$form.find( '.pnm-wc-product' ).each( function () {
				var $row  = $( this );
				var $inp  = $row.find( '.pnm-wc-qty-input' );
				var val   = parseInt( $inp.val(), 10 ) || 0;
				var itMax = parseInt( $inp.attr( 'max' ), 10 );
				var $plus = $row.find( '.pnm-wc-plus' );
				if ( count >= max || ( ! isNaN( itMax ) && val >= itMax ) ) {
					$plus.prop( 'disabled', true );
				}
			} );

			var ready = count >= min && count <= max;

			$form.toggleClass( 'pnm-wc-ready', ready );

			if ( count > max ) {
				$message.text( sprintf( pnmWc.i18nOver, count - max ) );
			} else if ( ready ) {
				$message.text( count >= max ? pnmWc.i18nFull : pnmWc.i18nReady );
			} else {
				$message.text( sprintf( pnmWc.i18nRemaining, min - count > 0 ? min - count : remaining ) );
			}

			$submit.prop( 'disabled', ! ready );
		}

		$form.on( 'click', '.pnm-wc-plus', function ( e ) {
			e.preventDefault();
			var $input = $( this ).closest( '.pnm-wc-product' ).find( '.pnm-wc-qty-input' );
			var val    = parseInt( $input.val(), 10 ) || 0;
			var itMax  = parseInt( $input.attr( 'max' ), 10 );

			if ( total() >= max ) {
				return;
			}
			if ( ! isNaN( itMax ) && val >= itMax ) {
				return;
			}

			$input.val( val + 1 );
			refresh();
		} );

		$form.on( 'click', '.pnm-wc-minus', function ( e ) {
			e.preventDefault();
			var $input = $( this ).closest( '.pnm-wc-product' ).find( '.pnm-wc-qty-input' );
			var val    = parseInt( $input.val(), 10 ) || 0;
			if ( val <= 0 ) {
				return;
			}
			$input.val( val - 1 );
			refresh();
		} );

		refresh();
	} );
} )( jQuery );
