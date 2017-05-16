$(document).ready(function () {

	$("#logout").attr("href","#");

	var TEMPLATE_BASE =
		'<div id="impersonate-notification" ' +
		'<div class="row">' +
		'<a href="{{docUrl}}" style="text-align: center;">{{displayText}}</a>' +
		'</div>' +
		'</div>';

	var ImpersonateNotification = {
		/** @type {Object} **/
		_templates: {},

		render: function () {
			var baseTemplate = this._getTemplate('base', TEMPLATE_BASE);
			return baseTemplate({
				docUrl: OC.generateUrl('apps/files'),
				displayText: t('core','Logged in as {currentUser}', {'currentUser': OC.getCurrentUser().uid})
			});
		},

		/**
		 *
		 * @param {string} key - an identifier for the template
		 * @param {string} template - the HTML to be compiled by Handlebars
		 * @returns {Function} from Handlebars
		 * @private
		 */
		_getTemplate: function (key, template) {
			if (!this._templates[key]) {
				this._templates[key] = Handlebars.compile(template);
			}
			return this._templates[key];
		},
	};

	var $templateImpersonate = ImpersonateNotification.render();
	$($templateImpersonate).insertBefore("#notification");

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
