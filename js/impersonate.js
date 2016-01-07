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

		$('<th>&nbsp;</th>').insertAfter('#userlist #headerName');
		$('<td><a class="action permanent impersonate" href="#" title="' +
			t('impersonate', 'Impersonate') + '">' +
			'<img class="svg permanent action" src="/core/img/actions/user.svg" />' +
			'</a></td>')
			.insertAfter('#userlist .name');

		$('#userlist').on('click', '.impersonate', function(e) {
			var userid = $(e.srcElement).parents('tr').find('.name').text();
			OCdialogs.confirm(
				t('impersonate', 'With great power comes great responsibility!'),
				t('impersonate', 'Are you sure you want to impersonate {userid}?',
					{userid: userid} ),
				function(result) {
					if (result) {
						impersonate(userid);
					}
				},
				true
			);
		});

	});

})();