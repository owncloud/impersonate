<?php

\OC_Util::checkAdminUser();

\OCP\Util::addStyle('impersonate', 'settings-admin');
\OCP\Util::addScript('impersonate', 'settings-admin');

$tmpl = new OCP\Template( 'impersonate', 'settings-admin' );
return $tmpl->fetchPage();
