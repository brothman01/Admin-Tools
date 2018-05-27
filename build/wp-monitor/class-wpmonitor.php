<?php
/**
 * Plugin Name: WP Monitor
 * Description: Collects important data from site and displays it on the dashboard
 * Version:     1.1.4
 * Author:      Ben Rothman
 * Slug:        wp-monitor
 * Author URI:  http://www.BenRothman.org
 * License:     GPL-2.0+
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'unauthorized' );
}

/**
 * WP-Monitor.
 *
 * @class    WP Monitor
 * @package  WP Monitor
 * @category Class
 * @author   Ben Rothman
 * @since    1.1.1
 */
class WPMonitor {

	public static $updates;

	public static $options;

	public static $grades;

	public static $highlight_color;

	/**
	 * WPMonitor class constructor
	 *
	 * @since 0.1
	 */
	public function __construct() {

		self::$options = get_option( 'wpm_options', [
			'wpm_how_often'       => __( 'daily', 'wp-monitor' ),
			'wpm_send_email'      => false,
			'wpm_how_often'       => 'daily',
			'wpm_send_email'      => true,
			'wpm_check_plugins'   => true,
			'wpm_check_themes'    => true,
			'wpm_check_WordPress' => true,
			'wpm_check_php'       => true,
			'wpm_check_ssl'       => true,
			'wpm_show_monitor'    => false,

		] );

		update_option( 'wpm_options', self::$options );

		if ( ! function_exists( 'get_plugins' ) ) {

				require_once ABSPATH . 'wp-admin/includes/plugin.php';

		}

		add_action( 'plugins_loaded', [ $this, 'wpm_check_for_updates' ] );

		add_action( 'plugins_loaded', [ $this, 'init' ] );

		include_once( plugin_dir_path( __FILE__ ) . 'class-phpversioner.php' );

		include_once( plugin_dir_path( __FILE__ ) . 'class-settings.php' );

	}

	/**
	 * Runs additional methods on startup
	 *
	 * @since 0.1
	 */
	public function init() {

		add_action( 'admin_enqueue_scripts', [ $this, 'wpm_enqueue_admin_styles' ] );

		if ( current_user_can( 'manage_options' ) ) {

			if ( true === self::$options['wpm_show_monitor'] ) {

				add_action( 'admin_notices', [ $this, 'wpm_dashboard_widget' ] );

			}
		}

		if ( get_option( 'wpm_config' ) !== 'active' ) {

			update_option( 'wpm_config', 'active' );

		}

		if ( false === self::$options['wpm_show_monitor'] ) {
			add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );
		}

		add_action( 'admin_menu', [ $this, 'register_newpage' ] );

