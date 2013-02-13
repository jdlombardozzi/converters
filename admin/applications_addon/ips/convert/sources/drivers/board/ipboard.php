<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * IP.Board Merge Tool
 * Last Update: $Date: 2012-04-14 22:49:14 +0100 (Sat, 14 Apr 2012) $
 * Last Updated By: $Author: ips_terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 633 $
 */
$info = array( 'key'	=> 'ipboard',
			   'name'	=> 'IP.Board 3.2.x',
			   'login'	=> false );

class admin_convert_board_ipboard extends ipsCommand
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
			'custom_bbcode' 			=> array(),
			'pfields'					=> array(),
			'rc_status'					=> array(),
			'rc_status_sev'				=> array('rc_status'),
			'forum_perms'				=> array(),
			'groups' 					=> array('forum_perms'),
			'members'					=> array('groups', 'custom_bbcode', 'pfields', 'rc_status_sev'),
			'dnames_change' 			=> array('members'),
			'profile_comments' 			=> array('members'),
			'profile_comment_replies'	=> array ( 'members', 'profile_comments' ),
			'profile_friends' 			=> array('members'),
			'profile_ratings' 			=> array('members'),
			'ignored_users'				=> array('members'),
			'forums'					=> array('forum_perms', 'members'),
			'moderators'				=> array('groups', 'members', 'forums'),
			'topics'					=> array('members', 'forums'),
			'topic_ratings' 			=> array('topics', 'members'),
			'posts'						=> array('members', 'topics', 'custom_bbcode', 'rc_status_sev'),
			'reputation_index' 			=> array('members', 'posts'),
			'polls'						=> array('topics', 'members', 'forums'),
			'announcements'				=> array('forums', 'members', 'custom_bbcode'),
			'pms'						=> array('members', 'custom_bbcode', 'rc_status_sev'),
			'ranks'						=> array(),
			'attachments_type'			=> array(),
			'attachments'				=> array('attachments_type', 'posts', 'pms'),
			'emoticons'					=> array(),
			'badwords'					=> array(),
			'banfilters'				=> array(),
			'rss_import'				=> array('forums', 'members', 'topics'),
			'topic_mmod'				=> array('forums'),
			'warn_logs'					=> array('members'),
			);

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------

		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'IP.Board Merge Tool' );

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
			case 'pms':
				return $this->lib->countRows('message_topics');
				break;

			case 'ranks':
				return $this->lib->countRows('titles');
				break;

			case 'pfields':
				return $this->lib->countRows('pfields_data');
				break;

			case 'attachments':
				return $this->lib->countRows('attachments', "attach_rel_module='post' OR attach_rel_module='msg'");
				break;
			
			case 'profile_comments':
				return $this->lib->countRows ( 'member_status_updates' );
			break;
			
			case 'profile_comment_replies':
				return $this->lib->countRows ( 'member_status_replies' );
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
			case 'attachments':
			case 'emoticons':
			case 'custom_bbcode':
			case 'rc_status':
			case 'rc_status_sev':
			case 'rss_import':
				return true;
				break;

			default:
				return false;
				break;
		}
	}

	/**
	 * Convert Members
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_members()
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$this->lib->saveMoreInfo('members', array('pp_path'));

		//---------------------------
		// Set up
		//---------------------------
		$main = array(
						'select'	=> '*',
						'from'	=> 'members',
					  	'order'  => 'member_id ASC' );

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Prepare for reports conversion
		//-----------------------------------------
		$this->lib->prepareReports('member');

		//-----------------------------------------
		// We need to know how to the uploaded avatar / profile path
		//-----------------------------------------
		$this->lib->getMoreInfo('members', $loop, array('pp_path' => array('type' => 'text', 'label' => 'Path to uploads folder (no trailing slash): ')), 'path');

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$profilePortal = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'profile_portal', 'where' => "pp_member_id='{$row['member_id']}'" ) );
			$pfieldsContent = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'pfields_content', 'where' => "member_id='{$row['member_id']}'" ) );
			$rcModpref = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_modpref', 'where' => "mem_id='{$row['member_id']}'" ) );

			// Set basic info
			$info = array( 'id'               => $row['member_id'],
						   'username'     	  => $row['name'],
						   'displayname'	  => $row['members_display_name'],
						   'email'			  => $row['email'],
						   'group'			  => $row['member_group_id'],
						   'secondary_groups' => $row['mgroup_others'],
						   'joined'		      => $row['joined'],
						   'pass_salt'		  => $row['members_pass_salt'],
						   'pass_hash'		  => $row['members_pass_hash'] );

			// Filter data
			foreach (array_keys($row) as $key)
			{
				if ( !in_array($key, array('name', 'member_group_id', 'email', 'joined', 'ip_address', 'posts', 'title', 'allow_admin_mails', 'time_offset', 'hide_email', 'email_pm', 'last_post', 'view_sigs', 'view_avs', 'bday_day', 'bday_month', 'bday_year', 'msg_count_new', 'msg_count_total', 'msg_count_reset', 'msg_show_notification', 'misc', 'last_visit', 'last_activity', 'dst_in_use', 'auto_track', 'members_editor_choice', 'members_auto_dst', 'members_display_name', 'members_seo_name', 'members_created_remote', 'members_disable_pm', 'members_l_display_name', 'members_l_username', 'members_profile_views')) )
				{
					unset($row[$key]);
				}
			}

			// And send it to the lib
			$this->lib->convertMember($info, $row, $profilePortal, $pfieldsContent, $us['pp_path'], $us['pp_path']);

			//-----------------------------------------
			// Report Center
			//-----------------------------------------

			// Is this user a report center mod?
			if ($rcModpref['mem_id'])
			{
				$rcModpref['mem_id'] = $this->lib->getLink($rcModpref['mem_id'], 'members');
				$this->DB->insert( 'rc_modpref', $rcModpref );
			}

			// Or is he a naughty boy?
			$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='profiles'" ) );
			$rs = array( 'select' 	=> '*',
						 'from' 		=> 'rc_reports_index',
						 'order'		=> 'id ASC',
						 'where'		=> "exdat1='{$row['member_id']}' AND rc_class='{$rc['com_id']}'" );

			ipsRegistry::DB('hb')->build($rs);
			$rsRes = ipsRegistry::DB('hb')->execute();

			while ($report = ipsRegistry::DB('hb')->fetch($rsRes))
			{
				$rs = array( 'select' 	=> '*',
							 'from' 		=> 'rc_reports',
							 'order'		=> 'id ASC',
							 'where'		=> 'rid='.$report['id'] );

				ipsRegistry::DB('hb')->build($rs);
				$rsInnerRes = ipsRegistry::DB('hb')->execute();
				$reports = array();
				while ($r = ipsRegistry::DB('hb')->fetch($rsInnerRes))
				{
					$reports[] = $r;
				}
				$this->lib->convertReport('member', $report, $reports);
			}
		}

		$this->lib->next();
	}


	/**
	 * Convert Groups
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
						'order'		=> 'g_id ASC',
					);

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot'	=> 'Old group',	'nt'	=> 'New group'), '', array('idf' => 'g_id', 'nf' => 'g_title'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$save = array(
					'g_title'				=> $row['g_title'],
					'g_max_messages'		=> $row['g_max_messages'],
					'g_max_mass_pm'			=> $row['g_max_mass_pm'],
					'prefix'				=> $row['prefix'],
					'suffix'				=> $row['suffix'],
					'g_view_board'			=> $row['g_view_board'],
					'g_mem_info'			=> $row['g_mem_info'],
					'g_other_topics'		=> $row['g_other_topics'],
					'g_use_search'			=> $row['g_use_search'],
					'g_email_friend'		=> $row['g_email_friend'],
					'g_invite_friend'		=> $row['g_invite_friend'],
					'g_edit_profile'		=> $row['g_edit_profile'],
					'g_post_new_topics'		=> $row['g_post_new_topics'],
					'g_reply_own_topics'	=> $row['g_reply_own_topics'],
					'g_reply_other_topics'	=> $row['g_reply_other_topics'],
					'g_edit_posts'			=> $row['g_edit_posts'],
					'g_delete_own_posts'	=> $row['g_delete_own_posts'],
					'g_open_close_posts'	=> $row['g_open_close_posts'],
					'g_delete_own_topics'	=> $row['g_delete_own_topics'],
					'g_post_polls'		 	=> $row['g_post_polls'],
					'g_vote_polls'		 	=> $row['g_vote_polls'],
					'g_use_pm'			 => $row['g_use_pm'],
					'g_is_supmod'		 	=> $row['g_is_supmod'],
					'g_access_cp'		 	=> $row['g_access_cp'],
					'g_access_offline'	 	=> $row['g_access_offline'],
					'g_avoid_q'			 	=> $row['g_avoid_q'],
					'g_avoid_flood'		 	=> $row['g_avoid_flood'],
					'g_perm_id'				=> $row['g_perm_id'],
			);
					
			$this->lib->convertGroup($row['g_id'], $save);
		}

		$this->lib->next();

	}

	/**
	 * Convert Permission Masks
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
						'from' 		=> 'forum_perms',
						'order'		=> 'perm_id ASC',
					);

		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'perm_id', 'nf' => 'perm_name'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['perm_id'], $row['perm_name']);
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

		$main = array(	'select' 	=> 'f.*',
						'from' 		=> array('forums' => 'f'),
						'order'		=> 'id ASC',
						'add_join'	=> array(
										array( 	'select' => 'p.*',
												'from'   =>	array( 'permission_index' => 'p' ),
												'where'  => "p.perm_type='forum' AND p.perm_type_id=f.id",
												'type'   => 'left'
											),
										)
					);

		$loop = $this->lib->load('forums', $main, array('forum_tracker', 'rss_export'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Handle permissions
			//-----------------------------------------

			$perms = array();
			$perms['view']		= $row['perm_view'];
			$perms['read']		= $row['perm_2'];
			$perms['reply']		= $row['perm_3'];
			$perms['start']		= $row['perm_4'];
			$perms['upload']	= $row['perm_5'];
			$perms['download']	= $row['perm_6'];

			//-----------------------------------------
			// And go
			//-----------------------------------------
			$save = array('topics'			=> $row['topics'],
					'posts'			  	=> $row['posts'],
					'last_post'		  	=> $row['last_post'],
					'last_poster_name'	=> $row['last_poster_name'],
					'parent_id'		  	=> $row['parent_id'],
					'name'			  	=> $row['name'],
					'description'	  	=> $row['description'],
					'position'		  	=> $row['position'],
					'use_ibc'		  	=> $row['use_ibc'],
					'use_html'		  	=> $row['use_html'],
					'status'			=> $row['status'],
					'inc_postcount'	  	=> $row['inc_postcount'],
					'password'		  	=> $row['password'],
					'sub_can_post'		=> $row['sub_can_post'],
					'redirect_on'		=> $row['redirect_on'],
					'redirect_url'		=> $row['redirect_url'],
					'preview_posts'		=> $row['preview_posts'],
					'forum_allow_rating'=> $row['forum_allow_rating'],
					);

			$this->lib->convertForum($row['id'], $save, $perms);
			
			//-----------------------------------------
			// RSS Exports?
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'rss_export', 'where' => "rss_export_forums LIKE '%{$row['id']}%'"));
			ipsRegistry::DB('hb')->execute();
			while ($export = ipsRegistry::DB('hb')->fetch())
			{
				if ( !$this->lib->getLink($export['rss_export_id'], 'rss_export') and in_array( $row['id'],	explode( ',', $export['rss_export_forums'] ) ) )
				{
					$this->lib->convertRSSExport($export['rss_export_id'], $export);
				}
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
	private function convert_moderators()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'moderators',
						'order'		=> 'mid ASC',
					);

		$loop = $this->lib->load('moderators', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertModerator($row['mid'], $row);
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
						'order'		=> 'tid ASC',
					);

		$loop = $this->lib->load('topics', $main, array('tracker'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'title'			  => $row['title'],
							   'state'			  => $row['state'],
							   'posts'			  => $row['posts'],
							   'starter_id'		  => $row['starter_id'],
							   'starter_name'	  => $row['starter_name'],
							   'start_date'		  => $row['start_date'],
							   'last_post'		  => $row['last_post'],
							   'last_poster_name' => $row['last_poster_name'],
							   'poll_state'		  => $row['poll_state'],
							   'views'			  => $row['views'],
							   'forum_id'		  => $row['forum_id'],
							   'approved'		  => $row['approved'],
							   'pinned'			  => $row['pinned'],
							   'topic_hasattach'  => $row['topic_hasattach'] );
							   
			$this->lib->convertTopic($row['tid'], $save);
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

		$main = array(	'select' 	=> 'p.*',
						'from' 		=> array('posts' => 'p'),
						'order'		=> 'p.pid ASC',
						'add_join'	=> array(
										array( 	'select' => 'r.rep_points',
												'from'   =>	array( 'reputation_cache' => 'r' ),
												'where'  => "r.app='forums' AND r.type='pid' AND r.type_id=p.pid",
												'type'   => 'left'
											),
										),
					);

		$loop = $this->lib->load('posts', $main, array('reputation_cache'));

		//-----------------------------------------
		// Prepare for reports conversion
		//-----------------------------------------

		$this->lib->prepareReports('posts');

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertPost($row['pid'], $row);

			//-----------------------------------------
			// Report Center
			//-----------------------------------------

			$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='post'" ) );
			$rs = array(	'select' 	=> '*',
							'from' 		=> 'rc_reports_index',
							'order'		=> 'id ASC',
							'where'		=> 'exdat3='.$row['pid']." AND rc_class='{$rc['com_id']}'"
						);

			ipsRegistry::DB('hb')->build($rs);
			ipsRegistry::DB('hb')->execute();
			while ($report = ipsRegistry::DB('hb')->fetch())
			{
				$rs = array(	'select' 	=> '*',
								'from' 		=> 'rc_reports',
								'order'		=> 'id ASC',
								'where'		=> 'rid='.$report['id']
							);

				ipsRegistry::DB('hb')->build($rs);
				ipsRegistry::DB('hb')->execute();
				$reports = array();
				while ($r = ipsRegistry::DB('hb')->fetch())
				{
					$reports[] = $r;
				}
				$this->lib->convertReport('post', $report, $reports);
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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'polls',
						'order'		=> 'pid ASC',
					);

		$loop = $this->lib->load('polls', $main, array('voters'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// We need to do voters...
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'voters', 'where' => "tid={$row['tid']}"));
			ipsRegistry::DB('hb')->execute();
			while ($voter = ipsRegistry::DB('hb')->fetch())
			{
				$this->lib->convertPollVoter($voter['vid'], $voter);
			}

			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------

			$this->lib->convertPoll($row['pid'], $row);
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
						'from' 		=> 'message_topics',
						'order'		=> 'mt_id ASC',
					);

		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));

		//-----------------------------------------
		// Prepare for reports conversion
		//-----------------------------------------

		$this->lib->prepareReports('pm');

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{

			//-----------------------------------------
			// Load the posts
			//-----------------------------------------

			$posts = array();
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'message_posts', 'where' => "msg_topic_id={$row['mt_id']}", 'order' => 'msg_date ASC'));
			ipsRegistry::DB('hb')->execute();
			while ($post = ipsRegistry::DB('hb')->fetch())
			{
				$post['msg_post'] = $this->fixPostData($post['msg_post']);
				$posts[] = $post;
			}

			//-----------------------------------------
			// And the maps
			//-----------------------------------------

			$maps = array();
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'message_topic_user_map', 'where' => "map_topic_id={$row['mt_id']}"));
			ipsRegistry::DB('hb')->execute();
			while ($map = ipsRegistry::DB('hb')->fetch())
			{
				$maps[] = $map;
			}

			//-----------------------------------------
			// And send it through
			//-----------------------------------------

			$this->lib->convertPM($row, $posts, $maps);


			//-----------------------------------------
			// Report Center
			//-----------------------------------------

			foreach ($posts as $post)
			{
				$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='messages'" ) );
				$rs = array(	'select' 	=> '*',
								'from' 		=> 'rc_reports_index',
								'order'		=> 'id ASC',
								'where'		=> 'exdat2='.$post['msg_id']." AND rc_class='{$rc['com_id']}'"
							);

				ipsRegistry::DB('hb')->build($rs);
				ipsRegistry::DB('hb')->execute();
				while ($report = ipsRegistry::DB('hb')->fetch())
				{
					$rs = array(	'select' 	=> '*',
									'from' 		=> 'rc_reports',
									'order'		=> 'id ASC',
									'where'		=> 'rid='.$report['id']
								);

					ipsRegistry::DB('hb')->build($rs);
					ipsRegistry::DB('hb')->execute();
					$reports = array();
					while ($r = ipsRegistry::DB('hb')->fetch())
					{
						$reports[] = $r;
					}
					$this->lib->convertReport('pm', $report, $reports);
				}
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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'titles',
						'order'		=> 'id ASC',
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
			$this->lib->convertRank($row['id'], $row, $us['rank_opt']);
		}

		$this->lib->next();

	}

	/**
	 * Convert Mime Types
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_attachments_type()
	{

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'attachments_type',
						'order'		=> 'atype_id ASC',
					);

		$loop = $this->lib->load('attachments_type', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertAttachType($row['atype_id'], $row);
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
						'from' 		=> 'attachments',
						'where'		=> "attach_rel_module='post' OR attach_rel_module='msg'",
						'order'		=> 'attach_id ASC',
					);

		$loop = $this->lib->load('attachments', $main);

		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------

		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_ipboard/uploads):')), 'path');

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
			$done = $this->lib->convertAttachment($row['attach_id'], $row, $path);

			// Fix inline attachments
			if ($done === true)
			{
				$aid = $this->lib->getLink($row['attach_id'], 'attachments');

				switch ($row['attach_rel_module'])
				{
					case 'post':
						$field = 'post';
						$table = 'posts';
						$pid = $this->lib->getLink($row['attach_rel_id'], 'posts');
						$where = "pid={$pid}";
						break;

					case 'msg':
						$field = 'msg_post';
						$table = 'message_posts';
						$pid = $this->lib->getLink($row['attach_rel_id'], 'pm_posts');
						$where = "msg_id={$pid}";
						break;

					default:
						continue;
						break;
				}

				if ( $pid )
				{
					$attachrow = $this->DB->buildAndFetch( array( 'select' => $field, 'from' => $table, 'where' => $where ) );
					$save = preg_replace("#(\[attachment=)({$row['attach_id']}+?)\:([^\]]+?)\]#ie", "'$1'. $aid .':$3]'", $attachrow[$field]);
					$this->DB->update($table, array($field => $save), $where);
				}
			}

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
						'from' 		=> 'emoticons',
						'order'		=> 'id ASC',
					);

		$loop = $this->lib->load('emoticons', $main);

		//-----------------------------------------
		// We need to know the path and how to handle duplicates
		//-----------------------------------------

		$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_ipboard/style_emoticons):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
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
			$done = $this->lib->convertEmoticon($row['id'], $row, $us['emo_opt'], $path.'/'.$row['emo_set']);
		}

		$this->lib->next();

	}

	/**
	 * Convert Announcements
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_announcements()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'announcements',
						'order'		=> 'announce_id ASC',
					);

		$loop = $this->lib->load('announcements', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$row['announce_post'] = $this->fixPostData($row['announce_post']);
			$this->lib->convertAnnouncement($row['announce_id'], $row);
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
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'badwords',
						'order'		=> 'wid ASC',
					);

		$loop = $this->lib->load('badwords', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertBadword($row['wid'], $row);
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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'banfilters',
						'order'		=> 'ban_id ASC',
					);

		$loop = $this->lib->load('banfilters', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertBan($row['ban_id'], $row);
		}

		$this->lib->next();

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
						'from' 		=> 'custom_bbcode',
						'order'		=> 'bbcode_id ASC',
					);

		$loop = $this->lib->load('custom_bbcode', $main, array('bbcode_media'));

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
			$this->lib->convertBBCode($row['bbcode_id'], $row, $us['custom_bbcode_opt']);

			// We need to do special stuff for [media]
			if ($row['bbcode_tag'] == 'media')
			{
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'bbcode_mediatag'));
				ipsRegistry::DB('hb')->execute();
				while ($media = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertMediaTag($row['bbcode_id'], $media, $us['custom_bbcode_opt']);
				}
			}
		}

		$this->lib->next();

	}

	/**
	 * Convert Display Name history
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_dnames_change()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'dnames_change',
						'order'		=> 'dname_id ASC',
					);

		$loop = $this->lib->load('dnames_change', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertDname($row['dname_id'], $row);
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
						'from' 		=> 'ignored_users',
						'order'		=> 'ignore_id ASC',
					);

		$loop = $this->lib->load('ignored_users', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertIgnore($row['ignore_id'], $row);
		}

		$this->lib->next();

	}

	/**
	 * Convert custom profile fields
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_pfields()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'pfields_data',
						'order'		=> 'pf_id ASC',
					);

		$loop = $this->lib->load('pfields', $main, array('pfields_groups'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			// Do we have a group?
			if ($this->lib->getLink($row['pf_group_id'], 'pfields_groups'))
			{
				$group = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'pfields_groups', 'where' => "pf_group_id = '{$row['pf_group_id']}'" ) );
 				$this->lib->convertPFieldGroup($group['pf_group_id'], $group);
			}

			// Carry on...
			$this->lib->convertPField($row['pf_id'], $row);
		}

		$this->lib->next();

	}

	/**
	 * Convert profile comments
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_profile_comments()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'member_status_updates',
						'order'		=> 'status_id ASC',
					);

		$loop = $this->lib->load('profile_comments', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertProfileComment($row['status_id'], $row);
		}

		$this->lib->next();

	}

	/** Convert profile comment replies
	 * 
	 * @access private
	 * @return void
	 */
	private function convert_profile_comment_replies ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'member_status_replies',
			'order'		=> 'reply_id ASC',
		);
		
		$loop = $this->lib->load ( 'profile_comment_replies', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$this->lib->convertProfileCommentReply ( $row['reply_id'], $row );
		}
		
		$this->lib->next ( );
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
						'from' 		=> 'profile_friends',
						'order'		=> 'friends_id ASC',
					);

		$loop = $this->lib->load('profile_friends', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertFriend($row['friends_id'], $row);
		}

		$this->lib->next();

	}

	/**
	 * Convert profile ratings
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_profile_ratings()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'profile_ratings',
						'order'		=> 'rating_id ASC',
					);

		$loop = $this->lib->load('profile_ratings', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertProfileRating($row['rating_id'], $row);
		}

		$this->lib->next();

	}

	/**
	 * Convert Report Center Statuses
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_rc_status()
	{

		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------

		$this->lib->saveMoreInfo('rc_status', array('rc_status_opt'));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'rc_status',
						'order'		=> 'status ASC',
					);

		$loop = $this->lib->load('rc_status', $main);

		//-----------------------------------------
		// We need to know what do do with duplicates
		//-----------------------------------------

		$this->lib->getMoreInfo('rc_status', $loop, array('rc_status_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate statuses?')));

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertRCStatus($row['status'], $row, $us['rc_status_opt']);
		}

		$this->lib->next();

	}

	/**
	 * Convert Report Center Severities
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_rc_status_sev()
	{

		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------

		$this->lib->saveMoreInfo('rc_status_sev', array('rc_status_sev_opt'));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'rc_status_sev',
						'order'		=> 'status ASC',
					);

		$loop = $this->lib->load('rc_status_sev', $main);

		//-----------------------------------------
		// We need to know what do do with duplicates
		//-----------------------------------------

		$this->lib->getMoreInfo('rc_status_sev', $loop, array('rc_status_sev_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate severities?')));

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertRCSeverity($row['id'], $row, $us['rc_status_sev_opt']);
		}

		$this->lib->next();

	}

	/**
	 * Convert Post Reputations
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_reputation_index()
	{

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'reputation_index',
						'order'		=> 'id ASC',
					);

		$loop = $this->lib->load('reputation_index', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertRep($row['id'], $row);
		}

		$this->lib->next();

	}

	/**
	 * Convert RSS Imports
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_rss_import()
	{

		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------

		$this->lib->saveMoreInfo('rss_import', array('rss_import_opt'));

		//-----------------------------------------
		// We need to get rid of unwanted import logs
		// I know, this is really hacky but if they don't use keys in the tables...
		//-----------------------------------------

		if (!$this->request['st'])
		{
			$this->DB->build(array('select' => 'ipb_id as id', 'from' => 'conv_link', 'where' => "type = 'rss_import'"));
			$this->DB->execute();
			$ids = array();
			while ($row = $this->DB->fetch())
			{
				$ids[] = $row['id'];
			}
			if (!empty($id_string))
			{
				$id_string = implode(",", $rids);
				$this->DB->delete('rss_imported', "rss_imported_impid IN({$id_string})");
			}
		}

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'rss_import',
						'order'		=> 'rss_import_id ASC',
					);

		$loop = $this->lib->load('rss_import', $main);

		//-----------------------------------------
		// We need to know what do do with duplicates
		//-----------------------------------------

		$this->lib->getMoreInfo('rss_import', $loop, array('rss_import_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate imports?')));

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			if ($this->lib->convertRSSImport($row['rss_import_id'], $row, $us['rss_import_opt']))
			{
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'rss_imported', 'where' => "rss_imported_impid='{$row['rss_import_id']}'"));
				ipsRegistry::DB('hb')->execute();
				while ($log = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertRSSImportLog($row['rss_import_id'], $log);
				}
			}
		}

		$this->lib->next();

	}

	/**
	 * Convert Multi-Moderation
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_topic_mmod()
	{

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'topic_mmod',
						'order'		=> 'mm_id ASC',
					);

		$loop = $this->lib->load('topic_mmod', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$row['topic_reply_content'] = ($row['topic_reply_content']) ? $this->fixPostData($row['topic_reply_content']) : '';
			$this->lib->convertMultiMod($row['mm_id'], $row);
		}

		$this->lib->next();

	}

	/**
	 * Convert topic ratings
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_topic_ratings()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'topic_ratings',
						'order'		=> 'rating_id ASC',
					);

		$loop = $this->lib->load('topic_ratings', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertTopicRating($row['rating_id'], $row);
		}

		$this->lib->next();

	}

	/**
	 * Convert warn logs
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_warn_logs()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'warn_logs',
						'order'		=> 'wlog_id ASC',
					);

		$loop = $this->lib->load('warn_logs', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{

			//-----------------------------------------
			// Process info
			//-----------------------------------------

			$notes = unserialize($row['wlog_notes']);
			$notes['content'] = $this->fixPostData($row['wlog_notes']);
			$row['wlog_notes'] = serialize($notes);

			$row['wlog_contact_content'] = preg_replace('/<content>(.+)<\/content>/', $this->fixPostData('$1'), $row['wlog_contact_content'] );

			//-----------------------------------------
			// Pass it on
			//-----------------------------------------

			$this->lib->convertWarn($row['wlog_id'], $row);
		}

		$this->lib->next();

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

}

