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

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Page\UserImpersonatePage;
use Page\OwncloudPage;
use PHPUnit\Framework\Assert;

require_once 'bootstrap.php';

/**
 * WebUI Impersonate context.
 */
class WebUIImpersonateContext extends RawMinkContext implements Context {
	private ?FeatureContext $featureContext = null;
	private UserImpersonatePage $userImpersonatePage;
	private OwncloudPage $owncloudPage;

	/**
	 * WebUIImpersonateContext constructor.
	 *
	 * @param UserImpersonatePage $userImpersonatePage
	 * @param OwncloudPage $owncloudPage
	 */
	public function __construct(
		UserImpersonatePage $userImpersonatePage,
		OwncloudPage $owncloudPage
	) {
		$this->userImpersonatePage = $userImpersonatePage;
		$this->owncloudPage = $owncloudPage;
	}

	/**
	 * @When the user/administrator impersonates user :user using the webUI
	 *
	 * @param string $user
	 *
	 * @return void
	 * @throws JsonException
	 * @throws Exception
	 */
	public function administratorImpersonatesUserUsingTheWebui(string $user):void {
		$username = $this->featureContext->getActualUsername($user);
		$this->userImpersonatePage->impersonateUser($this->getSession(), $username);
	}

	/**
	 * @Then the administrator/user should be redirected to the files page of user :username
	 *
	 * @param string $username
	 *
	 * @return void
	 * @throws JsonException
	 * @throws Exception
	 */
	public function administratorShouldBeRedirectedToFilesPageOfUser(string $username):void {
		$expectedUsername = $this->featureContext->getActualUsername($username);
		Assert::assertEquals(
			$this->userImpersonatePage->userShouldBeInTheirPage($expectedUsername),
			true,
			__METHOD__
			. "User was supposed to be redirected to their own page but they aren't"
		);
	}

	/**
	 * @Then impersonate notification should be displayed on the webUI with the text :message
	 *
	 * @param string $message
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function impersonateNotificationShouldBeDisplayedOnTheWebuiWithTheText(string $message):void {
		$notification = $this->userImpersonatePage->getImpersonationNotificationText();
		Assert::assertEquals(
			$message,
			$notification,
			__METHOD__
			. " A notification was expected to be displayed on the webUI with the text '$message', but got '"
			. $notification
			. "' instead"
		);
	}

	/**
	 * Re-defining this step instead of using the existing one because the existing one expects the
	 * user to be in log in page after user logs out but in case of impersonation the impersonating person
	 * is navigated to their own files page
	 *
	 * @When the impersonated user logs out of the webUI
	 *
	 * @return void
	 */
	public function theImpersonatedUserLogsOutOfTheWebui(): void {
		$session = $this->getSession();
		$settingsMenu = $this->owncloudPage->openSettingsMenu($session);
		$settingsMenu->logout();
	}

	/**
	 * @Then :user should be navigated back to their own account
	 *
	 * @param string $user
	 *
	 * @throws Exception
	 */
	public function theUserShouldBeNavigatedBackToTheirOwnAccount(string $user) {
		if ($user === 'admin') {
			$expectedUsername = $this->featureContext->getAdminUsername();
		} else {
			$expectedUsername =  $this->featureContext->getActualUsername($user);
		}
		Assert::assertEquals(
			$this->userImpersonatePage->userShouldBeInTheirPage($expectedUsername),
			true,
			__METHOD__
			."User was supposed to be redirected to their own page but they aren't"
		);
	}

	/**
	* This will run before EVERY scenario.
	* It will set the properties for this object.
	*
	* @BeforeScenario
	*
	* @param BeforeScenarioScope $scope
	*
	* @return void
	*/
	public function before(BeforeScenarioScope $scope) {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->featureContext = $environment->getContext('FeatureContext');
	}
}
