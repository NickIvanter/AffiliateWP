<?php
namespace AffWP\Utils\Batch_Process;

use AffWP\Utils\Batch_Process as Batch;

require_once 'class-batch-generate-payouts.php';

/**
 * Implements a batch processor for generating payouts logs and exporting them to a CSV file.
 *
 * @since 2.0
 *
 * @see \AffWP\Utils\Batch_Process\Export\CSV
 * @see \AffWP\Utils\Batch_Process\With_PreFetch
 */
class Generate_Payouts_Sells extends Generate_Payouts implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @access public
	 * @since  2.0
	 * @var    string
	 */
	public $batch_id = 'generate-payouts-sells';

	/**
	 * Pre-fetches data to speed up processing.
	 *
	 * @access public
	 * @since  2.0
	 */
	public function pre_fetch() {
		// Referrals to export.
		$compiled_data = affiliate_wp()->utils->data->get( "{$this->batch_id}_compiled_data", array() );

		if ( false === $compiled_data ) {
			$args = array(
				'status'       => 'unpaid',
				'number'       => -1,
				'date'         => $this->date,
				'affiliate_id' => $this->affiliate_id,
				'sell'		   => true,
			);

			$referrals_for_export = affiliate_wp()->referrals->get_referrals( $args );

			$this->compile_potential_payouts( $referrals_for_export );
		}
	}

}
