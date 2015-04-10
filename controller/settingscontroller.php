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


use OCP\AppFramework\Http\JSONResponse;
use \OCP\IRequest;
use \OCP\AppFramework\Controller;
use OCP\IUserManager;
use OCP\IUserSession;

class SettingsController extends Controller {

    /**
     * @var IUserManager
     */
    private $userManager;
    /**
     * @var IUserSession
     */
    private $userSession;

    public function __construct($appName, IRequest $request, IUserManager $userManager, IUserSession $userSession){
        parent::__construct($appName, $request);
        $this->userManager = $userManager;
        $this->userSession = $userSession;
    }



    /**
     * become another user
     * @UseSession
     */
    public function impersonate($userid) {
        $user = $this->userManager->get($userid);
        $this->userSession->setUser($user);
        return new JSONResponse();
    }


}