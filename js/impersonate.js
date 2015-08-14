(function(){

	$(document).ready(function() {

		function impersonate(userid) {
			$.post(
				OC.generateUrl('apps/impersonate/user'),
				{ userid: userid }
			).done(function( result ) {
				window.location = OC.generateUrl('apps/files');
			}).fail(function( result ) {
				OC.dialogs.alert(result.responseJSON.message, t('impersonate', 'Could not impersonate user'));
			});
		}

		$('#impersonate').on('click', 'button', function(e) {
			impersonate($('#impersonate input').val());
		});

	});

})();