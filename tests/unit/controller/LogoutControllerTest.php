<?php
/**
 * ownCloud - impersonate
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @copyright Jörn Friedrich Dreyer 2015
 */

namespace OCA\Impersonate\Tests\Controller;

use OC\SubAdmin;
use OCA\Impersonate\Controller\LogoutController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OC\Group\Backend;
use Symfony\Component\EventDispatcher\GenericEvent;
use Test\TestCase;

/**
 * Class LogoutControllerTest
 * @group DB
 */

class LogoutControllerTest extends TestCase {
	/** @var string */
	private $appName;
	/** @var IRequest */
	private $request;
	/** @var IUserManager */
	private $userManager;
	/** @var IUserSession */
	private $userSession;
	/** @var SettingsController */
	private $controller;
	/** @var ILogger */
	private $logger;
	/** @var ISession  */
	private $session;
	private $eventDispatcher;
	private $tokenProvider;
	private $util;

	public function setUp(): void {
		$this->appName = 'impersonate';
		$this->request = $this->getMockBuilder(
			'\OCP\IRequest'
		)
			->disableOriginalConstructor()
			->getMock();
		$this->userManager = $this->getMockBuilder(
			'\OCP\IUserManager'
		)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder(
			'\OC\User\Session'
		)
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder(
			'\OCP\ILogger'
		)
			->disableOriginalConstructor()
			->getMock();
		$this->session = $this->getMockBuilder('OCP\ISession')
			->disableOriginalConstructor()
			->getMock();
		$this->tokenProvider = $this->createMock(\OC\Authentication\Token\DefaultTokenProvider::class);
		$this->util = $this->createMock(\OCA\Impersonate\Util::class);

		$this->controller = new LogoutController(
			$this->appName,
			$this->request,
			$this->userManager,
			$this->userSession,
			$this->logger,
			$this->session,
			$this->tokenProvider,
			$this->util
		);

		parent::setUp();
	}

	public function userSessionData() {
		return [
			[null],
			['impersonator']
		];
	}

	/**
	 * @dataProvider userSessionData
	 * @param $userId
	 */
	public function testImpersonateLogout($userId) {
		$genericEvent = new GenericEvent(null, ['cancel' => false]);
		if ($userId === null) {
			$this->session->expects($this->once())
				->method('get')
				->willReturn(null);

			$this->assertEquals(
				new JSONResponse([
					'error' => "cannotLogout",
					'message' => "Cannot logout"
				], Http::STATUS_NOT_FOUND),
				$this->controller->logoutcontroller($genericEvent)
			);
		} else {
			$this->session->expects($this->any())
				->method('get')
				->willReturn('impersonator');

			$currentUser = $this->createMock(IUser::class);
			$this->userManager->expects($this->any())
				->method('get')
				->willReturn($this->createMock('OCP\IUser'));
			$this->userSession->expects($this->any())
				->method('getUser')
				->willReturn($currentUser);
			$currentUser->expects($this->any())
				->method('getUID')
				->willReturn('foo');

			$calledAfterLogout = [];
			\OC::$server->getEventDispatcher()->addListener(
				'user.afterimpersonatelogout',
				function (GenericEvent $event) use (&$calledAfterLogout) {
					$calledAfterLogout[] = 'user.afterimpersonatelogout';
					$calledAfterLogout[] = $event;
				}
			);
			$this->assertEquals(
				new JSONResponse(),
				$this->controller->logoutcontroller($genericEvent)
			);
			$this->assertEquals('user.afterimpersonatelogout', $calledAfterLogout[0]);
			$this->assertInstanceOf(GenericEvent::class, $calledAfterLogout[1]);
			$this->assertArrayHasKey('impersonator', $calledAfterLogout[1]);
			$this->assertArrayHasKey('targetUser', $calledAfterLogout[1]);
			$this->assertEquals('impersonator', $calledAfterLogout[1]->getArgument('impersonator'));
			$this->assertEquals('foo', $calledAfterLogout[1]->getArgument('targetUser'));
		}
	}

	/**
	 * Test to verify if the current user session is not there then no logout
	 */
	public function testMissingCurrentUserSession() {
		$genericEvent = new GenericEvent(null, ['cancel' => false]);
		$this->session->expects($this->any())
			->method('get')
			->willReturn('impersonator');

		$this->userManager->expects($this->any())
			->method('get')
			->willReturn($this->createMock('OCP\IUser'));
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn(null);

		$this->assertEquals(
			new JSONResponse([
				'error' => 'currentUserUnavailable',
				'message' => 'Cannot logout'
			]),
			$this->controller->logoutcontroller($genericEvent)
		);
	}
}
