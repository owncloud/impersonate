<?php

namespace OCA\Impersonate\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;


class LogoutController extends Controller {
    /** @var IUserManager */
    private $userManager;
    /** @var IUserSession */
    private $userSession;
    /** @var ILogger */
    private $logger;

    /**
     * @NoAdminRequired
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
     *  @NoAdminRequired
     *
     *  @param string userid
     *  @UseSession
     *  @return JSONResponse
     */
    public function logoutcontroller($userid) {
		$user = \OC::$server->getSession()->get('oldUserId');
		$user = $this->userManager->get($user);

        if($user === null) {
            return new JSONResponse("No user found for $userid", Http::STATUS_NOT_FOUND);
        } else {
			$this->userSession->setUser($user);
			$this->logger->info("Switching back to previous user $userid", ['app' => 'impersonate']);
			//Resume the logout
			\OC::$server->getSession()->remove('oldUserId');
		}
		return new JSONResponse();
	}
}
