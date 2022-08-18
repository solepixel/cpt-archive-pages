<?php
/**
 * Yoast SEO Support
 *
 * @package CPTArchivePages
 */

add_filter(
	'wpseo_frontend_presentation',
	function( $presentation, $context ) {
		if ( ! $context || empty( $context->page_type ) || 'Post_Type_Archive' !== $context->page_type ) {
			return $presentation;
		}

		if ( ! isset( $presentation->source ) || ! is_array( $presentation->source ) || empty( $presentation->source['post_type'] ) ) {
			return $presentation;
		}

		$page = cptap_get_archive_page( $presentation->source['post_type'] );

		if ( ! $page ) {
			return $presentation;
		}

		$seo_title        = get_post_meta( $page, '_yoast_wpseo_title', true );
		$fb_title         = get_post_meta( $page, '_yoast_wpseo_opengraph-title', true );
		$twitter_title    = get_post_meta( $page, '_yoast_wpseo_twitter-title', true );
		$fb_desc          = get_post_meta( $page, '_yoast_wpseo_opengraph-description', true );
		$twitter_desc     = get_post_meta( $page, '_yoast_wpseo_twitter-description', true );
		$fb_image         = get_post_meta( $page, '_yoast_wpseo_opengraph-image', true );
		$fb_image_id      = get_post_meta( $page, '_yoast_wpseo_opengraph-image-id', true );
		$twitter_image    = get_post_meta( $page, '_yoast_wpseo_twitter-image', true );
		$twitter_image_id = get_post_meta( $page, '_yoast_wpseo_twitter-image-id', true );

		// Adjust Titles.
		if ( $seo_title ) {
			$presentation->title = $seo_title;
		}
		if ( $fb_title ) {
			$presentation->open_graph_title = $fb_title;
		}
		if ( $twitter_title ) {
			$presentation->twitter_title = $twitter_title;
		}

		// Adjust Descriptions.
		if ( $fb_desc ) {
			$presentation->open_graph_description = $fb_desc;
		}
		if ( $twitter_desc ) {
			$presentation->twitter_description = $twitter_desc;
		}

		// Adjust Social Images.
		if ( $fb_image ) {
			$presentation->open_graph_image = $fb_image;
		}
		if ( $fb_image_id ) {
			$presentation->open_graph_image_id = $fb_image_id;
		}
		if ( $twitter_image ) {
			$presentation->twitter_image = $twitter_image;
		}
		if ( $twitter_image_id ) {
			$presentation->twitter_image_id = $twitter_image_id;
		}

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
