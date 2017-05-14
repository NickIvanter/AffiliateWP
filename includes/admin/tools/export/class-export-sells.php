<?php
/**
 * Export Class
 *
 * This is the base class for all export methods. Each data export type (referrals, affiliates, visits) extends this class.
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

use AffWP\Utils\Exporter;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once('class-export-referrals.php');

/**
 * Affiliate_WP_Export Class
 *
 * @since 1.0
 */
class Affiliate_WP_Sells_Export extends Affiliate_WP_Referral_Export implements Exporter\CSV {

	/**
	 * Retrieves the data being exported.
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @return array $data Data for Export
	 */
	public function get_data() {

		$args = array(
			'status'       => $this->status,
			'date'         => ! empty( $this->date ) ? $this->date : '',
			'affiliate_id' => $this->affiliate,
			'number'       => -1,
			'sell'		   => true,
		);

		$data         = array();
		$affiliates   = array();
		$referral_ids = array();
		$referrals    = affiliate_wp()->referrals->get_referrals( $args );

		if( $referrals ) {

			foreach( $referrals as $referral ) {

				/**
				 * Filters an individual line of referral data to be exported.
				 *
				 * @since 1.9.5
				 *
				 * @param array           $referral_data {
				 *     Single line of exported referral data
				 *
				 *     @type int    $affiliate_id  Affiliate ID.
				 *     @type string $email         Affiliate email.
				 *     @type string $payment_email Affiliate payment email.
				 *     @type float  $amount        Referral amount.
				 *     @type string $currency      Referral currency.
				 *     @type string $description   Referral description.
				 *     @type string $campaign      Campaign.
				 *     @type string $reference     Referral reference.
				 *     @type string $context       Context the referral was created under, e.g. 'woocommerce'.
				 *     @type string $status        Referral status.
				 *     @type string $date          Referral date.
				 * }
				 * @param \AffWP\Referral $referral Referral object.
				 */
				$referral_data = apply_filters( 'affwp_referral_export_get_data_line', array(
					'affiliate_id'  => $referral->affiliate_id,
					'email'         => affwp_get_affiliate_email( $referral->affiliate_id ),
					'name'          => affwp_get_affiliate_name( $referral->affiliate_id ),
					'payment_email' => affwp_get_affiliate_payment_email( $referral->affiliate_id ),
					'username'      => affwp_get_affiliate_login( $referral->affiliate_id ),
					'amount'        => $referral->amount,
					'currency'      => $referral->currency,
					'description'   => $referral->description,
					'campaign'      => $referral->campaign,
					'reference'     => $referral->reference,
					'context'       => $referral->context,
					'status'        => $referral->status,
					'date'          => $referral->date
				), $referral );

				// Add slashing.
				$data[] = array_map( function( $column ) {
					return addslashes( preg_replace( "/\"/","'", $column ) );
				}, $referral_data );

				unset( $referral_data );
			}

		}

		/** This filter is documented in includes/admin/tools/export/class-export.php */
		$data = apply_filters( 'affwp_export_get_data', $data );

		/** This filter is documented in includes/admin/tools/export/class-export.php */
		$data = apply_filters( 'affwp_export_get_data_' . $this->export_type, $data );

		return $data;
	}

}
