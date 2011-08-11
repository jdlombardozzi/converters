<?php
/**
 * IPS Converters
 * Application Files
 * Sends use to the correct conversion page
 * Last Update: $Date: 2009-08-24 11:09:44 +0200(lun, 24 ago 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 352 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_convert_setup_switch extends ipsCommand
{

	/**
    * Main class entry point
	* Sends use to the correct conversion page
    *
    * @access	public
    * @param	object		ipsRegistry
    * @return	void
    */
    public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
        // Load HTML
        //-----------------------------------------
		
        $this->html = $this->registry->output->loadTemplate( 'cp_skin_convert' );
		
		//-----------------------------------------
		// Grab settings
		//-----------------------------------------
		
		$app = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'conv_apps', 'where' => "name='{$this->settings['conv_current']}'" ) );
		
		if (!$app)
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=convert&module=setup&section=setup' );
		}
		
		//-----------------------------------------
		// Test DB connection
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $this->registry );
		
		if($app['db_host']) // Don't do the check if this converter doesn't use a database
		{
			$test = $this->lib->test_connect($app);
			if ($test !== true)
			{
				if (!$test)
				{
					$test = 'An unknown error occurred. Check database details';
				}
				
				$this->registry->output->html .= $this->html->convertError('<strong>Database error</strong><br />'.$test.'<br /><br /><a href="'.$this->settings['base_url'] . 'app=convert&module=setup&section=manage&do=edit&id='.$app['app_id'].'">Reconfigure</a>');
				$this->sendOutput();
				exit;
			}
		}
		
		//-----------------------------------------
		// Send us on
		//-----------------------------------------

		if (file_exists(IPS_ROOT_PATH.'/applications_addon/ips/convert/modules_admin/'.$app['sw'].'/'.$app['app_key'].'.php'))
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=convert&module='.$app['sw'].'&section='.$app['app_key'] );
		}
		else
		{
			$this->registry->output->html .= $this->html->convertError('Invalid converter selected - reset system.');
			$this->sendOutput();
			exit;
		}
		
		//-----------------------------------------
        // Pass to CP output hander
        //-----------------------------------------
		
		$this->sendOutput();
				
	}
	
	/**
    * Output to screen and exit
    *
    * @access	private
    * @return	void
    */
	private function sendOutput()
	{
		$this->registry->output->html .= $this->html->convertFooter();
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
		
}	

?>