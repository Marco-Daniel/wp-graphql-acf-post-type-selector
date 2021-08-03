<?php

/**
 * Plugin Name: ACF Post Type Selector WPGraphQL Extension
 * Plugin URI: https://mddd.nl
 * Description: Add ACF post type selector field to WPGraphQL.
 * Author: M.D. Leguijt
 * Author URI: https://mddd.nl
 * Version: 1.2.0
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!function_exists('wpgql_get_acf_field_type')) {
	function wpgql_get_acf_field_type($acf_field) {
		return isset($acf_field['type']) ? $acf_field['type'] : null;
	}
}

if (!function_exists('is_post_type_selector')) {
	function is_post_type_selector($acf_type) {
		return $acf_type === 'post_type_selector' ? true : false;
	}
}

if (!function_exists('pts_graphql_type_name')) {
	function pts_graphql_type_name() {
		return 'ACFNodesOfSelectedPostTypes';
	}
}

add_filter('post_type_selector_post_types', function($post_types) {
	if(isset($post_types["attachment"])) {
		unset($post_types["attachment"]);
	}

	return $post_types;
}, 10);

add_action( 'graphql_register_types', function() {
	register_graphql_object_type(pts_graphql_type_name(), [
		'description' => __( 'Connection to selected post types.'),
		'fields' => [
			'postTypes' => ['type' => ['list_of' => 'String']],
		],
		'connections' => [
			'nodesOfPostTypes' => [
				'toType' => 'ContentNode',
				'resolve' => function ($postTypes, $args, $context, $info) {
					$post_types = isset($postTypes['postTypes']) ? $postTypes['postTypes'] : 'all';
					$resolver = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver( $postType, $args, $context, $info, $post_types );

					$connection = $resolver->get_connection();
					return $connection;
				},
				'connectionArgs' => [
					'orderBy' => [
						'type' => 'PostObjectsConnectionOrderbyInput',
						'description' => __('Order by input.'),
					],
					'dateQuery' => [
						'type' => 'DateQueryInput',
						'description' => __('Filter the connection based on dates'),
					],
					'id' => [
						'type' => 'Int',
						'description' => __('Specific ID of the object'),
					],
					'name' => [
						'type' => 'String',
						'description' => __('Slug / post_name of the object'),
					],
					'hasPassword' => [
						'type' => 'Boolean',
						'description' => __('True for objects with passwords; False for objects without passwords; null for all objects with or without passwords'),
					],
					'in' => [
						'type' => ['list_of' => 'ID'],
						'description' => __('Array of IDs for the objects to retrieve'),
					],
					'notIn' => [
						'type' => ['list_of' => 'ID'],
						'description' => __('Specify IDs NOT to retrieve. If this is used in the same query as "in", it will be ignored'),
					],
					'stati' => [
						'type' => ['list_of' => 'PostStatusEnum'],
						'description' => __('Retrieve posts where post status is in an array.'),
					],
					'status' => [
						'type' => 'PostStatusEnum',
						'description' => __('Show posts with a specific status.'),
					],
					'title' => [
						'type' => 'String',
						'description' => __('Title of the object'),
					],
				],
			],
		]
	]);
}, 9);

add_filter('wpgraphql_acf_supported_fields', function($supported_fields) {
	$supported_fields[] = 'post_type_selector';

	return $supported_fields;
});

add_filter('graphql_acf_field_value', function($value, $acf_field, $root, $id) {
	$acf_type = wpgql_get_acf_field_type($acf_field);

	if(!is_post_type_selector($acf_type)) {
		return $value;
	}

	if (is_array($value)) {
		$data['postTypes'] = $value;
	} else {
		$data['postTypes'] = Array($value);
	}

	return !empty( $value ) ? $data : null;
}, 10, 4);

add_filter( 'wpgraphql_acf_register_graphql_field', function($field_config, $type_name, $field_name, $config) {
	$acf_field = isset($config['acf_field']) ? $config['acf_field'] : null;
	$acf_type  = wpgql_get_acf_field_type($acf_field);

	if( !$acf_field ) {
		return $field_config;
	} 

	// ignore all other field types
	if(!is_post_type_selector($acf_type)) {
			return $field_config;
	}

	// define data type
	$field_config['type'] = pts_graphql_type_name();

	return $field_config;
}, 10, 4 );