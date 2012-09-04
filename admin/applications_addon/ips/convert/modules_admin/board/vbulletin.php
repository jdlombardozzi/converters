<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * vBulletin
 * Last Update: $Date: 2012-05-29 16:40:05 +0100 (Tue, 29 May 2012) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 638 $
 */


	$info = array(
		'key'	=> 'vbulletin',
		'name'	=> 'vBulletin 4.0',
		'login'	=> true,
	);

	class admin_convert_board_vbulletin extends ipsCommand
	{
		private $attachmentContentTypes = array();

		/**
		 * Bitwise settings - Forum Options
		 *
		 * @access	private
		 * @var 	array
		 **/
		private $FORUMOPTIONS = array( 'active'            => 1,
								   'allowposting'      => 2,
								   'cancontainthreads' => 4,
								   'moderatenewpost'   => 8,
								   'moderatenewthread' => 16,
								   'moderateattach'    => 32,
								   'allowbbcode'       => 64,
								   'allowimages'       => 128,
								   'allowhtml'         => 256,
								   'allowsmilies'      => 512,
								   'allowicons'        => 1024,
								   'allowratings'      => 2048,
								   'countposts'        => 4096,
								   'canhavepassword'   => 8192,
								   'indexposts'        => 16384,
								   'styleoverride'     => 32768,
								   'showonforumjump'   => 65536,
								   'warnall'           => 131072 );

		/**
		 * Bitwise settings - User forum permissions
		 *
		 * @access	private
		 * @var 	array
		 **/
		private $USER_FORUM = array( 'canview'           => 1,
								 'canviewothers'     => 2,
								 'cansearch'         => 4,
								 'canemail'          => 8,
								 'canpostnew'        => 16,
								 'canreplyown'       => 32,
								 'canreplyothers'    => 64,
								 'caneditpost'       => 128,
								 'candeletepost'     => 256,
								 'candeletethread'   => 512,
								 'canopenclose'      => 1024,
								 'canmove'           => 2048,
								 'cangetattachment'  => 4096,
								 'canpostattachment' => 8192,
								 'canpostpoll'       => 16384,
								 'canvote'           => 32768,
								 'canthreadrate'     => 65536,
								 'isalwaysmoderated' => 131072,
								 'canseedelnotice'   => 262144 );

		/**
		 * Bitwise settings - User groups
		 *
		 * @access	private
		 * @var 	array
		 **/
		private $USER_PERM = array( 'ismoderator'         => 1,
								'cancontrolpanel'     => 2,
								'canadminsettings'    => 4,
								'canadminstyles'      => 8,
								'canadminlanguages'   => 16,
								'canadminforums'      => 32,
								'canadminthreads'     => 64,
								'canadmincalendars'   => 128,
								'canadminusers'       => 256,
								'canadminpermissions' => 512,
								'canadminfaq'         => 1024,
								'canadminimages'      => 2048,
								'canadminbbcodes'     => 4096,
								'canadmincron'        => 8192,
								'canadminmaintain'    => 16384,
								'canadminupgrade'     => 32768 );

		/**
		 * Bitwise settings - Mod permissions
		 *
		 * @access	private
		 * @var 	array
		 **/
		private $MOD_PERM = array( 'caneditposts'           => 1,
							   'candeleteposts'         => 2,
							   'canopenclose'           => 4,
							   'caneditthreads'         => 8,
							   'canmanagethreads'       => 16,
							   'canannounce'            => 32,
							   'canmoderateposts'       => 64,
							   'canmoderateattachments' => 128,
							   'canmassmove'            => 256,
							   'canmassprune'           => 512,
							   'canviewips'             => 1024,
							   'canviewprofile'         => 2048,
							   'canbanusers'            => 4096,
							   'canunbanusers'          => 8192,
							   'newthreademail'         => 16384,
							   'newpostemail'           => 32768,
							   'cansetpassword'         => 65536,
							   'canremoveposts'         => 131072,
							   'caneditsigs'            => 262144,
							   'caneditavatar'          => 524288,
							   'caneditpoll'            => 1048576,
							   'caneditprofilepic'      => 2097152 );


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
				'custom_bbcode' => array(),
				'emoticons'		=> array(),
				'pfields'	=> array(),
				'forum_perms' => array(),
				'groups'	=> array('forum_perms'),
				'members'	=> array('groups', 'custom_bbcode', 'pfields'),
				'profile_comments' => array('members'),
				'profile_friends' => array('members'),
				'ignored_users'	=> array('members'),
				'forums'	=> array(),
				'moderators'	=> array('groups', 'members', 'forums'),
				'topics'	=> array('forums'),
				'topic_ratings' => array('topics', 'members'),
				'tags'			=> array('topics', 'members'),
				'posts'		=> array('topics', 'custom_bbcode', 'emoticons'),
				'reputation_index' => array('members', 'posts'),
				'polls'		=> array('topics', 'members', 'forums'),
				'announcements'	=> array('forums', 'members', 'custom_bbcode', 'emoticons'),
				'pms'		=> array('members', 'custom_bbcode'),
				'ranks'			=> array(),
				'attachments_type' => array(),
				'attachments'=> array('attachments_type', 'posts'),
				'warn_logs'		=> array('members'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'vBulletin &rarr; IP.Board Converter' );

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
					return $this->lib->countRows('user');
					break;

				case 'groups':
				case 'forum_perms':
					return $this->lib->countRows('usergroup');
					break;

				case 'forums':
					return $this->lib->countRows('forum');
					break;

				case 'topics':
				case 'tags':
					return $this->lib->countRows('thread');
					break;

				case 'posts':
					return $this->lib->countRows('post');
					break;

				case 'polls':
					return $this->lib->countRows('poll');
					break;

				case 'pms':
					return $this->lib->countRows('pmtext');
					break;

				case 'attachments_type':
					return $this->lib->countRows('attachmenttype');
					break;

				case 'attachments':
					$contenttype = ipsRegistry::DB ( 'hb' )->buildAndFetch ( array (
						'select'	=> 'contenttypeid',
						'from'		=> 'contenttype',
						'where'		=> 'class = \'Post\''
					) );
					return $this->lib->countRows('attachment', 'contenttypeid = ' . $contenttype['contenttypeid']);
					break;

				case 'announcements':
					return $this->lib->countRows('announcement');
					break;

				case 'custom_bbcode':
					return $this->lib->countRows('bbcode');
					break;

				case 'pfields':
					return $this->lib->countRows('profilefield');
					break;

				case 'emoticons':
					return $this->lib->countRows('smilie');
					break;

				case 'moderators':
					return $this->lib->countRows('moderator');
					break;

				case 'profile_friends':
					return $this->lib->countRows('userlist', "type='buddy'");
					break;

				case 'ignored_users':
					return $this->lib->countRows('userlist', "type='ignore'");
					break;

				case 'reputation_index':
					return $this->lib->countRows('reputation');
					break;

				case 'profile_comments':
					return $this->lib->countRows('visitormessage');
					break;

				case 'ranks':
					return $this->lib->countRows('usertitle');
					break;

				case 'topic_ratings':
					return $this->lib->countRows('threadrate');
					break;

				case 'warn_logs':
					return $this->lib->countRows('infraction');
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
			$post = str_replace("<", "&lt;", $post);
			$post = str_replace(">", "&gt;", $post);

			// Sort out newlines
			$post = nl2br($post);

			// And quote tags
			$post = preg_replace("#\[quote=([^\];]+?)\]#i", "[quote name='$1']", $post);
			$post = preg_replace("#\[quote=([^\];]+?);\d+\]#i", "[quote name='$1']", $post);
			//$post = preg_replace("#\[quote=(.+)\](.+)\[/quote\]#i", "[quote name='$1']$2[/quote]", $post);



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
							'from' 		=> 'usergroup',
							'order'		=> 'usergroupid ASC',
						);

			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'usergroupid', 'nf' => 'title'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['usergroupid'], $row['title']);
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
							'from' 		=> 'usergroup',
							'order'		=> 'usergroupid ASC',
						);

			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'usergroupid', 'nf' => 'title'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				// Silly bitwise permissions
				foreach( $this->USER_FORUM as $name => $bit ) {
					$row[ $name ] = ( $row['forumpermissions'] & $bit ) ? 1 : 0;
				}
				foreach( $this->USER_PERM as $name => $bit ) {
					$row[ $name ] = ( $row['adminpermissions'] & $bit ) ? 1 : 0;
				}

				$save = array(
					'g_title'				=> $row['title'],
					'g_max_messages'		=> $row['pmquota'],
					'g_max_mass_pm'			=> $row['pmsendmax'],
					'prefix'				=> $row['opentag'],
					'suffix'				=> $row['closetag'],
					'g_view_board'			=> $row['canview'],
					'g_mem_info'			=> 1,
					'g_other_topics'		=> $row['canviewothers'],
					'g_use_search'			=> $row['cansearch'],
					'g_email_friend'		=> $row['canemail'],
					'g_invite_friend'		=> 1,
					'g_edit_profile'		=> $row['canmodifyprofile'],
					'g_post_new_topics'		=> $row['canpostnew'],
					'g_reply_own_topics'	=> 1,
					'g_reply_other_topics'	=> isset($row['canreplyothers']) && $row['canreplyothers'] == 0 ? 0 : 1,
					'g_edit_posts'			=> $row['caneditpost'],
					'g_delete_own_posts'	=> $row['candeletepost'],
					'g_open_close_posts'	=> $row['canopenclose'],
					'g_delete_own_topics'	=> $row['candeletethread'],
					'g_post_polls'		 	=> $row['canpostpoll'],
					'g_vote_polls'		 	=> $row['canvote'],
					'g_use_pm'			 	=> $row['pmpermissions'] != 0 ? 1 : 0,
					'g_is_supmod'		 	=> $row['ismoderator'],
					'g_access_cp'		 	=> $row['cancontrolpanel'],
					'g_access_offline'	 	=> $row['cancontrolpanel'],
					'g_avoid_q'			 	=> $row['ismoderator'],
					'g_avoid_flood'		 	=> $row['ismoderator'],
					'g_perm_id'				=> $row['usergroupid'],
					);
				$this->lib->convertGroup($row['usergroupid'], $save);
			}

			$this->lib->next();

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

			$pcpf = array(
				'icq'			=> 'ICQ Number',
				'aim'			=> 'AIM ID',
				'yahoo'			=> 'Yahoo ID',
				'msn'			=> 'MSN ID',
				'skype'			=> 'Skype ID',
				'homepage'		=> 'Website',
				);

			$this->lib->saveMoreInfo( 'members', array_merge( array_keys($pcpf), array( /*'avvy_path',*/ 'pp_path', 'pp_type' ) ) );

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> 'u.*',
							'from' 		=> array('user' => 'u'),
							'order'		=> 'u.userid ASC',
							'add_join'	=> array(
											array( 	'select' => 't.*',
													'from'   =>	array( 'usertextfield' => 't' ),
													'where'  => "u.userid = t.userid",
													'type'   => 'left'
												),
											array( 	'select' => 'c.*',
													'from'   =>	array( 'userfield' => 'c' ),
													'where'  => "u.userid = c.userid",
													'type'   => 'left'
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

			// We need to know the avatars path
			//$ask['avvy_path'] = array('type' => 'text', 'label' => 'The path to the folder where custom avatars are saved (no trailing slash - usually /path_to_vb/customavatars):');
			$ask['pp_path'] = array('type' => 'text', 'label' => 'The path to the folder where your custom profile pictures/avatars are saved (no trailing slash - usually /path_to_vb/customprofilepics or /path_to_vb/customavatars):');
			$ask['pp_type'] = array ( 'type' => 'dropdown', 'label' => 'The Member Photo type to convert?', 'options' => array ( 'avatar' => 'Avatars', 'profile' => 'Profile Pictures' ) );

			// And those custom profile fields
			$options = array('x' => '-Skip-');
			$this->DB->build(array('select' => '*', 'from' => 'pfields_data'));
			$fieldRes = $this->DB->execute();
			while ($row = $this->DB->fetch($fieldRes))
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
			$fieldRes = $this->DB->execute();
			$pfields = array();
			while ($row = $this->DB->fetch($fieldRes))
			{
				$pfields[] = $row;
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
								'id'             	=> $row['userid'],
								'username'     	 	=> $row['username'],
								'email'			 	=> $row['email'],
								'group'			 	=> $row['usergroupid'],
								'secondary_groups'	=> $row['membergroupids'],
								'joined'			=> $row['joindate'],
								'password'		 => $row['password'],
								);

				// Member info
				$birthday = ($row['birthday']) ? explode('-', $row['birthday']) : null;

				$members = array(
					'title'				=> strip_tags($row['usertitle']),
					'last_visit'		=> $row['lastvisit'],
					'last_activity'		=> $row['lastactivity'],
					'last_post'			=> $row['lastpost'],
					'posts'				=> $row['posts'],
					'time_offset'		=> $row['timezoneoffset'],
					'bday_day'			=> ($row['birthday']) ? $birthday[1] : '',
					'bday_month'		=> ($row['birthday']) ? $birthday[0] : '',
					'bday_year'			=> ($row['birthday']) ? $birthday[2] : '',
					'ip_address'		=> $row['ipaddress'],
					'misc'				=> $row['salt'],
					'warn_level'		=> $row['warnings'],
					);

				// Profile
				$profile = array(
					'pp_reputation_points'	=> $row['reputation'],
					//'pp_friend_count'		=> $row['friendcount'],
					//'pp_about_me' =>
					'signature'				=> $this->fixPostData($row['signature']),
					'pp_setting_count_friends' => 1,
					'pp_setting_count_comments' => 1,
					);

				//-----------------------------------------
				// Has he been a naughty boy?
				//-----------------------------------------

				$ban = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'userban', 'where' => "userid='{$row['userid']}'" ) );
				if ($ban)
				{
					// Permenant?
					if ($ban['liftdate'] == 0)
					{
						$members['member_banned'] = 1;
					}
					// Or just temporary?
					else
					{
						//-----------------------------------------
						// Work out the length... this could be fun..
						//-----------------------------------------

						$inseconds = $ban['liftdate'] - $ban['bandate'];

						if (($inseconds / 86400) >= 1)
						{
							// It's at least a day...
							$indays = round($inseconds / 86400);
							$length = "{$indays}:d";
						}
						else
						{
							// It's less than a day...
							$inhours = round($inseconds / 3600);
							$length = "{$inhours}:h";
						}

						//-----------------------------------------
						// Save
						//-----------------------------------------

						$members['temp_ban'] = "{$ban['bandate']}:{$ban['liftdate']}:{$length}";
					}
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
					$custom['field_'.$field['ipb_id']] = $row['field'.$field['foreign_id']];

					// Multi-selects
					if ( $row['field'.$field['foreign_id']] and array_key_exists( $field['foreign_id'], $us['multi_fields'] ) )
					{
						$options = unserialize( $us['multi_fields'][ $field['foreign_id'] ] );
						$f_options = array();
						$i = 1;
						for ( $j = 0; $j < count( $options ); $j++ )
						{
							$f_options[ $j ] = $i;
							$i <<= 1;
						}

						$final = array();
						foreach( $f_options as $ipbid => $bit )
						{
							if ( $row['field'.$field['foreign_id']] & $bit )
							{
								$final[] = $ipbid;
							}
						}

						$custom['field_'.$field['ipb_id']] = '|' . implode( '|', $final ) . '|';

					}
				}

				//-----------------------------------------
				// Avatars and profile pictures
				//-----------------------------------------
				$profile['photo_type'] = 'custom';
				if ( $us['pp_type'] == 'avatar' )
				{
					$customavatar = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'customavatar', 'where' => "userid='{$row['userid']}'" ) );
					if ($customavatar)
					{
						$profile['pp_main_photo'] = $customavatar['filename'];
	
						if ($customavatar['filedata'])
						{
							$profile['photo_data'] = $customavatar['filedata'];
						}
						else
						{
							$profile['pp_main_photo'] = "avatar{$customavatar['userid']}_{$row['avatarrevision']}.gif";
						}
						
						$profile['pp_main_width'] = $customavatar['width'];
						$profile['pp_main_height'] = $customavatar['height'];
						$profile['photo_filesize'] = $customavatar['filesize'];
					}
				}
				else
				{
					$customprofilepic = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'customprofilepic', 'where' => "userid='{$row['userid']}'" ) );
					if ($customprofilepic)
					{
						$profile['pp_main_photo'] = $customprofilepic['filename'];
	
						if ($customprofilepic['filedata'])
						{
							$profile['photo_data'] = $customprofilepic['filedata'];
						}
						else
						{
							$profile['pp_main_photo'] = "profilepic{$customprofilepic['userid']}_{$row['profilepicrevision']}.gif";
						}
	
						$profile['pp_main_width'] = $customprofilepic['width'];
						$profile['pp_main_height'] = $customprofilepic['height'];
						$profile['photo_filesize'] = $customprofilepic['filesize'];
					}
				}

				//-----------------------------------------
				// Go
				//-----------------------------------------

				$this->lib->convertMember($info, $members, $profile, $custom, $us['pp_path']);

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
							'from' 		=> 'forum',
							'order'		=> 'forumid ASC',
						);

			$loop = $this->lib->load('forums', $main, array('forum_tracker'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Work stuff out
				//-----------------------------------------

				// Permissions will need to be reconfigured
				$perms = array();

				// Silly bitwise permissions
				foreach( $this->FORUMOPTIONS as $name => $bit ) {
					$row[ $name ] = ( $row['options'] & $bit ) ? 1 : 0;
				}

				// Is this forum on mod queue?
				$moderation = 0;
				if ($row['moderatenewpost'] and $row['moderatenewthread'])
				{
					$moderation = 1;
				}
				elseif ($row['moderatenewthread'])
				{
					$moderation = 2;
				}
				elseif ($row['moderatenewpost'])
				{
					$moderation = 1;
				}

				//-----------------------------------------
				// Save
				//-----------------------------------------

				$save = array(
					'topics'			=> $row['threadcount'],
					'posts'			  	=> $row['replycount'],
					'last_post'		  	=> $row['lastpost'],
					'last_poster_name'	=> $row['lastposter'],
					'parent_id'		  	=> $row['parentid'],
					'name'			  	=> $row['title'],
					'description'	  	=> $row['description'],
					'position'		  	=> $row['displayorder'],
					'use_ibc'		  	=> $row['allowbbcode'],
					'use_html'		  	=> $row['allowhtml'],
					'status'			=> $row['allowposting'],
					'inc_postcount'	  	=> 1,
					'password'		  	=> $row['password'],
					'sub_can_post'		=> ($row['parentid'] == -1) ? 1 : $row['cancontainthreads'],
					'redirect_on'		=> ($row['link']) ? 1 : 0,
					'redirect_url'		=> $row['link'],
					'preview_posts'		=> $moderation,
					'forum_allow_rating'=> $row['allowratings'],
					'inc_postcount'		=> $row['countposts'],
					);

				$this->lib->convertForum($row['forumid'], $save, $perms);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'subscribeforum', 'where' => "forumid={$row['forumid']}"));
				$subRes = ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch($subRes))
				{
					switch ($row['emailupdate'])
					{
						case 2:
							$type = 'daily';
							break;

						case 3:
							$type = 'weekly';

						default:
							$type = 'none';
							break;
					}

					$savetracker = array(
						'member_id'	=> $tracker['userid'],
						'forum_id'	=> $tracker['forumid'],
						'forum_track_type' => $type,
						);
					$this->lib->convertForumSubscription($tracker['subscribeforumid'], $savetracker);
				}

			}

			$this->lib->next();

		}


		private function convert_tags()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'thread',
							'order'		=> 'threadid ASC',
						);

			$loop = $this->lib->load('tags', $main, array());

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Sort out this (ridiculously overly complicated) prefix system...
				$prefix = ($row['prefixid']) ? ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'text', 'from' => 'phrase', 'where' => "varname='prefix_{$row['prefixid']}_title_plain'" ) ) : false;
				
				if ( $prefix )
				{
					$this->lib->convertTag(
						$row['prefixid'],
						array(
							'tag_prefix'		=> 1,
							'meta_id'			=> $row['threadid'],
							'meta_parent_id'	=> $row['forumid'],
							'tag'				=> $prefix['text']
						)
					);
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
			$main = array( 'select' => '*',
						   'from'   => 'thread',
						   'order'  => 'threadid ASC' );

			$loop = $this->lib->load('topics', $main, array('tracker'));

			$this->lib->prepareDeletionLog('topics');

			//---------------------------
			// Loop
			//---------------------------
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				$save = array( 'title'			  => $row['title'],
							   'state'			  => ($row['open'] == 1) ? 'open' : 'closed',
							   'posts'			  => $row['replycount'],
							   'starter_id'		  => $row['postuserid'],
							   'starter_name'	  => $row['postusername'],
							   'start_date'		  => $row['dateline'],
							   'last_post'		  => $row['lastpost'],
							   'last_poster_name' => $row['lastposter'],
							   'poll_state'		  => ($row['pollid'] > 0) ? 1 : 0,
							   'views'			  => $row['views'],
							   'forum_id'		  => $row['forumid'],
							   'approved'		  => ( $row['visible'] == 2 ) ? 0 : 1,
							   'pinned'			  => $row['sticky'],
							   'topic_hasattach'  => $row['attach'] );

				$this->lib->convertTopic($row['threadid'], $save);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'subscribethread', 'where' => "threadid={$row['threadid']}"));
				$subRes = ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch($subRes))
				{
					switch ($row['emailupdate'])
					{
						case 1:
							$type = 'immediate';
							break;

						case 2:
							$type = 'daily';
							break;

						case 3:
							$type = 'weekly';

						default:
							$type = 'none';
							break;
					}

					$savetracker = array( 'member_id'		 => $tracker['userid'],
										  'topic_id'		 => $tracker['threadid'],
										  'topic_track_type' => $type );
					$this->lib->convertTopicSubscription($tracker['subscribethreadid'], $savetracker);
				}

				//-----------------------------------------
				// Soft delete
				//-----------------------------------------
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'deletionlog', 'where' => "type='thread' AND primaryid='{$row['threadid']}'"));
				$deleteRes = ipsRegistry::DB('hb')->execute();
				while ($deletionData = ipsRegistry::DB('hb')->fetch($deleteRes))
				{
					$this->lib->convertDeletionLog( 'topics', $deletionData['primaryid'], $deletionData['userid'], $deletionData['dateline'], $deletionData['reason'] );
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
			$main = array( 'select' => '*',
						   'from'   => 'post',
						   'order'  => 'postid ASC' );

			$loop = $this->lib->load('posts', $main);
			$this->lib->prepareDeletionLog('posts');

			//---------------------------
			// Loop
			//---------------------------
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Has this post been editted?
				// I quite like this edit log feature :)
				//-----------------------------------------

				$log = false;
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'postedithistory', 'where' => "postid={$row['postid']}", 'order' => 'postedithistoryid ASC'));
				$editRes = ipsRegistry::DB('hb')->execute();
				while ($editlog = ipsRegistry::DB('hb')->fetch($editRes))
				{
					$log = $editlog;
				}

				//-----------------------------------------
				// We've got a reputation to think about here!
				//-----------------------------------------

				$rep = 0;
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'reputation', 'where' => "postid={$row['postid']}"));
				$repRes = ipsRegistry::DB('hb')->execute();
				while ($reprow = ipsRegistry::DB('hb')->fetch($repRes))
				{
					if ($reprow['reputation'] > 0)
					{
						$rep++;
					}
					elseif ($reprow['reputation'] < 0)
					{
						$rep--;
					}
				}

				//-----------------------------------------
				// Carry on
				//-----------------------------------------

				$save = array( 'append_edit'	=> ($log) ? 1 : 0,
							   'edit_time'		=> ($log) ? $log['dateline'] : '',
							   'edit_name'		=> ($log) ? str_replace( "'" , '&#39;', $log['username'] ) : '',
							   'post_edit_reason' => ($log) ? $log['reason'] : '',
							   'author_id'		=> $row['userid'],
							   'author_name' 	=> $row['username'],
							   'use_sig'     	=> $row['showsignature'],
							   'use_emo'     	=> $row['allowsmilie'],
							   'ip_address' 	=> $row['ipaddress'],
							   'post_date'   	=> $row['dateline'],
							   'post'		 	=> $this->fixPostData($row['pagetext']),
							   'queued'      	=> $row['visible'] == 2 ? 1 : 0,
							   'topic_id'    	=> $row['threadid'],
							   'post_title'  	=> $row['title'],
							   'rep_points'	=> $rep );

				$this->lib->convertPost($row['postid'], $save);

				//-----------------------------------------
				// Soft delete
				//-----------------------------------------
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'deletionlog', 'where' => "type='post' AND primaryid='{$row['postid']}'"));
				$deleteRes = ipsRegistry::DB('hb')->execute();
				while ($deletionData = ipsRegistry::DB('hb')->fetch($deleteRes))
				{
					$this->lib->convertDeletionLog( 'posts', $deletionData['primaryid'], $deletionData['userid'], $deletionData['dateline'], $deletionData['reason'] );
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
			$main = array( 'select' => '*',
							'from'  => 'poll',
							'order' => 'pollid ASC' );

			$loop = $this->lib->load('polls', $main, array('voters'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// What topic is this in?
				//-----------------------------------------
				$topic = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'threadid, forumid, postuserid', 'from' => 'thread', 'where' => "pollid='{$row['pollid']}'" ) );

				//-----------------------------------------
				// Convert votes
				//-----------------------------------------
				$votes = array();

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'pollvote', 'where' => "pollid={$row['pollid']}"));
				$voteRes = ipsRegistry::DB('hb')->execute();
				while ($voter = ipsRegistry::DB('hb')->fetch($voteRes))
				{
					$choice = array();
					
					// Do we already have this user's votes
					if (!$voter['userid'] or in_array($voter['userid'], $votes))
					{
						continue;
					}

					// Get their other votes
					ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'pollvote', 'where' => "pollid={$row['pollid']} AND userid={$voter['userid']}"));
					$voterRes = ipsRegistry::DB('hb')->execute();
					while ($thevote = ipsRegistry::DB('hb')->fetch($voterRes))
					{
						$choice[$thevote['votetype']] = str_replace( "'" , '&#39;', $thevote['voteoption'] );
					}

					// And save
					$vsave = array( 'vote_date'		=> $voter['votedate'],
									'tid'			=> $topic['threadid'],
									'member_id'		=> $voter['userid'],
									'forum_id'		=> $topic['forumid'],
									'member_choices'=> serialize(array(1 => $choice)) );

					$this->lib->convertPollVoter($voter['pollvoteid'], $vsave);
				}

				//-----------------------------------------
				// Then we can do the actual poll
				//-----------------------------------------
				$poll_array = array( // vB only allows one question per poll
									 1 => array( 'question'	=> str_replace( "'" , '&#39;', $row['question'] ),
									 			 'multi'	=> $row['multiple'],
									 			 'choice'	=> explode('|||', str_replace( "'" , '&#39;',$row['options']) ),
									 			 'votes'	=> explode('|||', $row['votes']) ) );

				$save = array( 'tid'			  => $topic['threadid'],
							   'start_date'		  => $row['dateline'],
							   'choices'   		  => serialize($poll_array),
							   'starter_id'		  => $topic['postuserid'],
							   'votes'     		  => $row['voters'],
							   'forum_id'  		  => $topic['forumid'],
							   'poll_question'	  => str_replace( "'" , '&#39;', $row['question'] ),
							   'poll_view_voters' => $row['public'] );

				$this->lib->convertPoll($voter['pollvoteid'], $save);
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
			$main = array( 'select' => '*',
							'from'  => 'pmtext',
							'order' => 'pmtextid ASC' );

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
					'msg_id'			=> $row['pmtextid'],
					'msg_topic_id'      => $row['pmtextid'],
					'msg_date'          => $row['dateline'],
					'msg_post'          => $this->fixPostData($row['message']),
					'msg_post_key'      => md5(microtime()),
					'msg_author_id'     => $row['fromuserid'],
					'msg_is_first_post' => 1
					);

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$maps = array();
				$_invited   = array();
				$recipient = array();

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'pm', 'where' => "pmtextid={$row['pmtextid']}"));
				$pmRes = ipsRegistry::DB('hb')->execute();
				while ($to = ipsRegistry::DB('hb')->fetch($pmRes))
				{
					if (!$to['userid'])
					{
						break;
					}

					foreach ($maps as $map)
					{
						if ($map['map_user_id'] == $to['userid'] and $map['map_topic_id'] == $to['pmtextid'])
						{
							break 2;
						}
					}

					$maps[] = array(
						'map_user_id'     => $to['userid'],
						'map_topic_id'    => $to['pmtextid'],
						'map_folder_id'   => 'myconvo',
						'map_read_time'   => 0,
						'map_last_topic_reply' => $row['dateline'],
						'map_user_active' => 1,
						'map_user_banned' => 0,
						'map_has_unread'  => $to['messageread'] > 1 ? 1 : 0,
						'map_is_system'   => 0,
						'map_is_starter'  => ( $to['userid'] == $row['fromuserid'] ) ? 1 : 0,
						'map_last_topic_reply' => $row['dateline']
						);

					if ( $to['userid'] != $row['fromuserid'] )
					{
						$_invited[ $to['userid'] ] = $to['userid'];
						$recipient[] = $to['userid'];
					}
				}

				//-----------------------------------------
				// Topic Data
				//-----------------------------------------


				$topic = array(
					'mt_id'			     => $row['pmtextid'],
					'mt_date'		     => $row['dateline'],
					'mt_title'		     => $row['title'],
					'mt_starter_id'	     => $row['fromuserid'],
					'mt_start_time'      => $row['dateline'],
					'mt_last_post_time'  => $row['dateline'],
					'mt_invited_members' => serialize( array_keys( $_invited ) ),
					'mt_to_count'		 => count(  array_keys( $_invited ) ) + 1,
					'mt_to_member_id'	 => array_shift($recipient),
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
							'from' 		=> 'attachmenttype',
						);

			$loop = $this->lib->load('attachments_type', $main);

			//---------------------------
			// Loop
			//---------------------------

			$count = $this->request['st'];

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$count++;

				$rm = unserialize($row['mimetype']);
				$mime = str_replace('Content-type: ', '', $rm[0]);

				$save = array(
					'atype_extension'	=> $row['extension'],
					'atype_mimetype'	=> $mime,
					);

				$this->lib->convertAttachType($count, $save);
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
			
			$contenttype = ipsRegistry::DB ( 'hb' )->buildAndFetch ( array (
						'select'	=> 'contenttypeid',
						'from'		=> 'contenttype',
						'where'		=> 'class = \'Post\''
					) );

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'attachment',
							'order'		=> 'attachmentid ASC',
							'where'		=> 'contenttypeid = ' . $contenttype['contenttypeid']
						);

			$loop = $this->lib->load('attachments', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - if using database storage, enter "."):')), 'path');

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
				//-----------------------------------------
				// Init
				//-----------------------------------------

				$filedata = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'filedata', 'where' => "filedataid='" .intval($row['filedataid'])."'" ) );
				if ( array_key_exists( $row['contenttypeid'], $this->attachmentContentTypes ) )
				{
					$contenttype = $this->attachmentContentTypes[ $row['contenttypeid'] ];
				}
				else
				{
					$contenttype = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'contenttype', 'where' => "contenttypeid='".intval($row['contenttypeid'])."'" ) );
					$this->attachmentContentTypes[ $row['contenttypeid'] ] = $contenttype;
				}

				if ( $contenttype['class'] != 'Post' )
				{
					continue;
				}

				// What's the mimetype?
				$type = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments_type', 'where' => "atype_extension='{$filedata['extension']}'" ) );

				// Is this an image?
				$image = false;
				if (preg_match('/image/', $type['atype_mimetype']))
				{
					$image = true;
				}
				
				// Need to grab the topic ID
				$topicid = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'threadid', 'from' => 'post', 'where' => "postid='" . intval( $row['contentid'] ) . "'" ) );
				
				if ( ! $topicid )
				{
					$this->lib->logError ( $row['attachmentid'], 'Orphaned Attachment - Post Missing' );
					continue;
				}
				else
				{
					// Grab our real id
					$ipbTopic	= $this->lib->getLink( $topicid['threadid'], 'topics', true );
				}

				$save = array(
					'attach_ext'			=> $filedata['extension'],
					'attach_file'			=> $row['filename'],
					'attach_is_image'		=> $image,
					'attach_hits'			=> $row['counter'],
					'attach_date'			=> $row['dateline'],
					'attach_member_id'		=> $row['userid'],
					'attach_filesize'		=> $filedata['filesize'],
					'attach_rel_id'			=> $row['contentid'],
					'attach_rel_module'		=> 'post',
					'attach_parent_id'		=> $ipbTopic
					);

				//-----------------------------------------
				// Database
				//-----------------------------------------

				if ( !$row['filedata'] && $path == '.' )
				{
					// Race issue... seen it a couple times, the file data is lost but the row still exists.
					$this->lib->logError ( $row['attachmentid'], 'No File Data' );
					continue;
				}

				if ($filedata['filedata'])
				{
					$save['attach_location'] = $row['filename'];
					$save['data'] = $filedata['filedata'];

					$done = $this->lib->convertAttachment($row['attachmentid'], $save, '', true);
				}

				//-----------------------------------------
				// File storage
				//-----------------------------------------

				else
				{
					/*if ($path == '.')
					{
						$this->lib->error('You entered "." for the path but you have some attachments in the file system');
					}*/

					$tmpPath = '/' . implode('/', preg_split('//', $filedata['userid'],  -1, PREG_SPLIT_NO_EMPTY));
					$save['attach_location'] = "{$row['filedataid']}.attach";

					$done = $this->lib->convertAttachment($row['attachmentid'], $save, $path . $tmpPath);
				}

				//-----------------------------------------
				// Fix inline attachments
				//-----------------------------------------

				if ($done === true)
				{
					$aid = $this->lib->getLink($row['attachmentid'], 'attachments');
					$pid = $this->lib->getLink($save['attach_rel_id'], 'posts');

					if ( $pid )
					{
						$attachrow = $this->DB->buildAndFetch( array( 'select' => 'post', 'from' => 'posts', 'where' => "pid={$pid}" ) );

						$rawaid = $row['attachmentid'];
						$update = preg_replace("/\[ATTACH(.+?)\]".$rawaid."\[\/ATTACH\]/i", "[attachment={$aid}:{$save['attach_location']}]", $attachrow['post']);

						$this->DB->update('posts', array('post' => $update), "pid={$pid}");
					}
				}

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
							'from' 		=> 'announcement',
							'order'		=> 'announcementid ASC',
						);

			$loop = $this->lib->load('announcements', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'announce_title'	=> $row['title'],
					'announce_post'		=> $this->fixPostData($row['pagetext']),
					'announce_forum'	=> ($row['forumid'] == -1) ? '*' : $row['forumid'],
					'announce_member_id'=> $row['userid'],
					'announce_views'	=> $row['views'],
					'announce_start'	=> $row['startdate'],
					'announce_end'		=> $row['enddate'],
					);
				$this->lib->convertAnnouncement($row['announcementid'], $save);
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
							'from' 		=> 'bbcode',
							'order'		=> 'bbcodeid ASC',
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
				$replacement = str_replace('%1$s', '{content}', $row['bbcodereplacement']);

				$save = array(
					'bbcode_title'				=> $row['title'],
					'bbcode_desc'				=> $row['bbcodeexplanation'],
					'bbcode_tag'				=> $row['bbcodetag'],
					'bbcode_replace'			=> $replacement,
					'bbcode_useoption'			=> $row['twoparams'],
					'bbcode_example'			=> $row['bbcodeexample'],
					'bbcode_menu_option_text'	=> 'option',
					'bbcode_menu_content_text'	=> 'content',
					'bbcode_groups'				=> 'all',
					'bbcode_sections'			=> 'all',
					'bbcode_parse'				=> 2,
					'bbcode_app'				=> 'core',
					);

				$this->lib->convertBBCode($row['bbcodeid'], $save, $us['custom_bbcode_opt']);
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
							'from' 		=> 'profilefield',
							'order'		=> 'profilefieldid ASC',
						);

			$loop = $this->lib->load('pfields', $main, array('pfields_groups'));

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$us['multi_fields'] = array();

			//-----------------------------------------
			// Create an unfiled group
			//-----------------------------------------

			if (!$us['pfield_group'])
			{
				$group = $this->lib->convertPFieldGroup(99, array('pf_group_name' => 'Converted', 'pf_group_key' => 'vbulletin'), true);
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
				//-----------------------------------------
				// Sort out groups
				//-----------------------------------------

				$usegroup = false;
				if ($row['profilefieldcategoryid'])
				{
					$usegroup = true;
					if (!$this->lib->getLink($row['profilefieldcategoryid'], 'pfields_groups', true))
					{
						$group = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'profilefieldcategory', 'where' => "profilefieldcategoryid = '{$row['profilefieldcategoryid']}'" ) );
						$glang = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'text', 'from' => 'phrase', 'where' => "varname = 'category{$group['profilefieldcategoryid']}_title'" ) );
						$savegroup = array(
							'pf_group_name'	=> $glang['text'],
							'pf_group_key'	=> 'vbcat'.$group['profilefieldcategoryid'],
							);
	 					$this->lib->convertPFieldGroup($group['profilefieldcategoryid'], $savegroup);
					}
				}

				//-----------------------------------------
				// Now the data
				//-----------------------------------------

				// This phrase table is the most ridiculous idea yet...
				$lang_title = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'text', 'from' => 'phrase', 'where' => "varname = 'field{$row['profilefieldid']}_title'" ) );
				$lang_desc  = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'text', 'from' => 'phrase', 'where' => "varname = 'field{$row['profilefieldid']}_desc'" ) );

				// Implode data
				$data = array();
				if ($row['data'])
				{
					$tmpData = unserialize($row['data']);
					if ( is_array($tempData) )
					{
						foreach ( $tempData as $key => $value)
						{
							$data[] = "{$key}={$value}";
						}
					}
				}
				// Type?
				switch ($row['type'])
				{
					case 'textarea':
						$type = 'textarea';
						break;

					case 'radio':
						$type = 'radio';
						break;

					case 'select':
						$type = 'drop';
						break;

					case 'select_multiple':
					case 'checkbox';
						$type = 'cbox';
						$us['multi_fields'][ $row['profilefieldid'] ] = $row['data'];
						break;

					default:
						$type = 'input';
						break;
				}

				// Required?
				$not_null = 0;
				$reg = 0;
				if ($row['required'] == 1 or $row['required'] == 3)
				{
					$not_null = 1;
					$reg = 1;
				}
				if ($row['required'] == 2)
				{
					$reg = 1;
				}

				// Editable?
				$editable = 0;
				if ($row['editable'] == 0 or $row['editable'] == 2)
				{
					$editable = 1;
				}

				// Finalise
				$save = array(
					'pf_title'		=> $lang_title['text'],
					'pf_desc'		=> $lang_desc['text'],
					'pf_content'	=> implode('|', $data),
					'pf_type'		=> $type,
					'pf_not_null'	=> $not_null,
					'pf_member_hide'=> $row['hidden'],
					'pf_max_input'	=> $row['maxlength'],
					'pf_member_edit'=> $editable,
					'pf_position'	=> $row['displayorder'],
					'pf_show_on_reg'=> $reg,
					'pf_group_id'	=> ($usegroup) ? $row['profilefieldcategoryid'] : 99,
					'pf_key'		=> 'vb'.$row['profilefieldid']
					);

				// And save
				$this->lib->convertPField($row['profilefieldid'], $save);
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
							'from' 		=> 'smilie',
							'order'		=> 'smilieid ASC',
						);

			$loop = $this->lib->load('emoticons', $main);

			//-----------------------------------------
			// We need to know the path and how to handle duplicates
			//-----------------------------------------

			$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_vb/images/smilies):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

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
					'typed'		=> $row['smilietext'],
					'image'		=> preg_replace('#^(.+)?images/smilies/(.+?)$#', '$2', $row['smiliepath']),
					'clickable'	=> 0,
					'emo_set'	=> 'default',
					);
				$done = $this->lib->convertEmoticon($row['smilieid'], $save, $us['emo_opt'], $path);
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
							'from'		=> array( 'moderator' => 'm' ),
							'add_join'	=> array(
											array(	'select' => 'mem.username',
							 						'from'   => array( 'user' => 'mem' ),
							 						'where'  => 'm.userid = mem.userid',
							 						'type'   => 'inner'
												),
											),
							'order'		=> 'moderatorid ASC',
						);

			$loop = $this->lib->load('moderators', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// We handle supermods slightly differently
				if ($row['forumid'] == -1)
				{
					continue;
				}

				foreach( $this->MOD_PERM as $name => $bit ) {
					$row[ $name ] = ( $row['permissions'] & $bit ) ? 1 : 0;
				}

				$save = array(
								   'forum_id'	  => $row['forumid'],
								   'member_name'  => $row['username'],
								   'member_id'	  => $row['userid'],
								   'edit_post'	  => $row['caneditposts'],
								   'edit_topic'	  => $row['caneditthreads'],
								   'delete_post'  => $row['candeleteposts'],
								   'delete_topic' => $row['canmanagethreads'],
								   'view_ip'	  => $row['canviewips'],
								   'open_topic'	  => $row['canopenclose'],
								   'close_topic'  => $row['canopenclose'],
								   'mass_move'	  => $row['canmassmove'],
								   'mass_prune'	  => $row['canmassprune'],
								   'move_topic'	  => $row['canmanagethreads'],
								   'pin_topic'	  => $row['canmanagethreads'],
								   'unpin_topic'  => $row['canmanagethreads'],
								   'post_q'		  => $row['canmoderateposts'],
								   'topic_q'	  => $row['canmoderateposts'],
								   'allow_warn'	  => 0,
								   'is_group'	  => 0,
								   'split_merge'  => $row['canmanagethreads'] );


				$this->lib->convertModerator($row['mid'], $save);
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
							'from' 		=> 'userlist',
							'where'		=> "type='buddy'",
							'order'		=> 'userid ASC',
						);

			$loop = $this->lib->load('profile_friends', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'friends_member_id'	=> $row['userid'],
					'friends_friend_id'	=> $row['relationid'],
					'friends_approved'	=> 1,
					);
				$this->lib->convertFriend($row['userid'].'-'.$row['relationid'], $save);
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
							'from' 		=> 'userlist',
							'where'		=> "type='ignore'",
							'order'		=> 'userid ASC',
						);

			$loop = $this->lib->load('ignored_users', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'ignore_owner_id'	=> $row['userid'],
					'ignore_ignore_id'	=> $row['relationid'],
					'ignore_messages'	=> '1',
					'ignore_topics'		=> '1',
					);
				$this->lib->convertIgnore($row['userid'].'-'.$row['relationid'], $save);
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
							'from' 		=> 'reputation',
							'order'		=> 'reputationid ASC',
						);

			$loop = $this->lib->load('reputation_index', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'member_id'	=> $row['whoadded'],
					'app'		=> 'forums',
					'type'		=> 'pid',
					'type_id'	=> $row['postid'],
					'rep_date'	=> $row['dateline'],
					'rep_msg'	=> $row['reason'],
					'rep_rating'=> ($row['reputation'] > 0) ? 1 : -1,
					);
				$this->lib->convertRep($row['reputationid'], $save);
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
							'from' 		=> 'visitormessage',
							'order'		=> 'vmid ASC',
						);

			$loop = $this->lib->load('profile_comments', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'status_member_id'	=> $row['userid'],
					'status_author_id'	=> $row['postuserid'],
					'status_date'		=> $row['dateline'],
					'status_author_ip'	=> $row['ipaddress'],
					'status_content'	=> $row['pagetext'],
					'status_approved'	=> ($row['state'] == 'visible') ? 1 : 0,
					);
				$this->lib->convertProfileComment($row['vmid'], $save);
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
							'from' 		=> 'usertitle',
							'order'		=> 'usertitleid ASC',
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
					'posts'	=> $row['minposts'],
					'title'	=> $row['title'],
					);
				$this->lib->convertRank($row['usertitleid'], $save, $us['rank_opt']);
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
							'from' 		=> 'threadrate',
							'order'		=> 'threadrateid ASC',
						);

			$loop = $this->lib->load('topic_ratings', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'rating_tid'		=> $row['threadid'],
					'rating_member_id'	=> $row['userid'],
					'rating_value'		=> $row['vote'],
					'rating_ip_address'	=> $row['ipaddress'],
					);
				$this->lib->convertTopicRating($row['threadrateid'], $save);
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
							'from' 		=> 'infraction',
							'order'		=> 'infractionid ASC',
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

				$log = unserialize($row['log_data']);
				
				$save = array(
					'wlog_mid'		=> $row['userid'],
					'wlog_notes'	=> serialize(array('content' => $this->fixPostData($row['note']))),
					'wlog_date'		=> $row['dateline'],
					'wlog_type'		=> ($row['action'] == 0) ? 'neg' : 'pos',
					'wlog_addedby'	=> $row['whoadded']
					);

				//-----------------------------------------
				// Pass it on
				//-----------------------------------------

				$this->lib->convertWarn($row['infractionid'], $save);
			}

			$this->lib->next();

		}

	}
