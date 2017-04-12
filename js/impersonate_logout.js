$(document).ready(function () {

	$("#logout").attr("href","#");

	var text = t(
		'core',
		'<a href="{docUrl}">{displayText}</a>',
		{
			docUrl: OC.generateUrl('apps/files'),
			displayText: "Logged in as " + OC.getCurrentUser().uid,
		}
	);

	var timeout = 15;
	OC.Notification.showHtml(
		text,
		{
			isHTML: true, timeout
		}
	);

	function logoutHandler(userid) {
		var promisObj = $.post(
			OC.generateUrl('apps/impersonate/logout'),
			{userid: userid}
		).promise();

		promisObj.done(function () {
			OC.redirect('apps/files');
		});
	}

		$("#logout").on('click', function (event) {
			event.preventDefault();
			var userid = $("#expandDisplayName").text();
			logoutHandler(userid);
		});
});
