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

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
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

	public function __construct($appName, IRequest $request, IUserManager $userManager, IUserSession $userSession, ILogger $logger) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
	}

	/**
	 * become another user
	 * @param string $userid
	 * @UseSession
	 * @return JSONResponse
	 */
	public function impersonate($userid) {
		$oldUserId = $this->userSession->getUser()->getUID();
		\OC::$server->getSession()->set('oldUserId',$oldUserId);
		$this->logger->warning("User $oldUserId trying to impersonate user $userid", ['app' => 'impersonate']);

		$user = $this->userManager->get($userid);
		\OC::$server->getSession()->set('newUserId',$userid);
		if ($user === null) {
			return new JSONResponse("No user found for $userid", Http::STATUS_NOT_FOUND);
		} else {
			$this->logger->warning("changing to user $userid", ['app' => 'impersonate']);
			$this->userSession->setUser($user);
		}
		return new JSONResponse();
	}
}

