<?php
$affiliate_id = affwp_get_affiliate_id();
?>
<div id="affwp-affiliate-dashboard-referral-counts" class="affwp-tab-content">

	<h4><?php _e( 'Sell Statistics', 'affiliate-wp' ); ?></h4>

	<table class="affwp-table">
		<thead>
			<tr>
				<th><?php _e( 'Unpaid Sells', 'affiliate-wp' ); ?></th>
				<th><?php _e( 'Paid Sells', 'affiliate-wp' ); ?></th>
				<th><?php _e( 'Sell Visits', 'affiliate-wp' ); ?></th>
				<th><?php _e( 'Sell Conversion Rate', 'affiliate-wp' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<td><?php echo affwp_count_sell_referrals( $affiliate_id, 'unpaid' ); ?></td>
				<td><?php echo affwp_count_sell_referrals( $affiliate_id, ['paid', 'refunded'] ); ?></td>
				<td><?php echo affwp_count_sell_visits( $affiliate_id ); ?></td>
				<td><?php echo affwp_get_affiliate_sell_conversion_rate( $affiliate_id ); ?></td>
			</tr>
		</tbody>
	</table>

	<?php
	/**
	 * Fires immediately after stats counts in the affiliate area.
     *
  	 * @param int $affiliate_id Affiliate ID of the currently logged-in affiliate.
	 */
	do_action( 'affwp_affiliate_dashboard_after_counts', $affiliate_id );
	?>

</div>

<div id="affwp-affiliate-dashboard-earnings-stats" class="affwp-tab-content">
	<table class="affwp-table">
		<thead>
			<tr>
				<th><?php _e( 'Unpaid Earnings', 'affiliate-wp' ); ?></th>
				<th><?php _e( 'Paid Earnings', 'affiliate-wp' ); ?></th>
				<th><?php _e( 'Commission Rate', 'affiliate-wp' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<td><?php echo affwp_get_affiliate_sell_unpaid_earnings( $affiliate_id, true ); ?></td>
				<td><?php echo affwp_get_affiliate_sell_earnings( $affiliate_id, true ); ?></td>
				<td><?php echo affwp_get_affiliate_sell_rate( $affiliate_id, true ); ?></td>
			</tr>
		</tbody>
	</table>

	<?php
	/**
	 * Fires immediately after earnings stats in the affiliate area.
     *
  	 * @param int $affiliate_id Affiliate ID of the currently logged-in affiliate.
	 */
	do_action( 'affwp_affiliate_dashboard_after_earnings', $affiliate_id );
	?>

</div>
