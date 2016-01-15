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
			[
				'name' => 'Settings#impersonate',
				'url' => '/user',
				'verb' => 'POST',
			],
		],
	]
);