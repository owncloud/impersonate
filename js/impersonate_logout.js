$(document).ready(function () {

	var context = {
		docUrl: OC.generateUrl('apps/files'),
		displayText: t('impersonate','Logged in as {currentUser}', {'currentUser': OC.getCurrentUser().uid})
	};

	var $html = OCA.Impersonate['impersonateNotification'](context);
	$($html).insertBefore("#notification");

});
