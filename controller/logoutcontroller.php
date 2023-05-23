<?php

namespace OCA\Impersonate\Controller;

use OC\Authentication\Token\DefaultTokenProvider;
use OC\User\Session;
use OCA\Impersonate\Util;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class LogoutController extends Controller {
	/** @var IUserManager */
	private $userManager;
	/** @var IUserSession */
	private $userSession;
	/** @var ILogger */
	private $logger;
	/** @var ISession  */
	private $session;
	/** @var EventDispatcher|EventDispatcherInterface  */
	private $eventDispatcher;
	private $tokenProvider;
	private $util;

	/**
	 * LogoutController constructor.
	 *
	 * @NoAdminRequired
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param ILogger $logger
	 * @param ISession $session
	 * @param DefaultTokenProvider $tokenProvider
	 * @param Util $util
	 */

	public function __construct(
		$appName,
		IRequest $request,
		IUserManager $userManager,
		IUserSession $userSession,
		ILogger $logger,
		ISession $session,
		DefaultTokenProvider $tokenProvider,
		Util $util
	) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->session = $session;
		$this->eventDispatcher = \OC::$server->getEventDispatcher();
		$this->tokenProvider = $tokenProvider;
		$this->util = $util;
	}

	/**
	 *  @NoAdminRequired
	 *
	 *  @UseSession
	 *  @return JSONResponse
	 */
	public function logoutcontroller(GenericEvent $event) {
		$impersonator = $this->session->get('impersonator');
		if ($impersonator === null) {
			return new JSONResponse([
				'error' => "cannotLogout",
				'message' => "Cannot logout"
			], Http::STATUS_NOT_FOUND);
		}
		$impersonatorUser = $this->userManager->get($impersonator);

		if ($impersonatorUser === null) {
			return new JSONResponse([
				'error' => "cannotLogout",
				'message' => "Cannot logout"
			], Http::STATUS_NOT_FOUND);
		} else {
			//Get the current user
			$currentUser = $this->userSession->getUser();
			if ($currentUser === null) {
				return new JSONResponse([
					'error' => 'currentUserUnavailable',
					'message' => 'Cannot logout'
				]);
			}
			$currentUser = $currentUser->getUID();
			$this->util->switchUser($impersonatorUser, null);
			$stopEvent = new GenericEvent(null, ['impersonator' => $impersonator, 'targetUser' => $currentUser]);
			$this->logger->info("Switching back to previous user $impersonator", ['app' => 'impersonate']);
			$event->setArgument('cancel', true);
			$this->eventDispatcher->dispatch($stopEvent, 'user.afterimpersonatelogout');
		}

		return new JSONResponse();
	}
}
