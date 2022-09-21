<?php declare(strict_types = 1);
/**
 * ownCloud
 *
 * @author Kiran Adhikari <kiran.adhikari@jankaritech>
 *
 * @copyright Copyright (c) 2022, JankariTech
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use TestHelpers\SetupHelper;
use TestHelpers\HttpRequestHelper;
use GuzzleHttp\Client;

require_once 'bootstrap.php';

/**
 * Context for Impersonate app
 */
class ImpersonateAppContext implements Context {
	/**
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 * @var OccUsersGroupsContext
	 */
	private $occUsersGroupsContext;

	/**
	 * @var int
	 */
	private $impersonateResponseCode;

	/**
	 * Returns base url for the impersonate app
	 *
	 * @return string
	 */
	public function getImpersonateUrl(): string {
		return "/index.php/apps/impersonate/user";
	}

	/**
	 * Reset the response code
	 *
	 * @return void
	 */
	public function resetImpersonateResponseCode(): void {
		$this->impersonateResponseCode = null;
	}

	/**
	 * Get the commands to obtain desired configuration
	 *
	 * @param string $option
	 * @return array
	 * @throws Exception
	 */
	public function getOccCommandsForImpersonateOption(string $option, ?string $value = ""): array {
		$commandsArray = [
			"allow only an admin" => "config:app:set impersonate impersonate_all_groupadmins --value false",
			"allow all group admins" => "config:app:set impersonate impersonate_include_groups --value false",
			"only group admins of specific groups" => "config:app:set impersonate impersonate_include_groups_list --value '[]'"
		];
		if (!isset($commandsArray[$option])) {
			throw new Exception("Invalid option");
		}

		if ($option === "allow all group admins") {
			$commandsArray[$option] = "config:app:set impersonate impersonate_all_groupadmins --value true";
		}

		if ($option === "only group admins of specific groups") {
			$string = "\"".implode("\",\"", (explode(", ", $value)))."\"";
			$commandsArray[$option] = "config:app:set impersonate impersonate_include_groups_list --value '[$string]'";
		}

		return $commandsArray;
	}

	/**
	 * Create a user with given username, password and group
	 * @param string $username
	 * @param string $password
	 * @param string $group
	 *
	 * @return void
	 * @throws Exception
	 */
	public function createUserWithGroup(string $username, string $password, string $group): void {
		$user = $this->featureContext->getActualUsername($username);
		$actualPassword = $this->featureContext->getActualPassword($password);
		$cmd = "user:add $user  --password-from-env --group=$group";
		$args = [$cmd];
		$this->featureContext->runOccWithEnvVariables(
			$args,
			['OC_PASS' => $actualPassword]
		);

		$this->featureContext->addUserToCreatedUsersList(
			$user,
			$password,
			null,
			null,
			null,
		);
		$this->featureContext->addGroupToCreatedGroupsList($group);
	}

	/**
	 * @Given /^the administrator has created following users:$/
	 * expects a table of users data
	 * | username | password | groupname | role |
	 * this is using provisioning api
	 *
	 * @param TableNode $table
	 *
	 * @return void
	 */
	public function createUserWithGroupAndRole(TableNode $table): void {
		$this->featureContext->verifyTableNodeColumns($table, ['username', 'password', 'group'], ['role']);

		foreach ($table as $row) {
			$username = $row['username'];
			$password = $row['password'];
			$group = $row['group'];

			$this->createUserWithGroup($username, $password, $group);

			if ($row['role'] === "group-admin") {
				$this->featureContext->userHasBeenMadeSubadminOfGroup(
					$username,
					$group
				);
			}
		}
	}

	/**
	 * @Given /"([^"]*)" option in impersonate settings has been set to "([^"]*)"$/
   *
	 * @param string $option
	 * @param string $value
	 */
	public function setImpersonateSettings(string $option, ?string $value = ""): void {
		$occCommandsArray = $this->getOccCommandsForImpersonateOption($option, $value);
		foreach ($occCommandsArray as $occCommand) {
			$args = [$occCommand];
			$this->featureContext->runOccWithEnvVariables(
				$args,
			);
		}
	}

	/**
	 * @When /^"([^"]*)" sends a request to impersonate user "([^"]*)"$/
	 *
	 * @param string $requester
	 * @param string $impersonateUser
	 * @return void
	 */
	public function sendRequestToImpersonateUser(string $requester, string $impersonateUser): void {
		$requestData = [
				"target" => $this->featureContext->getActualUsername($impersonateUser)
			];

		if ($requester === "admin") {
			$userName =  $this->featureContext->getAdminUsername();
			$password = $this->featureContext->getAdminPassword();
		} else {
			$userName = $this->featureContext->getActualUsername($requester);
			$password = $this->featureContext->getActualPassword($requester);
		}
		$response = null;
		$client = new Client([
			'auth' => [$userName, $password],
		]);
		$response = null;
		try {
			$response= HttpRequestHelper::post($this->featureContext->getBaseUrl().$this->getImpersonateUrl(), '', $userName, $password, null, $requestData);
		} catch (Exception $e) {
			$response = $e->getResponse();
		} finally {
			$this->featureContext->setResponse($response);
		}
	}

	/**
	 * @Then /^the status code of impersonate action should be "([^"]*)"$/
	 *
	 * @param string $successStatus
	 * @return void
	 */
	public function checkStatusCodeOfImpersonateApp(int $statusCode): void {
		if ($this->impersonateResponseCode !== $statusCode) {
			throw new Exception("Expected status code $statusCode but got $this->impersonateResponseCode");
		}
		$this->resetImpersonateResponseCode();
	}

	/**
	 * @BeforeScenario
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 * @throws Exception
	 */
	public function setUpScenario(BeforeScenarioScope $scope): void {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->featureContext = $environment->getContext('FeatureContext');
		SetupHelper::init(
			$this->featureContext->getAdminUsername(),
			$this->featureContext->getAdminPassword(),
			$this->featureContext->getBaseUrl(),
			$this->featureContext->getOcPath()
		);
	}
}
