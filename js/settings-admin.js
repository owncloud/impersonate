$(document).ready(function () {
	$("#impersonateIncludeGroups").change(function () {
		$("#selectIncludedGroups").toggleClass('hidden', !this.checked);
		var val = $("#impersonateIncludeGroups").is(":checked");
		OC.AppConfig.setValue('impersonate',$(this).attr('name'),val);
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

