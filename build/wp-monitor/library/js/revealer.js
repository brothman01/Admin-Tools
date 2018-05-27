jQuery( document ).ready( function() {


	jQuery( 'body' ).on( 'click', '.reveal-address', function() {

	 var ip = jQuery( this ).data( 'ip' );
		//jQuery( this ).html( address );

		var link = jQuery( this );

		jQuery.ajax( {
		  url: 'http://ip-api.com/json/' + ip,
		  method: 'GET',
		  success:function( data ){

				if ( data.status === "fail" ) {

					link.html( 'No Address' );

				}

				if ( data.status === "success" ) {

					link.html( data.city + ' ' + data.region + ' ' + data.countryCode );

				}

		  }
} );



		jQuery( this ).css( 'color', 'black' );

		jQuery( this ).css( 'text-decoration', 'none' );

	} );

} );
