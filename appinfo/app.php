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

// --- register js for user management------------------------------------------
if ($user = \OC::$server->getUserSession()->getUser()) {
	if (\OC::$server->getGroupManager()->isAdmin($user->getUID())) {
		\OCP\Util::addScript('impersonate', 'impersonate');
	}
}