<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * phpBB
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
		'key'	=> 'phpbb',
		'name'	=> 'phpBB 3.0',
		'login'	=> true,
	);

	class admin_convert_board_phpbb extends ipsCommand
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
				'pfields'		=> array(),
				'forum_perms'	=> array(),
				'groups' 		=> array('forum_perms'),
				'members'		=> array('groups', 'custom_bbcode', 'pfields'),
				'profile_friends' => array('members'),
				'ignored_users'	=> array('members'),
				'forums'		=> array(),
				'topics'		=> array('members', 'forums'),
				'posts'			=> array('members', 'topics', 'custom_bbcode', 'emoticons'),
				'polls'			=> array('topics', 'members', 'forums'),
				'pms'			=> array('members', 'custom_bbcode', 'emoticons'),
				'ranks'			=> array(),
				'attachments'	=> array('posts'),
				'badwords'		=> array(),
				'banfilters'	=> array('members'),
				'warn_logs'		=> array('members'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'phpBB &rarr; IP.Board Converter' );

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
				$this->lib->menu( array(
					'banfilters' => array(
						'single' => 'banlist',
						'multi'  => array( 'banlist', 'disallow' )
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
					return  $this->lib->countRows('groups');
					break;

				case 'members':
					return  $this->lib->countRows('users', 'user_type<>2');
					break;

				case 'polls':
					return  $this->lib->countRows('topics', "poll_title != ''");
					break;

				case 'pms':
					return  $this->lib->countRows('privmsgs');
					break;

				case 'custom_bbcode':
					return  $this->lib->countRows('bbcodes');
					break;

				case 'badwords':
					return  $this->lib->countRows('words');
					break;

				case 'pfields':
					return  $this->lib->countRows('profile_fields');
					break;

				case 'emoticons':
					return  $this->lib->countRows('smilies');
					break;

				case 'profile_friends':
					return  $this->lib->countRows('zebra', 'friend=1');
					break;

				case 'ignored_users':
					return  $this->lib->countRows('zebra', 'foe=1');
					break;

				case 'ranks':
					return  $this->lib->countRows('ranks', 'rank_special=0');
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
				case 'members':
				case 'groups':
				case 'forum_perms':
				case 'ranks':
				case 'attachments':
				case 'emoticons':
				case 'custom_bbcode':
				case 'forums':
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
			$text = nl2br($text);
			$text = html_entity_decode($text);

			// I have no idea what phpBB was thinking, but they like to hav e [code:randomstring] tags instead of proper BBCode...
			// Oh, and just to spice things up, 'randomstring' can have a : in it
			$text = preg_replace("#(\w+)://#", "\\1{~~}//", $text );
			$text = preg_replace("#\[(\w+?)=([^\]:]*):([^\]]*)\]#", "[$1=$2]", $text);
			$text = str_replace( '{~~}//', '://', $text );
			$text = preg_replace("#\[(\w+?):([^\]]*)\]#", "[$1]", $text);
			$text = preg_replace( "#\[/([^\]:]*):([^\]]*)\]#"    , "[/$1]", $text );

			// We need to rework quotes a little (there's no standard on [quote]'s attributes)
			$text = str_replace('][quote', ']
[quote', $text);
			$text = preg_replace("#\[quote=(.+)\]#", "[quote name=$1]", $text);

			// We also don't need [/*] - IP.Board can work out XHTML for itself!
			$text = preg_replace("/\[\/\*\]/", '', $text);

			// Oh, and we need to sort out emoticons
			$text = preg_replace("/<!-- s(\S+?) --><img(?:[^<]+?)<!-- (?:\S+?) -->/", '$1', $text);

			// And URLs
			$text = preg_replace("#<a class=\"postlink\" href=\"(.*)\">(.*)</a>#", "[url=$1]$2[/url]", $text);

			return $text;
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
							'from' 		=> 'bbcodes',
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
				if (preg_match('/(.+)=/', $row['bbcode_tag'], $matches))
				{
					$option = 1;
					$tag = $matches[1];

					// Get the things they've defined this as
					preg_match('/\[.+=(.+)\](.+)\[\/.+\]/', $row['bbcode_match'], $matches2);
					// And replace it out
					$replacement = str_replace($matches2[1], '{option}', $row['bbcode_tpl']);
					$replacement = str_replace($matches2[2], '{content}', $row['bbcode_tpl']);
				}
				else
				{
					$option = 0;
					$tag = $row['bbcode_tag'];

					// Get the thing they've defined this as
					preg_match('/\[.+\](.+)\[\/.+\]/', $row['bbcode_match'], $matches2);
					// And replace it out
					$replacement = str_replace($matches2[1], '{content}', $row['bbcode_tpl']);

				}

				$save = array(
					'bbcode_title'				=> $row['bbcode_tag'],
					'bbcode_tag'				=> $tag,
					'bbcode_replace'			=> $replacement,
					'bbcode_useoption'			=> $option,
					'bbcode_example'			=> $row['bbcode_match'],
					'bbcode_menu_option_text'	=> $option ? $matches[1] : '', // I know that's REALLY sloppy, but it's the best I could do
					'bbcode_menu_content_text'	=> $option ? $matches[2] : '',
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

			foreach( $loop as $row )
			{
				$prefix = '';
				$suffix = '';
				if ($row['group_colour'])
				{
					$prefix = "<span style='color:{$row['group_color']}'>";
					$suffix = '</span>';
				}

				$save = array(
					'g_title'			=> $row['group_name'],
					'g_hide_from_list'	=> ($row['group_legend'] == 0) ? 1 : 0,
					'g_use_pm'			=> $row['group_receive_pm'],
					'g_max_messages'	=> $row['group_message_limit'],
					'g_max_mass_pm'		=> $row['group_max_recipients'],
					'prefix'			=> $prefix,
					'suffix'			=> $suffix,
					'g_perm_id'			=> $row['group_id'],
					);
				$this->lib->convertGroup($row['group_id'], $save);
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
				'user_from'		=> 'Location',
				'user_icq'		=> 'ICQ Number',
				'user_aim'		=> 'AIM ID',
				'user_yim'		=> 'Yahoo ID',
				'user_msnm'		=> 'MSN ID',
				'user_jabber'	=> 'Jabber ID',
				'user_website'	=> 'Website',
				'user_occ'		=> 'Occupation',
				'user_interests'=> 'Interests',
				);

			$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('pp_path', 'gal_path', 'avatar_salt')));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'users',
							'order'		=> 'user_id ASC',
							'where'		=> 'user_type<>2'
						);

			$loop = $this->lib->load('members', $main);

			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();

			// We need to know how to the avatar paths
			$ask['pp_path']  	= array('type' => 'text', 'label' => 'Path to avatars uploads folder (no trailing slash, default /pathtophpbb/images/avatars/upload): ');
			$ask['gal_path'] 	= array('type' => 'text', 'label' => 'Path to avatars gallery folder (no trailing slash, default /pathtophpbb/images/avatars/gallery): ');
			$ask['avatar_salt']	= array('type' => 'text', 'label' => 'Avatar salt (this is the string that all files in the avatars uploads folder start with, no trailing underscore - if not applicable, enter "."): ');

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

				// Secondary groups
				ipsRegistry::DB('hb')->build(array('select' => 'group_id', 'from' => 'user_group', 'where' => "user_id='{$row['user_id']}'"));
				ipsRegistry::DB('hb')->execute();
				$sgroups = array();
				while ($group = ipsRegistry::DB('hb')->fetch())
				{
					$sgroups[] = $group['group_id'];
				}

				// Basic info
				$info = array(
					'id'				=> $row['user_id'],
					'group'				=> $row['group_id'],
					'secondary_groups'	=> implode(',', $sgroups),
					'joined'			=> $row['user_regdate'],
					'username'			=> $row['username'],
					'email'				=> $row['user_email'],
					'password'			=> $row['user_password'],
					);

				// Member info
				$rank = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'rank_title', 'from' => 'ranks', 'where' => 'rank_id='.$row['user_rank']));
				$time_arry = explode( ".", $row['user_timezone']);
				$time_offset  = $time_arry[0];
				
			 	if ($bday != " 0- 0-   0")
			 	{
  					$bday_day = substr($bday,0,2);
  					$bday_month = substr($bday,3,2); 
 					$bday_year = substr($bday,6,4);
 				} 
 				else 
 				{
 					$bday = false;
 				}

				$members = array(
					'ip_address'		=> $row['user_ip'],
					'conv_password'		=> $row['user_password'],
					'bday_day'			=> ($bday) ? $bday_day : '0',
					'bday_month'		=> ($bday) ? $bday_month : '0',
					'bday_year'			=> ($bday) ? $bday_year : '0',
					'last_visit'		=> $row['user_lastvisit'],
					'last_activity' 	=> $row['user_lastmark'],
					'last_post'			=> $row['user_lastpost_time'],
					'warn_level'		=> $row['user_warnings'],
					'warn_lastwarn'		=> $row['user_last_warning'],
					'posts'				=> $row['user_posts'],
					'time_offset'		=> $time_offset,
					'dst_in_use'		=> $row['user_dst'],
					'title'				=> $rank['rank_title'],
					'email_pm'      	=> $row['user_notify_pm'],
					'members_disable_pm'=> ($row['user_allow_pm'] == 1) ? 0 : 1,
					'hide_email' 		=> ($row['user_allow_viewemail']) == 1 ? 0 : 1,
					'allow_admin_mails' => $row['user_allow_massemail'],
					);

				// Profile
				$profile = array(
					'signature'			=> $this->fixPostData($row['user_sig']),
					);

				//-----------------------------------------
				// Avatars
				//-----------------------------------------

				$path = '';
				// Uploaded
				if ($row['user_avatar_type'] == 1)
				{
					$ex	= substr(strrchr($row['user_avatar'], '.'), 1);
					$profile['avatar_type'] = 'upload';
					$profile['avatar_location'] = $us['avatar_salt'].'_'.$row['user_id'].'.'.$ex;
					$profile['avatar_size'] = $row['user_avatar_width'].'x'.$row['user_avatar_height'];
					$path = $us['pp_path'];
				}
				// URL
				elseif ($row['user_avatar_type'] == 2)
				{
					$profile['avatar_type'] = 'url';
					$profile['avatar_location'] = $row['user_avatar'];
					$profile['avatar_size'] = $row['user_avatar_width'].'x'.$row['user_avatar_height'];
				}
				// Gallery
				elseif ($row['user_avatar_type'] == 3)
				{
					$profile['avatar_type'] = 'upload';
					$profile['avatar_location'] = $row['user_avatar'];
					$profile['avatar_size'] = $row['user_avatar_width'].'x'.$row['user_avatar_height'];
					$path = $us['gal_path'];
				}

				//-----------------------------------------
				// Custom Profile fields
				//-----------------------------------------

				// Get data from phpBB
				// We couldn't join this because we might loose user_id
				$get = array(	'select' => '*',
								'from'   =>	'profile_fields_data',
								'where'  => "user_id={$row['user_id']}",
							);
				$userpfields = ipsRegistry::DB('hb')->buildAndFetch( $get );

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
					if ($field['pf_type'] == 'drop')
					{
						$custom['field_'.$field['pf_id']] = $us['pfield_data'][$field['pf_key']][$userpfields['pf_'.$field['pf_key']]-1];
					}
					else
					{
						$custom['field_'.$field['pf_id']] = $userpfields['pf_'.$field['pf_key']];
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
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();

			$this->lib->saveMoreInfo('forums', array('forum_pass'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'forums',
							'order'		=> 'forum_id ASC',
						);

			$loop = $this->lib->load('forums', $main, array('forum_tracker'), array(), TRUE );

			//-----------------------------------------
			// We need to ask about passwords....
			//-----------------------------------------

			foreach ($loop as $forum)
			{
				if ($forum['forum_password'] and !$us['forumpass_'.$forum['forum_id']])
				{
					$ask['forum_pass['.$forum['forum_id'].']'] = array('type' => 'text', 'label' => 'Password for <strong>'.$forum['forum_name'].'</strong>: ', 'override' => array('name' => 'forum_pass', 'id' => $forum['forum_id']) );
				}
			}

			$this->lib->getMoreInfo('forums', $loop, $ask);

			//---------------------------
			// Loop
			//---------------------------

			foreach ( $loop as $row )
			{
				// Permissions will need to be reconfigured
				$perms = array();

				//-----------------------------------------
				// And go
				//-----------------------------------------

				$sub_can_post = 1;
				$redirect = 0;
				if ($row['forum_type'] == '0')
				{
					$sub_can_post = 0;
				}
				if ($row['forum_type'] == '2')
				{
					$redirect = 1;
				}

				$save = array(
					'parent_id'		=> ($row['parent_id']) ? $row['parent_id'] : -1,
					'position'		=> $row['left_id'],
					'name'			=> $row['forum_name'],
					'description'	=> $this->fixPostData($row['forum_desc']),
					'password'		=> $us['forum_pass'][$row['forum_id']],
					'rules_text'	=> $row['forum_rules'],
					'rules_title'	=> $row['forum_rules_link'],
					'sub_can_post'	=> $sub_can_post,
					'redirect_on'	=> $redirect,
					'redirect_url'	=> $row['forum_link'],
					'redirect_hits' => ($row['forum_type'] == 2) ? $row['forum_posts'] : 0,
					'status'		=> ($row['forum_status'] == 1) ? 0 : 1,
					'posts'			=> $row['forum_posts'],
					'topics'		=> $row['forum_topics'],
					'inc_postcount'		=> 1,
					);

				$this->lib->convertForum($row['forum_id'], $save, $perms);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'forums_watch', 'where' => "forum_id={$row['forum_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$savetracker = array(
						'member_id'	=> $tracker['user_id'],
						'forum_id'	=> $tracker['forum_id'],
						);
					$this->lib->convertForumSubscription($tracker['forum_id'].'-'.$tracker['user_id'], $savetracker);
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
							'from' 		=> 'topics',
							'order'		=> 'topic_id ASC',
						);

			$loop = $this->lib->load('topics', $main, array('tracker'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				// Ignore shadow topics
				if ($row['topic_status'] == 2)
				{
					continue;
				}

				$save = array(
					'title'				=> $row['topic_title'],
					'state'		   	 	=> $row['topic_status'] == 0 ? 'open' : 'closed',
					'posts'		    	=> $row['topic_replies'],
					'starter_id'    	=> $row['topic_poster'],
					'starter_name'  	=> $row['topic_first_poster_name'],
					'start_date'    	=> $row['topic_time'],
					'last_post' 	    => $row['topic_last_post_time'],
					'last_poster_id'	=> $row['topic_last_poster_id'],
					'last_poster_name'	=> $row['topic_last_poster_name'],
					'poll_state'	 	=> ($row['poll_title'] != '') ? 'open' : 0,
					'last_vote'		 	=> $row['poll_last_vote'],
					'views'			 	=> $row['topic_views'],
					'forum_id'		 	=> $row['forum_id'],
					'approved'		 	=> $row['topic_approved'],
					'author_mode'	 	=> 1,
					'pinned'		 	=> $row['topic_type'] == 0 ? 0 : 1,
					'topic_hasattach'	=> $row['topic_attachment'],
					);

				$this->lib->convertTopic($row['topic_id'], $save);

				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'topics_watch', 'where' => "topic_id={$row['topic_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$savetracker = array(
						'member_id'	=> $tracker['user_id'],
						'topic_id'	=> $tracker['topic_id'],
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
					'author_id'   => $row['poster_id'],
					'author_name' => $row['username'] ? $row['username'] : $row['post_username'],
					'use_sig'     => $row['enable_sig'],
					'use_emo'     => $row['enable_smilies'],
					'ip_address'  => $row['poster_ip'],
					'post_date'   => $row['post_time'],
					'post'		  => $this->fixPostData($row['post_text']),
					'queued'      => $row['post_approved'] == 1 ? 0 : 1,
					'topic_id'    => $row['topic_id']
					);

				$this->lib->convertPost($row['post_id'], $save);

				//-----------------------------------------
				// Report Center
				//-----------------------------------------

				$link = $this->lib->getLink($row['topic_id'], 'topics');
				if(!$link)
				{
					continue;
				}

				$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='post'" ) );
				$forum = $this->DB->buildAndFetch( array( 'select' => 'forum_id, title', 'from' => 'topics', 'where' => 'tid='.$link ) );

				$rs = array(	'select' 	=> '*',
								'from' 		=> 'reports',
								'order'		=> 'report_id ASC',
								'where'		=> 'post_id='.$row['post_id']
							);

				ipsRegistry::DB('hb')->build($rs);
				ipsRegistry::DB('hb')->execute();
				$reports = array();
				while ($rget = ipsRegistry::DB('hb')->fetch())
				{
					$report = array(
						'id'			=> $rget['report_id'],
						'title'			=> "Reported post #{$row['post_id']}",
						'status'		=>	($rget['report_closed']) ? $complete['status'] : $new['status'],
						'rc_class'		=> $rc['com_id'],
						'updated_by'	=> $rget['user_id'],
						'date_updated'	=> $rget['report_time'],
						'date_created'	=> $rget['report_time'],
						'exdat1'		=> $forum['forum_id'],
						'exdat2'		=> $row['topic_id'],
						'exdat3'		=> $row['post_id'],
						'num_reports'	=> '1',
						'num_comments'	=> '0',
						'seoname'		=> IPSText::makeSeoTitle( $forum['title'] ),
						);

					$reports = array(
						array(
								'id'			=> $rget['report_id'],
								'report'		=> $rget['report_text'],
								'report_by'		=> $rget['user_id'],
								'date_reported'	=> $rget['report_time']
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
							'from' 		=> 'topics',
							'where'		=> "poll_title != ''",
							'order'		=> 'topic_id ASC',
						);

			$loop = $this->lib->load('polls', $main, array('voters'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Options are stored in one place...
				//-----------------------------------------

				$choice = array();
				$votes = array();

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'poll_options', 'where' => "topic_id={$row['topic_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($options = ipsRegistry::DB('hb')->fetch())
				{
					$choice[ $options['poll_option_id'] ]	= $options['poll_option_text'];
					$votes[ $options['poll_option_id'] ]	= $options['poll_option_total'];
					$total_votes[] = $options['poll_option_total'];
				}

				//-----------------------------------------
				// Votes in another...
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'poll_votes', 'where' => "topic_id={$row['topic_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($voter = ipsRegistry::DB('hb')->fetch())
				{
					$vsave = array(
						'tid'			=> $voter['topic_id'],
						'member_choices'=> serialize(array(1 => array($voter['poll_option_id']))),
						'member_id'		=> $voter['vote_user_id'],
						'ip_address'	=> $voter['vote_user_ip'],
						'forum_id'		=> $row['forum_id']
						);

					$this->lib->convertPollVoter($row['topic_id'], $vsave);
				}

				//-----------------------------------------
				// Then we can do the actual poll
				//-----------------------------------------

				$poll_array = array(
					// phpBB only allows one question per poll
					1 => array(
						'question'	=> $row['poll_title'],
						'choice'	=> $choice,
						'votes'		=> $votes,
						)
					);

				$save = array(
					'tid'			=> $row['topic_id'],
					'start_date'	=> $row['topic_time'],
					'choices'   	=> addslashes(serialize($poll_array)),
					'starter_id'	=> $row['topic_poster'],
					'votes'     	=> array_sum($total_votes),
					'forum_id'  	=> $row['forum_id'],
					'poll_question'	=> $row['poll_title']
					);

				$this->lib->convertPoll($row['topic_id'], $save);
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
							'from' 		=> 'privmsgs',
							'order'		=> 'msg_id ASC',
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
					'msg_id'			=> $row['msg_id'],
					'msg_topic_id'      => $row['msg_id'],
					'msg_date'          => $row['message_time'],
					'msg_post'          => $this->fixPostData($row['message_text']),
					'msg_post_key'      => md5(microtime()),
					'msg_author_id'     => $row['author_id'],
					'msg_ip_address'    => $row['author_ip'],
					'msg_is_first_post' => 1
					);

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$maps = array();
				$_invited   = array();

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'privmsgs_to', 'where' => "msg_id={$row['msg_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($to = ipsRegistry::DB('hb')->fetch())
				{
					foreach ($maps as $map)
					{
						if ($map['map_user_id'] == $to['user_id'] and $map['map_topic_id'] == $to['msg_id'])
						{
							break 2;
						}
					}

					$maps[] = array(
						'map_user_id'     => $to['user_id'],
						'map_topic_id'    => $to['msg_id'],
						'map_folder_id'   => 'myconvo',
						'map_read_time'   => 0,
						'map_last_topic_reply' => $row['message_time'],
						'map_user_active' => 1,
						'map_user_banned' => 0,
						'map_has_unread'  => $to['pm_unread'],
						'map_is_system'   => 0,
						'map_is_starter'  => ( $to['user_id'] == $to['author_id'] ) ? 1 : 0
						);

					if ( $to['user_id'] != $to['author_id'] )
					{
						$_invited[ $to['user_id'] ] = $to['user_id'];
					}
				}

				//-----------------------------------------
				// Map Data
				//-----------------------------------------

				$explode = explode(':', $row['to_address']);
				$recipient = array_shift($explode);
				$recipient = str_replace('u_', '', $recipient);

				$topic = array(
					'mt_id'			     => $row['msg_id'],
					'mt_date'		     => $row['message_time'],
					'mt_title'		     => $row['message_subject'],
					'mt_starter_id'	     => $row['author_id'],
					'mt_start_time'      => $row['message_time'],
					'mt_last_post_time'  => $row['message_time'],
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
							'order'		=> 'attach_id ASC',
						);

			$loop = $this->lib->load('attachments', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_phpbb/files):')), 'path');

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
				// Is this an image?
				$image = false;
				if (preg_match('/image/', $row['mimetype']))
				{
					$image = true;
				}

				// Sort out data
				$save = array(
					'attach_ext'			=> $row['extension'],
					'attach_file'			=> $row['real_filename'],
					'attach_location'		=> $row['physical_filename'],
					'attach_is_image'		=> $image,
					'attach_hits'			=> $row['download_count'],
					'attach_date'			=> $row['filetime'],
					'attach_member_id'		=> $row['poster_id'],
					'attach_filesize'		=> $row['filesize'],
					'attach_rel_id'			=> $row['post_msg_id'],
					'attach_rel_module'		=> $row['in_message'] ? 'msg' : 'post',
					);


				// Send em on
				$done = $this->lib->convertAttachment($row['attach_id'], $save, $path);

				// Fix inline attachments
				if ($done === true)
				{
					$aid = $this->lib->getLink($row['attach_id'], 'attachments');

					switch ($save['attach_rel_module'])
					{
						case 'post':
							$field = 'post';
							$table = 'posts';
							$pid = $this->lib->getLink($save['attach_rel_id'], 'posts');
							$where = "pid={$pid}";
							break;

						case 'msg':
							$field = 'msg_id';
							$table = 'message_posts';
							$pid = $this->lib->getLink($save['attach_rel_id'], 'pm_posts');
							$where = "msg_id={$pid}";
							break;

						default:
							continue;
							break;
					}

					if(!$pid)
					{
						continue;
					}

					$attachrow = $this->DB->buildAndFetch( array( 'select' => $field, 'from' => $table, 'where' => $where ) );

					$update = preg_replace('/\[attachment=\d+\]<!-- .+? -->' . preg_quote( $row['real_filename'] ) . '<!-- .+? -->\[\/attachment\]/i', "[attachment={$aid}:{$save['attach_file']}]", $attachrow[$field]);
					$this->DB->update($table, array($field => $update), $where);

				}

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
							'order'		=> 'word_id ASC',
						);

			$loop = $this->lib->load('badwords', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$exact = '1';
				if ( strpos( $row['word'], '*' ) !== FALSE )
				{
					$row['word'] = str_replace( '*', '', $row['word'] );
					$exact = '0';
				}

				$save = array(
					'type'		=> $row['word'],
					'swop'		=> $row['replacement'],
					'm_exact'	=> $exact,
					);
				$this->lib->convertBadword($row['word_id'], $save);
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

			$loop = $this->lib->load('banfilters', $main, array(), 'disallow');

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				if ($row['ban_userid'])
				{
					$link = $this->lib->getLink($row['ban_userid'], 'members');
					if ($link)
					{
						$this->DB->update('members', array('member_banned' => 1), 'member_id='.$link);
					}
				}
				elseif ($row['ban_ip'])
				{
					$save = array(
						'ban_type'		=> 'ip',
						'ban_content'	=> $row['ban_ip'],
						'ban_date'		=> $row['ban_start'],
						);
					$this->lib->convertBan($row['ban_id'], $save);
				}
				elseif ($row['ban_email'])
				{
					$save = array(
						'ban_type'		=> 'email',
						'ban_content'	=> $row['ban_email'],
						'ban_date'		=> $row['ban_start'],
						);
					$this->lib->convertBan($row['ban_id'], $save);
				}
			}

			$this->lib->next();

		}

		/**
		 * Convert Disallowed usernames
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_disallow()
		{
			//---------------------------
			// Set up
			//---------------------------

			$mainBuild = array(	'select' 	=> '*',
								'from' 		=> 'disallow',
								'order'		=> 'disallow_id ASC',
							);

			$this->start = intval($this->request['st']);
			$this->end = $this->start + intval($this->request['cycle']);

			$mainBuild['limit'] = array($this->start, $this->end);

			if ($this->start == 0)
			{
				// Truncate
				$this->DB->build(array('select' => 'ipb_id as id', 'from' => 'conv_link', 'where' => "type = 'phpbb_disallow' AND duplicate = '0'"));
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

				$this->DB->delete('conv_link', "type = 'phpbb_disallow'");
			}

			$this->errors = unserialize($this->settings['conv_error']);

			ipsRegistry::DB('hb')->build($mainBuild);
			ipsRegistry::DB('hb')->execute();

			if (!ipsRegistry::DB('hb')->getTotalRows())
			{
				$action = 'banfilters';
				// Save that it's been completed
				$get = unserialize($this->settings['conv_completed']);
				$us = $get[$this->lib->app['name']];
				$us = is_array($us) ? $us : array();
				if (empty($this->errors))
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
				if (!empty($this->errors))
				{
					$es = 'The following errors occurred: <ul>';
					foreach ($this->errors as $e)
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
					'ban_content'	=> $row['disallow_username'],
					);
				$this->lib->convertBan($row['disallow_id'], $save);
			}

			//-----------------------------------------
			// Next
			//-----------------------------------------

			$total = $this->request['total'];
			$pc = round((100 / $total) * $this->end);
			$message = ($pc > 100) ? 'Finishing...' : "{$pc}% complete";
			IPSLib::updateSettings(array('conv_error' => serialize($this->errors)));
			$end = ($this->end > $total) ? $total : $this->end;
			$this->registry->output->redirect("{$this->settings['base_url']}app=convert&module={$this->lib->app['sw']}&section={$this->lib->app['app_key']}&do={$this->request['do']}&st={$this->end}&cycle={$this->request['cycle']}&total={$total}", "{$end} of {$total} converted<br />{$message}" );

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

			$main = array(	'select' 	=> 'f.*',
							'from' 		=> array('profile_fields' => 'f'),
							'order'		=> 'f.field_id ASC',
							'add_join'	=> array(
											array( 	'select' => 'l.*',
													'from'   =>	array( 'profile_lang' => 'l' ),
													'where'  => "f.field_id=l.field_id",
													'type'   => 'left'
												),
											),
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
				$group = $this->lib->convertPFieldGroup(1, array('pf_group_name' => 'Converted', 'pf_group_key' => 'phpbb'), true);
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
				if ($row['field_type'] == 1 or $row['field_type'] == 2 or $row['field_type'] == 6)
				{
					$type = 'input';
				}
				if ($row['field_type'] == 3)
				{
					$type = 'textarea';
				}
				elseif ($row['field_type'] == 5 or $row['field_type'] == 4)
				{
					$type = 'drop';
					$drop = array(	'select' 	=> '*',
									'from' 		=> 'profile_fields_lang',
									'order'		=> 'option_id ASC',
									'where'		=> 'field_id='.$row['field_id']
								);
					ipsRegistry::DB('hb')->build($drop);
					ipsRegistry::DB('hb')->execute();
					$options = array();
					while ($option = ipsRegistry::DB('hb')->fetch())
					{
						$options[] = $option['lang_value'];
						$us['pfield_data'][$row['field_ident']][$option['option_id']] = $option['lang_value'];
					}
					$content = implode('|', $options);
				}

				// Insert?
				$save = array(
					'pf_title'			=> $row['field_name'],
					'pf_desc'			=> $row['lang_explain'],
					'pf_content'		=> $content,
					'pf_type'			=> $type,
					'pf_not_null'		=> $row['field_required'],
					'pf_member_hide'	=> $row['field_hide'],
					'pf_max_input'		=> $row['field_maxlen'],
					'pf_member_edit'	=> $row['field_show_profile'],
					'pf_position'		=> $row['field_order'],
					'pf_show_on_reg'	=> $row['field_show_on_reg'],
					'pf_topic_format'	=> ($row['no_view']) ? '' : '<dt>{title}:</dt><dd>{content}</dd>',
					'pf_group_id'		=> 1,
					'pf_key'			=> $row['field_ident'],
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
							'order'		=> 'smiley_id ASC',
						);

			$loop = $this->lib->load('emoticons', $main);

			//-----------------------------------------
			// We need to know the path and how to handle duplicates
			//-----------------------------------------

			$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_phpbb/images/smilies):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

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
					'image'		=> $row['smiley_url'],
					'clickable'	=> $row['display_on_posting'],
					'emo_set'	=> 'default',
					);
				$done = $this->lib->convertEmoticon($row['smiley_id'], $save, $us['emo_opt'], $path);
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
							'from' 		=> 'zebra',
							'where'		=> 'friend=1',
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
					'friends_friend_id'	=> $row['zebra_id'],
					'friends_approved'	=> '1',
					);
				$this->lib->convertFriend($row['user_id'].'-'.$row['zebra_id'], $save);
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
							'from' 		=> 'zebra',
							'where'		=> 'foe=1',
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
					'ignore_ignore_id'	=> $row['zebra_id'],
					'ignore_messages'	=> '1',
					'ignore_topics'		=> '1',
					);
				$this->lib->convertIgnore($row['user_id'].'-'.$row['zebra_id'], $save);
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
							'where'		=> 'rank_special=0',
							'order'		=> 'rank_id ASC',
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
					'posts'	=> $row['rank_min'],
					'title'	=> $row['rank_title'],
					);
				$this->lib->convertRank($row['rank_id'], $save, $us['rank_opt']);
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

			$main = array(	'select' 	=> 'w.*',
							'from' 		=> array('warnings' => 'w'),
							'order'		=> 'w.warning_id ASC',
							'add_join'	=> array(
											array( 	'select' => 'l.*',
													'from'   =>	array( 'log' => 'l' ),
													'where'  => "w.log_id=l.log_id",
													'type'   => 'left'
												),
											),
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
					'wlog_mid'		=> $row['reportee_id'],
					'wlog_notes'	=> serialize(array('content' => $this->fixPostData($log[0]))),
					'wlog_date'		=> $row['warning_time'],
					'wlog_type'		=> 'neg',
					'wlog_addedby'	=> $row['user_id']
					);

				//-----------------------------------------
				// Pass it on
				//-----------------------------------------

				$this->lib->convertWarn($row['warning_id'], $save);
			}

			$this->lib->next();

		}

	}
