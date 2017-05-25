(function(){
	function getDataForApp() {
		return $.get(
			OC.generateUrl('apps/impersonate/getimpersonatedata')
		).promise();
	}

	var promiseGetData = getDataForApp();

	function addImpersonateIcon ($localtr) {
		var TEMPLATE_BASE =
			'<td><a class="action permanent impersonate" href="#" title="{{impersonate}}">' +
			'<img class="svg permanent action" src="{{impersonate_src}}" />' +
			'</a></td>';
		var addImpersonate = {
			/** @type {Object} **/
			_templates: {},

			render: function () {
				var baseTemplate = this._getTemplate('base', TEMPLATE_BASE);
				return baseTemplate({
					impersonate_src: OC.imagePath('core', 'actions/user.svg'),
					displayText: t('impersonate', 'Impersonate')
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

		var $templateAddImpersonate = addImpersonate.render();
		$($templateAddImpersonate).insertAfter($localtr.find('.name'));
	}

	function removeImpersonateIcon ($localtr) {
		var TEMPLATE_BASE =
			'<td class="impersonateDisabled"><span></span></td>';
		var removeImpersonate = {
			/** @type {Object} **/
			_templates: {},

			render: function () {
				var baseTemplate = this._getTemplate('base', TEMPLATE_BASE);
				return baseTemplate();
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

		var $templateRemoveImpersonate = removeImpersonate.render();
		$($templateRemoveImpersonate).insertAfter($localtr.find('.name'));
	}

	var includedGroups,
	groupEnabled,
	adminUser,
	subadminUser;

	var currentUser = OC.getCurrentUser().uid;

	OC.Plugins.register('OC.Settings.UserList', {
		attach: function (obj) {
			var $newColumn = $("#userlist").find("tr:first-child");
			$('<th id="impersonateId" scope="col">Impersonate</th>').insertAfter($newColumn.find("#headerName"));
			//Obj is UserList
			var oldAdd = obj.add;
			obj.add = function () {
				var $tr = oldAdd.apply(this, arguments);
				promiseGetData.then(function (result) {
					includedGroups = $.parseJSON(result[0]);
					groupEnabled = JSON.parse($.trim(result[1]));
					adminUser = JSON.parse($.trim(result[2]));
					subadminUser = JSON.parse($.trim(result[3]));
					if (!$tr) {
						return;
					}

					//Clean up of impersonation
					$tr.find('.impersonate').parent().remove();
					$tr.find('.impersonateDisabled').remove();

					var groupsSelectedByUser = $tr.find('.groups').data('groups');

					if ($tr.data('uid') === $.trim(currentUser)) {
						removeImpersonateIcon($tr);
					} else if (adminUser) {
						addImpersonateIcon($tr);

					} else if (subadminUser) {
						var found = false;
						if(groupEnabled) {
							for (var i = 0; i < includedGroups.length; i++) {
								if ($.inArray($.trim(includedGroups[i]), groupsSelectedByUser) !== -1) {
									found = true;
									addImpersonateIcon($tr);
									break;
								}
							}
						} else {
							addImpersonateIcon($tr);
							found = true;
						}
						if (found === false) {
							removeImpersonateIcon($tr);
						}
					}
					return $tr;
				});
			};
		}
	});

	$(document).ready(function () {
		function impersonate(target) {
			var currentUser = OC.getCurrentUser().uid;
			$.post(
				OC.generateUrl('apps/impersonate/user'),
				{ target: target }
			).done(function( result ) {
				OC.redirect(OC.generateUrl('apps/files'));
			}).fail(function( result ) {
				if((result.responseJSON.error === "userNeverLoggedIn") && (result.responseJSON.message.length > 0)) {
					OC.dialogs.alert(t('impersonate', result.responseJSON.message),t('impersonate', "Error"));
				} else if((result.responseJSON.error === "userNotFound") && (result.responseJSON.message.length > 0)){
					OC.dialogs.alert(t('impersonate', result.responseJSON.message), t('impersonate', "Error"));
				} else if((result.responseJSON.error === "cannotImpersonate") && (result.responseJSON.message.length > 0)){
					OC.dialogs.alert(t('impersonate', result.responseJSON.message), t('impersonate', "Error"));
				}
			});
		}

		$('#userlist').on('click', '.impersonate', function() {
			var target = $(this).parents('tr').find('.name').text();
			impersonate(target);
		});

	});

})();
