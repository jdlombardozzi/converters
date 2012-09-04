<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * Ikonboard
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Andy Millne
 * @copyright           (c) 2009 Invision Power Services, Inc.
 */

$info = array( 'key'	=> 'ikonboard',
			   'name'	=> 'Ikonboard',
			   'login'	=> true );

class admin_convert_board_ikonboard extends ipsCommand
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
			'forum_perms'	=> array(),
			'groups' 		=> array('forum_perms'),
			'members'		=> array('groups'),
			'forums'		=> array('members'),
			'topics'		=> array('members', 'forums'),
			'posts'			=> array('members', 'topics'),


			);

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------

		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'Ikonboard &rarr; IP.Board Converter' );

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

		if (array_key_exists($this->request['do'], $this->actions) or $this->request['do'] == 'forum_info')
		{
			call_user_func(array($this, 'convert_'.$this->request['do']));
		}
		else
		{
			$this->lib->menu( array(
				'forums' => array(
					'single' => 'categories',
					'multi'  => array( 'categories', 'forum_info' )
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

                    	case 'members':
				return $this->lib->countRows('member_profiles');
				break;

			case 'forum_perms':
				return $this->lib->countRows('mem_groups');
				break;

			case 'groups':
				return $this->lib->countRows('mem_groups');
				break;

			case 'forums':
				return $this->lib->countRows('categories') + $this->lib->countRows('forum_info');
				break;

			case 'topics':
				return $this->lib->countRows('forum_topics');
				break;

                        case 'posts':
				return $this->lib->countRows('forum_posts');
				break;

			case 'pms':
				return $this->lib->countRows('message_data');
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
	private function fixPostData($text)
	{
            $text = preg_replace("/<!--QuoteBegin(.+)-->(.+)\((.+) @(.+)<!--QuoteEBegin-->(.+)<!--QuoteEnd-->(.+)<!--QuoteEEnd-->/", '[quote name=\'$3\']$5[/quote]', $text);

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

		$main = array(	'select' 	=> '*',
						'from' 		=> 'mem_groups',
						'order'		=> 'ID ASC',
					);

		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'ID', 'nf' => 'TITLE'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['ID'], $row['TITLE']);
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
						'from' 		=> 'mem_groups',
						'order'		=> 'ID ASC',
					);

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'ID', 'nf' => 'TITLE'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{

			$save = array(
				'g_title'			=> $row['TITLE'],
				'g_perm_id'			=> $row['ID'],
				);
			$this->lib->convertGroup($row['ID'], $save);
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

			'WEBSITE'	=> 'Website',
			'LOCATION'		=> 'Location',
			'ICQNUMBER'			=> 'ICQ Number',
			'AOLNAME'			=> 'AIM ID',
			'YAHOONAME'			=> 'Yahoo ID',
			'MSNNAME'			=> 'MSN ID',
                        'INTERESTS'     => 'Interests',

			);

		$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('avatar_path')));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'member_profiles',
						'order'		=> 'MEMBER_ID ASC',
					);

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		// Avatars
		$ask['avatar_path'] = array('type' => 'text', 'label' => 'Path to avatars folder (no trailing slash, default /path_to_ikonboard/avatars): ');
		
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
			$info = array(
				'id'				=> $row['MEMBER_ID'],
				'username'			=> $row['MEMBER_NAME'],
				'displayname'		=> $row['MEMBER_NAME'],
				'joined'			=> $row['MEMBER_JOINED'],
				'group'				=> $row['MEMBER_GROUP'],
				'password'			=> $row['MEMBER_PASSWORD'],
				'email'				=> $row['MEMBER_EMAIL'],
				);


			$members = array(
				'posts'				=> $row['MEMBER_POSTS'],
				'hide_email' 		=> $row['HIDE_EMAIL'],
				'time_offset'		=> $row['TIME_ADJUST'],
				'ip_address'		=> $row['MEMBER_IP'],
                                'misc'  => $row['MEMBER_NAME'],
				);

			// Profile
			$profile = array(
				'signature'			=> $this->fixPostData($row['SIGNATURE']),
				);

			//-----------------------------------------
			// Avatars
			//-----------------------------------------

			$path = '';


				// URL
				if (preg_match('/http/', $row['avatar']))
				{
					$profile['avatar_type'] = 'url';
					$profile['avatar_location'] = $row['MEMBER_AVATAR'];
				}
				// Gallery
				else
				{
					$profile['avatar_type'] = 'upload';
					$profile['avatar_location'] = $row['MEMBER_AVATAR'];
					$path = $us['avatar_path'];
				}
			


			//-----------------------------------------
			// Custom Profile fields
			//-----------------------------------------

			// Pseudo
			$custom = array();
			foreach ($pcpf as $id => $name)
			{
				if ($us[$id] != 'x')
				{
					$custom['field_'.$us[$id]] = $row[$id];
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
						'from' 		=> 'categories',
						'order'		=> 'CAT_ID ASC',
					);

		$loop = $this->lib->load('forums', $main, array(), 'forum_info');

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertForum('c'.$row['CAT_ID'], array('name' => $row['CAT_NAME'], 'parent_id' => -1), array());
		}

		$this->lib->next();

	}

	/**
	 * Convert Forums
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_forum_info()
	{

		//---------------------------
		// Set up
		//---------------------------

		$mainBuild = array(	'select' 	=> '*',
							'from' 		=> 'forum_info',
							'order'		=> 'FORUM_ID ASC',
						);

		$this->start = intval($this->request['st']);
		$this->end = $this->start + intval($this->request['cycle']);

		$mainBuild['limit'] = array($this->start, $this->end);

		$this->lib->errors = unserialize($this->settings['conv_error']);

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

				$parent = 'c'.$row['CATEGORY'];


			// Set info
			$save = array(
				'parent_id'			=> $parent,
				'position'			=> $row['FORUM_POSITION'],
				'last_id'			=> $row['L_TOPIC_ID'],
				'name'				=> $row['FORUM_NAME'],
				'description'		=> $row['FORUM_DESC'],
				'topics'			=> $row['FORUM_TOPICS'],
				'posts'				=> $row['FORUM_POSTS'],
				'inc_postcount'		=> 1,
				);

			// Save
			$this->lib->convertForum($row['FORUM_ID'], $save, array());

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
	 * Convert topics
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
						'from' 		=> 'forum_topics',
						'order'		=> 'TOPIC_ID ASC',

					);

		$loop = $this->lib->load('topics', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'title'				=> $row['TOPIC_TITLE'],
				'start_date'		=> $row['TOPIC_START_DATE'],
				'pinned'			=> $row['PIN_STATE'],
				'forum_id'			=> $row['FORUM_ID'],
				'starter_id'		=> $row['TOPIC_STARTER'],
				'starter_name'		=> $row['TOPIC_STARTER_N'],
				'last_poster_id'	=> $row['TOPIC_LAST_POSTER'],
				'views'				=> $row['TOPIC_VIEWS'],
				'state'				=> $row['TOPIC_STATE'],
				'approved'			=> $row['APPROVED'],
				);
			$this->lib->convertTopic($row['TOPIC_ID'], $save);

			

		}

		$this->lib->next();

	}

	/**
	 * Convert posts
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
						'from' 		=> 'forum_posts',
						'order'		=> 'POST_ID ASC',
					);

		$loop = $this->lib->load('posts', $main);

	
		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{

			$save = array(
				'topic_id'			=> $row['TOPIC_ID'],
				'post_date'			=> $row['POST_DATE'],
				'author_id'			=> $row['AUTHOR'],
				'ip_address'		=> $row['IP_ADDR'],
				'use_emo'			=> $row['ENABLE_EMO'],
				'post'				=> $this->fixPostData($row['POST']),
				'queued'			=> $row['QUEUED'],
				);
			$this->lib->convertPost($row['POST_ID'], $save);

		

		}

		$this->lib->next();

	}




}