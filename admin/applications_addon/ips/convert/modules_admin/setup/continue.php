<?php
/**
 * IPS Converters
 * Application Files
 * Allows user to select a conversion that has already started to continue
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

class admin_convert_setup_continue extends ipsCommand
{

	/**
    * Main class entry point
    *
    * @access	public
    * @param	object		ipsRegistry 
    * @return	void
    */
    public function doExecute( ipsRegistry $registry )
	{
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_convert' );
		
		switch ($this->request['do'])
		{
			case 'save':
				IPSLib::updateSettings(array('conv_current' => $this->request['choice']));
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=convert&module=setup&section=switch' );
				break;
				
			default:
				$this->show_choices();
				break;
		}
		
		$this->registry->output->html .= $this->html->convertFooter();
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
		exit;		
	}
	
	/**
    * Show choices
    *
    * @access	private
    * @return	void
    */
	private function show_choices()
	{
		$this->DB->build(array('select' => '*', 'from' => 'conv_apps'));
		$this->DB->execute();
		$options = array();
		while ($r = $this->DB->fetch())
		{
			$options[] = $this->html->convertAddOption(array('key' => $r['name'], 'name' => $r['name']));
		}
		$this->registry->output->html .= $this->html->convertContinue(implode('', $options));
	}
	
}	

?>