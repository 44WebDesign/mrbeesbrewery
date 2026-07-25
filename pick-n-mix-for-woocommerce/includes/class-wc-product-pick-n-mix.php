<?php
/**
 * The Pick n Mix product type.
 *
 * @package PickNMixForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Product_Pick_N_Mix
 *
 * A box product whose price is fixed regardless of which child products the
 * customer selects. The selectable products and the required number of items
 * are stored as meta on the product.
 */
class WC_Product_Pick_N_Mix extends WC_Product {

	/**
	 * Default meta values for the product type.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'pnm_min'        => 3,
		'pnm_max'        => 3,
		'pnm_prices'     => array(),
		'pnm_products'   => array(),
		'pnm_categories' => array(),
		'pnm_colors'     => array(),
	);

	/**
	 * Product type slug.
	 *
	 * @return string
	 */
	public function get_type() {
		return PNM_WC_PRODUCT_TYPE;
	}

	/**
	 * Pick n mix boxes are always purchasable if they have a price and options.
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		$purchasable = '' !== $this->get_price() && $this->get_status() === 'publish';
		return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
	}

	/**
	 * Boxes are not sold individually by default so multiple boxes are allowed.
	 *
	 * @return bool
	 */
	public function is_sold_individually() {
		return apply_filters( 'woocommerce_is_sold_individually', false, $this );
	}

	/**
	 * Add to cart button text on the single product page.
	 *
	 * @return string
	 */
	public function single_add_to_cart_text() {
		return apply_filters( 'woocommerce_product_single_add_to_cart_text', __( 'Add box to cart', 'pick-n-mix-for-woocommerce' ), $this );
	}

	/**
	 * Add to cart text on shop/archive pages. Sends the customer to the product
	 * page because a box has to be configured before it can be bought.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		return apply_filters( 'woocommerce_product_add_to_cart_text', __( 'Build your box', 'pick-n-mix-for-woocommerce' ), $this );
	}

	/**
	 * Loop add to cart url points to the single product page.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		return apply_filters( 'woocommerce_product_add_to_cart_url', $this->get_permalink(), $this );
	}

	/**
	 * The loop button never adds straight to the cart.
	 *
	 * @return string
	 */
	public function get_add_to_cart_text() {
		return $this->add_to_cart_text();
	}

