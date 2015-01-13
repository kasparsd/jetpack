<?php
/**
 * Module Name: Protect
 * Module Description: Adds brute force protection to your login page. Formerly BruteProtect
 * Sort Order: 1
 * First Introduced: 3.4
 * Requires Connection: Yes
 * Auto Activate: Yes
 */

class Jetpack_Protect_Module {

	private static $__instance = null;
	public $api_key;
	public $api_key_error;

	/**
	 * Singleton implementation
	 *
	 * @return object
	 */
	public static function instance() {
		if ( ! is_a( self::$__instance, 'Jetpack_Protect_Module' ) )
			self::$__instance = new Jetpack_Protect_Module();

		return self::$__instance;
	}

	/**
	 * Registers actions
	 */
	private function __construct() {
		add_action( 'jetpack_activate_module_protect', array( $this, 'on_activation' ) );
		add_action( 'jetpack_modules_loaded', array( $this, 'modules_loaded' ) );
	}

	/**
	 * Set up the Protect configuration page
	 */
	public function modules_loaded() {
		Jetpack::enable_module_configurable( __FILE__ );
		Jetpack::module_configuration_load( __FILE__, array( $this, 'configuration_load' ) );
		Jetpack::module_configuration_screen( __FILE__, array( $this, 'configuration_screen' ) );
	}

	/**
	 * Get key or delete key
	 */
	public function configuration_load() {

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'remove_protect_key' && wp_verify_nonce( $_POST['_wpnonce'], 'jetpack-protect' ) ) {
			Jetpack::state( 'message', 'module_configured' );
			delete_site_option( 'jetpack_protect_key' );
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'get_protect_key' && wp_verify_nonce( $_POST['_wpnonce'], 'jetpack-protect' ) ) {
			$this->get_protect_key();
		}

		$this->api_key = get_site_option( 'jetpack_protect_key', false );
	}

	/**
	 * Prints the configuration screen
	 */
	public function configuration_screen() {
		?>
		<div class="narrow">
			<?php if ( ! $this->api_key ) : ?>

				<?php if( ! empty( $this->api_key_error ) ) : ?>
					<p class="error"><?php echo $this->api_key_error; ?></p>
				<?php endif; ?>

				<p><?php _e( 'An API key is needed for Jetpack Protect', 'jetpack' ); ?></p>
				<form method="post">
					<?php wp_nonce_field( 'jetpack-protect' ); ?>
					<input type='hidden' name='action' value='get_protect_key' />
					<p class="submit"><input type='submit' class='button-primary' value='<?php echo esc_attr( __( 'Get an API Key', 'jetpack' ) ); ?>' /></p>
				</form>

			<?php else : ?>

				<p><?php _e( 'Protect is set-up and running!', 'jetpack' ); ?></p>
				<p>Key: <?php echo $this->api_key; ?></p>
				<form method="post">
					<?php wp_nonce_field( 'jetpack-protect' ); ?>
					<input type='hidden' name='action' value='remove_protect_key' />
					<p class="submit"><input type='submit' class='button-primary' value='<?php echo esc_attr( __( 'Remove Key', 'jetpack' ) ); ?>' /></p>
				</form>

			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * On module activation, try to get an api key
	 */
	public function on_activation() {
		$this->get_protect_key();
	}

	/**
	 * Request an api key from wordpress.com
	 *
	 * @return bool | string
	 */
	public function get_protect_key() {

		$protect_blog_id = Jetpack_Protect_Module::get_main_blog_jetpack_id();

		// if we can't find the the blog id, that means we are on multisite, and the main site never connected
		// the protect api key is linked to the main blog id - instruct the user to connect their main blog
		if ( ! $protect_blog_id ) {
			$this->api_key_error = __( 'Your main blog is not connected to WordPress.com. Please connect to get an API key.', 'jetpack' );
			return false;
		}

		$request = array(
			'jetpack_blog_id'           => $protect_blog_id,
			'bruteprotect_api_key'      => get_site_option( 'bruteprotect_api_key' ),
			'multisite'                 => '0',
		);

		// send the number of blogs on the network if we are on multisite
		if ( is_multisite() ) {
			global $wpdb;
			$request['multisite'] = $wpdb->get_var( "SELECT COUNT(blog_id) as c FROM $wpdb->blogs WHERE spam = '0' AND deleted = '0' and archived = '0'" );
		}

		// request the key
		Jetpack::load_xml_rpc_client();
		$xml = new Jetpack_IXR_Client( array(
			'user_id' => get_current_user_id()
		) );
		$xml->query( 'jetpack.protect.requestKey', $request );

		// hmm, can't talk to wordpress.com
		if ( $xml->isError() ) {
			$code = $xml->getErrorCode();
			$message = $xml->getErrorMessage();
			$this->api_key_error = __( 'Error connecting to WordPress.com. Code: ' . $code . ', '. $message, 'jetpack');
			return false;
		}

		$response = $xml->getResponse();

		// hmm. can't talk to the protect servers ( api.bruteprotect.com )
		if( ! isset( $response['data'] ) ) {
			$this->api_key_error = __( 'No reply from Protect servers', 'jetpack' );
			return false;
		}

		// there was an issue generating the key
		if (  empty( $response['success'] ) ) {
			$this->api_key_error = $response['data'];
			return false;
		}

		// hey, we did it!
		$key = $response['data'];
		update_site_option( 'jetpack_protect_key', $key );
		return $key;
	}

	/**
	 * Get jetpack blog id, or the jetpack blog id of the main blog in the network
	 *
	 * @return int
	 */
	public function get_main_blog_jetpack_id() {
		$id = Jetpack::get_option( 'id' );
		if ( is_multisite() && get_current_blog_id() != 1 ) {
			switch_to_blog( 1 );
			$id = Jetpack::get_option( 'id', false );
			restore_current_blog();
		}
		return $id;
	}

}

Jetpack_Protect_Module::instance();