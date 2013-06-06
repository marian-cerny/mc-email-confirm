$(document).ready(function(){

	if ( $('.mc_download_confirm').length > 0 )
	{
		$.ajax({
			url: mc_ec_ajax_vars.ajax_url,
			data: 'action=insert_form',
			success: function( result ) 
				{ 
					$('.mc_download_confirm').after( result );
					$('.mc_download_confirm').attr( 'href', '#student-form-wrapper' );
					console.log( 'form inserted' );
				}
		});
		
		$('.mc_download_confirm').fancybox();
		
	}

	$( 'body' ).delegate( '#student-submit', 'click', function( event ) {
	
		event.preventDefault();
	
		var name = $( '#name' ).val();
		var email = $( '#email' ).val();
		var phone = $( '#phone' ).val();
		
		if ( ( name == '' ) || ( email == '' ) || ( phone == '' ) )
		{
			alert( 'Please fill in the empty fields.' );
			return;
		}
		
		$.ajax({
			type: 'POST',
			url: mc_ec_ajax_vars.ajax_url,
			data: 'action=save_data&phone='+phone+'&email='+email+'&name='+name,
			success: function( result ) 
				{ 
					console.log( 'data inserted' );
				},
			complete: function( result ) 
				{ 
					$( '#name' ).val( '' );
					$( '#email' ).val( '' );
					$( '#phone' ).val( '' );
					alert( 'Thank you for your interest in our 2013 Student Report' );
					$.fancybox.close();
				}
		});
	} );

});