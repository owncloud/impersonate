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

namespace OCA\Impersonate\Tests\Controller;

use OCA\Impersonate\Controller\SettingsController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\IUserSession;
use Test\TestCase;

/**
 * Class SettingsControllerTest
 * @group DB
 */

class SettingsControllerTest extends TestCase {

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

	public function setUp() {
		$this->appName = 'impersonate';
		$this->request = $this->getMockBuilder(
			'\OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->userManager = $this->getMockBuilder(
			'\OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder(
			'\OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder(
			'\OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new SettingsController(
			$this->appName,
			$this->request,
			$this->userManager,
			$this->userSession,
			$this->logger
		);

		parent::setUp();
	}

	public function testImpersonateNotFound() {
		$user = $this->createMock('OCP\IUser');
		$user->method('getUID')
			->willReturn('admin');
		$this->userSession
			->method('getUser')
			->willReturn($user);

		$this->userSession->expects($this->never())
			->method('setUser');

		$this->assertEquals(
			new JSONResponse([
				'error' => 'userNotFound',
				'message' => "No user found for notexisting@uid"
			], Http::STATUS_NOT_FOUND),
			$this->controller->impersonate('notexisting@uid')
		);
	}

	public function usersProvider() {
		return [
			['username', 'username'],
			['UserName', 'username']
		];
	}
	/**
	 * @dataProvider usersProvider
	 * @param $query
	 * @param $uid
	 */
	public function testImpersonate($query, $uid) {
		$user = $this->createMock('\OCP\IUser');
		$user->method('getUID')
			->willReturn($uid);

		$this->userSession
			->method('getUser')
			->willReturn($user);

		$this->userManager->expects($this->at(0))
			->method('get')
			->with($query)
			->willReturn($user);

		$user->expects($this->once())
			->method('getLastLogin')
			->willReturn(1);

		$this->userSession->expects($this->once())
			->method('setUser')
			->with($user);

		$this->assertEquals(
			new JSONResponse(),
			$this->controller->impersonate($query)
		);
	}

	public function normalUsers() {
		return [
			['username', 'username'],
			['UserName', 'username']
		];
	}

	/**
	 * @dataProvider normalUsers
	 * @param $query
	 */

	public function testAdminImpersonateNormalUsers($query,$uid) {
		$loggedInUser = $this->createMock('OCP\IUser');
		$loggedInUser
			->expects($this->once())
			->method('getUID')
			->willReturn('admin');

		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($loggedInUser);

		$userManager = $this->getMockBuilder('OC\User\Manager')
			->disableOriginalConstructor()
			->getMock();

		$userManager->expects($this->any())
			->method('createUser')
			->with($query,'123');

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);

		$this->userManager->expects($this->at(0))
			->method('get')
			->with($query)
			->willReturn($user);

		$this->userSession->expects($this->once())
			->method('setUser')
			->with($user);

		$user->expects($this->once())
			->method('getLastLogin')
			->willReturn(1);

		$this->assertEquals(
			new JSONResponse(),
			$this->controller->impersonate($query)
		);
	}

	public function neverLoggedIn() {
		return [
			['UserName', 'username']
		];
	}

	/**
	 * @dataProvider neverLoggedIn
	 * @param $query
	 * @param $uid
	 */

	public function testImpersonateNeverLoggedInUser($query, $uid) {
		$user = $this->createMock('\OCP\IUser');
		$user->method('getUID')
			->willReturn($uid);

		$this->userSession
			->method('getUser')
			->willReturn($user);

		$this->userManager->expects($this->at(0))
			->method('get')
			->with($query)
			->willReturn($user);

		$user->expects($this->once())
			->method('getLastLogin')
			->willReturn(0);

		$this->assertEquals(
			new JSONResponse(['error' => "userNeverLoggedIn",
				'message' => "Cannot impersonate user " . '"' . $query . '"' . " who hasn't logged in yet."
			], http::STATUS_NOT_FOUND),
			$this->controller->impersonate($query)
		);
	}

}

