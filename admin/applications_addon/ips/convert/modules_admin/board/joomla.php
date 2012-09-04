<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * SMF
 * Last Update: $Date: 2009-12-06 08:57:22 -0500 (Sun, 06 Dec 2009) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Jason Lombardozzi
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 397 $
 */

$info = array( 'key'	=> 'joomla',
			   'name'	=> 'Joomla 1.5',
			   'login'	=> true );

class admin_convert_board_joomla extends ipsCommand
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

		$this->actions = array( 'groups'  => array(),
														'members' => array(),
														'forums'		=> array(),
														'moderators'	=> array('groups', 'members', 'forums'),
														'topics'		=> array('members', 'forums'),
														'posts'			=> array('members', 'topics'),
														'pms'			=> array('members'),
														'attachments'	=> array('posts'),
														'emoticons'		=> array(),
														'ranks' => array() );

		//-----------------------------------------
	  // Load our libraries
	  //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	  $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( $info['name'] + ' &rarr; IP.Board Converter' );

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
			case 'groups':
				return $this->lib->countRows('core_acl_aro_groups');
				break;

			case 'members':
				return $this->lib->countRows('users');
				break;

			case 'forums':
				return $this->lib->countRows('fb_categories');
				break;

			case 'moderators':
				return $this->lib->countRows('fb_moderation');
				break;

			case 'topics':
				return $this->lib->countRows('fb_messages', "parent='0'");
				break;

			case 'posts':
				return $this->lib->countRows('fb_messages');
				break;

			case 'pms':
				return $this->lib->countRows('uddeim');
				break;

			case 'attachments':
				return $this->lib->countRows('fb_attachments');
				break;

			case 'emoticons':
				return $this->lib->countRows('fb_smileys');
				break;

			case 'ranks':
				return $this->lib->countRows('fb_ranks');
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
	private function fixPostData($text)
	{
		// Sort out the list tags
    $text = stripslashes($text);

    // Sort out newlines
		$text = nl2br($text);

		// And img tags
		$text = preg_replace("#\[img width=(\d+) height=(\d+)\](.+)\[\/img\]#i", "[img]$3[/img]", $text);
		$text = preg_replace("#\[size=(\d+)\]#i", "[size=\"$1\"]", $text);
		$text = preg_replace("#\[color=(\#.{6})\]#i", "[color=\"$1\"]", $text);


		return $text;
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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'core_acl_aro_groups',
						'order'		=> 'id ASC' );

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'id', 'nf' => 'name'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$save = array(
				'g_title'			=> $row['name'],
				'g_perm_id'			=> $row['id'],
				'prefix'			=> '',
				'suffix'			=> '',
//				'g_max_messages'	=> $row['max_messages'],
//				'g_hide_from_list'	=> $row['hidden'],
				);
			$this->lib->convertGroup($row['id'], $save);
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
		$pcpf = array(
			'gender'		=> 'Gender',
			'websiteurl'	=> 'Website',
			'location'		=> 'Location',
			'ICQ'			=> 'ICQ Number',
			'AIM'			=> 'AIM ID',
			'YIM'			=> 'Yahoo ID',
			'MSN'			=> 'MSN ID',
			'personalText' => 'Personal Text' );

		$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('pp_path', 'gal_path', 'avatar_salt')));

		//---------------------------
		// Set up
		//---------------------------

		$main = array( 'select' 	=> 'u.id, u.username, u.email, u.password, u.gid, u.registerDate, u.lastvisitDate',
									 'from' 		=> array( 'users' => 'u' ),
									 'add_join' => array( array( 'select' => 'fu.signature, fu.posts, fu.avatar, fu.group_id, fu.personalText, fu.gender, fu.birthdate, fu.location, fu.ICQ, fu.AIM, fu.YIM, fu.MSN, fu.SKYPE, fu.GTALK, fu.websiteurl, fu.hideEmail',
									                             'from' => array( 'fb_users' => 'fu'),
									                             'where' => 'u.id = fu.userid',
									                             'type' => 'left' ) ),
									 'order'		=> 'id ASC' );

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		// Avatars
		$ask['gal_path'] = array('type' => 'text', 'label' => 'Path to avatars gallery folder (no trailing slash, default /path_to_joomla/avatars): ');

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

			// Basic info
			$info = array(
				'id'				  => $row['id'],
				'username'		=> $row['username'],
				'displayname' => $row['username'],
				'joined'			=> strtotime($row['registerDate']),
				'group'				=> $row['gid'],
				'password'		=> $row['password'],
				'email'				=> $row['email'] );

			// Member info
			$birthday = ($row['birthdate']) ? explode('-', $row['birthdate']) : null;

			$members = array(
				'posts'				=> $row['posts'],
				'last_visit'		=> $row['lastvisitDate'],
				'bday_day'			=> ($row['birthdate']) ? $birthday[2] : '',
				'bday_month'		=> ($row['birthdate']) ? $birthday[1] : '',
				'bday_year'			=> ($row['birthdate']) ? $birthday[0] : '',
				'hide_email' 		=> $row['hideEmail'],
				'time_offset'		=> 0,
				'email_pm'      	=> 0,
				'title'				=> $row['personalText'],
				'ip_address'		=> '127.0.0.1',
				'misc'				=> '',
				'warn_level'		=> '' );

			// Profile
			$profile = array( 'signature' => $this->fixPostData($row['signature']) );

			//-----------------------------------------
			// Avatars
			//-----------------------------------------
			$path = '';

			if ($row['avatar'])
			{
				// URL
				if (preg_match('/http/', $row['avatar']))
				{
					$profile['avatar_type'] = 'url';
					$profile['avatar_location'] = $row['avatar'];
				}
				// Gallery
				else
				{
					$profile['avatar_type'] = 'upload';
					$profile['avatar_location'] = $row['avatar'];
					$path = $us['gal_path'];
				}
			}

			//-----------------------------------------
			// Custom Profile fields
			//-----------------------------------------

			// Pseudo
			$custom = array();
			foreach ($pcpf as $id => $name)
			{
				if ($id == 'gender')
				{
					switch ($row['gender']) {
						case 1:
							$row['gender'] = 'm';
							break;

						case 2:
							$row['gender'] = 'f';
							break;

						default:
							$row['gender'] = 'u';
							break;
					}

				}
				if ($us[$id] != 'x')
				{
					$custom['field_'.$us[$id]] = $row[$id];
				}
			}

			//-----------------------------------------
			// And go!
			//-----------------------------------------
			$this->lib->convertMember($info, $members, $profile, $custom, $path);
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
						'from' 		=> 'fb_categories',
						'order'		=> 'id ASC' );

		$loop = $this->lib->load('forums', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Save
			//-----------------------------------------
			$save = array(
				'topics'			=> $row['numTopics'],
				'posts'			  	=> $row['numPosts'],
				'last_post'		  	=> $row['time_last_msg'],
				'last_poster_name'	=> '',
				'parent_id'		  	=> $row['parent'] == 0 ? -1 : $row['parent'],
				'name'			  	=> $row['name'],
				'description'	  	=> $row['description'],
				'position'		  	=> $row['ordering'],
				'use_ibc'		  	=> 1,
				'use_html'		  	=> 0,
				'status'			=> $row['locked'] == 1 ? 0 : 1,
				'inc_postcount'	  	=> 1,
				'password'		  	=> '',
				'sub_can_post'		=> ($row['parent'] == 0) ? 0 : 1,
				'redirect_on'		=> 0,
				'redirect_url'		=> '',
				//'preview_posts'		=> $moderation,
				'forum_allow_rating'=> 1,
				'inc_postcount'		=> 1,
				);

			$this->lib->convertForum($row['id'], $save, $perms);
		}
		$this->lib->next();
	}

	/**
	 * Convert topics
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_topics()
	{
		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> '*',
						'from' 		=> 'fb_messages',
						'where' => "parent='0'",
						'order'		=> 'id ASC' );

		$loop = $this->lib->load('topics', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'title'				=> stripslashes($row['subject']),
				'start_date'		=> $row['time'],
				'pinned'			=> $row['ordering'] > 0 ? 1 : 0,
				'forum_id'			=> $row['catid'],
				'starter_id'		=> $row['userid'],
				'starter_name'		=> $row['name'],
				'last_poster_id'	=> '0',
				'poll_state'		=> '',
				'posts'				=> 1,
				'views'				=> $row['hits'],
				'state'				=> ($row['locked']) == 1 ? 'closed' : 'open',
				'topic_queuedposts'	=> 0,
				'approved'			=> 1,
				);
			$this->lib->convertTopic($row['id'], $save);
		}
		$this->lib->next();
	}

	/**
	 * Convert posts
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_posts()
	{
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' 	=> 'm.*',
                   'from' 		=> array( 'fb_messages' => 'm'),
                   'add_join' => array( array( 'select' => 't.message',
                                               'from' => array( 'fb_messages_text' => 't'),
                                               'where' => 'm.id = t.mesid',
                                               'type' => 'inner' ) ),
                   'order'		=> 'm.id ASC' );

		$loop = $this->lib->load('posts', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'topic_id'			=> $row['thread'],
				'post_date'			=> $row['time'],
				'author_id'			=> $row['userid'],
				'author_name'		=> $row['name'],
				'ip_address'		=> $row['poster_ip'],
				'use_emo'			=> 1,
				'edit_time'			=> $row['modified_time'],
				'edit_name'			=> $row['modified_by'],
				'post'				=> $this->fixPostData($row['message']),
				'queued'			=> 0 );

			$this->lib->convertPost($row['id'], $save);
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
						'from' 		=> 'uddeim',
						'where' => "replyid = '0'",
						'order'		=> 'id ASC' );

		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
				$posts = array();
				$maps = array();

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'uddeim', 'where' => "replyid='{$row['id']}' || id='{$row['id']}'", 'order' => 'id ASC'));
				$pmRes = ipsRegistry::DB('hb')->execute();

				while ($message = ipsRegistry::DB('hb')->fetch($pmRes))
				{
//					foreach ($maps as $map)
//					{
//						// Skip if this message is to someone already mapped or message is first message
//						if ($map['map_user_id'] == $message['toid'] && $map['map_topic_id'] == $message['id'])
//						{
//							break 2;
//						}
//					}

					//-----------------------------------------
					// Post Data
					//-----------------------------------------
					$posts[] = array(
						'msg_id'			=> $message['id'],
						'msg_topic_id'      => $message['replyid'] == 0 ? $message['id'] : $message['replyid'],
						'msg_date'          => $message['datum'],
						'msg_post'          => $this->fixPostData($message['message']),
						'msg_post_key'      => md5(microtime()),
						'msg_author_id'     => $message['fromid'],
						'msg_is_first_post' => $message['replyid'] == 0 ? 1 : 0
						);

					if (in_array($message['toid'], array_keys($maps)))
					{
						continue;
					}

					$maps[$message['toid']] = array( 'map_user_id'     => $message['toid'],
						'map_topic_id'    => $message['replyid'] == 0 ? $message['id'] : $message['replyid'],
						'map_folder_id'   => 'myconvo',
						'map_read_time'   => $row['toread'] == 1 ? time() : 0,
						'map_last_topic_reply' => $row['datum'],
						'map_user_active' => 1,
						'map_user_banned' => 0,
						'map_has_unread'  => $row['toread'] == 1 ? 0 : 1,
						'map_is_system'   => 0,
						'map_is_starter'  => ( $message['toid'] == $row['fromid'] ) ? 1 : 0,
						'map_last_topic_reply' => $row['datum']
						);
				}

				if ( $row['toid'] != $row['fromid'] )
				{
					// Need to add self to this
					$maps[$row['fromid']] = array(
						'map_user_id'     => $row['fromid'],
						'map_topic_id'    => $row['id'],
						'map_folder_id'   => 'myconvo',
						'map_read_time'   => 0,
						'map_last_topic_reply' => $row['datum'],
						'map_user_active' => 1,
						'map_user_banned' => 0,
						'map_has_unread'  => 0,
						'map_is_system'   => 0,
						'map_is_starter'  => 1
						);
				}

				$topic = array(  'mt_id'			     => $row['id'],
					'mt_date'		     => $row['datum'],
					'mt_title'		     => substr($row['message'], 0, 99),
					'mt_starter_id'	     => $row['fromid'],
					'mt_start_time'      => $row['datum'],
					'mt_last_post_time'  => $row['datum'],
					'mt_invited_members' => serialize( array_keys( $maps ) ),
					'mt_to_count'		 => count(  array_keys( $maps ) ),
					'mt_to_member_id'	 => $row['toid'],
					'mt_replies'		 => count($posts) - 1,
					'mt_is_draft'		 => 0,
					'mt_is_deleted'		 => $row['totrash'],
					'mt_is_system'		 => 0 );
//if ( in_array( $row['id'], array( '144', '192', '48' ) ) )
//{
//	print "<PRE>";print_r($maps);exit;
//}

//print "<PRE>";print_r($maps);print_r($posts);print_r($topic);
			//-----------------------------------------
			// Go
			//-----------------------------------------
			$this->lib->convertPM($topic, $posts, $maps);
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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'fb_attachments',
						'order'		=> 'mesid ASC' );

		$loop = $this->lib->load('attachments', $main);

        //-----------------------------------------
        // We need to know the path
        //-----------------------------------------

        $this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved:')), 'path');

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

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
            // Joomla provides a full absolute path, but we don't really need it.
			//$path = substr( $row['filelocation'], 0, strrpos($row['filelocation'], '/') );
			$location = substr(strrchr($row['filelocation'], '/'),1);

			$row['attach_ext'] = substr( $location, strlen($location) - 3 );

			// Is this an image?
			$image = false;
			if ( in_array( $row['attach_ext'], array( 'png', 'jpg', 'jpeg', 'gif' ) ) )
			{
				$image = true;
			}

			// Sort out data
			$save = array(
				'attach_ext'			=> $row['attach_ext'],
				'attach_file'			=> $location,
				'attach_location'		=> $location,
				'attach_is_image'		=> $image,
				//'attach_filesize'		=> $row['attach_size'],
				'attach_rel_id'			=> $row['mesid'],
				'attach_rel_module'		=> 'post' );

			// Send em on
			$done = $this->lib->convertAttachment($row['mesid'], $save, $path);
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

		$main = array( 'select' 	=> 'm.*',
									 'from'		=> array( 'fb_moderation' => 'm' ),
									  'add_join' => array( array( 'select' => 'u.username',
									                              'from' => array( 'users' => 'u'),
									                              'where' => 'm.userid = u.id',
									                              'type' => 'inner' ) ) );

		$loop = $this->lib->load('moderators', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
							   'forum_id'	  => $row['catid'],
							   'member_name'  => $row['username'],
							   'member_id'	  => $row['userid'] );

			$this->lib->convertModerator($row['catid'].'-'.$row['userid'], $save);
		}
		$this->lib->next();
	}

	/**
	 * Convert Emoticons
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_emoticons()
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$this->lib->saveMoreInfo('emoticons', array('emo_path', 'emo_opt', 'emo_set'));

		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> '*',
						'from' 		=> 'fb_smileys',
						'order'		=> 'id ASC' );

		$loop = $this->lib->load('emoticons', $main);

		//-----------------------------------------
		// We need to know the path and how to handle duplicates
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$path = $us['emo_path'];

		$ask = array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_joomla/images):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') );

		// This is to work out which set to use
		if ($path)
		{
			if (!is_readable($path))
			{
				$this->lib->error('Your remote emoticons path is not readable.');
			}

			$desc = '';
			foreach (glob($path.'/*/') as $folder)
			{
				$explode = explode('/', $folder);
				array_pop($explode);
				$name = array_pop($explode);
				$options[$name] = $name;
			}
			$ask['emo_set'] = array('type' => 'dropdown', 'label' => 'The set to use', 'options' => $options);
		}

		if ($us['emo_set'])
		{
			$path .= '/'.$us['emo_set'];
		}

		$this->lib->getMoreInfo('emoticons', $loop, $ask, 'path' );

		//-----------------------------------------
		// Check all is well
		//-----------------------------------------
		if (!is_readable($path))
		{
			$this->lib->error('Your remote emoticons path is not readable.');
		}
		if (!is_writable(DOC_IPS_ROOT_PATH.'public/style_emoticons/'))
		{
			$this->lib->error('Your IP.Board emoticons path is not writeable. '.DOC_IPS_ROOT_PATH.'public/style_emoticons/');
		}

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'typed'		=> $row['code'],
				'image'		=> $row['location'],
				'clickable'	=> ($row['emoticonbar']) ? 1 : 0,
				'emo_set'	=> 'default' );
			$done = $this->lib->convertEmoticon($row['id'], $save, $us['emo_opt'], $path);
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
		$main = array(	'select' 	=> '*',
						'from' 		=> 'fb_ranks',
						'order'		=> 'rank_id ASC',
					);

		$loop = $this->lib->load('ranks', $main);

		//-----------------------------------------
		// We need to know what do do with duplicates
		//-----------------------------------------
		$this->lib->getMoreInfo('ranks', $loop, array('rank_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate ranks?')));

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'posts'	=> $row['rank_min'],
				'title'	=> $row['rank_title'],
				);
			$this->lib->convertRank($row['rank_id'], $save, $us['rank_opt']);
		}
		$this->lib->next();
	}
}