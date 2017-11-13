$(document).ready(function () {

	var context = {
		docUrl: OC.generateUrl('apps/files'),
		displayText: t('core','Logged in as {currentUser}', {'currentUser': OC.getCurrentUser().uid})
	};

	var $html = OCA.Impersonate['impersonateNotification'](context);
	$($html).insertBefore("#notification");

});
