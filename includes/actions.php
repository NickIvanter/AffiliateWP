<?php

/**
 * Hooks AffiliateWP actions, when present in the $_REQUEST superglobal. Every affwp_action
 * present in $_REQUEST is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since 1.0
 * @return void
*/
function affwp_do_actions() {
	if ( isset( $_REQUEST['affwp_action'] ) ) {
		$action = $_REQUEST['affwp_action'];

		/**
		 * Fires for every AffiliateWP action passed via `affwp_action`.
		 *
		 * The dynamic portion of the hook name, `$action`, refers to the action passed via
		 * the `affwp_action` parameter.
		 *
		 * @param array $_REQUEST Request data.
		 */
		do_action( "affwp_{$action}", $_REQUEST );
	}
}
add_action( 'init', 'affwp_do_actions', 9 );

// Process affiliate notification settings
add_action( 'affwp_update_profile_settings', 'affwp_update_profile_settings' );

/**
 * Removes single-use query args derived from executed actions in the admin.
 *
 * @since 1.8.6
 *
 * @param array $query_args Removable query arguments.
 * @return array Filtered list of removable query arguments.
 */
function affwp_remove_query_args( $query_args ) {
	// Prevent certain repeated AffWP actions on refresh.
	if ( isset( $_GET['_wpnonce'] )
		&& (
			isset( $_GET['affiliate_id'] )
			|| isset( $_GET['creative_id'] )
			|| isset( $_GET['referral_id'] )
			|| isset( $_GET['visit_id'] )
			|| isset( $_GET['payout_id'] )
	     )
	) {
		$query_args[] = '_wpnonce';
	}

	if ( ( isset( $_GET['filter_from'] ) || isset( $_GET['filter_to'] ) )
		&& ( isset( $_GET['range'] ) && 'other' !== $_GET['range'] )
	) {
		$query_args[] = 'filter_from';
		$query_args[] = 'filter_to';
	}

	$query_args[] = 'affwp_notice';

	return $query_args;
}
add_filter( 'removable_query_args', 'affwp_remove_query_args' );


/**
 * Updates the website URL associated with a given affiliate's user account.
 *
 * @since 2.1
 *
 * @param int   $affiliate_id Affiliate ID.
 * @param array $args         Arguments passed to {@see Affiliate_WP_DB_Affiliates::add()}.
 * @return int|\WP_Error|false The updated user's ID if successful, WP_Error object on error, otherwise false.
 */
function affwp_process_add_affiliate_website( $affiliate_id, $args ) {
	$updated = false;

	if ( ! empty( $args['website_url'] ) ) {

		$website_url = esc_url( $args['website_url'] );

		$user_id = affwp_get_affiliate_user_id( $affiliate_id );

		$updated = wp_update_user( array(
			'ID'       => $user_id,
			'user_url' => $website_url
		) );

	}

	return $updated;

}
add_action( 'affwp_insert_affiliate', 'affwp_process_add_affiliate_website', 11, 2 );
