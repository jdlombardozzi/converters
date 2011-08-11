<?php
/**
 * IPS Converters
 * Application Files
 * Library functions for IP.Tracker 1.3 conversions
 * Last Update: $Date: 2009-08-12 11:54:20 +0200(mer, 12 ago 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 349 $
 */
	class lib_tracker extends lib_master
	{
		/**
	     * Information box to display on convert screen
	     *
		 * @todo
	     * @access	public
	     * @return	string 		html to display
	     */
		public function getInfo()
		{
			return "<strong>Rebuild Data</strong><br />
				<a href='{$this->settings['base_url']}&app=tracker&module=tools&section=tools&do=overview' target='_blank'>Click here</a> and run the following tools:
				<ul>
					<li>Resynchronize Projects</li>
					<li>Resynchronize Issues</li>
					<li>Rebuild Tracker Statistics</li>
				</ul><br />
				<br /><br />
				<strong>Turn the application back online</strong><br />
				Visit your IP.Tracker settings and turn the application back online.";
		}
						
		/**
		 * Return the information needed for a specific action
		 *
	     * @access	public
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @param	boolean		If true, will return false instead of printing error if encountered
		 * @return 	array 		info needed for html->convertMenuRow
		 **/
		public function menuRow($action='', $return=false)
		{
			switch ($action)
			{
				case 'tracker_projects':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'tracker_projects' ) );
					$return = array(
						'name'	=> 'Projects',
						'rows'	=> $count['count'],
						'cycle'	=> 1000,
					);
					break;
					
				case 'tracker_fields_data':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'tracker_fields_data' ) );
					$return = array(
						'name'	=> 'Custom Fields',
						'rows'	=> $count['count'],
						'cycle'	=> 100,
					);
					break;
					
				case 'tracker_categories':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'tracker_categories' ) );
					$return = array(
						'name'	=> 'Statuses',
						'rows'	=> $count['count'],
						'cycle'	=> 100,
					);
					break;
					
				case 'tracker_issues':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'tracker_issues' ) );
					$return = array(
						'name'	=> 'Issues',
						'rows'	=> $count['count'],
						'cycle'	=> 1500,
					);
					break;
					
				case 'tracker_posts':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'tracker_posts' ) );
					$return = array(
						'name'	=> 'Comments',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;
					
				case 'tracker_field_changes':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'tracker_field_changes' ) );
					$return = array(
						'name'	=> 'Issue History',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;
				
				case 'tracker_attachments':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'attachments', 'where' => "attach_rel_module='tracker'" ) );
					$return = array(
						'name'	=> 'Attachments',
						'rows'	=> $count['count'],
						'cycle'	=> 100,
					);
					break;
					
				case 'tracker_moderators':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'tracker_moderators') );
					$return = array(
						'name'	=> 'Moderators',
						'rows'	=> $count['count'],
						'cycle'	=> 500,
					);
					break;
					
				case 'tracker_logs':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'tracker_logs') );
					$return = array(
						'name'	=> 'Moderator Logs',
						'rows'	=> $count['count'],
						'cycle'	=> 1000,
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
			
			$basic = array('section' => $this->app['app_key'], 'key' => $action, 'app' => 'tracker');
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
				case 'tracker_projects':
					return array( 'tracker_projects' => 'project_id' );
					break;
					
				case 'tracker_fields_data':
					return array( 'tracker_fields_data' => 'field_id' );
					break;
					
				case 'tracker_ptracker':
					return array( 'tracker_ptracker' => 'ptid' );
					break;
					
				case 'tracker_issues':
					return array( 'tracker_issues' => 'issue_id' );
					break;
					
				case 'tracker_categories':
					return array( 'tracker_categories' => 'cat_id' );
					break;
				
				case 'tracker_itracker':
					return array( 'tracker_itracker' => 'itid' );
					break;
					
				case 'tracker_posts':
					return array( 'tracker_posts' => 'pid' );
					break;
					
				case 'tracker_field_changes':
					return array( 'tracker_field_changes' => 'field_change_id' );
					break;
					
				case 'tracker_attachments':
					return array( 'attachments' => 'attach_id' );
					break;
					
				case 'tracker_moderators':
					return array( 'tracker_moderators' => 'moderate_id' );
					break;
					
				case 'tracker_logs':
					return array( 'tracker_logs' => 'id' );
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
			switch ($action)
			{
				case 'tracker_projects':
					return array('addfield' => array('tracker_projects', 'conv_parent', 'mediumint(5)'));
					break;
				
				default:
					return null;
					break;
			}
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
			return $report;
		}
		
		/**
		 * Convert an custom field
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertField($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['field_keyword'])
			{
				$this->logError($id, 'No keyword provided');
				return false;
			}
			if (!$info['field_title'])
			{
				$this->logError($id, 'No title provided');
				return false;
			}
			if (!$info['field_type'])
			{
				$this->logError($id, 'No type provided');
				return false;
			}
			
			$info['field_title_plural'] = ($info['field_title_plural']) ? $info['field_title_plural'] : $info['field_title'].'s';
															
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			unset($info['field_id']);
			$this->DB->insert( 'tracker_fields_data', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Save which projects this matches up with
			//-----------------------------------------
			
			$projects = unserialize($info['field_projects']);
			
			if (!empty($projects))
			{
				$get = unserialize($this->settings['conv_extra']);
				$us = $get[$this->app['name']];

				foreach ($projects as $project)
				{
					$us['tracker_fields'][$project][] = $inserted_id;
				}
				
				$get[$this->app['name']] = $us;
				IPSLib::updateSettings(array('conv_extra' => serialize($get)));
			}
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'tracker_fields_data');
			
			//-----------------------------------------
			// We need a column in pfields_content
			//-----------------------------------------
			
			$this->DB->addField( 'tracker_issues', "field_$inserted_id", 'text' );
			$this->DB->optimize( 'tracker_issues' );
			
			return true;
		}
		
		/**
		 * Convert a status
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertStatus($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['cat_title'])
			{
				$this->logError($id, 'No title provided');
				return false;
			}
			
			//-----------------------------------------
			// Handle duplicates
			//-----------------------------------------
			
			$dupe = $this->DB->buildAndFetch( array( 'select' => 'cat_id', 'from' => 'tracker_categories', 'where' => "cat_title = '{$info['cat_title']}'" ) );
			if ($dupe)
			{
				$this->addLink($dupe['cat_id'], $id, 'tracker_categories');
				return false;
			}
																		
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			unset($info['cat_id']);
			$this->DB->insert( 'tracker_categories', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'tracker_categories');
			
			return true;
		}
		
		
		/**
		 * Convert a Project
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertProject($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['project_title'])
			{
				$this->logError($id, 'No title provided');
				return false;
			}
			if (!$info['project_owner_id'])
			{
				$this->logError($id, 'No project owner provided');
				return false;
			}
			
			//-----------------------------------------
			// Link
			//-----------------------------------------
			
			$info['project_owner_id'] = $this->getLink($info['project_owner_id'], 'members', false, true);
			$info['project_last_poster_id'] = ($info['project_last_poster_id']) ? $this->getLink($info['project_last_poster_id'], 'members', false, true) : 0;
			
			// We need to sort out the parent id
			if ($info['project_parent'] != 0)
			{
				$parent = $this->getLink($info['project_parent'], 'tracker_projects');
				if ($parent)
				{
					$info['project_parent'] = $parent;
				}
				else
				{
					$info['conv_parent'] = $info['project_parent'];
					unset($info['project_parent']);
				}
			}
			
			// Permissions
			foreach (array('project_show_perms', 'project_read_perms', 'project_start_perms', 'project_reply_perms', 'project_manage_perms', 'project_upload_perms', 'project_download_perms') as $pkey)
			{
				$save_masks = array();
				if (!$info[$pkey] or $info[$pkey] == '*')
				{
					continue;
				}
				foreach (explode(',', $info[$pkey]) as $mask)
				{
					$save_masks[] = $this->getLink($mask, 'forum_perms', false, true);
				}
				$info[$pkey] = implode(',', $save_masks);
			}
			
			// Custom fields
			$uspcf = unserialize($info['project_custom_fields']);
			if (!empty($uspcf))
			{
				$get = unserialize($this->settings['conv_extra']);
				$us = $get[$this->app['name']];
				
				$info['project_custom_fields'] = serialize($us['tracker_fields'][$id]);
			}
			
			// These will be fixed in the rebuild
			unset($info['project_last_issue_id']);
			unset($info['project_last_post_id']);
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			unset($info['project_id']);
			$this->DB->insert( 'tracker_projects', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'tracker_projects');
			
			//-----------------------------------------
			// Sort out children
			//-----------------------------------------

			$this->DB->update('tracker_projects', array('project_parent' => $inserted_id), 'conv_parent='.$id);
			
			return true;
		}
		
		/**
		 * Convert an project subscription
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertProjectSubscription($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['project_id'])
			{
				$this->logError($id, 'No project ID provided');
				return false;
			}
			if (!$info['member_id'])
			{
				$this->logError($id, 'No member ID provided');
				return false;
			}
															
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$info['member_id'] = $this->getLink($info['member_id'], 'members', false, true);
			$info['project_id'] = $this->getLink($info['project_id'], 'tracker_projects');
			
			unset($info['ptid']);
			$this->DB->insert( 'tracker_ptracker', $info );
			$inserted_id = $this->DB->getInsertId();
						
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'tracker_ptracker');
			
			return true;
		}
		
		/**
		 * Convert an Issue
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @param 	array 		Custom fields data
		 * @return 	boolean		Success or fail
		 **/
		public function convertIssue($id, $info, $custom)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['project_id'])
			{
				$this->logError($id, 'No project ID provided');
				return false;
			}
			if (!$info['cat_id'])
			{
				$this->logError($id, 'No status provided');
				return false;
			}
			
			//-----------------------------------------
			// Link
			//-----------------------------------------
			
			$info['project_id'] = $this->getLink($info['project_id'], 'tracker_projects');
			$info['cat_id'] = $this->getLink($info['cat_id'], 'tracker_categories');
			$info['issue_starter_id'] = $this->getLink($info['issue_starter_id'], 'members', false, true);
			$info['issue_last_poster_id'] = $this->getLink($info['issue_last_poster_id'], 'members', false, true);			
			
			foreach($custom as $key => $value)
			{
				preg_match('/field_(.+)/', $key, $matches);
				$newKey = $this->getLink($matches[1], 'tracker_fields_data');
				if ($newKey)
				{
					$info['field_'.$newKey] = $value;
				}
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			unset($info['issue_id']);
			$this->DB->insert( 'tracker_issues', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'tracker_issues');
						
			return true;
		}
		
		/**
		 * Convert an issue subscription
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertIssueSubscription($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['issue_id'])
			{
				$this->logError($id, 'No issue ID provided');
				return false;
			}
			if (!$info['member_id'])
			{
				$this->logError($id, 'No member ID provided');
				return false;
			}
															
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$info['member_id'] = $this->getLink($info['member_id'], 'members', false, true);
			$info['issue_id'] = $this->getLink($info['issue_id'], 'tracker_issues');
			
			unset($info['itid']);
			$this->DB->insert( 'tracker_itracker', $info );
			$inserted_id = $this->DB->getInsertId();
						
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'tracker_itracker');
			
			return true;
		}
		
		/**
		 * Convert a Post
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertPost($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['post'])
			{
				$this->logError($id, 'No post provided');
				return false;
			}
			if (!$info['author_id'])
			{
				$this->logError($id, 'No poster ID number provided');
				return false;
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$info['author_id'] = $this->getLink($info['author_id'], 'members', false, true);
			$info['issue_id'] = $this->getLink($info['issue_id'], 'tracker_issues');

			unset($info['pid']);
			$this->DB->insert( 'tracker_posts', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'tracker_posts');
						
			return true;
		}
		
		/**
		 * Convert Issue History
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertIssueLog($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['field_change_mid'])
			{
				$this->logError($id, 'No member ID provided');
				return false;
			}
			if (!$info['field_issue_id'])
			{
				$this->logError($id, 'No issue ID provided');
				return false;
			}
			if (!$info['field_type'])
			{
				$this->logError($id, 'No field type provided');
				return false;
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$info['field_change_mid'] = $this->getLink($info['field_change_mid'], 'members', false, true);
			$info['field_issue_id'] = $this->getLink($info['field_issue_id'], 'tracker_issues');

			unset($info['field_change_id']);
			$this->DB->insert( 'tracker_field_changes', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'tracker_field_changes');
						
			return true;
		}
	
		/**
		 * Convert a moderator
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertModerator($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['moderate_type'])
			{
				$this->logError($id, 'No type provided');
				return false;
			}
			if (!$info['moderate_mg_id'])
			{
				$this->logError($id, 'No member or group ID number provided');
				return false;
			}

			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$info['moderate_pid'] = ($info['moderate_pid']) ? $this->getLink($info['moderate_pid'], 'tracker_projects') : 0;

			// Is this a member or a group?
			if ($info['moderate_type'] == 'member')
			{
				$info['moderate_mg_id'] = $this->getLink($info['moderate_mg_id'], 'members', false, true);
			}
			elseif ($info['moderate_type'] == 'group')
			{
				$info['moderate_mg_id'] = $this->getLink($info['moderate_mg_id'], 'groups', false, true);
			}
			
			unset($info['moderate_id']);
			$this->DB->insert( 'tracker_moderators', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'tracker_moderators');
			
			return true;
		}
	
		/**
		 * Convert Moderator Log
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertModLog($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['member_id'])
			{
				$this->logError($id, 'No member ID provided');
				return false;
			}
			if (!$info['issue_id'])
			{
				$this->logError($id, 'No issue ID provided');
				return false;
			}
			if (!$info['project_id'])
			{
				$this->logError($id, 'No project ID provided');
				return false;
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$info['project_id'] = $this->getLink($info['project_id'], 'tracker_projects');
			$info['issue_id'] = $this->getLink($info['issue_id'], 'tracker_issues');
			$info['post_id'] = ($info['post_id']) ? $this->getLink($info['post_id'], 'tracker_posts') : 0;
			$info['member_id'] = $this->getLink($info['member_id'], 'members', false, true);

			unset($info['id']);
			$this->DB->insert( 'tracker_logs', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'tracker_logs');
						
			return true;
		}
		
	
	}
	
?>