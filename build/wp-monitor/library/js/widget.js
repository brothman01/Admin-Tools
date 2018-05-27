jQuery( document ).ready( function() {


	//var gauge_divs = jQuery( '1' ).find( '' );

	jQuery('#tabs-dashboard-1 .onequarter.cell').each( function() {
        jQuery(this).removeClass('onequarter');
				jQuery(this).addClass('onethird');
});

	//jQuery('#tabs-dashboard-2').css( 'width', '25%' );
	// jQuery('#tabs-dashboard-2 .onethird.cell').css( 'margin', '0px auto' );
	// jQuery('#tabs-dashboard-2 .onethird.cell').css( 'float', 'none' );
	jQuery('#tabs-dashboard-2 .gauge .indicator_light').css( 'width', '110px' );
	jQuery('#tabs-dashboard-2 .gauge .indicator_light').css( 'height', '110px' );

	jQuery('#tabs-dashboard-2 .onequarter').css( 'width', '50%' );
	jQuery('#tabs-dashboard-2 .onethird').css( 'width', '50%' );

	jQuery('#wpm_php_indicator').css( 'width', '110px' );
	jQuery('#wpm_php_indicator').css( 'height', '110px' );

	jQuery('#tabs-dashboard-3 .onethird').css( 'width', '50%' );
	jQuery('#tabs-dashboard-3 .onethird').css( 'width', '50%' );

	//console.log(gauge_divs);


// 	jQuery( 'body' ).on( 'click', '.reveal-address', function() {
//
// 	 var ip = jQuery( this ).data( 'ip' );
// 		//jQuery( this ).html( address );
//
// 		var link = jQuery( this );
//
// 		jQuery.ajax( {
// 		  url: 'http://ip-api.com/json/' + ip,
// 		  method: 'GET',
// 		  success:function( data ){
//
// 				if ( data.status === "fail" ) {
//
// 					link.html( 'No Address' );
//
// 				}
//
// 				if ( data.status === "success" ) {
//
// 					link.html( data.city + ' ' + data.region + ' ' + data.countryCode );
//
// 				}
//
// 		  }
// } );
//
//
//
// 		jQuery( this ).css( 'color', 'black' );
//
// 		jQuery( this ).css( 'text-decoration', 'none' );
//
// 	} );

} );
