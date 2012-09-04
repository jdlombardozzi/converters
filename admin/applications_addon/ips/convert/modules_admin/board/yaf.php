<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * Woltlab Burning Board
 * Last Update: $Date: 2011-07-12 21:15:48 +0100 (Tue, 12 Jul 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 550 $
 */

$info = array( 'key'	=> 'yaf',
			   'name'	=> 'Yet Another Forum.NET 1.9',
			   'login'	=> false );

class admin_convert_board_yaf extends ipsCommand
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
		//-----------------------------------------
		// What can this thing do?
		//-----------------------------------------

		// array('action' => array('action that must be completed first'))
		$this->actions = array(
			'emoticons'		=> array(),
			//'pfields'		=> array(),
			'forum_perms'	=> array(),
			'groups' 		=> array('forum_perms'),
			'members'		=> array('groups'),
			//'profile_friends' => array('members'),
			//'ignored_users'	=> array('members'),
			'forums'		=> array('forum_perms', 'members'),
			//'moderators'	=> array('groups', 'members', 'forums'),
			'topics'		=> array('members', 'forums'),
			//'topic_ratings' => array('topics', 'members'),
			'posts'			=> array('members', 'topics', 'emoticons'),
			'polls'			=> array('members', 'topics', 'posts'),
			'pms'			=> array('members'),
			'attachments'	=> array('posts', 'pms'),
			'ranks' => array()
			);

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------

		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'Yet Another Forum &rarr; IP.Board Converter' );

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
			case 'attachments':
				return  $this->lib->countRows('Attachment');
				break;

			case 'emoticons':
				return  $this->lib->countRows('Smiley');
				break;

			case 'forums':
				return  $this->lib->countRows('Forum') + $this->lib->countRows('Category');
				break;

			case 'forum_perms':
			case 'groups':
				return  $this->lib->countRows('Group');
				break;

			case 'members':
				return  $this->lib->countRows('User');
				break;

			case 'pms':
				return  $this->lib->countRows('PMessage');
				break;

			case 'polls':
				return  $this->lib->countRows('Poll');
				break;

			case 'posts':
				return  $this->lib->countRows('Message');
				break;

			case 'ranks':
				return  $this->lib->countRows('Rank', 'MinPosts >= 0');
				break;

			case 'topics':
				return  $this->lib->countRows('Topic');
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
						'from' 		=> 'Group',
						'order'		=> 'GroupID ASC' );

		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'GroupID', 'nf' => 'Name'));

		//---------------------------
		// Loop
		//---------------------------
		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['GroupID'], $row['Name']);
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
		$main = array( 'select' => '*',
					   'from' 	=> 'Group',
					   'order'	=> 'GroupID ASC' );

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'GroupID', 'nf' => 'Name'));

		//---------------------------
		// Loop
		//---------------------------
		foreach( $loop as $row )
		{
			$save = array( 'g_title'   => $row['Name'],
						   'g_perm_id' => $row['GroupID'] );
			$this->lib->convertGroup($row['GroupID'], $save);
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
		$pcpf = array( //'gender'			=> 'Gender',
						'Location'			=> 'Location',
						'Occupation'		=> 'Occupation',
						'Interests'			=> 'Interests',
						'RealName'		=> 'Real Name',
						'HomePage'			=> 'Website URL',
						'ICQ'				=> 'ICQ',
						'AIM'				=> 'AIM',
						'YIM'				=> 'YIM',
						'MSN'				=> 'MSN',
						//'skype'				=> 'Skype',
						);

		$this->lib->saveMoreInfo('members', array_keys($pcpf));

		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> 'u.*',
						'from' 		=> array('User' => 'u'),
						'order'		=> 'u.UserID ASC' );

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

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
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'UserGroup', 'where' => "UserID='{$row['UserID']}'"));
			ipsRegistry::DB('hb')->execute();
			$sgroups = array();
			while ($group = ipsRegistry::DB('hb')->fetch())
			{
				$sgroups[] = $group['GroupID'];
			}
			$group = array_shift($sgroups);

			// Basic info
			$info = array(
				'id'				=> $row['UserID'],
				'username'			=> $row['Name'],
				'email'				=> $row['Email'],
				'md5pass'			=> strtolower($row['Password']),
				'joined'			=> $this->_fixTime($row['Joined']),
				'secondary_groups'	=> implode(',', $sgroups),
				'group'				=> $group );

			// Member info
			$members = array( 'ip_address'		=> $row['IP'],
							  'last_activity'		=> $this->_fixTime($row['LastVisit']),
							  'last_visit'		=> $this->_fixTime($row['LastVisit']),
							  'posts'				=> $row['NumPosts'],
							  'view_sigs'			=> 1,
							  'hide_email'		=> 1,
							  /*'email_pm'			=> $row['PMNotification']*/ );

			// Profile
			$profile = array( 'signature'			=> $this->fixPostData($row['Signature']),
							  'photo_type' => $row['Avatar'] ? 'url' : '',
							  'photo_location' => $row['Avatar'] ? $row['Avatar'] : '' );

			//-----------------------------------------
			// Avatars
			//-----------------------------------------
			if ($row['Avatar'])
			{
				$profile['photo_type'] = 'url';
				$profile['photo_location'] = $row['Avatar'];
			} elseif ( $row['AvatarImage'] )
			{
				$profile['photo_type'] = 'custom';
				$profile['photo_location'] = 'av-conv-' . $row['UserID'] . '.' . str_ireplace('image/', '', $row['AvatarImageType']);
				$profile['photo_data'] = $row['AvatarImage'];
				$profile['photo_filesize'] = strlen($row['AvatarImage']);
				//$profile['avatar_size'] = $customavatar['width'].'x'.$customavatar['height'];
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
			$this->lib->convertMember($info, $members, $profile, array());
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
						'from' 		=> 'Category',
						'order'		=> 'CategoryID ASC' );

		$loop = $this->lib->load('forums', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'parent_id'		=> -1,
				'name'			=> $row['Name'],
				'position'	   => $row['SortOrder'],
				'sub_can_post'	=> 0 );

			$this->lib->convertForum('C_'.$row['CategoryID'], $save, array());
		}

		ipsRegistry::DB('hb')->build( array( 'select' => '*',
											 'from'   => 'Forum',
											 'order'  => 'ForumID ASC' ) );
		$forumRes = ipsRegistry::DB('hb')->execute();

		while( $row = ipsRegistry::DB('hb')->fetch($forumRes) )
		{
			$save = array( 'parent_id'		=> intval($row['ParentID']) > 0 ? $row['ParentID'] : 'C_'.$row['CategoryID'],
							'name'			=> $row['Name'],
							'description'	=> $row['Description'],
							'position'	   => $row['SortOrder'],
							'sub_can_post'	=> 1,
							'topics'		=> $row['NumTopics'],
							'posts'			=> $row['NumPosts'],
							'use_html' => 0,
							'sort_key' => 'last_post',
							'sort_order' => 'A-Z',
							);
			$this->lib->convertForum($row['ForumID'], $save, array());

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'WatchForum', 'where' => "ForumID={$row['ForumID']}"));
			$trackRes = ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch($trackRes))
			{
				$savetracker = array( 'member_id'	=> $tracker['UserID'],
									  'forum_id'	=> $tracker['ForumID'],
									  'forum_track_type' => 'delayed' );
				$this->lib->convertForumSubscription($tracker['WatchForumID'], $savetracker);
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
		$main = array(	'select' 	=> 't.*',
						'from' 		=> array( 'Topic' => 't' ),
						'add_join' => array( array( 'select' => 'u.Name',
												    'from' => array( 'User' => 'u' ),
												    'where' => 't.UserID = u.UserID',
												    'type' => 'left' ) ),
						'order'		=> 't.TopicID ASC' );

		$loop = $this->lib->load('topics', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'forum_id'			=> $row['ForumID'],
				'title'				=> $row['Topic'],
				//'description'       => $row['topic_description'],
				'start_date'		=> $this->_fixTime($row['Posted']),
				'starter_id'		=> $row['UserID'],
				'starter_name'		=> $row['Name'],
				'posts'				=> intval($row['NumPosts']),
				'views'				=> $row['Views'],
				//'topic_hasattach'	=> $row['attachments'],
				'pinned'			=> $row['Priority'] > 0 ? 1 : 0,
				'state'				=> ($row['Active'] == 0) ? 'closed' : 'open',
				'approved'			=> $row['IsDeleted'] == 0 ? 1 : 0,
				'last_post'			=> $this->_fixTime($row['Posted']) );

			$this->lib->convertTopic($row['TopicID'], $save);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'WatchTopic', 'where' => "TopicID={$row['TopicID']}"));
			$subRes = ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch($subRes))
			{
				$savetracker = array(
					'member_id'	=> $tracker['UserID'],
					'topic_id'	=> $tracker['TopicID'],
					'topic_track_type' => 'delayed' );
				$this->lib->convertTopicSubscription($tracker['WatchTopicID'], $savetracker);
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
		$main = array( 'select' 	=> 'p.*',
					   'from' 	 	=> array( 'Message' => 'p' ),
						'add_join' => array( array( 'select' => 'u.Name',
												    'from' => array( 'User' => 'u' ),
												    'where' => 'p.UserID = u.UserID',
												    'type' => 'left' ) ),
					   	'order' => 'p.MessageID ASC' );

		$loop = $this->lib->load('posts', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'topic_id'			=> $row['TopicID'],
				'author_id'			=> $row['UserID'],
				'author_name'		=> substr( $row['Name'], 0, 32),
				'post'				=> $this->fixPostData($row['Message']),
				'post_date'			=> $this->_fixTime($row['Posted']),
				'use_emo'			=> 1,
				'post_htmlstate'	=> 0,
				'use_sig'			=> 1,
				'edit_time'		=> $row['Edited'] ? $this->_fixTime($row['Edited']) : '',
				'queued'      	=> $row['isDeleted'] == 1 ? 2 : ( $row['IsApproved'] == 1 ? 0 : 1),
				'ip_address'		=> $row['IP'] );
			ipsRegistry::DB()->allow_sub_select=1;
			$this->lib->convertPost($row['MessageID'], $save);
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
						'from' 		=> 'Rank',
						'where' => 'MinPosts >= 0',
						'order'		=> 'RankID ASC' );

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
				'posts'	=> $row['MinPosts'],
				'title'	=> $row['Name'],
				);
			$this->lib->convertRank($row['RankID'], $save, $us['rank_opt']);
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
		$main = array( 'select' => 'p.Question',
						'from'  => array( 'Poll' => 'p' ),
						'add_join' => array( array( 'select' => 't.*',
													'from' => array( 'Topic' => 't' ),
													'where' => 'p.PollID = t.PollID',
													'type' => 'inner' ) ),
						'order' => 'p.PollID ASC' );

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

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'Choice', 'where' => "PollID={$row['PollID']}"));
			$optionRes = ipsRegistry::DB('hb')->execute();
			while ($options = ipsRegistry::DB('hb')->fetch($optionRes))
			{
				$choices[ $options['ChoiceID'] ]	= $options['Choice'];
				$votes[ $options['ChoiceID'] ]	= $options['Votes'];
				$total_votes[] = $options['Votes'];
			}

			//-----------------------------------------
			// Convert votes
			//-----------------------------------------
			$voters = array();

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'PollVote', 'where' => "PollID={$row['PollID']}"));
			$voterRes = ipsRegistry::DB('hb')->execute();
			while ($voter = ipsRegistry::DB('hb')->fetch($voterRes))
			{
				// Do we already have this user's votes
				if ($voter['UserID'] == 0 || in_array($voter['UserID'], $voters))
				{
					continue;
				}

				// And save
				$vsave = array( 'vote_date'		=> time(),
								'tid'			=> $row['TopicID'],
								'member_id'		=> $voter['UserID'],
								'forum_id'		=> $row['ForumID'],
								'member_choices'=> serialize(array()) );

				$this->lib->convertPollVoter($voter['PollVoteID'], $vsave);
				$voters[] = $voter['UserID'];
			}

			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------
			$poll_array = array( // only allows one question per poll
								 1 => array( 'question'	=> str_replace( "'" , '&#39;', $row['Question'] ),
									 		 'multi'	=> 0,
									 		 'choice'	=> $choices,
									 		 'votes'	=> $votes ) );

			$save = array( 'tid'			  => $row['TopicID'],
						   'start_date'		  => $this->_fixTime($row['Posted']),
						   'choices'   		  => addslashes(serialize($poll_array)),
						   'starter_id'		  => $row['UserID'],
						   'votes'     		  => array_sum($total_votes),
						   'forum_id'  		  => $row['ForumID'],
						   'poll_question'	  => str_replace( "'" , '&#39;', $row['Question'] ),
						   'poll_view_voters' => 0 );

 			if ( $this->lib->convertPoll($row['PollID'], $save) === TRUE )
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
		$main = array( 'select' => 'pm.*',
					   'from' => array( 'PMessage' => 'pm' ),
					   'add_join' => array( array( 'select'	=> 'pmt.*',
					   							   'from' 		=> array( 'UserPMessage' => 'pmt'),
					   							   'where'		=> 'pm.PMessageID=pmt.PMessageID',
					   							   'type'		=> 'inner') ),
						'order'		=> 'pm.PMessageID ASC' );

		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Post Data
			//-----------------------------------------
			$post = array( 'msg_id'			=> $row['PMessageID'],
						   'msg_topic_id'      => $row['PMessageID'],
						   'msg_date'          => $this->_fixTime($row['Created']),
						   'msg_post'          => $this->fixPostData($row['Body']),
						   'msg_post_key'      => md5(microtime()),
						   'msg_author_id'     => $row['FromUserID'],
						   'msg_is_first_post' => 1 );

			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$maps = array( array( 'map_user_id'     => $row['UserID'], // Recepient
								  'map_topic_id'    => $row['PMessageID'],
								  'map_folder_id'   => 'myconvo',
								  'map_read_time'   => $row['IsRead'] == 0 ? 0 : time(),
								  'map_last_topic_reply' => $row['Created'],
								  'map_user_active' => 1,
								  'map_user_banned' => 0,
								  'map_has_unread'  => $row['IsRead'] == 0 ? 1 : 0,
								  'map_is_system'   => 0,
								  'map_is_starter'  => 0 ),
						   array( 'map_user_id'     => $row['FromUserID'], // Starter
						   		  'map_topic_id'    => $row['PMessageID'],
						   		  'map_folder_id'   => 'myconvo',
						   		  'map_read_time'   => time(),
						   		  'map_last_topic_reply' => $row['Created'],
						   		  'map_user_active' => 1,
						   		  'map_user_banned' => 0,
						   		  'map_has_unread'  => 0,
						   		  'map_is_system'   => 0,
						   		  'map_is_starter'  => 1 ) );

			$topic = array(
				'mt_id'			     => $row['PMessageID'],
				'mt_date'		     => $this->_fixTime($row['Created']),
				'mt_title'		     => $row['Subject'],
				'mt_starter_id'	     => $row['FromUserID'],
				'mt_start_time'      => $this->_fixTime($row['Created']),
				'mt_last_post_time'  => $this->_fixTime($row['Created']),
				'mt_invited_members' => serialize( array( $row['UserID'] => $row['UserID'] ) ),
				'mt_to_count'		 => 1,
				'mt_to_member_id'	 => $row['UserID'],
				'mt_replies'		 => 0,
				'mt_is_draft'		 => $row['IsInOutbox'],
				'mt_is_deleted'		 => 0,
				'mt_is_system'		 => 0 );
