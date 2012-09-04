<?php
/**
 * IPS Converters
 * IP.Gallery 3.0 Converters
 * Photopost
 * Last Update: $Date: 2011-06-08 12:44:41 -0400 (Wed, 08 Jun 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 529 $
 */

$info = array( 'key'	=> 'photopostvbgallery',
			   'name'	=> 'Photopost vBGallery 3.0',
			   'login'	=> false );

// Check for XF
$parent = array( 'required' => false,
				 'choices' => array( array('app' => 'board', 'key' => 'vbulletin_legacy', 'newdb' => true),
				 					 array('app' => 'board', 'key' => 'vbulletin', 'newdb' => true),
				 					 array('app' => 'board', 'key' => 'phpbb', 'newdb' => true),
				 					 array('app' => 'board', 'key' => 'ipboard', 'newdb' => true),
				 					 array('app' => 'board', 'key' => 'smf', 'newdb' => true),
				 					 array('app' => 'board', 'key' => 'smf_legacy', 'newdb' => true),
				 					 array('app' => 'board', 'key' => 'mybb', 'newdb' => true) ) );
	
class admin_convert_gallery_photopostvbgallery extends ipsCommand
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
		
		$app = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'conv_apps', 'where' => "name='{$this->settings['conv_current']}'" ) );
		
		$forSome = array();
		$useLocal = true;
		
		if(!$app['parent'])
		{
			$useLocal = false;
			$forSome = array(
				'forum_perms'			=> array(),
				'groups' 				=> array('forum_perms'),
				'members'				=> array('groups'),
			);
		}
		
		$forAll = array(
			'gallery_categories'	=> array('members'),
			'gallery_albums'		=> array('members', 'gallery_categories'),
			'gallery_images'		=> array('members', 'gallery_categories', 'gallery_albums'),
			'gallery_comments'		=> array('members', 'gallery_images'),
			//'gallery_ecardlog'		=> array('gallery_images', 'members'),
			);
		
		$this->actions = array_merge($forSome, $forAll);
				
		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_gallery.php' );
		$this->lib =  new lib_gallery( $this->registry, $html, $this, $useLocal );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'Photopost &rarr; IP.Gallery Converter' );

		//-----------------------------------------
		// Are we connected?
		// (in the great circle of life...)
		//-----------------------------------------
		
		$this->HB = $this->lib->connect();

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
				return $this->lib->countRows('ppgal_usergroups');
				break;
			
			case 'members':
				return $this->lib->countRows('ppgal_users');
				break;
									
			case 'gallery_categories':
				return $this->lib->countRows('ppgal_categories', "membercat='0'");
				break;
				
			case 'gallery_albums':
				return $this->lib->countRows('ppgal_categories', "membercat='1'");
				break;
				
			case 'gallery_images':
				return $this->lib->countRows('ppgal_images');
				break;
					
			case 'gallery_comments':
				return $this->lib->countRows('ppgal_posts');
				break;
				
			 /*case 'gallery_ecardlog':
				return $this->lib->countRows('ppgal_ecards');
				break;*/
				
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
			case 'gallery_albums':
			case 'gallery_images':
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
						'from' 		=> 'usergroups',
						'order'		=> 'groupid ASC',
					);
					
		$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
					
		$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'groupid', 'nf' => 'groupname'));

		//---------------------------
		// Loop
		//---------------------------
		
		foreach( $loop as $row )
		{
			$this->lib->convertPermSet($row['groupid'], $row['groupname']);			
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
						'from' 		=> 'usergroups',
						'order'		=> 'groupid ASC',
					);
					
		$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

		//-----------------------------------------
		// We need to know how to map these
		//-----------------------------------------
					
		$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'groupid', 'nf' => 'groupname'));

		//---------------------------
		// Loop
		//---------------------------
		
		foreach( $loop as $row )
		{
			$save = array(
				'g_title'			=> $row['groupname'],
				'g_access_cp'		=> $row['cpaccess'],
				'g_is_supmod'		=> $row['modaccess'],
				'g_max_diskspace'	=> $row['diskspace'],
				'g_max_upload'		=> $row['uploadsize'],
				'g_edit_own'		=> $row['editpho'],
				'g_create_albums'	=> $row['useralbums'],
				'g_perm_id'			=> $row['groupid'],
				);
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
		
		$pcpf = array(
			'icq'			=> 'ICQ Number',
			'aim'			=> 'AIM ID',
			'yahoo'			=> 'Yahoo ID',
			'homepage'		=> 'Website',
			'location'		=> 'Location',
			'interests'		=> 'Interests',
			'occupation'	=> 'Occupation',
			);
		
		$this->lib->saveMoreInfo('members', array_keys($pcpf));
		
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'users',
						'order'		=> 'userid ASC',
					);

		$loop = $this->lib->load('members', $main);
		
		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
		
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();
		
		// We need to know the avatars path
		$ask['avvy_path'] = array('type' => 'text', 'label' => 'The path to the folder where avatars are saved (no trailing slash - usually /path_to_photopost/data/avatars):');
						
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

			$info = array(
				'id'				=> $row['userid'],
				'group'				=> $row['usergroupid'],
				'joined'			=> $row['joindate'],
				'username'			=> $row['username'],
				'email'				=> $row['email'],
				'md5pass'			=> $row['password'],
				);
			
			$birthday = ($row['birthday']) ? explode('-', $row['birthday']) : null;
			$members = array(
				'bday_day'			=> ($row['birthday']) ? $birthday[2] : '',
				'bday_month'		=> ($row['birthday']) ? $birthday[1] : '',
				'bday_year'			=> ($row['birthday']) ? $birthday[0] : '',
				'ip_address'		=> $row['ipaddress'],
				'time_offset'		=> $row['offset'],
				'title'				=> $row['title'],
				'last_visit'		=> $row['laston'],
				);
				
			$profile = array(
				'pp_about_me'		=> $this->fixPostData($row['bio']),
				'signature'			=> $row['signature'],
				);
			
			//-----------------------------------------
			// Avatars
			//-----------------------------------------
			
			$path = '';
			
			if ($row['avatar'])
			{
				$profile['avatar_type'] = 'upload';
				$profile['avatar_location'] = $row['avatar'];
				$path = $us['avvy_path'];
			}
			
			//-----------------------------------------
			// Custom Profile fields
			//-----------------------------------------
			
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
	private function convert_gallery_categories()
	{
		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> '*',
						'from' 		=> 'ppgal_categories',
						'where' => "membercat='0'",
						'order'		=> 'catid ASC',
					);
										
		$loop = $this->lib->load('gallery_categories', $main);
		
		//-----------------------------------------
		// Get remote groups
		//-----------------------------------------
	/*	$groups = array();
		ipsRegistry::DB('hb')->build( array( 'select' => '*', 'from' => 'usergroups' ) );
		ipsRegistry::DB('hb')->execute();
		while ( $row = ipsRegistry::DB('hb')->fetch() )
		{
			$groups[] = $row['groupid'];
		}*/
		
		//---------------------------
		// Loop
		//---------------------------
	
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$perms = array();
			//$perms['view_thumbnails']	= $this->_populatePerms($row, $groups, 'view');
			//$perms['view_images']		= $this->_populatePerms($row, $groups, 'view');
			//$perms['post_images']		= $this->_populatePerms($row, $groups, 'upload');
			//$perms['comment']			= $this->_populatePerms($row, $groups, 'comment');
			
			$save = array(
				'album_parent_id'			=> $row['parent'],
				'album_name'				=> $row['title'],
				'album_description'			=> $row['description'],
				'password'					=> $row['password'],
				'album_g_container_only'	=> ($row['parent'] == 0 ? 1 : 0),
				);
			
			$this->lib->convertAlbum($row['catid'], $save, array ( ), true);			
		}
	
		$this->lib->next();

	}
	
	/**
	 * Work out the permissions column
	 *
	 * @param 	array		row from lib_master::load()
	 * @param 	groups		remote usergroups data
	 * @param 	string		action (e.g. 'view', 'upload', etc.)
	 * @return 	null
	 **/
	private function _populatePerms($row, $groups, $type)
	{
		$refuse = array();
		switch ($type)
		{
			case 'view':
				$refuse = explode(',', $row['ugnoview']);
				break;
				
			case 'upload':
				$refuse = explode(',', $row['ugnoupload']);
				break;
					
			case 'comment':
				$refuse = explode(',', $row['ugnopost']);
				break;
		}
		
		$allow = array();
		foreach($groups as $g)
		{
			if ( ! in_array($g, $refuse) )
			{
				$allow[] = $g;
			}
		}
		
		return implode(',', $allow);
	}
	
	/**
	 * Convert Albums
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_gallery_albums()
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		
		$this->lib->saveMoreInfo('gallery_albums', array('container_album'));

		//---------------------------
		// Set up
		//---------------------------
	
		$main = array(	'select' 	=> '*',
						'from' 		=> 'ppgal_categories',
						'where'		=> "membercat='1'",
						'order'		=> 'catid ASC',
					);
				
		$loop = $this->lib->load('gallery_albums', $main);
		
		//-----------------------------------------
		// We need to know how to handle orphans
		//-----------------------------------------
		
		$options = array ( );
		$this->DB->build ( array (
			'select'	=> '*',
			'from'		=> 'gallery_albums_main',
			'where'		=> 'album_is_global = 1',
		) );
		$albumRes = $this->DB->execute ( );
		while ( $row = $this->DB->fetch ( $albumRes ) )
		{
			$options[$row['album_id']]	= $row['album_name'];
		}
			
		if ( count ( $options ) < 1 )
		{
			$this->lib->error ( 'You need at least one Global Album before you may continue.' );
		}

		$this->lib->getMoreInfo('gallery_albums', $loop, array('container_album' => array('type' => 'dropdown', 'label' => 'The Global Album to store all Member Albums in:', 'options' => $cats)));
		
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		
		//---------------------------
		// Loop
		//---------------------------
	
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'album_owner_id'	=> $row['catuserid'],
				'album_is_public'	=> 1,
				'album_name'		=> $row['title'],
				'album_parent_id'	=> $row['parent'] == 0 ? $us['container_album'] : $row['parent'],
				);
			
			$this->lib->convertAlbum($row['catid'], $save, array ( ), true);			
		}
	
		$this->lib->next();

	}
	
	/**
	 * Convert Images
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_gallery_images()
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$this->lib->saveMoreInfo('gallery_images', array('gallery_path'));

		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => '*',
					   'from'   => 'ppgal_images',
					   'order'	=> 'imageid ASC' );
				
		$loop = $this->lib->load('gallery_images', $main);
		
		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------
		$this->lib->getMoreInfo('gallery_images', $loop, array('gallery_path' => array('type' => 'text', 'label' => 'The path to the folder where images are saved (no trailing slash - usually path_to_photopost/files):')), 'path');
		
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$path = $us['gallery_path'];

		//-----------------------------------------
		// Check all is well
		//-----------------------------------------
		if (!is_writable($this->settings['gallery_images_path']))
		{
			$this->lib->error('Your IP.Gallery upload path is not writeable. '.$this->settings['gallery_images_path']);
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
			//-----------------------------------------
			// Do the image
			//-----------------------------------------
			$row['extension'] = ($row['extension'] == 'jpg') ? 'jpeg' : $row['extension'];
			$mime = "image/{$row['extension']}";
							
			// Basic info
			$save = array( 'member_id'			=> $row['userid'],
						   'img_album_id'			=> ( $this->lib->getLink($row['catid'], 'gallery_albums', true) ) ? $row['catid'] : 0,
						   'caption'			=> $row['title'],
						   'description'		=> $row['description'],
						   'directory'			=> $row['catid'],
						   'file_name'			=> $row['filename'],
						   'file_size'			=> $row['filesize'],
						   'file_type'			=> $mime,
						   'approved'			=> $row['open'],
						   'views'		   => $row['views'],
						   'comments'	   => $row['posts'],
						   'idate'		   => $row['dateline'],
						   'ratings_total' => $row['votetotal'],
						   'ratings_count' => $row['votenum'],
						   'rating'				=> $row['votetotal'] > 0 ? $row['votetotal'] / $row['votenum'] : '' );

			$tmpPath = '/' . implode('/', preg_split('//', $row['userid'],  -1, PREG_SPLIT_NO_EMPTY));
												
			// Go!				
			$this->lib->convertImage($row['imageid'], $save, $path . $tmpPath);
		}
		$this->lib->next();
	}
	
	/**
	 * Convert Comments
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_gallery_comments()
	{

		//---------------------------
		// Set up
		//---------------------------
	
		$main = array(	'select' 	=> '*',
						'from' 		=> 'ppgal_posts',
						'order'		=> 'postid ASC',
					);
				
		$loop = $this->lib->load('gallery_comments', $main);
		
	
		//---------------------------
		// Loop
		//---------------------------
	
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'img_id'			=> $row['imageid'],
				'author_name'		=> $row['username'],
				'comment'			=> $this->fixPostData($row['pagetext']),
				'post_date'			=> $row['dateline'],
				'ip_address'		=> $row['ipaddress'],
				'author_id'			=> $row['userid'],
				'approved'			=> $row['visible'],
				);
			
			$this->lib->convertComment($row['postid'], $save);							
		}
	
		$this->lib->next();

	}
	
			
	/**
	 * Convert eCard Logs
	 *
	 * @deprecated
	 * @access	private
	 * @return void
	 **/
	private function convert_gallery_ecardlog()
	{

		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> '*',
						'from' 		=> 'ppgal_ecards',
						'order'		=> 'cardid ASC',
					);
				
		$loop = $this->lib->load('gallery_ecardlog', $main);
	
		//---------------------------
		// Loop
		//---------------------------
	
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$user = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'users', 'where' => "username='{$row['fromname']}'" ) );
		
			$save = array(
				'img_id'		=> $row['imageid'],
				'edate'			=> $row['dateline'],
				'member_id'		=> $row['fromuserid'],
				'receiver_name'	=> $row['tousername'],
				'title'			=> $row['title'],
				'msg'			=> $row['message'],
				'bg'			=> $row['bgcolors']
				);
			
			$this->lib->convertECard($row['cardid'], $save);			
		}
		$this->lib->next();
	}
}