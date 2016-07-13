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
if (OC_User::isAdminUser(OC_User::getUser())) {
	\OCP\Util::addScript('impersonate', 'impersonate');
}
