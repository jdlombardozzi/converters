<?php
/**
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Remote API integration gateway file
 * Last Updated: $Date: 2010-07-22 11:29:06 +0200(gio, 22 lug 2010) $
 *
 * @author 		$Author: terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.invisionpower.com
 * @version		$Rev: 447 $
 *
 */

if ( !isset($_SERVER['argc']) || $_SERVER['argc']=='0')
{
	print "<h1>Incorrect access</h1>This script must be accessed from command line. You have incorrectly accessed this file.";
	exit();
}

define( 'IPS_IS_SHELL', TRUE );
define( 'IPB_THIS_SCRIPT', 'admin' );
define( 'IPS_CLI_MEMORY_DEBUG', FALSE );
define( 'IPS_DEFAULT_APP', 'convert' );

// Will need to change admin to whatever
// the value is in initdata for CP_DIRECTORY
$_SERVER['PHP_SELF'] = '/' . 'admin';

require_once( '../../initdata.php' );

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );

$registry = ipsRegistry::instance();
$registry->init();

$registry->DB()->obj['use_shutdown'] = 0;
$registry->DB()->setDebugMode(0);

// Do CLI authorization.

// Run controller.
ipsController::run();

exit();

