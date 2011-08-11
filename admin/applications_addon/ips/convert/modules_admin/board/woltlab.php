<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * Woltlab Burning Board
 * Last Update: $Date: 2011-08-02 17:12:19 +0100 (Tue, 02 Aug 2011) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 573 $
 */

$info = array( 'key'	=> 'woltlab',
			   'name'	=> 'Woltlab Burning Board 3.1',
			   'login'	=> true );

class admin_convert_board_woltlab extends ipsCommand
{
	/**
	 * Prefix for framework
	 *
	 * @var string
	 **/
	private $pf = 'wcf1_';

	/**
	 * Prefix for board
	 *
	 * @var string
	 **/
	private $pb = 'wbb1_1_';

	/**
	 * The default custom profile fields
	 *
	 * @var string
	 **/
	private $profile_fields = array(
		"'inlineHelpStatus'",
		"'timeZone'",
		"'enableDaylightSavingTime'",
		"'wysiwygEditorMode'",
		"'wysiwygEditorHeight'",
		"'messageParseURL'",
		"'messageEnableSmilies'",
		"'messageEnableHtml'",
		"'messageEnableBBCodes'",
		"'messageShowSignature'",
		"'showSignature'",
		"'birthday'",
		"'gender'",
		"'location'",
		"'occupation'",
		"'hobbies'",
		"'adminComment'",
		"'homepage'",
		"'email'",
		"'invisible'",
		"'hideEmailAddress'",
		"'protectedProfile'",
		"'shareWhitelist'",
		"'userCanMail'",
		"'onlyBuddyCanMail'",
		"'adminCanMail'",
		"'showAvatar'",
		"'icq'",
		"'aim'",
		"'yim'",
		"'msn'",
		"'skype'",
		"'acceptPm'",
		"'onlyBuddyCanPm'",
		"'emailOnPm'",
		"'showPmPopup'",
		"'pmsPerPage'",
		"'topThreadsStatus'",
		"'normalThreadsStatus'",
		"'enableSubscription'",
		"'enableEmailNotification'",
		"'postsPerPage'",
		"'threadsPerPage'",
		"'parentName'",
		"'parentRelationship'",
		"'parentPhone'",
		"'parentEmail'",
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
			'ignored_users'	=> array('members'),
			'forums'		=> array('forum_perms', 'members'),
			'moderators'	=> array('groups', 'members', 'forums'),
			'topics'		=> array('members', 'forums'),
			'topic_ratings' => array('topics', 'members'),
			'posts'			=> array('members', 'topics', 'emoticons'),
			'polls'			=> array('members', 'topics', 'posts'),
			'pms'			=> array('members', 'emoticons'),
			'attachments'	=> array('posts', 'pms'),
			);

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------

		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'Woltlab Burning Board &rarr; IP.Board Converter' );

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
			case 'groups':
				return  $this->lib->countRows($this->pf.'group');
				break;

			case 'groups':
				return  $this->lib->countRows($this->pf.'group');
				break;

			case 'members':
				return  $this->lib->countRows($this->pf.'user');
				break;

			case 'forums':
				return  $this->lib->countRows($this->pb.'board');
				break;

			case 'topics':
				return  $this->lib->countRows($this->pb.'thread');
				break;

			case 'posts':
				return  $this->lib->countRows($this->pb.'post');
				break;

			case 'polls':
				$count = @ipsRegistry::DB('hb')->buildAndFetch( array( 'select'   => 'count(*) as count',
																	   'from'     => array( $this->pb.'post' => 'p'),
																	   'add_join' => array( array( 'from' => array( $this->pb.'thread' => 't' ),
																								   'where' => 'p.threadID = t.threadID AND p.postID = t.firstPostID',
																								   'type' => 'inner' ),
										  													array( 'from' => array( $this->pf.'poll' => 'poll' ),
																								   'where' => 'p.pollID = poll.pollID',
																								   'type' => 'inner' ) ),
																		'where'   => "p.pollID > 0" ) );
				return $count['count'];
				break;

			case 'pms':
				return  $this->lib->countRows($this->pf.'pm');
				break;

			case 'attachments':
				return  $this->lib->countRows($this->pf.'attachment');
				break;

			case 'emoticons':
				return  $this->lib->countRows($this->pf.'smiley');
				break;

			case 'moderators':
				return  $this->lib->countRows($this->pb.'board_moderator');
				break;

			case 'profile_friends':
				return  $this->lib->countRows($this->pf.'user_whitelist');
				break;

			case 'ignored_users':
				return  $this->lib->countRows($this->pf.'user_blacklist');
				break;

			case 'topic_ratings':
				return  $this->lib->countRows($this->pb.'thread_rating');
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

		// Fix aligns
		$post = preg_replace("#\[align=(.+)\](.+)\[/align\]#i", "[$1]$2[/$1]", $post);

		// We don't have justify
		$post = preg_replace("#\[justify\](.+)\[/justify\]#i", "$1", $post);

		// Their 'mysql' is just 'sql' to us
		$post = preg_replace("#\[mysql\](.+)\[/mysql\]#i", "[sql]$1[/sql]", $post);

		// Fix quotes too! =O
		$post = preg_replace_callback("/\[quote='(.+)',index\.php\?page=Thread&postID=(\d+)#post(\d+)\]/i", array( &$this, 'fixQuotePid' ), $post);

		return $post;
	}

	/**
	 * Fix Quote Post ID
	 *
	 * @access	private
	 * @param 	array		Details of the quote found
	 * @return 	string		parsed post data
	 **/
	private function fixQuotePid($matches)
	{
		/* Init vars */
		$name = '';
		$post = '';

		/* Got a name? */
		if ( $matches[1] != '' )
		{
			$name = " name='{$matches[1]}'";
		}

		/* Got a post ID? Find the new ID then */
		if ( $matches[2] > 0 )
		{
			$pid = $this->lib->getLink( $matches[2], 'posts' );

			$post = $pid ? " post='{$pid}'" : '';
		}

		return "[quote{$name}{$post}]";
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
						'from' 		=> $this->pf.'group',
						'order'		=> 'groupID ASC',
					);

		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'groupID', 'nf' => 'groupName'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['groupID'], $row['groupName']);
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
						'from' 		=> $this->pf.'group',
						'order'		=> 'groupID ASC',
					);

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'groupID', 'nf' => 'groupName'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$save = array(
				'g_title'			=> $row['groupName'],
				'g_perm_id'			=> $row['groupID'],
				);
			$this->lib->convertGroup($row['groupID'], $save);
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
		// Load user_option
		//-----------------------------------------

		$getOptions = implode(',', $this->profile_fields);
		ipsRegistry::DB('hb')->build(array('select' => 'optionID, optionName', 'from' => $this->pf.'user_option', 'where' => "optionName IN($getOptions)"));
		ipsRegistry::DB('hb')->execute();
		$extra = array();
		while ($user_option = ipsRegistry::DB('hb')->fetch())
		{
			$extra[$user_option['optionName']] = $user_option['optionID'];
		}

		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------

		$pcpf = array(
			'gender'			=> 'Gender',
			'location'			=> 'Location',
			'occupation'		=> 'Occupation',
			'hobbies'			=> 'Hobbies',
			'adminComment'		=> 'Admin Comment',
			'homepage'			=> 'Website URL',
			'icq'				=> 'ICQ',
			'aim'				=> 'AIM',
			'yim'				=> 'YIM',
			'msn'				=> 'MSN',
			'skype'				=> 'Skype',
			);

		$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('')));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> 'u.*',
						'from' 		=> array($this->pf.'user' => 'u'),
						'add_join'	=> array(
										array( 	'select' => 'b.boardLastVisitTime, boardLastActivityTime, posts',
												'from'   =>	array( $this->pb.'user' => 'b' ),
												'where'  => "b.userID=u.userID",
												'type'   => 'left'
											),
										),
						'order'		=> 'u.userID ASC',
					);

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		$ask['pp_path']  	= array('type' => 'text', 'label' => 'Path to avatars uploads folder (no trailing slash, default /pathtowoltlab/wcf/images/avatars): ');

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
			$ask[$id] = array('type' => 'dropdown', 'label' => 'Custom profile field to store '.$name.': ', 'options' => $options);
		}

		$this->lib->getMoreInfo('members', $loop, $ask, 'path');

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Get extra fields
			//-----------------------------------------

			$user_option_value = ipsRegistry::DB('hb')->buildAndFetch(array('select' => '*', 'from' => $this->pf.'user_option_value', 'where' => "userID='{$row['userID']}'"));
			$fields = array();
			foreach ($extra as $key => $value)
			{
				$fields[$key] = $user_option_value['userOption'.$value];
			}

			//-----------------------------------------
			// Work stuff out
			//-----------------------------------------

			// Ranks
			$title = '';
			if ($row['userTitle'])
			{
				$title = $row['userTitle'];
			}
			elseif ($row['rankID'])
			{
				$rank_db = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'rankTitle', 'from' => $this->pf.'user_rank', 'where' => 'rankID='.$row['rankID']));
				$rank_lang = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'languageItemValue', 'from' => $this->pf.'language_item', 'where' => "languageItem='{$rank_db['rankTitle']}'"));
				$title = $rank_lang['languageItemValue'];
			}

			// Group
			ipsRegistry::DB('hb')->build(array('select' => 'groupID', 'from' => $this->pf.'user_to_groups', 'where' => "userID='{$row['userID']}'"));
			ipsRegistry::DB('hb')->execute();
			$sgroups = array();
			while ($group = ipsRegistry::DB('hb')->fetch())
			{
				$sgroups[] = $group['groupID'];
			}

			// Birthday
			$birthday = ($fields['birthday']) ? explode('-', $fields['birthday']) : array(0 => '', 1 => '', 2 => '');

			//-----------------------------------------
			// Set info
			//-----------------------------------------

			// Basic info
			$info = array(
				'id'				=> $row['userID'],
				'username'			=> $row['username'],
				'email'				=> $row['email'],
				'password'			=> $row['password'],
				'joined'			=> $row['registrationDate'],
				'secondary_groups'	=> implode(',', $sgroups),
				'group'				=> $row['userOnlineGroupID'] ? $row['userOnlineGroupID'] : array_pop($sgroups),
				);

			// Member info
			$members = array(
				'misc'				=> $row['salt'],
				'ip_address'		=> $row['registrationIpAddress'],
				'member_banned'		=> $row['banned'],
				'title'				=> $title,
				'last_activity'		=> $row['lastActivityTime'],
				'last_visit'		=> $row['boardLastVisitTime'],
				'posts'				=> $row['posts'],
				'time_offset'		=> $fields['timezone'],
				'members_auto_dst'	=> $fields['enableDaylightSavingTime'],
				'view_sigs'			=> $fields['showSignature'],
				'bday_day'			=> $birthday[2],
				'bday_month'		=> $birthday[1],
				'bday_year'			=> $birthday[0],
				'hide_email'		=> $fields['hideEmailAddress'],
				'allow_admin_mails' => $fields['adminCanMail'],
				'members_disable_pm'=> ($fields['acceptPm']) ? 0 : 1,
				'email_pm'			=> $fields['emailOnPm'],
				);

			// Profile
			$profile = array(
				'signature'			=> $this->fixPostData($row['signature']),
				);

			//-----------------------------------------
			// Avatars
			//-----------------------------------------

			$path = $us['pp_path'];
			if($row['avatarID'])
			{
				$avatar = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => $this->pf.'avatar', 'where' => "avatarID={$row['avatarID']}" ) );
				if($avatar)
				{
					$profile['photo_type'] = 'custom';
					$profile['pp_main_photo'] = 'avatar-'.$avatar['avatarID'].'.'.$avatar['avatarExtension'];
					$profile['pp_main_width'] = $row['width'];
					$profile['pp_main_height'] = $row['height'];
				}
			}

			//-----------------------------------------
			// And go!
			//-----------------------------------------

			$this->lib->convertMember($info, $members, $profile, array(), $path);
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
						'from' 		=> $this->pb.'board',
						'order'		=> 'boardID ASC',
					);

		$loop = $this->lib->load('forums', $main);

		// Do we need to create a forum?
		if(!$this->request['st'] AND !$this->lib->getLink('master', 'forums', true))
		{
			$this->lib->convertForum('master', array( 'name' => 'Woltlab Forums', 'parent_id' => -1 ), array());
		}

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'parent_id'		=> ($row['parentID']) ? $row['parentID'] : 'master',
				'name'			=> $row['title'],
				'description'	=> $row['description'],
				'sub_can_post'	=> ($row['boardType'] == 1) ? 0 : 1,
				'redirect_on'	=> ($row['boardType'] == 2) ? 1 : 0,
				'redirect_url'	=> $row['externalURL'],
				'status'		=> ($row['isClosed']) ? 0 : 1,
				'inc_postcount'	=> $row['countUserPosts'],
				'redirect_hits'	=> $row['clicks'],
				'topics'		=> $row['threads'],
				'posts'			=> $row['posts'],
				);

			$this->lib->convertForum($row['boardID'], $save, array());

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->pb.'board_subscription', 'where' => "boardID={$row['boardID']}"));
			ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch())
			{
				$savetracker = array(
					'member_id'	=> $tracker['userID'],
					'forum_id'	=> $tracker['boardID'],
					'forum_track_type' => ($tracker['enableNotification']) ? 'delayed' : 'none',
					);
				$this->lib->convertForumSubscription($tracker['boardID'].'-'.$tracker['userID'], $savetracker);
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
						'from' 		=> $this->pb.'thread',
						'order'		=> 'threadID ASC',
					);

		$loop = $this->lib->load('topics', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$pinned = false;
			if($row['isSticky'] or $row['isAnnouncement'])
			{
				$pinned = true;
			}

			$save = array(
				'forum_id'			=> $row['boardID'],
				'title'				=> $row['topic'],
				'start_date'		=> $row['time'],
				'starter_id'		=> $row['userID'],
				'starter_name'		=> $row['username'],
				'posts'				=> $row['replies'],
				'views'				=> $row['views'],
				'topic_hasattach'	=> $row['attachments'],
				'pinned'			=> $pinned,
				'state'				=> ($row['isClosed']) ? 'closed' : 'open',
				'approved'			=> 1,
				'topic_rating_total'=> $row['rating'],
				'topic_rating_hits'	=> $row['ratings'],
				);

			$this->lib->convertTopic($row['threadID'], $save);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->pb.'thread_subscription', 'where' => "threadID={$row['threadID']}"));
			ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch())
			{
				$savetracker = array(
					'member_id'	=> $tracker['userID'],
					'topic_id'	=> $tracker['threadID'],
					'topic_track_type' => ($tracker['enableNotification']) ? 'delayed' : 'none',
					);
				$this->lib->convertTopicSubscription($tracker['threadID'].'-'.$tracker['userID'], $savetracker);
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
						'from' 		=> $this->pb.'post',
						'order'		=> 'postID ASC',
					);

		$loop = $this->lib->load('posts', $main);

		//-----------------------------------------
		// Prepare for reports conversion
		//-----------------------------------------

		$this->lib->prepareReports('post');

		$new = $this->DB->buildAndFetch( array( 'select' => 'status', 'from' => 'rc_status', 'where' => 'is_new=1' ) );

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'topic_id'			=> $row['threadID'],
				'author_id'			=> $row['userID'],
				'author_name'		=> $row['username'],
				'post'				=> $this->fixPostData($row['message']),
				'post_date'			=> $row['time'],
				'use_emo'			=> $row['enableSmilies'],
				'post_htmlstate'	=> $row['enableHtml'],
				'use_sig'			=> $row['showSignature'],
				'ip_address'		=> $row['ipAddress'],
				);

			$this->lib->convertPost($row['postID'], $save);

			//-----------------------------------------
			// Report Center
			//-----------------------------------------

			$link = $this->lib->getLink($row['threadID'], 'topics');
			if(!$link)
			{
				continue;
			}

			$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='post'" ) );
			$forum = $this->DB->buildAndFetch( array( 'select' => 'forum_id, title', 'from' => 'topics', 'where' => 'tid='.$link ) );

			$rs = array(	'select' 	=> '*',
							'from' 		=> $this->pb.'post_report',
							'order'		=> 'reportID ASC',
							'where'		=> 'postID='.$row['postID']
						);

			ipsRegistry::DB('hb')->build($rs);
			ipsRegistry::DB('hb')->execute();
			$reports = array();
			while ($rget = ipsRegistry::DB('hb')->fetch())
			{
				$report = array(
					'id'			=> $rget['reportID'],
					'title'			=> "Reported post #{$row['postID']}",
					'status'		=> $new['status'],
					'rc_class'		=> $rc['com_id'],
					'updated_by'	=> $rget['userID'],
					'date_updated'	=> $rget['reportTime'],
					'date_created'	=> $rget['reportTime'],
					'exdat1'		=> $forum['forum_id'],
					'exdat2'		=> $row['threadID'],
					'exdat3'		=> $row['postID'],
					'num_reports'	=> '1',
					'num_comments'	=> '0',
					'seoname'		=> IPSText::makeSeoTitle( $forum['title'] ),
					);

				$reports = array(
					array(
							'id'			=> $rget['reportID'],
							'report'		=> $rget['report'],
							'report_by'		=> $rget['userID'],
							'date_reported'	=> $rget['reportTime']
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
	{// Woltlab allows multiple polls per topic by post. WE are only going to import polls if they are the topic first post.
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => 'p.threadID, p.userID',
						'from'  => array( $this->pb.'post' => 'p'),
						'add_join' => array( array( 'select' => 't.boardID, t.time',
													'from' => array( $this->pb.'thread' => 't' ),
													'where' => 'p.threadID = t.threadID AND p.postID = t.firstPostID',
													'type' => 'inner' ),
										  	 array( 'select' => 'poll.pollID, poll.question, poll.votes, poll.choiceCount',
													'from' => array( $this->pf.'poll' => 'poll' ),
													'where' => 'p.pollID = poll.pollID',
													'type' => 'inner' ) ),
						'where' => "p.pollID > 0",
						'order' => 'p.pollID ASC' );

		$loop = $this->lib->load('polls', $main, array('voters'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Options are stored in one place...
			//-----------------------------------------
			$choices = array();
			$votes = array();

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->pf.'poll_option', 'where' => "pollID={$row['pollID']}"));
			$optionRes = ipsRegistry::DB('hb')->execute();
			while ($options = ipsRegistry::DB('hb')->fetch($optionRes))
			{
				$choices[ $options['pollOptionID'] ]	= $options['pollOption'];
				$votes[ $options['pollOptionID'] ]	= $options['votes'];
				$total_votes[] = $options['votes'];
			}

			//-----------------------------------------
			// Convert votes
			//-----------------------------------------
			$voters = array();

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->pf.'poll_option_vote', 'where' => "pollID={$row['pollID']} AND userID > 0"));
			$voterRes = ipsRegistry::DB('hb')->execute();
			while ($voter = ipsRegistry::DB('hb')->fetch($voterRes))
			{
				// Do we already have this user's votes
				if (!$voter['userID'] or in_array($voter['userID'], $voters))
				{
					continue;
				}

				$userChoices = array();
				// Get their other votes
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->pf.'poll_option_vote', 'group' => 'pollOptionID', 'where' => "pollid={$row['pollID']} AND userID={$voter['userID']}"));
				$voteRes = ipsRegistry::DB('hb')->execute();
				while ($thevote = ipsRegistry::DB('hb')->fetch($voteRes))
				{
					$userChoices[$thevote['pollOptionID']] = str_replace( "'" , '&#39;', $thevote['pollOptionID'] );
				}

				// And save
				$vsave = array( 'vote_date'		=> time(),
								'tid'			=> $row['threadID'],
								'member_id'		=> $voter['userID'],
								'forum_id'		=> $row['boardID'],
								'member_choices'=> serialize(array(1 => $userChoices)) );

				$this->lib->convertPollVoter($row['pollID'], $vsave);
				$voters[] = $voter['userID'];
			}

			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------
			$poll_array = array( // only allows one question per poll
								 1 => array( 'question'	=> str_replace( "'" , '&#39;', $row['question'] ),
									 		 'multi'	=> $row['choiceCount'] > 0 ? 1 : 0,
									 		 'choice'	=> $choices,
									 		 'votes'	=> $votes ) );

			$save = array( 'tid'			  => $row['threadID'],
						   'start_date'		  => $row['time'],
						   'choices'   		  => addslashes(serialize($poll_array)),
						   'starter_id'		  => $row['userID'],
						   'votes'     		  => array_sum($total_votes),
						   'forum_id'  		  => $row['boardID'],
						   'poll_question'	  => str_replace( "'" , '&#39;', $row['question'] ),
						   'poll_view_voters' => $row['isPublic'] );

 			if ( $this->lib->convertPoll($row['pollID'], $save) === TRUE )
			{
				$tid = $this->lib->getLink($save['tid'], 'topics');
				$this->DB->update('topics', array( 'poll_state' => 1 ), "tid='" . intval($tid) . "'");
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
						'from' 		=> $this->pf.'pm',
						'order'		=> 'pmID ASC',
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
				'msg_id'			=> $row['pmID'],
				'msg_topic_id'      => $row['pmID'],
				'msg_date'          => $row['time'],
				'msg_post'          => $this->fixPostData($row['message']),
				'msg_post_key'      => md5(microtime()),
				'msg_author_id'     => $row['userID'],
				'msg_is_first_post' => 1
				);

			//-----------------------------------------
			// Map Data
			//-----------------------------------------

			$maps = array();
			$_invited   = array();
			$recipient = $row['userID'];

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->pf.'pm_to_user', 'where' => "pmID={$row['pmID']}"));
			ipsRegistry::DB('hb')->execute();
			while ($to = ipsRegistry::DB('hb')->fetch())
			{
				$maps[] = array(
					'map_user_id'     => $to['recipientID'],
					'map_topic_id'    => $row['pmID'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => $to['isViewed'],
					'map_last_topic_reply' => $row['time'],
					'map_user_active' => ($to['isDeleted']) ? 0 : 1,
					'map_user_banned' => 0,
					'map_has_unread'  => (bool) $to['isViewed'],
					'map_is_system'   => 0,
					'map_is_starter'  => 0
					);

				$_invited[ $to['recipientID'] ] = $to['recipientID'];

				if(!$to['isBlindCopy'])
				{
					$recipient = $to['recipientID'];
				}
			}

			// Add the starter
			if(!in_array($row['userID'], $_invited))
			{
				$maps[] = array(
					'map_user_id'     => $row['userID'],
					'map_topic_id'    => $row['pmID'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => $to['time'],
					'map_last_topic_reply' => $row['time'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => 0,
					'map_is_system'   => 0,
					'map_is_starter'  => 1
					);
			}

			//-----------------------------------------
			// Topic Data
			//-----------------------------------------

			$topic = array(
				'mt_id'			     => $row['pmID'],
				'mt_date'		     => $row['time'],
				'mt_title'		     => $row['subject'],
				'mt_starter_id'	     => $row['userID'],
				'mt_start_time'      => $row['time'],
				'mt_last_post_time'  => $row['time'],
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
						'from' 		=> $this->pf.'attachment',
						'order'		=> 'attachmentID ASC',
					);

		$loop = $this->lib->load('attachments', $main);

		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------

		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_woltlab/wcf/attachments):')), 'path');

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
			// Set type
			switch($row['messageType'])
			{
				case 'post':
					$type = 'post';
					break;

				case 'pm';
					$type = 'msg';
					break;

				default:
					continue 2;
			}

			// Set extension
			$bits = explode('.', $row['attachmentName']);
			$ext = array_pop($bits);

			// Set rest of the info
			$save = array(
				'attach_ext'		=> $ext,
				'attach_file'		=> $row['attachmentName'],
				'attach_location'	=> 'attachment-'.$row['attachmentID'],
				'attach_is_image'	=> $row['isImage'],
				'attach_hits'		=> $row['downloads'],
				'attach_date'		=> $row['uploadTime'],
				'attach_member_id'	=> $row['userID'],
				'attach_filesize'	=> $row['attachmentSize'],
				'attach_rel_id'		=> $row['messageID'],
				'attach_rel_module'	=> $type
				);

			// Save
			$done = $this->lib->convertAttachment($row['attachmentID'], $save, $path);

			// Fix inline attachments
			if ($done === true and $row['embedded'])
			{
				$aid = $this->lib->getLink($row['attachmentID'], 'attachments');

				switch ($save['attach_rel_module'])
				{
					case 'post':
						$field = 'post';
						$table = 'posts';
						$pid = $this->lib->getLink($row['messageID'], 'posts');
						$where = "pid={$pid}";
						break;

					case 'msg':
						$field = 'msg_id';
						$table = 'message_posts';
						$pid = $this->lib->getLink($row['messageID'], 'pm_posts');
						$where = "msg_id={$pid}";
						break;

					default:
						continue 2;
						break;
				}

				if ( $pid )
				{
					$attachrow = $this->DB->buildAndFetch( array( 'select' => $field, 'from' => $table, 'where' => $where ) );
					$save = preg_replace("#\[attach\](\d+)\[/attach\]#", "[attachment={$aid}:{$save['attach_location']}]", $attachrow[$field]);
					$this->DB->update($table, array($field => $save), $where);
				}
			}

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
						'from' 		=> $this->pf.'smiley',
						'order'		=> 'smileyID ASC',
					);

		$loop = $this->lib->load('emoticons', $main);

		//-----------------------------------------
		// We need to know the path and how to handle duplicates
		//-----------------------------------------

		$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder your wcf folder (no trailing slash - usually path_to_woltlab/wcf):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

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
				'typed'		=> $row['smileyCode'],
				'image'		=> $row['smileyPath'],
				'clickable'	=> 1,
				'emo_set'	=> 'default',
				);
			$done = $this->lib->convertEmoticon($row['smileyID'], $save, $us['emo_opt'], $path);
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
						'from'		=> $this->pb.'board_moderator'
					);

		$loop = $this->lib->load('moderators', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			if($row['userID'])
			{
				$type = 'u';
				$id = $row['userID'];
			}
			else
			{
				$type = 'g';
				$id = $row['groupID'];
			}

			$save = array(
							   'forum_id'	  => $row['boardID'],
							   'edit_post'	  => $row['canEditPost'],
							   'edit_topic'	  => $row['canEditPost'],
							   'delete_post'  => $row['canDeletePost'],
							   'delete_topic' => $row['canDeleteThread'],
							   'open_topic'	  => $row['canCloseThread'],
							   'close_topic'  => $row['canCloseThread'],
							   'mass_move'	  => $row['canMoveThread'],
							   'mass_prune'	  => $row['canDeleteThread'],
							   'move_topic'	  => $row['canMoveThread'],
							   'pin_topic'	  => $row['canPinThread'],
							   'unpin_topic'  => $row['canPinThread'],
						 );

			if($type == 'u')
			{
				$member = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'username', 'from' => $this->pf.'user', 'where' => 'userID='.$row['userID']));
				$save['member_name']	= $member['username'];
				$save['member_id']		= $row['userID'];
			}
			else
			{
				$group = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'groupName', 'from' => $this->pf.'group', 'where' => 'groupID='.$row['groupID']));
				$save['is_group']		= '1';
				$save['group_id']		= $row['groupID'];
				$save['group_name']		= $group['groupName'];
			}

			$this->lib->convertModerator($row['boardID'].'-'.$type.$id, $save);
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
						'from' 		=> $this->pf.'user_whitelist',
						'order'		=> 'userID ASC',
					);

		$loop = $this->lib->load('profile_friends', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'friends_member_id'	=> $row['userID'],
				'friends_friend_id'	=> $row['whiteUserID'],
				'friends_approved'	=> '1',
				);
			$this->lib->convertFriend($row['userID'].'-'.$row['whiteUserID'], $save);
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
						'from' 		=> $this->pf.'user_blacklist',
						'order'		=> 'userID ASC',
					);

		$loop = $this->lib->load('ignored_users', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'ignore_owner_id'	=> $row['userID'],
				'ignore_ignore_id'	=> $row['blackUserID'],
				'ignore_messages'	=> '1',
				'ignore_topics'		=> '1',
				);
			$this->lib->convertIgnore($row['userID'].'-'.$row['blackUserID'], $save);
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
						'from' 		=> $this->pb.'thread_rating',
						'order'		=> 'threadID ASC',
					);

		$loop = $this->lib->load('topic_ratings', $main);
		//SELECT t.threadID, p.postID, p.pollID FROM `wbb1_1_post` p, `wbb1_1_thread` t WHERE p.`pollID` >0 AND p.threadID = t.threadID
		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'rating_tid'		=> $row['threadID'],
				'rating_member_id'	=> $row['userID'],
				'rating_value'		=> $row['rating'],
				'rating_ip_address'	=> $row['ipAddress'],
				);
			$this->lib->convertTopicRating($row['threadID'].'-'.$row['userID'], $save);
		}

		$this->lib->next();

	}


}
