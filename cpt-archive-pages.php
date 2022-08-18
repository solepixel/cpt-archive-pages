<?php
/**
 * Plugin Name: CPT Archive Pages
 * Plugin URI:  https://b7s.co
 * Description: Use pages for custom post type archives.
 * Version:     0.1.1
 * Author:      Briantics, Inc.
 * Author URI:  https://b7s.co
 * Text Domain: cpt-archive-pages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 *
 * @package CPTArchivePages
 */

define( 'CPTAP_VERSION', '0.1.1' );
define( 'CPTAP_FILE', __FILE__ );
define( 'CPTAP_PATH', plugin_dir_path( CPTAP_FILE ) );
define( 'CPTAP_URL', plugin_dir_url( CPTAP_FILE ) );

require CPTAP_PATH . '/lib/plugin-update-checker/plugin-update-checker.php';

$updater = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/solepixel/cpt-archive-pages',
	__FILE__,
	'cpt-archive-pages'
);
$updater->setBranch( 'main' );

/**
 * Supported Post Archives for Post States
 *
 * @return array
 */
function cptap_get_post_archives() {
	$post_archives = apply_filters(
		'cptap_post_types',
		array_diff(
			get_post_types(),
			apply_filters(
				'cptap_excluded_post_types',
				array(
					// Core Post Types.
					'post',
					'page',
					'attachment',
					'revision',
					'nav_menu_item',
					'custom_css',
					'wp_block',
					'wp_global_styles',
					'wp_template',
					'wp_template_part',
					'wp_navigation',
					'customize_changeset',

					// 3rd Party Post Types.
					'acf-field-group',
					'acf-field',
					'oembed_cache',
					'user_request',
					'cookielawinfo',
				)
			)
		)
	);

	foreach ( $post_archives as $post_type => $label ) {
		$obj = get_post_type_object( $post_type );
		if ( ! $obj ) {
			continue;
		}

		$post_archives[ $post_type ] = $obj->labels->name . ' ' . __( 'Archive', 'cpt-archive-pages' );
	}

	return apply_filters( 'cptap_post_archives', $post_archives );
}

/**
 * Get the post type archive page ID.
 *
 * @param string $post_type Post Type.
 *
 * @return false|int
 */
function cptap_get_archive_page( $post_type = false ) {
	if ( ! $post_type ) {
		$post_type = get_post_type();
	}

	if ( ! $post_type ) {
		return false;
	}

	$page_id = get_option( 'cptap_archive_' . $post_type );

	if ( ! $page_id ) {
		return false;
	}

	return (int) $page_id;
}

/**
 * Output Archive Page content.
 */
function cptap_archive_page_content() {
	if ( get_queried_object_id() === (int) get_option( 'page_for_posts' ) ) {
		$page_id = get_option( 'page_for_posts' );
	} else {
		$page_id = cptap_get_archive_page();

		if ( ! $page_id ) {
			return;
		}
	}

	$archive_page = get_post( $page_id );

	if ( ! $archive_page ) {
		return;
	}

	echo apply_filters( 'the_content', $archive_page->post_content );
}

/**
 * If custom post type archive page should still use loop.
 *
 * @return bool
 */
function cptap_use_loop() {
	$use_loop = (array) get_option( 'cptap_use_loop' );

	if ( in_array( get_post_type(), $use_loop, true ) ) {
		return true;
	}

	return false;
}

/**
 * Add custom post States.
 *
 * @param array $states Post States array.
 *
 * @return array
 */
add_filter(
	'display_post_states',
	function ( $states ) {
		global $post;

		if ( ! $post || 'page' !== get_post_type( $post->ID ) ) {
			return $states;
		}

		$archives = cptap_get_post_archives();

		foreach ( $archives as $archive_post_type => $label ) {
			if ( cptap_get_archive_page( $archive_post_type ) === $post->ID ) {
				$states[] = $label;
			}
		}

		return $states;
	}
);

add_filter( 'allowed_options', 'cptap_reading_options_support' );

/**
 * Add our settings to the reading allowed options array.
 *
 * @param array $allowed_options The allowed options list.
 *
 * @return array
 */
function cptap_reading_options_support( $allowed_options ) {
	if ( ! isset( $allowed_options['reading'] ) ) {
		$allowed_options['reading'] = array();
	}

	$allowed_options['reading'][] = 'cptap_singular';
	$allowed_options['reading'][] = 'cptap_use_loop';

	foreach ( cptap_get_post_archives() as $archive_post_type => $label ) {
		$allowed_options['reading'][] = 'cptap_archive_' . $archive_post_type;
	}

	return $allowed_options;
}

add_action( 'admin_init', 'cptap_admin_settings', 11 );

/**
 * Create some Admin settings under Settings > Reading.
 */
