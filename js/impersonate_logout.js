$(document).ready(function () {

	$("#logout").attr("href","#");

	var context = {
		docUrl: OC.generateUrl('apps/files'),
		displayText: t('core','Logged in as {currentUser}', {'currentUser': OC.getCurrentUser().uid})
	};

	var $html = OCA.Impersonate['impersonateNotification'](context);
	$($html).insertBefore("#notification");

	function logoutHandler() {
		var promisObj = $.post(
			OC.generateUrl('apps/impersonate/logout')
		).promise();

		promisObj.done(function () {
			OC.redirect('apps/files');
		}).fail(function (result) {
			if ((result.responseJSON.error === "cannotLogout") && (result.responseJSON.message.length > 0))
			OC.dialogs.alert(t('impersonate', result.responseJSON.message),t('impersonate', "Error"));
		});

	}

		$("#logout").on('click', function (event) {
			event.preventDefault();
			logoutHandler();
		});
});
