<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * SMF
 * Last Update: $Date: 2009-12-06 08:57:22 -0500 (Sun, 06 Dec 2009) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 397 $
 */

$info = array( 'key'	=> 'smf',
			   'name'	=> 'SMF 2.0',
			   'login'	=> true );

class admin_convert_board_smf extends ipsCommand
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
			'emoticons'		=> array(),
			'pfields'		=> array(),
			'forum_perms'	=> array(),
			'groups' 		=> array('forum_perms'),
			'members'		=> array('groups'),
			'profile_friends' => array('members'),
			'ignored_users'	=> array('members'),
			'forums'		=> array('members'),
			'moderators'	=> array('groups', 'members', 'forums'),
			'topics'		=> array('members', 'forums'),
			'posts'			=> array('members', 'topics'),
			'polls'			=> array('topics', 'members', 'forums'),
			'pms'			=> array('members'),
			'attachments'	=> array('posts'),
			'banfilters'	=> array(),
			'warn_logs'		=> array('members'),
			);

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------

		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'SMF &rarr; IP.Board Converter' );

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

		if (array_key_exists($this->request['do'], $this->actions) or $this->request['do'] == 'boards')
		{
			call_user_func(array($this, 'convert_'.$this->request['do']));
		}
		else
		{
			$this->lib->menu( array(
				'forums' => array(
					'single' => 'categories',
					'multi'  => array( 'categories', 'boards' )
				) )	);
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
				return $this->lib->countRows('membergroups');
				break;

			case 'groups':
				return $this->lib->countRows('membergroups');
				break;

			case 'forums':
				return $this->lib->countRows('categories') + $this->lib->countRows('boards');
				break;

			case 'posts':
				return $this->lib->countRows('messages');
				break;

			case 'pms':
				return $this->lib->countRows('personal_messages');
				break;

			case 'warn_logs':
				return $this->lib->countRows('log_comments');
				break;

			case 'banfilters':
				return $this->lib->countRows('ban_items');
				break;

			case 'emoticons':
				return $this->lib->countRows('smileys');
				break;

			case 'pfields':
				return $this->lib->countRows('custom_fields');
				break;

			case 'profile_friends':
			case 'ignored_users':
				return $this->lib->countRows('members');
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
			case 'attachments':
			case 'emoticons':
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
		$text = str_replace('[li]', '[*]', $text);
		$text = str_replace('[/li]', '', $text);

		// And img tags
		$text = preg_replace("#\[img width=(\d+) height=(\d+)\](.+)\[\/img\]#i", "[img]$3[/img]", $text);

		return $text;
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
						'from' 		=> 'membergroups',
						'order'		=> 'id_group ASC',
					);

		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'id_group', 'nf' => 'group_name'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['id_group'], $row['group_name']);
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
						'from' 		=> 'membergroups',
						'order'		=> 'id_group ASC',
					);

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'id_group', 'nf' => 'group_name'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$prefix = '';
			$suffix = '';
			if ($row['online_color'])
			{
				$prefix = "<span style='color:{$row['group_color']}'>";
				$suffix = '</span>';
			}

			$save = array(
				'g_title'			=> $row['group_name'],
				'g_perm_id'			=> $row['id_group'],
				'prefix'			=> $prefix,
				'suffix'			=> $suffix,
				'g_max_messages'	=> $row['max_messages'],
				'g_hide_from_list'	=> $row['hidden'],
				);
			$this->lib->convertGroup($row['id_group'], $save);
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
			'website_url'	=> 'Website',
			'location'		=> 'Location',
			'icq'			=> 'ICQ Number',
			'aim'			=> 'AIM ID',
			'yim'			=> 'Yahoo ID',
			'msn'			=> 'MSN ID',
			'personal_text'	=> 'Personal Text',
			);

		$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('pp_path', 'gal_path', 'avatar_salt')));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'members',
						'order'		=> 'id_member ASC',
					);

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		// Avatars
		$ask['gal_path'] = array('type' => 'text', 'label' => 'Path to avatars gallery folder (no trailing slash, default /path_to_smf/avatars): ');
		$ask['attach_path'] = array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually /path_to_smf/attachments):');

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
				'id'				=> $row['id_member'],
				'username'			=> $row['member_name'],
				'displayname'		=> $row['real_name'],
				'joined'			=> $row['date_registered'],
				'group'				=> $row['id_group'],
				'password'			=> $row['passwd'],
				'email'				=> $row['email_address'],
				'secondary_groups'	=> $row['additional_groups'],
				);

			// Member info
			$birthday = ($row['birthdate']) ? explode('-', $row['birthdate']) : null;

			$members = array(
				'posts'				=> $row['posts'],
				'last_visit'		=> $row['last_login'],
				'bday_day'			=> ($row['birthdate']) ? $birthday[2] : '',
				'bday_month'		=> ($row['birthdate']) ? $birthday[1] : '',
				'bday_year'			=> ($row['birthdate']) ? $birthday[0] : '',
				'hide_email' 		=> $row['hide_email'],
				'time_offset'		=> $row['time_offset'],
				'email_pm'      	=> $row['pm_email_notify'],
				'title'				=> $rank['usertitle'],
				'ip_address'		=> $row['member_ip'],
				'misc'				=> $row['password_salt'],
				'warn_level'		=> $row['warning'],
				);

			// Profile
			$profile = array(
				'signature'			=> $this->fixPostData($row['signature']),
				);

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
			// Upload
			else
			{
				$attach = ipsRegistry::DB('hb')->buildAndFetch(array(	'select' => '*', 'from' => 'attachments', 'where' => 'id_member='.$row['id_member']));
				if ($attach)
				{
					$profile['avatar_type'] = 'upload';
					$profile['avatar_location'] = $attach['id_attach'].'_'.$attach['file_hash'];
					$path = $us['attach_path'];
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

			// Actual
			$getpf = array(	'select' 	=> '*',
							'from' 		=> 'themes', // Yes, it is a silly place to put it
							'order'		=> 'id_theme ASC',
							'where'		=> "id_member={$row['id_member']}"
						);
			ipsRegistry::DB('hb')->build($getpf);
			ipsRegistry::DB('hb')->execute();
			while ($pfields = ipsRegistry::DB('hb')->fetch())
			{
				if (is_array($pfields['variable']) and in_array($pfields['variable'], array_keys($us['pfield_data'])))
				{
					$link = $this->lib->getLink($us['pfield_data'][$pfields['variable']], 'pfields');
					if ($us['pfields_values'][$pfields['variable']][$pfields['value']])
					{
						$custom['field_'.$link] = $us['pfields_values'][$pfields['variable']][$pfields['value']];
					}
					else
					{
						$custom['field_'.$link] = $pfields['value'];
					}
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
	 * Convert Categories
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
						'order'		=> 'id_cat ASC',
					);

		$loop = $this->lib->load('forums', $main, array(), 'boards');

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertForum('c'.$row['id_cat'], array('name' => $row['name'], 'parent_id' => -1), array());
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

		$mainBuild = array(	'select' 	=> '*',
							'from' 		=> 'boards',
							'order'		=> 'id_board ASC',
						);

		$this->start = intval($this->request['st']);
		$this->end = $this->start + intval($this->request['cycle']);

		$mainBuild['limit'] = array($this->start, $this->end);

		$this->lib->errors = unserialize($this->settings['conv_error']);

		ipsRegistry::DB('hb')->build($mainBuild);
		ipsRegistry::DB('hb')->execute();

		if (!ipsRegistry::DB('hb')->getTotalRows())
		{
			$action = 'forums';
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
			$get[$this->app['name']] = $us;
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

		while ( $row = ipsRegistry::DB('hb')->fetch() )
		{
			$records[] = $row;
		}

		$loop = $records;

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			// Work out what the parent is
			if ($row['id_parent'])
			{
				$parent = $row['id_parent'];
			}
			else
			{
				$parent = 'c'.$row['id_cat'];
			}

			$redirect_on = (bool) $row['redirect'];

			// Set info
			$save = array(
				'parent_id'			=> $parent,
				'position'			=> $row['board_order'],
				'last_id'			=> $row['id_last_msg'],
				'name'				=> $row['name'],
				'description'		=> $row['description'],
				'topics'			=> $row['num_topics'],
				'posts'				=> $row['num_posts'],
				'inc_postcount'		=> $row['count_posts'],
				'queued_posts'		=> $row['unapproved_posts'],
				'queued_topics'		=> $row['unapproved_topics'],
				'redirect_url'		=> $row['redirect'],
				'redirect_on'		=> intval($redirect_on),
				);

			// Save
			$this->lib->convertForum($row['id_board'], $save, array());

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'log_notify', 'where' => "id_board={$row['id_board']}"));
			ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch())
			{
				$savetracker = array(
					'member_id'	=> $tracker['id_member'],
					'forum_id'	=> $tracker['id_board'],
					);
				$this->lib->convertForumSubscription($tracker['id_member'].'-'.$tracker['id_board'], $savetracker);
			}

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
						'from' 		=> array('topics' => 't'),
						'order'		=> 't.id_topic ASC',
						'add_join'	=> array(
										array( 	'select' => 'p.subject, p.poster_time, p.poster_name',
												'from'   =>	array( 'messages' => 'p' ),
												'where'  => "p.id_msg=t.id_first_msg",
												'type'   => 'left'
											),
										),

					);

		$loop = $this->lib->load('topics', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'title'				=> $row['subject'],
				'start_date'		=> $row['poster_time'],
				'pinned'			=> $row['is_sticky'],
				'forum_id'			=> $row['id_board'],
				'starter_id'		=> $row['id_member_started'],
				'starter_name'		=> $row['poster_name'],
				'last_poster_id'	=> $row['id_member_updated'],
				'poll_state'		=> (bool) $row['id_poll'],
				'posts'				=> $row['num_replies'],
				'views'				=> $row['num_views'],
				'state'				=> ($row['locked']) ? 'closed' : 'open',
				'topic_queuedposts'	=> $row['unapproved_posts'],
				'approved'			=> $row['approved'],
				);
			$this->lib->convertTopic($row['id_topic'], $save);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'log_notify', 'where' => "id_topic={$row['id_topic']}"));
			ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch())
			{
				$savetracker = array(
					'member_id'	=> $tracker['id_member'],
					'topic_id'	=> $tracker['id_topic'],
					);
				$this->lib->convertTopicSubscription($tracker['id_member'].'-'.$tracker['id_topic'], $savetracker);
			}

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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'messages',
						'order'		=> 'id_msg ASC',
					);

		$loop = $this->lib->load('posts', $main);

		//-----------------------------------------
		// Prepare for reports conversion
		//-----------------------------------------

		$this->lib->prepareReports('post');

		$new = $this->DB->buildAndFetch( array( 'select' => 'status', 'from' => 'rc_status', 'where' => 'is_new=1' ) );
		$complete = $this->DB->buildAndFetch( array( 'select' => 'status', 'from' => 'rc_status', 'where' => 'is_complete=1' ) );

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{

			$save = array(
				'topic_id'			=> $row['id_topic'],
				'post_date'			=> $row['poster_time'],
				'author_id'			=> $row['id_member'],
				'author_name'		=> $row['poster_name'],
				'ip_address'		=> $row['poster_ip'],
				'use_emo'			=> $row['smileys_enabled'],
				'edit_time'			=> $row['modified_time'],
				'edit_name'			=> $row['modified_name'],
				'post'				=> $this->fixPostData($row['body']),
				'queued'			=> ($row['approved']) ? 0 : 1,
				);
			$this->lib->convertPost($row['id_msg'], $save);

			//-----------------------------------------
			// Report Center
			//-----------------------------------------

			$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='post'" ) );

			$rs = array(	'select' 	=> '*',
							'from' 		=> 'log_reported',
							'order'		=> 'id_report ASC',
							'where'		=> 'id_msg='.$row['id_msg']
						);

			$rget = ipsRegistry::DB('hb')->buildAndFetch($rs);

			if($rget)
			{
				// Comments
				$comments = array();
				$rsc = array(	'select' 	=> '*',
								'from' 		=> 'log_comments',
								'order'		=> 'id_comment ASC',
								'where'		=> 'comment_type=\'reportc\' AND id_notice='.$rget['id_report']
							);
				$reports = array();
				ipsRegistry::DB('hb')->build($rsc);
				ipsRegistry::DB('hb')->execute();
				while ($rgetc = ipsRegistry::DB('hb')->fetch())
				{
					$comments[] = array(
							'rid'			=> $rget['id_report'],
							'comment'		=> $rgetc['body'],
							'comment_by'	=> $rgetc['id_member'],
							'comment_date'	=> $rgetc['log_time']
						);
				}

				// Details about the post
				$report = array(
					'id'			=> $rget['id_report'],
					'title'			=> $rget['subject'],
					'status'		=>	($rget['closed'] or $rget['ignore_all']) ? $complete['status'] : $new['status'],
					'rc_class'		=> $rc['com_id'],
					'updated_by'	=> $rget['id_member'],
					'date_updated'	=> $rget['time_updated'],
					'date_created'	=> $rget['time_started'],
					'exdat1'		=> $rget['id_board'],
					'exdat2'		=> $rget['id_topic'],
					'exdat3'		=> $rget['id_msg'],
					'num_reports'	=> $rget['num_reports'],
					'num_comments'	=> count($comments),
					'seoname'		=> IPSText::makeSeoTitle( $forum['title'] ),
					);

				// Everyone who's reported it
				$rs2 = array(	'select' 	=> '*',
								'from' 		=> 'log_reported_comments',
								'order'		=> 'id_comment ASC',
								'where'		=> 'id_report='.$rget['id_report']
							);
				$reports = array();
				ipsRegistry::DB('hb')->build($rs2);
				ipsRegistry::DB('hb')->execute();
				while ($rget2 = ipsRegistry::DB('hb')->fetch())
				{
					$reports[] = array(
							'id'			=> $rget2['id_report'],
							'report'		=> $rget2['comment'],
							'report_by'		=> $rget2['id_member'],
							'date_reported'	=> $rget2['time_sent']
						);
				}

				$this->lib->convertReport('post', $report, $reports, false, $comments);

			}

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
						'order'		=> 'id_attach ASC',
					);

		$loop = $this->lib->load('attachments', $main);

		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------

		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_smf/attachments):')), 'path');

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
			// Is this the right path?
			if($row['id_folder'] and $row['id_folder'] != 1)
			{
				$path = $us['attach_path'] . $row['id_folder'];
			}
			else
			{
				$path = $us['attach_path'];
			}

			// Skip thumbnails and avatars
			if ($row['attachment_type'] == 3 or !$row['id_msg'])
			{
				continue;
			}

			// Now where is it?
			if($row['file_hash'])
			{
				$location = $row['id_attach'].'_'.$row['file_hash'];
			}
			else
			{
				$location = $row['id_attach'] . '_' . str_replace( '.', '_', $row['filename'] ) . md5( str_replace(' ', '_', $row['filename'] ) );
			}
			$location = str_replace(' ', '_', $location);

			if(!file_exists($path.'/'.$location))
			{
				$location = str_replace( ' ', '_', $row['filename'] );
			}

			// Is this an image?
			$image = false;
			if (preg_match('/image/', $row['mime_type']))
			{
				$image = true;
			}

			// Date
			$post = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'id_member, poster_time', 'from' => 'messages', 'where' => 'id_msg='.$row['id_msg']));

			// Fix attachments crap
			// http://community.invisionpower.com/tracker/issue-21020-smf-attachments-are-crazy/
			$row['filename'] = str_replace(' ', '_', $row['filename']);
			$row['filename'] = str_replace('!', '', $row['filename']);
			$row['filename'] = str_replace('(', '', $row['filename']);
			$row['filename'] = str_replace(')', '', $row['filename']);
			$row['filename'] = str_replace(',', '', $row['filename']);
			$row['filename'] = str_replace('[', '', $row['filename']);
			$row['filename'] = str_replace(']', '', $row['filename']);
			$row['filename'] = str_replace('&', '', $row['filename']);
			$row['filename'] = str_replace('\'', '', $row['filename']);
			$row['filename'] = str_replace( chr(195) . chr(182) , 'A', $row['filename']);
			$row['filename'] = str_replace( chr(195) . chr(164) , 'A', $row['filename']);

			// Sort out data
			$save = array(
				'attach_ext'			=> $row['fileext'],
				'attach_file'			=> $row['filename'],
				'attach_location'		=> $location,
				'attach_is_image'		=> $image,
				'attach_hits'			=> $row['downloads'],
				'attach_date'			=> $post['poster_time'],
				'attach_member_id'		=> $post['id_member'],
				'attach_approved'		=> $row['approved'],
				'attach_filesize'		=> $row['size'],
				'attach_rel_id'			=> $row['id_msg'],
				'attach_rel_module'		=> 'post',
				'attach_img_width'		=> $row['width'],
				'attach_img_height'		=> $row['height'],
				);


			// Send em on
			$done = $this->lib->convertAttachment($row['id_attach'], $save, $path);

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
						'order'		=> 'id_poll ASC',
					);

		$loop = $this->lib->load('polls', $main, array('voters'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Get data
			//-----------------------------------------

			$topic = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'id_topic, id_board, id_first_msg', 'from' => 'topics', 'where' => 'id_poll='.$row['id_poll']));
			$firstpost = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'poster_time', 'from' => 'messages', 'where' => 'id_msg='.$topic['id_first_msg']));

			//-----------------------------------------
			// Options are stored in one place...
			//-----------------------------------------

			$choice = array();
			$votes = array();

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'poll_choices', 'where' => "id_poll={$row['id_poll']}"));
			ipsRegistry::DB('hb')->execute();
			while ($options = ipsRegistry::DB('hb')->fetch())
			{
				$choice[ $options['id_choice'] ]	= $options['label'];
				$votes[ $options['id_choice'] ]	= $options['votes'];
				$total_votes[] = $options['votes'];
			}

			//-----------------------------------------
			// Votes in another...
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'log_polls', 'where' => "id_poll={$row['id_poll']}"));
			ipsRegistry::DB('hb')->execute();
			while ($voter = ipsRegistry::DB('hb')->fetch())
			{
				$vsave = array(
					'tid'			=> $topic['id_topic'],
					'member_choices'=> serialize(array(1 => array($voter['id_choice']))),
					'member_id'		=> $voter['id_member'],
					'forum_id'		=> $topic['id_board']
					);

				$this->lib->convertPollVoter($voter['id_poll'].'-'.$voter['id_member'], $vsave);
			}

			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------

			$poll_array = array(
				// SMF only allows one question per poll
				1 => array(
					'question'	=> $row['question'],
					'choice'	=> $choice,
					'votes'		=> $votes,
					)
				);

			$save = array(
				'tid'			=> $topic['id_topic'],
				'start_date'	=> $firstpost['poster_time'],
				'choices'   	=> addslashes(serialize($poll_array)),
				'starter_id'	=> $row['id_member'],
				'votes'     	=> array_sum($total_votes),
				'forum_id'  	=> $topic['id_board'],
				'poll_question'	=> $row['question']
				);

			$this->lib->convertPoll($row['id_poll'], $save);
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
						'from' 		=> 'personal_messages',
						'order'		=> 'id_pm ASC',
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

			$post = array(
				'msg_id'			=> $row['id_pm'],
				'msg_topic_id'      => $row['id_pm'],
				'msg_date'          => $row['msgtime'],
				'msg_post'          => $this->fixPostData($row['body']),
				'msg_post_key'      => md5(microtime()),
				'msg_author_id'     => $row['id_member_from'],
				'msg_is_first_post' => 1
				);

			//-----------------------------------------
			// Map Data
			//-----------------------------------------

			$maps = array();
			$_invited   = array();
			$recipient = $row['id_member_from'];

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'pm_recipients', 'where' => "id_pm={$row['id_pm']}"));
			ipsRegistry::DB('hb')->execute();
			while ($to = ipsRegistry::DB('hb')->fetch())
			{
				if ($to['id_member'] == $to['id_member_from'])
				{
					continue;
				}

				$maps[$to['id_member']] = array(
					'map_user_id'     => $to['id_member'],
					'map_topic_id'    => $to['id_pm'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => 0,
					'map_last_topic_reply' => $row['msgtime'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => $to['is_new'],
					'map_is_system'   => 0,
					'map_is_starter'  => 0
					);

				$_invited[ $to['id_member'] ] = $to['id_member'];

				if (!$to['bcc'])
				{
					$recipient = $to['id_member'];
				}
			}

			// Need to add self to this
			if (!in_array($row['id_member_from'], array_keys($maps)))
			{
				$maps[$row['id_member_from']] = array(
					'map_user_id'     => $row['id_member_from'],
					'map_topic_id'    => $row['id_pm'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => 0,
					'map_last_topic_reply' => $row['msgtime'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => 0,
					'map_is_system'   => 0,
					'map_is_starter'  => 1
					);
			}

			//-----------------------------------------
			// Map Data
			//-----------------------------------------

			$topic = array(
				'mt_id'			     => $row['id_pm'],
				'mt_date'		     => $row['msgtime'],
				'mt_title'		     => $row['subject'],
				'mt_starter_id'	     => $row['id_member_from'],
				'mt_start_time'      => $row['msgtime'],
				'mt_last_post_time'  => $row['msgtime'],
				'mt_invited_members' => serialize( array_keys( $_invited ) ),
				'mt_to_count'		 => count(  array_keys( $_invited ) ),
				'mt_to_member_id'	 => $recipient,
				'mt_replies'		 => 0,
				'mt_is_draft'		 => 0,
				'mt_is_deleted'		 => 0,
				'mt_is_system'		 => 0
				);

			//-----------------------------------------
			// Go
			//-----------------------------------------

			$this->lib->convertPM($topic, array($post), $maps);

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
						'from'		=> 'moderators'
					);

		$loop = $this->lib->load('moderators', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{

			$member = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'member_name', 'from' => 'members', 'where' => 'id_member='.$row['id_member']));

			$save = array(
							   'forum_id'	  => $row['id_board'],
							   'member_name'  => $member['member_name'],
							   'member_id'	  => $row['id_member']
						 );


			$this->lib->convertModerator($row['id_board'].'-'.$row['id_member'], $save);
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
						'from' 		=> 'log_comments',
						'where'		=> "comment_type='warning'",
						'order'		=> 'id_comment ASC',
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

			$save = array(
				'wlog_mid'		=> $row['id_recipient'],
				'wlog_notes'	=> serialize(array('content' => $this->fixPostData($row['body']))),
				'wlog_date'		=> $row['log_time'],
				'wlog_type'		=> ($row['counter'] < 0) ? 'neg' : 'pos',
				'wlog_addedby'	=> $row['id_member']
				);

			//-----------------------------------------
			// Pass it on
			//-----------------------------------------

			$this->lib->convertWarn($row['id_comment'], $save);
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
							'from' 		=> 'ban_items',
							'order'		=> 'id_ban ASC',
						);

		$loop = $this->lib->load('banfilters', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			// Hostname
			if ($row['hostname'])
			{
				continue;
			}

			// User
			elseif ($row['id_member'])
			{
				$link = $this->lib->getLink($row['id_member'], 'members');
				if ($link)
				{
					$this->DB->update('members', array('member_banned' => 1), 'member_id='.$link);
				}
				continue;
			}

			// IP
			elseif ($row['ip'])
			{
				$save = array(
					'ban_type'		=> 'ip',
					'ban_content'	=> $row['ip_low1'].'.'.$row['ip_low2'].'.'.$row['ip_low3'].'.'.$row['ip_low4'],
					);
			}

			// EMail
			elseif ($row['email_address'])
			{
				$save = array(
					'ban_type'		=> 'email',
					'ban_content'	=> $row['email_address'],
					);
			}

			$this->lib->convertBan($row['id_ban'], $save);
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
						'from' 		=> 'custom_fields',
						'order'		=> 'id_field ASC',
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
			$group = $this->lib->convertPFieldGroup(1, array('pf_group_name' => 'Converted', 'pf_group_key' => 'smf'), true);
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
			$content = '';
			$type = '';

			// What kind of field is this
			if ($row['field_type'] == 'textarea')
			{
				$type = 'textarea';
			}
			elseif ($row['field_type'] == 'select')
			{
				$type = 'drop';
				$options = explode(',', $row['field_options']);
				$save_options = array();
				foreach ($options as $key => $value)
				{
					$us['pfields_values'][$row['col_name']][$value] = $key;
					$save_options[] = "{$key}={$value}";
				}
				$content = implode('|', $save_options);
			}
			elseif ($row['field_type'] == 'radio')
			{
				$type = 'radio';
				$options = explode(',', $row['field_options']);
				$save_options = array();
				foreach ($options as $key => $value)
				{
					$us['pfields_values'][$row['col_name']][$value] = $key;
					$save_options[] = "{$key}={$value}";
				}
				$content = implode('|', $save_options);
			}
			elseif ($row['field_type'] == 'check')
			{
				$type = 'drop';
				$content = implode('|', array('1=Yes', '0=No'));
			}
			else
			{
				$type = 'input';
			}

			// Privacy
			switch ($row['private'])
			{
				case 1:
					$hide = 0;
					$edit = 0;
					$admin = 0;
					break;

				case 2:
					$hide = 1;
					$edit = 0;
					$admin = 0;
					break;

				case 3:
					$hide = 1;
					$edit = 0;
					$admin = 1;
					break;

				default:
					$hide = 0;
					$edit = 1;
					$admin = 0;
					break;
			}

			// Insert?
			$save = array(
				'pf_title'			=> $row['field_name'],
				'pf_desc'			=> $row['field_desc'],
				'pf_content'		=> $content,
				'pf_type'			=> $type,
				'pf_not_null'		=> ($row['show_reg'] == 2) ? 1 : 0,
				'pf_member_hide'	=> $hide,
				'pf_max_input'		=> $row['field_length'],
				'pf_member_edit'	=> $edit,
				'pf_show_on_reg'	=> (bool) $row['show_reg'],
				'pf_admin_only'		=> $admin,
				'pf_group_id'		=> 1,
				'pf_key'			=> $row['col_name'],
				);

			$this->lib->convertPField($row['id_field'], $save);

			// Save
			$us['pfield_data'][$row['col_name']] = $row['id_field'];
		}

		// Save pfield_data
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_extra' => serialize($get)));

		// Next, please!
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
						'from' 		=> 'smileys',
						'order'		=> 'id_smiley ASC',
					);

		$loop = $this->lib->load('emoticons', $main);

		//-----------------------------------------
		// We need to know the path and how to handle duplicates
		//-----------------------------------------

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$path = $us['emo_path'];

		$ask = array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_smf/Smileys):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') );

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
				'image'		=> $row['filename'],
				'clickable'	=> ($row['hidden']) ? 0 : 1,
				'emo_set'	=> 'default',
				);
			$done = $this->lib->convertEmoticon($row['id_smiley'], $save, $us['emo_opt'], $path);
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

		$main = array(	'select' 	=> 'id_member, buddy_list',
						'from' 		=> 'members',
						'order'		=> 'id_member ASC',
					);

		$loop = $this->lib->load('profile_friends', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			if (!$row['buddy_list'])
			{
				continue;
			}
			$explode = explode(',', $row['buddy_list']);
			foreach ($explode as $friend)
			{
				$save = array(
					'friends_member_id'	=> $row['id_member'],
					'friends_friend_id'	=> $friend,
					'friends_approved'	=> '1',
					);
				$this->lib->convertFriend($row['id_member'].'-'.$friend, $save);
			}
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

		$main = array(	'select' 	=> 'id_member, pm_ignore_list',
						'from' 		=> 'members',
						'order'		=> 'id_member ASC',
					);

		$loop = $this->lib->load('ignored_users', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			if (!$row['pm_ignore_list'])
			{
				continue;
			}
			$explode = explode(',', $row['pm_ignore_list']);
			foreach ($explode as $foe)
			{
				$save = array(
					'ignore_owner_id'	=> $row['id_member'],
					'ignore_ignore_id'	=> $foe,
					'ignore_messages'	=> '1',
					'ignore_topics'		=> '1',
					);
				$this->lib->convertIgnore($row['id_member'].'-'.$foe, $save);
			}
		}

		$this->lib->next();

	}

}