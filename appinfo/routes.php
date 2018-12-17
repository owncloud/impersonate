<?php
/**
 * ownCloud - impersonate
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @copyright Jörn Friedrich Dreyer 2015
 */

use OCP\AppFramework\App;

$application = new App('impersonate');
$application->registerRoutes(
	$this,
	[
		'routes' => [

			// Land in users setting page ( for admin user only )
			[
				'name' => 'Settings#impersonate',
				'url' => '/user',
				'verb' => 'POST',
			],

			// Land in admin section to add settings
			[
				'name' => 'admin_settings#impersonateAdminTemplate',
				'url'  => '/settings/impersonatetemplate',
				'verb' => 'POST',
			],

			//Land in index page
			[
				'name' => 'Logout#logoutcontroller',
				'url' => '/logout',
				'verb' => 'POST',
			],

			//Get the data to validate for the app
			[
				'name' => 'Settings#getDataForImpersonateApp',
				'url' => '/getimpersonatedata',
				'verb' => 'GET',
			]

		],
	]
);
