<?php
/**
 * IPS Converters
 * IP.Gallery 3.0 Converters
 * Photopost
 * Last Update: $Date: 2011-07-12 21:15:48 +0100 (Tue, 12 Jul 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 550 $
 */


	$info = array(
		'key'	=> 'photoplog',
		'name'	=> 'PhotoPlog 2.X',
		'login'	=> false );

	$parent = array('required' => false, 'choices' => array(
		array('app' => 'board', 'key' => 'vbulletin_legacy', 'newdb' => true),
		array('app' => 'board', 'key' => 'vbulletin', 'newdb' => true) ));

	class admin_convert_gallery_photoplog extends ipsCommand
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

			$forAll = array( 'gallery_albums'	=> array('members'),
							'gallery_images'	=> array('members', 'gallery_albums'),
							'gallery_comments'	=> array('members', 'gallery_images') );

			$this->actions = array_merge($forSome, $forAll);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_gallery.php' );
			$this->lib =  new lib_gallery( $this->registry, $html, $this, $useLocal );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'PhotoPlog &rarr; IP.Gallery Converter' );

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
				//case 'forum_perms':
				//case 'groups':
				//	return $this->lib->countRows('usergroups');
				//	break;

				case 'members':
					return $this->lib->countRows('users');
					break;

				case 'gallery_albums':
					return $this->lib->countRows('categories');
					break;

				//case 'gallery_albums':
				//	return $this->lib->countRows('useralbums');
					//break;

				case 'gallery_images':
					return $this->lib->countRows('fileuploads');
					break;

				case 'gallery_comments':
				 	return $this->lib->countRows('ratecomment');
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
		 **/
		private function convert_gallery_albums()
		{
			$this->lib->saveMoreInfo ( 'gallery_albums', array ( 'container_album' ) );
			
			//---------------------------
			// Set up
			//---------------------------
			$main = array(	'select' 	=> '*',
							'from' 		=> 'categories',
							'order'		=> 'catid ASC' );

			$loop = $this->lib->load('gallery_albums', $main);

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
			
			$this->lib->getMoreInfo ( 'gallery_albums', $loop, array (
				'container_album' => array (
					'type'		=> 'dropdown',
					'label' 	=> 'The Global Album to store all Member Albums in:',
					'options'	=> $options,
				)
			), 'container_album' );

			$get	= unserialize ( $this->settings['conv_extra'] );
			$us		= $get[$this->lib->app['name']];

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$perms = array();

				$save = array(
					'album_parent_id'			=> $row['parentid'] == -1 ? 0 : $row['parentid'],
					'album_name'				=> $row['title'],
					'album_description'			=> $row['description'],
					'album_g_container_only'	=> $row['parentid'] == -1 ? 1 : 0,
					'album_is_global' 			=> ( $row['parentid'] == -1 ? 1 : 0 ),
					);

				$this->lib->convertAlbum($row['catid'], $save, $us);
			}
			$this->lib->next();
		}

		/**
		 * Convert Albums
		 *
		 * @deprecated As of IP.Gallery 4, wasn't even used anyway.
		 * @access	private
		 * @return void
		 **/
		private function convert_gallery_albums_deprecated()
		{
			//photoplog albums are simply user grouped images of existing images.

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------
			$this->lib->saveMoreInfo('gallery_albums', array('orphans'));

			//---------------------------
			// Set up
			//---------------------------
			$main = array(	'select' 	=> '*',
							'from' 		=> 'useralbums',
							'order'		=> 'albumid ASC' );

			$loop = $this->lib->load('gallery_albums', $main);

			//-----------------------------------------
			// We need to know how to handle orphans
			//-----------------------------------------
			$cats = array();
			$this->DB->build(array('select' => '*', 'from' => 'gallery_albums', 'where' => 'album_g_container_only = 1'));
			$this->DB->execute();
			while ($r = $this->DB->fetch())
			{
				$cats[$r['id']] = $r['name'];
			}

			$this->lib->getMoreInfo('gallery_albums', $loop, array('orphans' => array('type' => 'dropdown', 'label' => 'To which category do you wish to put albums?', 'options' => $cats)));

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array( 'member_id'			=> $row['userid'],
								'public_album'		=> $row['visible'],
								'name'				=> $row['title'],
								'description' => $row['description'],
								//'images'			=> $row['photos'],
								//'comments'			=> $row['posts'],
								'category_id'		=> $us['orphans'] );

				$this->lib->convertAlbum($row['albumid'], $save, true);
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
							'from' 		=> 'fileuploads',
							'order'		=> 'fileid ASC' );

			$loop = $this->lib->load('gallery_images', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('gallery_images', $loop, array('gallery_path' => array('type' => 'text', 'label' => 'The path to the folder where images are saved (no trailing slash - usually path_to_photoplog/images):')), 'path');

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
					'img_album_id'		=> $row['catid'],
					'caption'			=> $row['title'],
					'description'		=> $row['description'],
					'directory'			=> $row['userid'],
					'file_name'			=> $row['filename'],
					'file_size'			=> $row['filesize'],
					'file_type'			=> $mime,
					'approved'			=> $row['moderate'] == 1 ? 0 : 1,
					'views'				=> $row['views'],
					'comments'			=> $row['num_comments0'] + $row['num_comments1'],
					'idate'				=> $row['dateline'],
					'ratings_total'		=> $row['sum_ratings1'],
					'ratings_count'		=> $row['num_ratings1'],
					'rating'			=> $row['num_ratings1'] > 0 ? intval($row['sum_ratings1'] / $row['num_ratings1']) : 0,
					);

				// Go!
				$this->lib->convertImage($row['fileid'], $save, $path);

				//-----------------------------------------
				// Ratings
				//-----------------------------------------

				$rates = array(	'select' 	=> '*',
								'from' 		=> 'ratecomment',
								'order'		=> 'commentid ASC',
								'where'		=> 'fileid='.$row['fileid'].' AND rating=0' );

				ipsRegistry::DB('hb')->build($rates);
				ipsRegistry::DB('hb')->execute();
				while ($rate = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertRating($rate['commentid'], array( 'member_id' => $row['userid'],
																		 'img_id' => $row['fileid'],
																		 'date' => $row['dateline'],
																		 'rate' => $row['rating'] ) );
				}
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
							'from' 		=> 'ratecomment',
							'where' => 'rating=0',
							'order'		=> 'commentid ASC' );

			$loop = $this->lib->load('gallery_comments', $main);

			//---------------------------
			// Loop
			//---------------------------
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'img_id'			=> $row['fileid'],
					'author_name'		=> $row['username'],
					'comment'			=> $this->fixPostData($row['comment']),
					'post_date'			=> $row['dateline'],
					//'ip_address'		=> $row['ipaddress'],
					'author_id'			=> $row['userid'],
					'approved'			=> $row['moderate'] == 1 ? 0 : 1 );

				$this->lib->convertComment($row['commentid'], $save);
			}
			$this->lib->next();
		}
	}

