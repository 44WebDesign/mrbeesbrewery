<?php
/**
 * Admin: product type registration, data panel and saving.
 *
 * @package PickNMixForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class PNM_WC_Admin
 */
class PNM_WC_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var PNM_WC_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return PNM_WC_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook into admin.
	 */
	private function __construct() {
		add_filter( 'product_type_selector', array( $this, 'add_product_type' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_product_data_panel' ) );
		add_action( 'woocommerce_process_product_meta_' . PNM_WC_PRODUCT_TYPE, array( $this, 'save_product_meta' ) );

		// The General price field is not shown for our type, so show our own.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add "Pick n Mix box" to the product type dropdown.
	 *
	 * @param array $types Existing product types.
	 * @return array
	 */
	public function add_product_type( $types ) {
		$types[ PNM_WC_PRODUCT_TYPE ] = __( 'Pick n Mix box', 'pick-n-mix-for-woocommerce' );
		return $types;
	}

	/**
	 * Add the "Pick n Mix" tab to the product data metabox.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_product_data_tab( $tabs ) {
		$tabs['pnm_wc'] = array(
			'label'    => __( 'Pick n Mix', 'pick-n-mix-for-woocommerce' ),
			'target'   => 'pnm_wc_product_data',
			'class'    => array( 'show_if_' . PNM_WC_PRODUCT_TYPE ),
			'priority' => 15,
		);

		// Hide the tax/shipping panels that don't apply cleanly, keep pricing here.
		return $tabs;
	}

	/**
	 * Render the settings panel for the box.
	 */
	public function render_product_data_panel() {
		global $post, $product_object;

		$product = ( $product_object instanceof WC_Product ) ? $product_object : wc_get_product( $post->ID );

		$min          = 3;
		$max          = 3;
		$prices       = array();
		$product_ids  = array();
		$category_ids = array();

		if ( $product instanceof WC_Product_Pick_N_Mix ) {
			$min          = $product->get_pnm_min( 'edit' );
			$max          = $product->get_pnm_max( 'edit' );
			$prices       = $product->get_pnm_prices( 'edit' );
			$product_ids  = $product->get_pnm_products( 'edit' );
			$category_ids = $product->get_pnm_categories( 'edit' );
		}

		echo '<div id="pnm_wc_product_data" class="panel woocommerce_options_panel hidden">';

		echo '<div class="options_group">';

		// Minimum items.
		woocommerce_wp_text_input(
			array(
				'id'                => '_pnm_min',
				'value'             => $min,
				'label'             => __( 'Minimum items', 'pick-n-mix-for-woocommerce' ),
				'desc_tip'          => true,
				'description'       => __( 'The fewest items the customer must add to the box.', 'pick-n-mix-for-woocommerce' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			)
		);

		// Maximum items.
		woocommerce_wp_text_input(
			array(
				'id'                => '_pnm_max',
				'value'             => $max,
				'label'             => __( 'Maximum items', 'pick-n-mix-for-woocommerce' ),
				'desc_tip'          => true,
				'description'       => __( 'The most items allowed in the box. Set the same as the minimum to require an exact number (e.g. "pick any 3").', 'pick-n-mix-for-woocommerce' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			)
		);

		echo '</div>';

		// Per-quantity price table. Rows are rebuilt by JS as the min/max change,
		// but we render the current range server-side so it works without JS too.
		echo '<div class="options_group pnm-wc-prices-group">';
		?>
		<p class="form-field">
			<label><?php esc_html_e( 'Box price per size', 'pick-n-mix-for-woocommerce' ); ?></label>
			<span class="description" style="display:inline-block;max-width:60%;vertical-align:top;">
				<?php
				printf(
					/* translators: %s: currency symbol */
					esc_html__( 'Set the price (%s) for each possible number of items in the box. One row appears for every size between your minimum and maximum.', 'pick-n-mix-for-woocommerce' ),
					esc_html( get_woocommerce_currency_symbol() )
				);
				?>
			</span>
		</p>
		<table class="widefat pnm-wc-prices-table" style="width:60%;margin:0 0 12px 12px;" data-prices="<?php echo esc_attr( wp_json_encode( (object) $prices ) ); ?>">
			<thead>
				<tr>
					<th style="width:40%;"><?php esc_html_e( 'Items in box', 'pick-n-mix-for-woocommerce' ); ?></th>
					<th><?php
						printf(
							/* translators: %s: currency symbol */
							esc_html__( 'Box price (%s)', 'pick-n-mix-for-woocommerce' ),
							esc_html( get_woocommerce_currency_symbol() )
						);
					?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$row_min = max( 1, (int) $min );
				$row_max = max( $row_min, (int) $max );
				for ( $count = $row_min; $count <= $row_max; $count++ ) {
					$value = isset( $prices[ $count ] ) ? $prices[ $count ] : '';
					echo '<tr>';
					echo '<td>' . sprintf(
						/* translators: %d: number of items */
						esc_html( _n( '%d item', '%d items', $count, 'pick-n-mix-for-woocommerce' ) ),
						(int) $count
					) . '</td>';
					echo '<td><input type="text" class="wc_input_price pnm-wc-price-input" name="_pnm_prices[' . esc_attr( $count ) . ']" value="' . esc_attr( $value ) . '" placeholder="0.00" /></td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>
		<?php
		echo '</div>';

		echo '<div class="options_group">';

		// Selectable products (product search multiselect).
		?>
		<p class="form-field">
			<label for="_pnm_products"><?php esc_html_e( 'Selectable products', 'pick-n-mix-for-woocommerce' ); ?></label>
			<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="_pnm_products" name="_pnm_products[]" data-placeholder="<?php esc_attr_e( 'Search for products…', 'pick-n-mix-for-woocommerce' ); ?>" data-action="woocommerce_json_search_products" data-exclude="<?php echo esc_attr( $post->ID ); ?>">
				<?php
				foreach ( $product_ids as $product_id ) {
					$selectable_product = wc_get_product( $product_id );
					if ( $selectable_product ) {
						echo '<option value="' . esc_attr( $product_id ) . '" selected="selected">' . esc_html( wp_strip_all_tags( $selectable_product->get_formatted_name() ) ) . '</option>';
					}
				}
				?>
			</select>
			<?php echo wc_help_tip( __( 'Individual simple products the customer can choose from. Combine with categories below if you like.', 'pick-n-mix-for-woocommerce' ) ); ?>
		</p>

		<p class="form-field">
			<label for="_pnm_categories"><?php esc_html_e( 'Selectable categories', 'pick-n-mix-for-woocommerce' ); ?></label>
			<select class="wc-enhanced-select" multiple="multiple" style="width: 50%;" id="_pnm_categories" name="_pnm_categories[]" data-placeholder="<?php esc_attr_e( 'Choose categories…', 'pick-n-mix-for-woocommerce' ); ?>">
				<?php
				$terms = get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
					)
				);
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( in_array( $term->term_id, $category_ids, true ), true, false ) . '>' . esc_html( $term->name ) . '</option>';
					}
				}
				?>
			</select>
			<?php echo wc_help_tip( __( 'Every published simple product in these categories becomes selectable.', 'pick-n-mix-for-woocommerce' ) ); ?>
		</p>
		<?php

		echo '</div>';

		wp_nonce_field( 'pnm_wc_save_product', 'pnm_wc_nonce' );

		echo '</div>';
	}

	/**
	 * Persist the box settings when the product is saved.
	 *
	 * @param int $post_id Product ID.
	 */
	public function save_product_meta( $post_id ) {
		if ( ! isset( $_POST['pnm_wc_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pnm_wc_nonce'] ) ), 'pnm_wc_save_product' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		$product = wc_get_product( $post_id );
		if ( ! $product instanceof WC_Product_Pick_N_Mix ) {
			return;
		}

		$min = isset( $_POST['_pnm_min'] ) ? absint( wp_unslash( $_POST['_pnm_min'] ) ) : 1;
		$max = isset( $_POST['_pnm_max'] ) ? absint( wp_unslash( $_POST['_pnm_max'] ) ) : $min;

		if ( $max < $min ) {
			$max = $min;
		}

		// Collect a price for every valid box size within the min/max range.
		$prices_raw = isset( $_POST['_pnm_prices'] ) ? (array) wp_unslash( $_POST['_pnm_prices'] ) : array();
		$prices     = array();
		for ( $count = $min; $count <= $max; $count++ ) {
			if ( isset( $prices_raw[ $count ] ) && '' !== $prices_raw[ $count ] ) {
				$prices[ $count ] = wc_format_decimal( $prices_raw[ $count ] );
			}
		}

		$product_ids  = isset( $_POST['_pnm_products'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['_pnm_products'] ) ) : array();
		$category_ids = isset( $_POST['_pnm_categories'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['_pnm_categories'] ) ) : array();

		$product->set_pnm_min( $min );
		$product->set_pnm_max( $max );
		$product->set_pnm_prices( $prices );
		$product->set_pnm_products( $product_ids );
		$product->set_pnm_categories( $category_ids );

		// Store the cheapest box size's price as the product's regular price so
		// WooCommerce always has a native price to sort by / mark it purchasable.
		// The actual charge is set per box size in the cart.
		$base_price = '';
		if ( ! empty( $prices ) ) {
			$base_price = isset( $prices[ $min ] ) ? $prices[ $min ] : reset( $prices );
		}
		$product->set_regular_price( $base_price );
		$product->set_sale_price( '' );
		$product->set_price( $base_price );

		$product->save();
	}

	/**
	 * Enqueue admin JS to toggle the panels for our product type.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'pnm-wc-admin',
			PNM_WC_URL . 'assets/js/pick-n-mix-admin.js',
			array( 'jquery' ),
			PNM_WC_VERSION,
			true
		);
	}
}
