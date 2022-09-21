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

namespace OCA\Impersonate;

use OC\Authentication\Token\DefaultTokenProvider;
use OC\User\Session;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;

class Util {
	/** @var ISession  */
	private $session;
	/** @var DefaultTokenProvider  */
	private $tokenProvider;
	/** @var Session  */
	private $userSession;
	/** @var IRequest  */
	private $request;

	/**
	 * Util constructor.
	 *
	 * @param ISession $session
	 * @param Session $userSession
	 * @param IRequest $request
	 * @param DefaultTokenProvider $tokenProvider
	 */
	public function __construct(
		ISession $session,
		Session $userSession,
		IRequest $request,
		DefaultTokenProvider $tokenProvider
	) {
		$this->session = $session;
		$this->tokenProvider = $tokenProvider;
		$this->userSession = $userSession;
		$this->request = $request;
	}

	/**
	 * Switch from admin/subAdmin $impersonator to $user (and vice versa).
	 * $impersonator is empty in case the impersonator switches back to their profile.
	 * The impersonator must be given via the $user attribute then.
	 *
	 * @param IUser $user
	 * @param mixed $impersonator
	 */
	public function switchUser(IUser $user, $impersonator) {
		$this->tokenProvider->invalidateToken($this->session->getId());
		$this->userSession->setUser($user);
		$this->userSession->createSessionToken($this->request, $user->getUID(), $user->getUID());

		if ($impersonator !== null) {
			// Store the session var impersonator with the impersonator value
			if ($this->session->get('impersonator') === null) {
				$this->session->set('impersonator', $impersonator);
			}

			// Save the impersonator's encryption key in another session variable to reset it again later.
			// This is necessary because the "privateKey" var gets removed on logout.
			$encryptionInitialized = $this->session->get('encryptionInitialized');
			if ($encryptionInitialized !== null) {
				$this->session->set('impersonatorEncryptionInitialized', $encryptionInitialized);
			}

			$privateKey = $this->session->get('privateKey');
			if ($privateKey !== null) {
				$this->session->set('impersonatorPrivateKey', $privateKey);
			}
		} else {
			// Remove the session variable impersonator set as it is a logout
			$this->session->remove('impersonator');

			// Reset encryption key for the impersonator.
			$impersonatorEncryptionInitialized = $this->session->get('impersonatorEncryptionInitialized');
			if ($impersonatorEncryptionInitialized !== null) {
				$this->session->set('encryptionInitialized', $impersonatorEncryptionInitialized);
				$this->session->remove('impersonatorEncryptionInitialized');
			}

			$impersonatorPrivateKey = $this->session->get('impersonatorPrivateKey');
			if ($impersonatorPrivateKey !== null) {
				$this->session->set('privateKey', $impersonatorPrivateKey);
				$this->session->remove('impersonatorPrivateKey');
			}
		}
	}
}
