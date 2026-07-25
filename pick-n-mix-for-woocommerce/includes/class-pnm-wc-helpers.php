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
	 * The configurable colour fields shared by the admin pickers and the
	 * front-end output. Each maps a slug to a human label, the CSS custom
	 * property it controls, and a default value.
	 *
	 * @return array<string,array{label:string,var:string,default:string}>
	 */
	public static function color_fields() {
		return array(
			'accent'   => array(
				'label'   => __( 'Primary colour (signboard, buttons & price)', 'pick-n-mix-for-woocommerce' ),
				'var'     => '--pnm-accent',
				'default' => '#e05a8a',
			),
			'accent2'  => array(
				'label'   => __( 'Highlight colour (badges & progress bar)', 'pick-n-mix-for-woocommerce' ),
				'var'     => '--pnm-accent-2',
				'default' => '#ffd23f',
			),
			'ink'      => array(
				'label'   => __( 'Text colour', 'pick-n-mix-for-woocommerce' ),
				'var'     => '--pnm-ink',
				'default' => '#3a2b2f',
			),
			'jar'      => array(
				'label'   => __( 'Treat tile background', 'pick-n-mix-for-woocommerce' ),
				'var'     => '--pnm-jar',
				'default' => '#ffffff',
			),
			'border'   => array(
				'label'   => __( 'Treat tile border', 'pick-n-mix-for-woocommerce' ),
				'var'     => '--pnm-border',
				'default' => '#f0d9e2',
			),
			'bag'      => array(
				'label'   => __( 'Bag colour', 'pick-n-mix-for-woocommerce' ),
				'var'     => '--pnm-bag',
				'default' => '#f3e4c7',
			),
			'bag_dark' => array(
				'label'   => __( 'Bag trim / fold colour', 'pick-n-mix-for-woocommerce' ),
				'var'     => '--pnm-bag-dark',
				'default' => '#e4d0aa',
			),
		);
	}

	/**
	 * Resolve the effective default for a colour field: the site-wide global
	 * default set under WooCommerce → Products → Pick n Mix if present,
	 * otherwise the plugin's built-in default.
	 *
	 * @param string $slug Colour field slug.
	 * @return string Hex colour, or '' for an unknown slug.
	 */
	public static function get_default_color( $slug ) {
		$fields = self::color_fields();
		if ( ! isset( $fields[ $slug ] ) ) {
			return '';
		}

		$global = '';
		if ( class_exists( 'PNM_WC_Settings' ) ) {
			$global = sanitize_hex_color( (string) get_option( PNM_WC_Settings::OPTION_PREFIX . $slug, '' ) );
		}

		return $global ? $global : $fields[ $slug ]['default'];
	}

	/**
	 * Build the inline CSS-variable declarations for a box. Each colour resolves
	 * as: the box's own override, then the global default, then the built-in
	 * default, so the stall always looks complete.
	 *
	 * @param array<string,string> $colors Saved slug => hex map for the box.
	 * @return string e.g. "--pnm-accent:#e05a8a;--pnm-bag:#f3e4c7;"
	 */
	public static function build_color_style( $colors ) {
		$style = '';
		foreach ( self::color_fields() as $slug => $field ) {
			$value = ( ! empty( $colors[ $slug ] ) ) ? sanitize_hex_color( $colors[ $slug ] ) : '';
			if ( ! $value ) {
				$value = self::get_default_color( $slug );
			}
			if ( $value ) {
				$style .= $field['var'] . ':' . $value . ';';
			}
		}
		return $style;
	}

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
