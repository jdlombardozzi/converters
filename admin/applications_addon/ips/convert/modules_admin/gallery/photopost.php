<?php
/**
 * IPS Converters
 * IP.Gallery 3.0 Converters
 * Photopost
 * Last Update: $Date: 2011-07-29 13:03:04 +0100 (Fri, 29 Jul 2011) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 568 $
 */


	$info = array(
		'key'	=> 'photopost',
		'name'	=> 'Photopost 6.2',
		'login'	=> false,
	);

	//TODO:See if this is the one which added XF support, and update appropriately.
	$parent = array('required' => false, 'choices' => array(
		array('app' => 'board', 'key' => 'vbulletin_legacy', 'newdb' => true),
		array('app' => 'board', 'key' => 'vbulletin', 'newdb' => true),
		array('app' => 'board', 'key' => 'phpbb', 'newdb' => true),
		array('app' => 'board', 'key' => 'ipboard', 'newdb' => true),
		array('app' => 'board', 'key' => 'smf', 'newdb' => true),
		array('app' => 'board', 'key' => 'smf_legacy', 'newdb' => true),
		array('app' => 'board', 'key' => 'mybb', 'newdb' => true),
		));

	class admin_convert_gallery_photopost extends ipsCommand
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
			$this->registry = $registry;
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
				//'gallery_categories'	=> array('members'),
				'gallery_albums'		=> array('members'),
				'gallery_images'		=> array('members', 'gallery_albums'),
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

			if (array_key_exists($this->request['do'], $this->actions) || $this->request['do'] == 'albums2' )
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
					return $this->lib->countRows('users');
					break;

				/*case 'gallery_categories':
					return $this->lib->countRows('categories', "cattype='c'");
					break;*/

				case 'gallery_albums':
					return $this->lib->countRows('categories');
					break;

				case 'gallery_images':
					return $this->lib->countRows('photos');
					break;

				case 'gallery_comments':
				 	return $this->lib->countRows('comments');
				 	break;

				 /*case 'gallery_ecardlog':
				 	return $this->lib->countRows('ecards');
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
					$profile['photo_type'] = 'custom';
					$profile['pp_main_photo'] = $row['avatar'];
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
		 **
		private function convert_gallery_categories()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'categories',
							'where'		=> "cattype='c'",
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_categories', $main);

			//-----------------------------------------
			// Get remote groups
			//-----------------------------------------

			$groups = array();
			ipsRegistry::DB('hb')->build( array( 'select' => '*', 'from' => 'usergroups' ) );
			ipsRegistry::DB('hb')->execute();
			while ( $row = ipsRegistry::DB('hb')->fetch() )
			{
				$groups[] = $row['groupid'];
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$perms = array();
				$perms['album_g_perms_view']		= $this->_populatePerms($row, $groups, 'view');
				$perms['album_g_perms_images']		= $this->_populatePerms($row, $groups, 'upload');
				$perms['album_g_perms_comments']	= $this->_populatePerms($row, $groups, 'comment');
				//$perms['album_g_perms_moderate']	= $this->_populatePerms ( $row, $groups, 'moderate' );

				if ( $row['intro'] == 'yes' )
				{
					$rules = serialize ( array (
						'title'	=> $row['introtitle'],
						'text'	=> $row['introcopy'],
					) );
				}

				$save = array(
					'album_parent_id'			=> $row['parent'],
					'album_name'				=> $row['catname'],
					'album_description'			=> $row['description'],
					'album_g_password'			=> $row['password'],
					'album_g_rules'				=> $rules,
					'album_is_global'			=> 1,
					'album_g_container_only'	=> 1,
				);

				$this->lib->convertAlbum($row['id'], $save, array ( ), true);
			}

			$this->lib->next();

		}*/

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

			$this->lib->saveMoreInfo('gallery_albums', array('orphans'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'categories',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_albums', $main);

			//-----------------------------------------
			// We need to know how to handle orphans
			//-----------------------------------------

			$cats = array();
			$this->DB->build(array('select' => '*', 'from' => 'gallery_albums_main', 'album_g_container_only = 1'));
			$this->DB->execute();
			while ($r = $this->DB->fetch())
			{
				$cats[$r['album_id']] = $r['album_name'];
			}

			$this->lib->getMoreInfo('gallery_albums', $loop, array('orphans' => array('type' => 'dropdown', 'label' => 'To which category do you wish to put albums?', 'options' => $cats)));

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			
			//-----------------------------------------
			// Get remote groups
			//-----------------------------------------

			$groups = array();
			ipsRegistry::DB('hb')->build( array( 'select' => '*', 'from' => 'usergroups' ) );
			$gp = ipsRegistry::DB('hb')->execute();
			while ( $row = ipsRegistry::DB('hb')->fetch($gp) )
			{
				$groups[] = $row['groupid'];
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$perms = array();
				$perms['album_g_perms_view']		= $this->_populatePerms($row, $groups, 'view');
				$perms['album_g_perms_images']		= $this->_populatePerms($row, $groups, 'upload');
				$perms['album_g_perms_comments']	= $this->_populatePerms($row, $groups, 'comment');
				//$perms['album_g_perms_moderate']	= $this->_populatePerms ( $row, $groups, 'moderate' );

				if ( $row['intro'] == 'yes' )
				{
					$rules = serialize ( array (
						'title'	=> $row['introtitle'],
						'text'	=> $row['introcopy'],
					) );
				}

				$save = array(
					'album_parent_id'			=> ( $row['parent'] ? $row['parent'] : $us['orphans'] ),
					'album_name'				=> $row['catname'],
					'album_description'			=> $row['description'],
					'album_g_password'			=> $row['password'],
					'album_g_rules'				=> $rules,
					'album_is_global'			=> ( $row['cattype'] == 'c' ? 1 : 0 ),
					'album_g_container_only'	=> 0,
                    'album_owner_id'            => $row['catuserid'],
                    'album_is_public'           => ($row['private'] == 'yes') ? 0 : 1,
				);
				
				$save = array_merge( $save, $perms );
                
				$this->lib->convertAlbum($row['id'], $save, array ( ), true);
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

			$main = array(	'select' 	=> '*',
							'from' 		=> 'photos',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_images', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('gallery_images', $loop, array('gallery_path' => array('type' => 'text', 'label' => 'The path to the folder where images are saved (no trailing slash - usually path_to_photopost/data):')), 'path');

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

				// Have a stab at the mimetype
				$explode = explode('.', $row['filename']);
				$ext = strtolower(array_pop($explode));
				$ext = ($ext == 'jpg') ? 'jpeg' : $ext;
				$mime = "image/{$ext}";

				// Basic info
				$save = array(
					'member_id'			=> $row['userid'],
					'img_album_id'		=> $row['cat'],
					'caption'			=> $row['title'],
					'description'		=> $row['description'],
					'directory'			=> $row['cat'],
					'file_name'			=> $row['bigimage'],
					'file_size'			=> $row['filesize'],
					'file_type'			=> $mime,
					'approved'			=> $row['approved'],
					'views'				=> $row['views'],
					'comments'			=> $row['numcom'],
					'idate'				=> $row['date'],
					'ratings_total'		=> $row['rating'] * $row['votes'],
					'ratings_count'		=> $row['votes'],
					'rating'			=> $row['rating'],
					);

				// Go!
				$this->lib->convertImage($row['id'], $save, $path);
				
				//Photopost 7?
				//$this->lib->convertImage($row['id'], $save, $path . '/' . $row['cat'], array());

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
							'from' 		=> 'comments',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_comments', $main);


			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'img_id'			=> $row['photo'],
					'author_name'		=> $row['username'],
					'comment'			=> $this->fixPostData($row['comment']),
					'post_date'			=> $row['date'],
					'ip_address'		=> $row['ipaddress'],
					'author_id'			=> $row['userid'],
					'approved'			=> $row['approved'],
					);

				$this->lib->convertComment($row['id'], $save);
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
		/*private function convert_gallery_ecardlog()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'ecards',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_ecardlog', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$user = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'users', 'where' => "username='{$row['fromname']}'" ) );

				$save = array(
					'img_id'		=> $row['photoid'],
					'edate'			=> $row['date'],
					'member_id'		=> $user['userid'],
					'receiver_name'	=> $row['toname'],
					'title'			=> $row['subject'],
					'msg'			=> $row['message'],
					'bg'			=> $row['backcolor'],
					'font'			=> $row['fontcolor'],
					'border'		=> $row['bordcolor'],
					);

				$this->lib->convertECard($row['id'], $save);
			}

			$this->lib->next();

		}*/

	}

