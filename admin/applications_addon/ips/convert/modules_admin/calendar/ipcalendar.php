<?php
/**
 * IPS Converters
 * IP.Calendar 3.0 Converters
 * IP.Calendar Merge Tool
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
		'key'	=> 'ipcalendar',
		'name'	=> 'IP.Calendar 3.0',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'ipboard', 'newdb' => false),
		));

	class admin_convert_calendar_ipcalendar extends ipsCommand
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

			// array('action' => array('action that must be completed first'))
			$this->actions = array(
				'cal_calendars' => array('forum_perms'),
				'cal_events'	=> array('cal_calendars', 'members'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_calendar.php' );
			$this->lib =  new lib_calendar( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'IP.Calendar Merge Tool' );

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
		 * Fix post data
		 *
		 * @access	private
		 * @param 	string		raw post data
		 * @return 	string		parsed post data
		 **/
		private function fixPostData($post)
		{
			return $post;
		}

		/**
		 * Convert Calendars
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_cal_calendars()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> 'c.*',
							'from' 		=> array('cal_calendars' => 'c'),
							'order'		=> 'c.cal_id ASC',
							'add_join'	=> array(
											array( 	'select' => 'p.*',
													'from'   =>	array( 'permission_index' => 'p' ),
													'where'  => "p.perm_type='calendar' AND p.perm_type_id=c.cal_id",
													'type'   => 'left'
												),
											),
						);

			$loop = $this->lib->load('cal_calendars', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Handle permissions
				//-----------------------------------------

				$perms = array();
				$perms['view']		= $row['perm_view'];
				$perms['create']	= $row['perm_2'];
				$perms['bypassmod']	= $row['perm_3'];

				//-----------------------------------------
				// Send
				//-----------------------------------------

				$this->lib->convertCalendar($row['cal_id'], $row, $perms);
			}

			$this->lib->next();

		}

		/**
		 * Convert Events
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_cal_events()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'cal_events',
							'order'		=> 'event_id ASC',
						);

			$loop = $this->lib->load('cal_events', $main);

			//-----------------------------------------
			// Prepare for reports conversion
			//-----------------------------------------

			$this->lib->prepareReports('calendar');

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$row['event_content'] = $this->fixPostData($row['event_content']);
				$this->lib->convertEvent($row['event_id'], $row);

				//-----------------------------------------
				// Report Center
				//-----------------------------------------

				$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='calendar'" ) );
				$rs = array(	'select' 	=> '*',
								'from' 		=> 'rc_reports_index',
								'order'		=> 'id ASC',
								'where'		=> 'exdat2='.$row['event_id']." AND rc_class='{$rc['com_id']}'"
							);

				ipsRegistry::DB('hb')->build($rs);
				ipsRegistry::DB('hb')->execute();
				while ($report = ipsRegistry::DB('hb')->fetch())
				{
					$rs = array(	'select' 	=> '*',
									'from' 		=> 'rc_reports',
									'order'		=> 'id ASC',
									'where'		=> 'rid='.$report['id']
								);

					ipsRegistry::DB('hb')->build($rs);
					ipsRegistry::DB('hb')->execute();
					$reports = array();
					while ($r = ipsRegistry::DB('hb')->fetch())
					{
						$reports[] = $r;
					}
					$this->lib->convertReport('calendar', $report, $reports);
				}
			}

			$this->lib->next();

		}

	}