	/**
	 * Force customers through the single product page from the loop.
	 *
	 * @return bool
	 */
	public function supports( $feature ) {
		if ( 'ajax_add_to_cart' === $feature ) {
			return false;
		}
		return parent::supports( $feature );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters / setters for the custom meta.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Minimum number of items the customer must select.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_pnm_min( $context = 'view' ) {
		return (int) $this->get_prop( 'pnm_min', $context );
	}

	/**
	 * Maximum number of items the customer may select.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_pnm_max( $context = 'view' ) {
		return (int) $this->get_prop( 'pnm_max', $context );
	}

	/**
	 * Explicitly selectable product IDs.
	 *
	 * @param string $context View or edit context.
	 * @return array
	 */
	public function get_pnm_products( $context = 'view' ) {
		return array_map( 'absint', (array) $this->get_prop( 'pnm_products', $context ) );
	}

	/**
	 * Product category IDs whose products are selectable.
	 *
	 * @param string $context View or edit context.
	 * @return array
	 */
	public function get_pnm_categories( $context = 'view' ) {
		return array_map( 'absint', (array) $this->get_prop( 'pnm_categories', $context ) );
	}

	/**
	 * Set the minimum item count.
	 *
	 * @param int $value Value.
	 */
	public function set_pnm_min( $value ) {
		$this->set_prop( 'pnm_min', max( 1, absint( $value ) ) );
	}

	/**
	 * Set the maximum item count.
	 *
	 * @param int $value Value.
	 */
	public function set_pnm_max( $value ) {
		$this->set_prop( 'pnm_max', max( 1, absint( $value ) ) );
	}

	/**
	 * Set the selectable product IDs.
	 *
	 * @param array $value Product IDs.
	 */
	public function set_pnm_products( $value ) {
		$this->set_prop( 'pnm_products', array_values( array_unique( array_map( 'absint', (array) $value ) ) ) );
	}

	/**
	 * Set the selectable category IDs.
	 *
	 * @param array $value Category term IDs.
	 */
	public function set_pnm_categories( $value ) {
		$this->set_prop( 'pnm_categories', array_values( array_unique( array_map( 'absint', (array) $value ) ) ) );
	}

	/**
	 * Get the per-quantity price table, keyed by item count.
	 *
	 * @param string $context View or edit context.
	 * @return array<int,string>
	 */
	public function get_pnm_prices( $context = 'view' ) {
		$prices = (array) $this->get_prop( 'pnm_prices', $context );
		$clean  = array();
		foreach ( $prices as $count => $price ) {
			$clean[ (int) $count ] = $price;
		}
		ksort( $clean );
		return $clean;
	}

	/**
	 * Set the per-quantity price table.
	 *
	 * @param array<int|string,mixed> $value Count => price.
	 */
	public function set_pnm_prices( $value ) {
		$clean = array();
		foreach ( (array) $value as $count => $price ) {
			$count = absint( $count );
			if ( $count < 1 || '' === $price || null === $price ) {
				continue;
			}
			$clean[ $count ] = wc_format_decimal( $price );
		}
		ksort( $clean );
		$this->set_prop( 'pnm_prices', $clean );
	}

	/**
	 * Look up the box price for a given number of items. Falls back to the
	 * nearest defined lower count, then the cheapest defined price, then the
	 * product's regular price, so a box can never end up unpriced.
	 *
	 * @param int $count Number of items in the box.
	 * @return string Price as a decimal string, or '' if nothing is set.
	 */
	public function get_price_for_count( $count ) {
		$count  = absint( $count );
		$prices = $this->get_pnm_prices();

		if ( isset( $prices[ $count ] ) ) {
			return $prices[ $count ];
		}

		// Nearest defined count at or below the requested count.
		$lower = null;
		foreach ( $prices as $defined => $price ) {
			if ( $defined <= $count && ( null === $lower || $defined > $lower ) ) {
				$lower = $defined;
			}
		}
		if ( null !== $lower ) {
			return $prices[ $lower ];
		}

		if ( ! empty( $prices ) ) {
			$counts = array_keys( $prices );
			return $prices[ min( $counts ) ];
		}

		return $this->get_regular_price();
	}

	/**
	 * Get the saved colour overrides, keyed by colour field slug.
	 *
	 * @param string $context View or edit context.
	 * @return array<string,string>
	 */
	public function get_pnm_colors( $context = 'view' ) {
		return (array) $this->get_prop( 'pnm_colors', $context );
	}

	/**
	 * Set the colour overrides. Values are sanitised as hex colours.
	 *
	 * @param array<string,string> $value Slug => hex colour.
	 */
	public function set_pnm_colors( $value ) {
		$clean = array();
		foreach ( (array) $value as $key => $hex ) {
			$hex = sanitize_hex_color( $hex );
			if ( $hex ) {
				$clean[ sanitize_key( $key ) ] = $hex;
			}
		}
		$this->set_prop( 'pnm_colors', $clean );
	}

	/**
	 * Show a price range (cheapest box to dearest box) on shop and product pages.
	 *
	 * @param string $deprecated Unused.
	 * @return string
	 */
	public function get_price_html( $deprecated = '' ) {
		$prices = array_filter(
			array_map( 'floatval', $this->get_pnm_prices() ),
			function ( $price ) {
				return $price > 0;
			}
		);

		if ( empty( $prices ) ) {
			return apply_filters( 'woocommerce_get_price_html', '', $this );
		}

		$min = min( $prices );
		$max = max( $prices );

		$html = ( $min === $max )
			? wc_price( $min )
			: wc_format_price_range( $min, $max );

		return apply_filters( 'woocommerce_get_price_html', $html, $this );
	}

	/**
	 * Resolve the full list of products the customer can choose from,
	 * combining the explicit product list and any chosen categories.
	 *
	 * Only published, purchasable, in-stock simple products are returned.
	 *
	 * @return WC_Product[] Keyed by product ID.
	 */
	public function get_selectable_products() {
		$ids = $this->get_pnm_products();

		$categories = $this->get_pnm_categories();
		if ( ! empty( $categories ) ) {
			$query_ids = wc_get_products(
				array(
					'status'   => 'publish',
					'type'     => array( 'simple' ),
					'limit'    => -1,
					'return'   => 'ids',
					'category' => array_map(
						function ( $term_id ) {
							$term = get_term( $term_id, 'product_cat' );
							return ( $term && ! is_wp_error( $term ) ) ? $term->slug : '';
						},
						$categories
					),
				)
			);
			$ids = array_merge( $ids, $query_ids );
		}

		$ids       = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		$available = array();

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product || ! $product->is_type( 'simple' ) ) {
				continue;
			}
			if ( 'publish' !== $product->get_status() || ! $product->is_purchasable() ) {
				continue;
			}
			$available[ $id ] = $product;
		}

		/**
		 * Filter the products a customer can pick from for a given box.
		 *
		 * @param WC_Product[]            $available Keyed by product ID.
		 * @param WC_Product_Pick_N_Mix   $box       The box product.
		 */
		return apply_filters( 'pnm_wc_selectable_products', $available, $this );
	}
}
