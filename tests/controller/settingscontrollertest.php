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

use OC\SubAdmin;
use OCA\Impersonate\Controller\SettingsController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\ISession;
use OC\Group\Backend;
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
	/** @var  IGroupManager */
	private $groupManger;
	/** @var  SubAdmin */
	private $subAdmin;
	/** @var  ISession */
	private $session;
	/** @var IAppConfig  */
	private $config;

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
		$this->groupManger = $this->getMockBuilder(IGroupManager::class)
			->getMock();
		$this->subAdmin  = $this->getMockBuilder(SubAdmin::class)
			->disableOriginalConstructor()
			->getMock();
		$this->session = $this->getMockBuilder(ISession::class)
			->getMock();
		$this->config = $this->getMockBuilder(IAppConfig::class)
			->getMock();

		$this->controller = new SettingsController(
			$this->appName,
			$this->request,
			$this->userManager,
			$this->userSession,
			$this->logger,
			$this->groupManger,
			$this->subAdmin,
			$this->session,
			$this->config
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
			['username', 'username', 'admin'],
			['Username', 'username', 'groupadmin'],
			['NormalUser', 'username', 'normaluser']
		];
	}
	/**
	 * @dataProvider usersProvider
	 * @param $query
	 * @param $uid
	 */
	public function testImpersonate($query, $uid, $group) {
		$user = $this->createMock('\OCP\IUser');

		$this->userSession
			->method('getUser')
			->willReturn($user);

		$user->method('getUID')
			->willReturn($uid);

		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->with($query)
			->willReturn($user);

		$user->expects($this->once())
			->method('getLastLogin')
			->willReturn(1);

		if ($group === 'admin') {
			//This user belongs to admin user
			$this->groupManger->expects($this->any())
				->method('isAdmin')
				->willReturn(true);

			$this->userSession->expects($this->once())
				->method('setUser')
				->with($user);

			$this->assertEquals(
				new JSONResponse(),
				$this->controller->impersonate($query)
			);
		} elseif ($group === 'groupadmin') {
			$this->config->expects($this->once())
				->method('getValue')
				->with('impersonate','impersonate_include_groups_list',"")
				->willReturn(json_encode([$group]));

			$this->groupManger->expects($this->once())
				->method('get')
				->willReturn($this->createMock('OCP\IGroup'));

			$this->subAdmin->expects($this->any())
				->method('isSubAdminofGroup')
				->willReturn(true);

			$this->userSession->expects($this->once())
				->method('setUser')
				->with($user);

			$this->assertEquals(
				new JSONResponse(),
				$this->controller->impersonate($query)
			);

		} elseif ($group === 'normaluser') {
			$this->config->expects($this->once())
				->method('getValue')
				->with('impersonate','impersonate_include_groups_list',"")
				->willReturn("");

			$this->groupManger->expects($this->any())
				->method('isAdmin')
				->willReturn(false);

			$this->assertEquals(
				new JSONResponse([
					'error' => "cannotImpersonate",
					'message' => "Cannot impersonate user <$query>",
				], http::STATUS_NOT_FOUND),
				$this->controller->impersonate($query)
			);
		}
	}

	public function normalUsers() {
		return [
			['username', 'username'],
			['UserName', 'username']
		];
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
				'message' => "Cannot impersonate user <$query> who hasn't logged in yet."
			], http::STATUS_NOT_FOUND),
			$this->controller->impersonate($query)
		);
	}
}

