
jQuery(document).ready(function() {



		setTimeout(function(){

			jQuery('#wpm_php_version').text('Running Version: ' + wpm_data_php.current_version);

			jQuery('#wpm_php_support').text('Supported Until: ' + wpm_data_php.supported_until);

				if (wpm_data_php.state == "Up To Date") {

					jQuery('#wpm_php_indicator').css( 'background',  '#01FC27' );

				} else {

					jQuery('#wpm_php_indicator').css( 'background', '#FF0000');

				}

					jQuery('#wpm_php_indicator').val( wpm_data_php.state + '' );

					jQuery('#php_message').html('<a style="text-decoration: underline!important;" href="http://www.wp-monitor.net/2017/04/04/why-upgrade-php/">Why Upgrade PHP?</a>');


		}, 1000);

	} );