		register_deactivation_hook( __FILE__, [ $this, 'wpm_deactivate' ] );

	}

	public function register_newpage() {
		add_menu_page( 'Status Page', 'statuspage', 'administrator', 'statuspage', [ $this, 'wpm_populate_status_page' ] );

		remove_menu_page( 'statuspage' );
	}

	/**
	 * Register the dashboard widget
	 *
	 * @since 0.1
	 */
	public function add_dashboard_widget() {

		wp_add_dashboard_widget(
			'wp_monitor',
			'WP Monitor ',
			[ $this, 'wpm_dashboard_widget_function' ] // Display function.
		);
	}

	/**
	 * Status page code
	 *
	 * @since 0.1
	 */
	public function wpm_populate_status_page() {

		$wpm_variables = get_option( 'wpm_variables' );

		$plugins_that_need_updates = $this->get_plugins_that_need_updates( get_plugins() );

		$themes_that_need_updates = $this->get_themes_that_need_updates( wp_get_themes() );

		echo '<h1 style="text-align: center; margin-bottom: 2%;">' . 'Status Page for \'' . $wpm_variables['Name'] . '\' (' . $wpm_variables['URL'] . ')</h1>';

		echo '<table class="wp-list-table widefat fixed striped">';

		echo '<tr>
		<th>Name:</th>
		<th>' . esc_attr( $wpm_variables['Name'] ) . '</th>
	</tr>
	<tr>
		<th>URL:</th>
		<th>' . esc_attr( $wpm_variables['URL'] ) . '</th>
	</tr>
	<tr>
		<th>Server IP:</th>
		<th>' . esc_attr( $wpm_variables['Server IP'] ) . '</th>
	</tr>
	<tr>
		<th>WP Version:</th>
		<th>' . esc_attr( $wpm_variables['WP Version'] ) . '</th>
	</tr>
	<tr>
		<th>PHP Version:</th>
		<th>' . esc_attr( $wpm_variables['PHP Version'] ) . '</th>
	</tr>
	<tr>
		<th>Uploads Directory Size:</th>
		<th>' . esc_attr( $wpm_variables['Uploads Directory Size'] ) . '</th>
	</tr>
	<tr>
		<th>SMTP:</th>
		<th>' . esc_attr( $wpm_variables['SMTP'] ) . '</th>
	</tr>
	<tr>
		<th>Discourage Search Engines:</th>
		<th>' . esc_attr( $wpm_variables['Discourage Search Engines'] ) . '</th>
	</tr>
	 <tr>
		<td>' . __( ' Plugin Update(s)', 'admin-tools' ) . '</td>
		<td>' . esc_attr( self::$updates['plugins'] ) . '</td>
	</tr>
	<tr>
			<td>' . __( ' Theme Update(s)', 'admin-tools' ) . '</td>
			<td>' . esc_attr( self::$updates['themes'] ) . '</td>
	</tr>';

		echo '</table>';
	}

	public function get_plugin_info( $slug ) {
		if ( false !== ( get_transient( $slug . '_remote_html' ) ) ) {

				return $data;

		}

		$response = wp_remote_get( $slug );

		if ( is_wp_error( $response ) ) {

				return;

		}

		$data = maybe_unserialize( wp_remote_retrieve_body( $response ) );

		if ( is_wp_error( $data ) ) {

			return;

		}

		if ( ! is_object( $data ) ) {

			return false;

		}

		$data = (array) $data;

		unset( $data['sections'] );

		set_transient( $slug . '_remote_html', $data, 24 * HOUR_IN_SECONDS );

		return $data;
	}


	public function get_plugins_that_need_updates( $installed_plugins ) {

					$plugins_that_need_updates = [];

		foreach ( $installed_plugins as $plugin ) {

						$plugin_name = $plugin['Name'];

						$plugin_version = ( null == $plugin['Version'] ) ? '0.0.0' : $plugin['Version'];

						$repo_plugin = $this->get_plugin_info( 'https://api.WordPress.org/plugins/info/1.0/' . $plugin['TextDomain'] );

			if ( ! $repo_plugin ) {

							array_push( $plugins_that_need_updates, $plugin_name );

							continue;

			}

			if ( version_compare( $plugin_version, $repo_plugin['version'], '<' ) ) {

								array_push( $plugins_that_need_updates, $plugin_name );

			}
		}

					return $plugins_that_need_updates;

	}

	public function get_themes_that_need_updates( $installed_themes ) {

			$a_theme = array_slice( $installed_themes, 0, 1 );

			$themes_that_need_updates = [];

		foreach ( $installed_themes as $theme ) {

				$theme_name = $theme->get( 'Name' );

				$theme_version = $theme->get( 'Version' );

				$slug = $theme->get( 'TextDomain' );

				$repo_theme = $this->get_theme_info( $slug );

			if ( empty( $repo_theme ) ) {

					continue;

			}

			if ( version_compare( $theme_version, $repo_theme->version, '<' ) ) {

					array_push( $themes_that_need_updates, $theme_name );

			}
		}

					return $themes_that_need_updates;

	}

	public function get_theme_info( $slug ) {

		$response = wp_remote_post( 'http://api.WordPress.org/themes/info/1.0/', [
			'body' => [
				'action'  => 'theme_information',
				'request' => serialize( (object) [
					'slug' => $slug,
				] ),
			],
		] );

		$wow = maybe_unserialize( wp_remote_retrieve_body( $response ) );

		return $wow;

	}



	/**
	* Generates the code for the dashboard widget
	*
	* @since 0.1
	*/
	public function wpm_dashboard_widget_function() {

		echo '<div id="tabs-dashboard">';

		echo '<a href="#" class="wpm_printout_link"> <i class="fa fa-print" aria-hidden="true" style="font-size: 2em; margin: 1% 0px 0px 1%;"></i> </a>';

		echo '<div id="tabs-dashboard-1" style="min-height: 200px;">';

		echo $this->gauge_cell(
			__( 'Plugins Up To Date', 'wp-monitor' ),
			'g1w',
			esc_attr( sizeof( get_plugins() ) - self::$updates['plugins'] ),
			esc_attr( sizeof( get_plugins() ) )
		);

		echo $this->gauge_cell(
			__( 'Themes Up To Date', 'wp-monitor' ),
			'gw2',
			esc_attr( sizeof( wp_get_themes() ) - self::$updates['themes'] ),
			esc_attr( sizeof( wp_get_themes() ) )
		);

		echo $this->indicator_cell( __( 'WordPress Core', 'wp-monitor' ), 'WordPress' );

		echo '</div>';

		echo '<div id="tabs-dashboard-2" style="min-height: 200px;">';

		echo $this->indicator_cell( __( 'SSL', 'wp-monitor' ), 'ssl' );

		echo $this->php_cell( __( 'PHP', 'wp-monitor' ) );

		echo '</div>';

		echo '<div id="tabs-dashboard-3" style="min-height: 200px;">';

		echo $this->counter_cell( __( 'Total Updates', 'wp-monitor' ), 'total' );

		echo $this->counter_cell( __( 'Overall Grade', 'wp-monitor' ), 'grade' );

		echo '</div>';

		echo '<div id="tabs-dashboard-4" style="min-height: 200px;">';

		echo '<table class="wp-list-table widefat fixed striped wpm_table">';

		echo '<thead>';

			echo '<tr>
				<th>' . __( 'Variable', 'wp-monitor' ) . '</th>
				<th>' . __( 'Value', 'wp-monitor' ) . '</th>
			</tr>';

		echo '</thead>';

		echo $this->variable_table();

		echo '</table>';

		echo '<a style="color: #0073aa;"href="http://wp-monitor.net/2017/03/30/what-does-that-value-mean/">What Does That Value Mean?</a>';

		echo '</div>';

		echo '<div id="tabs-dashboard-5" style="min-height: 200px;">';

		echo '<table class="wp-list-table widefat fixed striped wpm_table">';

			echo '<thead>';
				echo '<tr>';
					echo '<th>' . __( 'Username', 'wp-monitor' ) . '</th>';
					echo '<th>' . __( 'Date/Time', 'wp-monitor' ) . '</th>';
					echo '<th>' . __( 'Last IP Used', 'wp-monitor' ) . '</th>';
					echo '<th>' . __( 'Location', 'wp-monitor' ) . '</th>';
				echo '</tr>';
			echo '</thead>';

			$this->list_last_logins( 'wpm_table_tab', '' );

			echo '</table>';

		echo '</div>';

		echo '<ul>
			<li><a href="#tabs-dashboard-1">Updates (' . ( intval( self::$updates['plugins'] ) + intval( self::$updates['themes'] ) + intval( self::$updates['WordPress'] ) ) . ')</a></li>
			<li><a href="#tabs-dashboard-2">SSL/PHP (' . ( is_ssl() ? 'on' : 'off' ) . '/' . $this->php_version( 2 ) . ')</a></li>
			<li><a href="#tabs-dashboard-3">Grades</a></li>
			<li><a href="#tabs-dashboard-4">Variables (...)</a></li>
			<li><a href="#tabs-dashboard-5">Logins</a></li>
		</ul>';

		echo '</div>';

	}

	/**
	 * Runs on plugin deactivation
	 *
	 * @since 0.1
	 */
	public function wpm_deactivate() {

		update_option( 'wpm_config', 'inactive' );

	}

	/**
	 * Display email indicator
	 *
	 * @since 0.1
	 */
	public function wpm_mail_indicator() {

		return '';

	}

	/**
	 * Insert classic monitor on the dashboard
	 *
	 * @since 0.1
	 */
	function wpm_dashboard_widget() {

		if ( get_current_screen()->base !== 'dashboard' ) {

			return;

		}
	?>

	<div id="wpm_main" class="welcome-panel" style="display: none;">

		<?php
		if ( Settings::$options['wpm_show_monitor'] ) {
				$this->wpm_dashboard_callback();
		}
		?>

	</div>

	<script>
		jQuery(document).ready(function($) {

			$('#welcome-panel').after($('#wpm_main').show());

		});
	</script>

<?php
	}

	/**
	* Check WordPress site and server for updates
	*
	* @since 0.1
	*/
	public function wpm_check_for_updates() {

		if ( ! current_user_can( 'install_plugins' ) ) {

			return;

		}

			$update_data = wp_get_update_data();

			$php_info = PHPVersioner::$info;

			$current_php_version = $this->php_version( 2 );

			$user_version_info = $php_info[ $current_php_version ];

			$user_version_supported_until = $user_version_info['supported_until'];

			$current_date = date( 'dmy' );

			$php_action = ( $user_version_supported_until < $current_date ) ? 'Upgrade Now' : 'Up To Date';

		if ( 'Upgrade Now' === $php_action ) {

				$php_update = 1;

		} else {

				$php_update = 0;

		}

			$user_version_supported_until = gmdate( 'm-d-Y', $user_version_supported_until );

			// UPDATES ARRAY HERE
			self::$updates = [
				'plugins'             => $update_data['counts']['plugins'],
				'themes'              => $update_data['counts']['themes'],
				'WordPress'           => $update_data['counts']['wordpress'],
				'PHP_supported_until' => $user_version_supported_until,
				'php_action'          => $php_action,
				'PHP_update'          => $php_update,
				'SSL'                 => is_ssl() ? 1 : 0,
			];

			update_option( 'wpm_update_info', self::$updates );

	}

	/**
	 * Edit the returned PHP version to have 2 or 3 digits
	 *
	 * @param Array $parts - PHP version string split int parts.
	 *
	 * @since 0.1
	 */
	public function php_version( $parts ) {

		if ( 2 === $parts ) {

			return (string) substr( (string) phpversion(), 0, 3 );

		}

			return (string) phpversion();
	}

	/**
	 * Generate table row for each user
	 *
	 * @since 0.1
	 */
	function list_last_logins() {

				$all_users = get_users( 'blog_id=1' );

		foreach ( $all_users as $user ) {

						$timestamp = get_user_meta( $user->ID, 'last_login_timestamp', true ) ? get_user_meta( $user->ID, 'last_login_timestamp', true ) : ' - ';

						$ip = get_user_meta( $user->ID, 'last_ip', true ) ? get_user_meta( $user->ID, 'last_ip', true ) : ' - ';

						echo '<tr>' .

						'<td>' . $user->user_login . '</td>' .

						'<td class="centertext">' . $timestamp . '</td>' .

						'<td class="centertext">' . $ip . '</td>' .

						'<td class="centertext">' . '<a class="reveal-address" style="color: blue; text-decoration: underline; href="#" data-ip="' . $ip . '">Reveal</a>' . '</td>' .

						'</tr>';

		}

	}

	/**
	 * Create classic monitor
	 *
	 * @since 0.1
	 */
	public function wpm_dashboard_callback() {

			echo '<div id="wpm_main">';

				echo '<div class="twothirds">

				<h1 style="text-align: center; background: #F9F9F9;">WP Monitor: <a href="#" class="wpm_printout_link"> <i class="fa fa-print" aria-hidden="true" style="font-size: 1.2em; margin: 1% 0px 0px 1%;"></i> </a>'; // . '<div style="float: right; font-size: 14px;">' . apply_filters( 'wpm_mail_indicator', '' ) . '</div></h1>';

							echo '<div id="first_gauge_row" style="width: 100%; float: left; text-align: left;">';

								echo '<h3>Updates</h3>';

										echo $this->gauge_cell(
											__( 'Plugins Up To Date', 'wp-monitor' ),
											'g1',
											sizeof( get_plugins() ) - self::$updates['plugins'],
										sizeof( get_plugins() ) );

										echo $this->gauge_cell( __( 'Themes Up To Date', 'wp-monitor' ), 'g2', sizeof( wp_get_themes() ) - self::$updates['themes'], sizeof( wp_get_themes() ) );

										echo $this->indicator_cell( __( 'WordPress Core', 'wp-monitor' ), 'WordPress' );

										echo $this->php_cell( __( 'PHP', 'wp-monitor' ) );

							echo '</div>';

							echo '<div id="second_gauge_row" style="width: 100%; background: #F9F9F9; float: left;">';

								echo '<h3 style="text-align: left;">Summary</h3>';

										echo $this->indicator_cell( __( 'SSL', 'wp-monitor' ), 'ssl' );

										$final_grade = intval( self::$updates['plugins'] ) + intval( self::$updates['themes'] ) + intval( self::$updates['WordPress'] );
										// + self::$updates['PHP_update'] );

										echo $this->counter_cell( __( 'Total Updates', 'wp-monitor' ), 'total' );

										echo $this->gauge_cell( __( 'Overall Grade', 'wp-monitor' ), 'g3', (integer) $this->calculate_grade(), 100 );

							echo '</div>';

							echo '<div id="third_gauge_row">

							</div>';

						echo '</div>';

						echo '<div class="tablesthird" >';

						echo '
						<div id="tabs">
							<ul>

								<li><a href="#tabs-1">' . __( 'Variables', 'wp-monitor' ) . '</a></li>

								<li><a href="#tabs-2">' . __( 'User Logins', 'wp-monitor' ) . '</a></li>';

								echo apply_filters( 'wpm_tabs', '' );

							echo '</ul>';

							echo '<div id="tabs-1">';

							echo '<table class="wp-list-table widefat fixed striped wpm_table">';

								echo '<thead>';

									echo '<tr>
										<th>' . __( 'Variable', 'wp-monitor' ) . '</th>
										<th>' . __( 'Value', 'wp-monitor' ) . '</th>
									</tr>';

									echo '</thead>';

								echo $this->variable_table();

					echo '</table>';

					echo '<a style="color: #0073aa;"href="http://wp-monitor.net/2017/03/30/what-does-that-value-mean/">What Does That Value Mean?</a>';

				echo '</div>';

							echo '<div id="tabs-2">

									<table class="wp-list-table widefat fixed striped wpm_table">

										<thead>
											<tr>
												<th>' . __( 'Username', 'wp-monitor' ) . '</th>
												<th>' . __( 'Date/Time', 'wp-monitor' ) . '</th>
												<th>' . __( 'Last IP Used', 'wp-monitor' ) . '</th>
												<th>' . __( 'Location', 'wp-monitor' ) . '</th>
											</tr>
										</thead>';

								$this->list_last_logins( 'wpm_table_tab', '' );

							echo '</table>';

						echo '</div>';

					echo apply_filters( 'wpm_table_tab', '' );

					echo '</div>

						</div>

					</div>';

	}

	/**
	 * Create code for a cell with a title and a gauge.
	 *
	 * @param string $title - title of the cell.
	 *
	 * @param string $gauge_class - string passed in to each cell to give the gauge a unqique class for functionality.
	 *
	 * @param int    $value - the value for a gauge to display.
	 *
	 * @param int    $max - the maximum value of the gauge.
	 *
	 * @since 0.1
	 */
	public function gauge_cell( $title, $gauge_class, $value, $max ) {

				$content = '<div class="onequarter cell">';

				$content .= '<h3>' . esc_attr( $title ) . '</h3>';

				$content .= '<div id="' . esc_attr( $gauge_class ) . '" class="gauge"></div>
						<script>
							var g1_' . esc_attr( $gauge_class ) . ';
							document.addEventListener( "DOMContentLoaded", function( event ) {
								var g1_' . esc_attr( $gauge_class ) . ' = new JustGage( {
									id: "' . esc_attr( $gauge_class ) . '",
									value: ' . esc_attr( $value ) . ',
									min: 0,
									max: ' . esc_attr( $max ) . ',
									title: "' . esc_attr( $title ) . '",
									gaugeWidthScale: 0.6,
									customSectors: {
										percents: true,
										ranges: [{
											color : "#FF0000",
											lo : 0,
											hi : 50
										},{
											color : "#00FF00",
											lo : 51,
											hi : 100
										}]
									},
									counter: true
									});

							} );
						</script>';

		if ( 'Overall Grade' == $title ) {
							$content .= '<div id="grade_breakdown_link" class="breakdown_link" style="margin-left: 0px auto;">' . 'Why?' . '</div>';
		}

				$content .= '</div>';

				return $content;

	}

	/**
	 * Create the code for an indicator cell.
	 *
	 * @param string $title - the title of the cell.
	 *
	 * @param string $prefix - the prefix of the cell which decides the width of the cell.
	 *
	 * @since 0.1
	 */
	public function indicator_cell( $title, $prefix ) {

		$content = '';

		if ( 'WordPress' === $prefix ) {

			$content = '<div class="onequarter cell">';
		}

		if ( 'ssl' === $prefix ) {

			$content = '<div class="onethird cell">';

		}

				$content .= '<h3>' . $title . '</h3>

					<div class="gauge indicator">

							<div class="indicator_light" id="' . $prefix . '_light">&nbsp;</div>

					</div>

					<p id="wpm_' . $prefix . '_message">???</p>

					</div>';

					return $content;

	}

	/**
	 * Generates the code for the PHP cell
	 *
	 * @param string $title - the title of the cell.
	 *
	 * @since 0.1
	 */
	public function php_cell( $title ) {

						return '<div class="onequarter cell" style="text-align: center;">

						<h3>' . $title . '</h3>

						<div id="wpm_php_indicator" class="indicator_light">&nbsp;</div>

							<p id="wpm_php_version">Running Version: ???</p>

							<p id="wpm_php_support">Supported Until: ' . '??-??-????' . '</p>

							<p id="php_message"></p>

					</div>';

	}

	/**
	 * Generate the code for a counter cell.
	 *
	 * @param string $title - the title of the cell
	 *
	 * @param string $prefix - short string to prepend onto the ID for the counter value.
	 *
	 * @since 0.1
	 */
	public function counter_cell( $title, $prefix ) {

				return '<div class="onethird cell">

				<h3 style="padding-bottom: 5%;">' . $title . '</h3>

					<div class="gauge overall">

						<div class="counter" id="' . $prefix . '_counter">' . '&nbsp;' . '</div>

						<br />

						<div id="' . $prefix . '_breakdown_link" class="breakdown_link">' . '&nbsp;' . '</div>

					</div>

				</div>';

	}

	/**
	 * Calculate the overall grade of the website based on the results from the plugin.
	 *
	 * @since 0.1
	 */
	public function calculate_grade() {

				$grades = [
					'Plugins'   => ( ( ( count( get_plugins() ) - self::$updates['plugins'] ) / count( get_plugins() ) ) * 100 ),
					'Themes'    => ( ( ( count( wp_get_themes() ) - self::$updates['themes'] ) / count( wp_get_themes() ) ) * 100 ),
					'WordPress' => ( 0 == self::$updates['WordPress'] ) ? 100 : 50,
					'PHP'       => ( 0 == self::$updates['PHP_update'] ) ? 100 : 50,
					'SSL'       => self::$updates['SSL'] ? 100 : 50,
				];

				$subtotal = $grades['Plugins'] + $grades['Themes'] + $grades['WordPress'] + /*$grades['PHP'] +*/ $grades['SSL'];

				$subtotal = $subtotal / 5;

				$subtotal = round( $subtotal, 0 );

				return $subtotal;
	}

	/**
	 * Generates the code for the variable table
	 *
	 * @since 0.1
	 */
	public function variable_table() {

				$all_vars = '';

		if ( ( get_option( 'blog_public' ) == 0 ) || empty( get_option( 'blog_public' ) ) ) {

					$blog_public = 'true';

		} else {

					$blog_public = 'false';

		}

		$upload_dir = wp_upload_dir();

		$upload_dir = $upload_dir['path'];

		$upload_dir = strrev( $upload_dir );

		$upload_dir = substr( $upload_dir, strpos( $upload_dir, '/' ), strlen( $upload_dir ) );

		$upload_dir = substr( $upload_dir, strpos( $upload_dir, '/' ) + 1, strlen( $upload_dir ) );

		$upload_dir = substr( $upload_dir, strpos( $upload_dir, '/' ), strlen( $upload_dir ) );

		$upload_dir = substr( $upload_dir, strpos( $upload_dir, '/' ) + 1, strlen( $upload_dir ) );

		$upload_dir = strrev( $upload_dir );

		$uploads_size = $this->wpm_foldersize( $upload_dir );

				$variables = [
					'Name'                      => get_bloginfo( 'name' ),
					'URL'                       => get_bloginfo( 'url' ),
					'WP Version'                => get_bloginfo( 'version' ),
					'PHP Version'               => phpversion(),
					'Server IP'                 => $_SERVER['SERVER_ADDR'],
					'Charset'                   => get_bloginfo( 'charset' ),
					'Admin Email'               => get_bloginfo( 'admin_email' ),
					'Language'                  => get_bloginfo( 'language' ),
					'Stylesheet Directory'      => get_bloginfo( 'stylesheet_directory' ),
					'Uploads Directory '        => $upload_dir,
					'Uploads Directory Size'    => round( $uploads_size / 1048576, 2 ) . ' MB',
					'Front Page Displays'       => get_option( 'show_on_front' ),
					'Posts Per Page'            => get_option( 'posts_per_page' ),
					'Atom URL'                  => get_bloginfo( 'atom_url' ),
					'SMTP'                      => ini_get( 'SMTP' ),
					'Discourage Search Engines' => $blog_public,
					'PHP Memory Limit'          => ini_get( 'memory_limit' ),
				];

				update_option( 'wpm_variables', $variables );

				foreach ( $variables as $key => $value ) {

					$all_vars .=
					'<tr>
						<th>' . $key . '</th>
						<th>' . $value . '</th>
					</tr>';

				}

				return $all_vars;

	}

	/**
	 * Get the size of a folder.
	 *
	 * @param string $path - path to the folder to get the size of.
	 *
	 * @since 0.1
	 */
	public function wpm_foldersize( $path ) {

		$total_size = 0;

		$files = scandir( $path );

		$clean_path = rtrim( $path, '/' ) . '/';

		foreach ( $files as $t ) {

			if ( '.' <> $t && '..' <> $t ) {

				$current_file = $clean_path . $t;

				if ( is_dir( $current_file ) ) {

					$size = $this->wpm_foldersize( $current_file );

					$total_size += $size;

				} else {

					$size = filesize( $current_file );

					$total_size += $size;

				}
			}
		}

		return $total_size;

	}

	/**
	 * Callback for the general settings section of the settings page
	 *
	 * @since 0.1
	 */
	public function wpm_general_section_callback() {

		echo 'Edit the settings for the plugin here.';

	}

	/**
	 * Enqueue the admin stylesheet.
	 *
	 * @since 0.1
	 */
	public function wpm_enqueue_admin_styles( $hook ) {

		if ( 'index.php' !== $hook ) {

			return;

		}
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_register_style( 'wpm_admin_css', plugin_dir_url( __FILE__ ) . "/library/css/admin-style{$suffix}.css", false, '1.0.0' );
		wp_enqueue_style( 'wpm_admin_css' );

		wp_register_script( 'wpm_counter', plugin_dir_url( __FILE__ ) . 'library/js/renamed.js', [ 'jquery' ], '1.0.0' );
		wp_localize_script( 'wpm_counter', 'wpm_data', [
			// 'total'	=> self::$updates['plugins'] + self::$updates['themes'] + self::$updates['WordPress'] + self::$updates['PHP_update'],
			'grade'             => (integer) $this->calculate_grade(),
			'WordPress'         => intval( self::$updates['WordPress'] ),
			'ssl'               => self::$updates['SSL'],
			'plugin_updates'    => self::$updates['plugins'],
			'total_plugins'     => count( get_plugins() ),
			'total_themes'      => count( wp_get_themes() ),
			'theme_updates'     => self::$updates['themes'],
			'WordPress_updates' => self::$updates['WordPress'],
			'php_updates'       => self::$updates['php_action'],
			'ssl'               => self::$updates['SSL'] ? 'On' : 'Off',
		] );

		wp_enqueue_script( 'wpm_counter' );

		wp_register_script( 'wpm_phpcell', plugin_dir_url( __FILE__ ) . 'library/js/phpcell.js', [ 'jquery' ], '1.0.0' );
		wp_localize_script( 'wpm_phpcell', 'wpm_data_php', [
			'current_version' => $this->php_version( 2 ),
			'state'           => self::$updates['php_action'],
			'supported_until' => gmdate( 'm-d-Y', PHPVersioner::$info[ $this->php_version( 2 ) ]['supported_until'] ),
		] );

		wp_enqueue_script( 'wpm_phpcell' );

		wp_register_script( 'tabs-init', plugin_dir_url( __FILE__ ) . '/library/js/tabs-init.jquery.js', [ 'jquery-ui-tabs' ] );
		wp_enqueue_script( 'tabs-init' );

		wp_register_style( 'wpm_tabs_css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.min.css', false, '1.0.0' );
		wp_enqueue_style( 'wpm_tabs_css' );

		/* Gauges */
		wp_register_script( 'wpm_raphael', plugin_dir_url( __FILE__ ) . '/library/js/raphael-2.1.4.min.js' );
		wp_enqueue_script( 'wpm_raphael' );

		wp_register_script( 'wpm_justgage', plugin_dir_url( __FILE__ ) . '/library/js/justgage.js' );
		wp_enqueue_script( 'wpm_justgage' );

		/* Admin Jquery */
		wp_register_script( 'wpm_admin', plugin_dir_url( __FILE__ ) . '/library/js/admin-wpmonitor.js', [ 'jquery' ] );
		wp_enqueue_script( 'wpm_admin' );

		/* Font Awesome */
		wp_enqueue_style( 'cp_fontawesome', plugin_dir_url( __FILE__ ) . '/library/fonts/font-awesome-4.7.0/css/font-awesome.min.css', [], false, 'all' );

		/* Reveal */
		wp_register_script( 'wpm_revealer', plugin_dir_url( __FILE__ ) . '/library/js/revealer.js', [ 'jquery' ], '1.0.0' );
		wp_enqueue_script( 'wpm_revealer' );

		/* Widget */
		if ( false === self::$options['wpm_show_monitor'] ) {

			wp_register_script( 'wpm_widget', plugin_dir_url( __FILE__ ) . '/library/js/widget.js', [ 'jquery' ], '1.0.0' );
			wp_enqueue_script( 'wpm_widget' );

		}

	}



}

$wp_monitor = new WPMonitor();
