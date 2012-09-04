<?php
/**
 * IPS Converters
 * Application Files
 * Miscellaneous Functions
 * Last Update: $Date: 2009-07-27 15:31:31 +0200(lun, 27 lug 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 344 $
 */


if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_convert_setup_extra extends ipsCommand
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
		switch ($this->request['do'])
		{
			case 'count':
				$this->count();
				break;
				
			case 'createpfield':
				$this->createpfield();
				break;
		}
		exit;
	}
	
	/**
    * Refresh the per-cycle count
    *
    * @access	private
    * @return	void
    */
	private function count()
	{
		if (!$this->request['newcycles'])
		{
			exit;
		}
		$cycles = round($this->request['total'] / $this->request['newcycles']);
		$cycles = ($cycles == 0) ? 1 : $cycles;
		echo $cycles;
		exit;
	}
}	

?>