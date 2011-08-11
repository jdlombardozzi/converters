<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * vBulletin
 * Last Update: $Date: 2009-11-25 15:43:59 +0000 (Wed, 25 Nov 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 391 $
 */

$info = array( 'key'	=> 'ning',
			   'name'	=> 'Ning',
			   'login'	=> FALSE,
			   'nodb' => TRUE );

$custom = array( 'directory' => 'Input directory of Ning json export. (Contains: ning-members.json, ning-discussions.json)' );

class admin_convert_board_ning extends ipsCommand
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
		$this->actions = array( 'members'	=> array(),
								'forums'	=> array(),
								'topics'	=> array('forums'),
								'posts'		=> array('topics') );

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'Ning &rarr; IP.Board Converter' );

		$us = unserialize($this->settings['conv_extra']);
		$this->sourceDirectory = $us[$this->lib->app['name']]['core']['directory'];

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
				return count($this->_parseMemberListFile());
				break;

			case 'forums':
				$discussionData = $this->_parseDiscussionListFile();
				$categories = array();
				foreach ( $discussionData as $row )
				{
					if ( in_array( $row->category, $categories) ) continue;
					$categories[] = $row->category;
				}
				return count($categories);
				break;

			case 'topics':
				return count($this->_parseDiscussionListFile());
				break;

			case 'posts':
				$discussionData = $this->_parseDiscussionListFile();
				$count = 0;
				foreach ( $discussionData as $row )
				{
					$count = $count + count($row->comments) + 1;
				}
				return $count;
				break;

			default:
				return 0;
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
				return TRUE;
				break;

			default:
				return FALSE;
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
			'gender'			=> 'Gender',
			'location'			=> 'Location',
			'country'			=> 'Country',
			'zip'			=> 'Zip Code' );

		$this->lib->saveMoreInfo( 'members', array_keys($pcpf) );

		//---------------------------
		// Set up
		//---------------------------
		$loop = $this->lib->load('members', FALSE);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

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

		$memberData = $this->_parseMemberListFile();
		//---------------------------
		// Loop
		//---------------------------
		foreach ( $memberData as $id => $row )
		{
			//-----------------------------------------
			// Set info
			//-----------------------------------------

			// Basic info
			$info = array( 'id'             	=> $row->contributorName,
							'username'     	 	=> $row->email,
							'email'			 	=> $row->email,
							'displayname' => $row->fullName,
							'joined'			=> strtotime($row->createdDate),
							'password'		 => md5(microtime()) );

			// Member info
			$birthday = ($row->birthdate) ? explode('-', $row->birthdate) : null;

			$members = array(
				'title'				=> $row->level,
				'posts'				=> 0,
				'time_offset'		=> 0,
				'bday_day'			=> $birthday ? $birthday[2] : '',
				'bday_month'		=> $birthday ? $birthday[1] : '',
				'bday_year'			=> $birthday ? $birthday[0] : '',
				'ip_address'		=> '127.0.0.1' );

			//-----------------------------------------
			// Custom Profile fields
			//-----------------------------------------
			foreach ($pcpf as $id => $name)
			{
				if ($us[$id] != 'x')
				{
					$custom['field_'.$us[$id]] = $row->$id;
				}
			}

			//-----------------------------------------
			// Go
			//----------------------------------------
			$this->lib->convertMember($info, $members, array(), array() );
		}

		// Save that it's been completed
		$get = unserialize($this->settings['conv_completed']);
		$us = $get[$this->lib->app['name']];
		$us = is_array($us) ? $us : array();
		if (empty($this->lib->errors))
		{
			$us = array_merge($us, array('members' => true));
		}
		else
		{
			$us = array_merge($us, array('members' => 'e'));
		}
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_completed' => serialize($get)));

		// Display
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
		$info = $this->lib->menuRow('members');

		$this->registry->output->html .= $this->lib->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
		$this->sendOutput();
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
		$loop = $this->lib->load('forums', FALSE);

		// Do we need to create a forum?
		if ( !$this->lib->getLink('master', 'forums', true) )
		{
			$this->lib->convertForum('master', array( 'name' => 'Ning Forums', 'parent_id' => -1 ), array());
		}

		$discussionData = $this->_parseDiscussionListFile();

		//---------------------------
		// Loop
		//---------------------------
		$position = 0;
		$categories = array();
		foreach ( $discussionData as $row )
		{
			// Check if category has been converted yet
			if ( in_array( $row->category, $categories) )
			{
				// Exists, skip it.
				continue;
			}

			//-----------------------------------------
			// Save
			//-----------------------------------------
			$save = array( 'topics'			=> 0,
						   'posts'			  	=> 0,
						   'parent_id'		  	=> 'master',
						   'name'			  	=> $row->category,
						   'position'		  	=> $position,
						   'use_ibc'		  	=> 1,
							'use_html'		  	=> 0,
							'status'			=> 1,
							'inc_postcount'	  	=> 1,
							'sub_can_post'		=> 1,
							'redirect_on'		=> 0,
							'inc_postcount'		=> 1 );

			$this->lib->convertForum( addslashes($row->category), $save, array());
			$categories[] = $row->category;
			$position++;
		}

		// Save that it's been completed
		$get = unserialize($this->settings['conv_completed']);
		$us = $get[$this->lib->app['name']];
		$us = is_array($us) ? $us : array();
		if (empty($this->lib->errors))
		{
			$us = array_merge($us, array('forums' => true));
		}
		else
		{
			$us = array_merge($us, array('forums' => 'e'));
		}
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_completed' => serialize($get)));

		// Display
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
		$info = $this->lib->menuRow('forums');

		$this->registry->output->html .= $this->lib->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
		$this->sendOutput();
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
		$loop = $this->lib->load('topics', FALSE);

		$discussionData = $this->_parseDiscussionListFile();

		//---------------------------
		// Loop
		//---------------------------
		$categories = array();
		foreach ( $discussionData as $row )
		{
			$memberId = $this->lib->getLink( $row->contributorName, 'members', TRUE);
			if ( $memberId ) {
				$member = $this->DB->buildAndFetch( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => "member_id='{$memberId}'" ) );
			}

			$save = array( 'title'		  => $row->title,
						   'state'		  => 'open',
						   'posts'		  => count($row->comments),
						   'starter_id'   => $row->contributorName,
						   'starter_name' => $member['members_display_name'],
						   'start_date'	  => strtotime($row->createDate),
						   'poll_state'	  => 0,
						   'views'		  => 0,
						   'forum_id'	  => addslashes($row->category),
						   'approved'	  => 1,
						   'pinned'		  => 0 );

			$this->lib->convertTopic($row->id, $save);
		}

		// Save that it's been completed
		$get = unserialize($this->settings['conv_completed']);
		$us = $get[$this->lib->app['name']];
		$us = is_array($us) ? $us : array();
		if (empty($this->lib->errors))
		{
			$us = array_merge($us, array('topics' => true));
		}
		else
		{
			$us = array_merge($us, array('topics' => 'e'));
		}
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_completed' => serialize($get)));

		// Display
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
		$info = $this->lib->menuRow('topics');

		$this->registry->output->html .= $this->lib->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
		$this->sendOutput();
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
		$loop = $this->lib->load('posts', FALSE);

		$discussionData = $this->_parseDiscussionListFile();

		//---------------------------
		// Loop
		//---------------------------
		foreach ( $discussionData as $row )
		{
			// Get member name
			$memberId = $this->lib->getLink( $row->contributorName, 'members', TRUE);
			if ( $memberId ) {
				$member = $this->DB->buildAndFetch( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => "member_id='{$memberId}'" ) );
			}

			// Convert topic first post
			$save = array( 'author_id'		=> $row->contributorName,
						   'author_name' 	=> $member['members_display_name'],
						   'use_sig'     	=> 1,
						   'use_emo'     	=> 1,
						   'ip_address' 	=> '127.0.0.1',
						   'post_date'   	=> strtotime($row->createdDate),
						   'post'		 	=> $this->fixPostData($row->description),
						   'queued'      	=> 0,
						   'topic_id'    	=> $row->id );

			$this->lib->convertPost($row->id, $save);

			if ( !isset($row->comments) )
			{
				continue;
			}

			// Cycle topic reply posts
			foreach ( $row->comments as $post )
			{
				$memberId = $this->lib->getLink( $post->contributorName, 'members', TRUE);
				if ( $memberId ) {
					$member = $this->DB->buildAndFetch( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => "member_id='{$memberId}'" ) );
				}

				$save = array( 'author_id'		=> $post->contributorName,
							   'author_name' 	=> $member['members_display_name'],
							   'use_sig'     	=> 1,
							   'use_emo'     	=> 1,
							   'ip_address' 	=> '127.0.0.1',
							   'post_date'   	=> strtotime($post->createdDate),
							   'post'		 	=> $this->fixPostData($post->description),
							   'queued'      	=> 0,
							   'topic_id'    	=> $row->id );

				$this->lib->convertPost($post->id, $save);
			}
		}

		// Save that it's been completed
		$get = unserialize($this->settings['conv_completed']);
		$us = $get[$this->lib->app['name']];
		$us = is_array($us) ? $us : array();
		if (empty($this->lib->errors))
		{
			$us = array_merge($us, array('posts' => true));
		}
		else
		{
			$us = array_merge($us, array('posts' => 'e'));
		}
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_completed' => serialize($get)));

		// Display
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
		$info = $this->lib->menuRow('posts');

		$this->registry->output->html .= $this->lib->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
		$this->sendOutput();
	}

	// Parses the NING forum list file and returns it as an array
	private function _parseMemberListFile()
	{
		$jsonData = file_get_contents($this->sourceDirectory . "/ning-members.json", "r");
		$jsonData = trim($jsonData, "()");
		$jsonData = str_replace('}{', '},{', $jsonData);

		$parsedData = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonData), FALSE );

		if ( $parsedData == NULL || $parsedData == FALSE )
		{
			return FALSE;
		}

		// Returns an array of member records
		return $parsedData;
	}

	// Parses the NING forum list file and returns it as an array
	private function _parseDiscussionListFile()
	{
		$jsonData = file_get_contents($this->sourceDirectory . "/ning-discussions.json", "r");
		$jsonData = trim($jsonData, "()");
		$jsonData = str_replace('}{', '},{', $jsonData);

		$parsedData = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonData), FALSE );

		if ( $parsedData == NULL || $parsedData == FALSE )
		{
			return FALSE;
		}

		// Returns an array of member records
		return $parsedData;
	}
}