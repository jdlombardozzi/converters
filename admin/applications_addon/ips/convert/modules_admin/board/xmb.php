<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * XMB
 * Last Update: $Date: 2010-03-19 11:03:12 +0100(ven, 19 mar 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 437 $
 */

	$info = array(
		'key'	=> 'xmb',
		'name'	=> 'XMB 1.9',
		'login'	=> false,
	);

	class admin_convert_board_xmb extends ipsCommand
	{

		/**
		 * Bitwise settings - Permissions
		 *
		 * @access	private
		 * @var 	array
		 **/
		private $status_enum = array(
			'Super Administrator' => 1,
			'Administrator'       => 2,
			'Super Moderator'     => 4,
			'Moderator'           => 8,
			'Member'              => 16,
			'Guest'               => 32,
			);


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
				'forum_perms'	=> array(),
				'groups' 		=> array('forum_perms'),
				'members'		=> array('groups'),
				'profile_friends' => array('members'),
				'forums'		=> array('forum_perms'),
				'topics'		=> array('members', 'forums'),
				'posts'			=> array('members', 'topics', 'emoticons'),
				'polls'			=> array('topics', 'members', 'forums'),
				'pms'			=> array('members', 'emoticons'),
				'attachments'	=> array('posts'),
				'ranks'			=> array(),
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
			$this->lib->sendHeader( 'XMB &rarr; IP.Board Converter' );

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
				case 'forum_perms':
				case 'groups':
					return $this->lib->countRows('ranks');
					break;

				case 'topics':
					return $this->lib->countRows('threads');
					break;

				case 'polls':
					return $this->lib->countRows('vote_desc');
					break;

				case 'pms':
					return $this->lib->countRows('u2u', "type='incoming'");
					break;

				case 'attachments':
					return $this->lib->countRows('attachments', 'parentid=0');
					break;

				case 'banfilters':
					return $this->lib->countRows('banned');
					break;

				case 'profile_friends':
					return $this->lib->countRows('buddys');
					break;

				case 'emoticons':
					return $this->lib->countRows('smilies', "type='smiley'");
					break;

				case 'badwords':
					return $this->lib->countRows('words');
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
							'from' 		=> 'ranks',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'id', 'nf' => 'title'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['id'], $row['title']);
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
							'from' 		=> 'ranks',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'id', 'nf' => 'title'));

			//---------------------------
			// Loop
			//---------------------------

			$groups = array();

			// Loop
			foreach( $loop as $row )
			{
				$save = array(
					'g_title'			=> $row['title'],
					'g_perm_id'			=> $row['id'],
					'g_avatar_upload'	=> ($row['allowavatars'] == 'yes') ? 1 : 0,
					);
				$this->lib->convertGroup($row['id'], $save);
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
				'location'		=> 'Location',
				'aim'			=> 'AIM ID',
				'site'			=> 'Website',
				'bio'			=> 'Bio',
				'icq'			=> 'ICQ Number',
				'yahoo'			=> 'Yahoo ID',
				'msn'			=> 'MSN ID',
				);

			$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('pp_path', 'gal_path', 'avatar_salt')));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'members',
							'order'		=> 'uid ASC',
						);

			$loop = $this->lib->load('members', $main);

			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();

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
			// Load 'ranks'
			//---------------------------

			$ranks = array();
			ipsRegistry::DB('hb')->build( array( 'select' => '*', 'from' => 'ranks' ) );
			ipsRegistry::DB('hb')->execute();
			while($rank = ipsRegistry::DB('hb')->fetch())
			{
				$ranks[ $rank['title'] ] = $rank['id'];
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
					'group'				=> ( $ranks[ $row['status'] ] ) ? $ranks[ $row['status'] ] : $this->settings['member_group'],
					'joined'			=> $row['regdate'],
					'username'			=> $row['username'],
					'email'				=> $row['email'],
					'md5pass'			=> $row['password'],
					);

				// Member info
				$birthday = explode('-', $row['bday']);

				$members = array(
					'posts'				=> $row['postnum'],
					'hide_email' 		=> ($row['showemail'] == 'yes') ? 0 : 1,
					'time_offset'		=> $row['timeoffset'],
					'title'				=> $rank['customstatus'],
					'bday_day'			=> ($row['bday'] != '0000-00-00') ? $birthday[2] : '',
					'bday_month'		=> ($row['bday'] != '0000-00-00') ? $birthday[1] : '',
					'bday_year'			=> ($row['bday'] != '0000-00-00') ? $birthday[0] : '',
					'allow_admin_mails'	=> ($row['newsletter'] == 'yes') ? 1 : 0,
					'ip_address'		=> $row['regip'],
					'members_disable_pm'=> ($row['ban'] == 'u2u' or $row['ban'] == 'both') ? 1 : 0,
					'restrict_post'		=> ($row['ban'] == 'posts' or $row['ban'] == 'both') ? 1 : 0,
					'last_visit'		=> $row['lastvisit'],
					'email_pm'      	=> ($row['emailonu2u'] == 'yes') ? 1 : 0,
					);

				// Profile
				$profile = array(
					'signature'			=> $this->fixPostData($row['sig']),
					);

				//-----------------------------------------
				// Avatars
				//-----------------------------------------

				if ($row['avatar'])
				{
					$profile['avatar_type'] = 'url';
					$profile['avatar_location'] = $row['avatar'];
				}

				//-----------------------------------------
				// And go!
				//-----------------------------------------

				$this->lib->convertMember($info, $members, $profile, array(), '');
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

			$loop = $this->lib->load('forums', $main);


			// Do we need to create a forum?
			if(!$this->request['st'] AND !$this->lib->getLink('master', 'forums', true))
			{
				$this->lib->convertForum('master', array( 'name' => 'XMB Forums', 'parent_id' => -1 ), array('view' => '*') );
			}

			//---------------------------
			// Load 'ranks'
			//---------------------------

			$ranks = array();
			ipsRegistry::DB('hb')->build( array( 'select' => '*', 'from' => 'ranks' ) );
			ipsRegistry::DB('hb')->execute();
			while($rank = ipsRegistry::DB('hb')->fetch())
			{
				$ranks[ $rank['title'] ] = $rank['id'];
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//---------------------------
				// Permissions
				//---------------------------

				$perms = array();
				$view = array();
				$reply = array();
				$start = array();

				// 0=polls, 1=threads, 2=replies, 3=view
				$localperms = explode(',', $row['postperm']);

				foreach($this->status_enum as $name => $key)
				{
					if( !$ranks[ $name ] )
					{
						continue;
					}

					if($localperms[1] & $key)
					{
						$start[] = $ranks[ $name ];
					}
					if($localperms[2] & $key)
					{
						$reply[] = $ranks[ $name ];
					}
					if($localperms[3] & $key)
					{
						$view[] = $ranks[ $name ];
					}
				}

				$perms['view']	= implode(',', $view);
				$perms['read']	= implode(',', $view);
				$perms['reply'] = implode(',', $reply);
				$perms['start'] = implode(',', $start);
				$perms['upload'] = implode(',', $reply);
				$perms['download'] = implode(',', $view);

				//-----------------------------------------
				// And go
				//-----------------------------------------

				$save = array(
					'sub_can_post'	=> ($row['type'] == 'cat') ? 0 : 1,
					'name'			=> $row['name'],
					'status'		=> ($row['status'] == 'on') ? 1 : 0,
					'description'	=> $row['description'],
					'position'		=> $row['displayorder'],
					'use_html'		=> ($row['allowhtml'] == 'yes') ? 1 : 0,
					'posts'			=> $row['posts'],
					'topics'		=> $row['threads'],
					'parent_id'		=> ($row['fup']) ? $row['fup'] : 'master',
					'password'		=> $row['password'],
					);

				$this->lib->convertForum($row['fid'], $save, $perms);

				//---------------------------
				// Don't forget the mods!
				//---------------------------

				$mods = explode(', ', $row['moderator']);

				foreach($mods as $mod)
				{
					$modinfo = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'uid, username', 'from' => 'members', 'where' => "username='{$mod}'" ) );
					if($user)
					{
						$savemod = array(
							   'forum_id'	  => $row['fid'],
							   'member_name'  => $modinfo['username'],
							   'member_id'	  => $modinfo['uid'],
							 );
						$this->lib->convertModerator($row['fid'].'-'.$mod, $savemod);
					}
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

			$loop = $this->lib->load('topics', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// We need to get some info
				$author = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'uid', 'from' => 'members', 'where' => "username='{$row['author']}'" ) );
				$firstpost = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'posts', 'where' => "tid={$row['tid']}", 'order' => 'dateline DESC' ) );

				$save = array(
					'forum_id'		 	=> $row['fid'],
					'title'		 		=> $row['subject'],
					'views'			 	=> $row['views'],
					'posts'			 	=> $row['replies'],
					'starter_name'	 	=> $row['author'],
					'starter_id'		=> $author['uid'],
					'forum_id'		 	=> $row['fid'],
					'state'				=> $row['closed'] ? 'closed' : 'open',
					'approved'			=> 1,
					'start_date'		=> $firstpost['dateline'],
					'poll_state'		=> $row['pollopts'],
					);

				$this->lib->convertTopic($row['tid'], $save);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'favorites', 'where' => "tid={$row['tid']} AND type='subscription'"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$user = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'uid', 'from' => 'members', 'where' => "username='{$tracker['username']}'" ) );
					$savetracker = array(
						'member_id'	=> $user['uid'],
						'topic_id'	=> $tracker['tid'],
						);
					$this->lib->convertTopicSubscription($tracker['tid'].'-'.$user['uid'], $savetracker);
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

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// We need to get some info
				$author = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'uid', 'from' => 'members', 'where' => "username='{$row['author']}'" ) );

				$save = array(
					'author_name'	 	=> $row['author'],
					'author_id'			=> $author['uid'],
					'topic_id'			=> $row['tid'],
					'post'				=> $this->fixPostData( $row['message'] ),
					'post_date'			=> $row['dateline'],
					'use_sig'			=> ($row['usesig'] == 'yes') ? 1 : 0,
					'ip_address'		=> $row['useip'],
					'use_emo'			=> ($row['smileyoff'] == 'yes') ? 0 : 1,
					);

				$this->lib->convertPost($row['pid'], $save);

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
							'from' 		=> 'u2u',
							'where'		=> "type='incoming'",
							'order'		=> 'u2uid ASC',
						);

			$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				// We need to get some info
				$from = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'uid', 'from' => 'members', 'where' => "username='{$row['msgfrom']}'" ) );
				$to = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'uid', 'from' => 'members', 'where' => "username='{$row['msgto']}'" ) );

				//-----------------------------------------
				// Post Data
				//-----------------------------------------

				$post = array(
					'msg_id'			=> $row['u2uid'],
					'msg_topic_id'      => $row['u2uid'],
					'msg_date'          => $row['dateline'],
					'msg_post'          => $this->fixPostData($row['message']),
					'msg_post_key'      => md5(microtime()),
					'msg_author_id'     => $from['uid'],
					'msg_is_first_post' => 1
					);

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$map_master = array(
					'map_topic_id'    => $row['u2uid'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => 0,
					'map_last_topic_reply' => $row['dateline'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => 0,
					'map_is_system'   => 0,
					);

				$maps = array();
				$maps[] = array_merge( $map_master, array( 'map_user_id' => $from['uid'], 'map_is_starter' => 1 ) );
				$maps[] = array_merge( $map_master, array( 'map_user_id' => $to['uid'], 'map_is_starter' => 0 ) );

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$explode = explode(':', $row['to_address']);
				$recipient = array_shift($explode);
				$recipient = str_replace('u_', '', $recipient);

				$topic = array(
					'mt_id'			     => $row['u2uid'],
					'mt_date'		     => $row['dateline'],
					'mt_title'		     => $row['subject'],
					'mt_starter_id'	     => $from['uid'],
					'mt_start_time'      => $row['dateline'],
					'mt_last_post_time'  => $row['dateline'],
					'mt_invited_members' => serialize( array( $to['uid'], $from['uid'] ) ),
					'mt_to_count'		 => 2,
					'mt_to_member_id'	 => $to['uid'],
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
							'where'		=> 'parentid=0',
							'order'		=> 'aid ASC',
						);

			$loop = $this->lib->load('attachments', $main);

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

				$save = array(
					'attach_ext'			=> $extension,
					'attach_file'			=> $row['filename'],
					'attach_is_image'		=> $image,
					'attach_hits'			=> $row['downloads'],
					'attach_date'			=> $row['dateline'],
					'attach_member_id'		=> $row['uid'],
					'attach_filesize'		=> $row['filesize'],
					'attach_rel_id'			=> $row['pid'],
					'attach_rel_module'		=> 'post',
					'attach_location'		=> $row['filename'],
					'data'					=> $row['attachment'],
					);

				$this->lib->convertAttachment($row['aid'], $save, '', true);

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
								'from' 		=> 'banned',
								'order'		=> 'id ASC',
							);

			$loop = $this->lib->load('banfilters', $main, array());

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'ban_type'		=> 'ip',
					'ban_content'	=> $row['ip1'].'.'.$row['ip2'].'.'.$row['ip3'].'.'.$row['ip4'],
					'ban_date'		=> $row['dateline'],
					);
				$this->lib->convertBan($row['id'], $save);
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
							'from' 		=> 'buddys',
						);

			$loop = $this->lib->load('profile_friends', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// We need to get some info
				$user = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'uid', 'from' => 'members', 'where' => "username='{$row['username']}'" ) );
				$buddy = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'uid', 'from' => 'members', 'where' => "username='{$row['buddyname']}'" ) );

				// Now go!
				$save = array(
					'friends_member_id'	=> $user['uid'],
					'friends_friend_id'	=> $buddy['uid'],
					'friends_approved'	=> '1',
					);
				$this->lib->convertFriend($user['uid'].'-'.$buddy['uid'], $save);
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
							'from' 		=> 'ranks',
							'order'		=> 'id ASC',
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
				$this->lib->convertRank($row['id'], $save, $us['rank_opt']);
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
							'where'		=> "type='smiley'",
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('emoticons', $main);

			//-----------------------------------------
			// We need to know the path and how to handle duplicates
			//-----------------------------------------

			$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_xmb/images/smilies):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

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
					'typed'		=> $row['code'],
					'image'		=> $row['url'],
					'clickable'	=> 1,
					'emo_set'	=> 'default',
					);
				$done = $this->lib->convertEmoticon($row['id'], $save, $us['emo_opt'], $path);
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
							'from' 		=> 'vote_desc',
							'order'		=> 'vote_id ASC',
						);

			$loop = $this->lib->load('polls', $main, array('voters'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				//---------------------------
				// We need some info...
				//---------------------------

				$topic = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'fid', 'from' => 'threads', 'where' => "tid='{$row['topic_id']}'" ) );

				//-----------------------------------------
				// Options are stored in one place...
				//-----------------------------------------

				$choice = array();
				$votes = array();

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'vote_results', 'where' => "vote_id={$row['vote_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($options = ipsRegistry::DB('hb')->fetch())
				{
					$choice[ $options['vote_option_id'] ]	= $options['vote_option_text'];
					$votes[ $options['vote_option_id'] ]	= $options['vote_result'];
					$total_votes[] = $options['vote_result'];
				}

				//-----------------------------------------
				// Then we can do the actual poll
				//-----------------------------------------

				$poll_array = array(
					// XMB only allows one question per poll
					1 => array(
						'question'	=> $row['vote_text'],
						'choice'	=> $choice,
						'votes'		=> $votes,
						)
					);

				$save = array(
					'tid'			=> $row['topic_id'],
					//'start_date'	=> $row['topic_time'],
					'choices'   	=> addslashes(serialize($poll_array)),
					//'starter_id'	=> $row['topic_poster'],
					'votes'     	=> array_sum($total_votes),
					'forum_id'  	=> $topic['fid'],
					'poll_question'	=> $row['vote_text']
					);

				$this->lib->convertPoll($row['vote_id'], $save);
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
							'from' 		=> 'words',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('badwords', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'type'		=> $row['find'],
					'swop'		=> $row['replace1'],
					'm_exact'	=> '1',
					);
				$this->lib->convertBadword($row['id'], $save);
			}

			$this->lib->next();

		}

	}

