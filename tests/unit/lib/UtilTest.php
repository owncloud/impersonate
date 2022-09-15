<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 * @author Jannik Stehle <jstehle@owncloud.com>
 *
 * @copyright Copyright (c) 2021, ownCloud GmbH
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
	/** @var ISession | \PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var DefaultTokenProvider | \PHPUnit\Framework\MockObject\MockObject */
	private $tokenProvider;
	/** @var Session | \PHPUnit\Framework\MockObject\MockObject */
	private $userSession;
	/** @var IRequest | \PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var Util */
	private $util;
	/** @var IUser | \PHPUnit\Framework\MockObject\MockObject */
	private $user;

	public function setUp(): void {
		$this->session = $this->createMock(ISession::class);
		$this->tokenProvider = $this->createMock(DefaultTokenProvider::class);
		$this->userSession = $this->createMock(Session::class);
		$this->request = $this->createMock(IRequest::class);
		$this->util = new Util($this->session, $this->userSession, $this->request, $this->tokenProvider);
		$this->user = $this->createMock(IUser::class);
		parent::setUp();
	}

	public function testSwitchUserWithImpersonator() {
		$impersonator = 'admin';
		$encryptionInitialized = 1;
		$privateKey = 'privateKey';
		$this->session
			->expects($this->exactly(3))
			->method('get')
			->withConsecutive(
				['impersonator'],
				['encryptionInitialized'],
				['privateKey'],
			)
			->willReturn(null, $encryptionInitialized, $privateKey);
		$this->session
			->expects($this->exactly(3))
			->method('set')
			->withConsecutive(
				['impersonator', $impersonator],
				['impersonatorEncryptionInitialized', $encryptionInitialized],
				['impersonatorPrivateKey', $privateKey],
			);
		$this->util->switchUser($this->user, $impersonator);
	}

	public function testSwitchUserWithoutImpersonator() {
		$encryptionInitialized = 1;
		$privateKey = 'privateKey';
		$this->session
			->expects($this->exactly(2))
			->method('get')
			->withConsecutive(
				['impersonatorEncryptionInitialized'],
				['impersonatorPrivateKey'],
			)
			->willReturn($encryptionInitialized, $privateKey);
		$this->session
			->expects($this->exactly(2))
			->method('set')
			->withConsecutive(
				['encryptionInitialized', $encryptionInitialized],
				['privateKey', $privateKey],
			);
		$this->session
			->expects($this->exactly(3))
			->method('remove')
			->withConsecutive(
				['impersonator'],
				['impersonatorEncryptionInitialized'],
				['impersonatorPrivateKey'],
			);
		$this->util->switchUser($this->user, null);
	}
}
