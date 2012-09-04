<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * phpMyForum
 * Last Update: $Date: 2011-07-12 21:15:48 +0100 (Tue, 12 Jul 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 550 $
 */

	$info = array(
		'key'	=> 'phpmyforum',
		'name'	=> 'phpMyForum 4.1',
		'login'	=> false,
	);

	class admin_convert_board_phpmyforum extends ipsCommand
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
				'groups' 		=> array('forum_perms'),
				'members'		=> array('groups'),
				'forums'		=> array('members'),
				'moderators'	=> array('members', 'forums'),
				'topics'		=> array('members'),
				'posts'			=> array('members', 'topics'),
				'polls'			=> array('topics', 'members', 'forums'),
				'pms'			=> array('members'),
				'attachments'	=> array('posts'),
				'badwords'		=> array(),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'phpMyForum &rarr; IP.Board Converter' );

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
					return $this->lib->countRows('group');
					break;

				case 'members':
					return $this->lib->countRows('user');
					break;

				case 'forums':
					return $this->lib->countRows('board');
					break;

				case 'topics':
					return $this->lib->countRows('topic');
					break;

				case 'posts':
					return $this->lib->countRows('post');
					break;

				case 'polls':
					return $this->lib->countRows('poll');
					break;

				case 'pms':
					return $this->lib->countRows('private');
					break;

				case 'attachments':
					return $this->lib->countRows('attachment');
					break;

				case 'custom_bbcode':
					return $this->lib->countRows('bbcode');
					break;

				case 'emoticons':
					return $this->lib->countRows('smilie');
					break;

				case 'badwords':
					return $this->lib->countRows('word');
					break;

				case 'pfields':
					return $this->lib->countRows('user_field');
					break;

				case 'moderators':
					return $this->lib->countRows('board_mod');
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
			$post = nl2br($post);
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
							'from' 		=> 'group',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'id', 'nf' => 'name'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['id'], $row['name']);
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
							'from' 		=> 'group',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'id', 'nf' => 'name'));

			//---------------------------
			// Loop
			//---------------------------

			$groups = array();

			// Loop
			foreach( $loop as $row )
			{
				$save = array(
					'g_title'			=> $row['name'],
					'g_perm_id'			=> $row['id'],
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
				'gender'		=> 'Gender',
				'icq'			=> 'ICQ Number',
				);

			$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('pp_path', 'gal_path', 'avatar_salt')));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'user',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('members', $main);

			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();

			$ask['attach_path'] = array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually /path_to_phpmyforum/images/avatars):');

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

			$this->DB->build(array('select' => '*', 'from' => 'conv_link', 'where' => "type='pfields' AND app={$this->lib->app['app_id']}"));
			$this->DB->execute();
			$pfields = array();
			while ($row = $this->DB->fetch())
			{
				$pfields[ $row['foreign_id'] ] = $row['ipb_id'];
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
					'id'				=> $row['id'],
					'group'				=> $row['group_id'],
					'joined'			=> $row['reg'],
					'username'			=> $row['name'],
					'email'				=> $row['email'],
					'md5pass'			=> $row['pass'],
					);

				// Member info
				$birthday = explode('-', $row['geb']);

				$members = array(
					'bday_day'			=> ($row['geb'] != '0000-00-00') ? $birthday[2] : '',
					'bday_month'		=> ($row['geb'] != '0000-00-00') ? $birthday[1] : '',
					'bday_year'			=> ($row['geb'] != '0000-00-00') ? $birthday[0] : '',
					'posts'				=> $row['posts'],
					'last_visit'		=> $row['last_login'],
					'email_pm'			=> $row['pm_email'],
					'hide_email'		=> $row['email_show'] ? 0 : 1,
					'last_activity'		=> $row['last_action'],
					'time_offset'		=> (int) ( substr( $row['time_zone'], 0, 1) == '+' ) ? substr( $row['time_zone'], 1 ) : $row['time_zone'],
					);

				// Profile
				$profile = array(
					'signature'			=> $this->fixPostData($row['sign']),
					);

				//-----------------------------------------
				// Avatars
				//-----------------------------------------

				$path = '';

				if ($row['avatar'])
				{
					// URL
					if ( substr( $row['avatar'], 0, 4) == 'url:' )
					{
						$profile['photo_type'] = 'url';
						$profile['photo_location'] = str_replace( 'url:', '', $row['avatar'] );
					}
					// Upload
					elseif ( substr( $row['avatar'], 0, 7) == 'upload:' )
					{
						$profile['photo_type'] = 'custom';
						$profile['photo_location'] = str_replace( 'upload:', '', $row['avatar'] );
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
				ipsRegistry::DB('hb')->build( array( 'select' => '*', 'from' => 'user_field_value', 'where' => "user_id={$row['id']}" ) );
				ipsRegistry::DB('hb')->execute();
				while( $fieldRow = ipsRegistry::DB('hb')->fetch() )
				{
					if ( array_key_exists( $fieldRow['field_id'], $pfields ) )
					{
						$custom[ 'field_' . $pfields[ $fieldRow['field_id'] ] ] = $fieldRow['value'];
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
							'from' 		=> 'board',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('forums', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				$perms = array();

				$save = array(
					'name'				=> $row['name'],
					'description'		=> $row['info'],
					'topics'			=> $row['topics'],
					'posts'				=> $row['posts'],
					'last_post'			=> $row['last_post_date'],
					'last_poster_id'	=> $row['last_user_id'],
					'parent_id'			=> $row['parent_id'] ? $row['parent_id'] : -1,
					'redirect_url'		=> $row['redirect_url'],
					'redirect_on'		=> $row['is_board'] == 2 ? 1 : 0,
					'redirect_hits'		=> $row['redirects'],
					'sub_can_post'		=> $row['is_board'] == 0 ? 0 : 1,
					'inc_postcount'		=> $row['count_posts'],
					);

				$this->lib->convertForum($row['id'], $save, $perms);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'board_abo', 'where' => "board_id={$row['id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$savetracker = array(
						'member_id'	=> $tracker['user_id'],
						'forum_id'	=> $tracker['board_id'],
						'forum_track_type' => 'delayed',
						);
					$this->lib->convertForumSubscription($tracker['board_id'].'-'.$tracker['user_id'], $savetracker);
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
							'from' 		=> 'topic',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('topics', $main);

			//---------------------------
			// Loop
			//---------------------------

			$user_cache = array();
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				if ( array_key_exists( $row['user_id'], $user_cache ) )
				{
					$author = $user_cache[ $row['user_id'] ];
				}
				else
				{
					$author = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'name', 'from' => 'user', 'where' => 'id='.$row['user_id']));
					$user_cache[ $row['user_id'] ] = $author;
				}

				$save = array(
					'forum_id'			=> $row['board_id'],
					'title'				=> $row['name'],
					'starter_id'		=> $row['user_id'],
					'starter_name'		=> $author['name'],
					'start_date'		=> $row['post_date'],
					'views'				=> $row['views'],
					'state'		   	 	=> $row['closed'] == 0 ? 'open' : 'closed',
					'pinned'			=> $row['top'],
					'posts'				=> $row['posts'],
					'topic_hasattach'	=> $row['attachments']
					);

				$this->lib->convertTopic($row['id'], $save);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'topic_abo', 'where' => "topic_id={$row['id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$savetracker = array(
						'member_id'	=> $tracker['user_id'],
						'topic_id'	=> $tracker['topic_id'],
						'topic_track_type' => 'delayed',
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
							'from' 		=> 'post',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('posts', $main);

			//---------------------------
			// Loop
			//---------------------------

			$user_cache = array();
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				if ( array_key_exists( $row['user_id'], $user_cache ) )
				{
					$author = $user_cache[ $row['user_id'] ];
				}
				else
				{
					$author = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'name', 'from' => 'user', 'where' => 'id='.$row['user_id']));
					$user_cache[ $row['user_id'] ] = $author;
				}

				$save = array(
					'topic_id'		=> $row['topic_id'],
					'author_id'		=> $row['user_id'],
					'author_name'	=> $author['name'],
					'post_date'		=> $row['post_date'],
					'post'			=> $this->fixPostData($row['text']),
					'ip_address'	=> $row['ip'],
					'use_sig'		=> 1,
					'use_emo'		=> $row['smilie'],
					'append_edit'	=> $row['edit'],
					'edit_time'		=> $row['edit_time'],
					);

				$this->lib->convertPost($row['id'], $save);

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
							'from' 		=> 'poll',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('polls', $main, array('voters'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//---------------------------
				// We need topic info
				//---------------------------

				$topic = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'board_id, post_date, user_id', 'from' => 'topic', 'where' => "id={$row['topic_id']}" ) );

				//-----------------------------------------
				// Options are stored in one place...
				//-----------------------------------------

				$choice = array();
				$votes = array();

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'poll_option', 'where' => "poll_id={$row['id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($options = ipsRegistry::DB('hb')->fetch())
				{
					$choice[ $options['id'] ]	= $options['text'];
					$votes[ $options['id'] ]	= $options['votes'];
					$total_votes[] = $options['votes'];
				}

				//-----------------------------------------
				// Votes in another...
				//-----------------------------------------

				$imploded_options = implode( ',', array_keys($choice) );
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'poll_vote', 'where' => "option_id IN({$imploded_options})"));
				ipsRegistry::DB('hb')->execute();
				while ($voter = ipsRegistry::DB('hb')->fetch())
				{
					$vsave = array(
						'tid'			=> $row['topic_id'],
						'member_choices'=> serialize(array(1 => array($voter['option_id']))),
						'member_id'		=> $voter['user_id'],
						'forum_id'		=> $topic['board_id']
						);

					$this->lib->convertPollVoter($voter['id'], $vsave);
				}

				//-----------------------------------------
				// Then we can do the actual poll
				//-----------------------------------------

				$poll_array = array(
					// phpBB only allows one question per poll
					1 => array(
						'question'	=> $row['name'],
						'choice'	=> $choice,
						'votes'		=> $votes,
						)
					);

				$save = array(
					'tid'			=> $row['topic_id'],
					'start_date'	=> $topic['post_date'],
					'choices'   	=> addslashes(serialize($poll_array)),
					'starter_id'	=> $topic['user_id'],
					'votes'     	=> array_sum($total_votes),
					'forum_id'  	=> $topic['board_id'],
					'poll_question'	=> $row['name']
					);

				$this->lib->convertPoll($row['id'], $save);

				//---------------------------
				// Update the topic
				//---------------------------

				$this->DB->update( 'topics', array('poll_state' => 1), 'tid='.$this->lib->getLink( $row['topic_id'], 'topics' ) );

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
							'from' 		=> 'private',
							'order'		=> 'id ASC',
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
					'msg_id'			=> $row['id'],
					'msg_topic_id'      => $row['id'],
					'msg_date'          => $row['send'],
					'msg_post'          => $this->fixPostData($row['text']),
					'msg_post_key'      => md5(microtime()),
					'msg_author_id'     => $row['from_id'],
					'msg_is_first_post' => 1
					);

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$maps = array();
				$_invited   = array();

				$map_master = array(
					'map_topic_id'    => $row['id'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => 0,
					'map_last_topic_reply' => $row['send'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => 0,
					'map_is_system'   => 0,
					);

				$maps[] = array_merge( $map_master, array('map_user_id' => $row['to_id'], 'map_is_starter' => 0) );

				if ($row['to_id'] != $row['from_id'])
				{
					$maps[] = array_merge( $map_master, array('map_user_id' => $row['from_id'], 'map_is_starter' => 1) );
				}

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$topic = array(
					'mt_id'			     => $row['id'],
					'mt_date'		     => $row['send'],
					'mt_title'		     => $row['name'],
					'mt_starter_id'	     => $row['from_id'],
					'mt_start_time'      => $row['send'],
					'mt_last_post_time'  => $row['send'],
					'mt_invited_members' => serialize( array( $row['to_id'] ) ),
					'mt_to_count'		 => 1,
					'mt_to_member_id'	 => $row['to_id'],
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
							'from' 		=> 'attachment',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('attachments', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_phpmyforum/attachments):')), 'path');

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
				if (preg_match('/image/', $row['mime']))
				{
					$image = true;
				}

				// We have to rename the file or it'll freak out
				copy( $path . '/' . $row['id'], $path . '/' . $row['id'].'.'.$extension );

				// Sort out data
				$save = array(
					'attach_ext'			=> $extension,
					'attach_file'			=> $row['filename'],
					'attach_location'		=> $row['id'].'.'.$extension,
					'attach_is_image'		=> $image,
					'attach_hits'			=> $row['views'],
					'attach_date'			=> $row['upload_date'],
					'attach_member_id'		=> $row['user_id'],
					'attach_approved'		=> 1,
					'attach_filesize'		=> $row['size'],
					'attach_rel_id'			=> $row['post_id'],
					'attach_rel_module'		=> 'post',
					);

				// Send em on
				$done = $this->lib->convertAttachment($row['id'], $save, $path);

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
							'from' 		=> 'smilie',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('emoticons', $main);

			//-----------------------------------------
			// We need to know the path and how to handle duplicates
			//-----------------------------------------

			$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_phpmyforum/images/default/smilies):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

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
				$file = str_replace('%images%/', '', $row['filename']);

				$save = array(
					'typed'		=> $row['code'],
					'image'		=> $file,
					'clickable'	=> $row['view'],
					'emo_set'	=> 'default',
					);
				$done = $this->lib->convertEmoticon($row['id'], $save, $us['emo_opt'], $path);
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
							'from' 		=> 'word',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('badwords', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'type'		=> $row['search'],
					'swop'		=> $row['replacement'],
					'm_exact'	=> '1',
					);
				$this->lib->convertBadword($row['id'], $save);
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
							'from' 		=> 'user_field',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('pfields', $main, array('pfields_groups'));

			//-----------------------------------------
			// Create an unfiled group
			//-----------------------------------------

			if (!$us['pfield_group'])
			{
				$group = $this->lib->convertPFieldGroup(99, array('pf_group_name' => 'Converted', 'pf_group_key' => 'phpmyforum'), true);
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
				// pf_content
				$options = $row['options'] ? unserialize($row['options']) : array();
				$pf_content = array();
				foreach($options as $k => $v)
				{
					$pf_content[] = "{$k}={$v}";
				}

				// pf_type
				$py_type = 'input';
				switch($row['typ'])
				{
					case 2:
						$pf_type = 'textarea';
						break;
					case 3:
						$pf_type = 'radio';
						$pf_content = array('0=Yes', '1=No');
						break;
					case 4:
						$pf_type = 'textarea';
						break;
				}

				// Finalise
				$save = array(
					'pf_title'		=> $row['name_edit'],
					'pf_desc'		=> $row['comment'],
					'pf_content'	=> implode('|', $pf_content),
					'pf_type'		=> $pf_type,
					'pf_not_null'	=> $row['required'],
					'pf_member_hide'=> $row['profile'] ? 0 : 1,
					'pf_max_input'	=> $row['maxlength'],
					'pf_member_edit'=> $row['rang'],
					'pf_position'	=> $row['displayorder'],
					'pf_show_on_reg'=> $row['signup'],
					'pf_group_id'	=> 99,
					'pf_key'		=> $row['name']
					);

				// And save
				$this->lib->convertPField($row['id'], $save);
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

			$main = array(	'select' 	=> 'm.*',
							'from'		=> array( 'board_mod' => 'm' ),
							'add_join'	=> array(
											array(	'select' => 'mem.name',
							 						'from'   => array( 'user' => 'mem' ),
							 						'where'  => 'm.user_id = mem.id',
							 						'type'   => 'inner'
												),
											),
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('moderators', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
								   'forum_id'	  => $row['board_id'],
								   'member_name'  => $row['name'],
								   'member_id'	  => $row['user_id'],
								   'edit_post'	  => $row['mod_edit'],
								   'edit_topic'	  => $row['mod_edit'],
								   'delete_post'  => $row['mod_del'],
								   'delete_topic' => $row['mod_del'],
								   'view_ip'	  => 0,
								   'open_topic'	  => $row['mod_openclose'],
								   'close_topic'  => $row['mod_openclose'],
								   'mass_move'	  => $row['mod_move_topic'],
								   'mass_prune'	  => $row['mod_del'],
								   'move_topic'	  => $row['mod_move_topic'],
								   'pin_topic'	  => $row['mod_top'],
								   'unpin_topic'  => $row['mod_top'],
								   'post_q'		  => $row['mod_edit'],
								   'topic_q'	  => $row['mod_edit'],
								   'allow_warn'	  => 0,
								   'edit_user'	  => 0,
								   'is_group'	  => 0,
								   'split_merge'  => $row['mod_join'] );


				$this->lib->convertModerator($row['id'], $save);
			}

			$this->lib->next();

		}

	}

