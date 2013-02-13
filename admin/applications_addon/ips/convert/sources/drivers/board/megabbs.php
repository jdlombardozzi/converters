<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * MyBB
 * Last Update: $Date: 2011-07-29 18:42:31 +0100 (Fri, 29 Jul 2011) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 569 $
 */

$info = array( 'key'   => 'megabbs',
			   'name'  => 'MegaBBS',
			   'login' => true );

class admin_convert_board_megabbs extends ipsCommand
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
		$this->actions = array( 'forum_perms'	=> array(),
								'groups' 		=> array('forum_perms'),
								'members'		=> array('forum_perms', 'groups'),
								'forums'		=> array('members'),
								'topics'		=> array('members'),
								'posts'			=> array('members', 'topics'),
								//'pms'			=> array('members'),
								/*'ranks'			=> array(),*/
								/*'polls'         => array('topics'),*/
								'attachments'   => array('posts') );

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'MegaBBs &rarr; IP.Board Converter' );

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
				return $this->lib->countRows('groups');
				break;

			case 'members':
				return  $this->lib->countRows('members');
				break;

			case 'forums':
				return $this->lib->countRows('forums') + $this->lib->countRows('categories');
				break;

			case 'topics':
				return  $this->lib->countRows('threads');
				break;

			case 'posts':
				return  $this->lib->countRows('messages');
				break;

			case 'pms':
				return  $this->lib->countRows('private');
				break;

			case 'polls':
				return $this->lib->countRows('polls');
				break;

			case 'attachments':
				return $this->lib->countRows('attachments');
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
			case 'forum_perms':
			case 'groups':
			case 'members':
			case 'emoticons':
			case 'ranks':
			case 'attachments':
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
		// Sort out newlines
		$post = nl2br($post);

		// And odd align tags
		$post = preg_replace("#\[align=(.+)\](.+)\[/align\]#i", "[$1]$2[/$1]", $post);

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
					   'from'   => 'groups',
					   'order'  => 'groupid ASC' );

		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'groupid', 'nf' => 'groupname'));

		//---------------------------
		// Loop
		//---------------------------
		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['groupid'], $row['groupname']);
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
					   'from'   => 'groups',
					   'order'  => 'groupid ASC' );

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'groupid', 'nf' => 'groupname'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{

			$save = array(
				'g_title'			=> $row['groupname'],
				'g_perm_id'			=> $row['groupid'] );
			$this->lib->convertGroup($row['groupid'], $save);
		}
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
		$pcpf = array( //'gender'		=> 'Gender',
					   'icqnumber'	  => 'ICQ Number',
					   'aim'	  => 'AIM ID',
					   'yahoo'	  => 'Yahoo ID',
					   'msn'	  => 'MSN ID',
					   'website'  	  => 'Website',
					   'location' => 'Location',
					   'interests' => 'Interests' );

		$this->lib->saveMoreInfo( 'members', array_merge( array_keys($pcpf), array( 'pp_path', 'pp_type' ) ) );

		//---------------------------
		// Set up
		//---------------------------

		$main = array( 'select'	 => 'm.*',
						'from'	 => array( 'members' => 'm' ),
					  'add_join' => array( array( 'select' => 'i.PhotoImage, i.AvatarImage, i.PhotoFilename, i.AvatarFilename, i.photofileguid, i.avatarfileguid, i.photoinfilesystem',
													   'from'	=> array( 'memberphotos' => 'i' ),
													   'where'	=> 'm.memberid = i.memberid',
													   'type'	=> 'left' ) ),
					   'order'	  => 'm.memberid ASC' );

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		// We need to know the avatars path
		$ask['pp_path'] = array('type' => 'text', 'label' => 'Path to avatars uploads folder (no trailing slash, default /path_to_board/profile/uploads): ');
		$ask['pp_type'] = array ( 'type' => 'dropdown', 'label' => 'Which Member Photo would you like to convert?', 'options' => array ( 'avatar' => 'Avatars', 'profile' => 'Profile Photo' ) );

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
			ipsRegistry::DB('hb')->build( array( 'select' => 'groupid', 'from' => 'groupmembers', 'where' => "memberid='{$row['memberid']}'" ) );
			$group_loop = ipsRegistry::DB('hb')->execute();
			$groups = array();
			while( $group = ipsRegistry::DB('hb')->fetch($group_loop) )
			{
				$groups[] = $group['groupid'];
			}

			$primaryGroup = array_shift($groups);

			//-----------------------------------------
			// Set info
			//-----------------------------------------

			// Basic info
			$info = array( 'id'				  => $row['memberid'],
						   'group'			  => $primaryGroup,
						   'secondary_groups' => implode(', ', $groups),
						   'joined'	   		  => strtotime($row['dateregistered']),
						   'username'  		  => $row['username'],
						   'email'	   		  => $row['emailaddress'],
						   'pass_hash' 		  => strtolower($row['password']) );

			if ( strlen($row['password']) > 32 )
			{
				/* ' Encrypt the password
				   dim vAttemptedUser
				   vAttemptedUser = BBS.GetUserInfoByName(sPostUsername)
				   sPostPassword = Encrypt.HashEncode(sPostPassword & vAttemptedUser(UI_Salt))*/
				unset($info['pass_hash']);
				$info['password'] = $row['password'];
			}
			// Member info
			//$birthday = ($row['bday']) ? explode('-', $row['bday']) : null;

			$members = array( 'ip_address'			  => '127.0.0.1',
							  'posts'				  => $row['totalposts'],
							  'allow_admin_mails' 	  => $row['notificationpreference'] == 'none' ? 0 : 1 ,
							  'time_offset'			  => $row['timeoffset'],
							  'hide_email'			  => !$row['showemail'],
							  //'email_pm'			  => $row['sendprivatenotifications'],
							  'view_sigs'			  => 1,
							  'msg_show_notification' => 1,
							  'last_visit'			  => strtotime($row['lastlogon']),
							  'last_activity'		  => strtotime($row['lastlogon']),
							  'dst_in_use'			  => 0,
							  'coppa_user'			  => 0,
							  'members_disable_pm'	  => 1,
							  'misc' => $row['salt'] );

			// Profile
			$profile = array( 'signature' => $this->fixPostData($row['signature']) );

			//-----------------------------------------
			// Avatars and profile pictures
			//-----------------------------------------
			$path = $us['pp_path'];
			if ( $us['pp_type'] == 'avatar' )
			{
				if ( isset($row['avatarurl']) ) $row['AvatarURL'] = $row['avatarurl'];
				if ( isset($row['avatarimage']) ) $row['AvatarImage'] = $row['avatarimage'];

				if ( $row['AvatarURL'] != '' && $row['AvatarURL'] != NULL )
				{
					// URL
					if (preg_match('/http/', $row['AvatarURL']))
					{
						$profile['photo_type'] = 'url';
						$profile['photo_location'] = $row['AvatarURL'];
					}
				}
				elseif ( $row['AvatarFilename'] )
				{
					$profile['photo_type'] = 'custom';
					$profile['photo_location'] = $row['avatarfileguid'];

					if (!$row['avatarinfilesystem'])
					{
						$profile['photo_data'] = $customavatar['AvatarImage'];
					}
				}
			}
			else
			{
				if ( isset($row['photofilename']) ) $row['PhotoFilename'] = $row['photofilename'];
				if ( isset($row['photoimage']) ) $row['PhotoImage'] = $row['photoimage'];

				if ( $row['PhotoFilename'] )
				{
					$profile['pp_main_photo'] = $row['photofileguid'];

					if (!$row['photoinfilesystem'])
					{
						$profile['photo_data'] = $customprofilepic['PhotoImage'];
					}
				}
			}

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
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => '*',
						'from'  => 'categories',
						'order' => 'categoryid ASC' );

		$loop = $this->lib->load('forums', $main, array('forum_tracker'), array(), TRUE );

		$this->lib->getMoreInfo('forums', $loop);

		//---------------------------
		// Loop
		//---------------------------
		foreach ( $loop as $row )
		{
			// Set info
			$save = array( 'parent_id'	   => -1,
						   'position'	   => $row['sortorder'],
						   'name'		   => $row['name'],
						   'inc_postcount' => 1,
						   'sub_can_post'  => 0,
						   'status'		   => $row['locked'] == 1 ? 0 : 1 );
			// Save
			$this->lib->convertForum('C_'.$row['categoryid'], $save, array());
		}

		//---------------------------
		// Set up
		//---------------------------
		ipsRegistry::DB('hb')->build( array( 'select' => '*',
											 'from'  => 'forums',
											 'order' => 'forumid ASC' ) );
		$forumRes = ipsRegistry::DB('hb')->execute();

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($forumRes) )
		{
			// Permissions will need to be reconfigured
			$perms = array();

			//-----------------------------------------
			// And go
			//-----------------------------------------
			$save = array( 'parent_id'		=> 'C_'.$row['categoryid'],
						   'position'		=> $row['sortorder'],
						   'name'			=> $row['forumname'],
						   'description' => $row['forumdescription'],
						   'sub_can_post'	=> 1,
						   'redirect_on'	=> 0,
						   'redirect_hits' => 0,
						   'status'		=> 1,
						   'posts'			=> $row['postcount'],
						   'topics'		=> $row['threadcount'],
						   'use_ibc'      => 1 );

			$this->lib->convertForum($row['forumid'], $save, $perms);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'massnotifications', 'where' => "forumid={$row['forumid']}"));
			$trackerRes = ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch($trackerRes))
			{
				$savetracker = array( 'member_id'	=> $tracker['memberid'],
									  'forum_id'	=> $tracker['forumid'] );
				$this->lib->convertForumSubscription($tracker['forumid'].'-'.$tracker['memberid'], $savetracker);
			}
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
		$main = array( 'select'		=> '*',
					   'from'		=> 'threads',
					   'order'		=> 'threadid ASC' );

		$loop = $this->lib->load('topics', $main, array('tracker'));

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'forum_id'			=> $row['forumid'],
						   'title'				=> $row['threadsubject'],
						   'poll_state'			=> 0,
						   'starter_id'			=> intval($row['memberid']),
						   'starter_name'		=> $row['guestname'],
						   'start_date'			=> strtotime($row['datecreated']),
						   'last_post'			=> strtotime($row['lastactivity']),
						   'views'				=> $row['timesviewed'],
						   'posts'				=> $row['TotalPosts'],
						   'state'		   	 	=> $row['closed'] == 1 ? 'closed' : 'open',
						   'pinned'				=> $row['sticky'],
						   'approved'			=> $row['approved'] );
			$this->lib->convertTopic($row['threadid'], $save);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'notifications', 'where' => "threadid={$row['threadid']}"));
			$trackerRes = ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch($trackerRes))
			{
				$savetracker = array( 'member_id'	=> $tracker['memberid'],
									  'topic_id'	=> $tracker['threadid'] );
				$this->lib->convertTopicSubscription($tracker['threadid'].'-'.$tracker['memberid'], $savetracker);
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
	private function convert_posts()
	{
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select'	 => '*',
					  'from'	 => 'messages',
					  'order'	 => 'messageid ASC' );

		$loop = $this->lib->load('posts', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'topic_id'    => intval($row['threadid']),
						   'author_id'	 => intval($row['memberid']),
						   'author_name' => $row['guestname'],
						   'post_date'	 => strtotime($row['dateposted']),
						   'post'		 => $this->fixPostData($row['body']),
						   'ip_address'	 => $row['hostname'],
						   'use_sig'	 => $row['signature'],
						   'use_emo'	 => $row['emoticons'],
						   'queued'		 => $row['approved'] == 0 ? 1 : 0 );

			$this->lib->convertPost($row['messageid'], $save);
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

		$main = array( 'select'   => 'p.*',
					   'from'     => array( 'private' => 'p' ),
					   'add_join' => array( array( 'select' => 'm.memberid as toId',
					   							   'from'   => array( 'members' => 'm' ),
					   							   'where'  => 'p.toname = m.username',
					   							   'type'	=> 'inner' ),
					   						array( 'select' => 'r.memberid as fromId',
					   							   'from'	=> array( 'members' => 'r' ),
					   							   'where'	=> 'p.fromname = r.username',
					   							   'type'	=> 'inner' ) ),
					   'order'    => 'p.prvmessageid ASC' );

		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Post Data
			//-----------------------------------------
			$post = array( 'msg_id'			   => $row['prvmessageid'],
						   'msg_topic_id'      => $row['prvmessageid'],
						   'msg_date'          => strtotime($row['datesent']),
						   'msg_post'          => $this->fixPostData($row['body']),
						   'msg_post_key'      => md5(microtime()),
						   'msg_author_id'     => intval($row['fromId']),
						   'msg_is_first_post' => 1 );

			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$map_master = array( 'map_topic_id'    => $row['prvmessageid'],
								 'map_folder_id'   => 'myconvo',
								 'map_read_time'   => 0,
								 'map_last_topic_reply' => strtotime($row['datesent']),
								 'map_user_active' => 1,
								 'map_user_banned' => 0,
								 'map_has_unread'  => $row['messageread'] == 0 ? 1 : 0,
								 'map_is_system'   => 0 );

			$maps = array();
			$maps[] = array_merge( $map_master, array( 'map_user_id' => intval($row['fromId']), 'map_is_starter' => 1 ) );
			$maps[] = array_merge( $map_master, array( 'map_user_id' => intval($row['toId']), 'map_is_starter' => 0 ) );

			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$topic = array( 'mt_id'			     => $row['prvmessageid'],
							'mt_date'		     => strtotime($row['datesent']),
							'mt_title'		     => $row['subject'],
							'mt_starter_id'	     => intval($row['fromId']),
							'mt_start_time'      => strtotime($row['datesent']),
							'mt_last_post_time'  => strtotime($row['datesent']),
							'mt_invited_members' => serialize( array( intval($row['toId']) => intval($row['toId']) ) ),
							'mt_to_count'		 => 1,
							'mt_to_member_id'	 => intval($row['toId']),
							'mt_replies'		 => 0,
							'mt_is_draft'		 => 0,
							'mt_is_deleted'		 => 0,
							'mt_is_system'		 => 0 );

			//-----------------------------------------
			// Go
			//-----------------------------------------
			$this->lib->convertPM($topic, array($post), $maps);
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
						'from' 	 => 'customranks',
						'order'	 => 'rankid ASC' );

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
			$save = array( 'posts'	=> $row['minposts'],
						   'title'	=> $row['rankname'] );
			$this->lib->convertRank($row['rankid'], $save, $us['rank_opt']);
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
		$main = array( 'select'	 => 't.*',
					  'from'	 => array( 'threads' => 't' ),
					  'add_join' => array( array( 'select' => 'p.*',
													   'from'	=> array( 'polls' => 'p' ),
													   'where'	=> 't.pollid = p.pollid',
													   'type'	=> 'inner' ) ),
					  'order'	 => 't.pollid ASC' );

		$loop = $this->lib->load('polls', $main, array('voters'));

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Convert votes
			//-----------------------------------------
			$votes = array();

			ipsRegistry::DB('hb')->build(array( 'select' => '*', 'from' => 'pollvoted', 'where' => "pollid='{$row['pollid']}' AND registered='1'" ));
			$voterRes = ipsRegistry::DB('hb')->execute();
			while ( $voter = ipsRegistry::DB('hb')->fetch($voterRes) )
			{
				$vsave = array( 'vote_date'		 => time(),
								'tid'			 => $row['threadid'],
								'member_id'		 => $voter['memberid'],
								'forum_id'		 => $row['forumid'],
								'member_choices'=> serialize(array(1 => $row['optionid'])) );

				$this->lib->convertPollVoter($voter['voteid'], $vsave);
			}

			//-----------------------------------------
			// Options are stored in one place...
			//-----------------------------------------
			$choices = array();
			$votes = array();
			$totalVotes = 0;

			ipsRegistry::DB('hb')->build(array( 'select' => '*', 'from' => 'polloptions', 'where' => "pollid='{$row['pollid']}'" ));
			$choiceRes = ipsRegistry::DB('hb')->execute();
			while ( $choice = ipsRegistry::DB('hb')->fetch($choiceRes) )
			{
				$choices[ $choice['optionid'] ] = $choice['description'];
				$votes[ $choice['optionid'] ]	= $choice['votes'];
				$totalVotes += $choice['votes'];
			}

			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------
			$poll_array = array( // MegaBBS only allows one question per poll
								 1 => array( 'question'	=> $row['threadsubject'],
								 			 'choice'	=> $choices,
											 'votes'	=> $votes ) );
			$save = array( 'tid'		=> $row['threadid'],
						   'start_date'	=> strtotime($row['datecreated']),
						   'choices'   	=> addslashes(serialize($poll_array)),
						   'starter_id'	=> $row['memberid'] == '-1' ? 0 : $row['memberid'],
						   'votes'     	=> $totalVotes,
						   'forum_id'  	=> $row['forumid'],
						   'poll_question'	=> $row['threadsubject'] );

			$this->lib->convertPoll($row['pollid'], $save);
		}
		$this->lib->next();
	}

	private function convert_attachments()
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$this->lib->saveMoreInfo('attachments', array('attach_path'));

		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select'	=> '*',
					 'from'		=> array( 'attachments' => 'a' ),
					 'add_join'	=> array( array( 'select' => 'p.dateposted, p.threadid, p.memberid',
												  'from'   => array( 'messages' => 'p'),
												  'where'  => 'a.messageid = p.messageid',
												  'type'   => 'inner' ),
										  array( 'select' => 't.forumid',
												  'from'   => array( 'threads' => 't' ),
												  'where'  => 'p.threadid = t.threadid',
												  'type'   => 'inner' ) ),
					 'order'	=> 'a.attachmentid ASC' );

		$loop = $this->lib->load('attachments', $main);

		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------
		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_board/attachments):')), 'path');

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];

		//-----------------------------------------
		// Check all is well
		//-----------------------------------------
		if (!is_writable($this->settings['upload_dir']))
		{
			$this->lib->error('Your IP.Board upload path is not writeable. '.$this->settings['upload_dir']);
		}
		if (!is_readable($us['attach_path']))
		{
			$this->lib->error('Your remote upload path is not readable.');
		}

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			// What's the extension?
			$ext = explode('.', $row['filename']);
			$extension = array_pop( $ext );

			// Sort out data
			$save = array( 'attach_rel_id'	   => $row['messageid'],
						   'attach_ext'		   => $extension,
						   'attach_file'	   => $row['filename'],
						   'attach_location'   => $row['attachment'],
						   'attach_is_image'   => (int) in_array( $ext, array('gif', 'jpg', 'jpeg', 'png') ),
						   'attach_rel_module' => 'post',
						   'attach_filesize' => $row['filesize'],
						   'attach_member_id'  => $row['memberid'],
						   'attach_hits'	   => $row['downloadcount'],
						   'attach_date'	   => strtotime($row['datecreated']) );

			//-----------------------------------------
			// Database
			//-----------------------------------------

			if (!$row['infilesystem'])
			{
				$save['data'] = $row['file'];

				$done = $this->lib->convertAttachment($row['attachmentid'], $save, '', TRUE);
			}

			//-----------------------------------------
			// File storage
			//-----------------------------------------

			else
			{
				$done = $this->lib->convertAttachment($row['attachmentid'], $save, $path);
			}
		}
		$this->lib->next();
	}
}

