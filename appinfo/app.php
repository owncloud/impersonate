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

if (\OC::$server->getSession()->get('impersonator') !== null) {
	\OCP\Util::addScript('impersonate', 'impersonate_logout');
	\OCP\Util::addScript('impersonate', 'templates/impersonateNotification.handlebars');
	\OCP\Util::addStyle('impersonate', 'impersonate');
}
// --- register js for user management------------------------------------------
$eventDispatcher = \OC::$server->getEventDispatcher();
$eventDispatcher->addListener(
	'OC\Settings\Users::loadAdditionalScripts',
	function () {
		\OCP\Util::addScript('impersonate', 'impersonate');
		\OCP\Util::addScript('impersonate', 'templates/addImpersonateIcon.handlebars');
		\OCP\Util::addScript('impersonate', 'templates/removeImpersonateIcon.handlebars');
	}
);
/** @phan-suppress-next-line PhanUndeclaredClassMethod */
$logoutController = new OCA\Impersonate\Controller\LogoutController(
	'impersonate',
	\OC::$server->getRequest(),
	\OC::$server->getUserManager(),
	OC::$server->getUserSession(),
	OC::$server->getLogger(),
	OC::$server->getSession(),
	\OC::$server->query('\OC\Authentication\Token\DefaultTokenProvider'),
	new \OCA\Impersonate\Util(
		\OC::$server->getSession(),
		\OC::$server->getUserSession(),
		\OC::$server->getRequest(),
		\OC::$server->query('\OC\Authentication\Token\DefaultTokenProvider')
	)
);
/** @phan-suppress-next-line PhanUndeclaredClassInCallable */
$eventDispatcher->addListener('\OC\User\Session::pre_logout', [$logoutController, 'logoutcontroller']);
