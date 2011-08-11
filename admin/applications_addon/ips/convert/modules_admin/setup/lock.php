<?php
/**
 * IPS Converters
 * Application Files
 * Locks the converters
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

class admin_convert_setup_lock extends ipsCommand
{

	/**
    * Main class entry point
	* Locks the converters 
    *
    * @access	public
    * @param	object		ipsRegistry
    * @return	void
    */
    public function doExecute( ipsRegistry $registry )
	{
		
        $this->html = $this->registry->output->loadTemplate( 'cp_skin_convert' );

		if ( @file_put_contents( DOC_IPS_ROOT_PATH . 'cache/converter_lock.php', 'Just out of interest, what did you expect to see here?' ) )
		{
			$this->registry->output->html .= $this->html->convertError('The converters have been locked.');
		}
		else
		{
			$this->registry->output->html .= $this->html->convertError('The converters were <strong>NOT</strong> locked - you should uninstall the application and delete the admin/applications_addon/ips/convert folder.');
		}
		
		$this->sendOutput();
		exit;
				
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