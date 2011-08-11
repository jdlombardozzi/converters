<?php
/**
 * IPS Converters
 * Application Files
 * Redirects user to configuration or conversion page
 * Last Update: $Date: 2009-06-26 13:10:57 +0200(ven, 26 giu 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 331 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_convert_setup_boink extends ipsCommand
{

	/**
    * Main class entry point
	* Redirects user to configuration or conversion page 
    *
    * @access	public
    * @param	object		ipsRegistry
    * @return	void
    */
    public function doExecute( ipsRegistry $registry )
	{
		if ( file_exists( DOC_IPS_ROOT_PATH . 'cache/converter_lock.php' ) )
		{
			ipsRegistry::getClass('output')->showError( 'The converters have been locked. To unlock, delete the cache/converter_lock.php file.' );
		}
		
		if ($this->settings['conv_current'])
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=convert&module=setup&section=switch' );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=convert&module=setup&section=setup' );
		}				
	}		
}	

?>