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
		'key'	=> 'zenphoto',
		'name'	=> 'ZenPhoto 1.4',
		'login'	=> false,
	);

	class admin_convert_gallery_zenphoto extends ipsCommand
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
			$this->lib->sendHeader( 'ZenPhoto &rarr; IP.Gallery Converter' );

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
					return $this->lib->countRows('administrators', 'valid=1');
					break;

				case 'gallery_albums':
					return $this->lib->countRows('albums');
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

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'administrators',
							'where'		=> 'valid = 1',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('members', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				//-----------------------------------------
				// Set info
				//-----------------------------------------

				$info = array(
					'id'				=> $row['id'],
					'group'				=> $this->settings['member_group'],
					'joined'			=> time(),
					'username'			=> $row['user'],
					'email'				=> $row['email'],
					'md5pass'			=> $row['pass'],
					);

				$members = array();
				$profile = array();
				$custom = array();

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
							'from' 		=> 'albums',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_albums', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'album_name'		=> $row['title'],
					'album_parent_id'	=> $row['parentid'],
					'album_description'	=> $row['desc'],
					'album_is_global'	=> 1,
				);

				$this->lib->convertAlbum($row['id'], $save, array(), true);
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
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_images', $main, array());

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('gallery_images', $loop, array('gallery_path' => array('type' => 'text', 'label' => 'The path to the folder where images are saved (no trailing slash - usually path_to_zenphoto/albums):')), 'path');

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
				// Get album
				$album = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'albums', 'where' => 'id='.$row['albumid'] ) );

				// Have a stab at the mimetype
				$explode = explode('.', $row['image_media_file']);
				$ext = strtolower(array_pop($explode));
				$ext = ($ext == 'jpg') ? 'jpeg' : $ext;
				$mime = "image/{$ext}";

				// Basic info
				$save = array(
					'member_id'			=> '6',
					'img_album_id'		=> $row['albumid'],
					'caption'			=> $row['title'],
					'description'		=> $row['desc'],
					'directory'			=> $album['folder'],
					'file_name'			=> $row['filename'],
					'file_type'			=> $mime,
					'approved'			=> $row['show'],
					'views'				=> $row['filename'],
					'idate'				=> strtotime($row['date']),
					'ratings_total'		=> $row['total_value'],
					'ratings_count'		=> $row['total_votes'],
					'rating'			=> $row['rating'],
				);

				// Go!
				$this->lib->convertImage($row['id'], $save, $path);

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
					'img_id'			=> $row['ownerid'],
					'author_name'		=> $row['name'],
					'comment'			=> $row['comment'],
					'post_date'			=> strtotime($row['date']),
					'ip_address'		=> $row['comment_ip'],
					'author_id'			=> 0,
					'approved'			=> $row['inmoderation'] ? 0 : 1,
					);

				$this->lib->convertComment($row['id'], $save);
			}

			$this->lib->next();

		}

	}

