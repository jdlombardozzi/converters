<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * PHP-Fusion
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
		'key'	=> 'phpfusion',
		'name'	=> 'PHP-Fusion 6.01',
		'login'	=> true,
	);

	class admin_convert_board_phpfusion extends ipsCommand
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
				'members'		=> array(),
				'forums'		=> array(),
				'topics'		=> array('members', 'forums'),
				'posts'			=> array('members', 'topics'),
				'pms'			=> array('members'),
				'attachments'	=> array('posts'),
				'banfilters'	=> array(),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'PHP-Fusion &rarr; IP.Board Converter' );

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
				case 'members':
					return $this->lib->countRows('users');
					break;

				case 'topics':
					return $this->lib->countRows('threads');
					break;

				case 'pms':
					return $this->lib->countRows('messages', 'message_folder=1');
					break;

				case 'attachments':
					return $this->lib->countRows('forum_attachments');
					break;

				case 'banfilters':
					return $this->lib->countRows('blacklist');
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
			return $post;
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
				'user_location'	=> 'Location',
				'user_icq'		=> 'ICQ Number',
				'user_aim'		=> 'AIM ID',
				'user_yahoo'	=> 'Yahoo ID',
				'user_msn'		=> 'MSN ID',
				'user_web'		=> 'Website',
				);

			$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('pp_path', 'gal_path', 'avatar_salt')));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'users',
							'order'		=> 'user_id ASC',
						);

			$loop = $this->lib->load('members', $main);

			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();

			$ask['pp_path']  	= array('type' => 'text', 'label' => 'Path to avatars uploads folder (no trailing slash, default /path_to_phpfusion/images/avatars): ');

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
					'id'				=> $row['user_id'],
					'group'				=> ( $row['user_level'] == 103 and $this->settings['admin_group'] ) ? $this->settings['admin_group'] : $this->settings['member_group'],
					'joined'			=> $row['user_joined'],
					'username'			=> $row['user_name'],
					'email'				=> $row['user_email'],
					'password'			=> $row['user_password'],
					);

				// Member info
				$birthday = explode('-', $row['user_birthdate']);

				$members = array(
					'hide_email'		=> $row['user_hide_email'],
					'bday_day'			=> ($row['bday'] != '0000-00-00') ? $birthday[2] : '',
					'bday_month'		=> ($row['bday'] != '0000-00-00') ? $birthday[1] : '',
					'bday_year'			=> ($row['bday'] != '0000-00-00') ? $birthday[0] : '',
					'time_offset'		=> str_replace( '+', '', $row['user_offset']),
					'posts'				=> $row['user_posts'],
					'last_visit'		=> $row['user_lastvisit'],
					'last_activity' 	=> $row['user_lastvisit'],
					'ip_address'		=> $row['user_ip'],
					);

				// Profile
				$profile = array(
					'signature'			=> $this->fixPostData($row['user_sig']),
					);

				//-----------------------------------------
				// Avatars
				//-----------------------------------------

				if ($row['user_avatar'])
				{
					$profile['avatar_type'] = 'upload';
					$profile['avatar_location'] = $row['user_avatar'];
				}

				//-----------------------------------------
				// And go!
				//-----------------------------------------

				$this->lib->convertMember($info, $members, $profile, array(), $us['pp_path'], '', FALSE);
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

			$loop = $this->lib->load('forums', $main, array('forum_tracker'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Permissions will need to be reconfigured
				$perms = array();

				$save = array(
					'parent_id'		=> ($row['forum_cat']) ? $row['forum_cat'] : -1,
					'position'		=> $row['forum_order'],
					'name'			=> $row['forum_name'],
					'description'	=> $row['forum_description'],
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
							'from' 		=> 'threads',
							'order'		=> 'thread_id ASC',
						);

			$loop = $this->lib->load('topics', $main, array('tracker'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'title'				=> $row['thread_subject'],
					'state'		   	 	=> $row['thread_locked'] == 0 ? 'open' : 'closed',
					'posts'		    	=> $row['topic_replies'],
					'starter_id'    	=> $row['thread_author'],
					'start_date'    	=> $row['topic_time'],
					'last_post' 	    => $row['thread_lastpost'],
					'last_poster_id'	=> $row['thread_lastuser'],
					'views'			 	=> $row['thread_views'],
					'forum_id'		 	=> $row['forum_id'],
					'approved'		 	=> 1,
					'author_mode'	 	=> 1,
					'pinned'		 	=> $row['thread_sticky'],
					);

				$this->lib->convertTopic($row['thread_id'], $save);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'thread_notify', 'where' => "thread_id={$row['thread_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$savetracker = array(
						'member_id'	=> $tracker['notify_user'],
						'topic_id'	=> $tracker['thread_id'],
						'topic_track_type' => 'immediate',
						);
					$this->lib->convertTopicSubscription($tracker['thread_id'].'-'.$tracker['notify_user'], $savetracker);
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
				$save = array(
					'author_id'   => $row['post_author'],
					'use_sig'     => $row['post_showsig'],
					'use_emo'     => $row['post_smileys'],
					'ip_address'  => $row['post_ip'],
					'post_date'   => $row['post_datestamp'],
					'post'		  => $this->fixPostData($row['post_message']),
					'topic_id'    => $row['thread_id']
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
							'from' 		=> 'messages',
							'order'		=> 'message_id ASC',
							'where'		=> 'message_folder=1',
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
					'msg_id'			=> $row['message_id'],
					'msg_topic_id'      => $row['message_id'],
					'msg_date'          => $row['message_datestamp'],
					'msg_post'          => $this->fixPostData($row['message_message']),
					'msg_post_key'      => md5(microtime()),
					'msg_author_id'     => $row['message_from'],
					'msg_ip_address'    => $row['author_ip'],
					'msg_is_first_post' => 1
					);

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$map_master = array(
					'map_topic_id'    => $row['message_id'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => 0,
					'map_last_topic_reply' => $row['message_datestamp'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => 0,
					'map_is_system'   => 0,
					);

				$maps = array();
				$maps[] = array_merge( $map_master, array( 'map_user_id' => $row['message_from'], 'map_is_starter' => 1 ) );
				$maps[] = array_merge( $map_master, array( 'map_user_id' => $row['message_to'], 'map_is_starter' => 0 ) );

				//-----------------------------------------
				// Topic Data
				//-----------------------------------------

				$topic = array(
					'mt_id'			     => $row['message_id'],
					'mt_date'		     => $row['message_time'],
					'mt_title'		     => $row['message_subject'],
					'mt_starter_id'	     => $row['message_from'],
					'mt_start_time'      => $row['message_datestamp'],
					'mt_last_post_time'  => $row['message_datestamp'],
					'mt_invited_members' => serialize( array( $row['message_to'] ) ),
					'mt_to_count'		 => 1,
					'mt_to_member_id'	 => $row['message_to'],
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
								'from' 		=> 'blacklist',
								'order'		=> 'blacklist_id ASC',
							);

			$loop = $this->lib->load('banfilters', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				if ($row['blacklist_ip'])
				{
					$save = array(
						'ban_type'		=> 'ip',
						'ban_content'	=> $row['blacklist_ip'],
						);
					$this->lib->convertBan($row['ban_id'], $save);
				}
				elseif ($row['blacklist_email'])
				{
					$save = array(
						'ban_type'		=> 'email',
						'ban_content'	=> $row['blacklist_email'],
						);
					$this->lib->convertBan($row['ban_id'], $save);
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
							'from' 		=> 'forum_attachments',
							'order'		=> 'attach_id ASC',
						);

			$loop = $this->lib->load('attachments', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_phpfusion/forum/attachments):')), 'path');

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
				$row['attach_ext'] = str_replace( '.', '', $row['attach_ext'] );

				// Is this an image?
				$image = false;
				if ( in_array( $row['attach_ext'], array( 'png', 'jpg', 'jpeg', 'gif' ) ) )
				{
					$image = true;
				}

				// Sort out data
				$save = array(
					'attach_ext'			=> $row['attach_ext'],
					'attach_file'			=> $row['attach_name'],
					'attach_location'		=> $row['attach_name'],
					'attach_is_image'		=> $image,
					'attach_filesize'		=> $row['attach_size'],
					'attach_rel_id'			=> $row['post_id'],
					'attach_rel_module'		=> 'post',
					);


				// Send em on
				$done = $this->lib->convertAttachment($row['attach_id'], $save, $path);
			}

			$this->lib->next();

		}

	}

