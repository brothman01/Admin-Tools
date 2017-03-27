<?php
/*
 * Plugin Name: WP Monitor
 * Description: Notify user when updates to WordPress are needed.
 * Version:     1.0.0
 * Author:      Ben Rothman
 * Slug:				wp-monitor
 * Author URI:  http://www.BenRothman.org
 * License:     GPL-2.0+
 */

class WPMonitor {

	public static $updates;

	public static $options;

	public static $grades;

	public function __construct() {

		self::$options = get_option( 'wpm_options', array(

			'wpm_how_often'	=> __( 'daily', 'wp-monitor' ),

			'wpm_send_email' => true,

			'wpm_check_plugins' => true,

			'wpm_check_themes' => true,

			'wpm_check_wordpress' => true,

			'wpm_check_php' => true,

			'wpm_check_ssl' => true,

		) );

		add_action( 'init', array( $this, 'wpm_check_for_updates' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ) );

		include_once( plugin_dir_path( __FILE__ ) . 'PHPVersioner.php' );

		include_once( plugin_dir_path( __FILE__ ) . 'settings.php' );

	}

	public function init() {

		add_action( 'admin_enqueue_scripts', array( $this, 'wpm_enqueue_admin_styles' ) );

		add_action( 'admin_footer', array( $this, 'wpm_dashboard_widget' ) );

	}



	function wpm_dashboard_widget() {

		if ( get_current_screen()->base !== 'dashboard' ) {

			return;

		}
	?>

	<div id="custom-id" class="welcome-panel" style="display: none;">

		<?php $this->wpm_dashboard_callback(); ?>

	</div>

	<script>
		jQuery(document).ready(function($) {

			$('#welcome-panel').after($('#custom-id').show());

		});
	</script>

<?php }



	public function wpm_check_for_updates() {

		if ( ! current_user_can( 'install_plugins' ) ) {

			return;

		}

			$update_data = wp_get_update_data();

			$php_info = PHPVersioner::$info;

			$current_php_version = $this->php_version( 2 );

			$user_version_info = $php_info[ $current_php_version ];

			$user_version_supported_until = $user_version_info['supported_until'];

			$current_date = date_create();

			$php_action = ( $user_version_supported_until < date_timestamp_get( $current_date ) ) ? 'Upgrade Now' : 'Up To Date';

		if ( 'Upgrade Now' == $php_action ) {

				$php_update = 1;

		} else {

				$php_update = 0;

		}

			$user_version_supported_until = gmdate( 'm-d-Y', $user_version_supported_until );

			self::$updates = array(

				'plugins'	=> $update_data['counts']['plugins'],

				'themes'	=> $update_data['counts']['themes'],

				'WordPress'	=> $update_data['counts']['wordpress'],

				'PHP_supported_until' => $user_version_supported_until,

				'php_action'	=> $php_action,

				'PHP_update'	=> $php_update,

				'PHP_warning' => $user_version_info['supported_until'],

				'SSL'					=> is_ssl() ? 1 : 0,

			);

			update_option( 'wpm_update_info', self::$updates );

	}

	public function php_version( $parts ) {

		if ( 2 == $parts ) {

			return (string) substr( (string) phpversion(), 0, 3 );

		}

			return (string) phpversion();
	}




	function list_last_logins() {

				$all_users = get_users( 'blog_id=1' );

		foreach ( $all_users as $user ) {

						$response = wp_remote_get( 'http://www.ip-api.com/json/' . get_user_meta( $user->ID, 'last_ip', true ) );

						$body = wp_remote_retrieve_body( $response );

						$data = json_decode( $body, true );

						// Check for error
						if ( is_wp_error( $body ) || 'fail' === $data['status'] ) {

							$data = array( 'city' => 'Address Doesn\'t exist', 'region' => '' , 'country' => '' );

						}


						echo '<tr>' .

						'<th>' . $user->user_login . '</th>' .

						'<th>' . get_user_meta( $user->ID, 'last_login_timestamp', true ) . '</th>' .

						'<th>' . get_user_meta( $user->ID, 'last_ip', true ) . '</th>' .

						'<th>' . $data['city'] . ' ' . $data['region'] . ' ' . $data['country'] . '</th>' .

						'</tr>';

		}

	}

