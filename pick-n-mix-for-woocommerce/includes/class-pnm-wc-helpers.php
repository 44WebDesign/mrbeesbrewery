<?php
/**
 * Shared helper methods.
 *
 * @package PickNMixForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PNM_WC_Helpers
 */
class PNM_WC_Helpers {

	/**
	 * Parse the posted selection ($_POST['pnm_qty']) into a clean array of
	 * [ product_id => qty ], keeping only positive quantities for products
	 * that the box actually allows.
	 *
	 * @param array                 $raw The raw pnm_qty input.
	 * @param WC_Product_Pick_N_Mix $box The box product.
	 * @return array<int,int>
	 */
	public static function parse_selection( $raw, $box ) {
		$selection  = array();
		$selectable = $box->get_selectable_products();

		if ( ! is_array( $raw ) ) {
			return $selection;
		}

		foreach ( $raw as $product_id => $qty ) {
			$product_id = absint( $product_id );
			$qty        = absint( $qty );

			if ( $qty < 1 || ! isset( $selectable[ $product_id ] ) ) {
				continue;
			}

			$selection[ $product_id ] = $qty;
		}

		return $selection;
	}

	/**
	 * Total number of items in a selection array.
	 *
	 * @param array<int,int> $selection Product ID => qty.
	 * @return int
	 */
	public static function count_items( $selection ) {
		return array_sum( array_map( 'absint', (array) $selection ) );
	}

	/**
	 * Validate a selection against the box rules. Returns true on success or a
	 * translated error string describing the first problem found.
	 *
	 * @param array<int,int>        $selection Product ID => qty.
	 * @param WC_Product_Pick_N_Mix $box       The box product.
	 * @return true|string
	 */
	public static function validate_selection( $selection, $box ) {
		$min   = $box->get_pnm_min();
		$max   = $box->get_pnm_max();
		$total = self::count_items( $selection );

		if ( empty( $selection ) ) {
			return __( 'Please choose the items for your box before adding it to the cart.', 'pick-n-mix-for-woocommerce' );
		}

		if ( $total < $min ) {
			return sprintf(
				/* translators: 1: box name, 2: minimum items */
				_n(
					'%1$s requires at least %2$d item.',
					'%1$s requires at least %2$d items.',
					$min,
					'pick-n-mix-for-woocommerce'
				),
				$box->get_name(),
				$min
			);
		}

		if ( $total > $max ) {
			return sprintf(
				/* translators: 1: box name, 2: maximum items */
				_n(
					'%1$s allows at most %2$d item.',
					'%1$s allows at most %2$d items.',
					$max,
					'pick-n-mix-for-woocommerce'
				),
				$box->get_name(),
				$max
			);
		}

		// Confirm each chosen product is still purchasable and in stock.
		$selectable = $box->get_selectable_products();
		foreach ( $selection as $product_id => $qty ) {
			if ( ! isset( $selectable[ $product_id ] ) ) {
				return __( 'One of the items you chose is no longer available. Please review your box.', 'pick-n-mix-for-woocommerce' );
			}

			$product = $selectable[ $product_id ];
			if ( ! $product->has_enough_stock( $qty ) ) {
				return sprintf(
					/* translators: %s: product name */
					__( 'Sorry, "%s" does not have enough stock for your selection.', 'pick-n-mix-for-woocommerce' ),
					$product->get_name()
				);
			}
		}

		return true;
	}

	/**
	 * Build a human readable list of a selection, e.g. "2 × Chicken Chews, 1 × Beef Bites".
	 *
	 * @param array<int,int> $selection Product ID => qty.
	 * @return string
	 */
	public static function format_selection_text( $selection ) {
		$parts = array();
		foreach ( $selection as $product_id => $qty ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$parts[] = sprintf( '%d × %s', $qty, $product->get_name() );
		}
		return implode( ', ', $parts );
	}
}
