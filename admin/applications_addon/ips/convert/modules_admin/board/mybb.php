<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * MyBB
 * Last Update: $Date: 2011-07-31 13:28:48 +0100 (Sun, 31 Jul 2011) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 571 $
 */


	$info = array(
		'key'	=> 'mybb',
		'name'	=> 'MyBB 1.6',
		'login'	=> true,
	);

	class admin_convert_board_mybb extends ipsCommand
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
				'emoticons'		=> array(),
				'pfields'		=> array(),
				'forum_perms'	=> array(),
				'groups'		=> array('forum_perms'),
				'members'		=> array('groups', 'pfields'),
				'forums'		=> array('members'),
				'moderators'	=> array('groups', 'members', 'forums'),
				'topics'		=> array('members'),
				'topic_ratings' => array('topics', 'members'),
				'posts'			=> array('members', 'topics', 'emoticons'),
				'polls'			=> array('topics', 'members', 'forums'),
				'announcements'	=> array('forums', 'members', 'emoticons'),
				'pms'			=> array('members', 'emoticons'),
				'ranks'			=> array(),
				'attachments_type' => array(),
				'attachments'=> array('attachments_type', 'posts'),
				'badwords'		=> array(),
				'banfilters'	=> array(),
				'topic_mmod'	=> array('forums'),
				'warn_logs'		=> array('members'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'MyBB &rarr; IP.Board Converter' );

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

			if (array_key_exists($this->request['do'], $this->actions) or $this->request['do'] == 'disallow')
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
					return  $this->lib->countRows('usergroups');
					break;

				case 'groups':
					return  $this->lib->countRows('usergroups');
					break;

				case 'members':
					return  $this->lib->countRows('users');
					break;

				case 'topics':
					return  $this->lib->countRows('threads');
					break;

				case 'attachments_type':
					return  $this->lib->countRows('attachtypes');
					break;

				case 'pms':
					return  $this->lib->countRows('privatemessages');
					break;

				case 'pfields':
					return  $this->lib->countRows('profilefields');
					break;

				case 'emoticons':
					return  $this->lib->countRows('smilies');
					break;

				case 'topic_mmod':
					return  $this->lib->countRows('modtools');
					break;

				case 'ranks':
					return  $this->lib->countRows('usertitles');
					break;

				case 'topic_ratings':
					return  $this->lib->countRows('threadratings');
					break;

				case 'warn_logs':
					return  $this->lib->countRows('warnings');
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
		 * Convert groups
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
							'from' 		=> 'usergroups',
							'order'		=> 'gid ASC',
						);

			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'gid', 'nf' => 'title'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['gid'], $row['title']);
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
							'from' 		=> 'usergroups',
							'order'		=> 'gid ASC',
						);

			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'gid', 'nf' => 'title'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				// Work out the attachment quota
				if (!$row['canpostattachments'])
				{
					$attach_quota = 0;
				}
				elseif (!$row['attachquota'])
				{
					$attach_quota = -1;
				}
				else
				{
					$attach_quota = $row['attachquota'];
				}

				// Work out the prefix and suffix
				$prefix = substr($row['namestyle'], 0, strpos($row['namestyle'], '{username}'));
				$suffix = substr($row['namestyle'], strpos($row['namestyle'], '{username}')+10);

				// And save
				$save = array(
					'g_view_board'			=> $row['canview'],
					'g_mem_info'			=> $row['canviewprofiles'],
					'g_other_topics'		=> $row['canviewthreads'],
					'g_use_search'			=> $row['cansearch'],
					'g_email_friend'		=> $row['cansendemail'],
					'g_edit_profile'		=> $row['canusercp'],
					'g_post_new_topics'		=> $row['canpostthreads'],
					'g_reply_own_topics'	=> $row['canpostreplies'],
					'g_reply_other_topics'	=> $row['canpostreplies'],
					'g_edit_posts'			=> $row['caneditposts'],
					'g_delete_own_posts'	=> $row['candeleteposts'],
					'g_post_polls'			=> $row['canpostpolls'],
					'g_vote_polls'			=> $row['canvotepolls'],
					'g_use_pm'				=> $row['canusepms'],
					'g_is_supmod'			=> $row['issupermod'],
					'g_title'				=> $row['title'],
					'g_access_offline'		=> $row['issupermod'],
					'g_attach_max'			=> $attach_quota,
					'g_avatar_upload'		=> $row['canuploadavatars'],
					'prefix'				=> $prefix,
					'suffix'				=> $suffix,
					'g_max_messages'		=> $row['pmquota'],
					'g_max_mass_pm'			=> $row['maxpmrecipients'],
					'g_perm_id'				=> $row['gid'],
					'g_bypass_badwords'		=> $row['issupermod'],
					'g_topic_rate_setting'	=> $row['canratethreads'],
					'g_dname_changes'		=> $row['canchangename'],
					'g_rep_max_positive'	=> $row['maxreputationsday'],
					'g_rep_max_negative'	=> $row['maxreputationsday'],
					);
				$this->lib->convertGroup($row['gid'], $save);
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
				'icq'			=> 'ICQ Number',
				'aim'			=> 'AIM ID',
				'yahoo'			=> 'Yahoo ID',
				'msn'			=> 'MSN ID',
				'website'		=> 'Website',
				);

			$this->lib->saveMoreInfo('members', array_keys($pcpf));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> 'u.*',
							'from' 		=> array('users' => 'u'),
							'add_join'	=> array(
											array( 	'select' => 'c.*',
													'from'   =>	array( 'userfields' => 'c' ),
													'where'  => "c.ufid=u.uid",
													'type'   => 'left'
												),
											),
							'order'		=> 'uid ASC',
						);

			$loop = $this->lib->load('members', $main);

			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();

			// We need to know how to the avatar paths
			$ask['upload_path'] = array('type' => 'text', 'label' => 'Path to uploads folder (no trailing slash, default /pathtomybb/uploads): ');
			$ask['gal_path'] 	= array('type' => 'text', 'label' => 'Path to avatars gallery folder (no trailing slash, default /pathtomybb/images/avatars): ');

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
					'id'				=> $row['uid'],
					'group'				=> $row['usergroup'],
					'secondary_groups'	=> $row['additionalgroups'],
					'joined'			=> $row['regdate'],
					'username'			=> $row['username'],
					'email'				=> $row['email'],
					'password'			=> $row['password'],
					);

				// Member info
				$birthday = ($row['birthday']) ? explode('-', $row['birthday']) : null;

				$members = array(
					'ip_address'		=> $row['regip'],
					'posts'				=> $row['postnum'],
					'title'				=> $row['usertitle'],
					'allow_admin_mails' => $row['allownotices'],
					'time_offset'		=> $row['timezone'],
					'hide_email'		=> $row['hideemail'],
					'email_pm'			=> $row['pmnotify'],
					'warn_level'		=> $row['warningpoints'],
					'last_post'			=> $row['lastpost'],
					'view_sigs'			=> $row['showsigs'],
					'bday_day'			=> ($row['birthday']) ? $birthday[0] : '',
					'bday_month'		=> ($row['birthday']) ? $birthday[1] : '',
					'bday_year'			=> ($row['birthday']) ? $birthday[2] : '',
					'msg_show_notification' => $row['pmnotice'],
					'misc'				=> $row['salt'],
					'last_visit'		=> $row['lastvisit'],
					'last_activity'		=> $row['lastactive'],
					'dst_in_use'		=> ($row['dst'] > 0) ? 1 : 0,
					'coppa_user'		=> $row['coppauser'],
					'members_disable_pm'=> ($row['receivepms'] == 1) ? 0 : 1,
					);

				// Profile
				$profile = array(
					'pp_reputation_points'	=> $row['reputation'],
					'notes'					=> $this->fixPostData($row['notepad']),
					'signature'				=> $this->fixPostData($row['signature']),
					);

				//-----------------------------------------
				// Avatar
				//-----------------------------------------

				$path = '';
				// Uploaded
				if ($row['avatartype'] == 'upload')
				{
					$profile['photo_type'] = 'custom';
					$profile['photo_location'] = str_replace('./uploads/', '', $row['avatar']);
					$profile['photo_location'] = preg_replace ( "/\?dateline=([0-9]+)/si", '', $profile['avatar_location'] ); // MyBB drives me insane sometimes.
					$imgSize = explode ( '|', $row['avatardimensions'] );
					$profile['pp_main_width']	= $imgSize[0];
					$profile['pp_main_height']	= $imgSize[1];
					$path = $us['upload_path'];
				}
				// URL
				elseif ($row['avatartype'] == 'remote')
				{
					$profile['photo_type'] = 'url';
					$profile['photo_location'] = $row['avatar'];
					$imgSize = explode ( '|', $row['avatardimensions'] );
					$profile['pp_main_width']	= $imgSize[0];
					$profile['pp_main_height']	= $imgSize[1];
				}
				// Gallery
				elseif ($row['avatartype'] == 'gallery')
				{
					$profile['photo_type'] = 'custom';
					$profile['photo_location'] = str_replace('images/avatars/', '', $row['avatar']);
					$imgSize = explode ( '|', $row['avatardimensions'] );
					$profile['pp_main_width']	= $imgSize[0];
					$profile['pp_main_height']	= $imgSize[1];
					$path = $us['gal_path'];
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

				$this->lib->convertMember($info, $members, $profile, $custom, $path);

				//-----------------------------------------
				// FRIEND! (jumps on hood of car)
				// Little joke for the Brits there ;-)
				//-----------------------------------------

				$friends = explode(',', $row['buddylist']);
				foreach ($friends as $friendid)
				{
					if(!$friendid) continue;

					$save = array(
						'friends_member_id'	=> $row['uid'],
						'friends_friend_id'	=> $friendid,
						'friends_approved'	=> '1',
						);
					$this->lib->convertFriend($row['uid'].'-'.$friendid, $save);
				}

				//-----------------------------------------
				// And foes
				//-----------------------------------------

				$foes = explode(',', $row['ignorelist']);
				foreach ($foes as $foeid)
				{
					if(!$foeid) continue;

					$save = array(
						'ignore_owner_id'	=> $row['uid'],
						'ignore_ignore_id'	=> $foeid,
						'ignore_messages'	=> '1',
						'ignore_topics'		=> '1',
						);

					$this->lib->convertIgnore($row['uid'].'-'.$foeid, $save);
				}

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
							'order'		=> 'fid ASC',
						);

			$loop = $this->lib->load('forums', $main, array('forum_tracker'));

			//-----------------------------------------
			// Get groups
			//-----------------------------------------

			$groups = array();
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'usergroups'));
			ipsRegistry::DB('hb')->execute();
			while ($g = ipsRegistry::DB('hb')->fetch())
			{
				$groups[] = $g;
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Handle permissions
				//-----------------------------------------

				$canview = array();
				$canread = array();
				$canreply = array();
				$canstart = array();
				$canupload = array();
				$candownload = array();

				$perm_row = array();
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'forumpermissions', 'where' => "fid={$row['fid']}"));
				ipsRegistry::DB('hb')->execute();
				while ($perms = ipsRegistry::DB('hb')->fetch())
				{
					$perm_row[$perms['gid']] = $perms;
				}

				foreach ($groups as $group)
				{
					$perms = array();
					if (in_array($group['gid'], array_keys($perm_row)))
					{
						$perms = $perm_row[$group['gid']];
					}
					else
					{
						$perms = $group;
					}

					if ($perms['canview'])
					{
						$canview[] = $group['gid'];
					}
					if ($perms['canviewthreads'])
					{
						$canread[] = $group['gid'];
					}
					if ($perms['canpostreplys'])
					{
						$canreply[] = $group['gid'];
					}
					if ($perms['canpostthreads'])
					{
						$canstart[] = $group['gid'];
					}
					if ($perms['canpostattachments'])
					{
						$canupload[] = $group['gid'];
					}
					if ($perms['candlattachments'])
					{
						$candownload[] = $group['gid'];
					}
				}

				$perms = array();
				$perms['view']		= implode(',', $canview);
				$perms['read']		= implode(',', $canread);
				$perms['reply']		= implode(',', $canreply);
				$perms['start']		= implode(',', $canstart);
				$perms['upload']	= implode(',', $canupload);
				$perms['download']	= implode(',', $candownload);

				// We don't have an equivilent option - but people aren't going
				// to want these hidden forums suddenly displaying - so we'll
				// just remove permissions from them.
				if (!$row['active'])
				{
					$perms = array();
				}

				//-----------------------------------------
				// And go
				//-----------------------------------------

				// Rules
				switch ($row['rulestype'])
				{
					case 1:
						$show_rules = 2;
						break;

					case 2:
						$show_rules = 1;
						break;

					default:
						$show_rules = 0;
						break;
				}

				// Mod preview
				$mod = 0;
				if ($row['modposts'] and $row['modthreads'])
				{
					$mod = 1;
				}
				elseif ($row['modthreads'])
				{
					$mod = 2;
				}
				elseif ($row['modposts'])
				{
					$mod = 3;
				}

				// Parent
				// This is the most ridiculous method for storing the parent ID I've seen
				// Seriously - it's more bizzare than anything in vBulletin... (except maybe thread prefixes)
				$parents = explode(',', $row['parentlist']);
				array_pop($parents); # That will just be the same as $row['fid']
				if (count($parents))
				{
					$parent = array_pop($parents);
				}
				else
				{
					$parent = -1;
				}

				$save = array(
					'topics'			=> $row['threads'],
					'posts'				=> $row['posts'],
					'last_post'			=> $row['lastpost'],
					'last_poster_name'	=> $row['lastposter'],
					'name'				=> $row['name'],
					'description'		=> $row['description'],
					'position'			=> $row['disporder'],
					'use_ibc'			=> $row['allowmycode'],
					'use_html'			=> $row['allowhtml'],
					'status'			=> $row['open'],
					'password'			=> $row['password'],
					'last_title'		=> $row['lastpostsubject'],
					'show_rules'		=> $show_rules,
					'preview_posts'		=> $mod,
					'inc_postcount'		=> $row['usepostcounts'],
					'conv_parent'		=> $parent,
					'parent_id'			=> $parent,
					'redirect_url'		=> $row['linkto'],
					'redirect_on'		=> ($row['linkto'] == '') ? 0 : 1,
					'rules_title'		=> $row['rulestitle'],
					'rules_text'		=> $row['rules'],
					'sub_can_post'		=> ($row['type'] == 'c') ? 0 : 1,
					'forum_allow_rating'=> $row['allowratings'],
					);

				$this->lib->convertForum($row['fid'], $save, $perms);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'forumsubscriptions', 'where' => "fid={$row['fid']}"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertForumSubscription($tracker['frid'], array('member_id' => $tracker['uid'], 'forum_id' => $tracker['fid'] ));
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

			$main = array(	'select' 	=> '*',
							'from' 		=> 'threads',
							'order'		=> 'tid ASC',
						);

			$loop = $this->lib->load('topics', $main, array('tracker'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				$save = array(
					'forum_id'			=> $row['fid'],
					'title'				=> $row['subject'],
					'poll_state'		=> $row['poll'],
					'starter_id'		=> $row['uid'],
					'starter_name'		=> $row['username'],
					'start_date'		=> $row['dateline'],
					'last_post'			=> $row['lastpost'],
					'last_poster_name'	=> $row['lastposter'],
					'last_poster_id'	=> $row['lastposteruid'],
					'views'				=> $row['views'],
					'posts'				=> $row['replies'],
					'state'		   	 	=> $row['closed'] == 0 ? 'open' : 'closed',
					'pinned'			=> $row['sticky'],
					'topic_rating_hits'	=> $row['numratings'],
					'topic_rating_total'=> $row['totalratings'],
					'approved'			=> $row['visible'],
					'topic_queuedposts'	=> $row['unapprovedposts'],
					'topic_hasattach'	=> $row['attachmentcount']
					);

				$this->lib->convertTopic($row['tid'], $save);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'threadsubscriptions', 'where' => "tid={$row['tid']}"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$savetracker = array(
						'member_id'	=> $tracker['uid'],
						'topic_id'	=> $tracker['tid'],
						'topic_track_type' => ($tracker['notification']) ? 'delayed' : 'none',
						'start_date'=> $tracker['dateline']
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
							'order'		=> 'pid ASC',
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
				$edit = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'username', 'from' => 'users', 'where' => 'uid='.$row['edituid']));

				$save = array(
					'topic_id'		=> $row['tid'],
					'post_title'	=> $row['subject'],
					'author_id'		=> $row['uid'],
					'author_name'	=> $row['username'],
					'post_date'		=> $row['dateline'],
					'post'			=> $this->fixPostData($row['message']),
					'ip_address'	=> $row['ipaddress'],
					'use_sig'		=> $row['includesig'],
					'use_emo'		=> ($row['smilieoff'] == 1) ? 0 : 1,
					'edit_name'		=> $edit['username'],
					'edit_time'		=> $row['edittime'],
					'queued'		=> ($row['visible'] == 1) ? 0 : 1,
					);

				$this->lib->convertPost($row['pid'], $save);

				//-----------------------------------------
				// Report Center
				//-----------------------------------------

				$link = $this->lib->getLink($row['tid'], 'topics', true);
				if(!$link)
				{
					continue;
				}

				$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='post'" ) );
				$topic = $this->DB->buildAndFetch( array( 'select' => 'title', 'from' => 'topics', 'where' => 'tid='.$link ) );

				$rs = array(	'select' 	=> '*',
								'from' 		=> 'reportedposts',
								'order'		=> 'rid ASC',
								'where'		=> 'pid='.$row['pid']
							);

				ipsRegistry::DB('hb')->build($rs);
				ipsRegistry::DB('hb')->execute();
				$reports = array();
				while ($rget = ipsRegistry::DB('hb')->fetch())
				{
					$report = array(
						'id'			=> $rget['rid'],
						'title'			=> "Reported post #{$row['pid']}",
						'status'		=>	($rget['reportstatus']) ? $complete['status'] : $new['status'],
						'rc_class'		=> $rc['com_id'],
						'updated_by'	=> $rget['uid'],
						'date_updated'	=> $rget['dateline'],
						'date_created'	=> $rget['dateline'],
						'exdat1'		=> $row['fid'],
						'exdat2'		=> $row['tid'],
						'exdat3'		=> $row['pid'],
						'num_reports'	=> '1',
						'num_comments'	=> '0',
						'seoname'		=> IPSText::makeSeoTitle( $topic['title'] ),
						);

					$reports = array(
						array(
								'id'			=> $rget['rid'],
								'report'		=> $rget['reason'],
								'report_by'		=> $rget['uid'],
								'date_reported'	=> $rget['dateline']
							)
						);

					$this->lib->convertReport('post', $report, $reports, false);
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
				// What forum is this in?
				//-----------------------------------------

				$topic = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'fid, uid', 'from' => 'threads', 'where' => "tid='{$row['tid']}'" ) );