	public function wpm_dashboard_callback() {

			echo '<div id="dashboard_main">';

				echo '<div class="twothirds">

				<h1 style="text-align: center; background: #F9F9F9;">Site Status:</h1>';

							echo '<div id="first_gauge_row" style="width: 100%; float: left; text-align: left;">';

								echo '<h3>Updates</h3>';

										echo $this->gauge_cell( __( 'Plugins',  'wp-monitor' ), 'g1', sizeof( get_plugins() ) - self::$updates['plugins'], sizeof( get_plugins() ) );

										echo $this->gauge_cell( __( 'Themes',  'wp-monitor' ), 'g2', sizeof( wp_get_themes() ) - self::$updates['themes'], sizeof( wp_get_themes() ) );

										echo $this->indicator_cell( __( 'WordPress Core',  'wp-monitor' ), 'wordpress', self::$updates['WordPress'] );

										echo $this->php_cell( __( 'PHP',  'wp-monitor' ) );

							echo '</div>';

							echo '<div id="second_gauge_row" style="width: 100%; background: #F9F9F9; float: left;">';

								echo '<h3>Summary</h3>';

										echo $this->ssl_cell( __( 'SSL',  'wp-monitor' ) );

										$final_grade = ( intval( self::$updates['plugins'] ) + intval( self::$updates['themes'] ) + intval( self::$updates['WordPress'] ) + self::$updates['PHP_update'] );

										echo $this->counter_cell( __( 'Total Updates',  'wp-monitor' ), 'total' );

										echo $this->counter_cell( __( 'Overall Grade',  'wp-monitor' ), 'grade' );

							echo '</div>';

							echo '<div id="third_gauge_row">

							</div>';

						echo '</div>';

						echo '<div class="tablesthird" >';

						echo '
						<div id="tabs">
						  <ul>
						    <li><a href="#tabs-1">' . __( 'Variables',  'wp-monitor' ) . '</a></li>
						    <li><a href="#tabs-2">' . __( 'User Logins',  'wp-monitor' ) . '</a></li>
						  </ul>
						  <div id="tabs-1">';

							echo '<table class="wp-list-table widefat fixed striped wpm_table">';

								echo '<thead>';

									echo '<tr>
										<th>' . __( 'Variable',  'wp-monitor' ) . '</th>
										<th>' . __( 'Value',  'wp-monitor' ) . '</th>
									</tr>';

									echo '</thead>';

								echo $this->variable_table();

					echo '</table>';

						  echo '</div>
						  <div id="tabs-2">

									<table class="wp-list-table widefat fixed striped wpm_table">

										<thead>
											<tr>
												<th>' . __( 'Username',  'wp-monitor' ) . '</th>
												<th>' . __( 'Last Login Date/Time',  'wp-monitor' ) . '</th>
												<th>' . __( 'Last IP Used',  'wp-monitor' ) . '</th>
												<th>' . __( 'Location',  'wp-monitor' ) . '</th>
											</tr>
										</thead>';

								 $this->list_last_logins();

							echo '</table>
						</div>';

					echo '</div>


						</div>

					</div>';

	}

	public function gauge_cell( $title, $gauge_class, $value, $max ) {

				return '<div class="onequarter cell">

					<div id="' . $gauge_class . '" class="gauge"></div>
						<script>
							var g1;
							document.addEventListener( "DOMContentLoaded", function( event ) {
								var g1 = new JustGage( {
									id: "' . $gauge_class . '",
									value: ' . $value . ',
									min: 0,
									max: ' . $max . ',
									title: "' . $title . '",
									} );
							} );
						</script>

				</div>';

	}

	public function indicator_cell( $title, $class_prefix, $setting ) {

				return '<div class="onequarter cell">
				<h3>' . $title . '</h3>

					<div class="gauge indicator">

						<div class="inner_indicator">

							<div class="indicator_light" id="' . $class_prefix . '_red_light">&nbsp;</div>

							<div class="indicator_light" id="' . $class_prefix . '_green_light">&nbsp;</div>

						</div>

					</div>

								</div>';

	}

	public function php_cell( $title ) {

						return '<div class="onequarter cell" style="text-align: center;">

						<h3 style="margin-bottom: 5px;">' . $title . '</h3>

							<p>Running Version: ' . $this->php_version( 2 ) . '</p>

							<p>Supported Until: ' . self::$updates['PHP_supported_until'] . '</p>

							<input id="php_action_field" type="text" maxlength="14" size="14" style="text-align: center; font-style: bold;" readonly />

							<script>

								document.addEventListener( "DOMContentLoaded", function( event ) {

									var php_action_field = document.getElementById("php_action_field");


									setTimeout(function(){

											if ("' . self::$updates['php_action'] . '" == "Up To Date") {

												php_action_field.style.background = "#00CB25";

												php_action_field.value = "' . self::$updates['php_action'] . '";

											} else {

												php_action_field.style.background = "#FF0000";

												php_action_field.style.color = "white";

												php_action_field.value = "' . self::$updates['php_action'] . '";

											}

									}, 1000);

								} );
							</script>

					</div>';

	}

	public function ssl_cell( $title ) {

				return '<div class="onethird cell">

				<h3>' . $title . '</h3>

				<div class="gauge indicator">

					<div class="inner_indicator">

						<div class="indicator_light" id="ssl_red_light">&nbsp;</div>

						<div class="indicator_light" id="ssl_green_light">&nbsp;</div>

					</div>

				</div>

			</div>';

	}


	public function counter_cell( $title, $prefix ) {

				return '<div class="onethird cell">

				<h3>' . $title . '</h3>

					<div class="gauge overall">

						<span class="counter" id="' . $prefix . '_counter">' . '&nbsp;' . '</span>' .

					'</div>

				</div>';

	}

