<?php

class Affiliate_WP_WooCommerce extends Affiliate_WP_Base {

	/**
	 * The order object
	 *
	 * @access  private
	 * @since   1.1
	*/
	private $order;

	/**
	 * Setup actions and filters
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->context = 'woocommerce';

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_pending_referral' ), 50 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_pending_sells' ), 51 );

		add_action( 'woocommerce_order_status_processing', array( $this, 'add_pending_referral' ), 50 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'add_pending_referral' ), 50 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'add_pending_sells' ), 51 );

		// There should be an option to choose which of these is used
		add_action( 'woocommerce_order_status_completed', array( $this, 'mark_referral_complete' ), 100 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'mark_referral_complete' ), 100 );

		/*
		 * add_action( 'woocommerce_order_status_completed', array( $this, 'mark_sells_complete' ), 20 );
		 * add_action( 'woocommerce_order_status_processing', array( $this, 'mark_sells_complete' ), 20 );
		 */


		add_action( 'woocommerce_order_status_completed_to_refunded', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_processing_to_refunded', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_processing_to_cancelled', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_completed_to_cancelled', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_pending_to_cancelled', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'woocommerce_order_status_pending_to_failed', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'wc-on-hold_to_trash', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'wc-processing_to_trash', array( $this, 'revoke_referral_on_refund' ), 10 );
		add_action( 'wc-completed_to_trash', array( $this, 'revoke_referral_on_refund' ), 10 );

		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );

		add_action( 'woocommerce_coupon_options', array( $this, 'coupon_option' ) );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'store_discount_affiliate' ) );

		// Per product referral rates
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'product_settings' ), 100 );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variation_settings' ), 100, 3 );
		add_action( 'save_post', array( $this, 'save_meta' ) );
		add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'save_variation_data' ) );

		add_action( 'affwp_pre_flush_rewrites', array( $this, 'skip_generate_rewrites' ) );

		// Shop page.
		add_action( 'pre_get_posts', array( $this, 'force_shop_page_for_referrals' ), 5 );

        // Seller product page visits
		add_action( 'wp', array( $this, 'seller_product_visited' ), 100 );

	}

	/**
	 * Store a pending referral when a new order is created
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function add_pending_referral( $order_id = 0 ) {

		$this->order = apply_filters( 'affwp_get_woocommerce_order', new WC_Order( $order_id ) );

		// Check if an affiliate coupon was used
		$coupon_affiliate_id = $this->get_coupon_affiliate_id();

		if ( $this->was_referred() || $coupon_affiliate_id ) {

			// get affiliate ID
			$affiliate_id = $this->get_affiliate_id( $order_id );

			if ( false !== $coupon_affiliate_id ) {
				$affiliate_id = $coupon_affiliate_id;
			}

			// Customers cannot refer themselves
			if ( $this->is_affiliate_email( $this->order->get_billing_email(), $affiliate_id ) ) {

				if( $this->debug ) {
					$this->log( 'Referral not created because affiliates own account was used.' );
				}

				return false;
			}

			// Check for an existing referral
			$existing = affiliate_wp()->referrals->get_by( 'reference', $order_id, $this->context );

			// If an existing referral exists and it is paid or unpaid exit.
			if ( $existing ) {
				return false; // Completed Referral already created for this reference
			}

			$cart_shipping = $this->order->get_total_shipping();

			if ( ! affiliate_wp()->settings->get( 'exclude_tax' ) ) {
				$cart_shipping += $this->order->get_shipping_tax();
			}

			$items = $this->order->get_items();

			// Calculate the referral amount based on product prices
			$amount = 0.00;

			foreach ( $items as $product ) {

				if ( get_post_meta( $product['product_id'], '_affwp_' . $this->context . '_referrals_disabled', true ) ) {
					continue; // Referrals are disabled on this product
				}

				if( ! empty( $product['variation_id'] ) && get_post_meta( $product['variation_id'], '_affwp_' . $this->context . '_referrals_disabled', true ) ) {
					continue; // Referrals are disabled on this variation
				}

				// The order discount has to be divided across the items
				$product_total = $product['line_total'];
				$shipping      = 0;

				if ( $cart_shipping > 0 && ! affiliate_wp()->settings->get( 'exclude_shipping' ) ) {
					$shipping       = $cart_shipping / count( $items );
					$product_total += $shipping;
				}

				if ( ! affiliate_wp()->settings->get( 'exclude_tax' ) ) {
					$product_total += $product['line_tax'];
				}

				if ( $product_total <= 0 && 'flat' !== affwp_get_affiliate_rate_type( $affiliate_id ) ) {
					continue;
				}

				$product_id_for_rate = $product['product_id'];
				if( ! empty( $product['variation_id'] ) && $this->get_product_rate( $product['variation_id'] ) ) {
					$product_id_for_rate = $product['variation_id'];
				}
				$amount += $this->calculate_referral_amount( $product_total, $order_id, $product_id_for_rate, $affiliate_id );

			}

			if ( 0 == $amount && affiliate_wp()->settings->get( 'ignore_zero_referrals' ) ) {

				if( $this->debug ) {
					$this->log( 'Referral not created due to 0.00 amount.' );
				}

				return false; // Ignore a zero amount referral
			}

			$description = $this->get_referral_description();
			$visit_id    = affiliate_wp()->tracking->get_visit_id();

			if ( $existing ) {

				// Update the previously created referral
				affiliate_wp()->referrals->update_referral( $existing->referral_id, array(
					'amount'       => $amount,
					'reference'    => $order_id,
					'description'  => $description,
					'campaign'     => affiliate_wp()->tracking->get_campaign(),
					'affiliate_id' => $affiliate_id,
					'visit_id'     => $visit_id,
					'products'     => $this->get_products(),
					'context'      => $this->context,
					'date'		   => date_i18n( 'Y-m-d H:i:s', strtotime( $this->order->get_date_created() ) ),
				) );

				if( $this->debug ) {
					$this->log( sprintf( 'WooCommerce Referral #%d updated successfully.', $existing->referral_id ) );
				}

			} else {

				// Create a new referral
				$referral_id = affiliate_wp()->referrals->add( apply_filters( 'affwp_insert_pending_referral', array(
					'amount'       => $amount,
					'reference'    => $order_id,
					'description'  => $description,
					'campaign'     => affiliate_wp()->tracking->get_campaign(),
					'affiliate_id' => $affiliate_id,
					'visit_id'     => $visit_id,
					'products'     => $this->get_products(),
					'context'      => $this->context,
					'date'		   => date_i18n( 'Y-m-d H:i:s', strtotime( $this->order->get_date_created() ) ),
				), $amount, $order_id, $description, $affiliate_id, $visit_id, array(), $this->context ) );

				if ( $referral_id ) {

					if( $this->debug ) {
						$this->log( sprintf( 'Referral #%d created successfully.', $referral_id ) );
					}

					$amount = affwp_currency_filter( affwp_format_amount( $amount ) );
					$name   = affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id );

					$this->order->add_order_note( sprintf( __( 'Referral #%d for %s recorded for %s', 'affiliate-wp' ), $referral_id, $amount, $name ) );

				} else {

					if( $this->debug ) {
						$this->log( 'Referral failed to be created.' );
					}

				}
			}

            // $this->mark_referral_complete( $order_id );
		}

	}

	/**
	 * Store a pending sells when a new order is created
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function add_pending_sells( $order_id = 0 ) {

		if ( !isset($this->order) ) $this->order = apply_filters( 'affwp_get_woocommerce_order', new WC_Order( $order_id ) );

        $cart_shipping = $this->order->get_total_shipping();

        $items = $this->order->get_items();
        if ( isset($_COOKIE['affwp_sell_visits']) ) {
            $visits = @unserialize( $_COOKIE['affwp_sell_visits'] );
        } else {
            $visits = [];
        }

        // Calculate the sells amount based on product prices
        foreach ( $items as $product ) {

            if ( get_post_meta( $product['product_id'], '_affwp_' . $this->context . '_sell_referrals_disabled', true ) ) {
                continue; // Sell referrals are disabled on this product
            }

            if( ! empty( $product['variation_id'] ) && get_post_meta( $product['variation_id'], '_affwp_' . $this->context . '_sell_referrals_disabled', true ) ) {
                continue; // Referrals are disabled on this variation
            }

			$affiliate_id = $this->get_seller_id( $product['product_id'] );
            if ( !$affiliate_id ) continue;

            // The order discount has to be divided across the items
            $product_total = $product['line_total'];
            $shipping      = 0;

            // get affiliate ID
            $referrence = $this->make_sell_product_referrence( $order_id, $product );

            if ( $cart_shipping > 0 && ! affiliate_wp()->settings->get( 'sell_exclude_shipping' ) ) {
                $shipping       = $cart_shipping / count( $items );
                $product_total += $shipping;
            }

            if ( ! affiliate_wp()->settings->get( 'sell_exclude_tax' ) ) {
                $product_total += $product['line_tax'];
            }

            // Decrease total price by patment system fee
			if ( affiliate_wp()->settings->get( 'use_payment_method_decrease' ) )
            {
                $payment_method = $this->order->get_payment_method();
                $payment_method_rate = affiliate_wp()->settings->get( 'rate_' . $payment_method );

                if ( $payment_method_rate ) {
                    $product_total -= round( $product_total * $payment_method_rate / 100, affwp_get_decimal_count() );
                }
			}

            if ( $product_total <= 0 && 'flat' !== affwp_get_affiliate_sell_rate_type( $affiliate_id ) ) {
                continue;
            }

            $product_id_for_rate = $product['product_id'];
            if( ! empty( $product['variation_id'] ) && $this->get_product_sell_rate( $product['variation_id'], ['affiliate_id' => $affiliate_id] ) ) {
                $product_id_for_rate = $product['variation_id'];
            }
            $amount = $this->calculate_sell_referral_amount( $product_total, $referrence, $product_id_for_rate, $affiliate_id );


			if ( 0 == $amount && affiliate_wp()->settings->get( 'ignore_zero_sell_referrals' ) ) {

				if( $this->debug ) {
					$this->log( 'Seller referral not created due to 0.00 amount.' );
				}

                continue;
			}

			$description = $this->get_sell_referral_description( $product );
			$visit_id    = isset( $visits[$product['product_id']] ) ? $visits[$product['product_id']] : 0;

			// Customers cannot refer themselves
			if ( $this->is_affiliate_email( $this->order->get_billing_email(), $affiliate_id ) ) {

				if( $this->debug ) {
					$this->log( 'Seller referral not created because affiliates own account was used.' );
				}

				continue;
			}

			// Check for an existing referral
			$existing = affiliate_wp()->referrals->get_by( 'reference', $referrence, $this->context );

			// If an existing referral exists and it is paid or unpaid exit.
			if ( $existing && ( 'paid' == $existing->status || 'unpaid' == $existing->status ) ) {
				return false; // Completed Referral already created for this reference
			}

            $args = array(
                'amount'       => $amount,
                'reference'    => $referrence,
                'description'  => $description,
                'campaign'     => affiliate_wp()->tracking->get_campaign(),
                'affiliate_id' => $affiliate_id,
                'visit_id'     => $visit_id,
                'products'     => $this->make_sell_product_info( $product ),
                'context'      => $this->context,
                'sell'         => true,
            );

 			if ( $existing ) {

				// Update the previously created referral
				affiliate_wp()->referrals->update_referral( $existing->referral_id, $args );

				if( $this->debug ) {
					$this->log( sprintf( 'WooCommerce Seller referral #%d updated successfully.', $existing->referral_id ) );
				}
                setcookie('affwp_sell_visits', null, 0, COOKIEPATH, COOKIE_DOMAIN);

			} else {

				// Create a new referral
				$referral_id = affiliate_wp()->referrals->add(
                    apply_filters( 'affwp_insert_pending_referral', $args,
                                   $amount, $referrence, $description, $affiliate_id, $visit_id, array(), $this->context ) );

				if ( $referral_id ) {

					if( $this->debug ) {
						$this->log( sprintf( 'Seller referral #%d created successfully.', $referral_id ) );
					}

					$amount = affwp_currency_filter( affwp_format_amount( $amount ) );
					$name   = affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id );

					$this->order->add_order_note( sprintf( __( 'Seller referral #%d for %s recorded for %s', 'affiliate-wp' ), $referral_id, $amount, $name ) );
                    setcookie('affwp_sell_visits', null, 0, COOKIEPATH, COOKIE_DOMAIN);

				} else {

					if( $this->debug ) {
						$this->log( 'Sell referral failed to be created.' );
					}

				}
			}
        }

        $this->mark_sells_complete( $order_id );
    }

    public function get_seller_id( $product_id ) {
        return parent::get_seller_id(
			get_post_meta( $product_id, '_affwp_' . $this->context . '_product_seller_id', true )
		);
    }

    public function make_sell_product_referrence( $order_id, $product )
    {
        $referrence = "$order_id" . '-' . $product['product_id'];
        if ( isset($product['variation_id']) && $product['variation_id'] ) {
            $referrence .= '-' . $product['variation_id'];
        }

        return $referrence;
    }


	/**
	 * Retrieves the product details array for the referral
	 *
	 * @access  public
	 * @since   1.6
	 * @return  array
	*/
	public function get_products( $order_id = 0 ) {

		$products  = array();
		$items     = $this->order->get_items();
		foreach( $items as $key => $product ) {

			if( get_post_meta( $product['product_id'], '_affwp_' . $this->context . '_referrals_disabled', true ) ) {
				continue; // Referrals are disabled on this product
			}

			if( ! empty( $product['variation_id'] ) && get_post_meta( $product['variation_id'], '_affwp_' . $this->context . '_referrals_disabled', true ) ) {
				continue; // Referrals are disabled on this variation
			}

			if( affiliate_wp()->settings->get( 'exclude_tax' ) ) {
				$amount = $product['line_total'] - $product['line_tax'];
			} else {
				$amount = $product['line_total'];
			}

			if( ! empty( $product['variation_id'] ) ) {
				$product['name'] .= ' ' . sprintf( __( '(Variation ID %d)', 'affiliate-wp' ), $product['variation_id'] );
			}

			/**
			 * Filters an individual WooCommerce products line as stored in the referral record.
			 *
			 * @since 1.9.5
			 *
			 * @param array $line {
			 *     A WooCommerce product data line.
			 *
			 *     @type string $name            Product name.
			 *     @type int    $id              Product ID.
			 *     @type float  $amount          Product amount.
			 *     @type float  $referral_amount Referral amount.
			 * }
			 * @param array $product  Product data.
			 * @param int   $order_id Order ID.
			 */
			$products[] = apply_filters( 'affwp_woocommerce_get_products_line', array(
				'name'            => $product['name'],
				'id'              => $product['product_id'],
				'price'           => $amount,
				'referral_amount' => $this->calculate_referral_amount( $amount, $order_id, $product['product_id'] )
			), $product, $order_id );

		}

		return $products;

	}

	/**
	 * Retrieves the product details array for the referral
	 *
	 * @access  public
	 * @since   1.6
	 * @return  array
	*/
	public function make_sell_product_info( $product ) {

		$products  = array();
        $amount    = 0;

        if( affiliate_wp()->settings->get( 'sell_exclude_tax' ) ) {
            $amount = $product['line_total'] - $product['line_tax'];
        } else {
            $amount = $product['line_total'];
        }

        if( ! empty( $product['variation_id'] ) ) {
            $product['name'] .= ' ' . sprintf( __( '(Variation ID %d)', 'affiliate-wp' ), $product['variation_id'] );
        }

        /**
         * Filters an individual WooCommerce products line as stored in the referral record.
         *
         * @since 1.9.5
         *
         * @param array $line {
         *     A WooCommerce product data line.
         *
         *     @type string $name            Product name.
         *     @type int    $id              Product ID.
         *     @type float  $amount          Product amount.
         *     @type float  $referral_amount Referral amount.
         * }
         * @param array $product  Product data.
         * @param int   $order_id Order ID.
         */
        $products[] = apply_filters( 'affwp_woocommerce_get_products_line', array(
            'name'            => $product['name'],
            'id'              => $product['product_id'],
            'price'           => $amount,
            'referral_amount' => $this->calculate_sell_referral_amount( $amount, $order_id, $product['product_id'] )
        ), $product, $order_id );

		return $products;
	}


	/**
	 * Marks a referral as complete when payment is completed.
	 *
	 * @since 1.0
	 * @since 2.0 Orders that are COD and transitioning from `wc-processing` to `wc-complete` stati are now able to be completed.
	 * @access public
	 */
	public function mark_referral_complete( $order_id = 0 ) {

		$this->order = apply_filters( 'affwp_get_woocommerce_order', new WC_Order( $order_id ) );

		// If the WC status is 'wc-processing' and a COD order, leave as 'pending'.
		if ( 'processing' == $this->order->get_status() && 'cod' === get_post_meta( $order_id, '_payment_method', true ) ) {
			return;
		}

		$this->complete_referral( $order_id );
	}

	/**
	 * Marks a sell referrals as complete when payment is completed.
	 *
	 * @access public
	 */
	public function mark_sells_complete( $order_id = 0 ) {

		$this->order = apply_filters( 'affwp_get_woocommerce_order', new WC_Order( $order_id ) );

		// If the WC status is 'wc-processing' and a COD order, leave as 'pending'.
		if ( 'processing' == $this->order->get_status() && 'cod' === get_post_meta( $order_id, '_payment_method', true ) ) {
			return;
		}

        // Get sells all referrences from order
        $items = $this->order->get_items();
        foreach ( $items as $product ) {

            if ( get_post_meta( $product['product_id'], '_affwp_' . $this->context . '_sell_referrals_disabled', true ) ) {
                continue; // Sell referrals are disabled on this product
            }

            if( ! empty( $product['variation_id'] ) && get_post_meta( $product['variation_id'], '_affwp_' . $this->context . '_sell_referrals_disabled', true ) ) {
                continue; // Referrals are disabled on this variation
            }

            $this->complete_referral( $this->make_sell_product_referrence( $order_id, $product ) );
        }
	}

	/**
	 * Revoke the referral when the order is refunded
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function revoke_referral_on_refund( $order_id = 0 ) {

		if ( is_a( $order_id, 'WP_Post' ) ) {
			$order_id = $order_id->ID;
		}

		if( ! affiliate_wp()->settings->get( 'revoke_on_refund' ) ) {
			return;
		}

		if( 'shop_order' != get_post_type( $order_id ) ) {
			return;
		}

		$this->reject_referral( $order_id );

        // Also all sellers referrals if an
        $order = apply_filters( 'affwp_get_woocommerce_order', new WC_Order( $order_id ) );
        if (!$order) return;

        $items = $order->get_items();
        if (!$items) return;

        foreach ( $items as $product ) {
            // Don't check any setting, just try to reject
            $this->reject_referral( $this->make_sell_product_referrence( $order_id, $product ) );
        }

	}

	/**
	 * Setup the reference link
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function reference_link( $reference = 0, $referral ) {

		if( empty( $referral->context ) || 'woocommerce' != $referral->context ) {

			return $reference;

		}

		$url = get_edit_post_link( $reference );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}

	/**
	 * Shows the affiliate drop down on the discount edit / add screens
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function coupon_option() {

		global $post;

		add_filter( 'affwp_is_admin_page', '__return_true' );
		affwp_admin_scripts();

		$user_name    = '';
		$user_id      = '';
		$affiliate_id = get_post_meta( $post->ID, 'affwp_discount_affiliate', true );
		if( $affiliate_id ) {
			$user_id      = affwp_get_affiliate_user_id( $affiliate_id );
			$user         = get_userdata( $user_id );
			$user_name    = $user ? $user->user_login : '';
		}
?>
		<p class="form-field affwp-woo-coupon-field">
			<label for="user_name"><?php _e( 'Affiliate Discount?', 'affiliate-wp' ); ?></label>
			<span class="affwp-ajax-search-wrap">
				<span class="affwp-woo-coupon-input-wrap">
					<input type="text" name="user_name" id="user_name" value="<?php echo esc_attr( $user_name ); ?>" class="affwp-user-search" data-affwp-status="active" autocomplete="off" />
				</span>
				<img class="help_tip" data-tip='<?php _e( 'If you would like to connect this discount to an affiliate, enter the name of the affiliate it belongs to.', 'affiliate-wp' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
			</span>
		</p>
<?php
	}

	/**
	 * Stores the affiliate ID in the discounts meta if it is an affiliate's discount
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function store_discount_affiliate( $coupon_id = 0 ) {

		if( empty( $_POST['user_name'] ) ) {

			delete_post_meta( $coupon_id, 'affwp_discount_affiliate' );
			return;

		}

		if( empty( $_POST['user_id'] ) && empty( $_POST['user_name'] ) ) {
			return;
		}

		$data = affiliate_wp()->utils->process_request_data( $_POST, 'user_name' );

		$affiliate_id = affwp_get_affiliate_id( $data['user_id'] );

		update_post_meta( $coupon_id, 'affwp_discount_affiliate', $affiliate_id );
	}

	/**
	 * Retrieve the affiliate ID for the coupon used, if any
	 *
	 * @access  public
	 * @since   1.1
	*/
	private function get_coupon_affiliate_id() {

		$coupons = $this->order->get_used_coupons();

		if ( empty( $coupons ) ) {
			return false;
		}

		foreach ( $coupons as $code ) {

			$coupon       = new WC_Coupon( $code );
			$affiliate_id = get_post_meta( $coupon->id, 'affwp_discount_affiliate', true );

			if ( $affiliate_id ) {

				if ( ! affiliate_wp()->tracking->is_valid_affiliate( $affiliate_id ) ) {
					continue;
				}

				return $affiliate_id;

			}

		}

		return false;
	}

	/**
	 * Retrieves the referral description
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function get_referral_description() {

		$items       = $this->order->get_items();
		$description = array();

		foreach ( $items as $key => $item ) {

			if ( get_post_meta( $item['product_id'], '_affwp_' . $this->context . '_referrals_disabled', true ) ) {
				continue; // Referrals are disabled on this product
			}

			if( ! empty( $item['variation_id'] ) && get_post_meta( $item['variation_id'], '_affwp_' . $this->context . '_referrals_disabled', true ) ) {
				continue; // Referrals are disabled on this variation
			}

			if( ! empty( $item['variation_id'] ) ) {
				$item['name'] .= ' ' . sprintf( __( '(Variation ID %d)', 'affiliate-wp' ), $item['variation_id'] );
			}

			$description[] = $item['name'];

		}

		$description = implode( ', ', $description );

		return $description;

	}

	/**
	 * Retrieves the referral description
	 *
	 * @access  public
	 * @since   1.1
	*/
	public function get_sell_referral_description( $item ) {

        if( ! empty( $item['variation_id'] ) ) {
            $item['name'] .= ' ' . sprintf( __( '(Variation ID %d)', 'affiliate-wp' ), $item['variation_id'] );
        }

        $description[] = $item['name'];

		$description = implode( ', ', $description );

		return $description;

	}

	/**
	 * Register the product settings tab
	 *
	 * @access  public
	 * @since   1.8.6
	*/
	public function product_tab( $tabs ) {

		$tabs['affiliate_wp'] = array(
			'label'  => __( 'AffiliateWP', 'affiliate-wp' ),
			'target' => 'affwp_product_settings',
			'class'  => array( ),
		);

		return $tabs;

	}

	/**
	 * Adds per-product referral rate settings input fields
	 *
	 * @access  public
	 * @since   1.2
	*/
	public function product_settings() {

		global $post;

?>
		<div id="affwp_product_settings" class="panel woocommerce_options_panel">

			<div class="options_group">
				<p><?php _e( 'Configure affiliate rates for this product', 'affiliate-wp' ); ?></p>
<?php
				woocommerce_wp_text_input( array(
					'id'          => '_affwp_woocommerce_product_rate',
					'label'       => __( 'Affiliate Rate', 'affiliate-wp' ),
					'desc_tip'    => true,
					'description' => __( 'These settings will be used to calculate affiliate earnings per-sale. Leave blank to use default affiliate rates.', 'affiliate-wp' )
				) );
				woocommerce_wp_checkbox( array(
					'id'          => '_affwp_woocommerce_referrals_disabled',
					'label'       => __( 'Disable referrals', 'affiliate-wp' ),
					'description' => __( 'This will prevent orders of this product from generating referral commissions for affiliates.', 'affiliate-wp' ),
					'cbvalue'     => 1
				) );
                woocommerce_wp_text_input( array(
                    'id'          => '_affwp_woocommerce_product_seller',
                    'label'       => __( 'Seller user', 'affiliate-wp' ),
                    'desc_tip'    => true,
                    'description' => __( 'This setting will be used to create seller earnings. If there is no seller for this product then leave this field blank', 'affiliate-wp' )
                ) );
				woocommerce_wp_text_input( array(
					'id'          => '_affwp_woocommerce_product_seller_rate',
					'label'       => __( 'Seller Rate', 'affiliate-wp' ),
					'desc_tip'    => true,
					'description' => __( 'These settings will be used to calculate seller earnings per-sale. Leave blank to use default seller rates.', 'affiliate-wp' )
				) );
				woocommerce_wp_checkbox( array(
					'id'          => '_affwp_woocommerce_sell_referrals_disabled',
					'label'       => __( 'Disable sells', 'affiliate-wp' ),
					'description' => __( 'This will prevent orders of this product from generating seller referral commissions for affiliates.', 'affiliate-wp' ),
					'cbvalue'     => 1
				) );

				wp_nonce_field( 'affwp_woo_product_nonce', 'affwp_woo_product_nonce' );
?>
			</div>
		</div>
<?php

	}

	/**
	 * Adds per-product variation referral rate settings input fields
	 *
	 * @access  public
	 * @since   1.9
	*/
	public function variation_settings( $loop, $variation_data, $variation ) {

		$rate = $this->get_product_rate( $variation->ID );
		$seller_rate = $this->get_product_sell_rate( $variation->ID );

		$disabled = get_post_meta( $variation->ID, '_affwp_woocommerce_referrals_disabled', true );
		$sell_disabled = get_post_meta( $variation->ID, '_affwp_woocommerce_sell_referrals_disabled', true );
?>
		<div id="affwp_product_variation_settings">

			<div class="form-row form-row-full">
				<p><?php _e( 'Configure affiliate rates for this product variation', 'affiliate-wp' ); ?></p>
				<p class="form-row form-row-full options">
					<label><?php echo __( 'Referral Rate', 'affiliate-wp' ); ?></label>
					<input type="text" size="5" name="_affwp_woocommerce_variation_rates[<?php echo $variation->ID; ?>]" value="<?php echo esc_attr( $rate ); ?>" class="wc_input_price" placeholder="<?php esc_attr_e( 'Referral rate (optional)', 'affiliate-wp' ); ?>" />
					<label>
						<input type="checkbox" class="checkbox" name="_affwp_woocommerce_variation_referrals_disabled[<?php echo $variation->ID; ?>]" <?php checked( $disabled, true ); ?> /> <?php _e( 'Disable referrals for this product variation', 'affiliate-wp' ); ?>
					</label>
				</p>
			</div>
			<div class="form-row form-row-full">
				<p><?php _e( 'Configure seller rates for this product variation', 'affiliate-wp' ); ?></p>
				<p class="form-row form-row-full options">
					<label><?php echo __( 'Referral Seller Rate', 'affiliate-wp' ); ?></label>
					<input type="text" size="5" name="_affwp_woocommerce_variation_seller_rates[<?php echo $variation->ID; ?>]" value="<?php echo esc_attr( $seller_rate ); ?>" class="wc_input_price" placeholder="<?php esc_attr_e( 'Seller referral rate (optional)', 'affiliate-wp' ); ?>" />
					<label>
						<input type="checkbox" class="checkbox" name="_affwp_woocommerce_variation_sell_disabled[<?php echo $variation->ID; ?>]" <?php checked( $sell_disabled, true ); ?> /> <?php _e( 'Disable seller referrals for this product variation', 'affiliate-wp' ); ?>
					</label>
				</p>
			</div>
		</div>
<?php

	}

	/**
	 * Saves per-product referral rate settings input fields
	 *
	 * @access  public
	 * @since   1.2
	*/
	public function save_meta( $post_id = 0 ) {

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		if( empty( $_POST['affwp_woo_product_nonce'] ) || ! wp_verify_nonce( $_POST['affwp_woo_product_nonce'], 'affwp_woo_product_nonce' ) ) {
			return $post_id;
		}

		$post = get_post( $post_id );

		if( ! $post ) {
			return $post_id;
		}

		// Check post type is product
		if ( 'product' != $post->post_type ) {
			return $post_id;
		}

		// Check user permission
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if( isset( $_POST['_affwp_' . $this->context . '_product_seller'] ) ) {

			$seller = sanitize_text_field( $_POST['_affwp_' . $this->context . '_product_seller'] );

            $user = get_user_by('login', $seller);

            if ( !empty( $user ) ) {
                $affiliate = affiliate_wp()->affiliates->get_by( 'user_id', $user->ID );
                if ( $affiliate ) {
                    update_post_meta( $post_id, '_affwp_' . $this->context . '_product_seller', $seller );
                    update_post_meta( $post_id, '_affwp_' . $this->context . '_product_seller_id', $affiliate->affiliate_id );
                }
            }
		} else {
			delete_post_meta( $post_id, '_affwp_' . $this->context . '_product_seller' );
			delete_post_meta( $post_id, '_affwp_' . $this->context . '_product_seller_id' );
		}

		if( ! empty( $_POST['_affwp_' . $this->context . '_product_rate'] ) ) {

			$rate = sanitize_text_field( $_POST['_affwp_' . $this->context . '_product_rate'] );
			update_post_meta( $post_id, '_affwp_' . $this->context . '_product_rate', $rate );

		} else {

			delete_post_meta( $post_id, '_affwp_' . $this->context . '_product_rate' );

		}

		if( ! empty( $_POST['_affwp_' . $this->context . '_product_seller_rate'] ) ) {

			$seller_rate = sanitize_text_field( $_POST['_affwp_' . $this->context . '_product_seller_rate'] );
			update_post_meta( $post_id, '_affwp_' . $this->context . '_product_seller_rate', $seller_rate );

		} else {

			delete_post_meta( $post_id, '_affwp_' . $this->context . '_product_seller_rate' );

		}

		$this->save_variation_data( $post_id );

		if( isset( $_POST['_affwp_' . $this->context . '_referrals_disabled'] ) ) {

			update_post_meta( $post_id, '_affwp_' . $this->context . '_referrals_disabled', 1 );

		} else {

			delete_post_meta( $post_id, '_affwp_' . $this->context . '_referrals_disabled' );

		}

		if( isset( $_POST['_affwp_' . $this->context . '_sell_referrals_disabled'] ) ) {

			update_post_meta( $post_id, '_affwp_' . $this->context . '_sell_referrals_disabled', 1 );

		} else {

			delete_post_meta( $post_id, '_affwp_' . $this->context . '_sell_referrals_disabled' );

		}

	}

	/**
	 * Saves variation data
	 *
	 * @access  public
	 * @since   1.9
	*/
	public function save_variation_data( $product_id = 0 ) {

		if( ! empty( $_POST['variable_post_id'] ) && is_array( $_POST['variable_post_id'] ) ) {

			foreach( $_POST['variable_post_id'] as $variation_id ) {

				$variation_id = absint( $variation_id );

				if( ! empty( $_POST['_affwp_woocommerce_variation_rates'] ) && ! empty( $_POST['_affwp_woocommerce_variation_rates'][ $variation_id ] ) ) {

					$rate = sanitize_text_field( $_POST['_affwp_woocommerce_variation_rates'][ $variation_id ] );
					update_post_meta( $variation_id, '_affwp_' . $this->context . '_product_rate', $rate );

				} else {

					delete_post_meta( $variation_id, '_affwp_' . $this->context . '_product_rate' );

				}

				if( ! empty( $_POST['_affwp_woocommerce_variation_referrals_disabled'] ) && ! empty( $_POST['_affwp_woocommerce_variation_referrals_disabled'][ $variation_id ] ) ) {

					update_post_meta( $variation_id, '_affwp_' . $this->context . '_referrals_disabled', 1 );

				} else {

					delete_post_meta( $variation_id, '_affwp_' . $this->context . '_referrals_disabled' );

				}

				if( ! empty( $_POST['_affwp_woocommerce_variation_seller_rates'] ) && ! empty( $_POST['_affwp_woocommerce_variation_seller_rates'][ $variation_id ] ) ) {

					$rate = sanitize_text_field( $_POST['_affwp_woocommerce_variation_seller_rates'][ $variation_id ] );
					update_post_meta( $variation_id, '_affwp_' . $this->context . '_product_seller_rate', $rate );

				} else {

					delete_post_meta( $variation_id, '_affwp_' . $this->context . '_product_seller_rate' );

				}

				if( ! empty( $_POST['_affwp_woocommerce_variation_sell_disabled'] ) && ! empty( $_POST['_affwp_woocommerce_variation_sell_disabled'][ $variation_id ] ) ) {

					update_post_meta( $variation_id, '_affwp_' . $this->context . '_sell_referrals_disabled', 1 );

				} else {

					delete_post_meta( $variation_id, '_affwp_' . $this->context . '_sell_referrals_disabled' );

				}

			}

		}

	}

	/**
	 * Prevent WooCommerce from fixing rewrite rules when AffiliateWP runs affiliate_wp()->rewrites->flush_rewrites()
	 *
	 * See https://github.com/affiliatewp/AffiliateWP/issues/919
	 *
	 * @access  public
	 * @since   1.7.8
	*/
	public function skip_generate_rewrites() {
		remove_filter( 'rewrite_rules_array', 'wc_fix_rewrite_rules', 10 );
	}

	/**
	 * Forces the WC shop page to recognize it as such, even when accessed via a referral URL.
	 *
	 * @since 1.8
	 * @access public
	 *
	 * @param WP_Query $query Current query.
	 */
	public function force_shop_page_for_referrals( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( function_exists( 'wc_get_page_id' ) ) {
			$ref = affiliate_wp()->tracking->get_referral_var();

			if ( ( isset( $query->queried_object_id ) && wc_get_page_id( 'shop' ) == $query->queried_object_id )
				&& ! empty( $query->query_vars[ $ref ] )
			) {
				// Force WC to recognize that this is the shop page.
				$GLOBALS['wp_rewrite']->use_verbose_page_rules = true;
			}
		}
	}

	/**
	 * Strips pretty referral bits from pagination links on the Shop page.
	 *
	 * @since 1.8
	 * @since 1.8.1 Skipped for product taxonomies and searches
	 * @deprecated 1.8.3
	 * @see Affiliate_WP_Tracking::strip_referral_from_paged_urls()
	 * @access public
	 *
	 * @param string $link Pagination link.
	 * @return string (Maybe) filtered pagination link.
	 */
	public function strip_referral_from_paged_urls( $link ) {
		return affiliate_wp()->tracking->strip_referral_from_paged_urls( $link );
	}

    public function seller_product_visited()
    {
        global $post;

        if ( !is_product() ) return;

        $product_id = $post->ID;
		$affiliate_id = get_post_meta( $product_id, '_affwp_' . $this->context . '_product_seller_id', true );
        if ( !$affiliate_id ) return;

		$is_valid = affiliate_wp()->tracking->is_valid_affiliate( $affiliate_id );
        $referrer = isset( $_REQUEST['referrer'] ) ? $_REQUEST['referrer'] : '';

		if ( $is_valid ) {

			if( ! affwp_is_url_banned( $referrer ) ) {

                if ( isset($_COOKIE['affwp_sell_visits']) ) {
                    $cookie = @unserialize( $_COOKIE['affwp_sell_visits'] );
                }

                if ( !isset($cookie) || !is_array($cookie) ) $cookie = [];


                if ( !isset( $cookie[$product_id] ) ) {

                    // Store the visit in the DB
                    $visit_id = affiliate_wp()->visits->add( array(
                        'affiliate_id' => $affiliate_id,
                        'ip'           => affiliate_wp()->tracking->get_ip(),
                        'url'          => home_url( add_query_arg( NULL, NULL ) ), // Sorta hack
                        'referrer'     => $referrer,
                        'sell_id'      => 1,
                    ) );

                    if ( $visit_id ) {
                        $cookie[$product_id] = $visit_id;
                        setcookie('affwp_sell_visits', serialize( $cookie ), 0, COOKIEPATH, COOKIE_DOMAIN);
                    }
                }

				affiliate_wp()->utils->log( sprintf( 'Sell visit #%d recorded for affiliate #%d in seller_product_visited()', $visit_id, $affiliate_id ) );
			} else {
				affiliate_wp()->utils->log( sprintf( '"%s" is a banned URL. A sell visit was not recorded.', $referrer ) );
			}

		} elseif ( ! $is_valid ) {

			affiliate_wp()->utils->log( 'Invalid affiliate ID during seller_product_visited()' );

		} else {

			affiliate_wp()->utils->log( 'Affiliate ID missing during seller_product_visited()' );

		}

    }

}
new Affiliate_WP_WooCommerce;
