<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * InfoPop
 * Last Update: $Date: 2009-11-25 16:43:59 +0100(mer, 25 nov 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 391 $
 */

$info = array( 'key'   => 'infopop',
			   'name'  => 'Infopop/Social Strata',
			   'login' => FALSE );
		
class admin_convert_board_infopop extends ipsCommand
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
								'pfields'		=> array(),
								'members'		=> array('forum_perms', 'pfields', 'groups'),
								'forums'		=> array('members'),
								'topics'		=> array('members'),
								'posts'			=> array('members', 'topics'),
								'pms'			=> array('members'),
								//'ranks'			=> array(),
								/*'polls'         => array('topics'),*/
								'attachments'   => array('posts') );
				
		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'InfoPop / Social Strata &rarr; IP.Board Converter' );

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
				return $this->lib->countRows('IP_GROUPS', "GROUP_TYPE NOT IN  ('PERSONAL', 'MODERATOR')");
				break;
					
			case 'pfields':
				$count = @ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'COUNT(DISTINCT PROFILE_FIELD_NAME) as count', 'from' => 'IP_CUSTOM_PROFILE_FIELDS', 'where' => "PROFILE_FIELD_NAME LIKE 'CUSTOM_PROFILE_FIELD_%'" ) );
				return $count['count'];
				break;
					
			case 'members':
				return  $this->lib->countRows('IP_USERS');
				break;
				
			case 'forums':
				return $this->lib->countRows('IP_CAT_CATEGORY');
				break;
				
			case 'topics':
				return  $this->lib->countRows('IP_F_FORUM_TOPIC');
				break;
				
			case 'posts':
				$count = @ipsRegistry::DB('hb')->buildAndFetch( array( 'select'	=> 'count(*) as count',
																	   'from'   => array( 'IP_T_MESSAGE' => 'msg'),
																	   'add_join'   => array( array( 'from' => array( 'IP_F_FORUM_TOPIC' => 'ft' ),
																	   								 'where' => 'msg.TOPIC_OID = ft.TOPIC_OID',
																	   								 'type' => 'inner' ),
																	   						array( 'from' => array( 'IP_T_TOPIC' => 't' ),
																	   						'where' => 'ft.TOPIC_OID = t.TOPIC_OID AND t.IS_TOPIC_ARCHIVED=0',
																	   						'type' => 'inner' ) ) ) );
																	   								 
				$archiveCount = @ipsRegistry::DB('hb')->buildAndFetch( array( 'select'	=> 'count(*) as count',
																	   'from'   => array( 'IP_T_ARCHIVED_MESSAGE' => 'msg'),
																	   'add_join'   => array( array( 'from' => array( 'IP_F_FORUM_TOPIC' => 'ft' ),
																	   								 'where' => 'msg.TOPIC_OID = ft.TOPIC_OID',
																	   								 'type' => 'inner' ),
																	   						array( 'from' => array( 'IP_T_TOPIC' => 't' ),
																	   						'where' => 'ft.TOPIC_OID = t.TOPIC_OID AND t.IS_TOPIC_ARCHIVED=1',
																	   						'type' => 'inner' ) ) ) );
