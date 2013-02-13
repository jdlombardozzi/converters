<?php
$info = array( 'key'   => 'activeforum',
			   'name'  => 'Active Forum',
			   'login' => true );

class admin_convert_board_activeforum extends ipsCommand
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
		$this->actions = array(
								'members'		=> array(),
								'forums'		=> array('members'),
								'topics'		=> array('members'),
								'posts'			=> array('members', 'topics'),
								'polls'			=> array( 'topics', 'posts' ),
								'ranks'			=> array('members', 'posts'),
								'attachments'   => array('posts') );

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'Dot Net Nuke ActiveForums &rarr; IP.Board Converter' );

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
    if (array_key_exists($this->request['do'], $this->actions) or $this->request['do'] == 'boards')
		{
			call_user_func(array($this, 'convert_'.$this->request['do']));
		}
    else
    {
				$this->lib->menu( array(
					'forums' => array(
						'single' => 'activeforums_Groups',
						'multi'  => array( 'activeforums_Groups', 'activeforums_Forums' )
					) )	);    }

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
				return  $this->lib->countRows('Users');
				break;

			case 'forums':
				return $this->lib->countRows('activeforums_Forums') + $this->lib->countRows('activeforums_Groups');
				break;

			case 'topics':
				return  $this->lib->countRows('activeforums_Topics');
				break;

			case 'posts':
				return  $this->lib->countRows('activeforums_Replies') + $this->lib->countRows('activeforums_Topics');
				break;

			case 'polls':
				return $this->lib->countRows('activeforums_Poll');
				break;

			case 'ranks':
				return $this->lib->countRows('activeforums_Ranks', "ModuleId='1130'");
				break;

			case 'attachments':
				return $this->lib->countRows('activeforums_Attachments', "PostId > 0");
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
		$post = preg_replace("#\[align=(.+)\](.+)\[/align\]#i", "[$1]$2[/$1]", $post);

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
		$pcpf = array( //'gender'		=> 'Gender',
					   'ICQ'	  => 'ICQ Number',
					   'AOL'	  => 'AIM ID',
					   'Yahoo'	  => 'Yahoo ID',
					   'MSN'	  => 'MSN ID',
					   'Location'  	  => 'Location',
					   'Occupation' => 'Occupation',
					   'Interests' => 'Interests',
					   'WebSite' => 'Website' );

		$this->lib->saveMoreInfo('members', array_keys($pcpf));

		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select'   => 'u.*',
                   'from' 	  => array('Users' => 'u'),
                   'add_join' => array( array( 'select' 	=> 'p.ReplyCount, p.UserCaption, p.DateCreated, p.Signature, p.DateLastActivity',
                                               'from'		=> array('activeforums_UserProfiles' => 'p'),
                                               'where'		=> 'u.UserId = p.UserId',
                                               'type'		=> 'left'),
                                        array( 'select' => 'm.Password, m.PasswordSalt',
                                               'from' => array( 'aspnet_Membership' => 'm'),
                                               'where' => 'u.Email = m.Email',
                                               'type' => 'left') ),
                   'order'   => 'u.UserID ASC' );

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
			$info = array( 'id'				  => $row['UserID'],
						   'joined'	   		  => $this->lib->myStrToTime($row['DateCreated']),
						   'username'  		  => $row['Username'],
                'displayname' => $row['DisplayName'],
						   'email'	   		  => $row['Email'],
						   'password' 		  => $row['Password']);

			// Member info
			$members = array( 'ip_address'			  => '127.0.0.1',
							  'posts'				  => $row['ReplyCount'],
							  'allow_admin_mails' 	  => 1,
							  'time_offset'			  => intval($row['TimeZone']),
							  'email_pm'			  => 0,
							  'view_sigs'			  => 1,
							  'msg_show_notification' => 1,
							  'last_visit'			  => $this->lib->myStrToTime($row['DateLastActivity']),
							  'last_activity'		  => $this->lib->myStrToTime($row['DateLastActivity']),
							  'dst_in_use'			  => 0,
							  'coppa_user'			  => 0,
                'misc' => $row['PasswordSalt'] );

			// Profile
			$profile = array( 'signature' => $this->fixPostData($row['Signature']) );

			//-----------------------------------------
			// Custom Profile fields
			//-----------------------------------------

			// Pseudo
			foreach ($pcpf as $id => $name)
			{
				if ($us[$id] != 'x')
				{
					$custom['field_'.$us[$id]] = $row['PropertyNames'][$id];
				}
			}

			//-----------------------------------------
			// And go!
			//-----------------------------------------
			$this->lib->convertMember($info, $members, $profile, array());
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
            'from' 		=> 'activeforums_Groups',
            'order'		=> 'ForumGroupId ASC',
          );

    $loop = $this->lib->load('forums', $main, array(), array('boards', 'activeforums_Forums') );

    //---------------------------
    // Loop
    //---------------------------

    while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
    {
      $this->lib->convertForum('c'.$row['ForumGroupId'], array('name' => $row['GroupName'], 'position' => $row['SortOrder'], 'parent_id' => -1), array());
    }

    $this->lib->next();
  }

  /**
   * Convert Forums
   *
   * @access	private
   * @return void
   **/
  private function convert_boards()
	{
			//---------------------------
			// Set up
			//---------------------------

			$mainBuild = array(	'select' => '*',
								'from'   => 'activeforums_Forums',
								'order'  => 'ForumId ASC',
								);

			$this->start = intval($this->request['st']);
			$this->end = $this->start + intval($this->request['cycle']);

			$mainBuild['limit'] = array($this->start, $this->end);

			$this->lib->errors = array();# unserialize($this->settings['conv_error']);

			ipsRegistry::DB('hb')->build($mainBuild);
			ipsRegistry::DB('hb')->execute();

			if (!ipsRegistry::DB('hb')->getTotalRows())
			{
				$action = 'forums';
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
				$get[$this->app['name']] = $us;
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

			while ( $row = ipsRegistry::DB('hb')->fetch() )
			{
				$records[] = $row;
			}

			$loop = $records;

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				// Permissions will need to be reconfigured
				$perms = array();

				//-----------------------------------------
				// And go
				//-----------------------------------------

				$save = array(
					'parent_id'		=> $row['ParentForumId'] > 0 ? $row['ParentForumId'] : 'c'.$row['ForumGroupId'],
					'position'		=> $row['SortOrder'],
					'name'			=> $row['ForumName'],
					'description'	=> $row['ForumDesc'],
					'sub_can_post'	=> $row['ParentForumId'] > 0 ? 0 : 1,
					'redirect_on'	=> 0,
					'redirect_url'	=> '',
					'redirect_hits' => 0, # Hits not saved in punBB so reset to 0..
					'posts'			=> $row['TotalReplies'],
					'topics'		=> $row['TotalTopics'],
					);

				$this->lib->convertForum($row['ForumId'], $save, $perms);
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
		$main = array( 'select'		=> 't.*',
                   'from'		=> array( 'activeforums_Topics' => 't' ),
                    'add_join' => array( array( 'select' => 'f.ForumId',
                                                'from'  => array( 'activeforums_ForumTopics' => 'f'),
                                                'where' => 't.TopicId = f.TopicId',
                                                'type' => 'left' ) ),
//                                        array( 'select' => 'p.PollID',
//                                                'from' => array( 'activeforums_Poll' => 'p' ),
//                                                'where' => 't.TopicId = p.TopicId') ),
                   'order'		=> 't.TopicId ASC' );

		$loop = $this->lib->load('topics', $main, array());

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$post = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'Subject,DateCreated,DateUpdated', 'from' => 'activeforums_Content', 'where' => "ContentId='{$row['ContentId']}'"));

			$save = array( 'forum_id'			=> $row['ForumId'],
						   'title'				=> $post['Subject'],
						   'poll_state'			=> $row['pollId'] !=  '' ? 'open' : '0',
						   'starter_id'			=> $row['AuthorId'],
						   'starter_name'		=> $row['AuthorName'],
						   'start_date'			=> $this->lib->myStrToTime($post['DateCreated']),
						   'last_post'			=> $this->lib->myStrToTime($post['DateUpdated']),
						   'views'				=> $row['ViewCount'],
						   'posts'				=> $row['ReplyCount'],
						   'state'		   	 	=> $row['IsLocked'] == 1 ? 'closed' : 'open',
						   'pinned'				=> $row['IsPinned'],
						   'approved'			=> $row['IsApproved'] );

			$this->lib->convertTopic($row['TopicId'], $save);
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
     $main = array( 'select'		=> 't.*',
                    'from'		=> array( 'activeforums_Topics' => 't' ),
                     'add_join' => array( array( 'select' => 'c.*',
                                                 'from'  => array( 'activeforums_Content' => 'c'),
                                                 'where' => 't.ContentId = c.ContentId',
                                                 'type' => 'left' ) ),
                    'order'		=> 't.TopicId ASC' );

 		$loop = $this->lib->load('posts', $main);

 		//---------------------------
 		// Loop
 		//---------------------------
 		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
 		{
       $save = array( 'topic_id'    => intval($row['TopicId']),
    						   'post_title'  	=> $row['Subject'],
    						   'author_id'	 => intval($row['AuthorId']),
    						   'author_name' => $row['AuthorName'],
    						   'post_date'	 => $this->lib->myStrToTime($row['DateCreated']),
    						   'post'		 => $this->fixPostData($row['Body']),
    						   'ip_address'	 => $row['IPAddress'],
    						   'use_sig'	 => 1,
    						   'use_emo'	 => 1,
    						   'queued'		 => ( $row['IsApproved'] == '0' ) ? 1 : 0 );
      $this->lib->convertPost('t-'.$row['TopicId'], $save);

      ipsRegistry::DB('hb')->build(array( 'select'		=> 'p.*',
                          'from'		=> array( 'activeforums_Replies' => 'p' ),
                           'add_join' => array( array( 'select' => 'c.*',
                                                       'from'  => array( 'activeforums_Content' => 'c'),
                                                       'where' => 'p.ContentId = c.ContentId',
                                                       'type' => 'left' ) ),
                            'where' => "TopicId='{$row['TopicId']}'",
                          'order'		=> 'p.ReplyId ASC' ) );
       $post_res = ipsRegistry::DB('hb')->execute();

       while ( $row = ipsRegistry::DB('hb')->fetch($post_res) )
       {
         $save = array( 'topic_id'    => intval($row['TopicId']),
      						   'author_id'	 => intval($row['AuthorId']),
      						   'author_name' => $row['AuthorName'],
      						   'post_date'	 => $this->lib->myStrToTime($row['DateCreated']),
      						   'post'		 => $this->fixPostData($row['Body']),
      						   'ip_address'	 => $row['IPAddress'],
      						   'use_sig'	 => 1,
      						   'use_emo'	 => 1,
      						   'queued'		 => ( $row['IsApproved'] == '0' ) ? 1 : 0 );
         $this->lib->convertPost('p-'.$row['ReplyId'], $save);
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
	private function convert_posts_bak()
	{
		//---------------------------
		// Set up
		//---------------------------
    $main = array( 'select'		=> 'p.*',
                   'from'		=> array( 'activeforums_Replies' => 'p' ),
                    'add_join' => array( array( 'select' => 'c.*',
                                                'from'  => array( 'activeforums_Content' => 'c'),
                                                'where' => 'p.ContentId = c.ContentId',
                                                'type' => 'left' ) ),
                   'order'		=> 'p.ReplyId ASC' );

		$loop = $this->lib->load('posts', $main);

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'topic_id'    => intval($row['TopicId']),
						   'post_title'  	=> $row['Subject'],
						   'author_id'	 => intval($row['AuthorId']),
						   'author_name' => $row['AuthorName'],
						   'post_date'	 => $this->lib->myStrToTime($row['DateCreated']),
						   'post'		 => $this->fixPostData($row['Body']),
						   'ip_address'	 => $row['IPAddress'],
						   'use_sig'	 => 1,
						   'use_emo'	 => 1,
						   'queued'		 => ( $row['IsApproved'] == '0' ) ? 1 : 0 );

			$this->lib->convertPost($row['ReplyId'], $save);
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
						'from' 	 => 'activeforums_Ranks',
            'where' => "ModuleId='1130'",
						'order'	 => 'RankId ASC' );

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
			$save = array( 'posts'	=> $row['MinPosts'],
						   'title'	=> $row['RankName'] );
			$this->lib->convertRank($row['RankId'], $save, $us['rank_opt']);
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
	{print 'Need data to finish this section';exit;
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => 'PostID, ThreadID, PostAuthor, UserID, CAST(Subject AS varchar) as Subject, PostDate, IsApproved, CAST(Body AS TEXT) as Body, IPAddress, SectionID',
					   'from' => 'cs_Posts',
					   'where' => "PostType = '2'",
					   'order' => 't.pollid ASC' );

		$loop = $this->lib->load('polls', $main, array('voters'));

		//---------------------------
		// Loop
		//---------------------------
		require_once( IPS_KERNEL_PATH . 'classXML.php' );

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$xml = new classXML( IPSSetUp::charSet );
			$xml->loadXML( $row['Body'] );

			foreach( $xml->fetchElements( 'VoteOptions' ) as $option )
			{
				$data = $this->_xml->fetchElementsFromRecord( $_el );

				if ( $data['appears'] AND intval( $data['frequency'] ) > $this->_minF )
				{
					return TRUE;
				}
			}

			//-----------------------------------------
			// Convert votes
			//-----------------------------------------
			$votes = array();

			ipsRegistry::DB('hb')->build(array( 'select' => '*', 'from' => 'pollvoted', 'where' => "pollid='{$row['pollid']}' AND registered='1'" ));
			$voterRes = ipsRegistry::DB('hb')->execute();
			while ( $voter = ipsRegistry::DB('hb')->fetch($voterRes) )
			{
				$vsave = array( 'vote_date'		 => time(),
								'tid'			 => $row['threadid'],
								'member_id'		 => $voter['memberid'],
								'forum_id'		 => $row['forumid'],
								'member_choices'=> serialize(array(1 => $row['optionid'])) );

				$this->lib->convertPollVoter($voter['voteid'], $vsave);
			}

			//-----------------------------------------
			// Options are stored in one place...
			//-----------------------------------------
			$choices = array();
			$votes = array();
			$totalVotes = 0;

			ipsRegistry::DB('hb')->build(array( 'select' => '*', 'from' => 'polloptions', 'where' => "pollid='{$row['pollid']}'" ));
			$choiceRes = ipsRegistry::DB('hb')->execute();
			while ( $choice = ipsRegistry::DB('hb')->fetch($choiceRes) )
			{
				$choices[ $choice['optionid'] ] = $choice['description'];
				$votes[ $choice['optionid'] ]	= $choice['votes'];
				$totalVotes += $choice['votes'];
			}

			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------
			$poll_array = array( // MegaBBS only allows one question per poll
								 1 => array( 'question'	=> $row['threadsubject'],
								 			 'choice'	=> $choices,
											 'votes'	=> $votes ) );
			$save = array( 'tid'		=> $row['threadid'],
						   'start_date'	=> strtotime($row['datecreated']),
						   'choices'   	=> addslashes(serialize($poll_array)),
						   'starter_id'	=> $row['memberid'] == '-1' ? 0 : $row['memberid'],
						   'votes'     	=> $totalVotes,
						   'forum_id'  	=> $row['forumid'],
						   'poll_question'	=> $row['threadsubject'] );

			$this->lib->convertPoll($row['pollid'], $save);
		}
		$this->lib->next();
	}

	private function convert_attachments()
	{print 'Need to finish this section.';exit;
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$this->lib->saveMoreInfo('attachments', array('attach_path'));

		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select'  => 'a.PostID, a.SectionID, a.UserID, a.Created, CAST(a.FileName AS varchar) as FileName, a.Content, a.ContentType, a.ContentSize, a.Height, a.Width',
					   'from'     => array( 'cs_Postattachments' => 'a' ),
					   'add_join' => array( array( 'select'	=> 'p.ThreadID, p.TotalViews',
					   							   'from'   => array( 'cs_Posts' => 'p' ),
					   							   'where'  => 'a.PostID = p.PostID',
					   							   'type'   => 'left' ) ),
					   'order'    => 'a.PostID' );

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
			// Sort out data
			$save = array( 'attach_rel_id'	   => $row['PostID'],
						   'attach_file'	   => $row['FileName'],
						   'attach_hits'	   => $row['TotalViews'],
						   'attach_date'	   => $this->lib->myStrToTime($row['PostDate']),
						   'attach_member_id'  => $row['UserID'],
						   'attach_rel_module' => 'post' );

			if ( strlen($row['Content']) != $row['ContentSize'] )
			{
				$save = array_merge( $save, array( 'attach_location' => $row['FileName'] ) );
				$done = $this->lib->convertAttachment($row['PostID'], $save, $path);
			}
			else
			{
				$save = array_merge( $save, array( 'data'   		 => $row['Content'],
												   'attach_filesize' => $row['ContentSize'] ) );
				$done = $this->lib->convertAttachment($row['PostID'], $save, '', TRUE);
			}
		}
		$this->lib->next();
	}
}

