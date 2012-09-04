<?php
/**
 * IPS Converters
 * Application Files
 * Library functions for IP.Calendar 3.0 conversions
 * Last Update: $Date: 2011-07-12 19:47:24 +0100 (Tue, 12 Jul 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 549 $
 */

	class lib_calendar extends lib_master
	{
		/**
	     * Information box to display on convert screen
	     *
	     * @access	public
	     * @return	string 		html to display
	     */
		public function getInfo()
		{
			return "<strong>Rebuild Content</strong><br />
				<a href='{$this->settings['base_url']}&app=calendar' target='_blank'>Click here</a> and run the following tools:
				<ul>
					<li>Recache Calendar Events</li>
				</ul>";
		}
				
		/**
		 * Return the information needed for a specific action
		 *
	     * @access	public
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @return 	array 		info needed for html->convertMenuRow
		 **/
		public function menuRow($action='', $return=false)
		{
			switch ($action)
			{
				case 'cal_calendars':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'cal_calendars' ) );
					$return = array(
						'name'	=> 'Calendars',
						'rows'	=> $count['count'],
						'cycle'	=> 100,
						'conf'	=> false,
					);
					break;
					
				case 'cal_events':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'cal_events' ) );
					$return = array(
						'name'	=> 'Events',
						'rows'	=> $count['count'],
						'cycle'	=> 500,
						'conf'	=> false,
					);
					break;
				
				case 'cal_event_comments':
					$count	= $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'cal_event_comments' ) );
					$return	= array (
						'name'	=> 'Event Comments',
						'rows'	=> $count['count'],
						'cycle'	=> 1500,
						'conf'	=> false,
					);
				break;
				
				case 'cal_event_ratings':
					$count	= $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'cal_event_ratings' ) );
					$return	= array (
						'name'	=> 'Event Ratings',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
						'conf'	=> false,
					);
				break;
				
				case 'cal_event_rsvp':
					$count	= $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'cal_event_rsvp' ) );
					$return	= array (
						'name'	=> 'Event RSVP',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
						'conf'	=> false,
					);
				break;
												
				default:
					if ($return)
					{
						return false;
					}
					$this->error("There is a problem with the converter: called invalid action {$action}");
					break;
			}
			
			$basic = array('section' => $this->app['app_key'], 'key' => $action, 'app' => 'calendar');
			return array_merge($basic, $return);
		}
				
		/**
		 * Return the tables that need to be truncated for a given action
		 *
	     * @access	public
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @return 	array 		array('table' => 'id_field', ...)
		 **/
		public function truncate($action)
		{
			switch ($action)
			{
				case 'cal_calendars':
					return array( 'cal_calendars' => 'cal_id' );
					break;
					
				case 'cal_events':
					return array( 'cal_events' => 'event_id' );
					break;
				
				case 'cal_event_comments':
					return array ( 'cal_event_comments' => 'comment_id' );
				break;
				
				case 'cal_event_ratings':
					return array ( 'cal_event_ratings' => 'rating_id' );
				break;
				
				case 'cal_event_rsvp':
					return array ( 'cal_event_rsvp' => 'rsvp_id' );
				break;
									
				default:
					$this->error('There is a problem with the converter: bad truncate command');
					break;
			}
		}
		
		/**
		 * Database changes
		 *
		 * @access	public
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @return 	array 		Details of change - array('type' => array(info))
		 **/
		public function databaseChanges($action)
		{
			return null;
		}
		
		/**
		 * Process report links
		 *
		 * @access	protected
		 * @param 	string		type (e.g. 'post', 'pm')
		 * @param 	array 		Data for reports_index table with foreign IDs
		 * @return 	array 		Processed data for reports_index table
		 **/
		protected function processReportLinks($type, $report)
		{
			$report['exdat1'] = $this->getLink($report['exdat1'], 'cal_calendars');
			$report['exdat2'] = $this->getLink($report['exdat2'], 'cal_events');
			$report['exdat3'] = 0;
			$report['url'] = "/index.php?app=calendar&amp;module=calendar&amp;cal_id={$report['exdat1']}&amp;do=showevent&amp;event_id={$report['exdat2']}";
			$report['seotemplate'] = '';

			return $report;
		}
		
		/**
		 * Convert a calendar
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @param 	array 		Permissions index data
		 * @return 	boolean		Success or fail
		 **/
		public function convertCalendar($id, $info, $perms)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			
			if (!$info['cal_title'])
			{
				$this->logError($id, 'No title provided');
				return false;
			}

			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			// Make sure we don't have any fields we shouldn't have
			foreach (array('perm_id', 'app', 'perm_type', 'perm_type_id', 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7', 'owner_only', 'friend_only', 'authorized_users') as $unset)
			{
				unset($info[$unset]);
			}
			
			unset($info['cal_id']);
			$this->DB->insert( 'cal_calendars', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add permissions entry
			//-----------------------------------------
			
			foreach ($perms as $key => $value)
			{
				if ($value != '*')
				{
					$save = array();
					foreach (explode(',', $value) as $pset)
					{
						if ($pset)
						{
							$save[] = $this->getLink($pset, 'forum_perms', false, true);
						}
					}
					$perms[$key] = implode(',', $save);
				}
			}
			
			$this->addToPermIndex('calendar', $inserted_id, $perms, $id);
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'cal_calendars');
			
			return true;
		}
		
		/**
		 * Convert an event
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @param 	boolean		If true, getLink will NOT be run on event_calendar_id
		 * @return 	boolean		Success or fail
		 **/
		public function convertEvent($id, $info, $literal=false)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			
			if (!$info['event_title'])
			{
				$this->logError($id, 'No title provided');
				return false;
			}
			
			if ( !$info['event_content'] )
			{
				$this->logError ( $id, 'No content provided.' );
				return false;
			}
			
			if ( !$info['event_calendar_id'] )
			{
				$this->logError ( $id, 'No Calendar ID provided.' );
				return false;
			}
			
			if ( !$info['event_member_id'] )
			{
				$this->logError ( $id, 'No Member ID provided.' );
				return false;
			}

			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$info['event_calendar_id'] = ($literal) ? $info['event_calendar_id'] : $this->getLink($info['event_calendar_id'], 'cal_calendars');
			$info['event_member_id'] = $this->getLink($info['event_member_id'], 'members', false, true);
			
			unset($info['event_id']);
			$this->DB->insert( 'cal_events', $info );
			$inserted_id = $this->DB->getInsertId();
						
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'cal_events');
			
			return true;
		}

		/**
		 * Convert an event comment
		 * 
		 * @access	public
		 * @param	integer		Foreign ID Number
		 * @param	array		Data to insert
		 * @return	boolean		Success or fail
		 */
		public function convertEventComment ( $id, $info )
		{
			if ( !$id )
			{
				$this->logError ( $id, 'No ID provided.' );
				return false;
			}
			
			if ( !$info['comment_eid'] )
			{
				$this->logError ( $id, 'No Event ID provided.' );
				return false;
			}
			
			if ( !$info['comment_mid'] )
			{
				$this->logError ( $id, 'No Member ID provided.' );
				return false;
			}
			
			if ( !$info['comment_text'] )
			{
				$this->logError ( $id, 'No Comment text provided.' );
				return false;
			}
			
			$info['comment_eid'] = $this->getLink ( $info['comment_eid'], 'cal_events' );
			$info['comment_mid'] = $this->getLink ( $info['comment_mid'], 'members', false, true );
			
			unset ( $info['comment_id'] );
			$this->DB->insert ( 'cal_event_comments', $info );
			$inserted_id = $this->DB->getInsertId ( );
			
			$this->addLink ( $inserted_id, $id, 'cal_event_comments' );
			
			return true;
		}
		
		/**
		 * Convert an event rating.
		 * 
		 * @access	public
		 * @param	integer		Foreign ID Number
		 * @param	array		Data to insert
		 * @return	boolean		Success or fail
		 */
		public function convertEventRating ( $id, $info )
		{
			if ( !$id )
			{
				$this->logError ( $id, '(RATING) No ID Provided.' );
				return false;
			}
			
			if ( !$info['rating_eid'] )
			{
				$this->logError ( $id, '(RATING) No Event ID provided.' );
				return false;
			}
			
			if ( !$info['rating_member_id'] )
			{
				$this->logError ( $id, '(RATING) No Member ID provided.' );
				return false;
			}
			
			if ( !$info['rating_value'] )
			{
				$this->logError ( $id, '(RATING) No Rating provided.' );
				return false;
			}
			
			$info['rating_eid']			= $this->getLink ( $info['rating_eid'], 'cal_events' );
			$info['rating_member_id']	= $this->getLink ( $info['rating_member_id'], 'members', false, true );
			
			unset ( $info['rating_id'] );
			$this->DB->insert ( 'cal_event_ratings', $info );
			$inserted_id = $this->DB->getInsertId ( );
			
			$this->addLink ( $inserted_id, $id, 'cal_event_ratings' );
			
			return true;
		}
		
		/**
		 * Convert an event rsvp.
		 * 
		 * @access	public
		 * @param	integer		Foreign ID number
		 * @param	array		Data to insert
		 * @return	boolean
		 */
		public function convertEventRsvp ( $id, $info )
		{
			if ( !$id )
			{
				$this->logError ( $id, 'No ID provided.' );
				return false;
			}
			
			if ( !$info['rsvp_event_id'] )
			{
				$this->logError ( $id, 'No Event ID provided.' );
				return false;
			}
			
			if ( !$info['rsvp_member_id'] )
			{
				$this->logError ( $id, 'No Member ID provided.' );
				return false;
			}
			
			$info['rsvp_event_id']	= $this->getLink ( $info['rsvp_event_id'], 'cal_events' );
			$info['rsvp_member_id']	= $this->getLink ( $info['rsvp_member_id'], 'members', false, true );
			
			unset ( $info['rsvp_id'] );
			$this->DB->insert ( 'cal_event_rsvp', $info );
			$inserted_id = $this->DB->getInsertId ( );
			
			$this->addLink ( $inserted_id, $id, 'cal_event_rsvp' );
			
			return true;
		}
		
	}
	
?>