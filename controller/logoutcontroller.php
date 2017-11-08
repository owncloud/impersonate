<?php

namespace OCA\Impersonate\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
    /** @var EventDispatcher  */
    private $eventDispatcher;

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
	 * @param EventDispatcher $eventDispatcher
	 */

    public function __construct($appName, IRequest $request,IUserManager $userManager, IUserSession $userSession, ILogger $logger, ISession $session) {
		parent::__construct($appName, $request);
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->session = $session;
		$this->eventDispatcher = \OC::$server->getEventDispatcher();
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

		if($impersonatorUser === null) {
			return new JSONResponse([
				'error' => "cannotLogout",
				'message' => "Cannot logout"
			], Http::STATUS_NOT_FOUND);
		} else {
			$this->userSession->setUser($impersonatorUser);
			$this->logger->info("Switching back to previous user $impersonator", ['app' => 'impersonate']);
			//Resume the logout
			$this->session->remove('impersonator');
			$event->setArgument('cancel', true);
		}

		return new JSONResponse();
	}

}
