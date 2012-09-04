<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * MyBB
 * Last Update: $Date: 2009-11-25 16:43:59 +0100(mer, 25 nov 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 391 $
 */

$info = array( 'key'   => 'cs',
			   'name'  => 'Community Server',
			   'login' => true );

class admin_convert_board_cs extends ipsCommand
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
								'moderators' => array( 'forums', 'members'),
								'topics'		=> array('members'),
								'posts'			=> array('members', 'topics'),
								'polls'			=> array( 'topics', 'posts' ),
								'pms'			=> array('members'),
								'ranks'			=> array('members', 'posts'),
								'attachments'   => array('posts') );

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'Intelligent Community Server &rarr; IP.Board Converter' );

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
				return $this->lib->countRows('aspnet_Roles');
				break;

			case 'members':
				return  $this->lib->countRows('cs_Users');
				break;

			case 'forums':
				return $this->lib->countRows('cs_Sections') + $this->lib->countRows('cs_Groups');
				break;

			case 'moderators':
				return $this->lib->countRows('cs_Moderators');
				break;

			case 'topics':
				return  $this->lib->countRows('cs_Threads', "SectionID != '0'");
				break;

			case 'posts':
				return  $this->lib->countRows('cs_Posts', "SectionID > '0'");
				break;

			case 'pms':
				return  $this->lib->countRows('cs_Posts', "SectionID = '0'");
				break;

			case 'polls':
				return $this->lib->countRows('cs_Posts', "PostType = '2'");
				break;

			case 'ranks':
				return $this->lib->countRows('cs_Ranks');
				break;

			case 'attachments':
				return $this->lib->countRows('cs_PostAttachments');
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
					   'from'   => 'aspnet_Roles',
					   'order'  => 'RoleId ASC' );

		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'RoleId', 'nf' => 'RoleName'));

		//---------------------------
		// Loop
		//---------------------------
		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['RoleId'], $row['RoleName']);
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
					   'from'   => 'aspnet_Roles',
					   'order'  => 'RoleId ASC' );

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'RoleId', 'nf' => 'RoleName'));

		//---------------------------
		// Loop
		//---------------------------
		foreach( $loop as $row )
		{
			$save = array( 'g_title'   => $row['RoleName'],
						   'g_perm_id' => $row['RoleId'] );
			$this->lib->convertGroup($row['RoleId'], $save);
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
					   'icqIM'	  => 'ICQ Number',
					   'aolIM'	  => 'AIM ID',
					   'yahooIM'	  => 'Yahoo ID',
					   'msnIM'	  => 'MSN ID',
					   'webAddress'  	  => 'Website',
					   'occupation' => 'Occupation',
					   'interests' => 'Interests',
					   'webGallery' => 'Web Gallery',
					   'webLog' => 'Web Blog' );

		$this->lib->saveMoreInfo('members', array_keys($pcpf));

		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select'   => 'u.UserID, u.LastActivity, CAST(u.MembershipID AS CHAR(36)) as MembershipID',
					   'from' 	  => array('cs_users' => 'u'),
					   'add_join' => array( array( 'select' 	=> 'm.Email,m.CreateDate,m.LastLoginDate, m.Password, m.PasswordSalt',
					   							   'from'		=> array('aspnet_Membership' => 'm'),
					   							   'where'		=> 'u.MembershipID = m.UserId',
					   							   'type'		=> 'left'),
					   						array( 'select' 	=> 'CAST(ap.PropertyNames AS text) as PropertyNames, CAST(ap.PropertyValuesString AS text) as PropertyValuesString',
					   							   'from'		=> array('aspnet_Profile' => 'ap'),
					   							   'where'		=> 'u.MembershipID = ap.UserId',
					   							   'type'		=> 'left'),
					   						array( 'select' 	=> 'CAST(a.UserName AS varchar) as UserName',
					   							   'from'		=> array('aspnet_Users' => 'a'),
					   							   'where'		=> 'u.MembershipID = a.UserId',
					   							   'type'		=> 'left'),
					   						array( 'select'	=> 'p.TotalPosts, p.TimeZone',
					   							   'from'		=> array( 'cs_UserProfile' => 'p' ),
					   							   'where'		=> 'u.UserID = p.UserID',
					   							   'type'		=> 'left') ),
					   	'order'   => 'u.UserID ASC' );

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
			$propertyNamesArray = explode(':',$row['PropertyNames']);

			// Ditch extra empty cell
			array_pop($propertyNamesArray);

			for ( $i=0; $i<count($propertyNamesArray)-1; $i+=4 )
			{
				$row[ $propertyNamesArray[$i] ] = substr($row['PropertyValuesString'], $propertyNamesArray[$i+2], $propertyNamesArray[$i+3]);
			}
			unset($propertyNamesArray);

			ipsRegistry::DB('hb')->build( array( 'select' => 'RoleId', 'from' => 'aspnet_UsersInRoles', 'where' => "UserId='{$row['MembershipID']}'" ) );
			$groupRes = ipsRegistry::DB('hb')->execute();
			$groups = array();
			while( $group = ipsRegistry::DB('hb')->fetch($groupRes) )
			{
				$groups[] = $group['RoleId'];
			}

			$primaryGroup = count($groups) > 0 ? array_shift($groups) : null;

			//-----------------------------------------
			// Set info
			//-----------------------------------------

			// Basic info
			$info = array( 'id'				  => $row['UserID'],
						   'group'			  => $primaryGroup,
						   'secondary_groups' => implode(', ', $groups),
						   'joined'	   		  => $this->lib->myStrToTime($row['CreateDate']),
						   'username'  		  => $row['UserName'],
						   'email'	   		  => $row['Email'],
						   'password' 		  => $row['Password'] );

			// Member info
			 preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})/",$time, $birthday);

			$members = array( 'ip_address'			  => '127.0.0.1',
							  'posts'				  => $row['TotalPosts'],
							  'allow_admin_mails' 	  => 1,
							  'time_offset'			  => intval($row['TimeZone']),
							'bday_day'			=> $birthday && $birthday[1] != '0001' ? $birthday[3] : '',
							'bday_month'		=> $birthday && $birthday[1] != '0001' ? $birthday[2] : '',
							'bday_year'			=> $birthday && $birthday[1] != '0001' ? $birthday[1] : '',
							  'hide_email'			  => 1,
							  'email_pm'			  => 0,
							  'view_sigs'			  => 1,
							  'view_avs'			  => 1,
							  'msg_show_notification' => 1,
							  'last_visit'			  => $this->lib->myStrToTime($row['LastLoginDate']),
							  'last_activity'		  => $this->lib->myStrToTime($row['LastLoginDate']),
							  'dst_in_use'			  => 0,
							  'coppa_user'			  => 0,
							  'misc' => $row['PasswordSalt'] );

			// Profile
			$profile = array( 'signature' => $this->fixPostData($row['signature']) );

			//-----------------------------------------
			// Avatars and profile pictures
			//-----------------------------------------
			$avatar = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'Length, ContentType, Content', 'from' => 'cs_UserAvatar', 'where' => "UserId='{$row['UserID']}'" ) );
			if ( $avatar != '' )
			{
				$profile['avatar_type'] = 'upload';
				$profile['avatar_location'] = str_replace('/','.', strtolower($avatar['ContentType']));
				$profile['avatar_data'] = $avatar['Content'];
				$profile['avatar_filesize'] = $avatar['Length'];
			}

			//-----------------------------------------
			// Custom Profile fields
			//-----------------------------------------

			// Pseudo
			foreach ($pcpf as $id => $name)
			{
				if ($us[$id] != 'x')
				{
					$custom['field_'.$us[$id]] = $row['PropertyNames'][$id];
				}
			}

			//-----------------------------------------
			// And go!
			//-----------------------------------------
			$this->lib->convertMember($info, $members, $profile, array());
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
		$main = array( 'select' 	=> 'm.UserID, m.SectionID',
						'from'		=> array( 'cs_Moderators' => 'm' ),
						'add_join'	=> array( array( 'from' => array('cs_users' => 'u'),
													 'where' => 'm.UserID = u.UserID',
													 'type' => 'left' ),
											  array( 'select' => 'CAST(a.UserName AS varchar) as UserName',
													 'from'   => array('aspnet_Users' => 'a'),
													 'where'  => 'u.MembershipID = a.UserId',
													 'type'   => 'left') ),
						'order'		=> 'moderatorid ASC' );

		$loop = $this->lib->load('moderators', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
							   'forum_id'	  => $row['SectionID'],
							   'member_name'  => $row['UserName'],
							   'member_id'	  => $row['UserID'],

							   'edit_post'	  => 1,
							   'edit_topic'	  => 1,
							   'delete_post'  => 1,
							   'delete_topic' => 1,
							   'view_ip'	  => 1,
							   'open_topic'	  => 1,
							   'close_topic'  => 1,
							   'mass_move'	  => 1,
							   'mass_prune'	  => 1,
							   'move_topic'	  => 1,
							   'pin_topic'	  => 1,
							   'unpin_topic'  => 1,
							   'post_q'		  => 1,
							   'topic_q'	  => 1,
							   'allow_warn'	  => 1,
							   'edit_user'	  => 1,
							   'is_group'	  => 1,
							   'split_merge'  => 1 );


			$this->lib->convertModerator($row['SectionID'].'_'.$row['UserID'], $save);
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
		$main = array( 'select' => 'GroupID, CAST(Name AS varchar) as Name, SortOrder',
						'from'  => 'cs_Groups',
						'order' => 'GroupID ASC' );

		$loop = $this->lib->load('forums', $main, array('forum_tracker'), array(), TRUE );

		$this->lib->getMoreInfo('forums', $loop);

		//---------------------------
		// Loop
		//---------------------------
		foreach ( $loop as $row )
		{
			// Set info
			$save = array( 'parent_id'	   => -1,
						   'position'	   => $row['SortOrder'],
						   'name'		   => $row['Name'],
						   'inc_postcount' => 1,
						   'sub_can_post'  => 0,
						   'status'		   => 1 );
			// Save
			$this->lib->convertForum('C_'.$row['GroupID'], $save, array());
		}

		//---------------------------
		// Set up
		//---------------------------
		ipsRegistry::DB('hb')->build( array( 'select' => 'SectionID, IsActive, ParentID, GroupID, CAST(Name AS varchar) as Name, CAST(Description AS text) Description, TotalPosts, TotalThreads, SortOrder, ForumType, Url',
											 'from'  => 'cs_Sections',
											 'order' => 'SectionID ASC' ) );
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
			$save = array( 'parent_id'		=> $row['ParentID'] == 0 ? 'C_'.$row['GroupID'] : $row['ParentID'],
						   'position'		=> $row['SortOrder'],
						   'name'			=> $row['Name'],
						   'description' => $row['Description'],
						   'sub_can_post'	=> 1,
							'redirect_on'		=> ($row['ForumType'] == 2),
							'redirect_url'		=> $row['Url'],
							'redirect_hits'		=> ($row['ForumType'] == 2) ? $row['TotalPosts'] : 0,
						   'status'		=> $row['IsActive'] == 0 ? 0 : 1,
						   'posts'			=> $row['TotalPosts'],
						   'topics'		=> $row['TotalThreads'],
						   'use_ibc'      => 1 );

			$this->lib->convertForum($row['SectionID'], $save, $perms);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'cs_SectionSubscriptions', 'where' => "SectionID={$row['SectionID']}"));
			$trackerRes = ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch($trackerRes))
			{
				$savetracker = array( 'member_id'	=> $tracker['UserID'],
									  'forum_id'	=> $tracker['SectionID'] );
				$this->lib->convertForumSubscription($tracker['SubscriptionID'], $savetracker);
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
		$main = array( 'select'		=> 'ThreadID, SectionID, UserID, PostAuthor, PostDate, ThreadDate, TotalViews, TotalReplies, MostRecentPostAuthorID, MostRecentPostAuthor, MostRecentPostID, IsLocked, IsSticky, IsApproved',
					   'from'		=> 'cs_Threads',
					   'where' => "SectionID > '0'",
					   'order'		=> 'ThreadID ASC' );

		$loop = $this->lib->load('topics', $main, array('tracker'));

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$post = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'Subject,PostType', 'from' => 'cs_Posts', 'where' => "ThreadID='".$row['ThreadID']."' AND PostLevel='1'"));

			$save = array( 'forum_id'			=> $row['SectionID'],
						   'title'				=> $post['Subject'],
						   'poll_state'			=> ( ($post['PostType'] == '2') && $row['IsApproved'] ) ? 'open' : '0',
						   'starter_id'			=> $row['UserID'],
						   'starter_name'		=> $row['PostAuthor'],
						   'start_date'			=> $this->lib->myStrToTime($row['PostDate']),
						   'last_post'			=> $this->lib->myStrToTime($row['ThreadDate']),
						   'views'				=> $row['TotalViews'],
						   'posts'				=> $row['TotalReplies'],
						   'state'		   	 	=> $row['IsLocked'] == 1 ? 'closed' : 'open',
						   'pinned'				=> $row['IsSticky'],
						   'approved'			=> $row['IsApproved'] );
			$this->lib->convertTopic($row['ThreadID'], $save);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'cs_TrackedThreads', 'where' => "ThreadID={$row['ThreadID']}"));
			$trackerRes = ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch($trackerRes))
			{
				$savetracker = array( 'member_id'	=> $tracker['UserID'],
									  'topic_id'	=> $tracker['ThreadID'] );
				$this->lib->convertTopicSubscription($tracker['ThreadID'].'-'.$tracker['UserID'], $savetracker);
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
		$main = array( 'select'	 => 'PostID, ThreadID, PostAuthor, UserID, CAST(Subject AS varchar) as Subject, PostDate, IsApproved, CAST(Body AS TEXT) as Body, IPAddress, SectionID',
					  'from'	 => 'cs_Posts',
					 // 'where' => "SectionID > '0'",
					  'order'	 => 'PostID ASC' );
		$this->lib->useKey( 'PostID' );

		$loop = $this->lib->load('posts', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->setLastKeyValue($row['PostID']);
			$save = array( 'topic_id'    => intval($row['ThreadID']),
						   'author_id'	 => intval($row['UserID']),
						   'author_name' => $row['PostAuthor'],
						   'post_date'	 => $this->lib->myStrToTime($row['PostDate']),
						   'post'		 => $this->fixPostData($row['Body']),
						   'ip_address'	 => $row['IPAddress'],
						   'use_sig'	 => 1,
						   'use_emo'	 => 1,
						   'queued'		 => ( $row['IsApproved'] == '0' ) ? 1 : 0,
						   'rep_points'	=> $row['RatingSum'] );

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
		$main = array( 'select' => 'ThreadID, UserID, PostAuthor, PostDate, ThreadDate, TotalViews, TotalReplies, MostRecentPostAuthorID, MostRecentPostAuthor, MostRecentPostID, IsLocked, IsSticky, IsApproved, ThreadDate',
			   'from'		=> 'cs_Threads',
			   'where' => "SectionID='0'",
			   'order'		=> 'ThreadID ASC' );
		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			ipsRegistry::DB('hb')->build( array( 'select' => 'PostID, ThreadID, PostAuthor, UserID, CAST(Subject AS varchar) as Subject, PostDate, IsApproved, CAST(Body AS TEXT) as Body, IPAddress, SectionID',
											     'from'	  => 'cs_Posts',
											     'where'  => "ThreadID='{$row['ThreadID']}'",
											     'order'  => 'PostID ASC' ) );
			$pmRes = ipsRegistry::DB('hb')->execute();
			$posts = array();
			$haveFirst;
			$title;
			while ( $post = ipsRegistry::DB('hb')->fetch($pmRes) )
			{
				//-----------------------------------------
				// Post Data
				//-----------------------------------------
				$posts[] = array( 'msg_id'			   => $post['PostID'],
							   'msg_topic_id'      => $post['ThreadID'],
							   'msg_date'          => $this->lib->myStrToTime($post['PostDate']),
							   'msg_post'          => $post['Body'],
							   'msg_post_key'      => md5(microtime()),
							   'msg_author_id'     => $post['UserID'],
							   'msg_is_first_post' => $haveFirst == NULL ? 1 : 0 );

				if ( $haveFirst == NULL )
				{
					$haveFirst = TRUE;
					$title = $post['Subject'];
				}
			}

			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			ipsRegistry::DB('hb')->build( array( 'select' => 'UserID, ThreadID',
											     'from'	  => 'cs_PrivateMessages',
											     'where'  => "ThreadID='{$row['ThreadID']}'" ) );
			$mapRes = ipsRegistry::DB('hb')->execute();
			$maps = array();
			$toId;
			while ( $map = ipsRegistry::DB('hb')->fetch($mapRes) )
			{
				$maps[] = array( 'map_topic_id'    => $map['ThreadID'],
								'map_user_id' => $map['UserID'],
								 'map_folder_id'   => 'myconvo',
								 'map_last_topic_reply' => $this->lib->myStrToTime($row['ThreadDate']),
								 'map_read_time'   => 0,
								 'map_user_active' => 1,
								 'map_user_banned' => 0,
								 'map_has_unread'  => 0,
								 'map_is_system'   => 0,
								 'map_is_starter' => $row['UserID'] == $map['UserID'] ? 1 : 0 );
				if ( $toId == NULL && $row['UserID'] != $map['UserID'] )
				{
					$toId = $map['UserID'];
				}
			}

			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$topic = array( 'mt_id'			     => $row['ThreadID'],
							'mt_date'		     => $this->lib->myStrToTime($row['PostDate']),
							'mt_title'		     => $title,
							'mt_starter_id'	     => intval($row['UserID']),
							'mt_start_time'      => $this->lib->myStrToTime($row['PostDate']),
							'mt_last_post_time'  => $this->lib->myStrToTime($row['ThreadDate']),
							'mt_invited_members' => serialize( array( intval($row['toId']) => intval($row['toId']) ) ),
							'mt_to_count'		 => count($maps) - 1,
							'mt_to_member_id'	 => $toId,
							'mt_replies'		 => $row['TotalReplies'],
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
						'from' 	 => 'cs_Ranks',
						'order'	 => 'RankID ASC' );

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
			$save = array( 'posts'	=> $row['PostingCountMin'],
						   'title'	=> $row['RankName'] );
			$this->lib->convertRank($row['RankID'], $save, $us['rank_opt']);
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
	{print 'Need data to finish this section';exit;
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => 'PostID, ThreadID, PostAuthor, UserID, CAST(Subject AS varchar) as Subject, PostDate, IsApproved, CAST(Body AS TEXT) as Body, IPAddress, SectionID',
					   'from' => 'cs_Posts',
					   'where' => "PostType = '2'",
					   'order' => 't.pollid ASC' );

		$loop = $this->lib->load('polls', $main, array('voters'));

		//---------------------------
		// Loop
		//---------------------------
		require_once( IPS_KERNEL_PATH . 'classXML.php' );

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$xml = new classXML( IPSSetUp::charSet );
			$xml->loadXML( $row['Body'] );

			foreach( $xml->fetchElements( 'VoteOptions' ) as $option )
			{
				$data = $this->_xml->fetchElementsFromRecord( $_el );

				if ( $data['appears'] AND intval( $data['frequency'] ) > $this->_minF )
				{
					return TRUE;
				}
			}

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
		$main = array( 'select'  => 'a.PostID, a.SectionID, a.UserID, a.Created, CAST(a.FileName AS varchar) as FileName, a.Content, a.ContentType, a.ContentSize, a.Height, a.Width',
					   'from'     => array( 'cs_Postattachments' => 'a' ),
					   'add_join' => array( array( 'select'	=> 'p.ThreadID, p.TotalViews',
					   							   'from'   => array( 'cs_Posts' => 'p' ),
					   							   'where'  => 'a.PostID = p.PostID',
					   							   'type'   => 'left' ) ),
					   'order'    => 'a.PostID' );

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
			// Sort out data
			$save = array( 'attach_rel_id'	   => $row['PostID'],
						   'attach_file'	   => $row['FileName'],
						   'attach_hits'	   => $row['TotalViews'],
						   'attach_date'	   => $this->lib->myStrToTime($row['PostDate']),
						   'attach_member_id'  => $row['UserID'],
						   'attach_rel_module' => 'post' );

			if ( strlen($row['Content']) != $row['ContentSize'] )
			{
				$save = array_merge( $save, array( 'attach_location' => $row['FileName'] ) );
				$done = $this->lib->convertAttachment($row['PostID'], $save, $path);
			}
			else
			{
				$save = array_merge( $save, array( 'data'   		 => $row['Content'],
												   'attach_filesize' => $row['ContentSize'] ) );
				$done = $this->lib->convertAttachment($row['PostID'], $save, '', TRUE);
			}
		}
		$this->lib->next();
	}
}

