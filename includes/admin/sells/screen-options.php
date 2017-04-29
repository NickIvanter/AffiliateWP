<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/sells/class-list-table.php';

/**
 * Add per page screen option to the Referrals list table
 *
 * @since 1.7
 */
function affwp_sells_screen_options() {

	$screen = affwp_get_current_screen();
    file_put_contents('/tmp/s.log', var_export($screen, true), FILE_APPEND);

	if ( $screen !== 'affiliate-wp-sells' ) {
		return;
	}

	add_screen_option(
		'per_page',
		array(
			'label'   => __( 'Number of referrals per page:', 'affiliate-wp' ),
			'option'  => 'affwp_edit_sells_per_page',
			'default' => 30,
		)
	);

	// Instantiate the list table to make the columns array available to screen options.
	new AffWP_Sells_Table;

	/**
	 * Fires in the screen-options area of the referrals screen.
	 *
	 * @param string $screen The current screen.
	 */
	do_action( 'affwp_sells_screen_options', $screen );

}

/**
 * Per page screen option value for the Referrals list table
 *
 * @since  1.7
 * @param  bool|int $status
 * @param  string   $option
 * @param  mixed    $value
 * @return mixed
 */
function affwp_sells_set_screen_option( $status, $option, $value ) {

	if ( 'affwp_edit_sells_per_page' === $option ) {
		return $value;
	}

	return $status;

}
add_filter( 'set-screen-option', 'affwp_sells_set_screen_option', 10, 3 );
