<?php
namespace AffWP\Utils\Batch_Process;

use AffWP\Utils\Batch_Process as Batch;

/**
 * Implements a batch processor for exporting affiliate accounts based on status to a CSV file.
 *
 * @since 2.0
 *
 * @see \AffWP\Utils\Batch_Process\Export\CSV
 * @see \AffWP\Utils\Batch_Process\With_PreFetch
 */
class Export_Referrals extends Batch\Export\CSV implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @access public
	 * @since  2.0
	 * @var    string
	 */
	public $batch_id = 'export-referrals';

	/**
	 * Export type.
	 *
	 * @access public
	 * @since  2.0
	 * @var    string
	 */
	public $export_type = 'referrals';

	/**
	 * Capability needed to perform the current export.
	 *
	 * @access public
	 * @since  2.0
	 * @var    string
	 */
	public $capability = 'export_affiliate_data';

	/**
	 * ID of affiliate to export referrals for.
	 *
	 * @access public
	 * @since  2.0
	 * @var    string
	 */
	public $affiliate_id = 0;

	/**
	 * Start and/or end dates to retrieve referrals for.
	 *
	 * @access public
	 * @since  2.0
	 * @var    array
	 */
	public $date = array();

	/**
	 * Status to export referrals for.
	 *
	 * @access public
	 * @since  2.0
	 * @var    string
	 */
	public $status = '';

	/**
	 * Initializes the batch process.
	 *
	 * This is the point where any relevant data should be initialized for use by the processor methods.
	 *
	 * @access public
	 * @since  2.0
	 */
	public function init( $data = null ) {

		if ( null !== $data ) {

			if ( ! empty( $data['user_id'] ) ) {
				if ( $affiliate_id = affwp_get_affiliate_id( absint( $data['user_id'] ) ) ) {
					$this->affiliate_id = $affiliate_id;
				}
			}

			if ( ! empty( $data['start_date' ] ) ) {
				$this->date['start'] = sanitize_text_field( $data['start_date' ] );
			}

			if ( ! empty( $data['end_date'] ) ) {
				$this->date['end'] = sanitize_text_field( $data['end_date'] );
			}

			if ( ! empty( $data['status'] ) ) {
				$this->status = sanitize_text_field( $data['status'] );

				if ( 0 === $this->status ) {
					$this->status = '';
				}
			}
		}

	}

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
				'fields'       => 'ids',
				'status'       => $this->status,
				'date'         => $this->date,
				'affiliate_id' => $this->affiliate_id
			);

			$total_to_export = affiliate_wp()->referrals->get_referrals( $args, true );

			$this->set_total_count( $total_to_export );
		}
	}

	/**
	 * Retrieves the columns for the CSV export.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @return array The list of CSV columns.
	 */
	public function csv_cols() {
		return array(
			'affiliate_id'  => __( 'Affiliate ID', 'affiliate-wp' ),
			'email'         => __( 'Email', 'affiliate-wp' ),
			'name'          => __( 'Name', 'affiliate-wp' ),
			'payment_email' => __( 'Payment Email', 'affiliate-wp' ),
			'username'      => __( 'Username', 'affiliate-wp' ),
			'amount'        => __( 'Amount', 'affiliate-wp' ),
			'currency'      => __( 'Currency', 'affiliate-wp' ),
			'description'   => __( 'Description', 'affiliate-wp' ),
			'campaign'      => __( 'Campaign', 'affiliate-wp' ),
			'reference'     => __( 'Reference', 'affiliate-wp' ),
			'context'       => __( 'Context', 'affiliate-wp' ),
			'status'        => __( 'Status', 'affiliate-wp' ),
			'date'          => __( 'Date', 'affiliate-wp' ),
		);
	}

	/**
	 * Processes a single step (batch).
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @param int|string $step Step in the process. Accepts either a step number or 'done'.
	 */
	public function process_step( $step ) {
		if ( is_null( $this->status ) ) {
			return new \WP_Error( 'no_status_found', __( 'No valid referral status was selected for export.', 'affiliate-wp' ) );
		}

		$current_count = $this->get_current_count();

		$data = $this->get_data( $step );

		if ( empty( $data ) ) {
			// If empty and the first step, it's an empty export.
			if ( $step < 2 ) {
				$this->is_empty = true;
			}

			return 'done';
		}

		if ( $step < 2 ) {

			// Make sure we start with a fresh file on step 1.
			@unlink( $this->file );
			$this->csv_cols_out();
		}

		$this->csv_rows_out();

		$this->set_current_count( absint( $current_count ) + count( $data ) );

		return ++$step;
	}

	/**
	 * Retrieves the affiliate export data for a single step in the process.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @param int $step Optional. Step number. 'done' should be handled prior to calling this method. Default 1.
	 * @return array Data for a single step of the export.
	 */
	public function get_data( $step = 1 ) {

		$args = array(
			'status'       => $this->status,
			'date'         => $this->date,
			'affiliate_id' => $this->affiliate_id,
			'number'       => $this->per_step,
			'offset'       => $this->get_offset( $step ),
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

		return $data;
	}

	/**
	 * Retrieves a message for the given code.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @param string $code Message code.
	 * @return string Message.
	 */
	public function get_message( $code ) {

		switch( $code ) {

			case 'done':
				$final_count = $this->get_current_count();

				$message = sprintf(
					_n(
						'%s referral was successfully exported.',
						'%s referrals were successfully exported.',
						$final_count,
						'affiliate-wp'
					), number_format_i18n( $final_count )
				);
				break;

			default:
				$message = '';
				break;
		}

		return $message;
	}

	/**
	 * Defines logic to execute once batch processing is complete.
	 *
	 * @access public
	 * @since  2.0
	 * @abstract
	 */
	public function finish() {
		affiliate_wp()->utils->data->delete( "{$this->batch_id}_current_count" );
		affiliate_wp()->utils->data->delete( "{$this->batch_id}_total_count" );
	}

}