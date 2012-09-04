<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * UBB.Threads
 * Last Update: $Date: 2011-11-08 00:14:18 +0000 (Tue, 08 Nov 2011) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 593 $
 */

	$info = array(
		'key'	=> 'ubbthreads',
		'name'	=> 'UBB.Threads 7.5',
		'login'	=> true,
	);

	class admin_convert_board_ubbthreads extends ipsCommand
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
				'forum_perms'	=> array(),
				'groups' 		=> array('forum_perms'),
				'members'		=> array('groups'),
				'profile_comments' => array('members'),
				'profile_ratings' => array('members'),
				'profile_friends' => array('members'),
				'ignored_users'	=> array('members'),
				'forums'		=> array('forum_perms'),
				'moderators'	=> array('members', 'forums'),
				'topics'		=> array('members', 'forums'),
				'topic_ratings' => array('topics', 'members'),
				'posts'			=> array('members', 'topics', 'emoticons'),
				'pms'			=> array('members', 'emoticons'),
				'ranks'			=> array(),
				'attachments'	=> array('posts'),
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
			$this->lib->sendHeader( 'UBB.Threads &rarr; IP.Board Converter' );

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

			if (array_key_exists($this->request['do'], $this->actions) or $this->request['do'] == 'boards' or $this->request['do'] == 'bad_emails')
			{
				call_user_func(array($this, 'convert_'.$this->request['do']));
			}
			else
			{
				$this->lib->menu( array(
					'forums' => array(
						'single' => 'CATEGORIES',
						'multi'  => array( 'CATEGORIES', 'FORUMS' )
					),
					'banfilters' => array(
						'single' => 'BANNED_HOSTS',
						'multi'  => array( 'BANNED_HOSTS', 'BANNED_EMAILS' )
					),
					) );
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
					return $this->lib->countRows('GROUPS', 'GROUP_IS_DISABLED=0');
					break;

				case 'members':
					return $this->lib->countRows('USERS', 'USER_ID>1');
					break;

				case 'forums':
					return $this->lib->countRows('CATEGORIES') + $this->lib->countRows('FORUMS');
					break;

				case 'pms':
					return $this->lib->countRows('PRIVATE_MESSAGE_TOPICS');
					break;

				case 'attachments':
					return $this->lib->countRows('FILES');
					break;

				case 'badwords':
					return $this->lib->countRows('CENSOR_LIST');
					break;

				case 'banfilters':
					return $this->lib->countRows('BANNED_HOSTS');
					break;

				case 'emoticons':
					return $this->lib->countRows('GRAEMLINS');
					break;

				case 'ignored_users':
					return $this->lib->countRows('USER_PROFILE', 'USER_IGNORE_LIST <> ""');
					break;

				case 'profile_friends':
					return $this->lib->countRows('ADDRESS_BOOK', 'USER_ID > 0 AND ADDRESS_ENTRY_USER_ID > 0');
					break;

				case 'profile_ratings':
					return $this->lib->countRows('RATINGS', "RATING_TYPE='u'");
					break;

				case 'topic_ratings':
					return $this->lib->countRows('RATINGS', "RATING_TYPE='t'");
					break;

				case 'ranks':
					return $this->lib->countRows('USER_TITLES');
					break;

				default:
					return $this->lib->countRows(strtoupper($action));
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
			// Get rid of 'graemlins'.
			$post = str_replace( '<<GRAEMLIN_URL>>', 'graemlins', $post );
			$post = str_replace( '&lt;&lt;GRAEMLIN_URL&gt;&gt;', 'graemlins', $post );
			
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
							'from' 		=> 'GROUPS',
							'where'		=> 'GROUP_IS_DISABLED=0',
							'order'		=> 'GROUP_ID ASC',
						);

			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'GROUP_ID', 'nf' => 'GROUP_NAME'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['GROUP_ID'], $row['GROUP_NAME']);
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
							'from' 		=> 'GROUPS',
							'where'		=> 'GROUP_IS_DISABLED=0',
							'order'		=> 'GROUP_ID ASC',
						);

			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'GROUP_ID', 'nf' => 'GROUP_NAME'));

			//---------------------------
			// Loop
			//---------------------------

			$groups = array();

			// Loop
			foreach( $loop as $row )
			{
				$save = array(
					'g_title'			=> $row['GROUP_NAME'],
					'g_perm_id'			=> $row['GROUP_ID'],
					);
				$this->lib->convertGroup($row['GROUP_ID'], $save);
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
				'USER_LOCATION'		=> 'Location',
				'USER_AIM'			=> 'AIM ID',
				'USER_HOMEPAGE'		=> 'Website',
				'USER_ICQ'			=> 'ICQ Number',
				'USER_YAHOO'		=> 'Yahoo ID',
				'USER_MSN'			=> 'MSN ID',
				'USER_OCCUPATION'	=> 'Occupation',
				'USER_HOBBIES'		=> 'Interests',
				);

			$this->lib->saveMoreInfo('members', $pcpf);

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> 'u.*',
							'from' 		=> array('USERS' => 'u'),
							'where'		=> 'u.USER_ID>1',
							'add_join'	=> array(
								array( 	'select' => 'd.*',
										'from'   =>	array( 'USER_DATA' => 'd' ),
										'where'  => "u.USER_ID=d.USER_ID",
										'type'   => 'inner'
									),
								array( 	'select' => 'p.*',
									'from'   =>	array( 'USER_PROFILE' => 'p' ),
									'where'  => "u.USER_ID=p.USER_ID",
									'type'   => 'inner'
								),
								),
							'order'		=> 'u.USER_ID ASC',
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
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				//-----------------------------------------
				// Set info
				//-----------------------------------------

				// Secondary groups
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'USER_GROUPS', 'where' => "USER_ID='{$row['USER_ID']}'"));
				ipsRegistry::DB('hb')->execute();
				$sgroups = array();
				while ($group = ipsRegistry::DB('hb')->fetch())
				{
					$sgroups[] = $group['GROUP_ID'];
				}
				$group = array_shift($sgroups);

				// Basic info
				$info = array(
					'id'				=> $row['USER_ID'],
					'group'				=> $group,
					'secondary_groups'	=> implode(',', $sgroups),
					'joined'			=> $row['USER_REGISTERED_ON'],
					'username'			=> $row['USER_LOGIN_NAME'],
					'displayname'		=> $row['USER_DISPLAY_NAME'],
					'email'				=> $row['USER_REAL_EMAIL'],
					'md5pass'			=> $row['USER_PASSWORD'],
					);

				// Member info
				$birthday = ($row['USER_BIRTHDAY']) ? explode('/', $row['USER_BIRTHDAY']) : NULL;

				$members = array(
					'posts'				=> $row['USER_TOTAL_POSTS'],
					'hide_email' 		=> ($row['USER_DISPLAY_EMAIL'] == $row['USER_REAL_EMAIL']) ? 0 : 1,
					'time_offset'		=> $row['USER_TIME_OFFSET'],
					'title'				=> ($row['USER_CUSTOM_TITLE']) ? $row['USER_CUSTOM_TITLE'] : $row['USER_TITLE'],
					'bday_day'			=> ($row['USER_BIRTHDAY']) ? $birthday[1] : '',
					'bday_month'		=> ($row['USER_BIRTHDAY']) ? $birthday[0] : '',
					'bday_year'			=> ($row['USER_BIRTHDAY']) ? $birthday[2] : '',
					'ip_address'		=> $row['USER_REGISTRATION_IP'],
					'last_visit'		=> $row['USER_LAST_VISIT_TIME'],
					'last_post'			=> $row['USER_LAST_POST_TIME'],
					'email_pm'      	=> ($row['USER_NOTIFY_ON_PM'] == 'yes') ? 1 : 0,
					'member_banned'		=> $row['USER_IS_BANNED'],
					'view_sigs'			=> ($row['USER_SHOW_SIGNATURES'] == 'no') ? 0 : 1,
					);

				// Profile
				$profile = array(
					'signature'			=> $this->fixPostData($row['USER_SIGNATURE']),
					'pp_rating_value'	=> $row['USER_RATING'] * $row['USER_TOTAL_RATES'],
					'pp_rating_hits'	=> $row['USER_TOTAL_RATES'],
					'pp_rating_real'	=> $row['USER_RATING'],
					);

				//-----------------------------------------
				// Avatars
				//-----------------------------------------

				if ($row['USER_AVATAR'])
				{
					$profile['photo_type'] = 'url';
					$profile['photo_location'] = $row['USER_AVATAR'];
				}

				//-----------------------------------------
				// And go!
				//-----------------------------------------

				$this->lib->convertMember($info, $members, $profile, array(), '');
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
							'from' 		=> 'CATEGORIES',
							'order'		=> 'CATEGORY_ID ASC',
						);

			$loop = $this->lib->load('forums', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertForum( 'c' . $row['CATEGORY_ID'], array(
					'name'			=> $row['CATEGORY_TITLE'],
					'description'	=> $row['CATEGORY_DESCRIPTION'],
					'position'		=> $row['CATEGORY_SORT_ORDER'],
					'parent_id'		=> -1,
				), array());
				
				// Any children?
				ipsRegistry::DB('hb')->build(
					array(
						'select'	=> '*',
						'from'		=> 'FORUMS',
						'where'		=> 'CATEGORY_ID=' . $row['CATEGORY_ID']
					)
				);
				$child = ipsRegistry::DB('hb')->execute();
				
				if ( ipsRegistry::DB('hb')->getTotalRows( $child ) )
				{
					while( $forum = ipsRegistry::DB('hb')->fetch( $child ) )
					{
						// Set info
						$save = array(
							'parent_id'			=> $forum['FORUM_PARENT'] ? $forum['FORUM_PARENT'] : 'c' . $row['CATEGORY_ID'],
							'position'			=> $forum['FORUM_SORT_ORDER'],
							'name'				=> $forum['FORUM_TITLE'],
							'description'		=> $forum['FORUM_DESCRIPTION'],
							'topics'			=> $forum['FORUM_TOPICS'],
							'posts'				=> $forum['FORUM_POSTS'],
							'status'			=> $forum['FORUM_IS_ACTIVE'],
							'conv_parent'		=> $forum['FORUM_PARENT'] ? $forum['FORUM_PARENT'] : 'c' . $row['CATEGORY_ID'],
							);
		
						// Save
						$this->lib->convertForum($forum['FORUM_ID'], $save, array());
		
						//-----------------------------------------
						// Handle subscriptions
						//-----------------------------------------
		
						ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'WATCH_LISTS', 'where' => "WATCH_ID={$forum['FORUM_ID']} AND WATCH_TYPE='f'"));
						$tlib = ipsRegistry::DB('hb')->execute();
						while ($tracker = ipsRegistry::DB('hb')->fetch($tlib))
						{
							$savetracker = array(
								'member_id'	=> $tracker['USER_ID'],
								'forum_id'	=> $tracker['WATCH_ID'],
								'forum_track_type' => $tracker['WATCH_NOTIFY_IMMEDIATE'] ? 'immediate' : 'none',
								);
							$this->lib->convertForumSubscription($tracker['WATCH_ID'].'-'.$tracker['USER_ID'], $savetracker);
						}
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
							'from' 		=> 'TOPICS',
							'order'		=> 'TOPIC_ID ASC',
						);

			$loop = $this->lib->load('topics', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'forum_id'		 	=> $row['FORUM_ID'],
					'title'		 		=> $row['TOPIC_SUBJECT'],
					'views'			 	=> $row['TOPIC_VIEWS'],
					'posts'			 	=> $row['TOPIC_REPLIES'],
					'starter_name'	 	=> $row['TOPIC_POSTER_NAME'],
					'starter_id'		=> $row['USER_ID'],
					'state'				=> $row['TOPIC_STATUS'] == 'O' ? 'open' : 'closed',
					'approved'			=> $row['TOPIC_IS_APPROVED'],
					'start_date'		=> $row['TOPIC_CREATED_TIME'],
					'topic_rating_total'=> $row['TOPIC_RATING'] * $row['TOPIC_TOTAL_RATES'],
					'topic_rating_hits'	=> $row['TOPIC_TOTAL_RATES'],
					'pinned'			=> $row['TOPIC_IS_STICKY'],
					'topic_hasattach'	=> $row['TOPIC_HAS_FILE'],
					'poll_state'		=> $row['TOPIC_HAS_POLL'],
					);

				$this->lib->convertTopic($row['TOPIC_ID'], $save);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'WATCH_LISTS', 'where' => "WATCH_ID={$row['TOPIC_ID']} AND WATCH_TYPE='t'"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$savetracker = array(
						'member_id'	=> $tracker['USER_ID'],
						'topic_id'	=> $tracker['WATCH_ID'],
						'topic_track_type' => $tracker['WATCH_NOTIFY_IMMEDIATE'] ? 'immediate' : 'none',
						);
					$this->lib->convertTopicSubscription($tracker['WATCH_ID'].'-'.$tracker['USER_ID'], $savetracker);
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
							'from' 		=> 'POSTS',
							'order'		=> 'POST_ID ASC',
						);

			$loop = $this->lib->load('posts', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'author_name'	 	=> $row['POST_POSTER_NAME'],
					'author_id'			=> $row['USER_ID'],
					'topic_id'			=> $row['TOPIC_ID'],
					'post'				=> $this->fixPostData( $row['POST_BODY'] ),
					'post_date'			=> $row['POST_POSTED_TIME'],
					'use_sig'			=> $row['POST_ADD_SIGNATURE'],
					'ip_address'		=> $row['POST_POSTER_IP'],
					'use_emo'			=> 1,
					'new_topic'			=> $row['POST_IS_TOPIC'],
					'queued'			=> $row['POST_IS_APPROVED'] ? 0 : 1,
					'edit_time'			=> $row['POST_LAST_EDITED_TIME'],
					'edit_name'			=> $row['POST_LAST_EDITED_BY'],
					'post_edit_reason'	=> $row['POST_LAST_EDIT_REASON']
					);

				$this->lib->convertPost($row['POST_ID'], $save);

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
							'from' 		=> 'PRIVATE_MESSAGE_TOPICS',
							'order'		=> 'TOPIC_ID ASC',
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
				ipsRegistry::DB('hb')->build( array( 'select' => '*', 'from' => 'PRIVATE_MESSAGE_POSTS', 'where' => "TOPIC_ID='{$row['TOPIC_ID']}'" ) );
				ipsRegistry::DB('hb')->execute();
				while( $post =  ipsRegistry::DB('hb')->fetch() )
				{
					$posts[] = array(
						'msg_id'			=> $post['POST_ID'],
						'msg_topic_id'      => $row['TOPIC_ID'],
						'msg_date'          => $post['POST_TIME'],
						'msg_post'          => $this->fixPostData($post['POST_BODY']),
						'msg_post_key'      => md5(microtime()),
						'msg_author_id'     => $post['USER_ID'],
						);
				}

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$maps 	= array();
				$cache	= array();
				
				$_invited = array();
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'PRIVATE_MESSAGE_USERS', 'where' => "TOPIC_ID={$row['TOPIC_ID']}"));
				ipsRegistry::DB('hb')->execute();
				while ($map = ipsRegistry::DB('hb')->fetch())
				{
					if ( in_array( $row['USER_ID'], $cache[ $row['TOPIC_ID'] ] ) )
					{
						continue;
					}

					$maps[] = array(
						'map_user_id'     => $map['USER_ID'],
						'map_topic_id'    => $row['TOPIC_ID'],
						'map_folder_id'   => 'myconvo',
						'map_read_time'   => $row['MESSAGE_LAST_READ'],
						'map_last_topic_reply' => $row['TOPIC_LAST_REPLY_TIME'],
						'map_user_active' => 1,
						'map_user_banned' => 0,
						'map_has_unread'  => ($map['MESSAGE_LAST_READ'] > 0) ? 0 : 1,
						'map_is_system'   => 0,
						'map_is_starter'  => ( $map['USER_ID'] == $row['USER_ID'] ) ? 1 : 0
						);

					$_invited[ $map['USER_ID'] ] = $map['USER_ID'];

					$cache[ $row['TOPIC_ID'] ][] = $row['USER_ID'];
				}

				//-----------------------------------------
				// Topic Data
				//-----------------------------------------

				$to = array_shift($_invited);

				$topic = array(
					'mt_id'			     => $row['TOPIC_ID'],
					'mt_date'		     => $row['TOPIC_TIME'],
					'mt_title'		     => $row['TOPIC_SUBJECT'],
					'mt_starter_id'	     => $row['USER_ID'],
					'mt_start_time'      => $row['TOPIC_TIME'],
					'mt_last_post_time'  => $row['TOPIC_LAST_REPLY_TIME'],
					'mt_invited_members' => serialize( array_keys( $_invited ) ),
					'mt_to_count'		 => count(  array_keys( $_invited ) ),
					'mt_to_member_id'	 => $to,
					'mt_replies'		 => $row['TOPIC_REPLIES'],
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
							'from' 		=> 'FILES',
							'order'		=> 'FILE_ID ASC',
						);

			$loop = $this->lib->load('attachments', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where your UBB.Threads attachments are saved (no trailing slash):')), 'path');

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
				$topic	= ipsRegistry::DB('hb')->buildAndFetch(
					array(
						'select'	=> 'TOPIC_ID',
						'from'		=> 'POSTS',
						'where'		=> 'POST_ID=' . $row['POST_ID']
					)
				);
				
				// Now we have the foreign ID, grab our proper one
				if ( $topic['TOPIC_ID'] )
				{
					$ipbTopic = $this->lib->getLink( $topic['TOPIC_ID'], 'topics' );
				}
				
				$save = array(
					'attach_ext'			=> $row['FILE_TYPE'],
					'attach_file'			=> $row['FILE_ORIGINAL_NAME'],
					'attach_location'		=> ($row['FILE_DIR']) ? $row['FILE_DIR'].'/'.$row['FILE_NAME'] : $row['FILE_NAME'],
					'attach_is_image'		=> (int) in_array( $row['FILE_TYPE'], array('gif', 'jpg', 'jpeg', 'png') ),
					'attach_hits'			=> $row['FILE_DOWNLOADS'],
					'attach_date'			=> $row['FILE_ADD_TIME'],
					'attach_member_id'		=> $row['USER_ID'],
					'attach_filesize'		=> $row['FILE_SIZE'],
					'attach_rel_id'			=> $row['POST_ID'],
					'attach_rel_module'		=> 'post',
					'attach_img_width'		=> $row['FILE_WIDTH'],
					'attach_img_height'		=> $row['FILE_HEIGHT'],
					'attach_parent_id'		=> $ipbTopic ? $ipbTopic : 0
					);

				$this->lib->convertAttachment($row['FILE_ID'], $save, $path);

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
							'from' 		=> 'CENSOR_LIST',
						);

			$loop = $this->lib->load('badwords', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'type'		=> str_replace('(.*?)', '', $row['CENSOR_WORD']),
					'swop'		=> $row['CENSOR_REPLACE_WITH'] ? $row['CENSOR_REPLACE_WITH'] : '[censored]',
					'm_exact'	=> preg_match('/\(\.\*\?\)/', $row['CENSOR_WORD']) ? 0 : 1,
					);
				$this->lib->convertBadword($row['CENSOR_WORD'], $save);
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
								'from' 		=> 'BANNED_HOSTS',
							);

			$loop = $this->lib->load('banfilters', $main, array(), array('bad_emails', 'BANNED_EMAILS'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'ban_type'		=> 'ip',
					'ban_content'	=> str_replace('%', '*', $row['BANNED_HOST']),
					);
				$this->lib->convertBan($row['BANNED_HOST'], $save);
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
								'from' 		=> 'BANNED_EMAILS',
							);

			$this->start = intval($this->request['st']);
			$this->end = $this->start + intval($this->request['cycle']);

			$mainBuild['limit'] = array($this->start, $this->end);

			if ($this->start == 0)
			{
				// Truncate
				$this->DB->build(array('select' => 'ipb_id as id', 'from' => 'conv_link', 'where' => "type = 'ubb_bad_emails' AND duplicate = '0'"));
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

				$this->DB->delete('conv_link', "type = 'ubb_bad_emails'");
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
					'ban_type'		=> 'email',
					'ban_content'	=> str_replace('%', '*', $row['BANNED_EMAIL']),
					);
				$this->lib->convertBan($row['BANNED_EMAIL'], $save);
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
							'from' 		=> 'GRAEMLINS',
							'order'		=> 'GRAEMLIN_ID ASC',
						);

			$loop = $this->lib->load('emoticons', $main);

			//-----------------------------------------
			// We need to know the path and how to handle duplicates
			//-----------------------------------------

			$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_ubb/images/graemlins/default):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

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
					'typed'		=> $row['GRAEMLIN_SMILEY_CODE'] ? $row['GRAEMLIN_SMILEY_CODE'] : ':'.$row['GRAEMLIN_MARKUP_CODE'].':',
					'image'		=> $row['GRAEMLIN_IMAGE'],
					'clickable'	=> $row['GRAEMLIN_IS_ACTIVE'],
					'emo_set'	=> 'default',
					);
				$done = $this->lib->convertEmoticon($row['GRAEMLIN_ID'], $save, $us['emo_opt'], $path);
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
							'from'		=> 'MODERATORS'
						);

			$loop = $this->lib->load('moderators', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				$member = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'USER_DISPLAY_NAME', 'from' => 'USERS', 'where' => 'USER_ID='.$row['USER_ID']));

				$save = array(
								   'forum_id'	  => $row['FORUM_ID'],
								   'member_name'  => $member['USER_DISPLAY_NAME'],
								   'member_id'	  => $row['USER_ID']
							 );


				$this->lib->convertModerator($row['forum_id'].'-'.$row['user_id'], $save);
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

			$main = array(	'select' 	=> 'USER_ID, USER_IGNORE_LIST',
							'from' 		=> 'USER_PROFILE',
							'where'		=> 'USER_IGNORE_LIST <> ""',
							'order'		=> 'USER_ID ASC',
						);

			$loop = $this->lib->load('ignored_users', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$explode = explode('-', $row['USER_IGNORE_LIST']);
				foreach ($explode as $foe)
				{
					if(!$foe)
					{
						continue;
					}
					$save = array(
						'ignore_owner_id'	=> $row['USER_ID'],
						'ignore_ignore_id'	=> $foe,
						'ignore_messages'	=> '1',
						'ignore_topics'		=> '1',
						);
					$this->lib->convertIgnore($row['USER_ID'].'-'.$foe, $save);
				}
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
							'from' 		=> 'ADDRESS_BOOK',
							'where'		=> 'USER_ID > 0 AND ADDRESS_ENTRY_USER_ID > 0',
							'order'		=> 'USER_ID ASC',
						);

			$loop = $this->lib->load('profile_friends', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'friends_member_id'	=> $row['USER_ID'],
					'friends_friend_id'	=> $row['ADDRESS_ENTRY_USER_ID'],
					'friends_approved'	=> '1',
					);
				$this->lib->convertFriend($row['USER_ID'].'-'.$row['ADDRESS_ENTRY_USER_ID'], $save);
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
							'from' 		=> 'PROFILE_COMMENTS',
							'order'		=> 'COMMENT_ID ASC',
						);

			$loop = $this->lib->load('profile_comments', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'status_member_id'	=> $row['PROFILE_ID'],
					'status_author_id'	=> $row['USER_ID'],
					'status_date'			=> $row['COMMENT_TIME'],
					'status_content'		=> $row['COMMENT_BODY'],
					);
				$this->lib->convertProfileComment($row['COMMENT_ID'], $save);
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
							'from' 		=> 'RATINGS',
							'where'		=> "RATING_TYPE='u'",
						);

			$loop = $this->lib->load('profile_ratings', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'rating_for_member_id'	=> $row['RATING_TARGET'],
					'rating_by_member_id'	=> $row['RATING_RATER'],
					'rating_value'			=> $row['RATING_VALUE'],
					);

				$this->lib->convertProfileRating($row['RATING_TYPE'].$row['RATING_RATER'].$row['RATING_TARGET'], $save);
			}

			$this->lib->next();

		}

		/**
		 * Convert profile ratings
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
							'from' 		=> 'RATINGS',
							'where'		=> "RATING_TYPE='t'",
						);

			$loop = $this->lib->load('topic_ratings', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'rating_tid'		=> $row['RATING_TARGET'],
					'rating_member_id'	=> $row['RATING_RATER'],
					'rating_value'		=> $row['RATING_VALUE'],
					);

				$this->lib->convertTopicRating($row['RATING_TYPE'].$row['RATING_RATER'].$row['RATING_TARGET'], $save);
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
							'from' 		=> 'USER_TITLES',
							'order'		=> 'USER_TITLE_ID ASC',
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
					'posts'	=> $row['USER_TITLE_POST_COUNT'],
					'title'	=> $row['USER_TITLE_NAME'],
					);
				$this->lib->convertRank($row['USER_TITLE_ID'], $save, $us['rank_opt']);
			}

			$this->lib->next();

		}


	}