	public function calculate_grade() {

				$grades = array(

						'Plugins' => ( ( ( sizeof( get_plugins() ) - self::$updates['plugins'] ) / sizeof( get_plugins() ) ) * 100 ),

						'Themes' => ( ( ( sizeof( wp_get_themes() ) - self::$updates['themes'] ) / sizeof( wp_get_themes() ) ) * 100 ),

						'WordPress' => ( 0 == self::$updates['WordPress'] ) ? 100 : 50,

						'PHP' => ( 0 == self::$updates['PHP_update'] ) ? 100 : 50,

						'SSL'	=> self::$updates['SSL'] ? 100 : 50,

				);

				$subtotal = $grades['Plugins'] + $grades['Themes'] + $grades['WordPress'] + $grades['PHP'] + $grades['SSL'];

				$subtotal = $subtotal / 5;

				$subtotal = round( $subtotal, 0 );

				return $subtotal;
	}



	public function variable_table() {

				$all_vars = '';



		if ( ( get_option( 'blog_public' ) == 0 ) || empty( get_option( 'blog_public' ) ) ) {

					$blog_public = 'true';

		} else {

					$blog_public = 'false';

		}

				$variables = array(

					__( 'WP Version', 'wp-monitor' )	=> get_bloginfo( 'version' ),

					__( 'PHP Version', 'wp-monitor' )	=> phpversion(),

					__( 'Name', 'wp-monitor' )				=> get_bloginfo( 'name' ),

					__( 'URL', 'wp-monitor' )					=> get_bloginfo( 'url' ),

					__( 'Charset', 'wp-monitor' )			=> get_bloginfo( 'charset' ),

					__( 'Admin Email', 'wp-monitor' )	=> get_bloginfo( 'admin_email' ),

					__( 'Language', 'wp-monitor' )		=> get_bloginfo( 'language' ),

					__( 'Stylesheet Directory', 'wp-monitor' )	=> get_bloginfo( 'stylesheet_directory' ),


					__( 'Front Page Displays', 'wp-monitor' )			=> get_option( 'show_on_front' ),

					__( 'Posts Per Page', 'wp-monitor' )					=> get_option( 'posts_per_page' ),

					__( 'Atom URL', 'wp-monitor' )								=> get_bloginfo( 'atom_url' ),

					__( 'SMTP', 'wp-monitor' )										=> ini_get( 'SMTP' ),

					__( 'Discourage Search Engines', 'wp-monitor' )	=> $blog_public,

					__( 'PHP Memory Limit', 'wp-monitor' )				=> ini_get( 'memory_limit' ),

				);

		foreach ( $variables as $key => $value ) {

					$all_vars = $all_vars .
					'<tr>
						<th>' . $key . '</th>
						<th>' . $value . '</th>
					</tr>';

		}

				return $all_vars;

	}

	public function ssl_check() {

		return is_ssl() ? 0 : 1;

	}

	public function wpm_general_section_callback() {

				echo 'Edit the settings for the plugin here.';

	}




	public function wpm_enqueue_admin_styles( $hook ) {

		if ( 'index.php' !== $hook ) {

			return;

		}

		wp_register_style( 'wpm_admin_css',  plugin_dir_url( __FILE__ ) . '/library/css/admin-style.css', false, '1.0.0' );
		wp_enqueue_style( 'wpm_admin_css' );

		wp_register_script( 'wpm_indicator', plugin_dir_url( __FILE__ ) . '/library/js/other.js', array( 'jquery' ), '1.0.0' );
		wp_localize_script('wpm_indicator', 'wpm_data2', array(

			'wordpress'	=> intval( self::$updates['WordPress'] ),

			'ssl'	=> self::$updates['SSL'],

		) );
		wp_enqueue_script( 'wpm_indicator' );

		wp_register_script( 'wpm_counter', plugin_dir_url( __FILE__ ) . 'library/js/renamed.js', array( 'jquery' ), '1.0.0' );
		wp_localize_script( 'wpm_counter', 'wpm_data_counter', array(

			'total'	=> self::$updates['plugins'] + self::$updates['themes'] + self::$updates['WordPress'] + self::$updates['PHP_update'],

			'grade'	=> (integer) $this->calculate_grade(),

		) );
		wp_enqueue_script( 'wpm_counter' );

		wp_register_script( 'tabs-init',  plugin_dir_url( __FILE__ ) . '/library/js/tabs-init.jquery.js', array( 'jquery-ui-tabs' ) );
		wp_enqueue_script( 'tabs-init' );

		wp_register_style( 'wpm_tabs_css',  'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.min.css', false, '1.0.0' );
		wp_enqueue_style( 'wpm_tabs_css' );

		/* Gauges */
		wp_register_style( 'wpm_justgage_css',  plugin_dir_url( __FILE__ ) . '/library/css/justgage.css', false, '1.0.0' );
		wp_enqueue_style( 'wpm_justgage_css' );

		wp_register_script( 'wpm_raphael',  plugin_dir_url( __FILE__ ) . '/library/js/raphael-2.1.4.min.js' );
		wp_enqueue_script( 'wpm_raphael' );

		wp_register_script( 'wpm_justgage',  plugin_dir_url( __FILE__ ) . '/library/js/justgage.js' );
		wp_enqueue_script( 'wpm_justgage' );

	}



}

$wp_monitor = new WPMonitor();
