<?php
namespace AffWP\Utils\Batch_Process;

use AffWP\Utils;
use AffWP\Utils\Batch_Process as Batch;

require_once('class-batch-recount-affiliate-stats.php');

/**
 * Implements a batch process to recount all affiliate stats.
 *
 * @see \AffWP\Utils\Batch_Process\Base
 * @see \AffWP\Utils\Batch_Process
 * @package AffWP\Utils\Batch_Process
 */
class Recount_Seller_Stats extends Recount_Affiliate_Stats implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @access public
	 * @since  2.0
	 * @var    string
	 */
	public $batch_id = 'recount-seller-stats';


	/**
	 * Pre-fetches data to speed up processing.
	 *
	 * @access public
	 * @since  2.0
	 */
	public function pre_fetch() {

		// If an invalid affiliate is set, go no further.
		if ( ! $this->affiliate_id && $this->affiliate_filter ) {
			affiliate_wp()->utils->data->write( "{$this->batch_id}_affiliate_totals", array() );

			$this->set_total_count( 0 );

			return;
		}

		if ( false === $this->get_total_count() ) {
			if ( in_array( $this->type, array( 'earnings', 'unpaid-earnings' ), true ) ) {

				$this->compile_affiliate_totals();

			} else {

				$this->compile_totals();

			}
		}

	}

	/**
	 * Compiles and stores amount totals for all affiliates with unpaid referrals.
	 *
	 * @access public
	 * @since  2.0
	 */
	public function compile_affiliate_totals() {
		$affiliate_totals = affiliate_wp()->utils->data->get( "{$this->batch_id}_affiliate_totals", array() );

		if ( false === $affiliate_totals ) {
			if ( 'earnings' === $this->type ) {
				$status = ['paid', 'refunded'];
			} elseif ( 'unpaid-earnings' === $this->type ) {
				$status = 'unpaid';
			} else {
				$status = '';
			}

			if ( empty( $status ) ) {
				// Bail if no status.
				return;
			}

			$args = array(
				'number'       => -1,
				'status'       => $status,
				'affiliate_id' => $this->affiliate_id,
				'sell'		   => true,
			);

			$referrals = affiliate_wp()->referrals->get_referrals( $args );

			$data_sets = array();

			foreach ( $referrals as $referral ) {
				$data_sets[ $referral->affiliate_id ][] = $referral;
			}

			$affiliate_totals = array();

			if ( ! empty( $data_sets ) ) {
				foreach ( $data_sets as $affiliate_id => $referrals ) {
					foreach ( $referrals as $referral ) {
						if ( isset( $affiliate_totals[ $referral->affiliate_id ] ) ) {
							$affiliate_totals[ $referral->affiliate_id ] += $referral->amount;
						} else {
							$affiliate_totals[ $referral->affiliate_id ] = $referral->amount;
						}
					}
				}
			}

			affiliate_wp()->utils->data->write( "{$this->batch_id}_affiliate_totals", $affiliate_totals );

			$this->set_total_count( count( $affiliate_totals ) );
		}

	}

	/**
	 * Compiles totals for referrals and visits.
	 *
	 * @access public
	 * @since  2.0.5
	 */
	public function compile_totals() {
		$count = 0;

		$affiliate_totals = array();

		if ( 'referrals' === $this->type ) {

			$referrals = affiliate_wp()->referrals->get_referrals( array(
				'affiliate_id' => $this->affiliate_id,
				'number'       => -1,
				'fields'       => 'affiliate_id',
				'sell'		   => true,
			) );

			$referrals = array_map( 'absint', $referrals );

			$affiliate_totals = array_count_values( $referrals );

		} elseif ( 'visits' === $this->type ) {

			$visits = affiliate_wp()->visits->get_visits( array(
				'affiliate_id' => $this->affiliate_id,
				'number'       => -1,
				'fields'       => 'affiliate_id',
				'sell'		   => true,
			) );

			$visits = array_map( 'absint', array_count_values( $visits ) );

			$affiliate_totals = array_count_values( $visits );
		}

		affiliate_wp()->utils->data->write( "{$this->batch_id}_affiliate_totals", $affiliate_totals );

		$this->set_total_count( count( $affiliate_totals ) );
	}

	/**
	 * Processes a single step (batch).
	 *
	 * @access public
	 * @since  2.0
	 */
	public function process_step() {
		$offset        = $this->get_offset();
		$current_count = $this->get_current_count();

		$affiliate_totals = affiliate_wp()->utils->data->get( "{$this->batch_id}_affiliate_totals", array() );
		$affiliate_ids    = array_keys( $affiliate_totals );

		if ( isset( $affiliate_ids[ $offset ] ) ) {
			$affiliate_id = $affiliate_ids[ $offset ];
		} else {
			return 'done';
		}

		$total = $affiliate_totals[ $affiliate_id ];

		if ( 'earnings' === $this->type ) {

			affiliate_wp()->affiliates->update( $affiliate_id, array( 'sell_earnings' => floatval( $total ), '', 'affiliate' ) );

		} elseif ( 'unpaid-earnings' === $this->type ) {

			affiliate_wp()->affiliates->update( $affiliate_id, array( 'sell_unpaid_earnings' => floatval( $total ), '', 'affiliate' ) );

		} elseif ( 'referrals' === $this->type ) {

			affiliate_wp()->affiliates->update( $affiliate_id, array( 'sell_referrals' => $total ), '', 'affiliate' );

		} elseif ( 'visits' === $this->type ) {

			affiliate_wp()->affiliates->update( $affiliate_id, array( 'sell_visits' => $total ), '', 'affiliate' );
		}

		$this->set_current_count( absint( $current_count ) + 1 );

		return ++$this->step;
	}

}
