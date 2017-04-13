(function(){

	$(document).ready(function () {

		function impersonate(userid) {
			var currentUser = OC.getCurrentUser().uid;
			$.post(
				OC.generateUrl('apps/impersonate/user'),
				{ userid: userid }
			).done(function( result ) {
				OC.redirect(OC.generateUrl('apps/files'));
			}).fail(function( result ) {
				OC.dialogs.alert(result.responseJSON.message, t('impersonate', 'Could not impersonate user'));
			});
		}

		var includedGroups;
		OC.AppConfig.getValue('impersonate','impersonate_include_groups_list',"[]", function (data) {
			includedGroups = $.parseJSON(data);
		});

		var addImpersonate;
		var oldAdd = UserList.add;
		var $newColumn = $("#userlist").find("tr:first-child");
		$('<th id="impersonateId" scope="col">Impersonate</th>').insertAfter($newColumn.find("#headerName"));
		UserList.add = function () {
			var $tr = oldAdd.apply(this,arguments);
			if(!$tr) {
				return;
			}
			var groupsSelectedByUser = $tr.find('.groups').text();
			groupsSelectedByUser = $.trim(groupsSelectedByUser);
			groupsSelectedByUser = groupsSelectedByUser.split(',');
			if (includedGroups.length === 0) {
				addImpersonate = '<td><a class="action permanent impersonate" href="#" title="' +
					t('impersonate', 'Impersonate') + '">' +
					'<img class="svg permanent action" src="' + OC.imagePath('core', 'actions/user.svg') + '" />' +
					'</a></td>';
				$(addImpersonate).insertAfter($tr.find('.name'));
			} else {
				var found = false;
				for (var i = 0; i < includedGroups.length; i++) {
					if ($.inArray($.trim(includedGroups[i]), groupsSelectedByUser) !== -1) {
						found = true;
						addImpersonate = '<td><a class="action permanent impersonate" href="#" title="' +
							t('impersonate', 'Impersonate') + '">' +
							'<img class="svg permanent action" src="' + OC.imagePath('core', 'actions/user.svg') + '" />' +
							'</a></td>';
						$(addImpersonate).insertAfter($tr.find('.name'));
						break;
					}
				}
				if (found === false) {
					addImpersonate = '<td class="impersonateDisabled"><span></span></td>';
					$(addImpersonate).insertAfter($tr.find('.name'));
				}
			}
			return $tr;
		};

		$('#userlist').on('click', '.impersonate', function() {
			var userid = $(this).parents('tr').find('.name').text();
			impersonate(userid);
		});

	});

})();
