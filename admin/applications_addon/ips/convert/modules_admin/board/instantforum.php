<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * FusionBB
 * Last Update: $Date: 2010-07-22 11:29:06 +0200(gio, 22 lug 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 447 $
 */

	$info = array(
		'key'	=> 'instantforum',
		'name'	=> 'InstantForum',
		'login'	=> false,
	);
	
	class admin_convert_board_instantforum extends ipsCommand
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
			//-----------------------------------------
			// What can this thing do?
			//-----------------------------------------
			
			// array('action' => array('action that must be completed first'))
			$this->actions = array(
				'forum_perms'	=> array(),
				'groups' 		=> array('forum_perms'),
				'members'		=> array('groups'),
				'forums'		=> array('forum_perms'),
				'moderators'	=> array('members', 'forums'),
				'topics'		=> array('members', 'forums'),
				'posts'			=> array('members', 'topics'),
				'polls'			=> array('topics', 'members', 'forums'),
				'pms'			=> array('members'),
				'ranks'			=> array(),
				'attachments'	=> array('posts', 'pms'),
				);
					
			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );
	
	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'InstantForum &rarr; IP.Board Converter' );
	
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
				case 'forum_perms':
				case 'groups':
					return $this->lib->countRows('InstantForum_PermissionSets');
					break;
					
				case 'forums':
					return  $this->lib->countRows('InstantForum_Forums');
					break;
					
				case 'members':
					return $this->lib->countRows('InstantForum_Users');
					break;
					
				case 'topics':
					return  $this->lib->countRows('InstantForum_Topics', 'ParentID=0');
					break;
					
				case 'posts':
					return  $this->lib->countRows('InstantForum_Topics');
					break;
					
				case 'polls':
					return  $this->lib->countRows('InstantForum_Polls');
					break;
					
				case 'pms':
					return $this->lib->countRows('InstantForum_PrivateMessages');
					break;
					
				case 'attachments':
					return $this->lib->countRows('InstantForum_Attachments');
					break;
				
				case 'moderators':
					return $this->lib->countRows('InstantForum_ForumsModerators');
					break;
					
				case 'ranks':
					return  $this->lib->countRows('InstantForum_UserLevels');
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
				case 'members':
				case 'groups':
				case 'forum_perms':
				case 'ranks':
				//case 'attachments':
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
			// We have to sort out these odd spoilers before we do line breaks
			$post = preg_replace('#\[spoilerq:\d+?\](.+?)\[/spoilerq\]\s+?\[spoilera:\d+\](.+?)\[/spoilera\]#i', '$1[spoiler]$2[/spoiler]', $post);
			
			// New lines
			$post = nl2br($post);
			
			// Images
			$post = str_replace('[image]', '[img]', $post);
			$post = str_replace('[lightbox]', '[img]', $post);
			$post = str_replace('[/image]', '[/img]', $post);
			$post = str_replace('[/lightbox]', '[/img]', $post);
			
			// Highlight can just become bold and italics
			$post = str_replace('[highlight]', '[b][i]', $post);
			$post = str_replace('[/highlight]', '[/i][/b]', $post);
			
			// Lists
			$post = str_replace('[li]', '[*]', $post);
			$post = str_replace('[/li]', '', $post);
			
			// Quotes
			$post = preg_replace("#\[quote=(.+)\]#", "[quote name=$1]", $post);
						
			return $post;
		}
		
		/**
		 * Convert forum permissions
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_forum_perms()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------
			$this->lib->saveMoreInfo('forum_perms', 'map');
			
			//---------------------------
			// Set up
			//---------------------------
			$main = array( 'select' => '*',
						   'from'   => 'InstantForum_PermissionSets',
						   'order'  => 'PermissionID ASC' );
						
			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------
			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'PermissionID', 'nf' => 'PermissionName'));

			//---------------------------
			// Loop
			//---------------------------
			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['PermissionID'], $row['PermissionName']);			
			}
			
			$this->lib->next();
		}
		
		/**
		 * Convert groups
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_groups()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------
			$this->lib->saveMoreInfo('groups', 'map');
			
			//---------------------------
			// Set up
			//---------------------------
			$main = array( 'select' => '*',
							'from'  => 'InstantForum_PermissionSets',
							'order' => 'PermissionID ASC' );
						
			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------
			$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'PermissionID', 'nf' => 'PermissionName'));

			//---------------------------
			// Loop
			//---------------------------
			$groups = array();
			
			// Loop
			foreach( $loop as $row )
			{			
				$save = array( 'g_title'   => $row['PermissionName'],
							   'g_perm_id' => $row['PermissionID'] );
				$this->lib->convertGroup($row['PermissionID'], $save);			
			}
						
			// Next, please!			
			$this->lib->next();
		}
		
				
		/**
		 * Convert members
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_members()
		{
			
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------
			$pcpf = array( 'WebAddress' => 'Homepage',
						   'Interests'  => 'Interests',
						   'Notes'		=> 'User Notes',
						   'Location'	=> 'Location',
						   'ICQ'		=> 'ICQ Number',
						   'AIM'		=> 'AIM ID',
						   'Yahoo'		=> 'Yahoo ID',
						   'MSN'		=> 'MSN ID' );
			
			$this->lib->saveMoreInfo('members', array_keys($pcpf));
			
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array( 'select'   => 'u.UserID, u.WebAddress, u.ICQ, u.AIM, u.Yahoo, u.MSN, CAST(u.Notes AS text) AS Notes, u.Location, CAST(u.Interests AS text) AS Interests, CAST(u.Biography AS text) AS Biography, CAST(u.PostSignature AS text) AS PostSignature, u.DOBDay, u.DOBMonth, u.DOBYear, u.PostCount, u.LastPostDate, u.ReceiveEmailFromAdmins, u.ReceiveEmailFromMembers, u.EnablePM, u.ReceivePMEmailNotification, u.UserLevelTitle, u.ViewSignatures, u.ViewImages, u.ViewEmotIcons, u.ViewAvatars',
						   'from' 	  => array( 'InstantForum_Users' => 'u' ),
						   'add_join' => array( array( 'select'	=> 'au.EmailAddress, au.Username, au.PrimaryRoleID, au.IPAddress, au.TimeZoneOffset, au.CreatedDate, au.LastLoginDate, au.Password',
						   								 'from'		=> array( 'InstantASP_Users' => 'au' ),
						   								 'where'		=> 'u.UserID = au.UserID',
						   								 'type'		=> 'inner' ),
						   						  array( 'select'	=> 'r.AdministratorRole',
						   						  		 'from'		=> array( 'InstantASP_Roles' => 'r' ),
						   						  		 'where'		=> 'au.PrimaryRoleID = r.RoleID',
						   						  		 'type'		=> 'left' ) ),
						   'order'	  => 'u.UserID ASC',
						   'limit' 	  => array( $start, $end ) );
			$loop = $this->lib->load('members', $main);
			
			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------
			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();
			

			// And those custom profile fields
			$options = array('x' => '-Skip-');
			$this->DB->build(array('select' => '*', 'from' => 'pfields_data'));
			$this->DB->execute();
			while ($row = $this->DB->fetch())
			{
				$options[$row['pf_id']] = $row['pf_title'];
			}
			foreach ($pcpf as $id => $name)
			{
				$ask[$id] = array('type' => 'dropdown', 'label' => 'Custom profile field to store '.$name.': ', 'options' => $options, 'extra' => $extra );
			}
			
			$this->lib->getMoreInfo('members', $loop, $ask, 'path');
			
			//---------------------------
			// Loop
			//---------------------------
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Set info
				//-----------------------------------------

				// Work out group
				ipsRegistry::DB('hb')->build(array( 'select' => 'r.RoleID',
													'from' => array( 'InstantASP_UsersRoles' => 'r' ),
													'add_join' => array( array( 'select' => 'p.PermissionID',
																				'from' => array( 'InstantForum_PermissionSetsRoles' => 'p' ),
																				'where' => 'r.RoleID = p.PermissionID',
																				'type' => 'inner' ) ),
													'where' => "r.UserID='{$row['UserID']}'"));
				$groupRes = ipsRegistry::DB('hb')->execute();
				$userGroups = array();
				while ( $group = ipsRegistry::DB('hb')->fetch($groupRes) )
				{
					$userGroups[$group['PermissionID']] = $group['PermissionID'];
				}
				unset($userGroups[$row['PrimaryRoleID']]);
				
				// Basic info				
				$info = array( 'id'			 	  => $row['UserID'],
							   'group'		 	  => $row['PrimaryRoleID'],
							   'secondary_groups' => implode(',', $userGroups),
							   'joined'		 	  => $this->myStrToTime($row['CreatedDate']),
							   'username'    	  => $row['Username'],
							   'displayname' 	  => $row['Username'],
							   'email'		 	  => $row['EmailAddress'],
							   'password'	 	  => $row['Password'] );
				
				// Member info

				$members = array( 'posts'			  => $row['PostCount'],
								  'hide_email' 		  => 1,
								  'title'			  => $row['UserLevelTitle'],
								  'ip_address'		  => $row['IPAddress'],
								  'last_visit'		  => $this->myStrToTime($row['LastLoginDate']),
								  'last_activity'	  => $this->myStrToTime($row['LastLoginDate']),
								  'last_post'		  => $this->myStrToTime($row['LastPostDate']),
								  'view_sigs'		  => $row['ViewSignatures'],
								  'view_avs'		  => $row['ViewAvatars'],
								  'time_offset'		  => $row['TimeZoneOffset'],
								  'email_pm'		  => $row['ReceivePMEmailNotification'],
								  'bday_day'		  => ($row['DOBDay'] > 0) ? $row['DOBDay'] : '',
								  'bday_month'		  => ($row['DOBMonth'] > 0) ? $row['DOBMonth'] : '',
								  'bday_year'		  => ($row['DOBYear'] > 0) ? $row['DOBYear'] : '',
								  'allow_admin_mails' => $row['ReceiveEmailFromAdmins'] );
					
				// Profile
				$profile = array( 'signature'   => $this->fixPostData($row['PostSignature']),
								  'pp_about_me' => $this->fixPostData($row['Biography']) );
							
				//-----------------------------------------
				// And go!
				//-----------------------------------------
				$this->lib->convertMember($info, $members, $profile, array(), $path);
			}
			$this->lib->next();
		}
		
		/**
		 * Convert Forums
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_forums()
		{			
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'InstantForum_Forums',
							'order'		=> 'ForumID ASC',
						);
									
			$loop = $this->lib->load('forums', $main);
												
			//---------------------------
			// Loop
			//---------------------------
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Permissions will need to be reconfigured
				$perms = array();
				
				//-----------------------------------------
				// And go
				//-----------------------------------------
												
				$save = array( 'sub_can_post'	=> ($row['IsCategory'] == 1) ? 0 : 1,
							   'status'		=> $row['ClosedForum'] == 0 ? 0 : 1,
							   'topics'		=> $row['TotalTopics'],
							   'posts'			=> $row['TotalPosts'],
							   'position'		=> $row['SortOrder'],
							   'name'			=> $row['Name'],
							   'description'	=> $row['Description'],
							   'parent_id'		=> ($row['ParentID'] == 0) ? -1 : $row['ParentID'],
							   'redirect_on'	=> $row['RedirectURL'] != '' ? 1 : 0,
							   'redirect_url'	=> $row['RedirectURL'],
							   'redirect_hits'	=> $row['RedirectClicks'] );
				
				$this->lib->convertForum($row['ForumID'], $save, $perms);	
			}
			$this->lib->next();		
		}
		
		/**
		 * Convert Moderators
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_moderators()
		{
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' => 'm.ForumModeratorID, m.ForumID, m.UserID',
							'from'	 => array( 'InstantForum_ForumsModerators' => 'm' ),
							'add_join' => array( array( 'select' => 'au.Username',
						   								 'from'	 => array( 'InstantASP_Users' => 'au' ),
						   								 'where' => 'm.UserID = au.UserID',
						   								 'type'	 => 'inner' ) ),
							'order'  => 'm.ForumModeratorID ASC' );
			
			$loop = $this->lib->load('moderators', $main);
						
			//---------------------------
			// Loop
			//---------------------------
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array( 'forum_id'	  => $row['ForumID'],
							   'member_name'  => $row['Username'],
							   'member_id'	  => $row['UserID'] );
				$this->lib->convertModerator($row['ForumModeratorID'], $save);			
			}
			$this->lib->next();		
		}
		
		/**
		 * Convert Topics
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_topics()
		{
			//---------------------------
			// Set up
			//---------------------------
			$main = array( 'select'   => 't.*',
						   'from' 	  => array( 'InstantForum_Topics' => 't' ),
						   'add_join' => array( array( 'select' => 'au.Username',
						   							   'from'	=> array( 'InstantASP_Users' => 'au' ),
						   							   'where'  => 't.UserID = au.UserID',
						   							   'type'   => 'inner' ) ),
						   'where'	  => 't.ParentID = 0', 
						   'order'	  => 't.TopicID ASC' );

			$loop = $this->lib->load('topics', $main, array('tracker'));
						
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array( 'forum_id'		 => $row['ForumID'],
							   'starter_id'		 => $row['UserID'],
							   'starter_name'	 => $row['Username'],
							   'title'			 => $row['Title'],
							   'description'	 => $row['Description'],
							   'posts'			 => $row['Replies'],
							   'views'			 => $row['Views'],
							   'start_date'		 => $this->myStrToTime($row['DateStamp']),
							   'pinned'			 => $row['IsPinned'],
							   'state'			 => $row['IsLocked'] ? 'open' : 'closed',
							   'topic_hasattach' => $row['HasAttachments'],
							   'approved'		 => 1 );
				
				$this->lib->convertTopic($row['TopicID'], $save);
				
				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'InstantForum_TopicSubscriptions', 'where' => "TopicID={$row['TopicID']}"));
				$topicSubsRes = ipsRegistry::DB('hb')->execute();
				while ( $tracker = ipsRegistry::DB('hb')->fetch($topicSubsRes) )
				{
					$savetracker = array( 'member_id'	=> $tracker['UserID'],
										  'topic_id'	=> $tracker['TopicID'],
										  'topic_track_type' => 'delayed' );					
					$this->lib->convertTopicSubscription($tracker['TopicSubscriptionID'], $savetracker);	
				}
			}
			$this->lib->next();			
		}
		
	/**
	 * Convert Polls
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_polls()
	{
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => 'p.*',
					   'from' 	=> array( 'InstantForum_Polls' => 'p'),
					   'add_join' => array( array( 'select' => 't.*',
					   							   'from'   => array( 'InstantForum_Topics' => 't'),
					   							   'where'  => 'p.PostID = t.PostID',
					   							   'type'   => 'left' ) ),
					   'order'	=> 'p.PollID ASC' );
		
		$loop = $this->lib->load('polls', $main, array('voters'));
					
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Options are stored in one place...
			//-----------------------------------------
			$choices = array();
			$votes = array();
			$totalVotes = 0;
			
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'InstantForum_PollAnswers', 'where' => "PollID='{$row['PollID']}'"));
			$choiceRes = ipsRegistry::DB('hb')->execute();
			while ( $choice = ipsRegistry::DB('hb')->fetch($choiceRes) )
			{
				//-----------------------------------------
				// Convert votes
				//-----------------------------------------
				
				ipsRegistry::DB('hb')->build(array('select' => 'UserID', 'from' => 'InstantForum_PollVotes', 'where' => "PollAnswerID='{$row['PollAnswerID']}'"));
				$voterRes = ipsRegistry::DB('hb')->execute();
				$voteCount = 0;
				while ( $voter = ipsRegistry::DB('hb')->fetch($voterRes) )
				{
					// Do we already have this user's votes
					if (in_array($voter['UserID'], $votes))
					{
						continue;
					}
					
					$vsave = array( 'vote_date'		 => time(),
									'tid'			 => $row['TopicID'],
									'member_id'		 => $voter['UserID'],
									'forum_id'		 => $row['ForumID'],
									'member_choices' => serialize(array()) );
				
					$this->lib->convertPollVoter($voter['PollAnswerID'] . '-' . $voter['UserID'], $vsave);
					$voteCount++;
				}
			
				$choices[ $choice['PollAnswerID'] ] = $choice['AnswerText'];
				$votes[ $choice['PollAnswerID'] ]	= $voteCount;
				$totalVotes += $voteCount;
			}
			
			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------
			$poll_array = array( // InstantForum only allows one question per poll
								 1 => array( 'question'	=> $row['QuestionText'],
								 			 'choice'	=> $choices,
											 'votes'	=> $totalVotes ) );
			$save = array( 'tid'		=> $row['TopicID'],
						   'start_date'	=> $this->myStrToTime($row['DateStamp']),
						   'choices'   	=> addslashes(serialize($pollArray)),
						   'starter_id'	=> $row['UserID'],
						   'votes'     	=> $totalVotes,
						   'forum_id'  	=> $row['ForumID'],
						   'poll_question'	=> $row['QuestionText'] );

			$this->lib->convertPoll($row['Poll_ID'], $save);
		}
		$this->lib->next();
	}
		
		/**
		 * Convert Posts
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_posts()
		{
			//---------------------------
			// Set up
			//---------------------------
			$main = array( 'select'   => 't.*',
						   'from' 	  => array( 'InstantForum_Topics' => 't' ),
						   'add_join' => array( array( 'select' => 'p.Message',
						   							   'from'   => array( 'InstantForum_Messages' => 'p' ),
						   							   'where'  => 't.PostID = p.PostID',
						   							   'type'   => 'inner' ),
						   						array( 'select' => 'au.Username',
						   							   'from'	=> array( 'InstantASP_Users' => 'au' ),
						   							   'where'  => 't.UserID = au.UserID',
						   							   'type'   => 'inner' ) ),
						   'order'	  => 't.TopicID ASC' );
			$loop = $this->lib->load('posts', $main);
						
			//---------------------------
			// Loop
			//---------------------------	
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				$save = array( 'append_edit' => $row['EditUserID'] > 0 ? 1 : 0,
							   'edit_time'   => $row['EditUserID'] > 0 ? $this->myStrToTime($row['EditDateStamp']) : '',
							   'author_id'   => $row['UserID'],
							   'author_name' => $row['Username'],
							   'use_sig'	 => 1,
							   'use_emo'	 => 1,
							   'ip_address'	 => $row['IPAddress'],
							   'post_date'	 => $this->myStrToTime($row['DateStamp']),
							   'post'		 => $this->fixPostData($row['Message']),
							   'queued'		 => 0,
							   'topic_id'	 => $row['TopicID'] );

				$this->lib->convertPost($row['PostID'], $save);
			}
			$this->lib->next();	
		}
		
		/**
		 * Convert PMs
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_pms()
		{
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'InstantForum_PrivateMessages',
							'order'		=> 'PrivateMessageID ASC',
						);
			
			$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));
						
			//---------------------------
			// Loop
			//---------------------------
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Post Data
				//-----------------------------------------
				$posts = array( array( 'msg_id'			   => $row['PrivateMessageID'],
									   'msg_topic_id'      => $row['PrivateMessageID'],
									   'msg_date'          => $this->myStrToTime($row['DateStamp']),
									   'msg_post'          => $this->fixPostData($row['Message']),
									   'msg_post_key'      => md5(microtime()),
									   'msg_author_id'     => intval($row['AuthorID']),
									   'msg_is_first_post' => 1 ) );	
					
				//-----------------------------------------
				// Map Data
				//-----------------------------------------
				$map_master = array( 'map_topic_id'    => $row['PrivateMessageID'],
									 'map_folder_id'   => 'myconvo',
									 'map_read_time'   => 0,
							   		 'map_last_topic_reply' => $this->myStrToTime($row['DateStamp']),
									 'map_user_active' => 1,
									 'map_user_banned' => 0,
									 'map_has_unread'  => $row['ReadReceipt'] == 0 ? 1 : 0,
									 'map_is_system'   => 0 );
					
				$maps = array();
				$maps[] = array_merge( $map_master, array( 'map_user_id' => intval($row['AuthorID']), 'map_is_starter' => 1 ) );
				$maps[] = array_merge( $map_master, array( 'map_user_id' => intval($row['RecipientID']), 'map_is_starter' => 0 ) );
		
				//-----------------------------------------
				// Map Data
				//-----------------------------------------
				$topic = array( 'mt_id'			     => $row['PrivateMessageID'],
								'mt_date'		     => $this->myStrToTime($row['DateStamp']),
								'mt_title'		     => $row['Title'],
								'mt_starter_id'	     => intval($row['AuthorID']),
								'mt_start_time'      => $this->myStrToTime($row['DateStamp']),
								'mt_last_post_time'  => $this->myStrToTime($row['DateStamp']),
								'mt_invited_members' => serialize( array( intval($row['RecipientID']) => intval($row['RecipientID']) ) ),
								'mt_to_count'		 => 1,
								'mt_to_member_id'	 => intval($row['RecipientID']),
								'mt_replies'		 => 0,
								'mt_is_draft'		 => 0,
								'mt_is_deleted'		 => 0,
								'mt_is_system'		 => 0 );		
				//-----------------------------------------
				// Go
				//-----------------------------------------
				$this->lib->convertPM($topic, $posts, $maps);
			}	
			$this->lib->next();	
		}

		/**
		 * Convert Ranks
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_ranks()
		{
			
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------
			
			$this->lib->saveMoreInfo('ranks', array('rank_opt'));
			
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' => '*',
							'from' 	 => 'InstantForum_UserLevels',
							'order'	 => 'UserLevelID ASC' );
						
			$loop = $this->lib->load('ranks', $main);
			
			//-----------------------------------------
			// We need to know what do do with duplicates
			//-----------------------------------------
			$this->lib->getMoreInfo('ranks', $loop, array('rank_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate ranks?')));
			
			$get[$this->lib->app['name']] = $us;
			IPSLib::updateSettings(array('conv_extra' => serialize($get)));
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array( 'posts' => $row['MinPosts'],
							   'title' => $row['Description'],
							   'pips'  => $row['NoOfBlocks'] );
				$this->lib->convertRank($row['titles_min'].'-'.$row['titles_name'], $save, $us['rank_opt']);			
			}
			$this->lib->next();
		}
		
		/**
		 * Convert Attachments
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_attachments()
		{
			
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------
			
			//$this->lib->saveMoreInfo('attachments', array('attach_path'));
			
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> 'a.*',
							'from' 		=> array( 'InstantForum_Attachments' => 'a' ),
							'add_join' => array( array( 'select' => 'p.PostID, p.IsPrivateMessage',
														'from'   => array( 'InstantForum_AttachmentsPosts' => 'p' ),
														'where'  => 'a.AttachmentID = p.AttachmentID',
														'type'   => 'inner' ) ),
							'order'		=> 'a.AttachmentID ASC' );
						
			$loop = $this->lib->load('attachments', $main);
			
			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------
						
			//$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_fusionbb/fbbuploads):')), 'path');
			
			//$get = unserialize($this->settings['conv_extra']);
			//$us = $get[$this->lib->app['name']];
			//$path = $us['attach_path'];
			
			//-----------------------------------------
			// Check all is well
			//-----------------------------------------
			
			if (!is_writable($this->settings['upload_dir']))
			{
				$this->lib->error('Your IP.Board upload path is not writeable. '.$this->settings['upload_dir']);
			}
			//if (!is_readable($path))
			//{
				//$this->lib->error('Your remote upload path is not readable.');
			//}
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// What's the extension?
				$ext = explode('.', $row['Filename']);
				$extension = strtolower(array_pop( $ext ));
				
				// Is this an image?
				$image = in_array( $extension, array( 'png', 'jpg', 'jpeg', 'gif' ) ) ? TRUE : FALSE;
				
				$save = array( 'attach_ext'		   => $extension,
							   'attach_file'	   => $row['Filename'],
							   'data'			   => $row['AttachmentBLOB'],
							   'attach_is_image'   => $image,
							   'attach_hits'	   => $row['Views'],
							   'attach_date'	   => $this->myStrToTime($row['DateStamp']),
							   'attach_member_id'  => $row['UserID'],
							   'attach_filesize'   => $row['ContentLength'],
							   'attach_rel_id'	   => $row['PostID'],
							   'attach_rel_module' => ($row['IsPrivateMessage']) ? 'msg' : 'post' );
				
				$this->lib->convertAttachment($row['AttachmentID'], $save, NULL, TRUE);
			}
			$this->lib->next();
		}
		
		private function myStrToTime($value) { return is_object($value) ? strtotime(DATE_FORMAT($value, DATE_ATOM)) : intval(strtotime((string)($value)));}
	}