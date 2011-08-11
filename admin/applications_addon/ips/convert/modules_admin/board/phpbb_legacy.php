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

$info = array(
	'key'	=> 'phpbb_legacy',
	'name'	=> 'phpBB 2.X',
	'login'	=> false,
);

class admin_convert_board_phpbb_legacy extends ipsCommand
{
	private $nukedPrefix = '';
	private $nuked = FALSE;

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
			'emoticons'		=> array(),
				//'pfields'		=> array(),
			'forum_perms'	=> array(),
			'groups' 		=> array('forum_perms'),
			'members'		=> array('groups'),
				//'profile_friends' => array('members'),
				//'ignored_users'	=> array('members'),
			'forums'		=> array(),
			'topics'		=> array('members', 'forums'),
			'posts'			=> array('members', 'topics'),
			'polls'			=> array('topics', 'members', 'forums'),
			'pms'			=> array('members', 'emoticons'),
			'ranks'			=> array(),
			//'attachments'	=> array('posts'),
			'badwords'		=> array(),
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
		$this->lib->sendHeader( 'phpBB 2.X &rarr; IP.Board Converter' );

		//-----------------------------------------
		// Are we connected?
		// (in the great circle of life...)
		//-----------------------------------------
		$this->HB = $this->lib->connect();

		if ( ipsRegistry::DB('hb')->checkForTable('attachments') ) {
			$this->actions['attachments'] = array('posts');
		}

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
				return  $this->lib->countRows($this->nukedPrefix . 'users', 'user_active<>0');
				break;

			case 'polls':
				return  $this->lib->countRows($this->nukedPrefix . 'topics', "topic_vote=1");
				break;

			case 'pms':
				return  $this->lib->countRows($this->nukedPrefix . 'privmsgs');
				break;

			case 'forum_perms':
			case 'groups':
				return  $this->lib->countRows($this->nukedPrefix . 'groups', 'group_single_user != 1');
				break;

			case 'posts':
			case 'topics':
			case 'attachments':
				return $this->lib->countRows($this->nukedPrefix . $action );
				break;

			case 'forums':
				return $this->lib->countRows($this->nukedPrefix . $action ) + $this->lib->countRows($this->nukedPrefix . 'categories' );
				break;

			//case 'custom_bbcode':
			//	return  $this->lib->countRows('bbcodes');
			//	break;

			case 'badwords':
				return  $this->lib->countRows($this->nukedPrefix . 'words');
				break;

			//case 'pfields':
			//	return  $this->lib->countRows('profile_fields');
			//	break;

			case 'emoticons':
				return  $this->lib->countRows($this->nukedPrefix . 'smilies');
				break;

			//case 'profile_friends':
				//return  $this->lib->countRows('zebra', 'friend=1');
				//break;

			//case 'ignored_users':
			//	return  $this->lib->countRows('zebra', 'foe=1');
			//	break;

			case 'ranks':
				return  $this->lib->countRows($this->nukedPrefix . 'ranks', 'rank_special=0');
				break;

			//case 'warn_logs':
				//return  $this->lib->countRows('warnings');
				//break;

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
		$main = array( 'select' 	=> '*',
						'from' 		=> $this->nukedPrefix . 'groups',
						'where' => 'group_single_user != 1',
						'order'		=> 'group_id ASC' );

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
		$main = array( 'select' 	=> '*',
						'from' 		=> $this->nukedPrefix . 'groups',
						'where' => 'group_single_user != 1',
						'order'		=> 'group_id ASC' );

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
			$save = array( 'g_title'   => $row['group_name'],
						   'g_perm_id' => $row['group_id'] );
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
		$pcpf = array( 'user_from'		=> 'Location',
						'user_icq'		=> 'ICQ Number',
						'user_aim'		=> 'AIM ID',
						'user_yim'		=> 'Yahoo ID',
						'user_msnm'		=> 'MSN ID',
						//'user_jabber'	=> 'Jabber ID',
						'user_website'	=> 'Website',
						'user_occ'		=> 'Occupation',
						'user_interests'=> 'Interests' );

