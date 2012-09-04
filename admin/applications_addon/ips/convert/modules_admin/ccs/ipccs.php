<?php
/**
 * IPS Converters
 * IP.CCS 1.0 Converters
 * IP.CCS Merge Tool
 * Last Update: $Date: 2010-03-19 11:03:12 +0100(ven, 19 mar 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 437 $
 */


	$info = array(
		'key'	=> 'ipccs',
		'name'	=> 'IP.CCS 1.0',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'ipboard', 'newdb' => false),
		));

	class admin_convert_ccs_ipccs extends ipsCommand
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
			$this->registry = $registry;
			//-----------------------------------------
			// What can this thing do?
			//-----------------------------------------

			$this->actions = array(
				'ccs_folders'		=> array(),
				'ccs_containers'	=> array(),
				'ccs_blocks'		=> array('ccs_containers'),
				'ccs_page_templates'=> array('ccs_containers'),
				'ccs_pages'			=> array('ccs_page_templates', 'forum_perms'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_ccs.php' );
			$this->lib =  new lib_ccs( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'IP.CCS Merge Tool' );

			//-----------------------------------------
			// Are we connected?
			// (in the great circle of life...)
			//-----------------------------------------

			$this->HB = $this->lib->connect();

			//-----------------------------------------
			// What are we doing?
			//-----------------------------------------

			if (array_key_exists($this->request['do'], $this->actions))
			{
				call_user_func(array($this, 'convert_'.$this->request['do']));
			}
			else
			{
				$this->lib->menu();
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
			exit;
		}

		/**
		 * Count rows
		 *
		 * @access	private
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @return 	integer 	number of entries
		 **/
		public function countRows($action)
		{
			switch ($action)
			{
				default:
					return $this->lib->countRows($action);
					break;
			}
		}

		/**
		 * Check if section has configuration options
		 *
		 * @access	private
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @return 	boolean
		 **/
		public function checkConf($action)
		{
			return false;
		}

		/**
		 * Convert Containers
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_ccs_containers()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'ccs_containers',
							'order'		=> 'container_id ASC',
						);

			$loop = $this->lib->load('ccs_containers', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertContainer($row['container_id'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Blocks
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_ccs_blocks()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'ccs_blocks',
							'order'		=> 'block_id ASC',
						);

			$loop = $this->lib->load('ccs_blocks', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertBlock($row['block_id'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Templates
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_ccs_page_templates()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'ccs_page_templates',
							'order'		=> 'template_id ASC',
						);

			$loop = $this->lib->load('ccs_page_templates', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertTemplate($row['template_id'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Templates
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_ccs_folders()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'ccs_folders',
						);

			$loop = $this->lib->load('ccs_folders', $main);

			//---------------------------
			// Loop
			//---------------------------

			$count = $this->request['st'];

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$count++;
				$this->lib->convertFolder($row['folder_path'], $row['last_modified'], $count);
			}

			$this->lib->next();

		}

		/**
		 * Convert Templates
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_ccs_pages()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'ccs_pages',
							'order'		=> 'page_id ASC',
						);

			$loop = $this->lib->load('ccs_pages', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertPage($row['page_id'], $row);
			}

			$this->lib->next();

		}

	}

