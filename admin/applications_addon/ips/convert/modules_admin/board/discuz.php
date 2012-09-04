<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * MyBB
 * Last Update: $Date: 2011-07-12 21:15:48 +0100 (Tue, 12 Jul 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 550 $
 */

$info = array( 'key'   => 'discuz',
			   'name'  => 'Discuz 8.X',
			   'login' => FALSE );
		
class admin_convert_board_discuz extends ipsCommand
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
		$this->actions = array( 'forum_perms'	=> array(),
								'groups' 		=> array('forum_perms'),
								'members'		=> array('forum_perms', 'groups'),
								'forums'		=> array('members'),
								'topics'		=> array('members'),
								'posts'			=> array('members', 'topics'),
								//'pms'			=> array('members'),
								/*'ranks'			=> array(),*/
								/*'polls'         => array('topics'),*/
								'attachments'   => array('posts') );
				
		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'Discuz &rarr; IP.Board Converter' );

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
			case 'forum_perms':
			case 'groups':
				return $this->lib->countRows('usergroups');
				break;
				
			case 'members':
				return  $this->lib->countRows('members');
				break;
				
			case 'forums':
				return $this->lib->countRows('forums');
				break;
				
			case 'topics':
				return  $this->lib->countRows('threads');
				break;
				
			case 'posts':
				return  $this->lib->countRows('posts');
				break;
				
			case 'pms':
				return  0;//$this->lib->countRows('PMMessage');
				break;
				
			case 'polls':
				return $this->lib->countRows('polls');
				break;
				
			case 'attachments':
				return $this->lib->countRows('attachments');
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
			case 'emoticons':
			case 'ranks':
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
		
		// And odd align tags
		$post = preg_replace("#\[align=(.+)\](.+)\[/align\]#si", "[$1]$2[/$1]", $post);
		
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
		$main = array( 'select' => '*',
					   'from'   => 'usergroups',
					   'order'  => 'groupid ASC' );
					
		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'groupid', 'nf' => 'grouptitle'));

		//---------------------------
		// Loop
		//---------------------------
		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['groupid'], $row['grouptitle']);			
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
					   'from'   => 'usergroups',
					   'order'  => 'groupid ASC' );
					
		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'groupid', 'nf' => 'grouptitle'));

		//---------------------------
		// Loop
		//---------------------------
		
		foreach( $loop as $row )
		{
			$prefix = '';
			$suffix = '';
			if ($row['color'])
			{
				$prefix = "<span style='color:" . strtolower($row['color']) . "'>";
				$suffix = '</span>';
			}
							
			$save = array(
				'g_title'			=> $row['grouptitle'],
				'g_perm_id'			=> $row['groupid'],
				'prefix'			=> $prefix,
				'suffix'			=> $suffix );
			$this->lib->convertGroup($row['groupid'], $save);			
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
		$pcpf = array( //'gender'		=> 'Gender',
					   'icq'	  => 'ICQ Number',
					   'aim'	  => 'AIM ID',
					   'yahoo'	  => 'Yahoo ID',
					   'msn'	  => 'MSN ID',
					   'site'  	  => 'Website',
					   'location' => 'Location' );
		
		$this->lib->saveMoreInfo('members', array_keys($pcpf));

		//---------------------------
		// Set up
		//---------------------------

		$main = array( 'select'   => 'm.*',
					   'from' 	  => array( 'members' => 'm' ),
					   'add_join' => array( array( 'select' => 'g.*',
					   							   'from'   => array( 'memberfields' => 'g' ),
					   							   'where'  => 'm.uid = g.uid',
					   							   'type'   => 'left' ) ),
					   'order'	  => 'm.uid ASC' );

		$loop = $this->lib->load('members', $main);
		
		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();
		
		// We need to know the avatars path
		$ask['dirpath'] = array('type' => 'text', 'label' => 'The path to the folder/directory containing your Discuz installation.');
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
			$info = array( 'id'				  => $row['uid'],
						   'group'			  => $row['groupid'],
						   'secondary_groups' => $row['extgroupids'],
						   'joined'	   		  => $row['regdate'],
						   'username'  		  => $row['username'],
						   'email'	   		  => $row['email'],
						   'md5pass' 		  => $row['password'] );
//md5(md5($pass).$salt)
			// Member info
			$birthday = ($row['bday']) ? explode('-', $row['bday']) : null;
			
			$members = array( 'ip_address'			  => preg_match('/([0-9]{1,3}\.){3}[0-9]{1,3}/', $row['regip'] ) ? $row['regip'] : '127.0.0.1',
							  'posts'				  => $row['posts'],
							  'allow_admin_mails' 	  => 0,
							  'time_offset'			  => 0,
							  'hide_email'			  => !$row['showemail'],
							  'email_pm'			  => $row['prompt'],
							  'last_post'			  => $row['lastpost'],
							  'view_sigs'			  => 1,
							  'view_avs'			  => 1,
							  'msg_show_notification' => $row['prompt'],
							  'last_visit'			  => $row['lastvisit'],
							  'bday_day'			=> ($row['bday']) ? $birthday[2] : '',
							  'bday_month'		=> ($row['bday']) ? $birthday[1] : '',
							  'bday_year'			=> ($row['bday']) ? $birthday[0] : '',
							  'last_activity'		  => $row['lastvisit'],
							  'dst_in_use'			  => 0,
							  'coppa_user'			  => 0,
							  'members_disable_pm'	  => 1 );

			// Profile
			$profile = array( 'signature' => $this->fixPostData($row['sightml']) );

			//-----------------------------------------
			// Avatars and profile pictures
			//-----------------------------------------
			$path;
			if ( $row['avatar'] != '' && $row['avatar'] != NULL )
			{
				// URL
				if (preg_match('/http/', $row['avatar']))
				{
					$profile['photo_type'] = 'url';
					$profile['photo_location'] = $row['avatar'];
				}
				// Gallery
				else
				{
					$profile['photo_type'] = 'custom';
					$profile['photo_location'] = $row['avatar'];
					$path = $us['dirpath'];
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
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();
					
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => '*',
						'from'  => 'forums',
						'order' => 'fid ASC' );
								
		$loop = $this->lib->load('forums', $main, array('forum_tracker'), array(), TRUE );
					
					
		$this->lib->getMoreInfo('forums', $loop);
					
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
			$save = array( 'parent_id'		=> ($row['fup'] > 0) ? $row['fup'] : -1,
						   'position'		=> $row['displayorder'],
						   'name'			=> $row['name'],
						   'sub_can_post'	=> 1,
						   'redirect_on'	=> 0,
						   'redirect_hits' => 0,
						   'status'		=> $row['status'],
						   'posts'			=> $row['posts'],
						   'topics'		=> $row['threads'],
						   'use_ibc'      => $row['allowbbcode'] );
			
			$this->lib->convertForum($row['fid'], $save, $perms);	
			
			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'favoriteforums', 'where' => "fid={$row['fid']}"));
			$trackerRes = ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch($trackerRes))
			{
				$savetracker = array( 'member_id'	=> $tracker['uid'],
									  'forum_id'	=> $tracker['fid'] );					
				$this->lib->convertForumSubscription($tracker['fid'].'-'.$tracker['uid'], $savetracker);	
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
		$main = array( 'select'		=> '*',
					   'from'		=> 'threads',
					   'order'		=> 'tid ASC' );

		$loop = $this->lib->load('topics', $main, array('tracker'));

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'forum_id'			=> $row['fid'],
						   'title'				=> $row['subject'],
						   'poll_state'			=> 0,
						   'starter_id'			=> intval($row['authorid']),
						   'starter_name'		=> $row['author'],
						   'start_date'			=> $row['dateline'],
						   'last_post'			=> $row['lastpost'],
						   'views'				=> $row['views'],
						   'posts'				=> $row['replies'],
						   'state'		   	 	=> $row['closed'] == 1 ? 'closed' : 'open',
						   'pinned'				=> ($row['displayorder'] > 0) ? 1 : 0,
						   'approved'			=> 1 );
			$this->lib->convertTopic($row['tid'], $save);
			
			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'favoritethreads', 'where' => "tid={$row['tid']}"));
			$trackerRes = ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch($trackerRes))
			{
				$savetracker = array( 'member_id'	=> $tracker['uid'],
									  'topic_id'	=> $tracker['tid'] );					
				$this->lib->convertTopicSubscription($tracker['tid'].'-'.$tracker['uid'], $savetracker);	
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
		$main = array( 'select'	 => '*',
					  'from'	 => 'posts',
					  'order'	 => 'pid ASC' );

		$loop = $this->lib->load('posts', $main);
		
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'topic_id'    => intval($row['tid']),
						   'author_id'	 => intval($row['authorid']),
						   'author_name' => $row['author'],
						   'post_date'	 => $row['dateline'],
						   'post'		 => $this->fixPostData($row['message']),
						   'ip_address'	 => $row['useip'],
						   'use_sig'	 => $row['usesig'],
						   'use_emo'	 => $row['bbcodeoff'] == 1 ? 0 : 1,
						   'queued'		 => $row['invisible'] == 1 ? 1 : 0 );

			$this->lib->convertPost($row['tid'], $save);
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
					   'from' 	=> 'PMMessage',
					   'order'	=> 'PM_ID ASC' );
		
		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));
					
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$row['PM_Message_date'] = strtotime($this->fixFrenchDate($row['PM_Message_date']));
			
			//-----------------------------------------
			// Post Data
			//-----------------------------------------
			$post = array( 'msg_id'			   => $row['PM_ID'],
						   'msg_topic_id'      => $row['PM_ID'],
						   'msg_date'          => $row['PM_Message_date'],
						   'msg_post'          => $this->fixPostData($row['PM_Message']),
						   'msg_post_key'      => md5(microtime()),
						   'msg_author_id'     => intval($row['From_ID']),
						   'msg_is_first_post' => 1 );	
				
			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$map_master = array( 'map_topic_id'    => $row['u2uid'],
								 'map_folder_id'   => 'myconvo',
								 'map_last_topic_reply' => $row['PM_Message_date'],
								 'map_read_time'   => 0,
								 'map_user_active' => 1,
								 'map_user_banned' => 0,
								 'map_has_unread'  => $row['Read_Post'] == '-1' ? 1 : 0,
								 'map_is_system'   => 0 );
				
			$maps = array();
			$maps[] = array_merge( $map_master, array( 'map_user_id' => intval($row['From_ID']), 'map_is_starter' => 1 ) );
			$maps[] = array_merge( $map_master, array( 'map_user_id' => intval($row['Author_ID']), 'map_is_starter' => 0 ) );
	
			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$topic = array( 'mt_id'			     => $row['PM_ID'],
							'mt_date'		     => $row['PM_Message_date'],
							'mt_title'		     => $row['PM_Tittle'],
							'mt_starter_id'	     => intval($row['From_ID']),
							'mt_start_time'      => $row['PM_Message_date'],
							'mt_last_post_time'  => $row['PM_Message_date'],
							'mt_invited_members' => serialize( array( intval($row['Author_ID']) => intval($row['Author_ID']) ) ),
							'mt_to_count'		 => 1,
							'mt_to_member_id'	 => intval($row['Author_ID']),
							'mt_replies'		 => 0,
							'mt_is_draft'		 => 0,
							'mt_is_deleted'		 => 0,
							'mt_is_system'		 => 0 );
			
			//-----------------------------------------
			// Go
			//-----------------------------------------
			$this->lib->convertPM($topic, array($post), $maps);
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
		$main = array(	'select' => '*',
						'from' 	 => 'Group',
						'where'  => 'Special_rank = 0',
						'order'	 => 'Group_ID ASC' );
					
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
			$save = array( 'posts'	=> $row['Minimum_posts'],
						   'title'	=> $row['Name'] );
			$this->lib->convertRank($row['Group_ID'], $save, $us['rank_opt']);			
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
		$main = array( 'select' => 'p.*',
					   'from' 	=> array( 'polls' => 'p'),
					   'add_join' => array( array( 'select' => 't.*',
					   							   'from'   => array( 'threads' => 't'),
					   							   'where'  => 'p.tid = t.tid',
					   							   'type'   => 'left' ) ),
					   'order'	=> 'p.tid ASC' );
		
		$loop = $this->lib->load('polls', $main, array('voters'));
					
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Convert votes
			//-----------------------------------------
			$votes = array();
			
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'PollVote', 'where' => "Poll_ID='" . intval($row['Poll_ID']) . "'"));
			$voterRes = ipsRegistry::DB('hb')->execute();
			while ( $voter = ipsRegistry::DB('hb')->fetch($voterRes) )
			{
				// Do we already have this user's votes
				if (in_array($voter['Author_ID'], $votes))
				{
					continue;
				}
				
				$vsave = array( 'vote_date'		 => time(),
								'tid'			 => $row['Topic_ID'],
								'member_id'		 => $voter['Author_ID'],
								'forum_id'		 => $row['Forum_ID'],
								'member_choices' => serialize(array()) );
			
				$this->lib->convertPollVoter($voter['Poll_ID'] . '-' . $voter['Author_ID'], $vsave);
			}
			
			//-----------------------------------------
			// Options are stored in one place...
			//-----------------------------------------
			$choices = array();
			$votes = array();
			$totalVotes = 0;
			
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'PollChoice', 'where' => "Poll_ID='" . intval($row['Poll_ID']) . "'"));
			$choiceRes = ipsRegistry::DB('hb')->execute();
			while ( $choice = ipsRegistry::DB('hb')->fetch($choiceRes) )
			{
				$choices[ $choice['Choice_ID'] ] = $choice['Choice'];
				$votes[ $choice['Choice_ID'] ]	= $choice['Votes'];
				$totalVotes = $choice['Votes'];
			}
			
			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------
			$poll_array = array( // WebWiz only allows one question per poll
								 1 => array( 'question'	=> $row['Poll_question'],
								 			 'choice'	=> $choices,
											 'votes'	=> $votes ) );
			$save = array( 'tid'		=> $row['Topic_ID'],
						   'start_date'	=> is_object($row['Message_date']) ? strtotime(DATE_FORMAT($row['Message_date'], DATE_ATOM)) : intval(strtotime($row['Message_date'])),
						   'choices'   	=> addslashes(serialize($poll_array)),
						   'starter_id'	=> $row['Author_ID'],
						   'votes'     	=> $totalVotes,
						   'forum_id'  	=> $row['Forum_ID'],
						   'poll_question'	=> $row['Poll_question'] );

			$this->lib->convertPoll($row['Poll_ID'], $save);
		}
		$this->lib->next();
	}
	
	private function convert_attachments()
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$this->lib->saveMoreInfo('attachments', array('attach_path'));
		
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => '*',
					   'from'   => 'attachments',
					   'order'  => 'aid ASC' );
					
		$loop = $this->lib->load('attachments', $main);
		
		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------
					
		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_board/attachments):')), 'path');
		
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
					
		//-----------------------------------------
		// Check all is well
		//-----------------------------------------
		if (!is_writable($this->settings['upload_dir']))
		{
			$this->lib->error('Your IP.Board upload path is not writeable. '.$this->settings['upload_dir']);
		}
		if (!is_readable($us['attach_path']))
		{
			$this->lib->error('Your remote upload path is not readable.');
		}

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			// What's the extension?
			$ext = explode('.', $row['filename']);
			$extension = array_pop( $ext );

			// Sort out data
			$save = array( 'attach_rel_id'	   => $row['pid'],
						   'attach_ext'		   => $extension,
						   'attach_file'	   => $row['filename'],
						   'attach_location'   => $row['attachment'],
						   'attach_is_image'   => $row['isimage'],
						   'attach_rel_module' => 'post',
						   'attach_member_id'  => $row['uid'],
						   'attach_hits'	   => $row['downloads'],
						   'attach_date'	   => $row['dateline'] );
		// Send em on
		$this->lib->convertAttachment( $row['aid'], $save, $us['attach_path'] );
		}
		$this->lib->next();
	}
}
	
