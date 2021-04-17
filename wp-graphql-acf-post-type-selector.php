<?php

/**
 * Plugin Name: ACF Post Type Selector WPGraphQL Extension
 * Plugin URI: https://mddd.nl
 * Description: Add ACF post type selector field to WPGraphQL.
 * Author: M.D. Leguijt
 * Author URI: https://mddd.nl
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
  exit;
}

add_filter('wpgraphql_acf_supported_fields', function($supported_fields) {
	$supported_fields[] = 'post_type_selector';

	return $supported_fields;
});

add_filter( 'wpgraphql_acf_register_graphql_field', function($field_config, $type_name, $field_name, $config) {
	$acf_field = isset( $config['acf_field'] ) ? $config['acf_field'] : null;
	$acf_type  = isset( $acf_field['type'] ) ? $acf_field['type'] : null;

	if( !$acf_field ) {
		return $field_config;
	} 

	// ignore all other field types
	if( $acf_type !== 'post_type_selector' ) {
			return $field_config;
	}

	// define data type
	$field_config['type'] = 'String';

	// add resolver
	$field_config['resolve'] = function( $root ) use ( $acf_field ) {

		if( array_key_exists( $acf_field['key'], $root ) ) {
			$value = $root[$acf_field['key']];
		} 

		return !empty( $value ) ? $value : null;
	};

	return $field_config;
}, 10, 4 );