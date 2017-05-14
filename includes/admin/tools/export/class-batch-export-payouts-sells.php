<?php
namespace AffWP\Utils\Batch_Process;

use AffWP\Utils\Batch_Process as Batch;

require_once('class-batch-export-payouts.php');

/**
 * Implements a batch processor for exporting payouts based on affiliate ID or a date range
 * to a CSV file.
 *
 * @since 2.0
 *
 * @see \AffWP\Utils\Batch_Process\Export\CSV
 * @see \AffWP\Utils\Batch_Process\With_PreFetch
 */
class Export_Payouts_Sells extends Export_Payouts implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @access public
	 * @since  2.0
	 * @var    string
	 */
	public $batch_id = 'export-payouts-sells';

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

			$total_to_export = affiliate_wp()->affiliates->payouts->get_payouts( $args, true );

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

		/** @var \AffWP\Affiliate\Payout[] $payouts */
		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( $args );

		if( $payouts ) {

			$date_format = get_option( 'date_format' );

			foreach( $payouts as $payout ) {

				if ( $owner_user = get_user_by( 'id', $payout->owner ) ) {
					$owner = sprintf( '%s (#%d)',
						$owner_user->data->display_name,
						$payout->owner
					);
				} else {
					$owner = $payout->owner;
				}

				$affiliate = sprintf( '%s (#%d)',
					affwp_get_affiliate_name( $payout->affiliate_id ),
					$payout->affiliate_id
				);

				/**
				 * Filters an individual line of payout data to be exported.
				 *
				 * @since 2.0
				 *
				 * @param array           $payout_data {
				 *     Single line of exported payout data
				 *
				 *     @type int    $payout_id     Payout ID.
				 *     @type int    $affiliate_id  Affiliate ID.
				 *     @type string $referrals     Comma-separated list of referral IDs.
				 *     @type float  $amount        Payout amount.
				 *     @type string $owner         Username of payout owner.
				 *     @type string $payout_method Payout method.
				 *     @type string $status        Payout status.
				 *     @type string $date          Payout date.
				 * }
				 * @param \AffWP\Affiliate\Payout $payout Payout object.
				 */
				$payout_data = apply_filters( 'affwp_payout_export_get_data_line', array(
					'payout_id'     => $payout->ID,
					'affiliate'     => $affiliate,
					'referrals'     => $payout->referrals,
					'amount'        => $payout->amount,
					'owner'         => $owner,
					'payout_method' => $payout->payout_method,
					'status'        => $payout->status,
					'date'          => date_i18n( $date_format, strtotime( $payout->date ) ),
				), $payout );

				// Add slashing.
				$data[] = array_map( function( $column ) {
					return addslashes( preg_replace( "/\"/","'", $column ) );
				}, $payout_data );

				unset( $payout_data );
			}

		}

		return $data;
	}
}
