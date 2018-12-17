<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Impersonate\Tests\Lib;

use OC\Authentication\Token\DefaultTokenProvider;
use OC\User\Session;
use OCA\Impersonate\Util;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use Test\TestCase;

class UtilTest extends TestCase {
	/** @var  ISession | \PHPUnit_Framework_MockObject_MockObject */
	private $session;
	/** @var  DefaultTokenProvider | \PHPUnit_Framework_MockObject_MockObject */
	private $tokenProvider;
	/** @var  Session | \PHPUnit_Framework_MockObject_MockObject */
	private $userSession;
	/** @var  IRequest | \PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var  Util */
	private $util;
	public function setUp() {
		$this->session = $this->createMock(ISession::class);
		$this->tokenProvider = $this->createMock(DefaultTokenProvider::class);
		$this->userSession = $this->createMock(Session::class);
		$this->request = $this->createMock(IRequest::class);
		return parent::setUp();
	}

	public function impersonator() {
		return [
			[null],
			['admin']
			];
	}

	/**
	 * @dataProvider impersonator
	 * @param $impersonator
	 */
	public function testSwitchUser($impersonator) {
		$user = $this->createMock(IUser::class);
		$this->util = new Util($this->session, $this->userSession, $this->request, $this->tokenProvider);
		if ($impersonator !== null) {
			$this->session
				->method('get')
				->willReturn($impersonator);
		}
		$this->util->switchUser($user, $impersonator);
		if ($impersonator === null) {
			$this->assertEquals($this->session->exists('impersonator'), false);
		} else {
			$this->assertEquals($this->session->get('impersonator'), $impersonator);
		}
	}
}
