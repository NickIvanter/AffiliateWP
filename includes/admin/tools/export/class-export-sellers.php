<?php
/**
 * Affiliates Export Class
 *
 * This class handles exporting affiliate data.
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3
 */

use AffWP\Utils\Exporter;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once('class-export-affiliates.php');

/**
 * Affiliate_WP_Export Class
 *
 * @since 1.3
 */
class Affiliate_WP_Sellers_Export extends Affiliate_WP_Affiliate_Export implements Exporter\CSV {

	/**
	 * Retrieves the data being exported.
	 *
	 * @access public
	 * @since  1.3
	 *
	 * @return array $data Data for Export
	 */
	public function get_data() {

		$args = array(
			'status' => $this->status,
			'number' => -1,
			'seller' => true,
		);

		$data       = array();
		$affiliates = affiliate_wp()->affiliates->get_affiliates( $args );

		if( $affiliates ) {

			foreach( $affiliates as $affiliate ) {

				$data[] = array(
					'affiliate_id'    => $affiliate->affiliate_id,
					'email'           => affwp_get_affiliate_email( $affiliate->affiliate_id ),
					'name'            => affwp_get_affiliate_name( $affiliate->affiliate_id ),
					'payment_email'   => affwp_get_affiliate_payment_email( $affiliate->affiliate_id ),
					'username'        => affwp_get_affiliate_login( $affiliate->affiliate_id ),
					'rate'            => affwp_get_affiliate_sell_rate( $affiliate->affiliate_id ),
					'rate_type'       => affwp_get_affiliate_sell_rate_type( $affiliate->affiliate_id ),
					'earnings'        => $affiliate->sell_earnings,
					'referrals'       => $affiliate->sell_referrals,
					'visits'          => $affiliate->sell_visits,
					'conversion_rate' => affwp_get_affiliate_sell_conversion_rate( $affiliate->affiliate_id ),
					'status'          => $affiliate->status,
					'date_registered' => $affiliate->date_registered,
				);

			}

		}

		/** This filter is documented in includes/admin/tools/export/class-export.php */
		$data = apply_filters( 'affwp_export_get_data', $data );

		/** This filter is documented in includes/admin/tools/export/class-export.php */
		$data = apply_filters( 'affwp_export_get_data_' . $this->export_type, $data );

		return $data;
	}

}
