<?php
/**
 * IPS Converters
 * Application Files
 * Interface Loader
 * Last Update: $Date: 2010-03-19 11:03:12 +0100(ven, 19 mar 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 437 $
 */

class app_class_convert
{
	function __construct( ipsRegistry $registry )
	{
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/interface_acp.php' );
	}
}