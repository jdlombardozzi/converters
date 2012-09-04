<?php
/**
 * IPS Converters
 * IP.Gallery 3.0 Converters
 * 4images
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
		'key'	=> '4images',
		'name'	=> '4images 1.7',
		'login'	=> false,
	);

	class admin_convert_gallery_4images extends ipsCommand
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

			$this->actions = array(
				'members'			=> array(),
				'gallery_albums'	=> array('members'),
				'gallery_images'	=> array('members', 'gallery_albums'),
				'gallery_comments'	=> array('members', 'gallery_images'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_gallery.php' );
			$this->lib =  new lib_gallery( $this->registry, $html, $this, false );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( '4images &rarr; IP.Gallery Converter' );

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
				case 'members':
					return $this->lib->countRows('users', 'user_id > 0');
					break;

				case 'gallery_albums':
					return $this->lib->countRows('categories');
					break;

				case 'gallery_images':
					return $this->lib->countRows('images');
					break;

				case 'gallery_comments':
					return $this->lib->countRows('comments');
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
				'user_icq'		=> 'ICQ Number',
				'user_homepage'	=> 'Website',
				);

			$this->DB->build( array( 'select' => '*', 'from' => 'groups' ) );
			$this->DB->execute();
			while($row = $this->DB->fetch())
			{
				$groups[ $row['g_id'] ] = $row['g_title'];
			}

			$this->lib->saveMoreInfo('members', array_merge( array_keys($pcpf), array('g1', 'g2', 'g9') ) );

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'users',
							'where'		=> 'user_id > 0',
							'order'		=> 'user_id ASC',
						);

			$loop = $this->lib->load('members', $main);

			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();

			$ask['g1'] = array('type' => 'dropdown', 'label' => 'Group for <em>Registered Users (not activated)</em>', 'options' => $groups );
			$ask['g2'] = array('type' => 'dropdown', 'label' => 'Group for <em>Registered Users</em>', 'options' => $groups );
			$ask['g9'] = array('type' => 'dropdown', 'label' => 'Group for <em>Administrators</em>', 'options' => $groups );

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
					'id'				=> $row['user_id'],
					'group'				=> $us[ 'g' . $row['user_level'] ],
					'joined'			=> $row['user_joindate'],
					'username'			=> $row['user_name'],
					'email'				=> $row['user_email'],
					'md5pass'			=> $row['user_password'],
					);

				$members = array(
					'hide_email'		=> $row['user_showemail'] ? 0 : 1,
					'allow_admin_mails'	=> $row['user_allowemails'],
					'last_activity'		=> $row['user_lastaction'],
					'last_visit'		=> $row['user_lastvisit'],
					);

				$profile = array();

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

				$this->lib->convertMember($info, $members, $profile, $custom, '', FALSE);

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
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'categories',
							'order'		=> 'cat_id ASC',
						);

			$loop = $this->lib->load('gallery_albums', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array (
					'album_owner_id'	=> 0,
					'album_name'		=> $row['cat_name'],
					'album_description'	=> $row['cat_description'],
					'album_parent_id'	=> $row['cat_parent_id'],
					'album_is_global'	=> 1, // Since categories are no more, let's just convert them to global albums.
				);
				
				$this->lib->convertAlbum ( $row['cat_id'], $save, NULL, TRUE );
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
							'from' 		=> 'images',
							'order'		=> 'image_id ASC',
						);

			$loop = $this->lib->load('gallery_images', $main, array());

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('gallery_images', $loop, array('gallery_path' => array('type' => 'text', 'label' => 'The path to the folder where images are saved (no trailing slash - usually path_to_4images/data/media):')), 'path');

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
				//---------------------------
				// Skip URL ones
				//---------------------------

				if ( substr( $row['image_media_file'], 0, 7 ) == 'http://' or substr( $row['image_media_file'], 0, 8 ) == 'https://' )
				{
					continue;
				}

				//-----------------------------------------
				// Do the image
				//-----------------------------------------

				// Have a stab at the mimetype
				$explode = explode('.', $row['image_media_file']);
				$ext = strtolower(array_pop($explode));
				$ext = ($ext == 'jpg') ? 'jpeg' : $ext;
				$mime = "image/{$ext}";
				
				$media = 1;
				if ( preg_match ( '/image/', $mime ) )
				{
					$media = 0;
				}

				// Basic info
				$save = array(
					'member_id'			=> $row['user_id'],
					'img_album_id'		=> $row['cat_id'],
					'caption'			=> $row['image_name'],
					'description'		=> $row['image_description'],
					//'directory'			=> 'gallery'$row['cat_id'],
					'file_name'			=> $row['image_media_file'],
					'file_type'			=> $mime,
					'approved'			=> $row['image_active'],
					'views'				=> $row['image_hits'],
					'comments'			=> $row['image_comments'],
					'idate'				=> $row['image_date'],
					'ratings_total'		=> $row['image_rating'] * $row['image_votes'],
					'ratings_count'		=> $row['image_votes'],
					'rating'			=> $row['image_rating'],
					'masked_file_name'	=> $row['cat_id'] . '/' . $row['image_media_file'],
					'media'				=> $media,
					);

				// Go!
				$this->lib->convertImage($row['image_id'], $save, $path);

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
							'order'		=> 'comment_id ASC',
						);

			$loop = $this->lib->load('gallery_comments', $main);


			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'img_id'			=> $row['image_id'],
					'author_name'		=> $row['user_name'],
					'comment'			=> $row['comment_text'],
					'post_date'			=> $row['comment_date'],
					'ip_address'		=> $row['comment_ip'],
					'author_id'			=> $row['user_id'],
					'approved'			=> 1,
					);

				$this->lib->convertComment($row['comment_id'], $save);
			}

			$this->lib->next();

		}

	}