function cptap_admin_settings() {
	$archives = cptap_get_post_archives();
	$section  = 'cptap_archive_pages';
	$page     = 'reading';
	$singular = 'cptap_singular';
	$use_loop = 'cptap_use_loop';

	if ( ! count( $archives ) ) {
		return;
	}

	add_settings_section(
		$section,
		__( 'Post Archive Pages', 'cpt-archive-pages' ),
		'__return_false',
		$page
	);

	register_setting( $section, $singular, array( 'type' => 'array' ) );
	register_setting( $section, $use_loop, array( 'type' => 'array' ) );

	$selected_singular = (array) get_option( $singular );
	$selected_loop     = (array) get_option( $use_loop );

	foreach ( $archives as $archive_post_type => $label ) {
		$setting = 'cptap_archive_' . $archive_post_type;
		$args    = array(
			'type'         => 'string',
			'description'  => __( 'Page associated with the post type archive.', 'cpt-archive-pages' ),
			'show_in_rest' => true,
			'default'      => '',
		);

		register_setting( $section, $setting, $args );

		add_settings_field(
			$setting,
			$label,
			'cptap_archive_settings_fields',
			$page,
			$section,
			array(
				'label_for'         => $setting,
				'post_type'         => $archive_post_type,
				'selected_singular' => $selected_singular,
				'selected_loop'     => $selected_loop,
				'singular'          => $singular,
				'use_loop'          => $use_loop,
			)
		);
	}
}

/**
 * Post Archive States setting field.
 *
 * @param array $args Field setting args.
 */
function cptap_archive_settings_fields( $args ) {
	require CPTAP_PATH . '/views/archive-page-settings.php';
}

/**
 * Handle Custom Archive Permalinks.
 *
 * @param array $args Post Type arguments array.
 * @param string $post_type The post type.
 *
 * @return array
 */
add_filter(
	'register_post_type_args',
	function ( $args, $post_type ) {
		$page_id = cptap_get_archive_page( $post_type );

		if ( ! $page_id ) {
			return $args;
		}

		$archive = get_post( $page_id );

		if ( ! $archive ) {
			return $args;
		}

		if ( empty( $args['has_archive'] ) || $args['has_archive'] !== $archive->post_name ) {
			if ( $args['has_archive'] ) {
				$args['original_archive'] = $args['has_archive'];
			}
			$args['has_archive'] = $archive->post_name;
		}

		$singular = (array) get_option( 'cptap_singular' );

		if ( in_array( $post_type, $singular, true ) ) {
			if ( empty( $args['rewrite']['slug'] ) || $args['rewrite']['slug'] !== $archive->post_name ) {
				if ( ! empty( $args['rewrite']['slug'] ) ) {
					$args['rewrite']['original'] = $args['rewrite']['slug'];
				}

				$args['rewrite']['slug'] = $archive->post_name;
			}
		}

		return $args;
	},
	10,
	2
);

add_filter(
	'post_type_archive_title',
	function( $title, $post_type ) {
		$page = cptap_get_archive_page( $post_type );

		if ( ! $page ) {
			return $title;
		}

		return get_the_title( $page );
	},
	10,
	2
);

add_filter(
	'wpseo_frontend_presentation',
	function( $presentation, $context ) {
		if ( 'Post_Type_Archive' !== $context->page_type ) {
			return $presentation;
		}

		if ( ! is_array( $presentation->source ) || empty( $presentation->source['post_type'] ) ) {
			return $presentation;
		}

		$page = cptap_get_archive_page( $presentation->source['post_type'] );

		if ( ! $page ) {
			return $presentation;
		}

		$seo_title = get_post_meta( $page, '_yoast_wpseo_title', true );

		if ( ! $seo_title ) {
			return $presentation;
		}

		$presentation->title = $seo_title;

		return $presentation;
	},
	10,
	2
);

add_filter(
	'Yoast\WP\SEO\open_graph_description_post-type-archive',
	function( $description, $post_type ) {
		$page = cptap_get_archive_page( $post_type );

		if ( ! $page ) {
			return $description;
		}

		$seo_description = get_post_meta( $page, '_yoast_wpseo_metadesc', true );

		if ( ! $seo_description ) {
			return $description;
		}

		return $seo_description;
	},
	10,
	2
);

add_filter(
	'Yoast\WP\SEO\open_graph_image_post-type-archive',
	function( $image, $post_type ) {
		$page = cptap_get_archive_page( $post_type );

		if ( ! $page ) {
			return $image;
		}

		$seo_image = get_the_post_thumbnail_url( $page );

		if ( ! $seo_image ) {
			return $image;
		}

		return $seo_image;
	},
	10,
	2
);

add_filter(
	'Yoast\WP\SEO\open_graph_image_id_post-type-archive',
	function( $image_id, $post_type ) {
		$page = cptap_get_archive_page( $post_type );

		if ( ! $page ) {
			return $image_id;
		}

		$seo_image_id = get_post_thumbnail_id( $page );

		if ( ! $seo_image_id ) {
			return $image_id;
		}

		return $seo_image_id;
	},
	10,
	2
);
