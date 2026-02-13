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
use OCA\Impersonate\Controller\SettingsController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IAppConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\ISession;
use OC\Group\Backend;
use Symfony\Component\EventDispatcher\GenericEvent;
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
	/** @var  IL10N */
	private $l;
	private $defaultTokenProvider;
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
			'\OCP\IUserSession'
		)
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder(
			'\OCP\ILogger'
		)
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
		$this->l = $this->getMockBuilder(IL10N::class)
			->getMock();
		$this->l->method('t')->will($this->returnCallback(function ($text) {
			return $text;
		}));
		$this->defaultTokenProvider = $this->createMock(\OC\Authentication\Token\DefaultTokenProvider::class);
		$this->util = $this->createMock(\OCA\Impersonate\Util::class);

		$this->controller = new SettingsController(
			$this->appName,
			$this->request,
			$this->userManager,
			$this->userSession,
			$this->logger,
			$this->groupManger,
			$this->subAdmin,
			$this->session,
			$this->config,
			$this->l,
			$this->defaultTokenProvider,
			$this->util
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
				'message' => $this->l->t("Unexpected error occurred")
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
			$this->config
				->method('getValue')
				->will($this->returnValueMap([
					['impersonate', 'enabled', "no", "yes"]
				]));
			//This user belongs to admin user
			$this->groupManger->expects($this->any())
				->method('isAdmin')
				->willReturn(true);

			$this->assertEquals(
				new JSONResponse(),
				$this->controller->impersonate($query)
			);
		} elseif ($group === 'groupadmin') {
			$this->config
				->method('getValue')
				->will($this->returnValueMap([
					['impersonate', 'enabled', "no", "yes"],
					['impersonate', 'impersonate_include_groups_list', "", \json_encode([$group])],
					['impersonate', 'impersonate_all_groupadmins', "false", "false"]
				]));

			$iGroup = $this->createMock(IGroup::class);

			$this->groupManger->expects($this->any())
				->method('get')
				->willReturn($iGroup);

			$this->groupManger->expects($this->any())
				->method('isInGroup')
				->willReturn(true);

			$this->subAdmin->expects($this->any())
				->method('isSubAdminofGroup')
				->willReturn(true);

			$calledAfterImpersonate = [];
			\OC::$server->getEventDispatcher()->addListener(
				'user.afterimpersonate',
				function (GenericEvent $event) use (&$calledAfterImpersonate) {
					$calledAfterImpersonate[] = 'user.afterimpersonate';
					$calledAfterImpersonate[] = $event;
				}
			);
			$this->assertEquals(
				new JSONResponse(),
				$this->controller->impersonate($query)
			);
			$this->assertEquals('user.afterimpersonate', $calledAfterImpersonate[0]);
			$this->assertInstanceOf(GenericEvent::class, $calledAfterImpersonate[1]);
			$this->assertArrayHasKey('impersonator', $calledAfterImpersonate[1]);
			$this->assertArrayHasKey('targetUser', $calledAfterImpersonate[1]);
			$this->assertEquals('username', $calledAfterImpersonate[1]->getArgument('impersonator'));
			$this->assertEquals('Username', $calledAfterImpersonate[1]->getArgument('targetUser'));
		} elseif ($group === 'normaluser') {
			$this->config
				->method('getValue')
				->will($this->returnValueMap([
					['impersonate', 'enabled', "no", "yes"],
					['impersonate', 'impersonate_include_groups_list', "", ""],
					['impersonate', 'impersonate_all_groupadmins', "false", "false"]
				]));

			$this->groupManger->expects($this->any())
				->method('isAdmin')
				->willReturn(false);

			$this->assertEquals(
				new JSONResponse([
					'error' => "cannotImpersonate",
					'message' => $this->l->t("Can not impersonate"),
				], http::STATUS_NOT_FOUND),
				$this->controller->impersonate($query)
			);
		}
	}

	/**
	 * Negative test to verify a scenario:
	 * 1) Login as group admin
	 * 2) Try to impersonate a user who is not part of
	 * the group which is owned by group admin
	 */
	public function testUserNotPartOfGroup() {
		$user = $this->createMock('\OCP\IUser');

		$this->userSession
			->method('getUser')
			->willReturn($user);

		$user->method('getUID')
			->willReturn('username');

		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->with('Username')
			->willReturn($user);

		$user->expects($this->once())
			->method('getLastLogin')
			->willReturn(1);

		$this->config
			->method('getValue')
			->will($this->returnValueMap([
				['impersonate', 'enabled', "no", "yes"],
				['impersonate', 'impersonate_include_groups_list', "", \json_encode(['testgroup'])],
				['impersonate', 'impersonate_all_groupadmins', "false", "false"]
			]));

		$iGroup = $this->createMock(IGroup::class);

		$this->groupManger->expects($this->any())
			->method('get')
			->willReturn($iGroup);

		$this->groupManger->expects($this->any())
			->method('isInGroup')
			->willReturn(true);

		$this->subAdmin->expects($this->any())
			->method('isSubAdminofGroup')
			->willReturn(false);

		$this->assertEquals(
			new JSONResponse([
				'error' => 'cannotImpersonate',
				'message' => $this->l->t('Can not impersonate'),
			], http::STATUS_NOT_FOUND),
			$this->controller->impersonate('Username')
		);
	}

	/**
	 * Test to verify, that a vanished group will not break impersonation
	 * https://github.com/owncloud/impersonate/issues/118
	 */
	public function testWronglyConfiguredGroupListAllowsImpersonation() {
		$user = $this->createMock('\OCP\IUser');

		$this->userSession
			->method('getUser')
			->willReturn($user);

		$user->method('getUID')
			->willReturn('username');

		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->with('Username')
			->willReturn($user);

		$user->expects($this->once())
			->method('getLastLogin')
			->willReturn(1);

		$this->config
			->method('getValue')
			->will($this->returnValueMap([
				['impersonate', 'enabled', "no", "yes"],
				['impersonate', 'impersonate_include_groups_list', "", \json_encode(['testgroup','testgroup2'])],
				['impersonate', 'impersonate_all_groupadmins', "false", "false"]
			]));

		$iGroup = $this->createMock(IGroup::class);

		$this->groupManger->expects($this->any())
			->method('get')
			->will(
				$this->returnValueMap(
					[
					['testgroup', null],
					['testgroup2', $iGroup]
					]
				)
			);

		$this->groupManger->expects($this->any())
			->method('isInGroup')
			->will(
				$this->returnValueMap(
					[
						['username','testgroup', false],
						['username','testgroup2', true]
					]
				)
			);

		$this->subAdmin->expects($this->any())
			->method('isSubAdminofGroup')
			->willReturn(true);

		$calledAfterImpersonate = [];
		\OC::$server->getEventDispatcher()->addListener(
			'user.afterimpersonate',
			function (GenericEvent $event) use (&$calledAfterImpersonate) {
				$calledAfterImpersonate[] = 'user.afterimpersonate';
				$calledAfterImpersonate[] = $event;
			}
		);
		$this->assertEquals(
			new JSONResponse(),
			$this->controller->impersonate('Username')
		);
		$this->assertEquals('user.afterimpersonate', $calledAfterImpersonate[0]);
		$this->assertInstanceOf(GenericEvent::class, $calledAfterImpersonate[1]);
		$this->assertArrayHasKey('impersonator', $calledAfterImpersonate[1]);
		$this->assertArrayHasKey('targetUser', $calledAfterImpersonate[1]);
		$this->assertEquals('username', $calledAfterImpersonate[1]->getArgument('impersonator'));
		$this->assertEquals('Username', $calledAfterImpersonate[1]->getArgument('targetUser'));
	}

	/**
	 * When there is no user logged in or if the session is null,
	 * then no impersonation should be done. This test validates it.
	 */
	public function testNullUserSession() {
		$this->userSession
			->method('getUser')
			->willReturn(null);

		$this->assertEquals(
			new JSONResponse([
				'error' => 'cannotImpersonate',
				'message' => $this->l->t('Can not impersonate'),
			], http::STATUS_NOT_FOUND),
			$this->controller->impersonate('foo')
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

		$this->userManager->expects($this->once())
			->method('get')
			->with($query)
			->willReturn($user);

		$user->expects($this->once())
			->method('getLastLogin')
			->willReturn(0);

		$this->assertEquals(
			new JSONResponse(['error' => "userNeverLoggedIn",
				'message' => $this->l->t("Can not impersonate")
			], http::STATUS_NOT_FOUND),
			$this->controller->impersonate($query)
		);
	}

	public function adminAndGroupAdminUsers() {
		return [
			['admin', 'admin', 'subadmin', 'subadmin']
		];
	}

	/**
	 * @dataProvider adminAndGroupAdminUsers
	 * @param $adminUser
	 * @param $adminUid
	 * @param $subadminUser
	 * @param $subadminUid
	 */
	public function testRestrictSwitchToAdminUser($adminUser, $adminUid, $subadminUser, $subadminUid) {
		$user = $this->createMock('\OCP\IUser');
		$user->method('getUID')
			->willReturn($subadminUid);

		$this->userSession
			->method('getUser')
			->willReturn($user);

		$this->userManager->expects($this->once())
			->method('get')
			->with($adminUser)
			->willReturn($user);

		$user->expects($this->once())
			->method('getLastLogin')
			->willReturn(1);

		$this->groupManger
			->expects($this->exactly(2))
			->method('isAdmin')
			->withConsecutive(
				[$adminUser],
				[$subadminUser],
			)
			->willReturnOnConsecutiveCalls(true, false);

		$this->assertEquals(
			new JSONResponse(['error' => "cannotImpersonateAdminUser",
				'message' => $this->l->t("Can not impersonate")
				], http::STATUS_NOT_FOUND),
			$this->controller->impersonate($adminUser)
		);
	}

	public function groupAdminUsers() {
		return [
			['subadmin', 'subadmin']
		];
	}

	/**
	 * @dataProvider groupAdminUsers
	 * @param $subadminUser
	 * @param $subadminUid
	 */
	public function testRestrictNestedImpersonate($subadminUser, $subadminUid) {
		$user = $this->createMock('\OCP\IUser');
		$user->method('getUID')
			->willReturn($subadminUid);

		$this->userSession
			->method('getUser')
			->willReturn($user);

		$this->session
			->method('get')
			->willReturn('foo');

		$this->assertEquals(
			new JSONResponse(['error' => "stopNestedImpersonation",
			'message' => $this->l->t("Can not impersonate")
		], http::STATUS_NOT_FOUND),
			$this->controller->impersonate('bar')
		);
	}

	/**
	 * @dataProvider providesGetDataForImpersonateApp
	 */
	public function testGetDataForImpersonateApp($enabled, $includedGroups, $allowAllSubadminGroups, $currentUser, $isAdmin = false, $isSubAdmin = false) {
		$map = [
			['impersonate','impersonate_include_groups', false, $enabled],
			['impersonate','impersonate_include_groups_list', '[]', $includedGroups],
			['impersonate', 'impersonate_all_groupadmins', false, $allowAllSubadminGroups]
		];
		$this->config
			->method('getValue')
			->will($this->returnValueMap($map));

		if ($currentUser === null) {
			$user = null;
		} else {
			$user = $this->createMock('\OCP\IUser');
		}
		$this->userSession
			->method('getUser')
			->willReturn($user);
		if ($currentUser === null) {
			$user = null;
			$this->assertEquals(
				new JSONResponse([
					$includedGroups, $enabled, $allowAllSubadminGroups, false, false
				]),
				$this->controller->getDataForImpersonateApp('test')
			);
		} else {
			$user->expects($this->once())
				->method('getUID')
				->willReturn($currentUser);
			$this->groupManger
				->method('isAdmin')
				->willReturn($isAdmin);
			$this->subAdmin
				->method('isSubAdmin')
				->willReturn($isSubAdmin);
			$this->assertEquals(
				new JSONResponse([
					$includedGroups, $enabled, $allowAllSubadminGroups, $isAdmin, $isSubAdmin
				]),
				$this->controller->getDataForImpersonateApp('test')
			);
		}
	}

	public function providesGetDataForImpersonateApp() {
		return [
			[true, ['hello', 'world'], "true", null],
			[true, ['hello', 'world'], "false", 'user', true, true],
			[true, ['hello', 'world'], "false",'user', true, false],
			[true, ['hello', 'world'], "false", 'user', false, true],
			[false, ['hello', 'world'], "false", 'user', false, true],
			[false, [], "true", 'user', false, true],
		];
	}

	public function testImpersonateGrantAllSubadmin() {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn('foo');

		$this->userSession
			->method('getUser')
			->willReturn($user);

		$targetUser = $this->createMock(IUser::class);
		$targetUser->method('getUID')->willReturn('baro');
		$targetUser->method('getLastLogin')
			->willReturn(1);

		$this->userManager->method('get')
			->willReturn($targetUser);

		$this->groupManger->method('isAdmin')
			->willReturn(false);

		$group1 = $this->createMock(IGroup::class);
		$group1->method('getGID')->willReturn('group1');
		$group2 = $this->createMock(IGroup::class);
		$group2->method('getGID')->willReturn('group2');
		$this->subAdmin->method('getSubAdminsGroups')
			->with($this->equalTo($user))
			->willReturn([$group1, $group2]);

		$this->groupManger->method('isInGroup')->willReturnMap([
			['baro', 'group1', true],
			['baro', 'group2', false],
		]);

		$this->config
			->method('getValue')
			->will($this->returnValueMap([
				['impersonate', 'enabled', "no", "yes"],
				['impersonate', 'impersonate_include_groups_list', "", ""],
				['impersonate', 'impersonate_all_groupadmins', "false", "true"]
			]));

		$this->assertEquals(
			new JSONResponse(),
			$this->controller->impersonate('foo')
		);
	}

	public function testImpersonateAppDisabled() {
		$this->config
			->method('getValue')
			->will($this->returnValueMap([
				['impersonate', 'enabled', "no", "no"],
			]));
		
		$this->userSession->expects($this->never())
			->method('getUser');

		$this->assertEquals(
			new JSONResponse(['error' => "cannotImpersonate",
				'message' => $this->l->t("Can not impersonate. Please contact your server administrator to allow impersonation.")
			], http::STATUS_NOT_FOUND),
			$this->controller->impersonate('foo')
		);
	}

	public function testImpersonateAppEnabledForTargetUser() {
		$query='NormalUser';
		$uid='username';
		$group='app_enabled_group';

		$this->config
			->method('getValue')
			->will($this->returnValueMap([
				['impersonate', 'enabled', "no", \json_encode([$group])],
				['impersonate', 'impersonate_all_groupadmins', "false", "true"]
			]));
		
		$user = $this->createMock('\OCP\IUser');

		$user->method('getUID')
			->willReturn($uid);

		$user->expects($this->once())
			->method('getLastLogin')
			->willReturn(1);

		$this->userSession
			->method('getUser')
			->willReturn($user);
	
		$this->userManager->expects($this->atLeastOnce())
			->method('get')
			->with($query)
			->willReturn($user);

		$group1 = $this->createMock(IGroup::class);
		$group1->method('getGID')->willReturn('group1');
		$group2 = $this->createMock(IGroup::class);
		$group2->method('getGID')->willReturn('group2');
		$this->subAdmin->method('getSubAdminsGroups')
			->with($this->equalTo($user))
			->willReturn([$group1, $group2]);

		$this->groupManger->expects($this->any())
			->method('isAdmin')
			->willReturn(false);

		$this->groupManger->expects($this->any())
			->method('isInGroup')
			->will($this->returnValueMap([
				[$query, $group, true],
				[$uid, 'group1', true],
				[$uid, 'group2', false],
			]));

		$this->session->expects($this->never())
			->method('remove');

		$this->assertEquals(
			new JSONResponse(),
			$this->controller->impersonate($query)
		);
	}
}
