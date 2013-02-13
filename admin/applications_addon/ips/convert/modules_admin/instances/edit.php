<?php
/**
 * IPS Converters
 * Application Files
 * Manage Conversions
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

class admin_convert_instances_edit extends ipsCommand
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
			case 'delete':
				$this->delete();
				break;

			case 'edit':
				$this->edit();
				break;

			case 'edit_save':
				$this->edit_save();
				break;

			default:
				$this->show();
				break;
		}

		$this->registry->output->html .= $this->html->convertFooter();
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
		exit;
	}

	/**
    * Display the list of choices
    *
    * @access	private
    * @return	void
    */
	private function show()
	{
		$this->registry->output->html .= $this->html->convertHeader('Manage Conversions');

		$this->DB->build(array('select' => '*', 'from' => 'conv_apps'));
		$this->DB->execute();
		$apps = array();
		while ($r = $this->DB->fetch())
		{
			$apps[] = $this->html->convertAppTableRow($r);
		}

		$this->registry->output->html .= $this->html->convertAppTable(implode('', $apps));
	}

	/**
    * Edit - Show form
    *
    * @access	private
    * @return	void
    */
	private function edit()
	{
		$edit = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'conv_apps', 'where' => "app_id='{$this->request['id']}'" ) );
		if (!$edit)
		{
			$this->registry->output->html .= $this->html->convertError("Invalid ID");
			return;
		}

		@include_once IPS_ROOT_PATH.'applications_addon/ips/convert/modules_admin/'.$edit['sw'].'/'.$edit['app_key'].'.php';

		if ( $info['nodb'] )
		{
			$this->registry->output->html .= $this->html->convertEditAppCustom($custom);
			return;
		}
		$this->registry->output->html .= $this->html->convertEditApp($edit);
	}

	/**
    * Edit - Save
    *
    * @access	private
    * @return	void
    */
	private function edit_save()
	{
		$edit = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'conv_apps', 'where' => "app_id='{$this->request['id']}'" ) );
		if (!$edit)
		{
			$this->registry->output->html .= $this->html->convertError("Invalid ID");
			return;
		}

		@include_once IPS_ROOT_PATH.'applications_addon/ips/convert/modules_admin/'.$edit['sw'].'/'.$edit['app_key'].'.php';

		if ( $info['nodb'] && $this->request['custom'] )
		{
			$get = unserialize($this->settings['conv_extra']);
			$us = $get[ $edit['name'] ];
			$us = is_array($us) ? $us : array();
			$extra = is_array($us['core']) ? $us : array_merge($us, array('core' => array()));
			$get[ $edit['name'] ] = $extra;

			foreach($custom as $k => $v)
			{
				$get[ $edit['name'] ]['core'][$k] = $_REQUEST[$k];
			}

			IPSLib::updateSettings(array('conv_extra' => serialize($get)));
		}
		else
		{
			$this->DB->update('conv_apps', array( 'db_driver'	=> $this->request['hb_sql_driver'],
												  'db_host'	=> $this->request['hb_sql_host'],
												  'db_user'	=> $this->request['hb_sql_user'],
												  'db_pass'	=> $_REQUEST['hb_sql_pass'],
												  'db_db'		=> $this->request['hb_sql_database'],
												  'db_prefix'	=> $this->request['hb_sql_tbl_prefix'] ) ,"app_id='{$this->request['id']}'");
		}

		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=convert&module=configure&section=boink' );
	}

	/**
    * Delete
    *
    * @access	private
    * @return	void
    */
	private function delete()
	{
		$delete = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'conv_apps', 'where' => "app_id='{$this->request['id']}'" ) );
		if (!$delete)
		{
			$this->registry->output->html .= $this->html->convertError("Invalid ID");
			return;
		}

		$this->DB->delete('conv_apps', "app_id='{$this->request['id']}'");

		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=convert&module=configure&section=manage' );
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
		exit;
	}
}

?>