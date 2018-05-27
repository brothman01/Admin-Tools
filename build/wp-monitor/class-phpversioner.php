<?php

/**
 * [PHPVersioner - Main class of the main file of the plugin]
 */
class PHPVersioner extends WPMonitor {

	public static $info;

	public function __construct() {

		self::$info = $this->get_info();

	}

	public function get_info() {

		if ( false === get_transient( 'wpm_php_info' ) ) {

			try {
					set_transient( 'wpm_php_info', $this->wpm_version_info(), 24 * HOUR_IN_SECONDS );

				$php_data = get_transient( 'wpm_php_info' );

			} catch ( Exception $e ) {
				// Could not connect.
			}
		} else {

			$php_data = get_transient( 'wpm_php_info' );

		}

		return $php_data;

	}

	/**
	 * [wpm_version_info - Get or make transient of the php support data read from the official php site.
	 *
	 * @return array 'released', 'supported_until', 'security_until' for each version of php listed on the page.
	 */
	public function wpm_version_info() {

			$contents = wp_remote_get( 'http://php.net/supported-versions.php' );

			$body = str_replace( '<link rel="shortcut icon" href="http://php.net/favicon.ico">', '', wp_remote_retrieve_body( $contents ) );

			$dom = new DOMDocument();

			libxml_use_internal_errors( true );

			$dom->loadHTML( $body );

			$tr = $dom->getElementsByTagName( 'tr' );

			$column_text = [];

			$x = 1;

		foreach ( $tr as $row ) {

				$columns = $row->getElementsByTagName( 'td' );

			foreach ( $columns as $column ) {

				$column_text[ $x ] = trim( str_replace( '*', '', $column->textContent ) ); // @codingStandardsIgnoreLine

					$x++;

			}
		}

			$column_text = array_chunk( $column_text, 7 );

			$column_text = array_slice( $column_text, 1, -1 );

			$php_version_info = array();

			$y = 0;

		foreach ( $column_text as $php_info ) {

				$php_version_info[ $php_info[0] ] = [
					'released'        => strtotime( $php_info[1] ),
					'supported_until' => strtotime( $php_info[3] ),
					'security_until'  => strtotime( $php_info[5] ),
				];

				$y++;
		}

		return $php_version_info;
	}

}

$versioner = new PHPVersioner();
