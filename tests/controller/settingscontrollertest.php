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
use OCP\ILogger;
use OCP\IUserManager;
use OCP\IUserSession;
use Test\TestCase;

class SettingsControllerTest extends TestCase {

	/** @var IUserManager */
	private $userManager;
	/** @var IUserSession */
	private $userSession;
	/** @var SettingsController */
	private $controller;
	/** @var ILogger */
	private $logger;

	public function setUp() {
		$request = $this->getMockBuilder(
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
			'impersonate',
			$request,
			$this->userManager,
			$this->userSession,
			$this->logger
		);
	}

	public function testImpersonateNotFound() {
		$this->userManager->expects($this->once())
			->method('search')
			->with('notexisting@uid', 1, 0)
			->will($this->returnValue([]));

		$this->userSession->expects($this->never())
			->method('setUser');

		$this->assertEquals(
			new JSONResponse(),
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
		$user = $this->getMock('\OCP\IUser');
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue($uid));

		$this->userManager->expects($this->once())
			->method('search')
			->with($query, 1, 0)
			->will($this->returnValue([$user]));

		$this->userSession->expects($this->once())
			->method('setUser')
			->with($user);

		$this->assertEquals(
			new JSONResponse(),
			$this->controller->impersonate($query)
		);
	}

}