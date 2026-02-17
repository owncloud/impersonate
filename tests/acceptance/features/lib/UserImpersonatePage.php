<?php declare(strict_types=1);

/**
 * ownCloud
 *
 * @author Swikriti Tripathi <swikriti@jankaritech.com>
 * @copyright Copyright (c) 2022 Swikriti Tripathi swikriti@jankaritech.com
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License,
 * as published by the Free Software Foundation;
 * either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Page;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;
use Exception;

/**
 * Impersonate user page.
 */
class UserImpersonatePage extends UsersPage {
	/**
	 *
	 * @var string $path
	 */
	protected $path = '/index.php/settings/users';
	protected string $impersonateXpath = './/a[@class="action permanent impersonate"]';
	protected string $userXpath = '//span[contains(@id,"expandDisplayName") and text()="%s"]';
	protected string $impersonateNotificationXpath = '//div[@id="notification-container"]//div[@id="impersonate-notification"]/a';

	/**
	 * @param Session $session
	 * @param string $user
	 *
	 * @throws Exception
	 */
	public function impersonateUser(Session $session, string $user):void {
		$userFromTable = $this->findUserInTable($user);
		$impersonateButton = $userFromTable->find('xpath', $this->impersonateXpath);
		$this->assertElementNotNull(
			$impersonateButton,
			__METHOD__ . " could not find element with the xpath '$this->impersonateXpath'"
		);
		$impersonateButton->click();
		$this->waitForAjaxCallsToStartAndFinish($session);
	}

	/**
	 * @param string $username
	 *
	 * @return bool
	 *
	 * @throws Exception
	 */
	public function userShouldBeInTheirPage(string $username): bool {
		$userFieldElement = $this->waitTillXpathIsVisible(sprintf($this->userXpath, $username));
		if ($userFieldElement && $userFieldElement->isVisible()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return string
	 *
	 * @throws Exception
	 */
	public function getImpersonationNotificationText(): string {
		$notificationElement = $this->find('xpath', $this->impersonateNotificationXpath);
		$this->assertElementNotNull(
			$notificationElement,
			__METHOD__ . " could not find element with the xpath '$this->impersonateNotificationXpath'"
		);
		return $this->getTrimmedText($notificationElement);
	}
}
