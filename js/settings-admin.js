$(document).ready(function () {
	$("#impersonateNoGroupAdmins").change(function () {
		$("#selectIncludedGroups").addClass('hidden');
		OC.AppConfig.setValue('impersonate', 'impersonate_all_groupadmins', 'false');
		OC.AppConfig.setValue('impersonate', 'impersonate_include_groups', 'false');
		OC.AppConfig.setValue('impersonate', 'impersonate_include_groups_list', JSON.stringify([]));
	});

	$("#impersonateIncludeGroups").change(function () {
		$("#selectIncludedGroups").removeClass('hidden');
		var val = $("#impersonateIncludeGroups").is(":checked");
		OC.AppConfig.setValue('impersonate', 'impersonate_include_groups', val);
		OC.AppConfig.setValue('impersonate', 'impersonate_all_groupadmins', 'false');
	});

	$("#impersonateAllGroupAdmins").change(function () {
		var val = $("#impersonateAllGroupAdmins").is(":checked");
		$("#selectIncludedGroups").addClass('hidden');
		OC.AppConfig.setValue('impersonate', 'impersonate_all_groupadmins', val);
		OC.AppConfig.setValue('impersonate', 'impersonate_include_groups', 'false');
		OC.AppConfig.setValue('impersonate', 'impersonate_include_groups_list', JSON.stringify([]));
	});

	$('#includedGroups').each(function (index, element) {
		OC.Settings.setupGroupsSelect($(element));
		$(element).change(function(ev) {
			var groups = ev.val || [];
			groups = JSON.stringify(groups);
			OC.AppConfig.setValue('impersonate', $(this).attr('name'), groups);
		});
	});
});

