<?php
/**
 * IPS Converters
 * IP.Calendar 3.0 Converters
 * vBulletin
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
		'key'	=> 'vbulletin_legacy',
		'name'	=> 'vBulletin 3.8',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'vbulletin_legacy', 'newdb' => false),
		));

	class admin_convert_calendar_vbulletin_legacy extends ipsCommand
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
			$this->lib->sendHeader( 'vBulletin Calendar &rarr; IP.Calendar Converter' );

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
				case 'cal_calendars':
					return $this->lib->countRows('calendar');
					break;

				case 'cal_events':
					return $this->lib->countRows('event');
					break;

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
			// Sort out newlines
			$post = nl2br($post);

			// And quote tags
			$post = preg_replace("#\[quote=(.+);\d\]#i", "[quote name='$1']", $post);
			$post = preg_replace("#\[quote=(.+)\](.+)\[/quote\]#i", "[quote name='$1']$2[/quote]", $post);

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

			$main = array(	'select' 	=> '*',
							'from' 		=> 'calendar',
							'order'		=> 'calendarid ASC',
						);

			$loop = $this->lib->load('cal_calendars', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'cal_title'			=> $row['title'],
					'cal_moderate'		=> $row['moderatenew'],
					'cal_position'		=> $row['displayorder'],
					'cal_event_limit'	=> $row['eventcount'],
					'cal_bday_limit'	=> $row['birthdaycount'],
					);

				$this->lib->convertCalendar($row['calendarid'], $save, array());
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
							'from' 		=> 'event',
							'order'		=> 'eventid ASC',
						);

			$loop = $this->lib->load('cal_events', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'event_calendar_id'	=> $row['calendarid'],
					'event_member_id'	=> $row['userid'],
					'event_content'		=> $this->fixPostData($row['event']),
					'event_title'		=> $row['title'],
					'event_perms'		=> '*',
					'event_smilies'		=> $row['allowsmilies'],
					'event_approved'	=> $row['visible'],
					'event_unixstamp'	=> $row['dateline'],
					'event_recurring'	=> $row['recurring'],
					'event_tz'			=> $row['utc'],
					'event_unix_from'	=> $row['dateline_from'],
					'event_unix_to'		=> $row['dateline_to'],
					);

				$this->lib->convertEvent($row['eventid'], $save);
			}

			$this->lib->next();

		}

	}

