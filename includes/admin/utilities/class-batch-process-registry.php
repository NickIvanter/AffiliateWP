<?php
namespace AffWP\Utils\Batch_Process;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements a batch process registry class.
 *
 * @since 2.0
 */
class Registry {

	/**
	 * Batches processes ID registry.
	 *
	 * @access private
	 * @since  2.0
	 * @var    array
	 */
	private $batch_ids = array();

	/**
	 * Initializes the batch registry.
	 *
	 * @access public
	 * @since  2.0
	 */
	public function init() {

		$this->includes();
		$this->register_core_processes();

		/**
		 * Fires during instantiation of the batch processing registry.
		 *
		 * @since 2.0
		 *
		 * @param \AffWP\Utils\Batch_Process\Registry $this Registry instance.
		 */
		do_action( 'affwp_batch_process_init', $this );
	}

	/**
	 * Brings in core process files.
	 *
	 * @access public
	 * @since  2.0
	 */
	public function includes() {
		// Batch processing bootstrap.
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/interfaces/interface-batch-process.php';
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/interfaces/interface-batch-process-with-prefetch.php';
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/utilities/class-batch-process.php';

		// Exporters bootstrap.
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/interfaces/interface-base-exporter.php';
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/interfaces/interface-csv-exporter.php';
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-batch-export.php';
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-batch-export-csv.php';
	}

	/**
	 * Registers core batch processes.
	 *
	 * @access protected
	 * @since  2.0
	 */
	protected function register_core_processes() {
		//
		// Migrations
		//

		// User Migration.
		$this->register_process( 'migrate-users', array(
			'class' => 'AffWP\Utils\Batch_Process\Migrate_Users',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/utilities/class-batch-migrate-users.php',
		) );

		// WP Affiliate Migration.
		$this->register_process( 'migrate-wp-affiliate', array(
			'class' => 'AffWP\Utils\Batch_Process\Migrate_WP_Affiliate',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/utilities/class-batch-migrate-wp-affiliate.php',
		) );
//
//		// Affiliates Pro Migration.
//		$this->register_process( 'migrate-affiliates-pro', array(
//			'class' => 'Affiliate_WP_Migrate_Affiliates_Pro',
//		) );

		//
		// Exporters
		//

		// Export Affiliates.
		$this->register_process( 'export-affiliates', array(
			'class' => 'AffWP\Utils\Batch_Process\Export_Affiliates',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-batch-export-affiliates.php',
		) );

		// Export Referrals.
		$this->register_process( 'export-referrals', array(
			'class' => 'AffWP\Utils\Batch_Process\Export_Referrals',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-batch-export-referrals.php',
		) );

		// Export Payouts.
		$this->register_process( 'export-payouts', array(
			'class' => 'AffWP\Utils\Batch_Process\Export_Payouts',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-batch-export-payouts.php',
		) );

		//
		// Other stuff.
		//

		// Generate Payout Log.
		$this->register_process( 'generate-payouts', array(
			'class' => 'AffWP\Utils\Batch_Process\Generate_Payouts',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-batch-generate-payouts.php',
		) );

		// Recount all affiliate stats.
		$this->register_process( 'recount-affiliate-stats', array(
			'class' => 'AffWP\Utils\Batch_Process\Recount_Affiliate_Stats',
			'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-batch-recount-affiliate-stats.php',
		) );
	}

	/**
	 * Registers a new batch process.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @param string $batch_id     Unique batch process ID.
	 * @param array  $process_args {
	 *     Arguments for registering a new batch process.
	 *
	 *     @type string $class Batch processor class to use.
	 *     @type string $file  File containing the batch processor class.
	 * }
	 * @return \WP_Error|true True on successful registration, otherwise a WP_Error object.
	 */
	public function register_process( $batch_id, $process_args ) {
		$process_args = wp_parse_args( $process_args,  array_fill_keys( array( 'class', 'file' ), '' ) );

		if ( empty( $process_args['class'] ) ) {
			return new \WP_Error( 'invalid_batch_class', __( 'A batch process class must be specified.', 'affiliate-wp' ) );
		}

		if ( empty( $process_args['file'] ) ) {
			return new \WP_Error( 'missing_batch_class_file', __( 'No batch class handler file has been supplied.', 'affiliate-wp' ) );
		}

		// 2 if Windows path.
		if ( ! in_array( validate_file( $process_args['file'] ), array( 0, 2 ), true ) ) {
			return new \WP_Error( 'invalid_batch_class_file', __( 'An invalid batch class handler file has been supplied.', 'affiliate-wp' ) );
		}

		return $this->add_process( $batch_id, $process_args );
	}

	/**
	 * Adds a batch process to the registry.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @param int    $batch_id   Batch process ID.
	 * @param array  $attributes {
	 *     Batch attributes.
	 *
	 *     @type string $class Batch process handler class.
	 *     @type string $file  Batch process handler class file.
	 * }
	 * @return true Always true.
	 */
	private function add_process( $batch_id, $attributes ) {
		foreach ( $attributes as $attribute => $value ) {
			$this->batch_ids[ $batch_id ][ $attribute ] = $value;
		}

		return true;
	}

	/**
	 * Removes a batch process from the registry by ID.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @param string $batch_id Batch process ID.
	 */
	public function remove_process( $batch_id ) {
		unset( $this->batch_ids[ $batch_id ] );
	}

	/**
	 * Retrieves a batch process and its associated attributes.
	 *
	 * @access public
	 * @since  2.0
	 *
	 * @param string $batch_id Batch process ID.
	 * @return array|false Array of attributes for the batch process if registered, otherwise false.
	 */
	public function get( $batch_id ) {
		if ( array_key_exists( $batch_id, $this->batch_ids ) ) {
			return $this->batch_ids[ $batch_id ];
		}
		return false;
	}

}
