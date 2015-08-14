<?php
/**
 * ownCloud
 *
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

global $RUNTIME_NOAPPS;
$RUNTIME_NOAPPS = true;

define('PHPUNIT_RUN', 1);

require_once __DIR__.'/../../../lib/base.php';

if(!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
