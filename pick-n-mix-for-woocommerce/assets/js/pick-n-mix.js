/* global jQuery, pnmWc */
( function ( $ ) {
	'use strict';

	function sprintf( template, value ) {
		return String( template ).replace( '%d', value );
	}

	$( function () {
		var $form = $( '.pnm-wc-form' );
		if ( ! $form.length ) {
			return;
		}

		// ------------------------------------------------------------------
		// Break the picker out of WooCommerce's two-column single-product
		// layout. With the product image hidden, most themes still reserve the
		// empty gallery column, leaving the picker at ~half width. We walk the
		// real DOM (theme-agnostic) to hide that column and force the column
		// holding our form to full width with inline !important styles.
		// ------------------------------------------------------------------
		( function fullWidthLayout() {
			var formEl  = $form[ 0 ];
			var product = formEl.closest( '.product' ) ||
				formEl.closest( '[class*="product-type-"]' ) ||
				formEl.closest( '[class*="post-"]' );

			if ( ! product ) {
				return;
			}

			// Hide the (now empty) product image / gallery column.
			var galleries = product.querySelectorAll(
				'.woocommerce-product-gallery, .images, div.images, figure.woocommerce-product-gallery, .wp-block-woocommerce-product-image-gallery'
			);
			Array.prototype.forEach.call( galleries, function ( el ) {
				if ( ! el.contains( formEl ) ) {
					el.style.setProperty( 'display', 'none', 'important' );
				}
			} );

			// Collect the column(s) to widen: the summary wrapper and the direct
			// child of .product that contains the form (covers nested/flex/grid).
			var cols    = [];
			var summary = formEl.closest( '.summary' ) ||
				formEl.closest( '.entry-summary' ) ||
				formEl.closest( '.product-summary' );
			if ( summary ) {
				cols.push( summary );
			}

			var child = formEl;
			while ( child && child.parentElement && child.parentElement !== product ) {
				child = child.parentElement;
			}
			if ( child && child !== summary && child.parentElement === product ) {
				cols.push( child );
			}

			cols.forEach( function ( el ) {
				el.classList.add( 'pnm-wc-fullwidth-col' );
				el.style.setProperty( 'width', '100%', 'important' );
				el.style.setProperty( 'max-width', '100%', 'important' );
				el.style.setProperty( 'flex', '1 1 100%', 'important' );
				el.style.setProperty( 'flex-basis', '100%', 'important' );
				el.style.setProperty( 'float', 'none', 'important' );
				el.style.setProperty( 'grid-column', '1 / -1', 'important' );
				el.style.setProperty( 'margin-left', '0', 'important' );
				el.style.setProperty( 'margin-right', '0', 'important' );
			} );
		}() );

		var min = parseInt( $form.data( 'min' ), 10 ) || 1;
		var max = parseInt( $form.data( 'max' ), 10 ) || 1;

		var $current  = $form.find( '.pnm-wc-count-current' );
		var $message  = $form.find( '.pnm-wc-message' );
		var $progress = $form.find( '.pnm-wc-progress-bar' );
		var $submit   = $form.find( '.pnm-wc-submit' );
		var $bagItems = $form.find( '.pnm-wc-bag-items' );
		var $bagEmpty = $form.find( '.pnm-wc-bag-empty' );
		var $bag      = $form.find( '.pnm-wc-bag' );
		var $priceVal = $form.find( '.pnm-wc-price-value' );

		function qtyInput( id ) {
			return $form.find( '.pnm-wc-qty-input[name="pnm_qty[' + id + ']"]' );
		}

		function total() {
			var sum = 0;
			$form.find( '.pnm-wc-qty-input' ).each( function () {
				sum += parseInt( this.value, 10 ) || 0;
			} );
			return sum;
		}

		/**
		 * Redraw the contents of the on-screen bag from the hidden inputs.
		 */
		function renderBag() {
			$bagItems.empty();
			var hasItems = false;

			$form.find( '.pnm-wc-jar' ).each( function () {
				var $jar = $( this );
				var id   = $jar.data( 'product-id' );
				var qty  = parseInt( qtyInput( id ).val(), 10 ) || 0;
				if ( qty < 1 ) {
					return;
				}
				hasItems = true;

				var $chip = $(
					'<li class="pnm-wc-bag-item">' +
						'<img class="pnm-wc-bag-item-thumb" alt="" src="' + $jar.data( 'thumb' ) + '" />' +
						'<span class="pnm-wc-bag-item-name"></span>' +
						'<span class="pnm-wc-bag-item-qty">×' + qty + '</span>' +
						'<button type="button" class="pnm-wc-bag-remove" aria-label="' + pnmWc.i18nRemove + '">×</button>' +
					'</li>'
				);
				$chip.find( '.pnm-wc-bag-item-name' ).text( $jar.data( 'name' ) );
				$chip.data( 'product-id', id );
				$bagItems.append( $chip );
			} );

			$bagEmpty.toggle( ! hasItems );
		}

		/**
		 * Update counters, progress, per-jar badges and the submit state.
		 */
		function refresh() {
			var count = total();

			$current.text( count );

			var pct = max > 0 ? Math.min( 100, Math.round( ( count / max ) * 100 ) ) : 0;
			$progress.css( 'width', pct + '%' );

			// Per-jar badges and disabled state.
			$form.find( '.pnm-wc-jar' ).each( function () {
				var $jar  = $( this );
				var id    = $jar.data( 'product-id' );
				var val   = parseInt( qtyInput( id ).val(), 10 ) || 0;
				var itMax = parseInt( $jar.data( 'max' ), 10 );
				var full  = count >= max || ( ! isNaN( itMax ) && val >= itMax );

				$jar.find( '.pnm-wc-jar-badge' ).text( val ).toggleClass( 'is-active', val > 0 );
				$jar.toggleClass( 'is-picked', val > 0 );
				// Only block adding more; out-of-stock jars stay disabled always.
				if ( ! $jar.hasClass( 'pnm-wc-jar--oos' ) ) {
					$jar.find( '.pnm-wc-jar-btn' ).prop( 'disabled', full );
				}
			} );

			var ready = count >= min && count <= max;
			$form.toggleClass( 'pnm-wc-ready', ready );

			// Live bag price: show the price for the current size once the bag is
			// valid, otherwise show the starting ("from") price for the minimum.
			var prices = pnmWc.pricesHtml || {};
			if ( ready && prices[ count ] ) {
				$priceVal.html( prices[ count ] );
			} else if ( prices[ min ] ) {
				$priceVal.html( pnmWc.i18nFrom + ' ' + prices[ min ] );
			}

			if ( count > max ) {
				$message.text( sprintf( pnmWc.i18nOver, count - max ) );
			} else if ( count === 0 ) {
				$message.text( '' );
			} else if ( count >= max ) {
				$message.text( pnmWc.i18nFull );
			} else if ( ready ) {
				$message.text( pnmWc.i18nReady );
			} else {
				$message.text( sprintf( pnmWc.i18nRemaining, min - count ) );
			}

			$submit.prop( 'disabled', ! ready );
		}

		/**
		 * Animate a treat flying from a jar into the bag.
		 */
		function flyToBag( $jar ) {
			var thumb = $jar.data( 'thumb' );
			var $img  = $jar.find( '.pnm-wc-jar-image img' );
			if ( ! thumb || ! $img.length || ! $bag.length ) {
				return;
			}

			var start = $img[ 0 ].getBoundingClientRect();
			var end   = $bag[ 0 ].getBoundingClientRect();

			var $fly = $( '<img class="pnm-wc-fly" alt="" />' ).attr( 'src', thumb ).css( {
				left: start.left + start.width / 2 + 'px',
				top: start.top + start.height / 2 + 'px'
			} );
			$( 'body' ).append( $fly );

			// Force layout then animate towards the bag.
			// eslint-disable-next-line no-unused-expressions
			$fly[ 0 ].offsetWidth;

			var targetX = end.left + end.width / 2;
			var targetY = end.top + Math.min( end.height * 0.35, 80 );

			$fly.css( {
				left: targetX + 'px',
				top: targetY + 'px',
				opacity: 0.2,
				transform: 'translate(-50%, -50%) scale(0.3) rotate(20deg)'
			} );

			$bag.addClass( 'pnm-wc-bag--bump' );
			window.setTimeout( function () {
				$bag.removeClass( 'pnm-wc-bag--bump' );
			}, 350 );

			$fly.on( 'transitionend', function () {
				$fly.remove();
			} );
			// Safety cleanup.
			window.setTimeout( function () {
				$fly.remove();
			}, 900 );
		}

		function addOne( $jar ) {
			var id    = $jar.data( 'product-id' );
			var $inp  = qtyInput( id );
			var val   = parseInt( $inp.val(), 10 ) || 0;
			var itMax = parseInt( $jar.data( 'max' ), 10 );

			if ( total() >= max ) {
				return;
			}
			if ( ! isNaN( itMax ) && val >= itMax ) {
				return;
			}

			$inp.val( val + 1 );
			flyToBag( $jar );
			renderBag();
			refresh();
		}

		function removeOne( id ) {
			var $inp = qtyInput( id );
			var val  = parseInt( $inp.val(), 10 ) || 0;
			if ( val <= 0 ) {
				return;
			}
			$inp.val( val - 1 );
			renderBag();
			refresh();
		}

		// Tap a jar on the stall to drop one in the bag.
		$form.on( 'click', '.pnm-wc-jar-btn', function ( e ) {
			e.preventDefault();
			addOne( $( this ).closest( '.pnm-wc-jar' ) );
		} );

		// Remove from within the bag.
		$form.on( 'click', '.pnm-wc-bag-remove', function ( e ) {
			e.preventDefault();
			removeOne( $( this ).closest( '.pnm-wc-bag-item' ).data( 'product-id' ) );
		} );

		renderBag();
		refresh();
	} );
} )( jQuery );
