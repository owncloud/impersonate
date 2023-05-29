<?php
script('impersonate', 'settings-admin');
style('impersonate', 'settings-admin');
?>
<div class="section" id="impersonateTemplateSettings" >

	<h2><?php p($l->t('Impersonate Settings'));?></h2>

	<p class="<?php if (\OC::$server->getAppConfig()->getValue('impersonate', 'enabled', 'no') === 'no') {
		p('hidden');
	}?>">
		<input type="radio" name="groupadmins" id="impersonateNoGroupAdmins" class="radio"
			<?php if (\OC::$server->getAppConfig()->getValue('impersonate', 'impersonate_all_groupadmins', 'false') === 'false' && \OC::$server->getAppConfig()->getValue('impersonate', 'impersonate_include_groups', 'false') === 'false') {
				print_unescaped('checked="checked"');
			} ?> />
		<label for="impersonateNoGroupAdmins"><?php p($l->t('Only an administrator is allowed to impersonate users'))?></label><br/>

		<input type="radio" name="groupadmins" id="impersonateAllGroupAdmins" class="radio"
			<?php if (\OC::$server->getAppConfig()->getValue('impersonate', 'impersonate_all_groupadmins', 'false') !== 'false') {
				print_unescaped('checked="checked"');
			} ?> />
		<label for="impersonateAllGroupAdmins"><?php p($l->t('Allow all group admins to impersonate users within the groups they are admins of'))?></label><br/>

		<input type="radio" name="groupadmins" id="impersonateIncludeGroups" class="radio"
			<?php if (\OC::$server->getAppConfig()->getValue('impersonate', 'impersonate_include_groups', 'false') !== 'false') {
				print_unescaped('checked="checked"');
			} ?> />
		<label for="impersonateIncludeGroups"><?php p($l->t('Allow group admins of specific groups to impersonate the users within those groups'));?></label><br/>

	</p>
	<p id="selectIncludedGroups" class="indent <?php if (\OC::$server->getAppConfig()->getValue('impersonate', 'impersonate_include_groups', 'false') === 'false') {
		p('hidden');
	} ?>">
		<input name="impersonate_include_groups_list" type="hidden" id="includedGroups" value="<?php
			$includeGroupList = \OC::$server->getAppConfig()->getValue('impersonate', 'impersonate_include_groups_list', "[]");
$includeGroupList = \json_decode($includeGroupList);
$listToPrint = \count($includeGroupList) > 0 ? \implode('|', $includeGroupList) : '';
p($listToPrint);
?>" style="width: 400px"/>
		<br />
	</p>

</div>

