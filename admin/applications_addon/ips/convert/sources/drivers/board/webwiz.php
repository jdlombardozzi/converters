<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * WebWiz
 * Last Update: $Date: 2011-07-12 21:15:48 +0100 (Tue, 12 Jul 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 550 $
 */

$info = array( 'key'   => 'webwiz',
			   'name'  => 'WebWiz 8.X',
			   'login' => true );
		
class admin_convert_board_webwiz extends ipsCommand
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
								'members'		=> array(),
								'profile_friends' => array('members'),
								'forums'		=> array('members'),
								'topics'		=> array('members'),
								'posts'			=> array('members', 'topics'),
								'polls'			=> array('topics', 'members', 'forums'),
								//'pms'			=> array('members'),
								'attachments'	=> array('posts'),
								'ranks'			=> array() );
				
		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'WebWiz &rarr; IP.Board Converter' );

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
		if (array_key_exists($this->request['do'], $this->actions) || $this->request['do'] == 'boards')
		{
			call_user_func(array($this, 'convert_'.$this->request['do']));
		}
		else
		{
			$this->lib->menu( array( 'forums' => array( 'single' => 'Category',
														'multi'  => array( 'Category', 'Forum' ) ) ) );
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
				return  $this->lib->countRows('Group');
				break;
				
			case 'members':
				return  $this->lib->countRows('Author');
				break;
				
			case 'profile_friends':
				return $this->lib->countRows('BuddyList');
				break;
				
			case 'forums':
				return $this->lib->countRows('Category') + $this->lib->countRows('Forum');
				break;
				
			case 'topics':
				return  $this->lib->countRows('Topic');
				break;
				
			case 'posts':
				return  $this->lib->countRows('Thread');
				break;
				
			case 'pms':
				return  $this->lib->countRows('PMMessage');
				break;
				
			case 'polls':
				return  $this->lib->countRows('Poll');
				break;
				
			case 'attachments':
				return  $this->lib->countRows('Thread', "File_uploads != ''");
				break;
				
			case 'ranks':
				return  $this->lib->countRows('Group', "Special_rank = 0");
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
					   'from'   => 'Group',
					   'order'  => 'Group_ID ASC' );
					
		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'Group_ID', 'nf' => 'Name'));

		//---------------------------
		// Loop
		//---------------------------
		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['Group_ID'], $row['Name']);			
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
					   'from'   => 'Group',
					   'order'  => 'Group_ID ASC' );
					
		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'Group_ID', 'nf' => 'Name'));

		//---------------------------
		// Loop
		//---------------------------
		foreach( $loop as $row )
		{
			$save = array( 'g_title'   => $row['Name'],
						   'g_perm_id' => $row['Group_ID'] );
			$this->lib->convertGroup($row['Group_ID'], $save);			
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
	{//Passwords sha1(clearpass . salt)
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$pcpf = array( 'ICQ'	   => 'ICQ Number',
					   'AIM'	   => 'AIM ID',
					   'Location'  => 'Location',
					   'Yahoo'	   => 'Yahoo ID',
					   'MSN'	   => 'MSN ID',
					   'Homepage'  => 'Website',
					   'Interests' => 'Interests',
					   'Skype'	   => 'Skype' );
		
		$this->lib->saveMoreInfo('members', array_keys($pcpf));

		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => '*',
					   'from'   => 'Author',
					   'order'  => 'Author_ID ASC' );

		$loop = $this->lib->load('members', $main);
		
		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();
		
		// We need to know the avatars path
		$ask['dirpath'] = array('type' => 'text', 'label' => 'The path to the folder/directory containing your WebWiz installation.');

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
			$row['Join_date'] = is_object($row['Join_date']) ? strtotime(DATE_FORMAT($row['Join_date'], DATE_ATOM)) : intval(strtotime((string)$this->fixFrenchDate($row['Join_date'])));
			
			// Basic info				
			$info = array( 'id'		   => $row['Author_ID'],
						   'group'	   => $row['Group_ID'],
						   'joined'	   => $row['Join_date'],
						   'username'  => $row['Username'],
						   'email'	   => $row['Author_email'],
						   'password' => $row['Password'] );

			// Member info	
			$members = array( 'ip_address'			  => '127.0.0.1',
							  'posts'				  => $row['No_of_posts'],
							  'allow_admin_mails' 	  => 0,
							  'time_offset'			  => 0,
							  'hide_email'			  => !$row['Show_email'],
							  'email_pm'			  => $row['PM_notify'],
							  'last_post'			  => 0,
							  'view_sigs'			  => 1,
							  'view_avs'			  => 1,
							  'msg_show_notification' => $row['PM_notify'],
							  'last_visit'			  => is_object($row['Last_visit']) ? strtotime(DATE_FORMAT($row['Last_visit'], DATE_ATOM)) : intval(strtotime((string)$this->fixFrenchDate($row['Last_visit']))),
							  'last_activity'		  => $row['Join_date'],
							  'dst_in_use'			  => 0,
							  'coppa_user'			  => 0,
							  'members_disable_pm'	  => 1,
							  'misc'				  => $row['Salt'] );

			// Profile
			$profile = array( 'signature' => $this->fixPostData($row['Signature']) );
			
			//-----------------------------------------
			// Avatars and profile pictures
			//-----------------------------------------
			$path;
			if ( $row['Avatar'] != '' && $row['Avatar'] != NULL )
			{
				// URL
				if (preg_match('/http/', $row['Avatar']))
				{
					$profile['photo_type'] = 'url';
					$profile['photo_location'] = $row['Avatar'];
				}
				// Gallery
				else
				{
					$profile['photo_type'] = 'custom';
					$profile['pp_main_photo'] = $row['Avatar'];
					$path = $us['dirpath'];
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
	 * Convert friends
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_profile_friends()
	{
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => '*',
					   'from'   => 'BuddyList',
					   'order'  => 'Address_ID ASC' );
		
		$loop = $this->lib->load('profile_friends', $main);
		
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'friends_member_id'	=> $row['Author_ID'],
						   'friends_friend_id'	=> $row['Buddy_ID'],
						   'friends_approved'	=> '1' );
			$this->lib->convertFriend($row['id_member'].'-'.$friend, $save);
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
		$main = array( 'select' => '*',
					   'from'   => 'Category',
					   'order'  => 'Cat_ID ASC' );

		$loop = $this->lib->load('forums', $main, array(), array('boards', 'Forum'));

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{										
			$this->lib->convertForum( 'c'.$row['Cat_ID'], array( 'name' 	 => $row['Cat_name'],
																 'position'  => $row['Cat_order'],
																 'parent_id' => -1 ), array());
		}

		$this->lib->next();
	}
	
	/**
	 * Convert Forums
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_boards()
	{
		//---------------------------
		// Set up
		//---------------------------
		$mainBuild = array(	'select' => '*',
							'from'	 => 'Forum',
							'order'	 => 'Forum_ID ASC' );
							
		$this->start = intval($this->request['st']);
		$this->end = $this->start + intval($this->request['cycle']);
		
		$mainBuild['limit'] = array($this->start, $this->end);
					
		$this->errors = unserialize($this->settings['conv_error']);
		
		ipsRegistry::DB('hb')->build($mainBuild);
		$queryRes = ipsRegistry::DB('hb')->execute();
		
		if ( !ipsRegistry::DB('hb')->getTotalRows($queryRes) )
		{
			$action = 'forums';
			// Save that it's been completed
			$get = unserialize($this->settings['conv_completed']);
			$us = $get[$this->lib->app['name']];
			$us = is_array($us) ? $us : array();
			if (empty($this->errors))
			{
				$us = array_merge($us, array($action => true));
			}
			else
			{
				$us = array_merge($us, array($action => 'e'));
			}
			$get[$this->lib->app['name']] = $us;
			IPSLib::updateSettings(array('conv_completed' => serialize($get)));

			// Errors?
			if (!empty($this->errors))
			{
				$es = 'The following errors occurred: <ul>';
				foreach ($this->errors as $e)
				{
					$es .= "<li>{$e}</li>";
				}
				$es .= '</ul>';
			}
			else
			{
				$es = 'No problems found.';
			}

			// Display
			$this->registry->output->html .= $this->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
			$this->sendOutput();
		}
		
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($queryRes) )
		{
			// Set info
			$save = array( 'parent_id'	   => 'c'.$row['Cat_ID'],
						   'position'	   => $row['Forum_Order'],
						   'name'		   => $row['Forum_name'],
						   'description'   => $row['Forum_description'],
						   'topics'		   => $row['No_of_topics'],
						   'posts'		   => $row['No_of_posts'],
						   'inc_postcount' => 1,
						   'status'		   => 1 );
				
			// Save
			$this->lib->convertForum($row['Forum_ID'], $save, array());		
			
			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'EmailNotify', 'where' => "Forum_ID={$row['Forum_ID']}"));
			$subsRes = ipsRegistry::DB('hb')->execute();
			while ( $tracker = ipsRegistry::DB('hb')->fetch() )
			{
				$savetracker = array( 'member_id' => $tracker['Author_ID'],
									  'forum_id'  => $tracker['Forum_ID'] );					
				$this->lib->convertForumSubscription($tracker['Author_ID'].'-'.$tracker['Forum_ID'], $savetracker);	
			}
		}

		//-----------------------------------------
		// Next
		//-----------------------------------------
		$total = $this->request['total'];
		$pc = round((100 / $total) * $this->end);
		$message = ($pc > 100) ? 'Finishing...' : "{$pc}% complete";
		IPSLib::updateSettings(array('conv_error' => serialize($this->errors)));
		$end = ($this->end > $total) ? $total : $this->end;
		$this->registry->output->redirect("{$this->settings['base_url']}app=convert&module={$this->lib->app['sw']}&section={$this->lib->app['app_key']}&do={$this->request['do']}&st={$this->end}&cycle={$this->request['cycle']}&total={$total}", "{$end} of {$total} converted<br />{$message}" );
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
		$main = array( 'select'		=> 't.*',
					   'from'		=> array( 'Topic' => 't' ),
					   'add_join'	=> array( array( 'select' => 'th.Thread_id, th.Author_ID as starter_id, th.Message_date as start_date',
					   								 'from'	  => array( 'Thread' => 'th' ),
					   								 'where'  => 'th.Thread_ID = t.Start_Thread_ID',
					   								 'type'	  => 'inner' ),
					   						  array( 'select' => 'u.Username as starter_name',
					   						  		 'from'	  => array( 'Author' => 'u' ),
					   						  		 'where'  => 'th.Author_ID = u.Author_ID',
					   						  		 'type'	  => 'left' ) ),
					   'order'		=> 'Topic_ID ASC' );

		$loop = $this->lib->load('topics', $main, array('tracker'));

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$row['start_date'] = is_object($row['start_date']) ? strtotime(DATE_FORMAT($row['start_date'], DATE_ATOM)) : intval(strtotime($row['start_date']));
			
			$save = array( 'forum_id'			=> $row['Forum_ID'],
						   'title'				=> $row['Subject'],
						   'poll_state'			=> 0,
						   'starter_id'			=> intval($row['starter_id']),
						   'starter_name'		=> $row['starter_name'],
						   'start_date'			=> $row['start_date'],
						   'last_post'			=> $row['start_date'],
						   'views'				=> $row['No_of_views'],
						   'posts'				=> $row['No_of_replies'],
						   'state'		   	 	=> $row['Locked'] == 1 ? 'closed' : 'open',
						   'pinned'				=> ($row['Priority'] > 0) ? 1 : 0,
						   'approved'			=> $row['Hide'] == 1 ? 0 : 1 );
			$this->lib->convertTopic($row['Topic_ID'], $save);
			
			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'EmailNotify', 'where' => "Topic_ID={$row['Topic_ID']}"));
			$subsRes = ipsRegistry::DB('hb')->execute();
			while ( $tracker = ipsRegistry::DB('hb')->fetch($subsRes) )
			{
				$savetracker = array( 'member_id' => $tracker['Author_ID'],
									  'topic_id'  => $tracker['Topic_ID'] );					
				$this->lib->convertTopicSubscription($tracker['Author_ID'].'-'.$tracker['Topic_ID'], $savetracker);	
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
		$main = array( 'select'	 => 'p.*',
					  'from'	 => array( 'Thread' => 'p' ),
					  'add_join' => array( array( 'select' => 'u.Username, u.Author_ID',
					  							  'from'   => array( 'Author' => 'u'),
					  							  'where'  => 'p.Author_ID = u.Author_ID',
					  							  'type'   => 'left' ) ),
					  'order'	 => 'Thread_ID ASC' );

		$loop = $this->lib->load('posts', $main);
		
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'topic_id'    => intval($row['Topic_ID']),
						   'author_id'	 => intval($row['Author_ID']),
						   'author_name' => $row['Username'],
						   'post_date'	 => is_object($row['Message_date']) ? strtotime(DATE_FORMAT($row['Message_date'], DATE_ATOM)) : intval(strtotime((string)$this->fixFrenchDate($row['Message_date']))),
						   'post'		 => $this->fixPostData($row['Message']),
						   'ip_address'	 => $row['IP_addr'],
						   'use_sig'	 => $row['Show_signature'],
						   'use_emo'	 => 1,
						   'queued'		 => $row['Hide'] );

			$this->lib->convertPost($row['Thread_ID'], $save);
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
		
		$main = array( 'select' => '*',
					   'from' 	=> 'PMMessage',
					   'order'	=> 'PM_ID ASC' );
		
		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));
					
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$row['PM_Message_date'] = is_object($row['PM_Message_date']) ? strtotime(DATE_FORMAT($row['PM_Message_date'], DATE_ATOM)) : intval(strtotime((string)$this->fixFrenchDate($row['PM_Message_date'])));
			
			//-----------------------------------------
			// Post Data
			//-----------------------------------------
			$post = array( 'msg_id'			   => $row['PM_ID'],
						   'msg_topic_id'      => $row['PM_ID'],
						   'msg_date'          => $row['PM_Message_date'],
						   'msg_post'          => $this->fixPostData($row['PM_Message']),
						   'msg_post_key'      => md5(microtime()),
						   'msg_author_id'     => intval($row['From_ID']),
						   'msg_is_first_post' => 1 );	
				
			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$map_master = array( 'map_topic_id'    => $row['u2uid'],
								 'map_folder_id'   => 'myconvo',
								 'map_read_time'   => 0,
								 'map_last_topic_reply' => $row['PM_Message_date'],
								 'map_user_active' => 1,
								 'map_user_banned' => 0,
								 'map_has_unread'  => $row['Read_Post'] == '-1' ? 1 : 0,
								 'map_is_system'   => 0 );
				
			$maps = array();
			$maps[] = array_merge( $map_master, array( 'map_user_id' => intval($row['From_ID']), 'map_is_starter' => 1 ) );
			$maps[] = array_merge( $map_master, array( 'map_user_id' => intval($row['Author_ID']), 'map_is_starter' => 0 ) );
	
			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$topic = array( 'mt_id'			     => $row['PM_ID'],
							'mt_date'		     => $row['PM_Message_date'],
							'mt_title'		     => $row['PM_Title'],
							'mt_starter_id'	     => intval($row['From_ID']),
							'mt_start_time'      => $row['PM_Message_date'],
							'mt_last_post_time'  => $row['PM_Message_date'],
							'mt_invited_members' => serialize( array( intval($row['Author_ID']) => intval($row['Author_ID']) ) ),
							'mt_to_count'		 => 1,
							'mt_to_member_id'	 => intval($row['Author_ID']),
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
					   'from' 	=> array( 'Poll' => 'p'),
					   'add_join' => array( array( 'select' => 't.*',
					   							   'from'   => array( 'Topic' => 't'),
					   							   'where'  => 'p.Poll_ID = t.Poll_ID',
					   							   'type'   => 'left' ),
					   						array( 'select' => 'th.Author_ID, th.Message_date',
					   							   'from'   => array( 'Thread' => 'th' ),
					   							   'where'  => 'th.Thread_ID = t.Start_Thread_ID',
					   							   'type'   => 'left' ) ),
					   'order'	=> 'p.Poll_ID ASC' );
		
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
			
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'PollVote', 'where' => "Poll_ID='" . intval($row['Poll_ID']) . "'"));
			$voterRes = ipsRegistry::DB('hb')->execute();
			while ( $voter = ipsRegistry::DB('hb')->fetch($voterRes) )
			{
				// Do we already have this user's votes
				if (in_array($voter['Author_ID'], $votes))
				{
					continue;
				}
				
				$vsave = array( 'vote_date'		 => time(),
								'tid'			 => $row['Topic_ID'],
								'member_id'		 => $voter['Author_ID'],
								'forum_id'		 => $row['Forum_ID'],
								'member_choices' => serialize(array()) );
			
				$this->lib->convertPollVoter($voter['Poll_ID'] . '-' . $voter['Author_ID'], $vsave);
			}
			
			//-----------------------------------------
			// Options are stored in one place...
			//-----------------------------------------
			$choices = array();
			$votes = array();
			$totalVotes = 0;
			
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'PollChoice', 'where' => "Poll_ID='" . intval($row['Poll_ID']) . "'"));
			$choiceRes = ipsRegistry::DB('hb')->execute();
			while ( $choice = ipsRegistry::DB('hb')->fetch($choiceRes) )
			{
				$choices[ $choice['Choice_ID'] ] = $choice['Choice'];
				$votes[ $choice['Choice_ID'] ]	= $choice['Votes'];
				$totalVotes = $choice['Votes'];
			}
			
			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------
			$poll_array = array( // WebWiz only allows one question per poll
								 1 => array( 'question'	=> $row['Poll_question'],
								 			 'choice'	=> $choices,
											 'votes'	=> $votes ) );
			$save = array( 'tid'		=> $row['Topic_ID'],
						   'start_date'	=> is_object($row['Message_date']) ? strtotime(DATE_FORMAT($row['Message_date'], DATE_ATOM)) : intval(strtotime($row['Message_date'])),
						   'choices'   	=> addslashes(serialize($poll_array)),
						   'starter_id'	=> $row['Author_ID'],
						   'votes'     	=> $totalVotes,
						   'forum_id'  	=> $row['Forum_ID'],
						   'poll_question'	=> $row['Poll_question'] );

			$this->lib->convertPoll($row['Poll_ID'], $save);
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
		$this->lib->saveMoreInfo('attachments', array('attach_path'));
		
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => '*',
					   'from'   => 'Thread',
					   'where'  => "File_uploads != ''",
					   'order'  => 'Thread_ID ASC' );
					
		$loop = $this->lib->load('attachments', $main);
		
		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------
					
		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_board/uploads):')), 'path');
		
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
			$attachments = explode(',', $row['File_uploads']);
			
			foreach ( $attachments as $id => $data )
			{
				if ( $data == '' )
				{
					continue;
				}
				
				// What's the extension?
				$ext = explode('.', $data);
				$extension = array_pop( $e );
				
				// Is this an image?
				$image = false;
				if ( in_array( $extension, array( 'png', 'jpg', 'jpeg', 'gif' ) ) )
				{
					$image = true;
				}
			
				// Sort out data
				$save = array( 'attach_rel_id'	   => $row['Thread_ID'],
							   'attach_ext'		   => $extension,
							   'attach_file'	   => $data,
							   'attach_location'   => $data,
							   'attach_is_image'   => $image,
							   'attach_rel_module' => 'post',
							   'attach_member_id'  => $row['Author_ID'],
							   'attach_hits'	   => $row['downloads'],
							   'attach_date'	   => is_object($row['Message_date']) ? strtotime(DATE_FORMAT($row['Message_date'], DATE_ATOM)) : intval(strtotime((string)$this->fixFrenchDate($row['Message_date']))) );
			// Send em on
			$this->lib->convertAttachment( $row['Thread_ID'].'-'. $id, $save, $us['attach_path'] );
			}
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
						'from' 	 => 'Group',
						'where'  => 'Special_rank = 0',
						'order'	 => 'Group_ID ASC' );
					
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
			$save = array( 'posts'	=> $row['Minimum_posts'],
						   'title'	=> $row['Name'] );
			$this->lib->convertRank($row['Group_ID'], $save, $us['rank_opt']);			
		}
		$this->lib->next();
	}

	public function fixFrenchDate($date)
	{
		// Below array is there if need later on.
		/*$monthTranslations = array( 'janvier'	=> '',
									'février'	=> '',
									'fevrier'	=> '',
									'mars'		=> '',
									'avril'		=> '',
									'mai'		=> '',
									'juin'		=> '',
									'juillet'	=> '',
									'août'		=> '',
									'aout'		=> '',
									'septembre' => '',
									'octobre'	=> '',
									'novembre'	=> '',
									'décembre'	=> '',
									'decembre'	=> '' );*/

		// Make the months lowercase
		$date = strtolower($date);

		// Check if strtotime can create a unix timestamp.
		if ( strtotime($date) !== FALSE )
		{
			return $date;
		}

		// Add seconds to the timestamp
		$date .= ':00';

		// Check again if strtotime can create a unix timestamp.
		if ( strtotime($date) !== FALSE )
		{
			return $date;
		}

		$shortMonthTranslations = array( 'fév'	=> 'feb',
										 'fev'	=> 'feb',
										 'mars'	=> 'mar',
										 'avr'  => 'apr',
										 'mai'	=> 'may',
										 'juin' => 'june',
										 'jui'	=> 'jul',
										 'aoû'	=> 'aug',
										 'aou'	=> 'aug',
										 'déc'	=> 'dec' );

		// Time to replace all french months with english
		foreach( $shortMonthTranslations as $toFix => $theFix )
		{
			$date = str_replace( $toFix, $theFix, $date );
		}

		// Replace slashes with spaces.
		$date = str_replace('/', ' ', $date);

		// Check again if strtotime can create a unix timestamp.
		if ( strtotime($date) !== FALSE )
		{
			return $date;
		}

		print 'Error: ' . $date;exit;
	}
}
	
