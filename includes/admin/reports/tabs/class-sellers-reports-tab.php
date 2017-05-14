<?php
namespace AffWP\Sellers\Admin\Reports;

use AffWP\Admin\Reports;

/**
 * Implements an 'Affiliates' tab for the Reports screen.
 *
 * @since 1.9
 *
 * @see \AffWP\Admin\Reports\Tab
 */
class Tab extends Reports\Tab {

	/**
	 * Sets up the Affiliates tab for Reports.
	 *
	 * @access public
	 * @since  1.9
	 */
	public function __construct() {
		$this->tab_id   = 'sellers';
		$this->label    = __( 'Sellers', 'affiliate-wp' );
		$this->priority = 5;
		$this->graph    = new \Affiliate_WP_Registrations_Graph;

		parent::__construct();
	}

	/**
	 * Registers the 'Total Affiliates' (all time) tile.
	 *
	 * @access public
	 * @since  1.9
	 */
	public function total_affiliates_tile() {
		$this->register_tile( 'total_sellers', array(
			'label'           => __( 'Total Affiliates', 'affiliate-wp' ),
			'type'            => 'number',
			'data'            => affiliate_wp()->affiliates->count( ['sellers' => true] ),
			'comparison_data' => __( 'All Time', 'affiliate-wp' ),
		) );
	}

	/**
	 * Registers the 'Top Earning Affiliate' date-based tile.
	 *
	 * @access public
	 * @since  1.9
	 */
	public function top_earning_affiliates_tile() {
		$affiliate_ids = affiliate_wp()->referrals->get_referrals( array(
			'number' => -1,
			'status' => [ 'paid', 'refunded' ],
			'fields' => 'affiliate_id',
			'date'   => $this->date_query,
			'sell'	 => true,
		) );

		$affiliate_counts = array_count_values( $affiliate_ids );

		$top_affiliate = key( array_slice( $affiliate_counts, 0, 1, true ) );

		if ( ! empty( $top_affiliate ) && $affiliate = affwp_get_affiliate( $top_affiliate ) ) {
			$name = affwp_get_affiliate_name( $affiliate->ID );

			$data_link = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( affwp_admin_url( 'sellers', array(
					'affiliate_id' => $affiliate->ID,
					'action'       => 'view_affiliate',
				) ) ),
				empty( $name ) ? sprintf( __( 'Affiliate #%d', 'affiliate-wp' ), $affiliate->ID ) : $name
			);

			$this->register_tile( 'top_earning_seller', array(
				'label' => __( 'Top Earning Affiliate', 'affiliate-wp' ),
				'data'  => $data_link,
				'comparison_data' => sprintf( '%1$s (%2$s)',
					$this->get_date_comparison_label(),
					affwp_currency_filter( affwp_format_amount( $affiliate->sell_earnings ) )
				),
			) );
		} else {
			$this->register_tile( 'top_earning_seller', array(
				'label' => __( 'Top Earning Seller', 'affiliate-wp' ),
				'data'  => '',
				'comparison_data' => $this->get_date_comparison_label(),
			) );
		}

	}
	/**
	 * Registers the 'New Affiliates' date-based tile.
	 *
	 * @access public
	 * @since  1.9
	 */
	public function new_affiliates_tile() {
		$this->register_tile( 'new_affiliates', array(
			'label'           => __( 'New Affiliates', 'affiliate-wp' ),
			'type'            => 'number',
			'data'            => affiliate_wp()->affiliates->count( array(
				'date' => $this->date_query,
				'sellers' => true,
			) ),
			'comparison_data' => $this->get_date_comparison_label(),
			'context'         => 'secondary',
		) );
	}

	/**
	 * Register the 'Highest Converting Affiliate' date-based tile.
	 *
	 * @access public
	 * @since  1.9
	 */
	public function highest_converting_affiliate_tile() {
		$affiliate_ids = affiliate_wp()->visits->get_visits( array(
			'number'          => -1,
			'referral_status' => 'converted',
			'fields'          => 'affiliate_id',
			'date'            => $this->date_query,
			'sell'			  => true,
		) );

		$affiliate_counts = array_count_values( $affiliate_ids );

		$highest_converter = key( array_slice( $affiliate_counts, 0, 1, true ) );

		if ( ! empty( $highest_converter ) && $affiliate = affwp_get_affiliate( $highest_converter ) ) {
			$name       = affwp_get_affiliate_name( $affiliate->ID );
			$data_link  = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( affwp_admin_url( 'sells', array(
					'affiliate_id' => $affiliate->ID,
					'orderby'      => 'status',
					'order'        => 'ASC',
				) ) ),
				empty( $name ) ? sprintf( __( 'Affiliate #%d', 'affiliate-wp' ), $affiliate->ID ) : $name
			);

			$referrals_count = affwp_get_affiliate_sell_referral_count( $affiliate->ID );
			$referrals_data = sprintf( _n( '%d referral', '%d referrals', $referrals_count, 'affiliate-wp' ),
				number_format_i18n( $referrals_count )
			);

			$comparison_data = sprintf( '%1$s (%2$s)',
				$this->get_date_comparison_label(),
				$referrals_data
			);
		} else {
			$data_link = '';
			$comparison_data = $this->get_date_comparison_label();
		}

		$this->register_tile( 'highest_converting_seller', array(
			'label'           => __( 'Highest Converting Affiliate', 'affiliate-wp' ),
			'context'         => 'secondary',
			'data'            => $data_link,
			'comparison_data' => $comparison_data,
		) );
	}

	/**
	 * Registers the 'Average Payout' date-based tile.
	 *
	 * @access public
	 * @since  1.9
	 */
	public function average_payout_tile() {
		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'number' => -1,
			'fields' => affiliate_wp()->affiliates->payouts->table_name . '.amount',
			'sell'	 => true,
		) );

		if ( ! $payouts ) {
			$payouts = array( 0 );
		} else {
			$payouts = array_map(function($p) { return $p->amount; }, $payouts);
		}

		$this->register_tile( 'average_payout_amount_seller', array(
			'label'           => __( 'Average Payout', 'affiliate-wp' ),
			'type'            => 'amount',
			'context'         => 'tertiary',
			'data'            => array_sum( $payouts ) / count( $payouts ),
			'comparison_data' => $this->get_date_comparison_label(),
		) );
	}

	/**
	 * Registers the Affiliates tab tiles.
	 *
	 * @access public
	 * @since  1.9
	 */
	public function register_tiles() {
		$this->total_affiliates_tile();
		$this->top_earning_affiliates_tile();
		$this->new_affiliates_tile();
		$this->highest_converting_affiliate_tile();
		$this->average_payout_tile();
	}

	/**
	 * Handles displaying the 'Trends' graph.
	 *
	 * @access public
	 * @since  1.9
	 */
	public function display_trends() {
		$this->graph->set( 'show_controls', false );
		$this->graph->set( 'x_mode',   'time' );
		$this->graph->set( 'currency', false  );
		$this->graph->display();
	}

}
