<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * Woltlab Burning Board
 * Last Update: $Date: 2009-11-25 16:43:59 +0100(mer, 25 nov 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 391 $
 */

$info = array(
	'key'	=> 'wowbb',
	'name'	=> 'WowBB 1.7',
	'login'	=> false );

class admin_convert_board_wowbb extends ipsCommand
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
			//'emoticons'		=> array(),
			'pfields'		=> array(),
			'forum_perms'	=> array(),
			'groups' 		=> array('forum_perms'),
			'members'		=> array('groups', 'pfields'),
			//'profile_friends' => array('members'),
			//'ignored_users'	=> array('members'),
			'forums'		=> array('forum_perms', 'members'),
			'moderators'	=> array('groups', 'members', 'forums'),
			'topics'		=> array('members', 'forums'),
			//'topic_ratings' => array('topics', 'members'),
			'posts'			=> array('members', 'topics'),
			'polls'			=> array('members', 'topics', 'posts'),
			'pms'			=> array('members'),
			'attachments'	=> array('posts', 'pms'),
			'ranks' => array()
			);

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------

		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'WowBB &rarr; IP.Board Converter' );

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
			case 'attachments':
				return  $this->lib->countRows('attachments');
				break;

			case 'forums':
				return  $this->lib->countRows('forums') + $this->lib->countRows('categories');
				break;

			case 'forum_perms':
			case 'groups':
				return  $this->lib->countRows('user_groups');
				break;

			case 'members':
				return  $this->lib->countRows('users');
				break;

			case 'moderators':
				return  $this->lib->countRows('moderators');
				break;

			case 'pfields':
				return  $this->lib->countRows('profile_fields');
				break;

			case 'pms':
				return  $this->lib->countRows('pm', 'user_id != pm_from');
				break;

			case 'polls':
				return  $this->lib->countRows('topics', 'poll_id > 0');
				break;

			case 'posts':
				return  $this->lib->countRows('posts');
				break;

			case 'ranks':
				return  $this->lib->countRows('ratings');
				break;

			case 'topics':
				return  $this->lib->countRows('topics');
				break;








			/*
			case 'emoticons':
				return  $this->lib->countRows('smiley');
				break;



			case 'profile_friends':
				return  $this->lib->countRows('user_whitelist');
				break;

			case 'ignored_users':
				return  $this->lib->countRows('user_blacklist');
				break;

			case 'topic_ratings':
				return  $this->lib->countRows('thread_rating');
				break;

			default:
				return $this->lib->countRows($action);
				break;*/
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
		$post = str_replace("<", "&lt;", $post);
		$post = str_replace(">", "&gt;", $post);

		// Sort out newlines
		$post = nl2br($post);

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
						'from' 		=> 'user_groups',
						'order'		=> 'user_group_id ASC',
					);

		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'user_group_id', 'nf' => 'user_group_title'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['user_group_id'], $row['user_group_title']);
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
						'from' 		=> 'user_groups',
						'order'		=> 'user_group_id ASC',
					);

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'user_group_id', 'nf' => 'user_group_title'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$save = array(
				'g_title'			=> $row['user_group_title'],
				'g_perm_id'			=> $row['user_group_id'],
				);
			$this->lib->convertGroup($row['user_group_id'], $save);
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
		// Setup
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'profile_fields',
						'order'		=> 'field_id ASC',
					);

		$loop = $this->lib->load('pfields', $main, array('pfields_groups'));

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		if (!$this->request['st'])
		{
			$us['pfield_group'] = null;
			IPSLib::updateSettings(array('conv_extra' => serialize($us)));
		}

		//-----------------------------------------
		// Do we have a group
		//-----------------------------------------
		if (!$us['pfield_group'])
		{
			$group = $this->lib->convertPFieldGroup(1, array('pf_group_name' => 'Converted', 'pf_group_key' => 'wowbb'), true);
			if (!$group)
			{
				$this->lib->error('There was a problem creating the profile field group');
			}
			$us['pfield_group'] = $group;
			$get[$this->lib->app['name']] = $us;
			IPSLib::updateSettings(array('conv_extra' => serialize($get)));
		}

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			// What kind of field is this?
			$type;
			switch( $row['field_type'] ) {
				case 'm':	$type = 'textarea';		break;
				case 's':
				default: $type = 'input';
			}

			// Insert
			$save = array(
				'pf_title'			=> $row['field_name'],
				'pf_desc'			=> $row['field_description'],
				'pf_content'		=> '',
				'pf_type'			=> $type,
				'pf_not_null'		=> $row['field_required'],
				'pf_member_hide'	=> $row['field_private'],
				//'pf_max_input'		=> $row['maxlength'],
				'pf_member_edit'	=> 1,
				'pf_position'		=> $row['field_order'],
				'pf_show_on_reg'	=> $row['field_registration'],
				'pf_group_id'		=> 1,
				'pf_key'			=> $row['field_id'],
				);

			$this->lib->convertPField($row['field_id'], $save);
		}

		// Save pfield_data
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_extra' => serialize($get)));

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
			//'gender'			=> 'Gender',
			//'user_from'			=> 'Location',
			'user_occupation'		=> 'Occupation',
			'user_interests'			=> 'Interests',
			//'adminComment'		=> 'Admin Comment',
			'user_homepage'			=> 'Website URL',
			'user_icq'				=> 'ICQ',
			'user_aim'				=> 'AIM',
			'user_ym'				=> 'YIM',
			'user_msnm'				=> 'MSN',
			//'skype'				=> 'Skype',
			);

		$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array( 'pp_path' )));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> 'u.*',
						'from' 		=> array('users' => 'u'),
						'order'		=> 'u.user_id ASC',
					);

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		$ask['pp_path'] = array('type' => 'text', 'label' => 'The path to the folder/directory containing your WowBB installation.');

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
			$ask[$id] = array('type' => 'dropdown', 'label' => 'Custom profile field to store '.$name.': ', 'options' => $options);
		}

		$this->lib->getMoreInfo('members', $loop, $ask, 'path');

		//-----------------------------------------
		// Get our custom profile fields
		//-----------------------------------------

		if (isset($us['pfield_group']))
		{
			$this->DB->build(array('select' => '*', 'from' => 'pfields_data', 'where' => 'pf_group_id='.$us['pfield_group']));
			$this->DB->execute();
			$pfields = array();
			while ($row = $this->DB->fetch())
			{
				$pfields[] = $row;
			}
		}
		else
		{
			$pfields = array();
		}

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
				'id'				=> $row['user_id'],
				'username'			=> $row['user_name'],
				'email'				=> $row['user_email'],
				'md5pass'			=> $row['user_password'],
				'joined'			=> strtotime($row['user_joined']),
				//'secondary_groups'	=> implode(',', $sgroups),
				'group'				=> $row['user_group_id'],
				);

			$time_arry = explode( ".", $row['user_timezone']);
			$time_offset  = $time_arry[0];
			// Birthday
			list($year, $month, $day) = explode("-", $row['user_birthday']);

			// Member info
			$members = array(
				//'misc'				=> $row['salt'],
				'ip_address'		=> '127.0.0.1',
				//'member_banned'		=> $row['banned'],
				//'title'				=> $title,
				//'last_activity'		=> $row['lastActivityTime'],
				//'last_visit'		=> $row['boardLastVisitTime'],
				'posts'				=> $row['user_posts'],
				'time_offset'		=> $time_offset,
//				'members_auto_dst'	=> $fields['enableDaylightSavingTime'],
				'view_sigs'			=> 1,
				'bday_day'			=> $day,
				'bday_month'		=> $month,
				'bday_year'			=> $year,
				'hide_email'		=> $row['user_view_email'] ? 0 : 1,
				'allow_admin_mails' => 0,
				//'members_disable_pm'=> ($fields['acceptPm']) ? 0 : 1,
				'email_pm'			=> $row['user_pm_notification'],
				);

			// Profile
			$profile = array(
				'signature'			=> $this->fixPostData($row['user_signature']),
				);

			//-----------------------------------------
			// Avatars
			//-----------------------------------------
			$path = $us['pp_path'];
			if($row['user_avatar'])
			{
				$splitPos = strrpos( $row['user_avatar'], '/');
				$path = $path . '/' . substr($row['user_avatar'], 0, $splitPos);
				$profile['avatar_location'] = substr($row['user_avatar'], $splitPos + 1);
				$profile['avatar_type'] = 'upload';
			}

			//-----------------------------------------
			// Custom Profile fields
			//-----------------------------------------

			// Pseudo
			foreach ($pcpf as $id => $name)
			{
				if ($us[$id] != 'x')
				{
					$custom['field_'.$us[$id]] = $row[$id];
				}
			}

			// Actual
			foreach ($pfields as $field)
			{
				$custom['field_'.$field['pf_id']] = $row[$field['pf_key']];
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
						'from' 		=> 'categories',
						'order'		=> 'category_id ASC' );

		$loop = $this->lib->load('forums', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'parent_id'		=> -1,
				'name'			=> $row['category_name'],
				'position'	   => $row['category_order'],
				'sub_can_post'	=> 0 );

			$this->lib->convertForum('C_'.$row['category_id'], $save, array());

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			/*
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'board_subscription', 'where' => "boardID={$row['boardID']}"));
			ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch())
			{
				$savetracker = array(
					'member_id'	=> $tracker['userID'],
					'forum_id'	=> $tracker['boardID'],
					'forum_track_type' => ($tracker['enableNotification']) ? 'delayed' : 'none',
					);
				$this->lib->convertForumSubscription($tracker['boardID'].'-'.$tracker['userID'], $savetracker);
			}*/
		}

		ipsRegistry::DB('hb')->build( array( 'select' 	=> '*',
											 'from' 		=> 'forums',
											 'order'		=> 'forum_id ASC' ) );
		$forumRes = ipsRegistry::DB('hb')->execute();

		while( $row = ipsRegistry::DB('hb')->fetch($forumRes) )
		{
			$save = array( 'parent_id'		=> 'C_'.$row['category_id'],
							'name'			=> $row['forum_name'],
							'description'	=> $row['forum_description'],
							'position'	   => $row['forum_order'],
							'sub_can_post'	=> 1,
							'topics'		=> $row['forum_topics'],
							'posts'			=> $row['forum_posts'],
							);
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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'topics',
						'order'		=> 'topic_id ASC',
					);

		$loop = $this->lib->load('topics', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'forum_id'			=> $row['forum_id'],
				'title'				=> $row['topic_name'],
				'description'       => $row['topic_description'],
				'start_date'		=> strtotime($row['topic_date_time']),
				'starter_id'		=> $row['topic_starter_id'],
				'starter_name'		=> $row['topic_starter_user_name'],
				'posts'				=> $row['topic_replies'],
				'views'				=> $row['topic_views'],
				//'topic_hasattach'	=> $row['attachments'],
				'pinned'			=> $row['topic_type'] == 0 ? 0 : 1,
				'state'				=> ($row['topic_status'] == 1) ? 'closed' : 'open',
				'approved'			=> 1,
				);

			$this->lib->convertTopic($row['topic_id'], $save);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'notifications', 'where' => "topic_id={$row['topic_id']}"));
			$subRes = ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch($subRes))
			{
				$savetracker = array(
					'member_id'	=> $tracker['user_id'],
					'topic_id'	=> $tracker['topic_id'],
					'topic_track_type' => ($tracker['notify']) ? 'delayed' : 'none',
					);
				$this->lib->convertTopicSubscription($tracker['notification_id'], $savetracker);
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
		$main = array( 'select' 	=> 'p.*',
					   'from' 	 	=> array( 'posts' => 'p' ),
					   'add_join' 	=> array( array( 'select'	=> 'pt.*',
					   								 'from' 		=> array( 'post_texts' => 'pt'),
					   								 'where'		=> 'p.post_id = pt.post_id',
					   								 'type'		=> 'left' ) ),
					   	'order' => 'p.post_id ASC' );

		$loop = $this->lib->load('posts', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'topic_id'			=> $row['topic_id'],
				'author_id'			=> $row['user_id'],
				'author_name'		=> $row['post_user_name'],
				'post'				=> $this->fixPostData($row['post_text']),
				'post_date'			=> strtotime($row['post_date_time']),
				'use_emo'			=> 1,
				'post_htmlstate'	=> 0,
				'use_sig'			=> 1,
				'ip_address'		=> $row['post_ip'] != '' ? $row['post_ip'] : '127.0.0.1' );

			$this->lib->convertPost($row['post_id'], $save);
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
		$main = array( 'select' => '*',
						'from'  => 'poll_questions',
						'order' => 'poll_id ASC' );

		$loop = $this->lib->load('polls', $main, array('voters'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$topic = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'topic_id, topic_starter_id, topic_date_time, forum_id', 'from' => 'topics', 'where' => "poll_id='{$row['poll_id']}'" ) );

			//-----------------------------------------
			// Options are stored in one place...
			//-----------------------------------------
			$choices = array();
			$votes = array();

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'poll_options', 'where' => "poll_id={$row['poll_id']}"));
			$optionRes = ipsRegistry::DB('hb')->execute();
			while ($options = ipsRegistry::DB('hb')->fetch($optionRes))
			{
				$choices[ $options['poll_option_id'] ]	= $options['option_text'];
				$votes[ $options['poll_option_id'] ]	= $options['votes'];
				$total_votes[] = $options['votes'];
			}

			//-----------------------------------------
			// Convert votes
			//-----------------------------------------
			$voters = array();

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'poll_votes', 'where' => "poll_id={$row['poll_id']}"));
			$voterRes = ipsRegistry::DB('hb')->execute();
			while ($voter = ipsRegistry::DB('hb')->fetch($voterRes))
			{
				// Do we already have this user's votes
				if ($voter['user_id'] == 0 || in_array($voter['user_id'], $voters))
				{
					continue;
				}

				// And save
				$vsave = array( 'vote_date'		=> strtotime($voter['vote_date']),
								'tid'			=> $row['topic_id'],
								'member_id'		=> $voter['user_id'],
								'forum_id'		=> $topic['forum_id'],
								'member_choices'=> serialize(array(1 => $vote['poll_option_id'])) );

				$this->lib->convertPollVoter($voter['vote_id'], $vsave);
				$voters[] = $voter['user_id'];
			}

			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------
			$poll_array = array( // only allows one question per poll
								 1 => array( 'question'	=> str_replace( "'" , '&#39;', $row['question'] ),
									 		 'multi'	=> $row['multiple_cohice'] > 0 ? 1 : 0,
									 		 'choice'	=> $choices,
									 		 'votes'	=> $votes ) );

			$save = array( 'tid'			  => $row['topic_id'],
						   'start_date'		  => strtotime($topic['topic_date_time']),
						   'choices'   		  => addslashes(serialize($poll_array)),
						   'starter_id'		  => $topic['topic_starter_id'],
						   'votes'     		  => array_sum($total_votes),
						   'forum_id'  		  => $topic['forum_id'],
						   'poll_question'	  => str_replace( "'" , '&#39;', $row['question'] ),
						   'poll_view_voters' => 0 );

 			if ( $this->lib->convertPoll($row['poll_id'], $save) === TRUE )
			{
				$tid = $this->lib->getLink($save['tid'], 'topics');
				$this->DB->update('topics', array( 'poll_state' => 1 ), "tid='" . intval($tid) . "'");
			}
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
		$main = array( 'select'   => 'pm.*',
					   'from'     => array( 'pm' => 'pm' ),
					   'add_join' => array( array( 'select'	=> 'pmt.pm_text',
					   							   'from'   => array( 'pm_texts' => 'pmt'),
					   							   'where'  => 'pm.pm_id = pmt.pm_id',
					   							   'type'   => 'left' ) ),
					   'where' => 'pm.user_id != pm.pm_from',
						'order'		=> 'pm_id ASC' );

		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Post Data
			//-----------------------------------------
			$post = array( 'msg_id'			=> $row['pm_id'],
						   'msg_topic_id'      => $row['pm_id'],
						   'msg_date'          => strtotime($row['pm_date_time']),
						   'msg_post'          => $this->fixPostData($row['pm_text']),
						   'msg_post_key'      => md5(microtime()),
						   'msg_author_id'     => $row['pm_from'],
						   'msg_is_first_post' => 1 );

			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$maps = array( array( 'map_user_id'     => $row['pm_to'], // Recepient
								  'map_topic_id'    => $row['pm_id'],
								  'map_folder_id'   => 'myconvo',
								  'map_read_time'   => $row['pm_status'] == 0 ? 0 : time(),
								  'map_last_topic_reply' => $row['pm_date_time'],
								  'map_user_active' => $row['pm_folder_id'] == 102 ? 0 : 1,
								  'map_user_banned' => 0,
								  'map_has_unread'  => $row['pm_status'] == 0 ? 1 : 0,
								  'map_is_system'   => 0,
								  'map_is_starter'  => 0 ),
						   array( 'map_user_id'     => $row['from_id'], // Starter
						   		  'map_topic_id'    => $row['pm_id'],
						   		  'map_folder_id'   => 'myconvo',
						   		  'map_read_time'   => time(),
						   		  'map_last_topic_reply' => $row['pm_date_time'],
						   		  'map_user_active' => 1,
						   		  'map_user_banned' => 0,
						   		  'map_has_unread'  => 0,
						   		  'map_is_system'   => 0,
						   		  'map_is_starter'  => 1 ) );

			$topic = array(
				'mt_id'			     => $row['pm_id'],
				'mt_date'		     => strtotime($row['pm_date_time']),
				'mt_title'		     => $row['pm_subject'] == '' ? 'No subject' : $row['pm_subject'],
				'mt_starter_id'	     => $row['pm_from'],
				'mt_start_time'      => strtotime($row['pm_date_time']),
				'mt_last_post_time'  => strtotime($row['pm_date_time']),
				'mt_invited_members' => serialize( array( $row['pm_to'] => $row['pm_to'] ) ),
				'mt_to_count'		 => 1,
				'mt_to_member_id'	 => $row['pm_to'],
				'mt_replies'		 => 0,
				'mt_is_draft'		 => 0,
				'mt_is_deleted'		 => 0,
				'mt_is_system'		 => 0 );
