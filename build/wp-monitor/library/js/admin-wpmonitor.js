jQuery( document ).ready( function() {

		jQuery( 'body' ).on( 'click', '.wpm_printout_link', function( e ) {

			e.preventDefault();

			window.location.replace('/wp-admin/admin.php?page=statuspage');

		});

});
