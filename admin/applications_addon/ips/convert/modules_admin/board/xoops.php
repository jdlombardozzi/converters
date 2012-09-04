<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * Xoops
 * Last Update: $Date: 2010-07-22 11:29:06 +0200(gio, 22 lug 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 447 $
 */

$info = array( 'key'   => 'xoops',
			   'name'  => 'Xoops',
			   'login' => FALSE );
		
class admin_convert_board_xoops extends ipsCommand
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
								'pms'			=> array('members'),
								'ranks'			=> array(),
								/*'polls'         => array('topics'),*/
								'attachments'   => array('posts') );
				
		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'Xoops CBB &rarr; IP.Board Converter' );

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
				return  $this->lib->countRows('users');
				break;
				
			case 'forums':
				return $this->lib->countRows('bb_forums') + $this->lib->countRows('bb_categories');
				break;
				
			case 'topics':
				return  $this->lib->countRows('bb_topics');
				break;
				
			case 'posts':
				return  $this->lib->countRows('bb_posts');
				break;
				
			case 'pms':
				return  $this->lib->countRows('priv_msgs');
				break;
				
			case 'attachments':
				return $this->lib->countRows('bb_attachments');
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
		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'groupid', 'nf' => 'name'));

		//---------------------------
		// Loop
		//---------------------------
		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['groupid'], $row['name']);			
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
		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'groupid', 'nf' => 'name'));

		//---------------------------
		// Loop
		//---------------------------
		
		foreach( $loop as $row )
		{						
			$save = array(
				'g_title'	  => $row['name'],
				'g_perm_id'	  => $row['groupid'],
				'g_access_cp' => strstr(strtolower($row['group_type']), 'admin') ? 1 : 0 );
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
		$pcpf = array( 'user_from'		=> 'Location',
					   'user_icq'	  => 'ICQ Number',
					   'user_aim'	  => 'AIM ID',
					   'user_yim'	  => 'Yahoo ID',
					   'user_msnm'	  => 'MSN ID',
					   'user_occ'  	  => 'Occupation',
					   'user_intrest' => 'Interests',
					   'url' => 'Website' );
		
		$this->lib->saveMoreInfo('members', array_keys($pcpf));

		//---------------------------
		// Set up
		//---------------------------

		$main = array( 'select'   => '*',
					   'from' 	  => 'users',
					   'order'	  => 'uid ASC' );

		$loop = $this->lib->load('members', $main);
		
		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();
		
		// We need to know the avatars path
		$ask['avvy_path'] = array('type' => 'text', 'label' => 'The path to the folder where custom avatars are saved (no trailing slash - usually /path_to_cbb/uploads):');
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
			ipsRegistry::DB('hb')->build( array( 'select' => 'groupid',
														 'from' => 'groups_users_link',
														 'where' => "uid='{$row['uid']}'",
														 'order'  => 'linkid ASC' ) );
			$groupRes = ipsRegistry::DB('hb')->execute();
			
			while ( $group = ipsRegistry::DB('hb')->fetch($groupRes) )
			{
				$groups[] = $group['groupid'];
			}
			
			$primaryGroup = array_shift($groups);
				
			// Basic info				
			$info = array( 'id'				  => $row['uid'],
						   'group'			  => $primaryGroup,
						   'secondary_groups' => $groups,
						   'joined'	   		  => $row['user_regdate'],
						   'username'  		  => $row['uname'],
						   'email'	   		  => $row['email'],
						   'md5pass' 		  => $row['pass'] );
			
			if ( $row['name'] != '' ) { $info['displayname'] = $row['name']; }

			// Member info
			$members = array( 'ip_address'			  => '127.0.0.1',
							  'posts'				  => $row['posts'],
							  'allow_admin_mails' 	  => 0,
							  'time_offset'			  => intval($row['timezone_offset']),
							  'hide_email'			  => $row['user_viewemail'] == 0 ? 1 : 0,
							  'email_pm'			  => $row['user_mailok'],
							  'last_post'			  => $row['last_login'],
							  'view_sigs'			  => 1,
							  'view_avs'			  => 1,
							  'msg_show_notification' => 1,
							  'last_visit'			  => $row['last_login'],
							  'last_activity'		  => $row['last_login'],
							  'coppa_user'			  => 0,
							  'members_disable_pm'	  => 0 );

			// Profile
			$profile = array( 'signature' => $this->fixPostData($row['sightml']) );

			//-----------------------------------------
			// Avatars and profile pictures
			//-----------------------------------------
			$path;
			if ( $row['user_avatar'] != 'blank.gif' )
			{
				$profile['avatar_type'] = 'upload';
				$profile['avatar_location'] = $row['user_avatar'];
				$path = $us['avvy_path'];
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
						'from'  => 'bb_categories',
						'order' => 'cat_id ASC' );
						
		$loop = $this->lib->load('forums', $main, array('forum_tracker'), array(), TRUE );
		
		$this->lib->getMoreInfo('forums', $loop);	
		
		//---------------------------
		// Loop
		//---------------------------
		foreach ( $loop as $row )
		{
			// Set info
			$save = array( 'parent_id'	   => -1,
						   'position'	   => $row['cat_order'],
						   'name'		   => $row['cat_title'],
						   'description'   => $row['cat_description'],
						   'inc_postcount' => 1,
						   'sub_can_post'  => 0,
						   'status'		   => 1 );
			// Save
			$this->lib->convertForum('C_'.$row['cat_id'], $save, array());		
		}
								
					
		//---------------------------
		// Set up
		//---------------------------
		ipsRegistry::DB('hb')->build( array( 'select' => '*',
											 'from'  => 'bb_forums',
											 'order' => 'forum_id ASC' ) );
		$forumRes = ipsRegistry::DB('hb')->execute();
					
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($forumRes) )
		{		
			//-----------------------------------------
			// And go
			//-----------------------------------------
			$save = array( 'parent_id'		=> ($row['parent_forum'] > 0) ? $row['parent_forum'] : 'C_'.$row['cat_id'],
						   'position'		=> $row['forum_order'],
						   'name'			=> $row['forum_name'],
						   'description' => $row['forum_desc'],
						   'sub_can_post'	=> 1,
						   'redirect_on'	=> 0,
						   'redirect_hits' => 0,
						   'status'		=> 1,
						   'posts'			=> $row['posts'],
						   'topics'		=> $row['forum_posts'],
						   'use_ibc'      => 1 );
			
			$this->lib->convertForum($row['forum_id'], $save, array());	
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
		$main = array( 'select'		=> 't.*',
					   'from'		=> array( 'bb_topics' => 't' ),
					   'add_join' => array( array( 'select' => 'u.name, u.uname',
					   							   'from' => array( 'users' => 'u'),
					   							   'where' => 't.topic_poster = u.uid',
					   							   'type' => 'left' ) ),
					   'order'		=> 't.topic_id ASC' );

		$loop = $this->lib->load('topics', $main, array());

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			// Stupid Xoops in deleted a user and not putting a poster name on topics.
			if ( $row['name'] == '' AND $row['uname'] == '' ) { $row['name'] == 'guest'; $row['topic_poster'] = 0; }
			
			$save = array( 'forum_id'			=> $row['forum_id'],
						   'title'				=> $row['topic_title'],
						   'poll_state'			=> 0,
						   'starter_id'			=> intval($row['topic_poster']),
						   'starter_name'		=> $row['name'] != '' ? $row['name'] : $row['uname'],
						   'start_date'			=> $row['topic_time'],
						   'last_post'			=> $row['topic_time'],
						   'views'				=> $row['topic_views'],
						   'posts'				=> $row['topic_replies'],
						   'state'		   	 	=> 'open',
						   'pinned'				=> $row['topic_sticky'] == 0 ? 0 : 1,
						   'approved'			=> $row['approved'] == 1 ? 1 : 0 );
			$this->lib->convertTopic($row['topic_id'], $save);
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
		$main = array( 'select'		=> 'p.*',
					   'from'		=> array( 'bb_posts' => 'p' ),
					   'add_join' => array( array( 'select' => 't.post_text',
					   							   'from' => array( 'bb_posts_text' => 't' ),
					   							   'where' => 'p.post_id = t.post_id',
					   							   'type' => 'left' ),
					   						array( 'select' => 'u.name, u.uname',
					   							   'from' => array( 'users' => 'u'),
					   							   'where' => 'p.uid = u.uid',
					   							   'type' => 'left' ) ),
					   'order'		=> 'p.post_id ASC' );

		$loop = $this->lib->load('posts', $main);
		
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'topic_id'    => intval($row['topic_id']),
						   'author_id'	 => intval($row['uid']),
						   'author_name' => $row['poster_name'] != '' ? $row['poster_name'] : ( $row['name'] != '' ? $row['name'] : $row['uname']),
						   'post_date'	 => $row['post_time'],
						   'post'		 => $this->fixPostData($row['post_text']),
						   'ip_address'	 => '127.0.0.1',
						   'use_sig'	 => $row['attachsig'],
						   'use_emo'	 => $row['dosmiley'],
						   'queued'		 => $row['approved'] == -1 ? 1 : 0 );

			$this->lib->convertPost($row['post_id'], $save);
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
					   'from' 	=> 'priv_msgs',
					   'order'	=> 'msg_id ASC' );
		
		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));
					
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Post Data
			//-----------------------------------------
			$posts = array( array( 'msg_id'			   => $row['msg_id'],
								   'msg_topic_id'      => $row['msg_id'],
								   'msg_date'          => $row['msg_time'],
								   'msg_post'          => $this->fixPostData($row['msg_text']),
								   'msg_post_key'      => md5(microtime()),
								   'msg_author_id'     => intval($row['from_userid']),
								   'msg_is_first_post' => 1 ) );	
				
			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$map_master = array( 'map_topic_id'    => $row['msg_id'],
								 'map_folder_id'   => 'myconvo',
								 'map_read_time'   => 0,
								 'map_last_topic_reply' => $row['msg_time'],
								 'map_user_active' => 1,
								 'map_user_banned' => 0,
								 'map_has_unread'  => $row['read_msg'] == 0 ? 1 : 0,
								 'map_is_system'   => 0 );
				
			$maps = array();
			$maps[] = array_merge( $map_master, array( 'map_user_id' => intval($row['from_userid']), 'map_is_starter' => 1 ) );
			$maps[] = array_merge( $map_master, array( 'map_user_id' => intval($row['to_userid']), 'map_is_starter' => 0 ) );
	
			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$topic = array( 'mt_id'			     => $row['msg_id'],
							'mt_date'		     => $row['msg_time'],
							'mt_title'		     => $row['subject'],
							'mt_starter_id'	     => intval($row['from_userid']),
							'mt_start_time'      => $row['msg_time'],
							'mt_last_post_time'  => $row['msg_time'],
							'mt_invited_members' => serialize( array( intval($row['to_userid']) => intval($row['to_userid']) ) ),
							'mt_to_count'		 => 1,
							'mt_to_member_id'	 => intval($row['to_userid']),
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
						'from' 	 => 'ranks',
						'where'  => 'rank_special = 0',
						'order'	 => 'rank_id ASC' );
					
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
			$save = array( 'posts'	=> $row['rank_min'],
						   'title'	=> $row['rank_title'] );
			$this->lib->convertRank($row['rank_id'], $save, $us['rank_opt']);			
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
		$main = array( 'select' => 'a.*',
					   'from'   => array( 'bb_attachments' => 'a' ),
					   'add_join' => array( array( 'select' => 'p.uid',
					   							   'from'  => array( 'bb_posts' => 'p' ),
					   							   'where' => 'a.post_id = p.post_id',
					   							   'type' => 'inner' ) ),
					   'order'  => 'a.attach_id ASC' );
					
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
			// What's the extension?
			$ext = explode('.', $row['name_disp']);
			$extension = array_pop( $ext );
			
			$image = in_array( $extension, array( 'png', 'jpg', 'jpeg', 'gif' ) ) ? TRUE : FALSE;
		
			// Sort out data
			$save = array( 'attach_rel_id'	   => $row['post_id'],
						   'attach_ext'		   => $extension,
						   'attach_file'	   => $row['filename'],
						   'attach_location'   => $row['name_disp'],
						   'attach_is_image'   => $image,
						   'attach_rel_module' => 'post',
						   'attach_member_id'  => $row['uid'],
						   'attach_hits'	   => $row['download'],
						   'attach_date'	   => $row['attach_time'] );
		// Send em on
		$this->lib->convertAttachment( $row['attach_id'], $save, $us['attach_path'] );
		}
		$this->lib->next();
	}
}
	
