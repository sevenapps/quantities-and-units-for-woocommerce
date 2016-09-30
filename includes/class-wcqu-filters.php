<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Quantities_and_Units_Filters' ) ) :

class WC_Quantities_and_Units_Filters {

	public function __construct() {

		// Cart input box variable filters
		add_filter( 'woocommerce_quantity_input_min', array( $this, 'input_min_value' ), 10, 2);
		add_filter( 'woocommerce_quantity_input_max', array( $this, 'input_max_value' ), 10, 2);
		add_filter( 'woocommerce_quantity_input_step', array( $this, 'input_step_value' ), 10, 2);

		// Product input box argument filter
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'input_set_all_values' ), 10, 2 );

		add_filter( 'woocommerce_loop_add_to_cart_args', array( $this, 'woocommerce_loop_add_to_cart_args' ), 10, 2 );

		// TODO: Use filter below to output the min/max values onto the page
		// add_filter( 'woocommerce_get_price_html' , array( $this , 'price_html_filter' ) , 10 , 2 );
		// price_html_filter() should add something like: "<span class="wholesale_price_minimum_order_quantity">Min: 4</span>"
		// NOTE: Need to check and see what the WC filter does and if we need to copy and modify that.
		// could look at what 'woocommerce-wholesales-prices( and/or -premium)' is doing with this filter

		add_filter( 'woocommerce_check_cart_items', array( $this, 'check_cart_items' ), 10 );
	}

	/**
	 * This method ensures that if a minimum has been set, that we
	 * specify that when the ajax add to cart function is enabled.
	 * Also if a step is set, but no minimum, we'll use that.
	 * Only works with WooCommerce 2.5+
	 *
	 * @param array $args
	 * @param WC_Product $product
	 *
	 * @return mixed
	 */
	public function woocommerce_loop_add_to_cart_args( $args, $product ) {
		// Return Defaults if it isn't a simple product
		if( $product->product_type != 'simple' ) {
			return $args;
		}

		// Get Rule
		$rule = wcqu_get_applied_rule( $product );

		// Get Value from Rule
		$min = wcqu_get_value_from_rule( 'min', $product, $rule );
		$min = isset($min['min']) ? $min['min'] : 1;

		$step = wcqu_get_value_from_rule( 'step', $product, $rule );
		$step = isset($step['step']) ? $step['step'] : 1;

		if(!$min && $step > 0){
			$args['quantity'] = $step;
		}
		else if($min > 0){
			$args['quantity'] = $min;
		}


		return $args;
	}

	/*
	*	Filter Minimum Quantity Value for Input Boxes for Cart
	*
	*	@access public
	*	@param  int 	default
	*	@param  obj		product
	*	@return int		step
	*
	*/
	public function input_min_value( $default, $product ) {

		// Return Defaults if it isn't a simple product
		if ( $product->product_type != 'simple' ) {
			return $default;
		}

		// Get Rule
		$rule = wcqu_get_applied_rule( $product );

		// Get Value from Rule
		$min = wcqu_get_value_from_rule( 'min', $product, $rule );

		// Return Value
		if ( $min == '' or $min == null or (isset($min['min']) and $min['min'] == "")) {
			return $default;
		} else {
			return $min;
		}
	}

	/*
	*	Filter Maximum Quantity Value for Input Boxes for Cart
	*
	*	@access public
	*	@param  int 	default
	*	@param  obj		product
	*	@return int		step
	*
	*/
	public function input_max_value( $default, $product ) {

		// Return Defaults if it isn't a simple product
		if( $product->product_type != 'simple' ) {
			return $default;
		}

		// Get Rule
		$rule = wcqu_get_applied_rule( $product );

		// Get Value from Rule
		$max = wcqu_get_value_from_rule( 'max', $product, $rule );

		// Return Value
		if ( $max == '' or $max == null or (isset($max['max']) and $max['max'] == "")) {
			return $default;
		} else {
			return $max;
		}
	}

	/*
	*	Filter Step Quantity Value for Input Boxes woocommerce_quantity_input_step for Cart
	*
	*	@access public
	*	@param  int 	default
	*	@param  obj		product
	*	@return int		step
	*
	*/
	public function input_step_value( $default, $product ) {

		// Return Defaults if it isn't a simple product
		if ( $product->product_type != 'simple' ) {
			return $default;
		}

		// Get Rule
		$rule = wcqu_get_applied_rule( $product );

		// Get Value from Rule
		$step = wcqu_get_value_from_rule( 'step', $product, $rule );
		$allow_multi = wcqu_get_value_from_rule( 'allow_multi', $product, $rule );

		// Return Value
		if ( $allow_multi === 'yes' or $step == '' or $step == null or (isset($step['step']) and $step['step'] == "") ) {
			return $default;
		} else {
			return isset($step['step']) ? $step['step'] : $step;
		}
	}

	/*
	*	Filter Step, Min and Max for Quantity Input Boxes on product pages
	*
	*	@access public
	*	@param  array 	args
	*	@param  obj		product
	*	@return array	vals
	*
	*/
	public function input_set_all_values( $args, $product ) {

		// Return Defaults if it isn't a simple product
		/* Commented out to allow for grouped and variable products
		*  on their product pages
		if( $product->product_type != 'simple' ) {
			return $args;
		}
		*/

		// Get Rule
		$rule = wcqu_get_applied_rule( $product );

		// Get Value from Rule
		$values = wcqu_get_value_from_rule( 'all', $product, $rule );

		if ( $values == null ) {
			return $args;
		}

		$vals = array();
		$vals['input_name'] = 'quantity';

		// Check if the product is out of stock
		$stock = $product->get_stock_quantity();

		// Check stock status and if Out try Out of Stock value
		if ( strlen( $stock ) != 0 and $stock <= 0 and isset( $values['min_oos'] ) and $values['min_oos'] != '' ) {
			$args['min_value'] = $values['min_oos'];

		// Otherwise just check normal min
		} elseif ( $values['allow_multi'] === 'no' and $values['min_value'] != '' ) {
			$args['min_value'] = $values['min_value'];

		// If no min, try step
		} elseif ( $values['allow_multi'] === 'no' and $values['min_value'] == '' and $values['step'] != '' ) {
			$args['min_value'] = $values['step'];
		}

		// Check stock status and if Out try Out of Stock value
		if ( $stock <= 0 and isset( $values['min_oos'] ) and $values['max_oos'] != '' ) {
			$args['max_value'] = $values['max_oos'];

		// Otherwise just check normal max
		} elseif ( $values['allow_multi'] === 'no' and $values['max_value'] != ''  ) {
			$args['max_value'] = $values['max_value'];
		}

		// Set step value
		if ( $values['allow_multi'] === 'no' and $values['step'] != '' ) {
			$args['step'] = $values['step'];
		}

		return $args;
	}

	public function check_cart_items() {
		$rules = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = get_product( $cart_item['product_id'] );
			$rule = wcqu_get_applied_rule( $product );
			$values = wcqu_get_value_from_rule( 'all', $product, $rule );

			// No Rules? Skip to next
			if ( $values == null ) {
				continue;
			}

			if ( $values['allow_multi'] != 'yes' ) {
				$QuantityValidations = new WC_Quantities_and_Units_Quantity_Validations();
				$QuantityValidations->validate_single_product( true, $product->post->ID, $cart_item['quantity'], true, $cart_item['variation_id'], $cart_item['variation'] );
				continue;
			}

			// Do initial setup of rule tracking
			if ( !isset( $rules[ $rule->ID ] ) ) {
				$cat_id = get_post_meta( $rule->ID, '_cats' )[0][0];
				$rules[ $rule->ID ] = array(
					'rule'     => $rule,
					'values'   => $values,
					'cart_qty' => 0,
					'cat_name' => get_cat_name( $cat_id ),
					'cat_link' => get_category_link( $cat_id ),
				);
			}

			$rules[ $rule->ID ]['cart_qty'] += $cart_item['quantity'];
		}

		foreach ( $rules as $rule_id => $rule ) {
			if ( $rule['values']['allow_multi'] !== 'yes' ) {
				continue;
			}

			$qty = (float) $rule['cart_qty'];

			$min_value = (float) $rule['values']['min_value'];
			if ( $min_value > $qty ) {
				wc_add_notice( sprintf( __( "Your cart must have a minimum of %s <a href=\"%s\" style=\"text-decoration: underline\">%s</a> products to proceed.", 'woocommerce' ), $min_value, $rule['cat_link'], $rule['cat_name'] ), 'error' );
				return false;
			}

			$max_value = (float) $rule['values']['max_value'];
			if ( $max_value > 0 && $max_value < $qty ) {
				wc_add_notice( sprintf( __( "You may only add a maximum of %s <a href=\"%s\" style=\"text-decoration: underline\">%s</a> products to your cart.", 'woocommerce' ), $max_value, $rule['cat_link'], $rule['cat_name'] ), 'error' );
				return false;
			}

			$step = (float) $rule['values']['step'];
			if ( $step != 0 and $qty % $step != 0 ) {
				wc_add_notice( sprintf( __( "You may only add <a href=\"%s\" style=\"text-decoration: underline\">%s</a> products in multiples of %s to your cart.", 'woocommerce' ), $rule['cat_link'], $rule['cat_name'], $step ), 'error' );
				return false;
			}
		}

		return true;
	}

}

endif;

return new WC_Quantities_and_Units_Filters();
