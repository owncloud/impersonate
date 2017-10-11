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
use OC\SubAdmin;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\ISession;
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
	/** @var IGroupManager  */
	private $groupManager;
	/** @var SubAdmin  */
	private $subAdmin;
	/** @var  ISession */
	private $session;
	/** @var IAppConfig  */
	private $config;
	/** @var IL10N  */
	private $l;

	/**
	 * SettingsController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param ILogger $logger
	 */
	public function __construct($appName, IRequest $request, IUserManager $userManager,
				IUserSession $userSession, ILogger $logger, IGroupManager $groupManager,
				SubAdmin $subAdmin, ISession $session, IAppConfig $config, IL10N $l10n) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->subAdmin = $subAdmin;
		$this->session = $session;
		$this->config = $config;
		$this->l = $l10n;
	}

	/**
	 *  Get the data for Impersonate app
	 *  @NoAdminRequired
	 *
	 *  @return JSONResponse
	 */
	public function getDataForImpersonateApp() {
		$isEnabled = $this->config->getValue('impersonate','impersonate_include_groups',false);
		$includedGroups = $this->config->getValue('impersonate','impersonate_include_groups_list',"[]");

		return new JSONResponse([$includedGroups, $isEnabled,
			$this->groupManager->isAdmin($this->userSession->getUser()->getUID()),
			$this->subAdmin->isSubAdmin($this->userSession->getUser())]);
	}

	/**
	 * become another user
	 * @param string $target
	 * @UseSession
	 * @NoAdminRequired
	 * @return JSONResponse
	 */
	public function impersonate($target) {
		$impersonator = $this->userSession->getUser()->getUID();

		if ($this->session->get('impersonator') === null) {
			$this->session->set('impersonator', $impersonator);
		} else {
			return new JSONResponse([
				'error' => 'stopNestedImpersonation',
				'message' => $this->l->t("Can not impersonate"),
			], http::STATUS_NOT_FOUND);
		}

		$user = $this->userManager->get($target);
		if ($user === null) {
			$this->logger->info("User $target doesn't exist. User $impersonator cannot impersonate $target");
			$this->session->remove('impersonator');
			return new JSONResponse([
				'error' => 'userNotFound',
				'message' => $this->l->t("Unexpected error occured"),
			], Http::STATUS_NOT_FOUND);
		} elseif ($user->getLastLogin() === 0) {
			// It's a first time login
			$this->logger->info("User $target did not logged in yet. User $impersonator cannot impersonate $target");
			$this->session->remove('impersonator');
			return new JSONResponse([
				'error' => "userNeverLoggedIn",
				'message' => $this->l->t("Can not impersonate"),
			], http::STATUS_NOT_FOUND);
		} elseif ($this->groupManager->isAdmin($target) && !$this->groupManager->isAdmin($impersonator)) {
			// If not an admin then no impersonation
			$this->logger->warning('Can not allow user "' . $impersonator . '" trying to impersonate "'. $target . '"');
			$this->session->remove('impersonator');
			return new JSONResponse([
				'error' => "cannotImpersonateAdminUser",
				'message' => $this->l->t("Can not impersonate"),
			], http::STATUS_NOT_FOUND);
		} else {

			if ($this->groupManager->isAdmin($this->userSession->getUser()->getUID())) {
				$this->logger->info("User $impersonator impersonated user $target", ['app' => 'impersonate']);
				$this->userSession->setUser($user);
				return new JSONResponse();
			}

			$includedGroups = $this->config->getValue('impersonate','impersonate_include_groups_list',"");
			if ($includedGroups !== "") {
				$includedGroups = json_decode($includedGroups);

				foreach ($includedGroups as $group) {
					if($this->subAdmin->isSubAdminofGroup($this->userSession->getUser(), $this->groupManager->get($group))) {
						$this->logger->info("User $impersonator impersonated user $target", ['app' => 'impersonate']);
						$this->userSession->setUser($user);
						return new JSONResponse();
					}
				}
			}

			$this->session->remove('impersonator');
			return new JSONResponse([
				'error' => "cannotImpersonate",
				'message' => $this->l->t("Can not impersonate"),
			], http::STATUS_NOT_FOUND);
		}
	}
}

