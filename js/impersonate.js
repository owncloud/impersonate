(function(){
	function getDataForApp() {
		return $.get(
			OC.generateUrl('apps/impersonate/getimpersonatedata')
		).promise();
	}

	var promiseGetData = getDataForApp();

	function addImpersonateIcon ($localtr) {
		var context = {
			impersonate_src: OC.imagePath('core', 'actions/user.svg'),
			displayText: t('impersonate', 'Impersonate')
		};

		var $html = OCA.Impersonate['addImpersonateIcon'](context);
		$($html).insertAfter($localtr.find('.name'));
	}

	function removeImpersonateIcon ($localtr) {
		var context = {};
		var $html = OCA.Impersonate['removeImpersonateIcon'](context);
		$($html).insertAfter($localtr.find('.name'));
	}

	var includedGroups,
	groupEnabled,
	adminUser,
	subadminUser, subAdminFullAccess;

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
					subAdminFullAccess = JSON.parse($.trim(result[2]));
					adminUser = JSON.parse($.trim(result[3]));
					subadminUser = JSON.parse($.trim(result[4]));
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
						if (subAdminFullAccess) {
							addImpersonateIcon($tr);
						} else {
							var found = false;
							if (groupEnabled) {
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
				OC.redirect(OC.generateUrl(''));
			}).fail(function( result ) {
				if((result.responseJSON.error === "userNeverLoggedIn") && (result.responseJSON.message.length > 0)) {
					OC.dialogs.alert(result.responseJSON.message, t('impersonate', "Error"));
				} else if((result.responseJSON.error === "userNotFound") && (result.responseJSON.message.length > 0)){
					OC.dialogs.alert(result.responseJSON.message, t('impersonate', "Error"));
				} else if((result.responseJSON.error === "cannotImpersonate") && (result.responseJSON.message.length > 0)){
					OC.dialogs.alert(result.responseJSON.message, t('impersonate', "Error"));
				} else if ((result.responseJSON.error === "cannotImpersonateAdminUser") && (result.responseJSON.message.length > 0)) {
					OC.dialogs.alert(result.responseJSON.message, t('impersonate', "Error"));
				} else if ((result.responseJSON.error === "stopNestedImpersonation") && (result.responseJSON.message.length > 0)) {
					OC.dialogs.alert(result.responseJSON.message, t('impersonate', "Error"));
				}
			});
		}

		$('#userlist').on('click', '.impersonate', function() {
			var target = $(this).parents('tr').find('.name').text();
			impersonate(target);
		});

	});

})();