/*SELECT count( *  )
FROM `ip_t_message`
WHERE topic_oid
IN (

SELECT ft.topic_oid
FROM `ip_f_forum_topic` ft, `ip_t_topic` t
WHERE t.topic_oid = ft.topic_oid
AND t.is_topic_archived =0*/																	   						
				return $count['count'] + $archiveCount['count'];
				break;
				
			case 'unarchivedposts':
				$count = @ipsRegistry::DB('hb')->buildAndFetch( array( 'select'	=> 'count(*) as count',
																	   'from'   => array( 'IP_T_MESSAGE' => 'msg'),
																	   'add_join'   => array( array( 'from' => array( 'IP_F_FORUM_TOPIC' => 'ft' ),
																	   								 'where' => 'msg.TOPIC_OID = ft.TOPIC_OID',
																	   								 'type' => 'inner' ),
																	   						array( 'from' => array( 'IP_T_TOPIC' => 't' ),
																	   						'where' => 'ft.TOPIC_OID = t.TOPIC_OID AND t.IS_TOPIC_ARCHIVED=0',
																	   						'type' => 'inner' ) ) ) );
				return $count['count'];
				break;
				
			case 'pms':
				return  $this->lib->countRows('IP_PT_PRIVATE_TOPIC');
				break;
				
			case 'attachments':
				return $this->lib->countRows('UPLOADS');
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
		$post = preg_replace("#\[align=(.+)\](.+)\[/align\]#i", "[$1]$2[/$1]", $post);
		
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
					   'from'   => 'IP_GROUPS',
					   'where'  => "GROUP_TYPE NOT IN  ('PERSONAL', 'MODERATOR')",
					   'order'  => 'GROUP_OID ASC' );
					
		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'GROUP_OID', 'nf' => 'GROUP_NAME'));

		//---------------------------
		// Loop
		//---------------------------
		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['GROUP_OID'], $row['GROUP_NAME']);			
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
					   'from'   => 'IP_GROUPS',
					   'where'  => "GROUP_TYPE NOT IN  ('PERSONAL', 'MODERATOR')",
					   'order'  => 'GROUP_OID ASC' );
					
		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'GROUP_OID', 'nf' => 'GROUP_NAME'));

		//---------------------------
		// Loop
		//---------------------------
		
		foreach( $loop as $row )
		{						
			$save = array(
				'g_title'	  => $row['GROUP_NAME'],
				'g_perm_id'	  => $row['GROUP_OID'],
				'g_access_cp' => stristr($row['Administrators'], 'admin') ? 1 : 0 );
			$this->lib->convertGroup($row['GROUP_OID'], $save);			
		}
		$this->lib->next();
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
			$main = array( 'select' => 'DISTINCT PROFILE_FIELD_NAME',
						   'from'   => 'IP_CUSTOM_PROFILE_FIELDS',
						   'where'  => "PROFILE_FIELD_NAME LIKE 'CUSTOM_PROFILE_FIELD_%'" );
			
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
				$group = $this->lib->convertPFieldGroup(1, array('pf_group_name' => 'Converted', 'pf_group_key' => 'infopop'), true);
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
				//$customId = str_replace("CUSTOM_PROFILE_FIELD_", "", $row['PROFILE_FIELD_NAME']);
				$data = array( 'pf_type'		=> 'text',
							   'pf_title'  => $row['PROFILE_FIELD_NAME'],
							   'pf_member_hide'	=> 1,
							   'pf_member_edit'	=> 1,
							   'pf_key' => $row['PROFILE_FIELD_NAME'],
							   'pf_group_id' => 1 );
							   
				// Carry on...
				$this->lib->convertPField($row['PROFILE_FIELD_NAME'], $data);			
			}
			
			// Save pfield_data
			$get[$this->lib->app['name']] = $us;
			IPSLib::updateSettings(array('conv_extra' => serialize($get)));
			
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
		$pcpf = array( 'FIRST_NAME'		=> 'First Name',
					   'LAST_NAME'	  => 'Last Name',
					   'HOME_PAGE_URL'	  => 'Website',
					   'LOCATION'	  => 'Location',
					   'OCCUPATION'	  => 'Occupation',
					   'INTERESTS'  	  => 'Interests',
					   'BIO' => 'Biography' );
		
		$this->lib->saveMoreInfo('members', array_keys($pcpf));

		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select'	=> 'users.*',
					   'from'		=> array( 'IP_USERS' => 'users' ),
					   'add_join' => array( array( 'select' => 'profile.*',
					   							   'from'   => array( 'IP_PROFILES' => 'profile' ),
					   							   'where'  => 'profile.USER_OID = users.USER_OID',
					   							   'type'   => 'left' ),
					   						array( 'select' => 'stats.*',
					   							   'from'   => array( 'IP_USER_STATS' => 'stats'),
					   							   'where'  => 'stats.USER_OID = users.USER_OID',
					   							   'type'   => 'left' ) ),
					   	'order'	=> 'users.USER_OID ASC' );

		$loop = $this->lib->load('members', $main);
		
		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();
		
		// We need to know the avatars path
		//$ask['avvy_path'] = array('type' => 'text', 'label' => 'The path to the folder where custom avatars are saved (no trailing slash - usually /path_to_cbb/uploads):');
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
			if ( $row['LOGIN'] == "" ) { $row['LOGIN'] = $row['EMAIL'];}
			if ( $row['EMAIL'] == "" ) { $row['EMAIL'] = 'user_'.$row['USER_OID']. '@atime.org';}
			if ( $row['DISPLAY_NAME'] == "" ) { $row['DISPLAY_NAME'] = $row['LOGIN'];}
			
			//-----------------------------------------
			// Set info
			//-----------------------------------------
			ipsRegistry::DB('hb')->build(  array( 'select' => 'GROUP_OID', 'from' => 'IP_GROUP_USERS', 'where' => "USER_OID='{$row['USER_OID']}'" ) );
			$groupRes = ipsRegistry::DB('hb')->execute();
			
			while ( $group = ipsRegistry::DB('hb')->fetch($groupRes) )
			{
				$groups[$group['GROUP_OID']] = $group['GROUP_OID'];
			}
			
			// Don't allow duplicate group.
			unset($groups[ $row['GROUPEE_USER_OID'] ]);
				
			// Basic info				
			$info = array( 'id'				  => $row['USER_OID'],
						   'group'			  => $row['GROUPEE_USER_OID'],
						   'secondary_groups' => $groups,
						   'joined'	   		  => intval(strtotime($row['REGISTRATION_DATE'])),
						   'username'  		  => $row['LOGIN'],
						   'displayname'      => $row['DISPLAY_NAME'],
						   'email'	   		  => $row['EMAIL'],
						   'password' 		  => $row['PASSWORD'] );
			
			list($bday_year, $bday_month, $bday_day) = explode("-", $row['DOB']);

			// Member info
			$members = array( 'ip_address'			  => $row['USER_IP'] == '' ? '127.0.0.1' : $row['USER_IP'],
							  'posts'				  => $row['CUMULATIVE_USER_POST_COUNT'],
							  'allow_admin_mails' 	  => $row['HAS_OPTED_OUT_OF_EMAIL'] ? 0 : 1,
							  'time_offset'			  => 0,
							  'hide_email'			  => $row['DISPLAY_EMAIL'] == $row['EMAIL'] ? 1 : 0,
							  'email_pm'			  => 0,
							  'last_post'			  => 0,
							  'view_sigs'			  => 1,
							  'view_avs'			  => 1,
							  'msg_show_notification' => 1,
							  'last_visit'			  => intval(strtotime($row['LAST_LOGIN_DATETIME'])),
							  'last_activity'		  => intval(strtotime($row['LAST_LOGIN_DATETIME'])),
							  'coppa_user'			  => 0,
							  'members_disable_pm'	  => 0 );

			// Profile
			$profile = array( 'signature' => $this->fixPostData($row['SIGNATURE']) );

			//-----------------------------------------
			// Avatars and profile pictures
			//-----------------------------------------
			if ( $row['AVATAR_URL'] != '' )
			{
				$profile['avatar_type'] = 'url';
				$profile['avatar_location'] = $row['AVATAR_URL'];
			}
			
			ipsRegistry::DB('hb')->build( array( 'select' => 'PROFILE_FIELD_NAME, PROFILE_FIELD_VALUE',
							'from'   =>	'IP_CUSTOM_PROFILE_FIELDS',
							'where'  => "PROFILE_FIELD_NAME LIKE 'CUSTOM_PROFILE_FIELD_%' AND USER_OID='{$row['USER_OID']}'" ) );
			$customRes = ipsRegistry::DB('hb')->execute();
			$custom = array();
			$customPFields = array();
			while( $customRow = ipsRegistry::DB('hb')->fetch($customRes) )
			{
				$customPFields[ $customRow['PROFILE_FIELD_NAME'] ] = $customRow['PROFILE_FIELD_VALUE'];
			}
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
				$custom['field_'.$field['pf_id']] = $customPFields[$field['pf_title']];
			}
			
			//-----------------------------------------
			// And go!
			//-----------------------------------------
			$this->lib->convertMember($info, $members, $profile, $custom, NULL);	
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
						'from'  => 'IP_CAT_CATEGORY',
						'where' => 'PARENT_CATEGORY_OID IS NULL',
						'order' => 'PARENT_CATEGORY_OID ASC' );
						
		$loop = $this->lib->load('forums', $main, array(), array(), TRUE );
		
		$this->lib->getMoreInfo('forums', $loop);	
		
		//---------------------------
		// Loop
		//---------------------------
		foreach ( $loop as $row )
		{
			// Set info
			$save = array( 'parent_id'	   => $row['PARENT_CATEGORY_OID '] == NULL ? -1 : 'C_'.$row['PARENT_CATEGORY_OID'],
						   'position'	   => $row['THREADING_ORDER'],
						   'name'		   => $row['CATEGORY_NAME'],
						   'description'   => $row['CATEGORY_DESCRIPTION'],
						   'inc_postcount' => 1,
						   'sub_can_post'  => 0,
						   'status'		   => 1 );
			// Save
			$this->lib->convertForum('C_'.$row['CATEGORY_OID'], $save, array());		
		}
								
					
		//---------------------------
		// Set up
		//---------------------------
		ipsRegistry::DB('hb')->build( array( 'select'	=> 'f.*',
											 'from'	    => array( 'IP_F_FORUM' => 'f'),
											 'add_join' => array( array( 'select' => 'rc.*',
											 							 'from'   => array( 'IP_CAT_RESOURCE_CATEGORY' => 'rc' ),
											 							 'where'  => 'f.FORUM_OID = rc.RESOURCE_OID',
											 							 'type'   => 'left' ),
											 					  array( 'select' => 'c.*',
											 						  	 'from'   => array( 'IP_CAT_CATEGORY' => 'c' ),
											 						  	 'where'  => 'rc.CATEGORY_OID = c.CATEGORY_OID',
											 						  	 'type'   => 'left'),
											 					  array( 'select' => 's.*',
											 						  	 'from'   => array( 'IP_F_FORUM_STATS' => 's' ),
											 						  	 'where'  => 'f.FORUM_OID = s.FORUM_OID',
											 						  	 'type'   => 'left' ) ),
											 'where'    => 'c.PARENT_CATEGORY_OID IS NOT NULL',
											 'order'	=> 'f.FORUM_OID ASC' ) );
		$forumRes = ipsRegistry::DB('hb')->execute();
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($forumRes) )
		{		
			//-----------------------------------------
			// And go
			//-----------------------------------------
			$save = array( 'parent_id'		=> 'C_'.$row['PARENT_CATEGORY_OID'],
						   'position'		=> $row['THREADING_ORDER'],
						   'name'			=> $row['CATEGORY_NAME'],
						   'description' => $row['CATEGORY_DESCRIPTION'],
						   'sub_can_post'	=> 1,
						   'redirect_on'	=> 0,
						   'redirect_hits' => 0,
						   'status'		=> $row['IS_FORUM_ENABLED'],
						   'posts'			=> $row['FORUM_POST_COUNT'],
						   'topics'		=> $row['FORUM_TOPIC_COUNT'],
						   'use_html'         	=> $row['IS_HTML_ENABLED'],
						   'use_ibc'      => $row['IS_UBB_CODE_ALLOWED'] );
			
			$this->lib->convertForum($row['FORUM_OID'], $save, array());
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
		$main = array( 'select'	 => 'ft.TOPIC_OID, ft.FORUM_OID, ft.LAST_TOPIC_POST_DATETIME, ft.TOPIC_POSTED_DATETIME, ft.HIGHLIGHTED_THREADING_ORDER, ft.IS_VISIBLE',
					   'from'	 => array( 'IP_F_FORUM_TOPIC' => 'ft' ),
					   'add_join' => array( array( 'select'	=> 't.SUBJECT, t.IS_TOPIC_CLOSED, t.IS_TOPIC_ARCHIVED',
					   							   'from'	=> array( 'IP_T_TOPIC' => 't'),
					   							   'where'	=> 't.TOPIC_OID = ft.TOPIC_OID',
					   							   'type'	=> 'left' ),
					   						array( 'select'	=> 's.topic_post_count, s.message_page_view_count',
					   							   'from'	=> array( 'IP_T_TOPIC_STATS' => 's'),
					   							   'where'	=> 's.TOPIC_OID = ft.TOPIC_OID',
					   							   'type'	=> 'left' ) ),
					    'order'   => "ft.TOPIC_POSTED_DATETIME ASC" );

		$loop = $this->lib->load('topics', $main, array());

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			// Make An Attempt to Get The First Poster Details
			$starterBuild = array( 'select' => 'msg.*',
						   'from' => array( ($row['IS_TOPIC_ARCHIVED'] == 1 ? 'IP_T_ARCHIVED_MESSAGE' : 'IP_T_MESSAGE') => 'msg'),
						   'where' => "msg.MESSAGE_OID='{$row['TOPIC_OID']}'" );
						   
			
			if ( $row['IS_TOPIC_ARCHIVED'] != 1 )
			{
				$starterBuild['add_join'] = array( array( 'select' => 'con.AUTHOR_OID, con.GUEST_AUTHOR_OID',
														  'from' => array( 'IP_C_CONTENT' => 'con' ),
														  'where' => "msg.MESSAGE_OID = con.CONTENT_OID",
														  'type' => 'left' ),
												   array( 'select' => 'm.DISPLAY_NAME',
												   		  'from' => array( 'IP_USERS' => 'm' ),
												   		  'where' => 'con.AUTHOR_OID = m.USER_OID' ),
												   array( 'select' => 'mg.GUEST_USER_NAME',
												   		  'from' => array( 'IP_C_GUEST_USER' => 'mg' ),
												   		  'where' => 'con.GUEST_AUTHOR_OID = mg.GUEST_USER_OID' ) );
			} else {
				$starterBuild['add_join'] = array( array( 'select' => 'm.DISPLAY_NAME',
												   		  'from' => array( 'IP_USERS' => 'm' ),
												   		  'where' => 'msg.AUTHOR_OID = m.USER_OID' ),
												   array( 'select' => 'mg.GUEST_USER_NAME',
												   		  'from' => array( 'IP_C_GUEST_USER' => 'mg' ),
												   		  'where' => 'msg.GUEST_AUTHOR_OID = mg.GUEST_USER_OID' ) );	
			}
			
			$starter = ipsRegistry::DB('hb')->buildAndFetch($starterBuild);
			
			if( $starter['AUTHOR_OID'] == "" && $starter['GUEST_AUTHOR_OID'] == "" ) {
				ipsRegistry::DB('hb')->build($starterBuild);
				print "<PRE>Fail: " . ipsRegistry::DB('hb')->fetchSqlString();print_r($row);print_r($starter);exit;
			}
			
			$save = array( 'forum_id'			=> $row['FORUM_OID'],
						   'title'				=> $row['SUBJECT'],
						   'poll_state'			=> 0,
						   'starter_id'			=> $starter['AUTHOR_OID'] != '' ? $starter['AUTHOR_OID'] : $starter['GUEST_AUTHOR_OID'],
						   'starter_name'		=> $starter['AUTHOR_OID'] != '' ? $starter['DISPLAY_NAME'] : $starter['GUEST_USER_NAME'],
						   'start_date'			=> intval(strtotime($row['TOPIC_POSTED_DATETIME'])),
						   'last_post'			=> intval(strtotime($row['LAST_TOPIC_POST_DATETIME'])),
						   'views'				=> intval($row['message_page_view_count']),
						   'posts'				=> intval($row['topic_post_count']),
						   'state'		   	 	=> $row['IS_TOPIC_CLOSED'] == '1' ? 'closed' : 'open',
						   'pinned'				=> $row['HIGHLIGHTED_THREADING_ORDER'] > 0 ? 1 : 0,
						   'approved'			=> $row['IS_VISIBLE'] == 1 ? 1 : 0 );
			$this->lib->convertTopic($row['TOPIC_OID'], $save);
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
		$lastId = $this->request['lastId'] > 0 ? $this->request['lastId'] : 0;
		$lastTopic = $this->request['lastTopic'] > 0 ? $this->request['lastTopic'] : 0;
		$cycleCount = 0;

		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select'	 => 'ft.TOPIC_OID',
					   'from'	 => array( 'IP_F_FORUM_TOPIC' => 'ft' ),
					   'add_join' => array( array( 'select'	=> 't.IS_TOPIC_ARCHIVED',
					   							   'from'	=> array( 'IP_T_TOPIC' => 't'),
					   							   'where'	=> 't.TOPIC_OID = ft.TOPIC_OID',
					   							   'type'	=> 'left' ) ),
					   'where' => "ft.TOPIC_OID > {$lastTopic}", 
					   'order'   => "ft.TOPIC_OID ASC" );

		$loop = $this->lib->load('posts', FALSE, array());
		ipsRegistry::DB('hb')->build($main);
		$topicRes = ipsRegistry::DB('hb')->execute();
		
		//---------------------------
		// Loop
		//---------------------------
		while ( $topic = ipsRegistry::DB('hb')->fetch($topicRes) )
		{
			//print "<PRE>";print_r($topic);
			$doArchive = $topic['IS_TOPIC_ARCHIVED'] == 1 ? TRUE : FALSE;
			
			$joinTables = array( array( 'from' => array( 'IP_F_FORUM_TOPIC' => 't' ),
								 'where' => 'msg.TOPIC_OID = t.TOPIC_OID',
								 'type' => 'inner' ) );
								 
			if ( !$doArchive )
			{
				$joinTables = array_merge( $joinTables, array( array( 'select' => 'con.*',
											   'from' 	=> array( 'IP_C_CONTENT' => 'con' ),
											   'where' => " msg.MESSAGE_OID = con.CONTENT_OID",
											   'type'  => 'inner' ) ) );
			}
			
			ipsRegistry::DB('hb')->build( array( 'select' => 'msg.*',
												 'from'		=> array( 'IP_T'. ($doArchive ? '_ARCHIVED' : '') .'_MESSAGE' => 'msg'),
												   'add_join'   => $joinTables,
					   							   'where' => "msg.TOPIC_OID='{$topic['TOPIC_OID']}' AND msg.MESSAGE_OID > '{$lastId}'",
												   'order' => 'msg.MESSAGE_OID ASC' ) );
			$postRes = ipsRegistry::DB('hb')->execute();
			
			while ( $row = ipsRegistry::DB('hb')->fetch($postRes) )
			{
				//PRINT "<pre>";print_r($row);
				$data = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => $row['AUTHOR_OID'] == '' ? 'GUEST_USER_NAME' : 'DISPLAY_NAME', 'from' => $row['AUTHOR_OID'] == '' ? 'IP_C_GUEST_USER' : 'IP_USERS', 'where' => $row['AUTHOR_OID'] == '' ? "GUEST_USER_OID='{$row['GUEST_AUTHOR_OID']}'" : "USER_OID='{$row['AUTHOR_OID']}'" ) );
				$row = array_merge( $row, $data );			
			
				$save = array( 'topic_id'    => $row['TOPIC_OID'],
							   'author_id'	 => $row['AUTHOR_OID'] != '' ? $row['AUTHOR_OID'] : $row['GUEST_AUTHOR_OID'],
							   'author_name' => $row['AUTHOR_OID'] != '' ? $row['DISPLAY_NAME'] : $row['GUEST_USER_NAME'],
							   'post_date'	 => intval(strtotime($row['DATETIME_CREATED'])),
							   'post'		 => $this->fixPostData($row['BODY']),
							   'ip_address'	 => $row['POSTER_IP'],
							   'use_sig'	 => $row['HAS_SIGNATURE'],
							   'use_emo'	 => 1,
							   'queued'		 => $row['IS_MESSAGE_VISIBLE'] == 1 ? 0 : 1 );
				$this->lib->convertPost($row['MESSAGE_OID'], $save);
				$cycleCount++;

				// Check if we hit cycle limit.
				if ( $cycleCount >= $this->request['cycle'] )
				{
					$this->request['st'] += $cycleCount;
					$this->request['do'] = "posts&lastTopic={$topic['TOPIC_OID']}&lastId={$row['MESSAGE_OID']}";
					$this->lib->next();
				}
			}
			$lastId = 0;
		}

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
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_completed' => serialize($get), 'post_order_column' => 'post_date'));

		// Display
		$this->lib->load('posts', array('select'=>'*','from'=>'IP_F_FORUM_TOPIC','where'=>'0'));
	}

	/**
	 * Convert Posts
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_posts()
	{
		//$test = TRUE;
		$doArchive = $this->request['archive'] == 1 ? TRUE : FALSE;
		
		if ( $doArchive )
		{
			if ( $this->request['st'] == 0 )
			{
				$this->request['st'] = 1;
				$this->request['total'] = $this->countRows('posts') - $this->countRows('unarchivedposts');
			}
			
			$this->request['do'] = 'posts&archive=1';
		} else {
			$this->request['total'] = $this->countRows('unarchivedposts');
		}
		
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select'		=> 'msg.*',
					   'from'		=> array( 'IP_T'. ($doArchive ? '_ARCHIVED' : '') .'_MESSAGE' => 'msg'),
					   'add_join'   => array( array( 'from' => array( 'IP_F_FORUM_TOPIC' => 't' ),
					   								 'where' => 'msg.TOPIC_OID = t.TOPIC_OID',
					   								 'type' => 'inner' ) ) );

		if ( $doArchive )
		{
			$main['order'] = 'msg.DATETIME_CREATED ASC';
		} else {
			$main['add_join'] = array_merge( $main['add_join'], array( array( 'select' => 'con.*',
											   'from' 	=> array( 'IP_C_CONTENT' => 'con' ),
											   'where' => " msg.MESSAGE_OID = con.CONTENT_OID",
											   'type'  => 'inner' ) ) );
			$main['order'] = 'con.DATETIME_CREATED ASC';
			//$main['where'] = 'con.DATETIME_CREATED ASC';
		}
		//print '<PRE>';print_r($main);exit;
//ipsRegistry::DB('hb')->build($main);print ipsRegistry::DB('hb')->fetchSqlString();exit;
		$loop = $this->lib->load('posts', $main, array(), ($doArchive?array():'posts&archive=1'));
		
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$data = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => $row['AUTHOR_OID'] == '' ? 'GUEST_USER_NAME' : 'DISPLAY_NAME', 'from' => $row['AUTHOR_OID'] == '' ? 'IP_C_GUEST_USER' : 'IP_USERS', 'where' => $row['AUTHOR_OID'] == '' ? "GUEST_USER_OID='{$row['GUEST_AUTHOR_OID']}'" : "USER_OID='{$row['AUTHOR_OID']}'" ) );
			$row = array_merge( $row, $data );			
			
			$save = array( 'topic_id'    => intval($row['TOPIC_OID']),
						   'author_id'	 => $row['AUTHOR_OID'] != '' ? $row['AUTHOR_OID'] : $row['GUEST_AUTHOR_OID'],
						   'author_name' => $row['AUTHOR_OID'] != '' ? $row['DISPLAY_NAME'] : $row['GUEST_USER_NAME'],
						   'post_date'	 => intval(strtotime($row['DATETIME_CREATED'])),
						   'post'		 => $this->fixPostData($row['BODY']),
						   'ip_address'	 => $row['POSTER_IP'],
						   'use_sig'	 => $row['HAS_SIGNATURE'],
						   'use_emo'	 => 1,
						   'queued'		 => $row['IS_MESSAGE_VISIBLE'] == 1 ? 0 : 1 );
			$this->lib->convertPost($row['MESSAGE_OID'], $save);
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
		//IP_PT_PRIVATE_TOPIC -> FROM `IP_C_CONTENT` by CONTENT_OID

		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select'	 => 'pt.*',
					   'from'	 => array( 'IP_PT_PRIVATE_TOPIC' => 'pt' ),
					   'add_join' => array( array( 'select'	=> 't.SUBJECT, t.IS_TOPIC_CLOSED, t.IS_TOPIC_ARCHIVED',
					   							   'from'	=> array( 'IP_T_TOPIC' => 't'),
					   							   'where'	=> 'pt.PRIVATE_TOPIC_OID = t.TOPIC_OID',
					   							   'type'	=> 'left' ),
					   						array( 'select'	=> 's.PARTICIPANT_STATUS_DATE',
					   							   'from'	=> array( 'IP_PT_PRIVATE_TOPIC_PARTICIPANT' => 's'),
					   							   'where'	=> 's.PRIVATE_TOPIC_OID = pt.PRIVATE_TOPIC_OID AND s.USER_OID = pt.HOST_OID',
					   							   'type'	=> 'left' ) ),
					    'order'   => "s.PARTICIPANT_STATUS_DATE ASC" );

		$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));
					
		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//print "<PRE>";print_r($row);		
			//-----------------------------------------
			// And the maps
			//-----------------------------------------
			$maps = array();
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'IP_PT_PRIVATE_TOPIC_PARTICIPANT', 'where' => "PRIVATE_TOPIC_OID={$row['PRIVATE_TOPIC_OID']}"));
			$partRes = ipsRegistry::DB('hb')->execute();
			$users = array();
			while ($map = ipsRegistry::DB('hb')->fetch($partRes))
			{//print '<br /><br />Map:';print_r($map);
				$maps[] = array( 'map_topic_id'    => $row['PRIVATE_TOPIC_OID'],
								 'map_folder_id'   => 'myconvo',
								 'map_read_time'   => 0,
								 'map_user_active' => 1,
								 'map_user_banned' => 0,
								 'map_has_unread'  => 0,
								 'map_is_system'   => 0,
								 'map_user_id' => $map['USER_OID'],
								 'map_is_starter' => $map['USER_OID'] == $row['HOST_OID'] ? 1 : 0 );
				if ( $map['USER_OID'] != $row['HOST_OID'] )
				{
					$users[$map['USER_OID']] = $map['USER_OID'];
				}
			}
			//if ( count($maps) <= 1 ) { continue; }			
			
			//-----------------------------------------
			// Load the posts
			//-----------------------------------------
			$posts = array();
			ipsRegistry::DB('hb')->build( array( 'select' => 'msg.*',
												 'from' => array( 'IP_T_MESSAGE' => 'msg'),
												 'add_join' => array( array( 'select' => 'c.*',
						   													   'from' => array( 'IP_C_CONTENT' => 'c' ),
						   													   'where' => 'msg.MESSAGE_OID = c.CONTENT_OID',
						   													   'type' => 'left' ) ),
						   						 'where' => "msg.TOPIC_OID='{$row['PRIVATE_TOPIC_OID']}'",
						   						 'order' => 'msg.THREADING_ORDER ASC' ) );
			$postRes = ipsRegistry::DB('hb')->execute();
			$threadCount = 0;
			while ($post = ipsRegistry::DB('hb')->fetch($postRes))
			{//print '<br /><br />Post:';print_r($post);
				$threadCount++;
				$posts[] = array( 'msg_id'		  => $post['MESSAGE_OID'],
									   'msg_topic_id'      => $row['PRIVATE_TOPIC_OID'],
									   'msg_date'          => intval(strtotime($post['DATETIME_CREATED'])),
									   'msg_post'          => $this->fixPostData($post['BODY']),
									   'msg_post_key'      => md5(microtime()),
									   'msg_author_id'     => intval($post['AUTHOR_OID']),
									   'msg_is_first_post' => $threadCount == 1 ? 1 : 0 );
			}
			
			//-----------------------------------------
			// Map Data
			//-----------------------------------------
			$topic = array( 'mt_id'			     => $row['PRIVATE_TOPIC_OID'],
							'mt_date'		     => $posts[0]['msg_date'],
							'mt_title'		     => $row['SUBJECT'],
							'mt_starter_id'	     => intval($row['HOST_OID']),
							'mt_start_time'      => $posts[0]['msg_date'],
							'mt_last_post_time'  => $posts[ (count($posts) - 1) ]['msg_date'],
							'mt_invited_members' => serialize( $users ),
							'mt_to_count'		 => count($users),
							'mt_to_member_id'	 => current($users),
							'mt_replies'		 => count($posts) - 1,
							'mt_is_draft'		 => 0,
							'mt_is_deleted'		 => 0,
							'mt_is_system'		 => 0 );
//print_r($topic);print_r($posts);print_r($maps);print_r($users);exit;	
			//-----------------------------------------
			// And send it through
			//-----------------------------------------
			$this->lib->convertPM($topic, $posts, $maps);
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
		$main = array( 'select' => 'a.*',
					   'from'   => array( 'bb_attachments' => 'a' ),
					   'add_join' => array( array( 'select' => 'p.uid',
					   							   'from'  => array( 'bb_posts' => 'p' ),
					   							   'where' => 'a.post_id = p.post_id',
					   							   'type' => 'inner' ) ),
					   'order'  => 'a.attach_id ASC' );
					
		$loop = $this->lib->load('attachments', $main);
		
		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------
					
		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_board/uploads):')), 'path');
		
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
			$ext = explode('.', $row['name_disp']);
			$extension = array_pop( $ext );
			
			$image = in_array( $extension, array( 'png', 'jpg', 'jpeg', 'gif' ) ) ? TRUE : FALSE;
		
			// Sort out data
			$save = array( 'attach_rel_id'	   => $row['post_id'],
						   'attach_ext'		   => $extension,
						   'attach_file'	   => $row['filename'],
						   'attach_location'   => $row['name_disp'],
						   'attach_is_image'   => $image,
						   'attach_rel_module' => 'post',
						   'attach_member_id'  => $row['uid'],
						   'attach_hits'	   => $row['download'],
						   'attach_date'	   => $row['attach_time'] );
		// Send em on
		$this->lib->convertAttachment( $row['attach_id'], $save, $us['attach_path'] );
		}
		$this->lib->next();
	}
}
	
