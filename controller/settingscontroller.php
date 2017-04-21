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

namespace OCA\Impersonate\Controller;

use OC\Group\Manager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;


class SettingsController extends Controller {

	/** @var IUserManager */
	private $userManager;
	/** @var IUserSession */
	private $userSession;
	/** @var ILogger */
	private $logger;

	/**
	 * SettingsController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param ILogger $logger
	 */
	public function __construct($appName, IRequest $request, IUserManager $userManager, IUserSession $userSession, ILogger $logger) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
	}

	/**
	 *  Get the data for Impersonate app
	 *  @NoAdminRequired
	 *
	 *  @return JSONResponse
	 */
	public function getDataForImpersonateApp() {
		$isEnabled = \OC::$server->getAppConfig()->getValue('impersonate','impersonate_include_groups',false);
		$includedGroups = \OC::$server->getAppConfig()->getValue('impersonate','impersonate_include_groups_list',"[]");
		return new JSONResponse([$includedGroups, $isEnabled,
			\OC::$server->getGroupManager()->isAdmin($this->userSession->getUser()->getUID()),
			\OC::$server->getGroupManager()->getSubAdmin()->isSubAdmin($this->userSession->getUser())]);
	}

	/**
	 * become another user
	 * @param string $userid
	 * @UseSession
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 * @return JSONResponse
	 */
	public function impersonate($userid) {
		$oldUserId = $this->userSession->getUser()->getUID();
		if(\OC::$server->getSession()->get('oldUserId') === null) {
			\OC::$server->getSession()->set('oldUserId', $oldUserId);
		}
		$this->logger->warning("User $oldUserId trying to impersonate user $userid", ['app' => 'impersonate']);

		$user = $this->userManager->get($userid);
		if ($user === null) {
			return new JSONResponse("No user found for $userid", Http::STATUS_NOT_FOUND);
		} else {
			$this->logger->warning("changing to user $userid", ['app' => 'impersonate']);
			$this->userSession->setUser($user);
		}
		return new JSONResponse();
	}
}

