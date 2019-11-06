<?php
/**
 * Handles loading of JS and CSS.
 *
 * @since 1.0.0
 * @package TenUp\AutoshareForTwitter
 */

namespace TenUp\AutoshareForTwitter\Admin\Assets;

use function TenUp\AutoshareForTwitter\Utils\get_autoshare_for_twitter_meta;
use function TenUp\AutoshareForTwitter\Utils\opted_into_autoshare_for_twitter;
use function TenUp\AutoshareForTwitter\REST\post_autoshare_for_twitter_meta_rest_route;
use const TenUp\AutoshareForTwitter\Core\Post_Meta\ENABLE_AUTOSHARE_FOR_TWITTER_KEY;
use const TenUp\AutoshareForTwitter\Core\Post_Meta\TWEET_BODY_KEY;
use const TenUp\AutoshareForTwitter\Core\Post_Meta\TWITTER_STATUS_KEY;

/**
 * The handle used in registering plugin assets.
 */
const SCRIPT_HANDLE = 'autoshare_for_twitter';

/**
 * Adds WP hook callbacks.
 *
 * @since 1.0.0
 */
function add_hook_callbacks() {
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_shared_assets' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_classic_editor_assets' );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor_assets' );
}

/**
 * Enqueues assets shared by WP5.0 and classic editors.
 *
 * @since 1.0.0
 */
function enqueue_shared_assets() {
	wp_enqueue_style(
		'admin_autoshare_for_twitter',
		trailingslashit( AUTOSHARE_FOR_TWITTER_URL ) . 'assets/css/admin-autoshare-for-twitter.css',
		[],
		AUTOSHARE_FOR_TWITTER_VERSION
	);
}

/**
 * Enqueues assets for supported post type editors where the block editor is not active.
 *
 * @since 1.0.0
 * @param string $hook The current admin page.
 */
function maybe_enqueue_classic_editor_assets( $hook ) {
	if ( ! in_array( $hook, [ 'post-new.php', 'post.php' ], true ) ) {
		return;
	}

	if ( ! opted_into_autoshare_for_twitter( get_the_ID() ) ) {
		return;
	}

	$current_screen = get_current_screen();
	if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
		return;
	}

	$api_fetch_handle = 'wp-api-fetch';
	if ( ! wp_script_is( $api_fetch_handle, 'registered' ) ) {
		wp_register_script(
			$api_fetch_handle,
			trailingslashit( AUTOSHARE_FOR_TWITTER_URL ) . 'dist/api-fetch.js',
			[],
			'3.4.0',
			true
		);

		wp_add_inline_script(
			$api_fetch_handle,
			sprintf(
				'wp.apiFetch.use( wp.apiFetch.createNonceMiddleware( "%s" ) );',
				( wp_installing() && ! is_multisite() ) ? '' : wp_create_nonce( 'wp_rest' )
			),
			'after'
		);

		wp_add_inline_script(
			$api_fetch_handle,
			sprintf(
				'wp.apiFetch.use( wp.apiFetch.createRootURLMiddleware( "%s" ) );',
				esc_url_raw( get_rest_url() )
			),
			'after'
		);
	}

	$handle = 'admin_autoshare_for_twitter';
	wp_enqueue_script(
		$handle,
		trailingslashit( AUTOSHARE_FOR_TWITTER_URL ) . 'assets/js/admin-autoshare-for-twitter.js',
		[ 'jquery', 'wp-api-fetch' ],
		AUTOSHARE_FOR_TWITTER_VERSION,
		true
	);

	wp_enqueue_style(
		$handle,
		trailingslashit( AUTOSHARE_FOR_TWITTER_URL ) . 'assets/css/admin-autoshare-for-twitter.css',
		[],
		AUTOSHARE_FOR_TWITTER_VERSION
	);

	localize_data( $handle );
}

/**
 * Enqueues block editor assets.
 *
 * @since 1.0.0
 */
function enqueue_editor_assets() {
	if ( ! opted_into_autoshare_for_twitter( get_the_ID() ) ) {
		return;
	}

	wp_enqueue_script(
		SCRIPT_HANDLE,
		trailingslashit( AUTOSHARE_FOR_TWITTER_URL ) . 'dist/autoshare-for-twitter.js',
		[
			'lodash',
			'wp-components',
			'wp-compose',
			'wp-data',
			'wp-edit-post',
			'wp-element',
			'wp-i18n',
			'wp-plugins',
		],
		AUTOSHARE_FOR_TWITTER_VERSION,
		true
	);

	localize_data();
}

/**
 * Passes data to Javascript.
 *
 * @since 1.0.0
 * @param string $handle Handle of the JS script intended to consume the data.
 */
function localize_data( $handle = SCRIPT_HANDLE ) {
	$post_id = intval( get_the_ID() );

	if ( empty( $post_id ) ) {
		$post_id = intval(
			filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT )  // Filter removes all characters except digits.
		);
	}

	$status_meta = get_autoshare_for_twitter_meta( $post_id, TWITTER_STATUS_KEY );

	$localization = [
		'enabled'            => get_autoshare_for_twitter_meta( $post_id, ENABLE_AUTOSHARE_FOR_TWITTER_KEY ),
		'enableAutoshareKey' => ENABLE_AUTOSHARE_FOR_TWITTER_KEY,
		'errorText'          => __( 'Error', 'auto-share-for-twitter' ),
		'nonce'              => wp_create_nonce( 'wp_rest' ),
		'restUrl'            => rest_url( post_autoshare_for_twitter_meta_rest_route( $post_id ) ),
		'tweetBodyKey'       => TWEET_BODY_KEY,
		'status'             => $status_meta && is_array( $status_meta ) ? $status_meta : null,
		'unknownErrorText'   => __( 'An unknown error occurred', 'auto-share-for-twitter' ),
		'siteUrl'            => home_url(),
	];

	wp_localize_script( $handle, 'adminAutoshareForTwitter', $localization );
}