		$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('pp_path', 'gal_path', 'avatar_salt')));

		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> 'phpbb.*',
						'from' 		=> array( $this->nukedPrefix . 'users' => 'phpbb' ),
						'order'		=> 'phpbb.user_id ASC',
						'where'		=> 'phpbb.user_active=1' );

		if ( $this->nuked )
		{
			$main['add_join'] = array( array( 'select' => 'pn.*',
											'from' => array( 'users' => 'pn' ),
											'where' => 'phpbb.user_id = pn.pn_uid',
											'type' => 'left' ) );
		}

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
		//$ask['avatar_salt']	= array('type' => 'text', 'label' => 'Avatar salt (this is the string that all files in the avatars uploads folder start with, no trailing underscore - if not applicable, enter "."): ');

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
		$pfields = array();


		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{

			//-----------------------------------------
			// Set info
			//-----------------------------------------

			// Secondary groups
			ipsRegistry::DB('hb')->build( array( 'select' => 'g.group_id',
												 'from' => array( $this->nukedPrefix . 'user_group ' => 'g' ),
												 'add_join' => array( array( 'from' => array( $this->nukedPrefix . 'groups' => 'gr' ),
													 						 'where' => 'g.group_id = gr.group_id',
													 						 'type' => 'inner' ) ),
												 'where' => "g.user_id='{$row['user_id']}' AND gr.group_single_user != 1",
												 'order' => "gr.group_type DESC, g.group_id ASC" ) );
			ipsRegistry::DB('hb')->execute();
			$sgroups = array();
			while ($group = ipsRegistry::DB('hb')->fetch())
			{
				$sgroups[] = $group['group_id'];
			}

			// Basic info
			$info = array( 'id'				=> $row['user_id'],
						   //'group'				=> $row['user_level'] == 2 ? $this->settings['admin_group'] : $this->settings['member_group'],
						   'secondary_groups'	=> implode(',', $sgroups),
						   'joined'			=> $row['user_regdate'],
						   'username'			=> $this->nuked ? $row['pn_uname'] : $row['username'],
						   'email'				=> $this->nuked ? $row['pn_email'] : $row['user_email'],
						   'md5pass'			=> $this->nuked ? $row['pn_pass'] : $row['user_password'] );

			// Member info
			$rank = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'rank_title', 'from' => $this->nukedPrefix . 'ranks', 'where' => 'rank_id='.intval($row['user_rank'])));
			$time_arry = explode( ".", $row['user_timezone']);
			$time_offset  = $time_arry[0];

			$members = array( 'ip_address'		=> '127.0.0.1',
				'last_visit'		=> $row['user_lastvisit'],
				'last_activity' 	=> $row['user_lastvisit'],
				'last_post'			=> $row['user_lastvisit'],
				'warn_level'		=> $row['user_warnings'],
				'warn_lastwarn'		=> $row['user_last_warning'],
				'posts'				=> $row['user_posts'],
				'time_offset'		=> $time_offset,
				//'title'				=> $rank['rank_title'],
				'email_pm'      	=> $row['user_notify_pm'],
				'members_disable_pm'=> ($row['user_allow_pm'] == 1) ? 0 : 1,
				'hide_email' 		=> $row['user_viewemail'] ? 0 : 1,
				'allow_admin_mails' => $row['user_allow_massemail'] );

			// Profile
			$profile = array( 'signature' => $this->fixPostData($row['user_sig']) );

			//-----------------------------------------
			// Avatars
			//-----------------------------------------
			$path = '';
			// Uploaded
			if ($row['user_avatar_type'] == 1)
			{
				$profile['photo_type'] = 'custom';
				$profile['photo_location'] = $row['user_avatar'];
				$path = $us['pp_path'];
			}
			// URL
			elseif ($row['user_avatar_type'] == 2)
			{
				$profile['photo_type'] = 'url';
				$profile['photo_location'] = $row['user_avatar'];
			}
			// Gallery
			elseif ($row['user_avatar_type'] == 3)
			{
				$profile['photo_type'] = 'custom';
				$profile['photo_location'] = $row['user_avatar'];
				$path = $us['gal_path'];
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
						'from' 		=> $this->nukedPrefix . 'categories',
						'order'		=> 'cat_id ASC' );

		$loop = $this->lib->load('forums', $main, array('forum_tracker'), array(), TRUE );

		$this->lib->getMoreInfo('forums', $loop, $ask);

		//---------------------------
		// Loop
		//---------------------------
		foreach ( $loop as $row )
		{
			// Set info
			$save = array( 'parent_id'	   => $row['parent_forum_id '] == 0 ? -1 : 'C_'.$row['parent_forum_id'],
						   'position'	   => $row['cat_order'],
						   'name'		   => $row['cat_title'],
						   'inc_postcount' => 1,
						   'sub_can_post'  => 0,
						   'status'		   => 1 );
			// Save
			$this->lib->convertForum('C_'.$row['cat_id'], $save, array());
		}

		//---------------------------
		// Set up
		//---------------------------
		ipsRegistry::DB('hb')->build( array( 'select' => '*',
											 'from' => $this->nukedPrefix . 'forums',
											 'order' => 'forum_id ASC' ) );
		$forumRes = ipsRegistry::DB('hb')->execute();

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($forumRes) )
		{
			$save = array( 'parent_id'		=> 'C_'.$row['cat_id'],
						   'position'		=> $row['forum_order'],
						   'name'			=> $row['forum_name'],
						   'description'	=> $row['forum_desc'],
						   'sub_can_post'	=> 1,
						   'redirect_on'	=> 0,
						   'redirect_hits' => 0,
						   'status'		=> ($row['forum_status'] == 1) ? 0 : 1,
						   'posts'			=> $row['forum_posts'],
						   'topics'		=> $row['forum_topics'],
						   'inc_postcount'		=> 1,
			 );
			

			$this->lib->convertForum($row['forum_id'], $save, array() );
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
		$main = array( 'select' 	=> 't.*',
						'from' 		=> array( $this->nukedPrefix . 'topics' => 't' ),
						'add_join' => array( array( 'select'	=> 'u.username as topic_first_poster_name',
													'from' 		=> array( $this->nukedPrefix . 'users' => 'u'),
													'where'		=> 't.topic_poster = u.user_id',
													'type'		=> 'left' ) ),
						'order'		=> 't.topic_id ASC' );

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

			$save = array( 'title'				=> $row['topic_title'],
							'state'		   	 	=> $row['topic_status'] == 0 ? 'open' : 'closed',
							'posts'		    	=> $row['topic_replies'],
							'starter_id'    	=> $row['topic_poster'],
							'starter_name'  	=> $row['topic_first_poster_name'],
							'start_date'    	=> $row['topic_time'],
							'poll_state'	 	=> ($row['topic_vote'] == 1) ? 'open' : 0,
							'views'			 	=> $row['topic_views'],
							'forum_id'		 	=> $row['forum_id'],
							'approved'		 	=> 1,
							'author_mode'	 	=> 1,
							'pinned'		 	=> $row['topic_type'] == 0 ? 0 : 1,
							'topic_hasattach'	=> $row['topic_attachment'] );

			$this->lib->convertTopic($row['topic_id'], $save);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->nukedPrefix . 'topics_watch', 'where' => "topic_id={$row['topic_id']}"));
			ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch())
			{
				$savetracker = array( 'member_id'	=> $tracker['user_id'],
									  'topic_id'	=> $tracker['topic_id'] );
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
		$main = array( 'select'	=> 'p.*',
						'from' 	 	=> array( $this->nukedPrefix . 'posts' => 'p' ),
						'add_join' 	=> array( array( 'select'	=> 'pt.post_text, pt.post_subject',
														'from' 		=> array( $this->nukedPrefix . 'posts_text' => 'pt'),
														'where'		=> 'p.post_id = pt.post_id',
														'type'		=> 'left' ),
											  array( 'select'	=> 'u.username, u.user_id',
														'from' 		=> array( $this->nukedPrefix . 'users' => 'u'),
														'where'		=> 'p.poster_id = u.user_id',
														'type'		=> 'left' )
											),
						'order'	=> 'p.post_id DESC' );
		$loop = $this->lib->load('posts', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'author_id'   => $row['poster_id'],
							'author_name' => $row['username'] ? $row['username'] : $row['post_username'],
							'use_sig'     => $row['enable_sig'],
							'use_emo'     => $row['enable_smilies'],
							'ip_address'  => $this->convert_ip($row['poster_ip']),
							'post_date'   => $row['post_time'],
							'post'		  => $this->fixPostData($row['post_text']),
							'queued'      => 0,
							'topic_id'    => $row['topic_id'],
							'post_title'  => $row['post_subject'] );

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

		$main = array( 'select' 	=> 'p.*',
						'from' 		=> array( $this->nukedPrefix . 'vote_desc' => 'p' ),
						'add_join' => array( array( 'select' => 't.*',
													'from' => array( $this->nukedPrefix . 'topics' => 't'),
													'where' => 'p.topic_id = t.topic_id',
													'type' => 'inner' ) ),
						'order'		=> 'p.vote_id ASC' );

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

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->nukedPrefix . 'vote_results', 'where' => "vote_id={$row['vote_id']}"));
			ipsRegistry::DB('hb')->execute();
			while ($options = ipsRegistry::DB('hb')->fetch())
			{
				$choice[ $options['vote_option_id'] ]	= $options['vote_option_text'];
				$votes[ $options['vote_option_id'] ]	= $options['vote_result_option_total'];
				$total_votes[] = $options['vote_result'];
			}

			//-----------------------------------------
			// Votes in another...
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->nukedPrefix . 'vote_voters', 'where' => "vote_id={$row['vote_id']}"));
			ipsRegistry::DB('hb')->execute();
			while ($voter = ipsRegistry::DB('hb')->fetch())
			{
				$vsave = array(
					'tid'			=> $row['topic_id'],
					'member_choices'=> serialize(array()),
					'member_id'		=> $voter['vote_user_id'],
					'ip_address'	=> $this->convert_ip($voter['vote_user_ip']),
					'forum_id'		=> $row['forum_id'] );

				$this->lib->convertPollVoter($row['topic_id'], $vsave);
			}

			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------

			$poll_array = array(
				// phpBB only allows one question per poll
				1 => array(
					'question'	=> $row['vote_text'],
					'choice'	=> $choice,
					'votes'		=> $votes,
					)
				);

			$save = array(
				'tid'			=> $row['topic_id'],
				'start_date'	=> $row['vote_start'],
				'choices'   	=> addslashes(serialize($poll_array)),
				'starter_id'	=> $row['topic_poster'],
				'votes'     	=> array_sum($total_votes),
				'forum_id'  	=> $row['forum_id'],
				'poll_question'	=> $row['vote_text']
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

		$main = array(	'select' 	=> 'pm.*',
						'from' 		=> array($this->nukedPrefix . 'privmsgs' => 'pm'),
						'add_join' => array( array( 'select' => 't.privmsgs_text',
													'from' => array( $this->nukedPrefix . 'privmsgs_text' => 't' ),
													'where' => 'pm.privmsgs_id = t. privmsgs_text_id',
													'type' => 'inner' ) ),
						'order'		=> 'pm.privmsgs_id ASC',
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
				'msg_id'			=> $row['privmsgs_id'],
				'msg_topic_id'      => $row['privmsgs_id'],
				'msg_date'          => $row['privmsgs_date'],
				'msg_post'          => $this->fixPostData($row['privmsgs_text']),
				'msg_post_key'      => md5(microtime()),
				'msg_author_id'     => $row['privmsgs_from_userid'],
				'msg_ip_address'    => $this->convert_ip($row['privmsgs_ip']),
				'msg_is_first_post' => 1 );

			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$maps = array(
				array(
				'map_user_id'     => $row['privmsgs_to_userid'],
				'map_topic_id'    => $row['privmsgs_id'],
				'map_folder_id'   => 'myconvo',
				'map_read_time'   => 0,
				'map_last_topic_reply' => $row['privmsgs_date'],
				'map_user_active' => 1,
				'map_user_banned' => 0,
				'map_has_unread'  => 0,
				'map_is_system'   => 0,
				'map_is_starter'  => 0
				)
			);

			$topic = array(
				'mt_id'			     => $row['privmsgs_to_userid'],
				'mt_date'		     => $row['privmsgs_date'],
				'mt_title'		     => $row['privmsgs_subject'],
				'mt_starter_id'	     => $row['privmsgs_from_userid'],
				'mt_start_time'      => $row['privmsgs_date'],
				'mt_last_post_time'  => $row['privmsgs_date'],
				'mt_invited_members' => serialize( array( $row['privmsgs_to_userid'] => $row['privmsgs_to_userid'] ) ),
				'mt_to_count'		 => 1,
				'mt_to_member_id'	 => $row['privmsgs_to_userid'],
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

		$main = array(	'select' 	=> 'a.*',
						'from' 		=> array( $this->nukedPrefix . 'attachments' => 'a' ),
						'add_join' => array ( array( 'select' => 'aa.*',
													 'from' => array( $this->nukedPrefix . 'attachments_desc' => 'aa' ),
													 'where' => 'a.attach_id = aa.attach_id',
													 'type' => 'inner' ) ),
						'order'		=> 'a.attach_id ASC',
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
			//$rowExtra = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => array( $this->nukedPrefix . ($row['post_id'] > 0 ? 'posts' : 'privmsgs') => 'p' ), 'where' => ($row['post_id'] > 0 ? "post_id='{$row['post_id']}'" : "privmsgs_id='{$row['privmsgs_id']}'" ) );

			// Is this an image?
			$image = in_array( $row['extension'], array( 'png', 'jpg', 'jpeg', 'gif' ) ) ? TRUE : FALSE;

			// Sort out data
			$save = array(
				'attach_ext'			=> $row['extension'],
				'attach_file'			=> $row['real_filename'],
				'attach_location'		=> $row['physical_filename'],
				'attach_is_image'		=> $image,
				'attach_hits'			=> $row['download_count'],
				'attach_date'			=> $row['filetime'],
				'attach_member_id'		=> $row['user_id_1'],
				'attach_filesize'		=> $row['filesize'],
				'attach_rel_id'			=> $row['post_id'] > 0 ? $row['post_id'] : $row['privmsgs_id'],
				'attach_rel_module'		=> $row['post_id'] > 0 ? 'post' : 'msg',
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

				$update = preg_replace('/\[attachment=\d+\]<!-- .+? -->' . preg_quote( $row['real_filename'] ) . '<!-- .+? -->\[\/attachment\]/i', "[attachment={$aid}:{$save['attach_location']}]", $attachrow[$field]);
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
						'from' 		=> $this->nukedPrefix . 'smilies',
						'order'		=> 'smilies_id ASC' );

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
			$save = array( 'typed'		=> $row['code'],
						   'image'		=> $row['smile_url'],
						   'clickable'	=> 1,
						   'emo_set'	=> 'default' );
			$done = $this->lib->convertEmoticon($row['smilies_id'], $save, $us['emo_opt'], $path);
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
						'from' 		=> $this->nukedPrefix . 'ranks',
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

	private function convert_ip($ip)
	{
    	$hexipbang = explode(".", chunk_split($ip, 2, "."));
    	return hexdec($hexipbang[0]) . "." . hexdec($hexipbang[1]) . "." . hexdec($hexipbang[2]) . "." . hexdec($hexipbang[3]);
	}
}