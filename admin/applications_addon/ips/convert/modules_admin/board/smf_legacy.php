<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * SMF LEGACY (SMF 1.1)
 * Last Update: $Date: 2011-07-12 21:15:48 +0100 (Tue, 12 Jul 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 550 $
 */

/*
	IMPORTANT NOTE
	SMF 2.0 is currently in RC stages - but as people often ask
	for both SMF 1.1 and 2.0 conversions, I have written
	this one for 1.1, and the other one "smf" for 2.0.
	When 2.0 goes final, this one should be removed from the
	package so we don't have to mess around with backporting bug fixes. 
*/

$info = array( 'key'	=> 'smf_legacy',
			   'name'	=> 'SMF 1.1',
			   'login'	=> true );

class admin_convert_board_smf_legacy extends ipsCommand
{
	private $prefix = '';
	private $prefixFull = '';
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
			'forum_perms'	=> array(),
			'groups' 		=> array('forum_perms'),
			'members'		=> array('groups', 'emoticons'),
			'profile_friends' => array('members'),
			'ignored_users'	=> array('members'),
			'forums'		=> array('members'),
			'moderators'	=> array('groups', 'members', 'forums'),
			'topics'		=> array('members', 'forums'),
			'posts'			=> array('members', 'topics', 'emoticons'),
			'polls'			=> array('topics', 'members', 'forums'),
			'pms'			=> array('members', 'emoticons'),
			'attachments'	=> array('posts'),
			'banfilters'	=> array(),
			);

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------

		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'SMF &rarr; IP.Board Converter' );

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
					'single' => 'categories',
					'multi'  => array( 'categories', 'boards' )
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
				return $this->lib->countRows($this->prefixFull . 'membergroups');
				break;

			case 'groups':
				return $this->lib->countRows($this->prefixFull . 'membergroups');
				break;

			case 'forums':
				return $this->lib->countRows($this->prefixFull . 'categories') + $this->lib->countRows($this->prefixFull . 'boards');
				break;

			case 'posts':
				return $this->lib->countRows($this->prefixFull . 'messages');
				break;

			case 'pms':
				return $this->lib->countRows($this->prefix . 'personal_messages');
				break;

			case 'banfilters':
				return $this->lib->countRows($this->prefixFull . 'ban_items');
				break;

			case 'emoticons':
				return $this->lib->countRows($this->prefixFull . 'smileys');
				break;

			case 'profile_friends':
			case 'ignored_users':
			case 'members':
				return $this->lib->countRows($this->prefix . 'members');
				break;

			default:
				return $this->lib->countRows($this->prefixFull . $action);
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
	private function fixPostData($text)
	{
		// Sort out the list tags
		$text = str_replace('[li]', '[*]', $text);
		$text = str_replace('[/li]', '', $text);

		// Take care of the img tags too
		$text = preg_replace("#\[img width=(\d+) height=(\d+)\](.+)\[\/img\]#i", "[img]$3[/img]", $text);

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
						'from' 		=> $this->prefixFull . 'membergroups',
						'order'		=> 'id_group ASC',
					);

		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'ID_GROUP', 'nf' => 'groupName'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['ID_GROUP'], $row['groupName']);
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
						'from' 		=> $this->prefixFull . 'membergroups',
						'order'		=> 'id_group ASC',
					);

		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------

		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'ID_GROUP', 'nf' => 'groupName'));

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{
			$prefix = '';
			$suffix = '';
			if ($row['onlineColor'])
			{
				$prefix = "<span style='color:{$row['onlineColor']}'>";
				$suffix = '</span>';
			}

			$save = array(
				'g_title'			=> $row['groupName'],
				'g_perm_id'			=> $row['ID_GROUP'],
				'prefix'			=> $prefix,
				'suffix'			=> $suffix,
				'g_max_messages'	=> $row['maxMessages'],
				);
			$this->lib->convertGroup($row['ID_GROUP'], $save);
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
			'gender'		=> 'Gender',
			'websiteUrl'	=> 'Website',
			'location'		=> 'Location',
			'ICQ'			=> 'ICQ Number',
			'AIM'			=> 'AIM ID',
			'YIM'			=> 'Yahoo ID',
			'MSN'			=> 'MSN ID',
			);

		$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('attach_path', 'gal_path')));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> $this->prefix . 'members',
						'order'		=> 'id_member ASC',
					);

		$loop = $this->lib->load('members', $main);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		// Avatars
		$ask['gal_path'] = array('type' => 'text', 'label' => 'Path to avatars gallery folder (no trailing slash, default /path_to_smf/avatars): ');
		$ask['attach_path'] = array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually /path_to_smf/attachments):');

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
				'id'				=> $row['ID_MEMBER'],
				'username'			=> $row['memberName'],
				'joined'			=> $row['dateRegistered'],
				'group'				=> $row['ID_GROUP'],
				'password'			=> $row['passwd'],
				'email'				=> $row['emailAddress'],
				'secondary_groups'	=> $row['additionalGroups'],
				);

			// Member info
			$birthday = ($row['birthdate']) ? explode('-', $row['birthdate']) : null;

			$members = array(
				'posts'				=> $row['posts'],
				'last_visit'		=> $row['lastLogin'],
				'bday_day'			=> ($row['birthdate']) ? $birthday[2] : '',
				'bday_month'		=> ($row['birthdate']) ? $birthday[1] : '',
				'bday_year'			=> ($row['birthdate']) ? $birthday[0] : '',
				'hide_email' 		=> $row['hideEmail'],
				'time_offset'		=> $row['timeOffset'],
				'email_pm'      	=> $row['pm_email_notify'],
				'title'				=> $rank['usertitle'],
				'ip_address'		=> $row['memberIP'],
				'misc'				=> $row['passwordSalt'],
				);

			// Profile
			$profile = array(
				'signature'			=> $this->fixPostData($row['signature']),
				);

			//-----------------------------------------
			// Avatars
			//-----------------------------------------

			$path = '';

			if ($row['avatar'])
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
					$path = $us['gal_path'];
				}
			}
			// Upload
			else
			{
				$attach = ipsRegistry::DB('hb')->buildAndFetch(array(	'select' => '*', 'from' => $this->prefixFull . 'attachments', 'where' => 'ID_MEMBER='.$row['ID_MEMBER']));
				if ($attach)
				{
					$profile['photo_type'] = 'custom';
					$profile['photo_location'] = str_replace(' ', '_', $attach['filename']);
					$path = $us['attach_path'];
				}
			}

			//-----------------------------------------
			// Custom Profile fields
			//-----------------------------------------

			// Pseudo
			$custom = array();
			foreach ($pcpf as $id => $name)
			{
				if ($id == 'gender')
				{
					switch ($row['gender']) {
						case 1:
							$row['gender'] = 'm';
							break;

						case 2:
							$row['gender'] = 'f';
							break;

						default:
							$row['gender'] = 'u';
							break;
					}

				}
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
						'from' 		=> $this->prefixFull . 'categories',
						'order'		=> 'ID_CAT ASC',
					);

		$loop = $this->lib->load('forums', $main, array(), 'boards');

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$this->lib->convertForum('c'.$row['ID_CAT'], array('name' => $row['name'], 'parent_id' => -1), array());
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

		$mainBuild = array(	'select' 	=> '*',
							'from' 		=> $this->prefixFull . 'boards',
							'order'		=> 'ID_BOARD ASC',
						);

		$this->start = intval($this->request['st']);
		$this->end = $this->start + intval($this->request['cycle']);

		$mainBuild['limit'] = array($this->start, $this->end);

		$this->errors = unserialize($this->settings['conv_error']);

		ipsRegistry::DB('hb')->build($mainBuild);
		ipsRegistry::DB('hb')->execute();

		if (!ipsRegistry::DB('hb')->getTotalRows())
		{
			$action = 'forums';
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
			// Work out what the parent is
			if ($row['ID_PARENT'])
			{
				$parent = $row['ID_PARENT'];
			}
			else
			{
				$parent = 'c'.$row['ID_CAT'];
			}

			// Set info
			$save = array(
				'parent_id'			=> $parent,
				'position'			=> $row['boardOrder'],
				'last_id'			=> $row['ID_LAST_MSG'],
				'name'				=> $row['name'],
				'description'		=> $row['description'],
				'topics'			=> $row['numTopics'],
				'posts'				=> $row['numPosts'],
				'inc_postcount'		=> $row['countPosts'],
				);

			// Save
			$this->lib->convertForum($row['ID_BOARD'], $save, array());

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->prefixFull . 'log_notify', 'where' => "ID_BOARD={$row['ID_BOARD']}"));
			ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch())
			{
				$savetracker = array(
					'member_id'	=> $tracker['ID_MEMBER'],
					'forum_id'	=> $tracker['ID_BOARD'],
					);
				$this->lib->convertForumSubscription($tracker['ID_MEMBER'].'-'.$tracker['ID_BOARD'], $savetracker);
			}

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
						'from' 		=> array( $this->prefixFull . 'topics' => 't'),
						'order'		=> 't.ID_TOPIC ASC',
						'add_join'	=> array(
										array( 	'select' => 'p.subject, p.posterTime, p.posterName',
												'from'   =>	array( $this->prefixFull . 'messages' => 'p' ),
												'where'  => "p.ID_MSG=t.ID_FIRST_MSG",
												'type'   => 'left'
											),
										),

					);

		$loop = $this->lib->load('topics', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'title'				=> $row['subject'],
				'start_date'		=> $row['posterTime'],
				'pinned'			=> $row['isSticky'],
				'forum_id'			=> $row['ID_BOARD'],
				'starter_id'		=> $row['ID_MEMBER_STARTED'],
				'starter_name'		=> $row['posterName'],
				'last_poster_id'	=> $row['ID_MEMBER_UPDATED'],
				'poll_state'		=> (bool) $row['ID_POLL'],
				'posts'				=> $row['numReplies'],
				'views'				=> $row['numViews'],
				'state'				=> ($row['locked']) ? 'closed' : 'open',
				'approved'			=> 1,
				);
			$this->lib->convertTopic($row['ID_TOPIC'], $save);

			//-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->prefixFull . 'log_notify', 'where' => "ID_TOPIC={$row['ID_TOPIC']}"));
			ipsRegistry::DB('hb')->execute();
			while ($tracker = ipsRegistry::DB('hb')->fetch())
			{
				$savetracker = array(
					'member_id'	=> $tracker['ID_MEMBER'],
					'topic_id'	=> $tracker['ID_TOPIC'],
					);
				$this->lib->convertTopicSubscription($tracker['ID_MEMBER'].'-'.$tracker['ID_TOPIC'], $savetracker);
			}

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
						'from' 		=> $this->prefixFull . 'messages',
						'order'		=> 'ID_MSG ASC',
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
				'topic_id'			=> $row['ID_TOPIC'],
				'post_date'			=> $row['posterTime'],
				'author_id'			=> $row['ID_MEMBER'],
				'author_name'		=> $row['posterName'],
				'ip_address'		=> $row['posterIP'],
				'use_emo'			=> $row['smileysEnabled'],
				'use_sig'			=> 1,
				'edit_time'			=> $row['modifiedTime'],
				'edit_name'			=> $row['modifiedName'],
				'post'				=> $this->fixPostData($row['body']),
				);
			$this->lib->convertPost($row['ID_MSG'], $save);

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
						'from' 		=> $this->prefixFull . 'attachments',
						'order'		=> 'ID_ATTACH ASC',
					);

		$loop = $this->lib->load('attachments', $main);

		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------

		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_smf/attachments):')), 'path');

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
			// Skip thumbnails and avatars
			if ($row['attachmentType'] == 3 or !$row['ID_MSG'])
			{
				continue;
			}

			// Now where is it?
			if($row['file_hash'])
			{
				$location = $row['ID_ATTACH'].'_'.$row['file_hash'];
			}
			else
			{
				$location = $row['ID_ATTACH'] . '_' . str_replace( '.', '_', $row['filename'] ) . md5( str_replace(' ', '_', $row['filename'] ) );
			}
			$location = str_replace(' ', '_', $location);

			if(!file_exists($path.'/'.$location))
			{
				$location = str_replace( ' ', '_', $row['filename'] );
			}

			// Is this an image?
			$explode = explode('.', $row['filename']);
			$extension = array_pop($explode);
			$image = false;
			if ( in_array( $extension, array('jpeg', 'jpg', 'png', 'gif') ) )
			{
				$image = true;
			}

			// Date
			$post = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'ID_MEMBER, posterTime', 'from' => $this->prefixFull . 'messages', 'where' => 'ID_MSG='.$row['ID_MSG']));

			// Sort out data
			$save = array(
				'attach_ext'			=> $extension,
				'attach_file'			=> $row['filename'],
				'attach_location'		=> $location,
				'attach_is_image'		=> $image,
				'attach_hits'			=> $row['downloads'],
				'attach_date'			=> $post['posterTime'],
				'attach_member_id'		=> $post['ID_MEMBER'],
				'attach_filesize'		=> $row['size'],
				'attach_rel_id'			=> $row['ID_MSG'],
				'attach_rel_module'		=> 'post',
				'attach_img_width'		=> $row['width'],
				'attach_img_height'		=> $row['height'],
				);


			// Send em on
			$done = $this->lib->convertAttachment($row['ID_ATTACH'], $save, $path);

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
						'from' 		=> $this->prefixFull . 'polls',
						'order'		=> 'ID_POLL ASC',
					);

		$loop = $this->lib->load('polls', $main, array('voters'));

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Get data
			//-----------------------------------------

			$topic = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'ID_TOPIC, ID_BOARD, ID_FIRST_MSG', 'from' => $this->prefixFull . 'topics', 'where' => 'ID_POLL='.$row['ID_POLL']));
			$firstpost = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'posterTime', 'from' => $this->prefixFull . 'messages', 'where' => 'ID_MSG='.$topic['ID_FIRST_MSG']));

			//-----------------------------------------
			// Options are stored in one place...
			//-----------------------------------------

			$choice = array();
			$votes = array();

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->prefixFull . 'poll_choices', 'where' => "ID_POLL={$row['ID_POLL']}"));
			ipsRegistry::DB('hb')->execute();
			while ($options = ipsRegistry::DB('hb')->fetch())
			{
				$choice[ $options['ID_CHOICE'] ]	= $options['label'];
				$votes[ $options['ID_CHOICE'] ]	= $options['votes'];
				$total_votes[] = $options['votes'];
			}

			//-----------------------------------------
			// Votes in another...
			//-----------------------------------------

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->prefixFull . 'log_polls', 'where' => "ID_POLL={$row['ID_POLL']}"));
			ipsRegistry::DB('hb')->execute();
			while ($voter = ipsRegistry::DB('hb')->fetch())
			{
				$vsave = array(
					'tid'			=> $topic['ID_TOPIC'],
					'member_choices'=> serialize(array(1 => array($voter['ID_CHOICE']))),
					'member_id'		=> $voter['ID_MEMBER'],
					'forum_id'		=> $topic['ID_BOARD']
					);

				$this->lib->convertPollVoter($voter['ID_POLL'].'-'.$voter['ID_MEMBER'], $vsave);
			}

			//-----------------------------------------
			// Then we can do the actual poll
			//-----------------------------------------

			$poll_array = array(
				// SMF only allows one question per poll
				1 => array(
					'question'	=> $row['question'],
					'choice'	=> $choice,
					'votes'		=> $votes,
					)
				);

			$save = array(
				'tid'			=> $topic['ID_TOPIC'],
				'start_date'	=> $firstpost['posterTime'],
				'choices'   	=> addslashes(serialize($poll_array)),
				'starter_id'	=> $row['ID_MEMBER'],
				'votes'     	=> array_sum($total_votes),
				'forum_id'  	=> $topic['ID_BOARD'],
				'poll_question'	=> $row['question']
				);

			$this->lib->convertPoll($row['ID_POLL'], $save);
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
						'from' 		=> $this->prefix . 'personal_messages',
						'order'		=> 'ID_PM ASC',
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
				'msg_id'			=> $row['ID_PM'],
				'msg_topic_id'      => $row['ID_PM'],
				'msg_date'          => $row['msgtime'],
				'msg_post'          => $this->fixPostData($row['body']),
				'msg_post_key'      => md5(microtime()),
				'msg_author_id'     => $row['ID_MEMBER_FROM'],
				'msg_is_first_post' => 1
				);

			//-----------------------------------------
			// Map Data
			//-----------------------------------------

			$maps = array();
			$_invited   = array();
			$recipient = $row['ID_MEMBER_FROM'];

			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => $this->prefix . 'pm_recipients', 'where' => "ID_PM={$row['ID_PM']}"));
			ipsRegistry::DB('hb')->execute();
			while ($to = ipsRegistry::DB('hb')->fetch())
			{
				if ($to['ID_MEMBER'] == $to['ID_MEMBER_FROM'])
				{
					continue;
				}

				$maps[$to['ID_MEMBER']] = array(
					'map_user_id'     => $to['ID_MEMBER'],
					'map_topic_id'    => $to['ID_PM'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => 0,
					'map_last_topic_reply' => $row['msgtime'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => ($to['is_read']) ? 0 : 1,
					'map_is_system'   => 0,
					'map_is_starter'  => 0
					);

				$_invited[ $to['ID_MEMBER'] ] = $to['ID_MEMBER'];

				if (!$to['bcc'])
				{
					$recipient = $to['ID_MEMBER'];
				}
			}

			// Need to add self to ID_MEMBER_FROM
			if (!in_array($row['id_member_from'], array_keys($maps)))
			{
				$maps[$row['ID_MEMBER_FROM']] = array(
					'map_user_id'     => $row['ID_MEMBER_FROM'],
					'map_topic_id'    => $row['ID_PM'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => 0,
					'map_last_topic_reply' => $row['msgtime'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => 0,
					'map_is_system'   => 0,
					'map_is_starter'  => 1
					);
			}

			//-----------------------------------------
			// Map Data
			//-----------------------------------------

			$topic = array(
				'mt_id'			     => $row['ID_PM'],
				'mt_date'		     => $row['msgtime'],
				'mt_title'		     => $row['subject'],
				'mt_starter_id'	     => $row['ID_MEMBER_FROM'],
				'mt_start_time'      => $row['msgtime'],
				'mt_last_post_time'  => $row['msgtime'],
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
						'from'		=> $this->prefixFull . 'moderators'
					);

		$loop = $this->lib->load('moderators', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{

			$member = ipsRegistry::DB('hb')->buildAndFetch(array('select' => 'memberName', 'from' => $this->prefix . 'members', 'where' => 'ID_MEMBER='.$row['ID_MEMBER']));

			$save = array(
							   'forum_id'	  => $row['ID_BOARD'],
							   'member_name'  => $member['memberName'],
							   'member_id'	  => $row['ID_MEMBER']
						 );


			$this->lib->convertModerator($row['ID_BOARD'].'-'.$row['ID_MEMBER'], $save);
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
							'from' 		=> $this->prefixFull . 'ban_items',
							'order'		=> 'ID_BAN ASC',
						);

		$loop = $this->lib->load('banfilters', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			// Hostname
			if ($row['hostname'])
			{
				continue;
			}

			// User
			elseif ($row['ID_MEMBER'])
			{
				$link = $this->lib->getLink($row['ID_MEMBER'], 'members');
				if ($link)
				{
					$this->DB->update('members', array('member_banned' => 1), 'member_id='.$link);
				}
				continue;
			}

			// IP
			elseif ($row['ip_low1'])
			{
				$save = array(
					'ban_type'		=> 'ip',
					'ban_content'	=> $row['ip_low1'].'.'.$row['ip_low2'].'.'.$row['ip_low3'].'.'.$row['ip_low4'],
					);
			}

			// EMail
			elseif ($row['email_address'])
			{
				$save = array(
					'ban_type'		=> 'email',
					'ban_content'	=> $row['email_address'],
					);
			}

			$this->lib->convertBan($row['ID_BAN'], $save);
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

		$this->lib->saveMoreInfo('emoticons', array('emo_path', 'emo_opt', 'emo_set'));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> $this->prefixFull . 'smileys',
						'order'		=> 'ID_SMILEY ASC',
					);

		$loop = $this->lib->load('emoticons', $main);

		//-----------------------------------------
		// We need to know the path and how to handle duplicates
		//-----------------------------------------

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$path = $us['emo_path'];

		$ask = array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_smf/Smileys):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') );

		// This is to work out which set to use
		if ($path)
		{
			if (!is_readable($path))
			{
				$this->lib->error('Your remote emoticons path is not readable.');
			}

			$desc = '';
			foreach (glob($path.'/*/') as $folder)
			{
				$explode = explode('/', $folder);
				array_pop($explode);
				$name = array_pop($explode);
				$options[$name] = $name;
			}
			$ask['emo_set'] = array('type' => 'dropdown', 'label' => 'The set to use', 'options' => $options);
		}
		if ($us['emo_set'])
		{
			$path .= '/'.$us['emo_set'];
		}

		$this->lib->getMoreInfo('emoticons', $loop, $ask, 'path' );

		//-----------------------------------------
		// Check all is well
		//-----------------------------------------

		if (!is_readable($path))
		{
			$this->lib->error('Your remote emoticons path is not readable.');
		}
		if (!is_writable(DOC_IPS_ROOT_PATH.'public/style_emoticons/'))
		{
			$this->lib->error('Your IP.Board emoticons path is not writeable. '.DOC_IPS_ROOT_PATH.'public/style_emoticons/');
		}

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'typed'		=> $row['code'],
				'image'		=> $row['filename'],
				'clickable'	=> ($row['hidden']) ? 0 : 1,
				'emo_set'	=> 'default',
				);
			$done = $this->lib->convertEmoticon($row['ID_SMILEY'], $save, $us['emo_opt'], $path);
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

		$main = array(	'select' 	=> 'ID_MEMBER, buddy_list',
						'from' 		=> $this->prefix . 'members',
						'order'		=> 'ID_MEMBER ASC',
					);

		$loop = $this->lib->load('profile_friends', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			if (!$row['buddy_list'])
			{
				continue;
			}
			$explode = explode(',', $row['buddy_list']);
			foreach ($explode as $friend)
			{
				$save = array(
					'friends_member_id'	=> $row['ID_MEMBER'],
					'friends_friend_id'	=> $friend,
					'friends_approved'	=> '1',
					);
				$this->lib->convertFriend($row['ID_MEMBER'].'-'.$friend, $save);
			}
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

		$main = array(	'select' 	=> 'ID_MEMBER, pm_ignore_list',
						'from' 		=> $this->prefix . 'members',
						'order'		=> 'ID_MEMBER ASC',
					);

		$loop = $this->lib->load('ignored_users', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			if (!$row['pm_ignore_list'])
			{
				continue;
			}
			$explode = explode(',', $row['pm_ignore_list']);
			foreach ($explode as $foe)
			{
				$save = array(
					'ignore_owner_id'	=> $row['ID_MEMBER'],
					'ignore_ignore_id'	=> $foe,
					'ignore_messages'	=> '1',
					'ignore_topics'		=> '1',
					);
				$this->lib->convertIgnore($row['ID_MEMBER'].'-'.$foe, $save);
			}
		}

		$this->lib->next();

	}

}

