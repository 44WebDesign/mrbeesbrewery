<?php
/**
 * Cart & order integration for pick n mix boxes.
 *
 * @package PickNMixForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PNM_WC_Cart
 */
class PNM_WC_Cart {

	/**
	 * Singleton instance.
	 *
	 * @var PNM_WC_Cart|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return PNM_WC_Cart
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook into the cart and checkout lifecycle.
	 */
	private function __construct() {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 3 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_box_price' ), 10, 1 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_order_line_item' ), 10, 4 );
		add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'order_item_meta_label' ), 10, 3 );

		// Reduce the child products' stock when the order's stock is reduced.
		add_action( 'woocommerce_reduce_order_stock', array( $this, 'reduce_child_stock' ), 10, 1 );
	}

	/**
	 * Validate the customer's selection before the box is added to the cart.
	 *
	 * @param bool $passed     Whether validation passed so far.
	 * @param int  $product_id Product being added.
	 * @param int  $quantity   Quantity being added.
	 * @return bool
	 */
	public function validate_add_to_cart( $passed, $product_id, $quantity ) {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product_Pick_N_Mix ) {
			return $passed;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce add-to-cart is nonce-protected upstream.
		$raw       = isset( $_POST['pnm_qty'] ) ? wc_clean( wp_unslash( $_POST['pnm_qty'] ) ) : array();
		$selection = PNM_WC_Helpers::parse_selection( $raw, $product );
		$result    = PNM_WC_Helpers::validate_selection( $selection, $product );

		if ( true !== $result ) {
			wc_add_notice( $result, 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Attach the parsed selection to the cart item.
	 *
	 * @param array $cart_item_data Existing cart item data.
	 * @param int   $product_id     Product ID.
	 * @param int   $variation_id   Variation ID (unused).
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product_Pick_N_Mix ) {
			return $cart_item_data;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce add-to-cart is nonce-protected upstream.
		$raw       = isset( $_POST['pnm_qty'] ) ? wc_clean( wp_unslash( $_POST['pnm_qty'] ) ) : array();
		$selection = PNM_WC_Helpers::parse_selection( $raw, $product );

		if ( empty( $selection ) ) {
			return $cart_item_data;
		}

		$cart_item_data['pnm_selection'] = $selection;

		// Ensure two boxes with different contents stay on separate cart lines,
		// while identical boxes merge and increment quantity.
		$cart_item_data['pnm_hash'] = md5( wp_json_encode( $selection ) );

		return $cart_item_data;
	}

	/**
	 * Restore our custom data when the cart is loaded from the session.
	 *
	 * @param array $cart_item Cart item.
	 * @param array $values    Session values.
	 * @return array
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {
		if ( isset( $values['pnm_selection'] ) ) {
			$cart_item['pnm_selection'] = $values['pnm_selection'];
		}
		if ( isset( $values['pnm_hash'] ) ) {
			$cart_item['pnm_hash'] = $values['pnm_hash'];
		}
		return $cart_item;
	}

	/**
	 * Show the box contents beneath the line item in the cart and checkout.
	 *
	 * @param array $item_data Existing display data.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function display_cart_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['pnm_selection'] ) || ! is_array( $cart_item['pnm_selection'] ) ) {
			return $item_data;
		}

		foreach ( $cart_item['pnm_selection'] as $product_id => $qty ) {
			$child = wc_get_product( $product_id );
			if ( ! $child ) {
				continue;
			}
			$item_data[] = array(
				'key'     => __( 'Includes', 'pick-n-mix-for-woocommerce' ),
				'value'   => sprintf( '%d × %s', absint( $qty ), $child->get_name() ),
				'display' => '',
			);
		}

		return $item_data;
	}

	/**
	 * Force the box's fixed price on the cart line, ignoring child prices.
	 *
	 * @param WC_Cart $cart Cart object.
	 */
	public function set_box_price( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['pnm_selection'] ) ) {
				continue;
			}

			$product = $cart_item['data'];
			if ( ! $product instanceof WC_Product_Pick_N_Mix ) {
				continue;
			}

			// Charge the price that matches the number of items in this box.
			$count = PNM_WC_Helpers::count_items( $cart_item['pnm_selection'] );
			$price = $product->get_price_for_count( $count );

			if ( '' !== $price ) {
				$product->set_price( $price );
			}
		}
	}

	/**
	 * Persist the selection onto the order line item so it appears on the
	 * order, emails and admin.
	 *
	 * @param WC_Order_Item_Product $item          Order line item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item values.
	 * @param WC_Order              $order         Order object.
	 */
	public function save_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['pnm_selection'] ) || ! is_array( $values['pnm_selection'] ) ) {
			return;
		}

		// Human-readable summary for order/emails.
		$item->add_meta_data(
			__( 'Box contents', 'pick-n-mix-for-woocommerce' ),
			PNM_WC_Helpers::format_selection_text( $values['pnm_selection'] ),
			true
		);

		// Machine-readable copy for stock handling and reporting.
		$item->add_meta_data( '_pnm_selection', $values['pnm_selection'], true );
	}

	/**
	 * Hide the raw meta key from order displays (keep the readable one).
	 *
	 * @param string        $display_key Meta key shown.
	 * @param WC_Meta_Data  $meta        Meta object.
	 * @param WC_Order_Item $item        Order item.
	 * @return string
	 */
	public function order_item_meta_label( $display_key, $meta, $item ) {
		if ( '_pnm_selection' === $meta->key ) {
			return '';
		}
		return $display_key;
	}

	/**
	 * Reduce stock for each child product contained in purchased boxes.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function reduce_child_stock( $order ) {
		foreach ( $order->get_items() as $item ) {
			$selection = $item->get_meta( '_pnm_selection', true );
			if ( empty( $selection ) || ! is_array( $selection ) ) {
				continue;
			}

			$box_qty = max( 1, (int) $item->get_quantity() );

			foreach ( $selection as $product_id => $qty ) {
				$child = wc_get_product( $product_id );
				if ( ! $child || ! $child->managing_stock() ) {
					continue;
				}

				$reduce_by = absint( $qty ) * $box_qty;
				if ( $reduce_by < 1 ) {
					continue;
				}

				$new_stock = wc_update_product_stock( $child, $reduce_by, 'decrease' );

				/* translators: 1: product name, 2: old stock, 3: new stock */
				$order->add_order_note(
					sprintf(
						__( 'Pick n Mix: reduced stock for "%1$s" by %2$d (new level: %3$s).', 'pick-n-mix-for-woocommerce' ),
						$child->get_name(),
						$reduce_by,
						$new_stock
					)
				);
			}
		}
	}
}
