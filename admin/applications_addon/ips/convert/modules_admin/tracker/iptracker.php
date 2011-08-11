<?php
/**
 * IPS Converters
 * IP.Tracker 1.3 Converters
 * IP.Tracker Merge Tool
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
		'key'	=> 'iptracker',
		'name'	=> 'IP.Tracker 1.3',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'ipboard', 'newdb' => false),
		));

	class admin_convert_tracker_iptracker extends ipsCommand
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
				'tracker_fields_data'	=> array(),
				'tracker_categories'	=> array(),
				'tracker_projects'		=> array('members', 'forum_perms', 'tracker_fields_data'),
				'tracker_moderators'	=> array('members', 'groups', 'tracker_projects'),
				'tracker_issues'		=> array('members', 'tracker_projects', 'tracker_categories'),
				'tracker_posts'			=> array('members', 'tracker_issues'),
				'tracker_attachments'	=> array('attachments_type', 'tracker_posts'),
				'tracker_field_changes'	=> array('members', 'tracker_issues', 'tracker_fields_data'),
				'tracker_logs'			=> array('tracker_projects', 'tracker_issues', 'tracker_posts', 'members'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_tracker.php' );
			$this->lib =  new lib_tracker( $registry, $html, $this );

	        $this->html = $this->registry->output->loadTemplate( 'cp_skin_convert' );
			$this->lib->sendHeader( 'IP.Tracker Merge Tool' );

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
				case 'tracker_attachments':
					return $this->lib->countRows('attachments', "attach_rel_module='tracker'");
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
				case 'tracker_attachments':
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
		private function fixPostData($post)
		{
			return $post;
		}

		/**
		 * Convert Custom Fields
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_tracker_fields_data()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'tracker_fields_data',
							'order'		=> 'field_id ASC',
						);

			$loop = $this->lib->load('tracker_fields_data', $main);

			if (!$this->request['st'])
			{
				$get = unserialize($this->settings['conv_extra']);
				$us = $get[$this->app['name']];
				$us['tracker_fields'] = array();
				$get[$this->app['name']] = $us;
				IPSLib::updateSettings(array('conv_extra' => serialize($get)));
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertField($row['field_id'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Statuses
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_tracker_categories()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'tracker_categories',
							'order'		=> 'cat_id ASC',
						);

			$loop = $this->lib->load('tracker_categories', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertStatus($row['cat_id'], $row);
			}

			$this->lib->next();

		}


		/**
		 * Convert Projects
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_tracker_projects()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'tracker_projects',
							'order'		=> 'project_id ASC',
						);

			$loop = $this->lib->load('tracker_projects', $main, array('tracker_ptracker'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertProject($row['project_id'], $row);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'tracker_ptracker', 'where' => "project_id={$row['project_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertProjectSubscription($tracker['ptid'], $tracker);
				}
			}

			$this->lib->next();

		}

		/**
		 * Convert Issues
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_tracker_issues()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'tracker_issues',
							'order'		=> 'issue_id ASC',
						);

			$loop = $this->lib->load('tracker_issues', $main, array('tracker_itracker'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Seperate data
				foreach(array_keys($row) as $key)
				{
					if (preg_match('/field_(.+?)/', $key))
					{
						$custom[$key] = $row[$key];
					}
					else
					{
						$info[$key] = $row[$key];
					}
				}

				$this->lib->convertIssue($row['issue_id'], $info, $custom);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'tracker_itracker', 'where' => "issue_id={$row['issue_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertIssueSubscription($tracker['itid'], $tracker);
				}
			}

			$this->lib->next();

		}

		/**
		 * Convert Posts
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_tracker_posts()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'tracker_posts',
							'order'		=> 'pid ASC',
						);

			$loop = $this->lib->load('tracker_posts', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$row['post'] = $this->fixPostData($row['post']);
				$this->lib->convertPost($row['pid'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Issue History
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_tracker_field_changes()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'tracker_field_changes',
							'order'		=> 'field_change_id ASC',
						);

			$loop = $this->lib->load('tracker_field_changes', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertIssueLog($row['field_change_id'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Attachments
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_tracker_attachments()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('tracker_attachments', array('attach_path'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'attachments',
							'where'		=> "attach_rel_module='tracker'",
							'order'		=> 'attach_id ASC',
						);

			$loop = $this->lib->load('tracker_attachments', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('tracker_attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_ipboard/uploads):')), 'path');

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$path = $us['attach_path'];

			//-----------------------------------------
			// Check all is well
			//-----------------------------------------

			if (!is_writable($this->settings['upload_dir']))
			{
				$this->lib->error('Your IP.Board upload path is not writeable. '.$this->settings['upload_dir']);
			}
			if (!is_readable($path))
			{
				$this->lib->error('Your remote upload path is not readable.');
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Send em on
				$done = $this->lib->convertAttachment($row['attach_id'], $row, $path, false, true);

				// Fix inline attachments
				if ($done === true)
				{
					$aid = $this->lib->getLink($row['attach_id'], 'attachments');

					$pid = $this->lib->getLink($row['attach_rel_id'], 'tracker_posts');
					$attachrow = $this->DB->buildAndFetch( array( 'select' => 'post', 'from' => 'tracker_posts', 'where' => "pid={$pid}" ) );
					$save = preg_replace("#(\[attachment=)({$row['attach_id']}+?)\:([^\]]+?)\]#ie", "'$1'. $aid .':$3]'", $attachrow['post']);
					$this->DB->update('tracker_posts', array('post' => $save), "pid={$pid}");
				}

			}

			$this->lib->next();

		}

		/**
		 * Convert Moderators
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_tracker_moderators()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'tracker_moderators',
							'order'		=> 'moderate_id ASC',
						);

			$loop = $this->lib->load('tracker_moderators', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertModerator($row['moderate_id'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Moderator Logs
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_tracker_logs()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'tracker_logs',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('tracker_logs', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertModLog($row['id'], $row);
			}

			$this->lib->next();

		}

	}

