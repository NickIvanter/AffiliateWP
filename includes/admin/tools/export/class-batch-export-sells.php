<?php
namespace AffWP\Utils\Batch_Process;

use AffWP\Utils\Batch_Process as Batch;

require_once('class-batch-export-referrals.php');

/**
 * Implements a batch processor for exporting referrals based on status to a CSV file.
 *
 * @since 2.0
 *
 * @see \AffWP\Utils\Batch_Process\Export\CSV
 * @see \AffWP\Utils\Batch_Process\With_PreFetch
 */
class Export_Sells extends Export_Referrals implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @access public
	 * @since  2.0
	 * @var    string
	 */
	public $batch_id = 'export-sells';

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
				'number'       => -1,
				'fields'       => 'ids',
				'status'       => $this->status,
				'date'         => $this->date,
				'affiliate_id' => $this->affiliate_id,
				'sell'		   => true,
			);

			$total_to_export = affiliate_wp()->referrals->get_referrals( $args, true );

			$this->set_total_count( $total_to_export );
		}
	}

	/**
	 * Retrieves the referral export data for a single step in the process.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @return array Data for a single step of the export.
	 */
	public function get_data() {

		$args = array(
			'status'       => $this->status,
			'date'         => $this->date,
			'affiliate_id' => $this->affiliate_id,
			'number'       => $this->per_step,
			'offset'       => $this->get_offset(),
			'sell'		   => true,
		);

		$data         = array();
		$affiliates   = array();
		$referral_ids = array();
		$referrals    = affiliate_wp()->referrals->get_referrals( $args );

		if( $referrals ) {

			foreach( $referrals as $referral ) {

				/** This filter is documented in includes/admin/tools/export/class-export-referrals.php */
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

		return $data;
	}
}
