<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/*
 *
 *  @version       1.0.0
 *  @author        impleCode
 *
 */

if ( ! function_exists( 'register_block_type' ) ) {
	// Gutenberg is not active.
	return;
}

function ic_block_render_price_filter( $atts = array() ) {
	if ( ! class_exists( 'product_price_filter' ) ) {
		return '';
	}
	ob_start();
	$atts['title']             = isset( $atts['title'] ) ? $atts['title'] : '';
	$atts['shortcode_support'] = isset( $atts['shortcode_support'] ) ? $atts['shortcode_support'] : '';
	the_widget( 'product_price_filter', $atts );

	return ob_get_clean();
}

add_filter( 'ic_epc_blocks_localize', 'ic_block_price_filter_localize' );

function ic_block_price_filter_localize( $localize ) {
	$localize['strings']['price_filter_widget'] = __( 'Catalog Price Filter', 'ecommerce-product-catalog' );

	return $localize;
}


register_block_type( __DIR__,
	array(
		'render_callback' => 'ic_block_render_price_filter',
	)
);