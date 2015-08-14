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

namespace OCA\Impersonate\AppInfo;

use OCP\AppFramework\App;
use OCA\Impersonate\Controller\SettingsController;


class Application extends App {

	public function __construct (array $urlParams=array()) {
		parent::__construct('impersonate', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('SettingsController', function($c) {
			return new SettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('OCP\IUserManager'),
				$c->query('OCP\IUserSession')
			);
		});

	}


}