<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * FusionBB
 * Last Update: $Date: 2010-03-19 11:03:12 +0100(ven, 19 mar 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 437 $
 */

$info = array( 'key'	=> 'fusionbb',
			   'name'	=> 'FusionBB 3.0',
			   'login'	=> false );

class admin_convert_board_fusionbb extends ipsCommand
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

		// array('action' => array('action that must be completed first'))
		$this->actions = array(
			'custom_bbcode' => array(),
			'emoticons'		=> array(),
			'forum_perms'	=> array(),
			'groups' 		=> array('forum_perms'),
                'members'		=> array('groups', 'custom_bbcode'),
			'ignored_users'	=> array('members'),
			'profile_friends' => array('members'),
			'forums'		=> array('forum_perms'),
			'moderators'	=> array('members', 'forums'),
			'topics'		=> array('members', 'forums'),
			'posts'			=> array('members', 'topics', 'custom_bbcode', 'emoticons'),
			'pms'			=> array('members', 'custom_bbcode', 'emoticons'),
			'ranks'			=> array(),
			'attachments'	=> array('posts', 'pms'),
			'badwords'		=> array(),
			'banfilters'	=> array(),
			);

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------

		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'FusionBB &rarr; IP.Board Converter' );

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

		if (array_key_exists($this->request['do'], $this->actions) or $this->request['do'] == 'bad_emails' or $this->request['do'] == 'bad_usernames')
		{
			call_user_func(array($this, 'convert_'.$this->request['do']));
		}
		else
		{
			$this->lib->menu( array(
				'banfilters' => array(
					'single' => 'banlist',
					'multi'  => array(
						'banlist',
						'bad_emails',
						'bad_usernames',
				) ) ) );
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
				return $this->lib->countRows('users');
				break;

			case 'pms':
				return $this->lib->countRows('pt_topics', 'user_id>0');
				break;

			case 'attachments':
				return $this->lib->countRows('files');
				break;

			case 'badwords':
				return $this->lib->countRows('censorship');
				break;

			case 'banfilters':
				return $this->lib->countRows('banlist');
				break;

			case 'custom_bbcode':
				return $this->lib->countRows('bbcode');
				break;

			case 'emoticons':
				return $this->lib->countRows('smilies');
				break;

			case 'moderators':
				return $this->lib->countRows('forum_mods');
				break;

			case 'profile_friends':
				return $this->lib->countRows('buddies');
				break;

			case 'ignored_users':
				return  $this->lib->countRows('ignores');
				break;

			case 'ranks':
				return  $this->lib->countRows('user_titles');
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
			case 'emoticons':
			case 'custom_bbcode':
			case 'badwords':
			case 'attachments':
			case 'rss_import':
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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'groups',
						'order'		=> 'group_id ASC',
					);

		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'group_id', 'nf' => 'group_name'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['group_id'], $row['group_name']);
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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'groups',
						'order'		=> 'group_id ASC',
					);

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'group_id', 'nf' => 'group_name'));

		//---------------------------
		// Loop
		//---------------------------

		$groups = array();

		// Loop
		foreach( $loop as $row )
		{
			$save = array(
				'g_title'			=> $row['group_name'],
				'g_perm_id'			=> $row['group_id'],
				);
			$this->lib->convertGroup($row['group_id'], $save);
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

		$pcpf = array(
			'info_full_name'	=> 'Full Name',
			'info_homepage'		=> 'Homepage',
			'info_occupation'	=> 'Occupation',
			'info_interests'	=> 'Interests',
			'info_location'		=> 'Location',
			'info_icq'			=> 'ICQ Number',
			'info_aol'			=> 'AIM ID',
			'info_yahoo'		=> 'Yahoo ID',
			'info_msn'			=> 'MSN ID',
			);

		$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('pp_path', 'gal_path', 'avatar_salt')));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> 'u.*',
						'from' 		=> array('users' => 'u'),
						'order'		=> 'u.user_id ASC',
						'add_join'	=> array(
							array( 	'select' => 'i.*',
									'from'   =>	array( 'user_info' => 'i' ),
									'where'  => "u.user_id=i.user_id",
									'type'   => 'inner'
								),
							),
					);

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		// We need to know how to the avatar paths
		$ask['pp_path']  	= array('type' => 'text', 'label' => 'Path to avatars uploads folder (no trailing slash, default /pathtofusionbb/fbbavatars): ');
		$ask['gal_path'] 	= array('type' => 'text', 'label' => 'Path to avatars gallery folder (no trailing slash, default /pathtofusionbb/images/avatars): ');

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

			$fields = unserialize(urldecode($row['info_extra_profile_fields']));

			//-----------------------------------------
			// Set info
			//-----------------------------------------

			// Work out group
			ipsRegistry::DB('hb')->build(array('select' => 'group_id', 'from' => 'user_groups', 'where' => "user_id='{$row['user_id']}'"));
			ipsRegistry::DB('hb')->execute();
			$sgroups = array();
			while ($group = ipsRegistry::DB('hb')->fetch())
			{
				$sgroups[] = $group['group_id'];
			}
			$group = array_shift($sgroups);

			// Basic info
			$info = array(
				'id'				=> $row['user_id'],
				'group'				=> $group,
				'joined'			=> $row['info_reg_date'],
				'username'			=> $row['user_login'],
				'displayname'		=> $row['info_display_name'] ? $row['info_display_name'] : $row['user_login'],
				'email'				=> $row['info_real_email'],
				'md5pass'			=> $row['user_password'],
				);

			// Member info
			$birthday = ($row['info_birthday']) ? explode('-', $row['info_birthday']) : null;

			$members = array(
				'posts'				=> $row['info_total_posts'],
				'hide_email' 		=> ($row['info_display_email'] == $row['info_real_email']) ? 0 : 1,
				'title'				=> ($row['info_title_custom']) ? $row['info_title_custom'] : $row['info_title'],
				'ip_address'		=> $row['info_regip'],
				'last_visit'		=> $row['info_login_time'],
				'last_activity'		=> $row['info_last_online'],
				'last_post'			=> $row['info_last_post_time'],
				'view_sigs'			=> ($row['info_hide_sigs']) ? 0 : 1,
				'view_avs'			=> ($row['info_hide_avatars']) ? 0 : 1,
				'time_offset'		=> $row['info_timezone'],
				'email_pm'			=> $row['info_email_pt'],
				'bday_day'			=> ($row['info_birthday']) ? $birthday[2] : '',
				'bday_month'		=> ($row['info_birthday']) ? $birthday[1] : '',
				'bday_year'			=> ($row['info_birthday']) ? $birthday[0] : '',
				'allow_admin_mails' => $row['info_receive_emails'],
				'member_banned'		=> $row['info_is_banned'],
				);

			// Profile
			$profile = array(
				'signature'			=> $this->fixPostData($row['info_raw_signature']),
				'pp_about_me'		=> $this->fixPostData($row['info_raw_bio']),
				);

			//-----------------------------------------
			// Avatars
			//-----------------------------------------

			$path = '';
			// Gallery
			if ($row['info_avatar_type'] == 1)
			{
			  $profile['avatar_type'] = 'upload';
			  $profile['avatar_location'] = $row['info_avatar'];
			  $profile['avatar_size'] = $row['info_avatar_width'].'x'.$row['info_avatar_height'];
			  $path = $us['gal_path'];
			}
			// Uploaded
			elseif ($row['info_avatar_type'] == 2)
			{
				$profile['avatar_type'] = 'upload';
				$profile['avatar_location'] = $row['info_avatar'];
				$profile['avatar_size'] = $row['info_avatar_width'].'x'.$row['info_avatar_height'];
				$path = $us['pp_path'];
			}
			// URL
			elseif ($row['info_avatar_type'] == 3)
			{
				$profile['avatar_type'] = 'url';
				$profile['avatar_location'] = $row['info_avatar'];
				$profile['avatar_size'] = $row['info_avatar_width'].'x'.$row['info_avatar_height'];
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
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'forums',
						'order'		=> 'forum_id ASC',
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

			$save = array(
				'sub_can_post'	=> ($row['forum_is_cat']) ? 0 : 1,
				'status'		=> $row['forum_active'],
				'topics'		=> $row['forum_topics'],
				'posts'			=> $row['forum_postcount'],
				'position'		=> $row['forum_order'],
				'name'			=> $row['forum_title'],
				'description'	=> $row['forum_description'],
				'parent_id'		=> ($row['forum_parent_id']) ? ($row['forum_parent_id']) : -1,
				'redirect_on'	=> (int) (bool) $row['forum_link'],
				'redirect_url'	=> $row['forum_link'],
				'redirect_hits'	=> $row['forum_linkcount'],
				);

			$this->lib->convertForum($row['forum_id'], $save, $perms);

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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'topics',
						'order'		=> 'topic_id ASC',
					);

		$loop = $this->lib->load('topics', $main, array('tracker'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'forum_id'			=> $row['forum_id'],
				'starter_id'		=> $row['user_id'],
				'starter_name'		=> $row['topic_username'],
				'title'				=> $row['topic_subject'],
				'posts'				=> $row['topic_replies'],
				'views'				=> $row['topic_views'],
				'start_date'		=> $row['topic_first_post_time'],
				'pinned'			=> $row['topic_is_sticky'],
				'state'				=> $row['topic_is_closed'] ? 'closed' : 'open',
				'topic_hasattach'	=> $row['topic_files'],
				'approved'			=> $row['is_moderated'] ? 0 : 1,
				);

			$this->lib->convertTopic($row['topic_id'], $save);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'topic_subscriptions', 'where' => "topic_id={$row['topic_id']}"));
			ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch())
			{
				$savetracker = array(
					'member_id'	=> $tracker['user_id'],
					'topic_id'	=> $tracker['topic_id'],
					'topic_track_type' => ($tracker['subscription_type'] == 'instant') ? 'immediate' : $tracker['subscription_type'],
					);
				$this->lib->convertTopicSubscription($tracker['topic_id'].'-'.$tracker['user_id'], $savetracker);
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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'posts',
						'order'		=> 'post_id ASC',
					);

		$loop = $this->lib->load('posts', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			// We need to get some info
			$author = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'info_display_name', 'from' => 'user_info', 'where' => "user_id='{$row['user_id']}'" ) );

			$save = array(
				'append_edit'	 	=> $row['post_edited_by'] ? 1 : 0,
				'edit_time'			=> $row['post_edited'],
				'author_id'			=> $row['user_id'],
				'author_name'		=> $author['info_display_name'],
				'use_sig'			=> $row['post_include_sig'],
				'use_emo'			=> $row['post_disable_smilies'] ? 0 : 1,
				'ip_address'		=> $row['post_ip'],
				'post_date'			=> $row['post_posted'],
				'post'				=> $this->fixPostData($row['post_raw_body']),
				'queued'			=> $row['is_moderated'],
				'topic_id'			=> $row['topic_id'],
				'new_topic'			=> $row['post_is_topic'],
				'post_edit_reason'	=> $row['post_edited_reason'],
				);

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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'pt_topics',
						'where'		=> 'user_id>0',
						'order'		=> 'topic_id ASC',
					);

		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{

			//-----------------------------------------
			// Posts
			//-----------------------------------------

			$posts = array();
			ipsRegistry::DB('hb')->build( array( 'select' => '*', 'from' => 'pt_posts', 'where' => "topic_id='{$row['topic_id']}'" ) );
			ipsRegistry::DB('hb')->execute();
			while( $post =  ipsRegistry::DB('hb')->fetch() )
			{
				$posts[] = array(
					'msg_id'			=> $post['post_id'],
					'msg_topic_id'      => $row['topic_id'],
					'msg_date'          => $post['post_posted'],
					'msg_post'          => $this->fixPostData($post['post_raw_body']),
					'msg_post_key'      => md5(microtime()),
					'msg_author_id'     => $post['user_id'],
					'msg_ip_address'    => $post['post_ip'],
					'msg_is_first_post' => $post['post_is_topic'],
					);
			}

			//-----------------------------------------
			// Map Data
			//-----------------------------------------

			$maps = array();
			$_invited = array();
			
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'pt_participants', 'where' => "topic_id={$row['topic_id']}"));
			ipsRegistry::DB('hb')->execute();
			while ($map = ipsRegistry::DB('hb')->fetch())
			{
				$maps[] = array(
					'map_user_id'     => $map['user_id'],
					'map_topic_id'    => $row['topic_id'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => $row['private_last_read'],
				    'map_last_topic_reply' => $row['topic_last_post_time'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => $map['private_new'],
					'map_is_system'   => 0,
					'map_is_starter'  => ( $map['user_id'] == $row['user_id'] ) ? 1 : 0
					);

				$_invited[ $map['user_id'] ] = $map['user_id'];
			}

			//-----------------------------------------
			// Topic Data
			//-----------------------------------------

			$to = array_shift($_invited);

			$topic = array(
				'mt_id'			     => $row['topic_id'],
				'mt_date'		     => $row['topic_first_post_time'],
				'mt_title'		     => $row['topic_subject'],
				'mt_starter_id'	     => $row['user_id'],
				'mt_start_time'      => $row['topic_first_post_time'],
				'mt_last_post_time'  => $row['topic_last_post_time'],
				'mt_invited_members' => serialize( array_keys( $_invited ) ),
				'mt_to_count'		 => count(  array_keys( $_invited ) ),
				'mt_to_member_id'	 => $to,
				'mt_replies'		 => $row['topic_replies'],
				'mt_is_draft'		 => 0,
				'mt_is_deleted'		 => 0,
				'mt_is_system'		 => 0
				);

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
						'from' 		=> 'files',
						'order'		=> 'file_id ASC',
					);

		$loop = $this->lib->load('attachments', $main);

		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------

		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_fusionbb/fbbuploads):')), 'path');

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
			$save = array(
				'attach_ext'			=> $row['file_type'],
				'attach_file'			=> $row['file_name'],
				'attach_location'		=> $row['file_name'],
				'attach_is_image'		=> (int) in_array( $row['file_type'], array('gif', 'jpg', 'jpeg', 'png') ),
				'attach_hits'			=> $row['file_downloads'],
				'attach_date'			=> $row['file_created'],
				'attach_member_id'		=> $row['user_id'],
				'attach_filesize'		=> $row['file_size'],
				'attach_rel_id'			=> ($row['post_id']) ? $row['post_id'] : $row['pt_post_id'],
				'attach_rel_module'		=> ($row['post_id']) ? 'post' : 'msg',
				);

			$this->lib->convertAttachment($row['file_id'], $save, $path);

		}

		$this->lib->next();

	}

	/**
	 * Convert Bad Words
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_badwords()
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------

		$this->lib->saveMoreInfo('badwords', array('badwords_opt'));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'censorship',
					);

		$loop = $this->lib->load('badwords', $main);

		//-----------------------------------------
		// We need to know how to handle forum-specific ones
		//-----------------------------------------

		$this->lib->getMoreInfo(
			'badwords',
			$loop,
			array(
				'badwords_opt' => array(
					'type'		=> 'dropdown',
					'label'		=> 'How do you want to handle badwords that are only set to work in specific forums?',
					'options'	=> array(
						'global'	=> 'Become badwords everywhere',
						'skip'		=> 'Skip',
					)
				),
			),
			'path'
		);

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$opt = $us['badwords_opt'];

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			if($row['forum_id'] and $opt == 'skip')
			{
				continue;
			}
			$save = array(
				'type'		=> $row['censor_text'],
				'swop'		=> $row['censor_replace'] ? $row['censor_replace'] : '*******',
				'm_exact'	=> '1',
				);
			$this->lib->convertBadword($row['censor_text'], $save);
		}

		$this->lib->next();

	}

	/**
	 * Convert Ban Filters
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_banfilters()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(		'select' 	=> '*',
							'from' 		=> 'banlist',
							'order'		=> 'ban_id ASC',
						);

		$loop = $this->lib->load('banfilters', $main, array(), 'bad_emails');

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'ban_type'		=> 'ip',
				'ban_content'	=> $row['ip_addy'],
				'ban_date'		=> $row['time_stamp'],
				);
			$this->lib->convertBan($row['ban_id'], $save);
		}

		$this->lib->next();

	}

	/**
	 * Convert Disallowed Emails
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_bad_emails()
	{
		//---------------------------
		// Set up
		//---------------------------

		$mainBuild = array(	'select' 	=> '*',
							'from' 		=> 'bad_emails',
						);

		$this->start = intval($this->request['st']);
		$this->end = $this->start + intval($this->request['cycle']);

		$mainBuild['limit'] = array($this->start, $this->end);

		if ($this->start == 0)
		{
			// Truncate
			$this->DB->build(array('select' => 'ipb_id as id', 'from' => 'conv_link', 'where' => "type = 'fusionbb_bad_emails' AND duplicate = '0'"));
			$this->DB->execute();
			$ids = array();
			while ($row = $this->DB->fetch())
			{
				$ids[] = $row['id'];
			}
			$id_string = implode(",", $ids);

			if ($this->request['empty'])
			{
				$this->DB->delete('banfilters', "ban_type='email'");
			}
			elseif(count($ids))
			{
				$this->DB->delete('banfilters', "ban_type='email' AND ban_id IN ({$id_string})");
			}

			$this->DB->delete('conv_link', "type = 'fusionbb_bad_emails'");
		}

		$this->lib->errors = unserialize($this->settings['conv_error']);

		ipsRegistry::DB('hb')->build($mainBuild);
		ipsRegistry::DB('hb')->execute();

		if (!ipsRegistry::DB('hb')->getTotalRows())
		{
			$this->registry->output->redirect("{$this->settings['base_url']}app=convert&module={$this->lib->app['sw']}&section={$this->lib->app['app_key']}&do=bad_usernames&st=0&cycle={$this->request['cycle']}&total=".$this->countRows('bad_usernames'), "Continuing..." );
		}

		$i = 1;
		while ( $row = ipsRegistry::DB('hb')->fetch() )
		{
			$records[] = $row;
		}

		$loop = $records;

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'ban_type'		=> 'email',
				'ban_content'	=> $row['bad_addy'],
				);
			$this->lib->convertBan($row['bad_addy'], $save);
		}

		//-----------------------------------------
		// Next
		//-----------------------------------------

		$total = $this->request['total'];
		$pc = round((100 / $total) * $this->end);
		$message = ($pc > 100) ? 'Finishing...' : "{$pc}% complete";
		IPSLib::updateSettings(array('conv_error' => serialize($this->lib->errors)));
		$end = ($this->end > $total) ? $total : $this->end;
		$this->registry->output->redirect("{$this->settings['base_url']}app=convert&module={$this->lib->app['sw']}&section={$this->lib->app['app_key']}&do={$this->request['do']}&st={$this->end}&cycle={$this->request['cycle']}&total={$total}", "{$end} of {$total} converted<br />{$message}" );

	}

	/**
	 * Convert Disallowed usernames
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_bad_usernames()
	{
		//---------------------------
		// Set up
		//---------------------------

		$mainBuild = array(	'select' 	=> '*',
							'from' 		=> 'bad_usernames',
						);

		$this->start = intval($this->request['st']);
		$this->end = $this->start + intval($this->request['cycle']);

		$mainBuild['limit'] = array($this->start, $this->end);

		if ($this->start == 0)
		{
			// Truncate
			$this->DB->build(array('select' => 'ipb_id as id', 'from' => 'conv_link', 'where' => "type = 'fusionbb_bad_usernames' AND duplicate = '0'"));
			$this->DB->execute();
			$ids = array();
			while ($row = $this->DB->fetch())
			{
				$ids[] = $row['id'];
			}
			$id_string = implode(",", $ids);

			if ($this->request['empty'])
			{
				$this->DB->delete('banfilters', "ban_type='name'");
			}
			elseif(count($ids))
			{
				$this->DB->delete('banfilters', "ban_type='name' AND ban_id IN ({$id_string})");
			}

			$this->DB->delete('conv_link', "type = 'fusionbb_bad_usernames'");
		}

		$this->lib->errors = unserialize($this->settings['conv_error']);

		ipsRegistry::DB('hb')->build($mainBuild);
		ipsRegistry::DB('hb')->execute();

		if (!ipsRegistry::DB('hb')->getTotalRows())
		{
			$action = 'banfilters';
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

			// Errors?
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

			// Display
			$this->registry->output->html .= $this->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
			$this->sendOutput();
		}

		$i = 1;
		while ( $row = ipsRegistry::DB('hb')->fetch() )
		{
			$records[] = $row;
		}

		$loop = $records;

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'ban_type'		=> 'name',
				'ban_content'	=> $row['bad_name'],
				);
			$this->lib->convertBan($row['bad_name'], $save);
		}

		//-----------------------------------------
		// Next
		//-----------------------------------------

		$total = $this->request['total'];
		$pc = round((100 / $total) * $this->end);
		$message = ($pc > 100) ? 'Finishing...' : "{$pc}% complete";
		IPSLib::updateSettings(array('conv_error' => serialize($this->lib->errors)));
		$end = ($this->end > $total) ? $total : $this->end;
		$this->registry->output->redirect("{$this->settings['base_url']}app=convert&module={$this->lib->app['sw']}&section={$this->lib->app['app_key']}&do={$this->request['do']}&st={$this->end}&cycle={$this->request['cycle']}&total={$total}", "{$end} of {$total} converted<br />{$message}" );

	}

	/**
	 * Convert Custom BBCode
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_custom_bbcode()
	{

		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------

		$this->lib->saveMoreInfo('custom_bbcode', array('custom_bbcode_opt'));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'bbcode',
						'order'		=> 'bbcode_id ASC',
					);

		$loop = $this->lib->load('custom_bbcode', $main);

		//-----------------------------------------
		// We need to know what do do with duplicates
		//-----------------------------------------

		$this->lib->getMoreInfo('custom_bbcode', $loop, array('custom_bbcode_opt' => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate BBCodes?')));

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'bbcode_title'				=> $row['bbcode_title'],
				'bbcode_desc'				=> $row['bbcode_description'],
				'bbcode_tag'				=> $row['bbcode_tag'],
				'bbcode_replace'			=> str_replace('{param}', '{content}', $row['bbcode_replacement']),
				'bbcode_useoption'			=> $row['bbcode_option'],
				'bbcode_example'			=> $row['bbcode_example'],
				'bbcode_menu_option_text'	=> 'Enter option',
				'bbcode_menu_content_text'	=> 'Enter Paramater',
				'bbcode_groups'				=> 'all',
				'bbcode_sections'			=> 'all',
				'bbcode_parse'				=> 2,
				'bbcode_app'				=> 'core',
				);

			$this->lib->convertBBCode($row['bbcode_id'], $save, $us['custom_bbcode_opt']);
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

		$this->lib->saveMoreInfo('emoticons', array('emo_path', 'emo_opt'));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'smilies',
						'order'		=> 'smile_id ASC',
					);

		$loop = $this->lib->load('emoticons', $main);

		//-----------------------------------------
		// We need to know the path and how to handle duplicates
		//-----------------------------------------

		$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_fusionbb/images/smilies):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		IPSLib::updateSettings(array('conv_extra' => serialize($get)));
		$path = $us['emo_path'];

		//-----------------------------------------
		// Check all is well
		//-----------------------------------------

		if (!is_writable(DOC_IPS_ROOT_PATH.'public/style_emoticons/'))
		{
			$this->lib->error('Your IP.Board emoticons path is not writeable. '.DOC_IPS_ROOT_PATH.'public/style_emoticons/');
		}
		if (!is_readable($path))
		{
			$this->lib->error('Your remote emoticons path is not readable.');
		}

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'typed'		=> $row['smile_code'],
				'image'		=> $row['smile_image'],
				'clickable'	=> $row['smile_hiden'] ? 0 : 1,
				'emo_set'	=> 'default',
				);
			$done = $this->lib->convertEmoticon($row['smile_id'], $save, $us['emo_opt'], $path);
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

		$main = array(	'select' 	=> '*',
						'from'		=> 'forum_mods'
					);

		$loop = $this->lib->load('moderators', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{

			$member = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'info_display_name', 'from' => 'user_info', 'where' => 'user_id='.$row['user_id']));

			$save = array(
							   'forum_id'	  => $row['forum_id'],
							   'member_name'  => $member['info_display_name'],
							   'member_id'	  => $row['user_id']
						 );


			$this->lib->convertModerator($row['forum_id'].'-'.$row['user_id'], $save);
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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'buddies',
						'order'		=> 'user_id ASC',
					);

		$loop = $this->lib->load('profile_friends', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'friends_member_id'	=> $row['user_id'],
				'friends_friend_id'	=> $row['buddy_id'],
				'friends_approved'	=> $row['is_approved'],
				);
			$this->lib->convertFriend($row['user_id'].'-'.$row['buddy_id'], $save);
		}

		$this->lib->next();

	}

	/**
	 * Convert Ignored Users
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_ignored_users()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'ignores',
						'order'		=> 'user_id ASC',
					);

		$loop = $this->lib->load('ignored_users', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'ignore_owner_id'	=> $row['user_id'],
				'ignore_ignore_id'	=> $row['ignore_id'],
				'ignore_messages'	=> '1',
				'ignore_topics'		=> '1',
				);
			$this->lib->convertIgnore($row['user_id'].'-'.$row['ignore_id'], $save);
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
						'from' 		=> 'user_titles',
					);

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
			$save = array(
				'posts'		=> $row['titles_min'],
				'title'		=> $row['titles_name'],
				);
			$this->lib->convertRank($row['titles_min'].'-'.$row['titles_name'], $save, $us['rank_opt']);
		}

		$this->lib->next();

	}
}