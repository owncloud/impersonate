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

use OC\Authentication\Token\DefaultTokenProvider;
use OC\SubAdmin;
use OCA\Impersonate\Util;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\ISession;
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
	/** @var DefaultTokenProvider  */
	private $tokenProvider;
	/** @var \OC\User\Session  */
	private $ocUserSession;
	/** @var \OCA\Impersonate\Util  */
	private $util;

	/**
	 * SettingsController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param ILogger $logger
	 * @param IGroupManager $groupManager
	 * @param SubAdmin $subAdmin
	 * @param ISession $session
	 * @param IAppConfig $config
	 * @param IL10N $l10n
	 * @param DefaultTokenProvider $tokenProvider
	 */
	public function __construct($appName, IRequest $request, IUserManager $userManager,
				IUserSession $userSession, ILogger $logger, IGroupManager $groupManager,
				SubAdmin $subAdmin, ISession $session, IAppConfig $config, IL10N $l10n,
				DefaultTokenProvider $tokenProvider) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->subAdmin = $subAdmin;
		$this->session = $session;
		$this->config = $config;
		$this->l = $l10n;
		$this->tokenProvider = $tokenProvider;
		$this->ocUserSession = \OC::$server->getUserSession();
		$this->util = new Util($this->session, $this->ocUserSession, $this->request, $tokenProvider);
	}

	/**
	 *  Get the data for Impersonate app
	 *  @NoAdminRequired
	 *
	 *  @return JSONResponse
	 */
	public function getDataForImpersonateApp() {
		$isEnabled = $this->config->getValue('impersonate','impersonate_include_groups',false);
		$includedGroups = $this->config->getValue('impersonate','impersonate_include_groups_list','[]');
		$currentUser = $this->userSession->getUser();
		if ($currentUser === null) {
			return new JSONResponse([$includedGroups, $isEnabled,
				false, false]);
		}

		return new JSONResponse([$includedGroups, $isEnabled,
			$this->groupManager->isAdmin($currentUser->getUID()),
			$this->subAdmin->isSubAdmin($currentUser)]);
	}

	/**
	 * become another user
	 * @param string $target
	 * @UseSession
	 * @NoAdminRequired
	 * @return JSONResponse
	 */
	public function impersonate($target) {
		$currentUser = $this->userSession->getUser();

		//If there is no current user don't impersonate
		if ($currentUser === null) {
			return new JSONResponse([
				'error' => 'cannotImpersonate',
				'message' => $this->l->t('Can not impersonate'),
			], http::STATUS_NOT_FOUND);
		}

		$impersonator = $currentUser->getUID();

		if ($this->session->get('impersonator') === null) {
			$this->session->set('impersonator', $impersonator);
		} else {
			return new JSONResponse([
				'error' => 'stopNestedImpersonation',
				'message' => $this->l->t('Can not impersonate'),
			], http::STATUS_NOT_FOUND);
		}

		$user = $this->userManager->get($target);
		if ($user === null) {
			$this->logger->info("User $target doesn't exist. User $impersonator cannot impersonate $target");
			$this->session->remove('impersonator');
			return new JSONResponse([
				'error' => 'userNotFound',
				'message' => $this->l->t('Unexpected error occured'),
			], Http::STATUS_NOT_FOUND);
		} elseif ($user->getLastLogin() === 0) {
			// It's a first time login
			$this->logger->info("User $target did not logged in yet. User $impersonator cannot impersonate $target");
			$this->session->remove('impersonator');
			return new JSONResponse([
				'error' => 'userNeverLoggedIn',
				'message' => $this->l->t('Can not impersonate'),
			], http::STATUS_NOT_FOUND);
		} elseif ($this->groupManager->isAdmin($target) && !$this->groupManager->isAdmin($impersonator)) {
			// If not an admin then no impersonation
			$this->logger->warning('Can not allow user "' . $impersonator . '" trying to impersonate "'. $target . '"');
			$this->session->remove('impersonator');
			return new JSONResponse([
				'error' => 'cannotImpersonateAdminUser',
				'message' => $this->l->t('Can not impersonate'),
			], http::STATUS_NOT_FOUND);
		} else {

			if ($this->groupManager->isAdmin($currentUser->getUID())) {
				$this->logger->info("User $impersonator impersonated user $target", ['app' => 'impersonate']);
				$this->util->switchUser($user, $impersonator);
				return new JSONResponse();
			}

			$includedGroups = $this->config->getValue('impersonate','impersonate_include_groups_list','');
			if ($includedGroups !== '') {
				$includedGroups = json_decode($includedGroups);

				foreach ($includedGroups as $group) {
					if($this->groupManager->get($group)->inGroup($user)
						&& $this->subAdmin->isSubAdminofGroup($this->userSession->getUser(), $this->groupManager->get($group))) {
						$this->logger->info("User $impersonator impersonated user $target", ['app' => 'impersonate']);
						$this->util->switchUser($user, $impersonator);
						return new JSONResponse();
					}
				}
			}

			$this->session->remove('impersonator');
			return new JSONResponse([
				'error' => 'cannotImpersonate',
				'message' => $this->l->t('Can not impersonate'),
			], http::STATUS_NOT_FOUND);
		}
	}
}