//print "<PRE>";print_r($post);print_r($maps);print_r($topic);exit;
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
		$main = array( 'select'	  => 'a.*',
					   'from'	  => array( 'Attachment' => 'a' ),
					   'add_join' => array( array( 'select' => 'p.UserID',
					   							   'from'	=> array( 'Message' => 'p' ),
					   							   'where'	=> 'a.MessageID = p.MessageID',
					   							   'type'	=> 'left' ),
					   						/*array( 'select' => 'p.TopicID, p.ForumID, p.UserID',
					   							   'from'	=> array( 'Topic' => 't' ),
					   							   'where'	=> 'p.TopicID = t.TopicID',
					   							   'type'	=> 'left' )*/ ),
					   'order'	  => 'a.AttachmentID ASC' );

		$loop = $this->lib->load('attachments', $main);

		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------
		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_Yaf/uploads):')), 'path');

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
		//if (!is_readable($path))
		//{
		//	$this->lib->error('Your remote upload path is not readable.');
		//}

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$useDb = TRUE;
			// Set rest of the info
			$save = array(
				//'attach_ext'		=> $ext,
				'data'		=> $row['FileData'],
				'attach_file'  => $row['FileName'],
				'attach_date'		=> $this->_fixTime($row['Posted']),
				'attach_hits'		=> $row['Downloads'],
				'attach_rel_id'		=> $row['MessageID'],
				'attach_rel_module'	=> 'post',
				'attach_member_id'	=> $row['UserID'],
				'attach_filesize'	=> strlen($row['FileData']) );

			if ( !$row['FileData'] )
			{
				unset($save['data']);
				$save['attach_location'] = $save['attach_file'];
				$useDb = FALSE;
			}

			// Save
			$done = $this->lib->convertAttachment($row['AttachmentID'], $save, $path, $useDb);
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
						'from' 		=> 'Smiley',
						'order'		=> 'SmileyID ASC' );

		$loop = $this->lib->load('emoticons', $main);

		//-----------------------------------------
		// We need to know the path and how to handle duplicates
		//-----------------------------------------

		$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_yaf/images/emoticons):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

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
				'typed'		=> $row['Code'],
				'image'		=> $row['Icon'],
				'clickable'	=> 1,
				'emo_set'	=> 'default',
				);
			$done = $this->lib->convertEmoticon($row['SmileyID'], $save, $us['emo_opt'], $path);
		}

		$this->lib->next();

	}

	private function _fixTime( $value )
	{
		return is_object($value) ? strtotime(DATE_FORMAT($value, DATE_ATOM)) : intval(strtotime($value));
	}
}