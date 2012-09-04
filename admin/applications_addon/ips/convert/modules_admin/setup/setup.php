<?php
/**
 * IPS Converters
 * Application Files
 * Sets up a conversion
 * Last Update: $Date: 2012-02-24 01:02:49 +0000 (Fri, 24 Feb 2012) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	© 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 624 $
 */


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_convert_setup_setup extends ipsCommand
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
		if ( file_exists( DOC_IPS_ROOT_PATH . 'cache/converter_lock.php' ) )
		{
			ipsRegistry::getClass('output')->showError( 'The converters have been locked. To unlock, delete the cache/converter_lock.php file.' );
		}

		$this->html = $this->registry->output->loadTemplate( 'cp_skin_convert' );

		switch ($this->request['do'])
		{
			case 'save':
				$this->select_old();
				break;

			case 'info':
				$this->get_more_info();
				break;

			case 'convert':
				$this->finish();
				break;

			default:
				$this->show_apps();
				break;
		}

		$this->registry->output->html .= $this->html->convertFooter();
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
		exit;
	}

	/**
    * Display the list of apps (board, gallery, etc)
    *
    * @access	private
    * @return	void
    */
	private function show_apps()
	{
		$this->registry->output->html .= $this->html->convertHeader('Start new conversion');

		$extra = '';
		if (is_dir(IPS_ROOT_PATH.'/applications_addon/ips/calendar'))
		{
			$extra .= $this->html->convertApp('calendar', 'IP.Calendar');
		}
		/*
		No longer an IPS product.
		if (is_dir(IPS_ROOT_PATH.'/applications_addon/other/subscriptions') && !IPSLib::appIsInstalled ( 'nexus' ))
		{
			$extra .= $this->html->convertApp('subscriptions', 'IP.Subscriptions');
		}*/
		if (is_dir(IPS_ROOT_PATH.'/applications_addon/ips/blog'))
		{
			$extra .= $this->html->convertApp('blog', 'IP.Blog');
		}
		if (is_dir(IPS_ROOT_PATH.'/applications_addon/ips/gallery'))
		{
			$extra .= $this->html->convertApp('gallery', 'IP.Gallery');
		}
		if (is_dir(IPS_ROOT_PATH.'/applications_addon/ips/downloads'))
		{
			$extra .= $this->html->convertApp('downloads', 'IP.Downloads');
		}
		if (is_dir(IPS_ROOT_PATH.'/applications_addon/ips/ccs'))
		{
			$extra .= $this->html->convertApp('ccs', 'IP.Content');
		}
		if (is_dir(IPS_ROOT_PATH.'/applications_addon/other/tracker'))
		{
			$extra .= $this->html->convertApp('tracker', 'IP.Tracker');
		}
		if (is_dir ( IPS_ROOT_PATH . '/applications_addon/ips/nexus' ) )
		{
			$extra .= $this->html->convertApp ( 'nexus', 'IP.Nexus' );
		}

		$this->registry->output->html .= $this->html->convertShowSoftware($extra);
	}

	/**
    * Display the choices for converting from
    *
    * @access	private
    * @return	void
    */
	private function select_old()
	{
		switch ($this->request['sw'])
		{
			case 'board':
				$name = 'IP.Board';
				break;

			case 'calendar':
				$name = 'IP.Calendar';
				break;

			case 'subscriptions':
				$name = 'IP.Subscriptions';
				break;
				
			case 'nexus':
				$name = 'IP.Nexus';
				break;

			case 'blog':
				$name = 'IP.Blog';
				break;

			case 'gallery':
				$name = 'IP.Gallery';
				break;

			case 'downloads':
				$name = 'IP.Downloads';
				break;

			case 'tracker':
				$name = 'IP.Tracker';
				break;

			case 'ccs':
				$name = 'IP.Content';
				break;

			default:
				$this->registry->output->html .= $this->html->convertError('Invalid application. Reset system.');
				$this->sendOutput();
				exit;
		}
		$this->registry->output->html .= $this->html->convertHeader($name.' Conversion Set Up');

		$options = array();
		foreach (glob(IPS_ROOT_PATH.'applications_addon/ips/convert/modules_admin/'.$this->request['sw'].'/*.php') as $file)
		{
			require_once $file;
			$options[] = $this->html->convertAddOption($info);
		}

		$this->registry->output->html .= $this->html->convertShowOptions1(implode('', $options));
	}

	/**
    * Ask for more information
    *
    * @access	private
    * @return	void
    */
	private function get_more_info()
	{
		if (!$this->request['app_name'])
		{
			$this->registry->output->html .= $this->html->convertError("You did not enter an ID for the conversion");
			return;
		}

		require_once IPS_ROOT_PATH.'applications_addon/ips/convert/modules_admin/'.$this->request['sw'].'/'.$this->request['choice'].'.php';

		// Child app?
		if (!$this->request['parent'] and isset($parent))
		{
			// What choices do we have?
			$where = '';
			$hidden = '';
			foreach ($parent['choices'] as $choice)
			{
				$where[] = "(sw='{$choice['app']}' and app_key='{$choice['key']}')";
				$hidden .= "<input type='hidden' name='newdb_{$choice['key']}' value='{$choice['newdb']}' />";
			}

			$this->DB->build(array('select' => 'app_id, name', 'from' => 'conv_apps', 'where' => implode(' OR ', $where)));
			$this->DB->execute();
			$parentoptions = array();
			if($parent['required'] === false)
			{
				$parentoptions[] = $this->html->convertAddOption(array('key' => 'x', 'name' => 'NO PARENT'));
			}
			while ($r = $this->DB->fetch())
			{
				$parentoptions[] = $this->html->convertAddOption(array('key' => $r['app_id'], 'name' => $r['name']));
			}
			$this->registry->output->html .= $this->html->convertAskForParent(implode('', $parentoptions), $hidden);
			return;
		}

		elseif ($this->request['parent'])
		{
			// If parent is "NO PARENT" we need to find information
			if ( $this->request['parent'] == "x" )
			{
				$this->registry->output->html .= $this->html->convertShowOptions2();
				return;
			}
			
			// Do we need new db info?
			$chosenparent = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'conv_apps', 'where' => "app_id='{$this->request['parent']}'" ) );
			if (!$this->request['newdb_'.$chosenparent['app_key']])
			{
				$this->request['hb_sql_driver'] = $chosenparent['db_driver'];
				$this->request['hb_sql_host'] = $chosenparent['db_host'];
				$this->request['hb_sql_user'] = $chosenparent['db_user'];
				$this->request['hb_sql_pass'] = $chosenparent['db_pass'];
				$this->request['hb_sql_database'] = $chosenparent['db_db'];
				$this->request['hb_sql_tbl_prefix'] = $chosenparent['db_prefix'];
				$this->request['hb_sql_charset'] = $chosenparent['db_charset'];
				$this->finish($info);
				return;
			}
		}

		// File system?
		elseif($info['nodb'])
		{
			$this->registry->output->html .= $this->html->convertShowOptionsCustom($custom);
			return;
		}

		$this->registry->output->html .= $this->html->convertShowOptions2();
	}

	/**
    * Save and boink to conversion page
    *
    * @access	private
    * @return	void
    */
	private function finish($info=array())
	{
		if (!$this->request['app_name'])
		{
			$this->registry->output->html .= $this->html->convertError("You did not enter an ID for the conversion");
			return;
		}

		if (empty($info))
		{
			require_once IPS_ROOT_PATH.'applications_addon/ips/convert/modules_admin/'.$this->request['sw'].'/'.$this->request['choice'].'.php';
		}

		// Check
		if ($this->DB->buildAndFetch( array( 'select' => 'app_id', 'from' => 'conv_apps', 'where' => "name='{$this->request['app_name']}'" ) ))
		{
			$this->registry->output->html .= $this->html->convertError("An application already exists with that id");
			return;
		}

		// Insert
		$app = array(
			'sw'		=> $this->request['sw'],
			'app_key'	=> $info['key'],
			'name'		=> $this->request['app_name'],
			'login'		=> (int)$info['login'],
			'parent'	=> ($this->request['parent'] != 'x') ? $this->request['parent'] : '',
			'db_driver'	=> $this->request['hb_sql_driver'],
			'db_host'	=> $this->request['hb_sql_host'],
			'db_user'	=> $this->request['hb_sql_user'],
			'db_pass'	=> $_REQUEST['hb_sql_pass'],
			'db_db'		=> $this->request['hb_sql_database'],
			'db_prefix'	=> $this->request['hb_sql_tbl_prefix'],
			'db_charset'	=> $this->request['hb_sql_charset'],
			);
		$this->DB->insert( 'conv_apps', $app );

		// Enable login?
		if ($info['login'])
		{
			$this->enableLogin();
		}

		// Custom info?
		if($this->request['custom'])
		{
			$get = unserialize($this->settings['conv_extra']);
			$us = $get[ $this->request['app_name'] ];
			$us = is_array($us) ? $us : array();
			$extra = is_array($us['core']) ? $us : array_merge($us, array('core' => array()));
			$get[ $this->request['app_name'] ] = $extra;

			foreach($custom as $k => $v)
			{
				$get[ $this->request['app_name'] ]['core'][$k] = $_REQUEST[$k];
			}

			IPSLib::updateSettings(array('conv_extra' => serialize($get)));
		}

		// Update which one we're on now
		IPSLib::updateSettings(array('conv_current' => $this->request['app_name']));

		// And boink
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=convert&module=setup&section=switch' );
	}

	/**
    * Enable the converter's login method
    *
    * @access	private
    * @return	void
    */
	private function enableLogin()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------

		require_once( IPS_KERNEL_PATH . 'class_xml.php' );
		$xml			= new class_xml();
		$xml->doc_type	= IPS_DOC_CHAR_SET;

		$login_id	= basename('convert');

		//-----------------------------------------
		// Now get the XML data
		//-----------------------------------------

		$dh = opendir( IPS_PATH_CUSTOM_LOGIN );

		if ( $dh !== false )
		{
			while ( false !== ($file = readdir($dh) ) )
			{
				if( is_dir( IPS_PATH_CUSTOM_LOGIN . '/' . $file ) AND $file == $login_id )
				{
					if( file_exists( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/loginauth_install.xml' ) )
					{
						$file_content = file_get_contents( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/loginauth_install.xml' );

						$xml->xml_parse_document( $file_content );

						if( is_array($xml->xml_array['export']['group']['row']) )
						{
							foreach( $xml->xml_array['export']['group']['row'] as $f => $entry )
							{
								if( is_array($entry) )
								{
									foreach( $entry as $k => $v )
									{
										if ( $f == 'VALUE' or $f == 'login_id' )
										{
											continue;
										}

										$data[ $f ] = $v;
									}
								}
							}
						}
					}
					else
					{
						$this->error('Could not locate login method.');
					}

					$dir_methods[ $file ] = $data;

					break;
				}
			}

			closedir( $dh );
		}

		if( !is_array($dir_methods) OR !count($dir_methods) )
		{
			$this->error('An error occured while trying to enable the converter login method.');
		}

		//-----------------------------------------
		// Now verify it isn't installed
		//-----------------------------------------

		$login		= $this->DB->buildAndFetch( array( 'select' => 'login_id', 'from' => 'login_methods', 'where' => "login_folder_name='" . $login_id . "'" ) );

		if( ! $login['login_id'] )
		{
			$max = $this->DB->buildAndFetch( array( 'select' => 'MAX(login_order) as highest_order', 'from' => 'login_methods' ) );

			$dir_methods[ $login_id ]['login_order'] = $max['highest_order'] + 1;

			$dir_methods[$login_id]['login_enabled'] = 1;

			$this->DB->insert( 'login_methods', $dir_methods[ $login_id ] );
		}
		else
		{
			$this->DB->update( 'login_methods', array( 'login_enabled' => 1 ), 'login_id=' . $login['login_id'] );
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$cache	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
		$this->DB->execute();

		while ( $r = $this->DB->fetch() )
		{
			$cache[ $r['login_id'] ] = $r;
		}

		ipsRegistry::cache()->setCache( 'login_methods', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );

		//-----------------------------------------
		// Switch
		//-----------------------------------------

		IPSLib::updateSettings(array('conv_login' => 1));
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
