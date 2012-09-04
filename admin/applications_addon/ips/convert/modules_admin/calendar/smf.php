<?php
/**
 * IPS Converters
 * IP.Calendar 3.0 Converters
 * SMF
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
		'key'	=> 'smf',
		'name'	=> 'SMF 2.0',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'smf', 'newdb' => false),
		));

	class admin_convert_calendar_smf extends ipsCommand
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
				'cal_events'	=> array('members'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_calendar.php' );
			$this->lib =  new lib_calendar( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'SMF Calendar &rarr; IP.Calendar Converter' );

			//-----------------------------------------
			// Are we connected?
			// (in the great circle of life...)
			//-----------------------------------------

			$this->HB = $this->lib->connect();

			//-----------------------------------------
			// Parser
			//-----------------------------------------

			require_once( IPS_ROOT_PATH . 'sources/handlers/han_parse_bbcode.php' );
			$this->parser           =  new parseBbcode( $registry );
			$this->parser->parse_smilies = 1;
		 	$this->parser->parse_bbcode  = 1;
		 	$this->parser->parsing_section = 'convert';


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
				case 'cal_events':
					return $this->lib->countRows('calendar');
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
			switch ($action)
			{
				case 'cal_events':
					return true;
					break;

				default:
					return false;
					break;
			}

		}

		/**
		 * Fix post data
		 *
		 * @access	private
		 * @param 	string		raw post data
		 * @return 	string		parsed post data
		 **/
		private function fixPostData($text)
		{
			// Sort out the list tags
			$text = str_replace('[li]', '[*]', $text);
			$text = str_replace('[/li]', '', $text);

			// God knows why this is needed, but it is
			$text = $this->parser->preDbParse($this->parser->preDisplayParse($text));

			return $text;
		}

		/**
		 * Convert Events
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_cal_events()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('cal_events', array('calendar'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'calendar',
							'order'		=> 'id_event ASC',
						);

			$loop = $this->lib->load('cal_events', $main);

			//-----------------------------------------
			// We need to know what calendar to save them in to...
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];

			$cals = array('x' => '-Create New-');
			$this->DB->build(array('select' => '*', 'from' => 'cal_calendars'));
			$this->DB->execute();
			while ($row = $this->DB->fetch())
			{
				$cals[$row['cal_id']] = $row['cal_title'];
			}

			$ask = array('calendar' => array('type' => 'dropdown', 'label' => 'The calendar to import events into:', 'options' => $cals) );

			$this->lib->getMoreInfo('cal_events', $loop, $ask);

			//-----------------------------------------
			// Are we creating a calendar?
			//-----------------------------------------

			$literal = true;
			if ($us['calendar'] == 'x')
			{
				$literal = false;
				if (!$this->lib->getLink('1', 'cal_calendars', true))
				{
					$this->lib->convertCalendar( 1, array('cal_title' => 'Converted'), array() );
				}
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$explode_start = explode('-', $row['start_date']);
				$explode_end = explode('-', $row['end_date']);
				$start = mktime( 0, 0, 0, $explode_start[1], $explode_start[2], $explode_start[0] );
				$end = mktime( 0, 0, 0, $explode_end[1], $explode_end[2], $explode_end[0] );

				$topic = ipsRegistry::DB('hb')->buildAndFetch(array('select' => '*', 'from' => 'topics', 'where' => 'id_topic='.$row['id_topic']));
				$post = ipsRegistry::DB('hb')->buildAndFetch(array('select' => '*', 'from' => 'messages', 'where' => 'id_msg='.$topic['id_first_msg']));

				$save = array(
					'event_calendar_id'	=> ($literal) ? $us['calendar'] : 1,
					'event_member_id'	=> $row['id_member'],
					'event_content'		=> $this->fixPostData($post['body']),
					'event_title'		=> $row['title'],
					'event_smilies'		=> $post['smileys_enabled'],
					'event_perms'		=> '*',
					'event_approved'	=> $topic['approved'],
					'event_unixstamp'	=> $post['poster_time'],
					'event_unix_from'	=> $start,
					'event_unix_to'		=> $end,
					);

				$this->lib->convertEvent($row['id_event'], $save, $literal);
			}

			$this->lib->next();

		}

	}

