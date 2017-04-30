<?php

/**
 * Process the add referral request
 *
 * @since 1.2
 * @return void|false
 */
function affwp_process_add_sell( $data ) {

	if ( ! is_admin() ) {
		return false;
	}

	if ( ! current_user_can( 'manage_referrals' ) ) {
		wp_die( __( 'You do not have permission to manage referrals', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $data['affwp_add_referral_nonce'], 'affwp_add_referral_nonce' ) ) {
		wp_die( __( 'Security check failed', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

    $data['sell'] = true;
	if ( affwp_add_referral( $data ) ) {
		wp_safe_redirect( affwp_admin_url( 'sells', array( 'affwp_notice' => 'referral_added' ) ) );
		exit;
	} else {
		wp_safe_redirect( affwp_admin_url( 'sells', array( 'affwp_notice' => 'referral_add_failed' ) ) );
		exit;
	}

}
add_action( 'affwp_add_sell', 'affwp_process_add_sell' );

/**
 * Process the update referral request
 *
 * @since 1.2
 * @return void
 */
function affwp_process_update_sell( $data ) {

	if ( ! is_admin() ) {
		return false;
	}

	if ( ! current_user_can( 'manage_referrals' ) ) {
		wp_die( __( 'You do not have permission to manage referrals', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $data['affwp_edit_referral_nonce'], 'affwp_edit_referral_nonce' ) ) {
		wp_die( __( 'Security check failed', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

	if ( affiliate_wp()->referrals->update_referral( $data['referral_id'], $data ) ) {
		wp_safe_redirect( affwp_admin_url( 'sells', array( 'affwp_notice' => 'referral_updated' ) ) );
		exit;
	} else {
		wp_safe_redirect( affwp_admin_url( 'sells', array( 'affwp_notice' => 'referral_update_failed' ) ) );
		exit;
	}

}
add_action( 'affwp_process_update_sell', 'affwp_process_update_sell' );

/**
 * Process the delete referral request
 *
 * @since 1.7
 * @return void
 */
function affwp_process_delete_sell( $data ) {

	if ( ! is_admin() ) {
		return false;
	}

	if ( ! current_user_can( 'manage_referrals' ) ) {
		wp_die( __( 'You do not have permission to manage referrals', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( $data['_wpnonce'], 'affwp_delete_referral_nonce' ) ) {
		wp_die( __( 'Security check failed', 'affiliate-wp' ), array( 'response' => 403 ) );
	}

	if ( affwp_delete_referral( $data['referral_id'] ) ) {
		wp_safe_redirect( affwp_admin_url( 'sells', array( 'affwp_notice' => 'referral_deleted' ) ) );
		exit;
	} else {
		wp_safe_redirect( affwp_admin_url( 'sells', array( 'affwp_notice' => 'referral_delete_failed' ) ) );
		exit;
	}

}
add_action( 'affwp_process_delete_sell', 'affwp_process_delete_sell' );

/**
 * Process the referral payout file generation
 *
 * @since 1.0
 * @return void
 */
function affwp_generate_sell_payout_file( $data ) {

	$export = new Affiliate_WP_Referral_Payout_Export;

	if ( ! empty( $data['user_name'] ) && $affiliate = affwp_get_affiliate( $data['user_name'] ) ) {
		$export->affiliate_id = $affiliate->ID;
	}

	$export->date = array(
		'start' => $data['from'],
		'end'   => $data['to'] . ' 23:59:59'
	);
	$export->export();

}
add_action( 'affwp_generate_sell_payout', 'affwp_generate_sell_payout_file' );
