<?php

/**
 * Plugin Name: ACF Post Type Selector WPGraphQL Extension
 * Plugin URI: https://mddd.nl
 * Description: Add ACF post type selector field to WPGraphQL.
 * Author: M.D. Leguijt
 * Author URI: https://mddd.nl
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) {
  exit;
}

add_filter('post_type_selector_post_types', function($post_types) {
	if(isset($post_types["attachment"])) {
		unset($post_types["attachment"]);
	}

	return $post_types;
}, 10);

add_action( 'graphql_register_types', function() {
	register_graphql_object_type('ACFSelectedConnected', [
		'description' => __( 'Connection to selected post types.'),
		'fields' => [
			'postTypes' => ['type' => ['list_of' => 'String']],
		],
		'connections' => [
			'nodesOfPostTypes' => [
				'toType' => 'ContentNode',
				'resolve' => function ($postType, $args, $context, $info) {
					$post_type = isset($postType['postTypes']) ? $postType['postTypes'] : 'all';
					$resolver = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver( $postType, $args, $context, $info, $post_type );

					$connection = $resolver->get_connection();
					return $connection;
				},
			],
		]
	]);
}, 9);

add_filter('wpgraphql_acf_supported_fields', function($supported_fields) {
	$supported_fields[] = 'post_type_selector';

	return $supported_fields;
});

add_filter( 'wpgraphql_acf_register_graphql_field', function($field_config, $type_name, $field_name, $config) {
	$acf_field = isset($config['acf_field']) ? $config['acf_field'] : null;
	$acf_type  = isset($acf_field['type']) ? $acf_field['type'] : null;

	if( !$acf_field ) {
		return $field_config;
	} 

	// ignore all other field types
	if($acf_type !== 'post_type_selector') {
			return $field_config;
	}

	// define data type
	$field_config['type'] = 'ACFNodesOfPostTypes';

	// add resolver
	$field_config['resolve'] = function( $root ) use ( $acf_field ) {
		$value = \WPGraphQL\ACF\Config::get_acf_field_value($root, $acf_field);

		if (is_array($value)) {
			$data['postTypes'] = $value;
		} else {
			$data['postTypes'] = Array($value);
		}
 
		return !empty( $value ) ? $data : null;
	};

	return $field_config;
}, 10, 4 );