if ( !$topic ) { continue; }
				//-----------------------------------------
				// Convert votes
				//-----------------------------------------

				$votes = array();

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'pollvotes', 'where' => "pid={$row['pid']}"));
				ipsRegistry::DB('hb')->execute();
				while ($voter = ipsRegistry::DB('hb')->fetch())
				{
					// Do we already have this user's votes
					if (in_array($voter['userid'], $votes))
					{
						continue;
					}

					// Get their other votes
					ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'pollvotes', 'where' => "pid={$row['pid']} AND uid={$voter['uid']}"));
					ipsRegistry::DB('hb')->execute();
					while ($thevote = ipsRegistry::DB('hb')->fetch())
					{
						$choice[$thevote['votetype']] = $thevote['voteoption'];
					}

					$vsave = array(
						'vote_date'		=> $voter['dateline'],
						'tid'			=> $row['tid'],
						'member_id'		=> $voter['uid'],
						'forum_id'		=> $topic['fid'],
						'member_choices'=> serialize(array(1 => $choice)),
						);

					$this->lib->convertPollVoter($voter['vid'], $vsave);

				}

				//-----------------------------------------
				// Then we can do the actual poll
				//-----------------------------------------

				$poll_array = array(
					// MyBB only allows one question per poll
					1 => array(
						'question'	=> $row['question'],
						'multi'		=> $row['multiple'],
						'choice'	=> explode('||~|~||', $row['options']),
						'votes'		=> explode('||~|~||', $row['votes']),
						)
					);

				$save = array(
					'tid'			=> $row['tid'],
					'start_date'	=> $row['dateline'],
					'choices'   	=> addslashes(serialize($poll_array)),
					'starter_id'	=> $topic['uid'],
					'votes'     	=> $row['numvotes'],
					'forum_id'  	=> $topic['fid'],
					'poll_question'	=> $row['question'],
					'poll_view_voters' => $row['public'],
					);

				$this->lib->convertPoll($row['pid'], $save);
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
							'from' 		=> 'attachtypes',
						);

			$loop = $this->lib->load('attachments_type', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'atype_extension'	=> $row['extension'],
					'atype_mimetype'	=> $row['mimetype'],
					);

				$this->lib->convertAttachType($row['atid'], $save);
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
							'order'		=> 'aid ASC',
						);

			$loop = $this->lib->load('attachments', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_mybb/uploads):')), 'path');

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
				// What's the extension?
				$e = explode('.', $row['filename']);
				$extension = array_pop( $e );

				// Is this an image?
				$image = false;
				if (preg_match('/image/', $row['filetype']))
				{
					$image = true;
				}
				
				$topic	= ipsRegistry::DB('hb')->buildAndFetch(
					array(
						'select'	=> 'tid',
						'from'		=> 'posts',
						'where'		=> 'pid=' . $row['pid']
					)
				);
				
				$ipbTopic = 0;
				
				// Now we have the foreign ID, grab our proper one
				if ( $topic['tid'] )
				{
					$ipbTopic = $this->lib->getLink( $topic['tid'], 'topics' );
				}
				
				// Sort out data
				$save = array(
					'attach_ext'			=> $extension,
					'attach_file'			=> $row['filename'],
					'attach_location'		=> $row['attachname'],
					'attach_is_image'		=> $image,
					'attach_hits'			=> $row['downloads'],
					'attach_date'			=> $row['dateuploaded'],
					'attach_member_id'		=> $row['uid'],
					'attach_approved'		=> $row['visible'],
					'attach_filesize'		=> $row['filesize'],
					'attach_rel_id'			=> $row['pid'],
					'attach_rel_module'		=> 'post',
					'attach_parent_id'		=> $ipbTopic,
					);

				// Send em on
				$done = $this->lib->convertAttachment($row['aid'], $save, $path);

				// Fix inline attachments
				if ($done === true)
				{
					$aid = $this->lib->getLink($row['aid'], 'attachments');
					$pid = $this->lib->getLink($save['attach_rel_id'], 'posts');

					// Got the post ID?
					if ( $pid )
					{
						$attachrow = $this->DB->buildAndFetch( array( 'select' => 'post', 'from' => 'posts', 'where' => "pid={$pid}" ) );
						$update = preg_replace("/\[attachment={$row['aid']}\]/i", "[attachment={$aid}:{$save['attach_location']}]", $attachrow['post']);
						$this->DB->update('posts', array('post' => $update), "pid={$pid}");
					}
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

			$main = array(	'select' 	=> '*',
							'from' 		=> 'privatemessages',
							'order'		=> 'pmid ASC',
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
					'msg_id'			=> $row['pmid'],
					'msg_topic_id'      => $row['pmid'],
					'msg_date'          => $row['dateline'],
					'msg_post'          => $this->fixPostData($row['message']),
					'msg_post_key'      => md5(microtime()),
					'msg_author_id'     => $row['fromid'],
					'msg_is_first_post' => 1
					);

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$maps = array(
					array(
					'map_user_id'     => $row['uid'],
					'map_topic_id'    => $row['pmid'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => 0,
					'map_last_topic_reply' => $row['dateline'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => ($row['receipt'] == 0) ? 1 : 0,
					'map_is_system'   => 0,
					'map_is_starter'  => ( $row['fromid'] == $to['toid'] ) ? 1 : 0
					)
				);

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$topic = array(
					'mt_id'			     => $row['pmid'],
					'mt_date'		     => $row['dateline'],
					'mt_title'		     => $row['subject'],
					'mt_starter_id'	     => $row['fromid'],
					'mt_start_time'      => $row['dateline'],
					'mt_last_post_time'  => $row['dateline'],
					'mt_invited_members' => serialize( array( $row['toid'] => $row['toid'] ) ),
					'mt_to_count'		 => 1,
					'mt_to_member_id'	 => $row['toid'],
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
							'order'		=> 'aid ASC',
						);

			$loop = $this->lib->load('announcements', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'announce_title'	=> $row['subject'],
					'announce_post'		=> $this->fixPostData($row['message']),
					'announce_forum'	=> ($row['fid'] <= 0) ? '*' : $row['fid'],
					'announce_member_id'=> $row['uid'],
					'announce_html_enabled' => $row['allowhtml'],
					'announce_start'	=> $row['startdate'],
					'announce_end'		=> $row['enddatw'],
					);
				$this->lib->convertAnnouncement($row['aid'], $save);
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
							'order'		=> 'bid ASC',
						);

			$loop = $this->lib->load('badwords', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'type'		=> $row['badword'],
					'swop'		=> $row['replacement'],
					'm_exact'	=> '1',
					);
				$this->lib->convertBadword($row['bid'], $save);
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
								'from' 		=> 'banfilters',
								'order'		=> 'fid ASC',
							);

			$loop = $this->lib->load('banfilters', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'ban_content'	=> $row['filter'],
					'ban_date'		=> $row['dateline'],
					);

				switch ($row['type'])
				{
					case 1:
						$save['ban_type'] = 'ip';
						break;

					case 2:
						$save['ban_type'] = 'name';
						break;

					case 3:
						$save['ban_type'] = 'email';
						break;
				}

				$this->lib->convertBan($row['fid'], $save);
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
							'from' 		=> 'profilefields',
							'order'		=> 'fid ASC',
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
				$group = $this->lib->convertPFieldGroup(1, array('pf_group_name' => 'Converted', 'pf_group_key' => 'mybb'), true);
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
				$e = explode('\n', $row['type']);
				switch($e[0]) {
					case 'textarea':	$type = 'textarea';		break;
					case 'select':		$type = 'drop';			break;
					case 'radio':		$type = 'radio';		break;
					default: $type = 'input';
				}

				// What are the options?
				$content = '';
				if (isset($e[1]))
				{
					foreach ($e as $key => $value)
					{
						if ($key == 0)
						{
							continue;
						}
						$id = $key-1;
						$content_array[] = "{$id}={$value}";
					}
					$content = implode('|', $content_array);
				}

				// Insert
				$save = array(
					'pf_title'			=> $row['name'],
					'pf_desc'			=> $row['description'],
					'pf_content'		=> $content,
					'pf_type'			=> $type,
					'pf_not_null'		=> $row['required'],
					'pf_member_hide'	=> $row['hidden'],
					'pf_max_input'		=> $row['maxlength'],
					'pf_member_edit'	=> $row['editable'],
					'pf_position'		=> $row['disporder'],
					'pf_show_on_reg'	=> $row['required'],
					'pf_group_id'		=> 1,
					'pf_key'			=> 'fid'.$row['fid'],
					);

				$this->lib->convertPField($row['fid'], $save);
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

			$this->lib->saveMoreInfo('emoticons', array('emo_path', 'emo_opt'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'smilies',
							'order'		=> 'sid ASC',
						);

			$loop = $this->lib->load('emoticons', $main);

			//-----------------------------------------
			// We need to know the path and how to handle duplicates
			//-----------------------------------------

			$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_mybb/images/smilies):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

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
				$row['image'] = preg_replace('/(.*)\/(.*)\.(.*)/', '$2.$3', $row['image']);
				$save = array(
					'typed'		=> $row['find'],
					'image'		=> $row['image'],
					'clickable'	=> $row['showclickable'],
					'emo_set'	=> 'default',
					);
				$done = $this->lib->convertEmoticon($row['sid'], $save, $us['emo_opt'], $path);
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

			/*$main = array(	'select' 	=> 'm.*',
							'from'		=> array( 'moderators' => 'm' ),
							'add_join'	=> array(
											array(	'select' => 'mem.username',
							 						'from'   => array( 'users' => 'mem' ),
							 						'where'  => 'm.uid = mem.uid',
							 						'type'   => 'inner'
												),
											),
							'order'		=> 'mid ASC',
						);*/
			
			$main = array (
				'select'	=> '*',
				'from'		=> 'moderators',
				'order'		=> 'mid ASC',
			);
			
			// array caching ftw
			$users = array ( );
			ipsRegistry::DB ( 'hb' )->build ( array (
				'select'	=> 'uid, username',
				'from'		=> 'users',
				'order'		=> 'uid ASC',
			) );
			$usrqry = ipsRegistry::DB ( 'hb' )->execute ( );
			while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $usrqry ) )
			{
				$users[$row['uid']] = $row;
			}
			ipsRegistry::DB ( 'hb' )->freeResult ( $usrqry );
			
			$groups = array ( );
			ipsRegistry::DB ( 'hb' )->build ( array (
				'select'	=> 'gid, title',
				'from'		=> 'usergroups',
				'order'		=> 'gid ASC'
			) );
			$grpqry = ipsRegistry::DB ( 'hb' )->execute ( );
			while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $grpqry ) )
			{
				$groups[$row['gid']] = $row;
			}
			ipsRegistry::DB ( 'hb' )->freeResult ( $grpqry );

			$loop = $this->lib->load('moderators', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				if ( $row['isgroup'] == 1 )
				{
					$save = array (
						'is_group'		=> 1,
						'group_id'		=> $row['id'],
						'group_name'	=> $groups[$row['id']]['title'],
					);
				}
				else
				// Didn't work without an elseif...
				if ( $row['isgroup'] == 0 )
				{
					$save = array (
						'is_group'		=> 0,
						'member_name'	=> $users[$row['id']]['username'],
						'member_id'		=> $row['id'],
					);
				}

				$save = array_merge ( $save, array (
						'forum_id'		=> $row['fid'],
						'edit_post'		=> $row['caneditposts'],
						'edit_topic'	=> $row['caneditposts'],
						'delete_post'	=> $row['candeleteposts'],
						'delete_topic'	=> $row['candeleteposts'],
						'view_ip'		=> $row['canviewips'],
						'open_topic'	=> $row['canopenclosethreads'],
						'close_topic'	=> $row['canopenclosethreads'],
						'mass_move'		=> $row['canmovetonommodforum'],
						'mass_prune'	=> $row['candeleteposts'],
						'move_topic'	=> $row['canmovetonommodforum'],
						'pin_topic'		=> $row['canmanagethreads'],
						'unpin_topic'	=> $row['canmanagethreads'],
						'post_q'		=> 1,
						'topic_q'		=> 1,
						'allow_warn'	=> 0,
						'edit_user'		=> 0,
						'split_merge'	=> $row['canmanagethreads']
				) );

				$this->lib->convertModerator($row['mid'], $save);
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
							'from' 		=> 'modtools',
							'order'		=> 'tid ASC',
						);

			$loop = $this->lib->load('topic_mmod', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$options = unserialize($row['threadoptions']);
				preg_match('/(.*){subject}(.*)/', $options['newsubject'], $matches);

				if ($options['approvethread'] == 'approve')
				{
					$topic_approve = 1;
				}
				elseif ($options['approvethread'] == 'unapprove')
				{
					$topic_approve = 2;
				}
				else
				{
					$topic_approve = 0;
				}

				$save = array(
					'mm_title'			=> $row['name'],
					'mm_enabled'		=> 1,
					'topic_state'		=> ($options['openthread'] == 'open' or $options['openthread'] == 'close') ? $options['openthread'] : 'leave',
					'topic_move'		=> ($options['movethread']) ? $options['movethread'] : -1,
					'topic_move_link'	=> $options['movethreadredirect'],
					'topic_title_st'	=> $matches[1],
					'topic_title_end'	=> $matches[2],
					'topic_reply'		=> ($options['addreply']) ? 1 : 0,
					'topic_reply_content' => $this->fixPostData($options['addreply']),
					'mm_forums'			=> ($row['forums']) ? $row['forums'] : '*',
					'topic_approve'		=> $topic_approve,
					);
				$this->lib->convertMultiMod($row['tid'], $save);
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
							'from' 		=> 'usertitles',
							'order'		=> 'utid ASC',
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
					'posts'	=> $row['posts'],
					'title'	=> $row['title'],
					'pips'	=> $row['stars'],
					);
				$this->lib->convertRank($row['utid'], $save, $us['rank_opt']);
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
							'from' 		=> 'threadratings',
							'order'		=> 'rid ASC',
						);

			$loop = $this->lib->load('topic_ratings', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'rating_tid'		=> $row['tid'],
					'rating_member_id'	=> $row['uid'],
					'rating_value'		=> $row['rating'],
					'rating_ip_address'	=> $row['ipaddress'],
					);
				$this->lib->convertTopicRating($row['rid'], $save);
			}

			$this->lib->next();

		}

		/**
		 * Convert warn logs
		 *
		 * @return void
		 **/
		private function convert_warn_logs()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'warnings',
							'order'		=> 'wid ASC',
						);

			$loop = $this->lib->load('warn_logs', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				//-----------------------------------------
				// The actual warn
				//-----------------------------------------

				$save = array(
					'wlog_mid'		=> $row['uid'],
					'wlog_notes'	=> serialize(array('content' => $row['title'])),
					'wlog_date'		=> $row['dateline'],
					'wlog_type'		=> 'neg',
					'wlog_addedby'	=> $row['issuedby']
					);

				$this->lib->convertWarn($row['wid'], $save);

				//-----------------------------------------
				// Was it revoked?
				//-----------------------------------------

				if ($row['expired'])
				{
					$revoke = array(
						'wlog_mid'		=> $row['uid'],
						'wlog_notes'	=> serialize(array('content' => $row['revokereason'])),
						'wlog_date'		=> $row['daterevoked'],
						'wlog_type'		=> 'pos',
						'wlog_addedby'	=> $row['revokedby']
						);

					$this->lib->convertWarn($row['wid'].'r', $revoke);
				}

			}

			$this->lib->next();

		}


	}