//print "<PRE>";print_r($post);print_r($maps);print_r($topic);exit;
			//-----------------------------------------
			// Go
			//-----------------------------------------
			$this->lib->convertPM($topic, array($post), $maps);
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
		$main = array( 'select'	  => 'a.*',
					   'from'	  => array( 'attachments' => 'a' ),
					   'add_join' => array( array( 'select' => 'p.post_id, p.topic_id, p.forum_id, p.post_date_time',
					   							   'from'	=> array( 'posts' => 'p' ),
					   							   'where'	=> 'a.attachment_id = p.attachment_id',
					   							   'type'	=> 'left' ) ),
					   'where'	  => 'a.finalized = 1',
					   'order'	  => 'a.attachment_id ASC' );

		$loop = $this->lib->load('attachments', $main);

		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------
		//$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_woltlab/wcf/attachments):')), 'path');

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
		//	$this->lib->error('Your remote upload path is not readable.');
		//}

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			// Set rest of the info
			$save = array(
				//'attach_ext'		=> $ext,
				'data'		=> $row['file_contents'],
				'attach_file'  => $row['file_name'],
				'attach_date'		=> strtotime($row['upload_date']),
				'attach_hits'		=> $row['downloads'],
				'attach_rel_id'		=> $row['post_id'],
				'attach_rel_module'	=> 'post',
				'attach_member_id'	=> $row['user_id'],
				'attach_filesize'	=> strlen($row['file_contents']) );

			// Save
			$done = $this->lib->convertAttachment($row['attachment_id'], $save, $path, TRUE);
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
						'from' 		=> 'smiley',
						'order'		=> 'smileyID ASC',
					);

		$loop = $this->lib->load('emoticons', $main);

		//-----------------------------------------
		// We need to know the path and how to handle duplicates
		//-----------------------------------------

		$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder your wcf folder (no trailing slash - usually path_to_woltlab/wcf):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

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
				'typed'		=> $row['smileyCode'],
				'image'		=> $row['smileyPath'],
				'clickable'	=> 1,
				'emo_set'	=> 'default',
				);
			$done = $this->lib->convertEmoticon($row['smileyID'], $save, $us['emo_opt'], $path);
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
		$main = array( 'select'	=> 'm.*',
					   'from'		=> array('moderators' => 'm'),
					   'add_join'	=> array( array( 'select' => 'u.user_name',
					   								 'from'   => array('users' => 'u'),
					   								 'where'  => 'm.user_id = u.user_id',
					   								 'type'   => 'inner' ) ),
						'order'  => 'forum_id ASC, user_id ASC' );

		$loop = $this->lib->load('moderators', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'forum_id'	  => $row['forum_id'],
							'member_name' => $row['user_name'],
							'member_id' => $row['user_id'],
							 );

			$this->lib->convertModerator($row['forum_id'].'-'.$row['user_id'], $save);
		}

		$this->lib->next();
	}
}