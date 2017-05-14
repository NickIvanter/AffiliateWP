<?php
namespace AffWP\Utils\Batch_Process;

use AffWP\Utils\Batch_Process as Batch;

require_once('class-batch-export-affiliates.php');

/**
 * Implements a batch processor for exporting affiliate accounts based on status to a CSV file.
 *
 * @since 2.0
 *
 * @see \AffWP\Utils\Batch_Process\Export\CSV
 * @see \AffWP\Utils\Batch_Process\With_PreFetch
 */
class Export_Sellers extends Export_Affiliates implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @access public
	 * @since  2.0
	 * @var    string
	 */
	public $batch_id = 'export-sellers';

	/**
	 * Pre-fetches data to speed up processing.
	 *
	 * @access public
	 * @since  2.0
	 */
	public function pre_fetch() {
		$total_to_export = $this->get_total_count();

		if ( false === $total_to_export  ) {
			$args = array(
				'fields' => 'ids',
				'status' => $this->status,
				'sellers' => true,
			);

			$total_to_export = affiliate_wp()->affiliates->get_affiliates( $args, true );

			$this->set_total_count( absint( $total_to_export ) );
		}
	}

	/**
	 * Retrieves the affiliate export data for a single step in the process.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @return array Data for a single step of the export.
	 */
	public function get_data() {

		$args = array(
			'status' => $this->status,
			'number' => $this->per_step,
			'offset' => $this->get_offset(),
			'sellers' => true,
		);

		$data       = array();
		$affiliates = affiliate_wp()->affiliates->get_affiliates( $args );

		if( $affiliates ) {

			foreach( $affiliates as $affiliate ) {

				$data[] = array(
					'affiliate_id'	  => $affiliate->ID,
					'email'			  => affwp_get_affiliate_email( $affiliate->ID ),
					'name'			  => affwp_get_affiliate_name( $affiliate->ID ),
					'payment_email'	  => affwp_get_affiliate_payment_email( $affiliate->ID ),
					'username'		  => affwp_get_affiliate_login( $affiliate->ID ),
					'rate'			  => affwp_get_affiliate_sell_rate( $affiliate->ID ),
					'rate_type'       => affwp_get_affiliate_sell_rate_type( $affiliate->ID ),
					'earnings'        => $affiliate->sell_earnings,
					'unpaid_earnings' => $affiliate->sell_unpaid_earnings,
					'referrals'       => $affiliate->sell_referrals,
					'visits'          => $affiliate->sell_visits,
					'conversion_rate' => affwp_get_affiliate_sell_conversion_rate( $affiliate->ID ),
					'status'		  => $affiliate->status,
					'date_registered' => $affiliate->date_registered,
				);

			}

		}

		return $data;
	}

}
