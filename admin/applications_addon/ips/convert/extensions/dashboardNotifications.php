<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Dashboard Notifications
 * Last Updated: $Date
 *
 * @author 		$Author: rashbrook $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board
 * @subpackage	Members
 * @link		http://www.invisionpower.com
 * @version		$Rev: 547 $
 *
 */

class dashboardNotifications__convert
{
	public function __construct ( )
	{
		$this->settings	= ipsRegistry::fetchSettings ( );
		$this->lang		= ipsRegistry::getClass ( 'class_localization' );
		$this->DB		= ipsRegistry::DB ( );
	}

	public function get ( )
	{
		if ( @is_dir( IPS_ROOT_PATH . 'applications_addon/ips/convert/' ) and !@is_file( DOC_IPS_ROOT_PATH . 'cache/converter_lock.php' ) /*and $this->caches['app_cache']['convert']*/ )
		{
			$this->lang->words['cp_warning_converter']	= sprintf( $this->lang->words['cp_warning_converter'], $this->settings['_base_url'] );
			$entries[] = array( $this->lang->words['cp_unlocked_converter'], $this->lang->words['cp_warning_converter'] );
		}
		return $entries;
	}
}

?>