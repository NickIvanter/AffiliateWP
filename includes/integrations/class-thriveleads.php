<?php

class Affiliate_WP_Thrive_Leads extends Affiliate_WP_Base {

	/**
	 * Register hooks for this integration
	 *
	 * @access public
	 */
	public function init() {

		if ( !defined( 'TVE_LEADS_ACTION_FORM_CONVERSION' ) ) {
			return;
		}

		$this->context = 'thriveleads';

		// Gravity Forms hooks
		add_filter( 'tve_leads_register_contact_success', array( $this, 'add_referral' ), 10, 2 );
		// add_action( 'gform_post_payment_completed', array( $this, 'mark_referral_complete' ), 10, 2 );
		// add_action( 'gform_post_payment_refunded', array( $this, 'revoke_referral_on_refund' ), 10, 2 );

		// Internal hooks
		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );

		// Form settings @todo
		// add_filter( 'gform_form_settings', array( $this, 'add_settings' ), 10, 2 );
		// add_filter( 'gform_pre_form_settings_save', array( $this, 'save_settings' ) );
	}

	/**
	 * Add pending referral
	 *
	 * @access public
	 *
	 */
	public function add_referral( $contact_id, $data ) {

		// Get affiliate ID
		$affiliate_id = $this->affiliate_id;

		// Block referral if form does not allow them
		// if ( ! rgar( $form, 'affwp_allow_referrals' ) ) {
		// 	return;
		// }

		// Block referral if not referred or affiliate ID is empty
		if ( ! $this->was_referred() && empty( $affiliate_id ) ) {
			return;
		}

		// Get all emails from submitted form
		$emails = $this->get_emails( $data );

		// Block referral if any of the affiliate's emails have been submitted
		if ( $emails ) {
			foreach ( $emails as $customer_email ) {
				if ( $this->is_affiliate_email( $customer_email, $affiliate_id ) ) {

					if ( $this->debug ) {
						$this->log( 'Referral not created because affiliate\'s own account was used.' );
					}

					return false;

				}
			}
		}

        // Price, it's supposed to be zero, but what if...
		$total = 0;
        $desc = 'Thrive Leads contact'; // @todo More specific?

		$referral_total = $this->calculate_referral_amount( $total, $contact_id );

		$referral_id = $this->insert_pending_referral( $referral_total, $contact_id, $desc );

		if( empty( $total ) ) {
			$this->mark_referral_complete( $referral_id );
		}

	}

	/**
	 * Mark referral as complete
	 *
	 * @access public
	 */
	public function mark_referral_complete( $referral_id ) {

		$this->complete_referral( $referral_id );

        /*
		   $referral = affiliate_wp()->referrals->get_by( 'reference', $referral_id, $this->context );
		   $amount   = affwp_currency_filter( affwp_format_amount( $referral->amount ) );
		   $name     = affiliate_wp()->affiliates->get_affiliate_name( $referral->affiliate_id );
		   $note     = sprintf( __( 'Referral #%d for %s recorded for %s', 'affiliate-wp' ), $referral->referral_id, $amount, $name );
         */
	}


	/**
	 * Sets up the reference link in the Referrals table
	 *
	 * @access public
	 *
	 * @param  int    $reference
	 * @param  object $referral
	 * @return string
	 */
	public function reference_link( $reference = 0, $referral ) {

		if ( empty( $referral->context ) || 'thriveleads' != $referral->context ) {
			return $reference;
		}

		$url = admin_url( 'admin.php?page=thrive_leads_reporting#reporting');

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}


	/**
	 * Get all emails from form
	 *
	 * @since 2.0
	 * @access public
	 * @return array $emails all emails submitted via email fields
	 */
	public function get_emails( $post_data ) {

		return [ $post_data['email'] ];
	}

	/**
	 * Register the form-specific settings
	 *
	 * @since  1.7
	 * @return void
	 */
	// public function add_settings( $settings, $form ) {

	// 	$checked = rgar( $form, 'affwp_allow_referrals' );

	// 	$field  = '<input type="checkbox" id="affwp_allow_referrals" name="affwp_allow_referrals" value="1" ' . checked( 1, $checked, false ) . ' />';
	// 	$field .= ' <label for="affwp_allow_referrals">' . __( 'Enable affiliate referral creation for this form', 'affiliate-wp' ) . '</label>';

	// 	$settings['Form Options']['affwp_allow_referrals'] = '
	// 		<tr>
	// 			<th>' . __( 'Allow referrals', 'affiliate-wp' ) . '</th>
	// 			<td>' . $field . '</td>
	// 		</tr>';

	// 	return $settings;

	// }

	/**
	 * Save form settings
	 *
	 * @since 1.7
	 */
	// public function save_settings( $form ) {

	// 	$form['affwp_allow_referrals'] = rgpost( 'affwp_allow_referrals' );

	// 	return $form;

	// }

}

new Affiliate_WP_Thrive_Leads;
