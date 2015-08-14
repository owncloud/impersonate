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

\OC_Util::checkAdminUser();

\OCP\Util::addScript('impersonate', 'impersonate');

$tmpl = new \OCP\Template('impersonate', 'settings/admin');

return $tmpl->fetchPage();
