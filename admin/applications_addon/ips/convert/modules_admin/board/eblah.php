<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * UBB.Threads
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
		'key'	=> 'eblah',
		'name'	=> 'eBlah',
		'login'	=> false,
		'nodb' => TRUE
	);
	
	$custom = array( 'directory' => 'eBlah base directory. Contains directories: blahdocs, Boards, etc.'  );
	
	class admin_convert_board_eblah extends ipsCommand
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
			$this->actions = array( 'forum_perms' => array(),
									'groups' 	  => array('forum_perms'),
									'members'	  => array('groups'),
									'forums'	  => array('forum_perms'),
									'topics'	  => array('members', 'forums'),
									'posts'		  => array('members', 'topics'),
									'pms'		  => array('members'),
									'ranks'		  => array() );
					
			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );
	
	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'Dokuwiki &rarr; IP.Board Converter' );
	
			//-----------------------------------------
			// Parser
			//-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'sources/handlers/han_parse_bbcode.php' );
			$this->parser           =  new parseBbcode( $registry );
			$this->parser->parse_smilies = 1;
		 	$this->parser->parse_bbcode  = 1;
		 	$this->parser->parsing_section = 'convert';
		 	
			$us = unserialize($this->settings['conv_extra']);
			$this->sourceDirectory = $us[$this->lib->app['name']]['core']['directory'];

			//-----------------------------------------
			// What are we doing?
			//-----------------------------------------
			
			if (array_key_exists($this->request['do'], $this->actions) or $this->request['do'] == 'boards' or $this->request['do'] == 'bad_emails')
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
				case 'groups':
				case 'forum_perms':
					return count($this->_fetchUserGroups());
					break;
					
				case 'members':
					return count($this->_parseMemberListFile());
					break;
					
				case 'forums':
					return count($this->_parseCategoryFile()) + count($this->_parseForumListFile());
					break;
					
				case 'topics':
				case 'posts':
					$forums = $this->_parseForumListFile();
					$totalCount = 0;
					
					foreach ( array_keys($forums) as $forumKey )
					{
						$tmpData = $this->_parseForumFile($forumKey, 'ino');
						$totalCount += $tmpData[$action];
					}
					
					return $totalCount;
					break;
					
				case 'pms':
					$pmCount = 0;
					$members = $this->_parseMemberListFile();
					foreach ( $members as $memberId )
					{
						if ( $tmpData = $this->_parseMemberFile( $memberId, 'pm' ) )
						{
							$pmCount += count($tmpData);
						}
					}
					return $pmCount;
					break;
					
				case 'ranks':
					$i = 0;
					$ranks = $this->_parseRankFile ( );
					foreach ( $ranks AS $rank )
					{
						$i++;
					}
					return $i;
					break;
					
				default:
					return $this->lib->countRows(strtoupper($action));
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
				case 'groups':
				case 'forum_perms':
				//case 'ranks':
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
			return preg_replace_callback('#\[quote=(.*?)\]#', array(&$this, 'fixPostDataExt'), $post);
		}
		
		private function fixPostDataExt($matches)
		{
			$tmp = $this->_parseMemberFile( $matches[1], "dat" );

			return "[quote name='{$tmp["sn"]}']";
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
			
			$this->lib->prepare('forum_perms');
			//---------------------------
			// Fetch Data
			//---------------------------
			$forumPerms = $this->_fetchUserGroups();

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------
			$this->lib->getMoreInfo('forum_perms', $forumPerms, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'id', 'nf' => 'name'));

			//---------------------------
			// Loop
			//---------------------------
			foreach( $forumPerms as $row )
			{
				$this->lib->convertPermSet($row['id'], $row['name']);			
			}
			
			// Display
			$this->displayFinishScreen( 'forum_perms' );
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
			
			$this->lib->prepare('groups');
			//---------------------------
			// Fetch Data
			//---------------------------
			$groupNames = $this->_fetchUserGroups();

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------
			$this->lib->getMoreInfo('groups', $groupNames, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'id', 'nf' => 'name'));

			//---------------------------
			// Loop
			//---------------------------
			foreach( $groupNames as $row )
			{
				$save = array( 'g_title'   => $row['name'],
							   'g_perm_id' => $row['id'] );
				$this->lib->convertGroup($row['id'], $save);			
			}
						
			// Display
			$this->displayFinishScreen( 'groups' );
		}
		
		/**
		 * Convert members
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_members()
		{
			$this->lib->start = intval($this->request['st']);
			$this->lib->end = $this->lib->start + intval($this->request['cycle']) - 1;

			if ( $this->lib->start == 0 ) { $this->lib->prepare('members'); }

			$memberList = $this->_parseMemberListFile();
							
			//---------------------------
			// Loop
			//---------------------------
			if ( count($memberList) < $this->lib->start )
			{
				$this->displayFinishScreen( 'members' );
			}
			
			$userGroups = $this->_fetchUserGroups();
			
			for ( $i = $this->lib->start; $i <= $this->lib->end; $i++ )
			{
				$memberId = $memberList[$i];
				
				$memberData = $this->_parseMemberFile( $memberId, 'dat' );
				
				if ( !$memberData )
				{
					continue;
				}
				
				//-----------------------------------------
				// Set info
				//-----------------------------------------
				$membersGroups = array();
				foreach ( $userGroups as $group )
				{
					if ( in_array($memberId, $group['members']) )
					{
						$membersGroups[] = $group['id'];
					}
				}
				$group = array_shift($membersGroups);
													
				// Basic info				
				$info = array( 'id'				  => $memberId,
							   'group'			  => $group,
							   'secondary_groups' => implode(',', $membersGroups),
							   'joined'			  => $memberData['registered'],
							   'username'		  => $memberData['sn'],
							   'displayname'	  => $memberData['sn'],
							   'email'			  => $memberData['email'],
							   'md5pass'		  => $memberData['password'] );
							   
				if ( strlen($memberData['password']) != 32 )
				{
					print "<PRE>";print_r($memberData);exit;
				}
				
				// Member info
				$birthday = ($memberData['dob']) ? explode('/', $row['USER_BIRTHDAY']) : NULL;
				
				$members = array( 'posts'		  => intval($memberData['posts']),
								  'hide_email'    => intval($memberData['hidemail']),
								  'time_offset'   => intval($memberData['timezone']),
								  'title'		  => '',
								  'bday_day'	  => $memberData['dob'] ? $birthday[1] : '',
								  'bday_month'	  => $memberData['dob'] ? $birthday[0] : '',
								  'bday_year'	  => $memberData['dob'] ? $birthday[2] : '',
								  'ip_address'	  => '127.0.0.1',
								  'last_visit'	  => intval($memberData['lastvisit']),
								  'last_post'	  => 0,
								  'email_pm'      => 1,
								  'member_banned' => 0,
								  'view_sigs'	  => 1 );
					
				// Profile
				$profile = array( 'signature' => $this->fixPostData($memberData['sig']) );
				
				//-----------------------------------------
				// Avatars
				//-----------------------------------------
				//No avatars atm.
																				
				//-----------------------------------------
				// And go!
				//-----------------------------------------
				$this->lib->convertMember($info, $members, $profile, array(), '');			
			}
			
			$this->lib->next();
		}
		
		/**
		 * Convert Categories
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_forums()
		{
			$this->lib->prepare('forums');
			// Database changes?
			$dbChanges = $this->lib->databaseChanges('forums');
			if (is_array($dbChanges))
			{
				foreach ($dbChanges as $key => $value)
				{
					switch ($key)
					{
						case 'addfield':
							if (!$this->DB->checkForField($value[1], $value[0]))
							{
								$this->DB->addField( $value[0], $value[1], $value[2] );
							}
							break;
					}
				}
			}

			$categories = $this->_parseCategoryFile();
			$forums = $this->_parseForumListFile();
			
			$counter = 0;
			$parentId = -1;
			
			foreach ( $categories as $categoryId => $categoryData )
			{
				// Cycle each category to see if $categoryId is a sub category
				foreach ( $categories as $catData )
				{
					if ( in_array($categoryId, $catData[5]) )
					{
						// This category is a sub category, so treat as a forum.
						$parentId = $catData[1];
					}
				}
				
				$counter++;
				
				// -- $categoryData -- //
				// $categoryData[0] = title
				// $categoryData[1] = categoryId
				// $categoryData[2] = allowed user groups
				// $categoryData[3] = array of children forums
				// $categoryData[4] = description
				// $categoryData[5] = children categories
				
				$this->lib->convertForum( $categoryId, array( 'name'		=> $categoryData[0],
															  'description'	=> $categoryData[4],
															  'position'	=> $counter,
															  'parent_id'	=> $parentId ), array());
				
				$subCounter = 0;
				// Need to convert forums now as category stores the parentId of forums.											  
				foreach ( $categoryData[3] as $forumId )
				{
					// Make sure it exists
					if ( !isset($forums[$forumId]) ) { continue; }
					
					$subCounter++;
					
					// -- $categoryData -- //
					// $forums[$forumId][0] = forumId
					// $forums[$forumId][1] = description
					// $forums[$forumId][2] = Moderators
					// $forums[$forumId][3] = title
					// *The rest are permissions which we will ignore.
					
					$this->lib->convertForum( $forumId, array( 'name'		 => $forums[$forumId][3],
															   'description' => $forums[$forumId][1],
															   'position'	 => $subCounter,
															   'parent_id'	 => $categoryId ), array());
				}
			}
			
			// Display
			$this->displayFinishScreen( 'forums' );
		}
		
		/**
		 * Convert Topics
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_topics()
		{
			$this->lib->start = intval($this->request['st']);
			$this->lib->end = $this->lib->start + intval($this->request['cycle']) - 1;

			if ( $this->lib->start == 0 ) { $this->lib->prepare('topics'); }
			
			$topicData = $this->_parseTopicDb();
			
			$limitCounter = 0;
			
			$stickyData = $this->_parseStickyTopicsFile();
			
			//---------------------------
			// Loop
			//---------------------------
			foreach ( $topicData as $forumId => $topicList )
			{
				if ( $this->lib->start > ($limitCounter + count($topicList)) )
				{
					$limitCounter += count($topicList);
					continue;
				}
				
				foreach ( $topicList as $iteratorCount => $topicId)
				{
					$limitCounter++;
					if ( $this->lib->start > ($limitCounter - 1) )
					{
						continue;
					}
					
					$forumTopicList = $this->_parseForumFile($forumId,'msg');
//print "<PRE>";print 'ForumId: ' . $forumId . "<br />IteratorCount: " . $iteratorCount . "<br />topicId: " . $topicId . "<Br />limitCounter: " . $limitCounter . "<br />";print_r($topicList);print_r($forumTopicList);exit;				
					// Check topic exists
					if ( !isset($forumTopicList[$topicId] ) ) { continue; }
					
					// -- $forumTopicList[$topicId] -- //
					// $forumTopicList[$topicId][0] = topicId
					// $forumTopicList[$topicId][1] = title
					// $forumTopicList[$topicId][2] = starter id
					// $forumTopicList[$topicId][3] = start date
					// $forumTopicList[$topicId][4] = replies
					// $forumTopicList[$topicId][5] = poll
					// $forumTopicList[$topicId][6] = topic type
					// $forumTopicList[$topicId][7] = icon
					// $forumTopicList[$topicId][8] = last post
					// $forumTopicList[$topicId][9] = last poster
					
					$starterData = $this->_parseMemberFile( $forumTopicList[$topicId][2], 'dat' );
					
					$save = array( 'forum_id'		 => $forumId,
								   'title'		 	 => $forumTopicList[$topicId][1],
								   'views'			 => intval($this->_parseTopicFile($topicId, 'view')),
								   'posts'			 => $forumTopicList[$topicId][4],
								   'starter_name'	 => $starterData['sn'],
								   'starter_id'		 => $forumTopicList[$topicId][2],
								   'state'			 => 'open',
								   'approved'		 => 1,
								   'start_date'		 => $forumTopicList[$topicId][3],
								   'pinned'			 => is_array($stickyData[$forumId]) && intval(in_array($topicId, $stickyData[$forumId])),
								   'topic_hasattach' => 0,
								   'poll_state'		 => 0 );
					
					$this->lib->convertTopic($topicId, $save);
					
					if ( $limitCounter == ($this->lib->end - 1) )
					{
						$this->lib->next();
					}
				}
			}
			
			$this->displayFinishScreen( 'topics' );
		}
		
		/**
		 * Convert Posts
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_posts()
		{
			$this->lib->start = intval($this->request['st']);
			$this->lib->end = $this->lib->start + intval($this->request['cycle']) - 1;
			
if ( $this->lib->start == 0 ) { $this->lib->prepare('posts'); }
						
			$topicDb = $this->_parseTopicDb();
			$limitCounter = 0;
			$stickyData = $this->_parseStickyTopicsFile();

			//---------------------------
			// Loop iterates through each forum topic list
			//---------------------------
			foreach ( $topicDb as $forumId => $topicList )
			{
				//---------------------------------
				// This loop iterators through a specific forum topic list
				//---------------------------------
				foreach ( $topicList as $iteratorCount => $topicId )
				{
					$topicData = $this->_parseTopicFile($topicId, 'txt');

					if ( $this->lib->start > ($limitCounter + count($topicData)) )
					{
						$limitCounter += count($topicData);
						continue;
					}
					
					foreach ( $topicData as $pid =>  $postData )
					{
						$limitCounter++;
						if ( $this->lib->start > ($limitCounter - 1) )
						{
							continue;
						}
						
						//print $topicId;exit;//1225055354
								
						// -- $postData -- //
						// $postData[0] = starter id
						// $postData[1] = post
						// $postData[2] = ip address
						// $postData[3] = user email
						// $postData[4] = post date
						// $postData[5] = don't show emoticons
						// $postData[6] = locked
						// $postData[7] = atturl
						// $postData[8] = afile
						// $postData[9] = date modified
					
						$starterData = $this->_parseMemberFile( $postData[0], 'dat' );
							   
						$save = array( 'author_name' => $starterData['sn'],
									   'author_id'	 => $postData[0],
									   'topic_id'	 => $topicId,
									   'post'		 => $this->fixPostData( $postData[1] ),
									   'post_date'	 => $postData[4],
									   'use_sig'	 => 1,
									   'ip_address'  => $postData[2],
									   'use_emo'	 => intval($postData[5]) ? 0 : 1,
									   'queued'		 => intval($postData[6]) ? 1 : 0 );

						$this->lib->convertPost($topicId . $pid, $save);
						
						if ( $limitCounter == ($this->lib->end - 1) )
						{
							$this->lib->next();
						}
					}
				}
			}
			
			$this->displayFinishScreen( 'posts' );
		}

		/**
		 * Convert PMs
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_pms()
		{
			$this->lib->start = intval($this->request['st']);
			$this->lib->end = $this->lib->start + intval($this->request['cycle']) - 1;
			
			if ( $this->lib->start == 0 )
			{
				$this->lib->prepare('pms');
				$this->lib->prepare('pm_posts');
				$this->lib->prepare('pm_maps');
			}
			
			$memberList = $this->_parseMemberListFile();
			$limitCounter = 0;

			foreach ( $memberList as $memberId )
			{
				$memberPms = $this->_parseMemberFile( $memberId, 'pm' );
				
				if ( !is_array($memberPms) || count($memberPms) == 0 )
				{
					continue;
				}

				if ( $this->lib->start > ($limitCounter + count($memberPms)) )
				{
					$limitCounter += count($memberPms);
					continue;
				}
				
				foreach ( $memberPms as $memberPm )
				{
					$limitCounter++;
					if ( $this->lib->start > ($limitCounter - 1) )
					{
						continue;
					}
				
					// Unsure exactly what happens to "save" pms, but lets default to privacy over guessing
					if ( $memberPm[0] > 2 )
					{
						continue;
					}
				
					// -- $memberPm -- //
					// $memberPm[0] = folder ( 1 == inbox ) ( 2 == sent ) (3 == save)
					// $memberPm[1] = message time
					// $memberPm[2] = subject
					// $memberPm[3] = other user id
					// $memberPm[4] = message
					// $memberPm[5] = ip
					// $memberPm[6] = unread
					// $memberPm[7] = ?
					// $memberPm[8] = ?
					// $memberPm[9] = flag?
					// $memberPm[10] = emo
					
					if ( $memberPm[0] == 1 ) {
						$starterId = $this->lib->getLink($memberPm[3], 'members', true);
						$recipientId = $this->lib->getLink($memberId, 'members', true);
					} else {
						$starterId = $this->lib->getLink($memberId, 'members', true);
						$recipientId = $this->lib->getLink($memberPm[3], 'members', true);
					}
					
					if (!$starterId)
					{
						$this->lib->logError($starterId . ' - ' . $memberPm[1], 'Starter not found.');
						continue;
					}

					if (!$recipientId)
					{
						$this->lib->logError($starterId . ' - ' . $memberPm[1], 'Recipient not found.');
						continue;
					}
					
					$topic_id = $this->lib->getLink($starterId . ' - ' . $memberPm[1], 'pms' );
					
					if ( !$topic_id )
					{
						//-----------------------------------------
						// If no existing topic create one.
						//-----------------------------------------
						$topic = array( 'mt_date'		     => $memberPm[1],
										'mt_title'		     => $memberPm[2],
										'mt_starter_id'	     => $starterId,
										'mt_start_time'      => $memberPm[1],
										'mt_last_post_time'  => $memberPm[1],
										'mt_invited_members' => serialize( array( $recipientId ) ),
										'mt_to_count'		 => 1,
										'mt_to_member_id'	 => $recipientId,
										'mt_replies'		 => 0,
										'mt_is_draft'		 => 0,
										'mt_is_deleted'		 => 0,
										'mt_is_system'		 => 0 );

						$this->DB->insert( 'message_topics', $topic );
						$topic_id = $this->DB->getInsertId();
						$this->lib->addLink($topic_id, $starterId . ' - ' . $memberPm[1], 'pms');

						//-----------------------------------------
						// Create the sent/receive post
						//-----------------------------------------
						$post = array( 'msg_topic_id'      => $topic_id,
									   'msg_date'          => $memberPm[1],
									   'msg_post'          => $this->fixPostData($memberPm[4]),
									   'msg_post_key'      => md5(microtime()),
									   'msg_author_id'     => $starterId,
									   'msg_is_first_post' => 1 );

						$this->DB->insert( 'message_posts', $post );
						$inserted_id = $this->DB->getInsertId();
						$this->lib->addLink($inserted_id, $starterId . ' - ' . $memberPm[1], 'pm_posts');
					}
					
					// Need this stupid query to make sure we don't insert duplicates. Worthless eBlah.
					if ( !$this->DB->buildAndFetch( array( 'select' => '', 'from' => 'message_topic_user_map', 'where' => "map_topic_id='{$topic_id}' AND map_user_id='".($memberPm[0] == 1 ? $recipientId : $starterId)."' AND map_is_starter='".($memberPm[0] == 1 ? 0 : 1)."'" ) ) )
					{
						//-----------------------------------------
						// Map Data
						//-----------------------------------------
						$map_master = array( 'map_topic_id'    => $topic_id,
											 'map_folder_id'   => 'myconvo',
											 'map_last_topic_reply' => $memberPm[1],
											 'map_read_time'   => 0,
											 'map_user_active' => 1,
											 'map_user_banned' => 0,
											 'map_has_unread'  => 0,
											 'map_is_system'   => 0,
											 'map_user_id' => $memberPm[0] == 1 ? $recipientId : $starterId,
											 'map_is_starter' => $memberPm[0] == 1 ? 0 : 1 );
						// Check for dupes.
						if ( $this->lib->getLink ( $map_master['map_user_id'] . ' - ' . $memberPm[1], 'pm_maps', true ) )
						{
							$this->lib->logError ( $starterId . ' - ' . $memberPm[1], '(PM MAP) Duplicate PM Map, skipping.' );
							continue;
						}
						$this->DB->insert( 'message_topic_user_map', $map_master );
						$inserted_id = $this->DB->getInsertId();
						$this->lib->addLink($inserted_id, $map_master['map_user_id'] . ' - ' . $memberPm[1], 'pm_maps');
					}

					if ( $limitCounter == ($this->lib->end - 1) )
					{
						$this->lib->next();
					}
				}
			}
						
			$this->displayFinishScreen( 'pms' );	
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
			//$this->lib->saveMoreInfo('ranks', array('rank_opt'));
			
			$this->lib->prepare('ranks');
			
			//---------------------------
			// Set up
			//---------------------------
			/*$main = array(	'select' 	=> '*',
							'from' 		=> 'USER_TITLES',
							'order'		=> 'USER_TITLE_ID ASC',
						);*/
			
			$oldRanks = $this->_parseRankFile ( );
						
			//$loop = $this->lib->load('ranks', $main);
			
			//-----------------------------------------
			// We need to know what do do with duplicates
			//-----------------------------------------
			
			//$this->lib->getMoreInfo('ranks', $loop, array('rank_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate ranks?')));
			
			/*$us = unserialize ( )
			$get[$this->lib->app['name']] = $us;
			IPSLib::updateSettings(array('conv_extra' => serialize($get)));*/
			
			//---------------------------
			// Loop
			//---------------------------
			
			//while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			$i = 1;
			foreach ( $oldRanks AS $rank )
			{
				$save = array(
					'posts'	=> $rank['pcount'],
					'title'	=> $rank['name'],
				);
				$this->lib->convertRank($i, $save, 'local');	
				$i++;		
			}
			
			$this->displayFinishScreen('ranks');

		}
		
		// Parses the eBlah ranks file and returns it as an array
		private function _parseRankFile()
		{
			// Load the file to memory
			$rankFile = @file_get_contents($this->sourceDirectory . '/Prefs/Ranks2.txt');
			
			// Set the start/end of the arrays
			$rankFile = preg_replace('#(\w+?) => \{#', "'\\1' => array(", $rankFile);
			$rankFile = preg_replace('#\}#', "),", $rankFile);
			
			// Fix string values
			$rankFile = preg_replace("#(\w+?) = \'(.*?)\'#", "'\\1' => '\\2',", $rankFile);
			$rankFile = preg_replace("#(\w+?) = \((.*?)\)#", "'\\1' => '\\2',", $rankFile);
			
			$rankFile = "\$eblahRanks = array( " . $rankFile . " );";
			eval($rankFile);
			
			return $eblahRanks;
		}
		
		// Parses the eBlah forum list file and returns it as an array
		private function _parseForumListFile()
		{
			// Load the member list file to memory
			$forumsList = @file(trim($this->sourceDirectory . '/Boards/bdindex.db'), FILE_IGNORE_NEW_LINES);
			
			if ( !$forumsList )
			{
				return FALSE;
			}
			
			$forums = array();

			foreach ( $forumsList as $forumData )
			{
				$tmpData = explode( '/', $forumData );
				$forums[$tmpData[0]] = $tmpData;
			}
			
			return $forums;
		}
		
		// Parses the eBlah category list file and returns it as an array
		private function _parseCategoryFile()
		{
			// Load the member list file to memory
			$catsList = @file(trim($this->sourceDirectory . '/Boards/bdscats.db'), FILE_IGNORE_NEW_LINES);
			
			if ( !$catsList )
			{
				return FALSE;
			}
			
			$cats = array();

			foreach ( $catsList as $catData )
			{
				$tmpData = explode( '|', $catData );
				$tmpData[3] = explode( '/', $tmpData[3] );
				$tmpData[5] = explode( '/', $tmpData[5] );
				$cats[$tmpData[1]] = $tmpData;
			}
			
			return $cats;
		}
		
		// Parses the eBlah forum list file and returns it as an array
		private function _parseMemberListFile()
		{
			// Load the member list file to memory
			$memberList = @file(trim($this->sourceDirectory . '/Members/List.txt'), FILE_IGNORE_NEW_LINES);
			
			return $memberList;
		}
		
		// Returns an array of eBlah user group data
		private function _fetchUserGroups()
		{
			$rankFileData = $this->_parseRankFile();
			
			if ( !$rankFileData )
			{
				return FALSE;
			}
			
			$userGroups = array();
			foreach ( $rankFileData as $k => $v )
			{
				$v['id'] = $k;
				if ( isset($v['members']) )
				{
					$v['members'] = explode(',', $v['members']);
					$userGroups[$k] = $v;
				}
			}
			
			return $userGroups;
		}
		
		// Returns an array of eBlah user titles data
		private function _fetchUserTitles()
		{
			$rankFileData = $this->_parseRankFile();
			
			if ( !$rankFileData )
			{
				return FALSE;
			}
			
			$userTitles = array();
			foreach ( $rankFileData as $k => $v )
			{
				$v['id'] = $k;
				if ( isset($v['pcount']) )
				{
					$userTitles[$k] = $v;
				}
			}
			
			return $userTitles;
		}
		
		// Parses the eBlah topic db file and returns it as an array of forums with topic ids
		private function _parseTopicDb()
		{
			// Load the post db file to memory
			$topicList = @file(trim($this->sourceDirectory . '/Boards/Messages.db'), FILE_IGNORE_NEW_LINES);
			
			if ( !$topicList )
			{
				return FALSE;
			}
			
			$topics = array();

			foreach ( $topicList as $topic )
			{
				$tmpData = explode( '|', $topic );
				$topics[$tmpData[1]][] = $tmpData[0];
			}
			
			return $topics;
		}
		
		private function _parseForumFile($forumId, $fileType)
		{
			if ( ! in_array($fileType, array('hits', 'ino', 'mail', 'msg') ) )
			{
				return FALSE;
			}
			
			$forumFileLines = @file(trim($this->sourceDirectory . '/Boards/' . $forumId . '.' . $fileType), FILE_IGNORE_NEW_LINES);

			if ( !$forumFileLines )
			{
				return FALSE;
			}
			
			if ( $fileType == 'ino' )
			{
				return array( 'topics' => $forumFileLines[0], 'posts' => $forumFileLines[1] );
			}
			
			$forumData = array();
			
			foreach ( $forumFileLines as $line )
			{
				$tmpData = explode( '|', $line );
				$forumData[$tmpData[0]] = $tmpData;
			}
			
			return $forumData;
		}
		
		private function _parseTopicFile($topicId, $fileType)
		{
			if ( ! in_array($fileType, array('txt', 'view') ) )
			{
				return FALSE;
			}
			
			$topicFileLines = @file(trim($this->sourceDirectory . '/Messages/' . $topicId . '.' . $fileType), FILE_IGNORE_NEW_LINES);

			if ( !$topicFileLines )
			{
				return FALSE;
			}
			
			if ( $fileType == 'view' )
			{
				return intval($topicFileLines[0]);
			}

			$topicData = array();
			
			foreach ( $topicFileLines as $line )
			{
				$topicData[] = explode( '|', $line );
			}

			return $topicData;
		}
		
		private function _parseMemberFile($memberId, $fileType)
		{
			if ( ! in_array($fileType, array('dat', 'log', 'pm', 'prefs') ) )
			{
				return FALSE;
			}
			
			$memberFileLines = @file(trim($this->sourceDirectory . '/Members/' . $memberId . '.' . $fileType), FILE_IGNORE_NEW_LINES);

			if ( !$memberFileLines )
			{
				return FALSE;
			}
			
			$memberData = array();
			
			foreach ( $memberFileLines as $key => $line )
			{		
				if ( $fileType == 'dat' )
				{
					preg_match('#(\w+?) = \|(.*?)\|#', $line, $matches);
					$memberData[$matches[1]] = $matches[2];
				} else {
					$memberData[$key] = explode( '|', $line );
				}
			}
			
			return $memberData;
		}
		
		private function _parseStickyTopicsFile()
		{
			// Load the sticky topics db file to memory
			$stickyList = @file(trim($this->sourceDirectory . '/Boards/Stick.txt'), FILE_IGNORE_NEW_LINES);
			
			if ( !$stickyList )
			{
				return FALSE;
			}
			
			$stickyData = array();

			foreach ( $stickyList as $sticky )
			{
				$tmpData = explode( '|', $sticky );
				$stickyData[$tmpData[0]][] = $tmpData[1];
			}
			
			return $stickyData;
		}
		
		public function displayFinishScreen( $action )
		{
			$info = $this->lib->menuRow($action);
			
			// Save that it's been completed
			$get = unserialize($this->settings['conv_completed']);
			$us = $get[$this->lib->app['name']];
			$us = is_array($us) ? $us : array();
			if (empty($this->lib->errors))
			{
				$us = array_merge($us, array($action => true));
			}
			else
			{
				$us = array_merge($us, array($action => 'e'));
			}
			$get[$this->lib->app['name']] = $us;
			IPSLib::updateSettings(array('conv_completed' => serialize($get)));
			
			if (!empty($this->lib->errors))
			{
				$es = 'The following errors occurred: <ul>';
				foreach ($this->lib->errors as $e)
				{
					$es .= "<li>{$e}</li>";
				}
				$es .= '</ul>';
			}
			else
			{
				$es = 'No problems found.';
			}

			$this->registry->output->html .= $this->lib->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
			$this->sendOutput();
		}
	}