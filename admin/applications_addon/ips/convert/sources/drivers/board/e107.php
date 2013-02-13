<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * phpBB
 * Last Update: $Date: 2011-07-12 21:15:48 +0100 (Tue, 12 Jul 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 550 $
 */

$info = array( 'key'	=> 'e107',
			   'name'	=> 'e107 0.7',
			   'login'	=> false );

class admin_convert_board_e107 extends ipsCommand
{
	// Add your old root URL here. No trailing slash.
	// Temporary fix for fixPostData. See the TODO above that method.

	private $oldrooturl = 'http://www.example.org';
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
				//'custom_bbcode' => array(),
			//'emoticons'		=> array(),
				//'pfields'		=> array(),
			//'forum_perms'	=> array(),
			//'groups' 		=> array('forum_perms'),
			'members'		=> array(),
				//'profile_friends' => array('members'),
				//'ignored_users'	=> array('members'),
			'forums'		=> array(),
			'topics'		=> array('members', 'forums'),
			'posts'			=> array('members', 'topics'),
			//'polls'			=> array('topics', 'members', 'forums'),
			'pms'			=> array('members'),
			//'ranks'			=> array(),
			//'attachments'	=> array('posts'),
			//'badwords'		=> array(),
			//'banfilters'	=> array('members'),
			//'warn_logs'		=> array('members'),
			);

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------

		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'e107 0.7 &rarr; IP.Board Converter' );

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
			case 'members':
				return  $this->lib->countRows('user');
				break;

			case 'pms':
				return  $this->lib->countRows('private_msg');
				break;

			case 'posts':
				return $this->lib->countRows('forum_t' );
				break;

			case'topics':
				return $this->lib->countRows('forum_t', 'thread_parent = 0');
				break;

			case 'forums':
				return $this->lib->countRows('forum' );
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
			//case 'forums':
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
	 * @todo	Make {e_FILE} conversion work better.
	 * @param 	string		raw post data
	 * @return 	string		parsed post data
	 **/
	private function fixPostData($text)
	{
		// Convert [br] before the nl2br().
		$text = str_replace ( '[br]', "\n\r", $text );
		$text = nl2br($text);
		$text = html_entity_decode($text);

		$text = str_replace( '[html]', '', $text );
		$text = str_replace( '[/html] ', '', $text );

		//$text = preg_replace('/\[img:(width=\d+)?(&)?(height=\d+)?\]\{e_FILE\}(.+)\[\/img\]/iU', '[img]' . ipsRegistry::$settings['board_url'] . '/${1}[/img]', $text);
		$text = preg_replace( '/\[quote[0-9]+=(.*?)\]/', '[quote=\\1]', $text);
		$text = preg_replace( '/\[\/quote[0-9]+\]/', '[/quote]', $text);

		// Make an attempt at converting {e_FILE} (see @todo)
		$text = str_replace ( '{e_FILE}', $this->oldrooturl . '/e107_files/', $text );
		
		// Now convert [link]
		$text = preg_replace ( '/\[link=(.+)\](.+)\[\/link\]/si', '[url=${1}]${2}[/url]', $text );

		// ... and finally images.
		$text = preg_replace ( '/\[img:(width=\d+)?(&)?(height=\d+)?\](.+)\[\/img\]/iU', '[img]${1}[/img]', $text );

		return $text;
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
		$pcpf = array( //'user_from'		=> 'Location',
						//'user_icq'		=> 'ICQ Number',
						//'user_aim'		=> 'AIM ID',
						//'user_yim'		=> 'Yahoo ID',
						//'user_msnm'		=> 'MSN ID',
						//'user_jabber'	=> 'Jabber ID',
						//'user_website'	=> 'Website',
						//'user_occ'		=> 'Occupation',
						/*'user_interests'=> 'Interests'*/ );

		//$this->lib->saveMoreInfo('members');

		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> 'u.*',
						'from' 		=> array( 'user' => 'u' ),
						'add_join'	=> array( array(
														'select' 	=> 'e.*',
														'from'		=> array('user_extended' => 'e'),
														'where'		=> 'u.user_id = e.user_extended_id',
														'type'		=> 'left'),
											),
						'order'		=> 'u.user_id ASC' );

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		// We need to know how to the avatar paths
		//$ask['pp_path']  	= array('type' => 'text', 'label' => 'Path to avatars uploads folder (no trailing slash, default /path_to_e107/uploads): ');

		// And those custom profile fields
		/*$options = array('x' => '-Skip-');
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
		$pfields = array();*/


		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Set info
			//-----------------------------------------
			// Basic info
			$info = array( 'id'				=> $row['user_id'],
						   'group'				=> ($row['user_admin']) == '1' ? $this->settings['admin_group'] : $this->settings['member_group'],
						   'joined'			=> $row['user_join'],
						   'username'			=> $row['user_loginname'],
						   'display_name'	 => $row['user_name'],
						   'email'				=> $row['user_email'],
						   'md5pass'			=> $row['user_password'] );

			// Member info
			//$rank = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'rank_title', 'from' => $this->nukedPrefix . 'ranks', 'where' => 'rank_id='.intval($row['user_rank'])));

			$members = array( 'ip_address'		=> $row['user_ip'],
				'last_visit'		=> $row['user_lastvisit'],
				'last_activity' 	=> $row['user_currentvisit'],
				'last_post'			=> $row['user_lastpost'],
				//'warn_level'		=> $row[''],
				//'warn_lastwarn'		=> $row[''],
				'posts'				=> $row['user_forums'],
				'time_offset'		=> $row['user_timezone'],
				'title'				=> $rank['user_customtitle'],
				//'email_pm'      	=> 0,
				//'members_disable_pm'=> ($row[''] == 1) ? 0 : 1,
				'hide_email' 		=> $row['user_hideemail'],
				//'allow_admin_mails' => $row['']
			);

			// Profile
			$profile = array( 'signature' => $this->fixPostData($row['user_signature']),
							  /*'pp_profile_views' => $row['user_visits']*/ );

			//-----------------------------------------
			// Avatars - Skip for now
			//-----------------------------------------
			$path;
			if ( FALSE && $row['user_image'] != '' && $row['user_image'] != NULL )
			{
				// URL
				if (preg_match('/http/', $row['user_image']))
				{
					$profile['photo_type'] = 'url';
					$profile['photo_location'] = $row['user_image'];
				}
				// Gallery
				else
				{
					$profile['photo_type'] = 'custom';
					$profile['photo_location'] = $row['user_image'];
					$path = $us['pp_path'];
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

		//$this->lib->saveMoreInfo('forums');

		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> '*',
						'from' 		=> 'forum',
						'order'		=> 'forum_id ASC' );

		$loop = $this->lib->load('forums', $main, array(), array(), TRUE );

		//->lib->getMoreInfo('forums', $loop, $ask);

		//---------------------------
		// Loop
		//---------------------------
		foreach ( $loop as $row )
		{
			// Set info
			$save = array( 'parent_id'	   => $row['forum_parent'] == 0 ? -1 : $row['forum_parent'],
						   'position'	   => $row['forum_order'],
						   'name'		   => $row['forum_name'],
						   'description' => $row['forum_description'],
						   'topics' => $row['forum_replies'],
						   'posts' => $row['forum_threads'],
						   'sub_can_post'  => ($row['forum_parent'] == 0) ? 0 : 1 );

			// Save
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
		$main = array( 'select'		=> '*',
					 'from'			=> 'forum_t',
					 'where' 		=> 'thread_parent = 0',
					 'order' 		=> 'thread_id ASC' );

		$loop = $this->lib->load('topics', $main, array());

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'title'				=> $row['thread_name'],
							'state'		   	 	=> $row['thread_active'] != 1 ? 'closed' : 'open',
							'posts'		    	=> $row['thread_total_replies'],
							'starter_id'    	=> substr( $row['thread_user'], 0, strpos($row['thread_user'], '.') ),
							'starter_name'  	=> substr( $row['thread_user'], strpos($row['thread_user'], '.') + 1 ),
							'start_date'    	=> $row['thread_datestamp'],
							'poll_state'	 	=> 0,
							'views'			 	=> $row['thread_views'],
							'forum_id'		 	=> $row['thread_forum_id'],
							'approved'		 	=> $row['thread_active'],
							'author_mode'	 	=> 1,
							'pinned'		 	=> $row['thread_s'] == '1' ? 1 : 0,
							'topic_hasattach'	=> 0 );

			$this->lib->convertTopic($row['thread_id'], $save);
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
						'from' 	 => 'forum_t',
						'order' => 'thread_id ASC' );
		$loop = $this->lib->load('posts', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'author_id'   => substr( $row['thread_user'], 0, strpos($row['thread_user'], '.') ),
							'author_name' => substr( $row['thread_user'], strpos($row['thread_user'], '.') + 1 ),
							'use_sig'     => 1,
							'use_emo'     => 1,
							'ip_address'  => '127.0.0.1',
							'post_date'   => $row['thread_datestamp'],
							'post'		  => $this->fixPostData($row['thread_thread']),
							'queued'      => ( $row['thread_active'] == '1' ) ? 0 : 1,
							'topic_id'    => ( $row['thread_parent'] == '0' ) ? $row['thread_id'] : $row['thread_parent'],
							'post_title'  => $row['thread_name'] );

			$this->lib->convertPost($row['thread_id'], $save);
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
					   'from' => 'private_msg',
					   'order' => 'pm_id ASC' );

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
							'msg_date'          => $row['pm_sent'],
							'msg_post'          => $this->fixPostData($row['pm_text']),
							'msg_post_key'      => md5(microtime()),
							'msg_author_id'     => $row['pm_from'],
							'msg_ip_address'    => '127.0.0.1',
							'msg_is_first_post' => 1 );

			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$maps = array(
				array(
				'map_user_id'     => $row['pm_to'],
				'map_topic_id'    => $row['pm_id'],
				'map_folder_id'   => 'myconvo',
				'map_read_time'   => $row['pm_read'],
				'map_last_topic_reply' => $row['pm_sent'],
				'map_user_active' => 1,
				'map_user_banned' => 0,
				'map_has_unread'  => $row['pm_read'] == 0 ? 1 : 0,
				'map_is_system'   => 0,
				'map_is_starter'  => 0
				)
			);

			$topic = array(
				'mt_id'			     => $row['pm_id'],
				'mt_date'		     => $row['pm_sent'],
				'mt_title'		     => $row['pm_subject'],
				'mt_starter_id'	     => $row['pm_from'],
				'mt_start_time'      => $row['pm_sent'],
				'mt_last_post_time'  => $row['pm_sent'],
				'mt_invited_members' => serialize( array( $row['pm_to'] => $row['pm_to'] ) ),
				'mt_to_count'		 => 1,
				'mt_to_member_id'	 => $row['pm_to'],
				'mt_replies'		 => 0,
				'mt_is_draft'		 => 0,
				'mt_is_deleted'		 => $row['pm_read_del'],
				'mt_is_system'		 => 0
				);

			//-----------------------------------------
			// Go
			//-----------------------------------------
			$this->lib->convertPM($topic, array($post), $maps);
		}
		$this->lib->next();
	}
}