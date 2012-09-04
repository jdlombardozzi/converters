<?php
/**
 * IPS Converters
 * IP.Gallery 3.0 Converters
 * IP.Gallery Merge Tool
 * Last Update: $Date: 2011-06-08 12:44:41 -0400 (Wed, 08 Jun 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 529 $
 */


	$info = array(
		'key'	=> 'ipgallery',
		'name'	=> 'IP.Gallery 3.0',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'ipboard', 'newdb' => false),
		));

	class admin_convert_gallery_ipgallery extends ipsCommand
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
				'gallery_albums'		=> array('members'),
				'gallery_images'		=> array('members','gallery_albums'),
				'gallery_comments'		=> array('members', 'gallery_images'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_gallery.php' );
			$this->lib =  new lib_gallery( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'IP.Gallery Merge Tool' );

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
				case 'gallery_albums':
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
		 * Convert Albums
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_gallery_albums()
		{
			// Save extra information
			$this->lib->saveMoreInfo ( 'gallery_albums', array ( 'container_album' ) );

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'gallery_albums_main',
							'order'		=> 'album_id ASC',
						);

			$loop = $this->lib->load('gallery_albums', $main);
			
			// Have any info?
			$options = array ( );
			$this->DB->build ( array (
				'select'	=> '*',
				'from'		=> 'gallery_albums_main',
				'where'		=> 'album_is_global = 1',
			) );
			$albumRes = $this->DB->execute ( );
			while ( $row = $this->DB->fetch ( $albumRes ) )
			{
				$options[$row['album_id']] = $row['album_name'];
			}
			
			if ( count ( $options ) < 1 )
			{
				$this->lib->error ( 'You need at least one Global Album before you may continue.' );
			}
			
			$this->lib->getMoreInfo ( 'gallery_albums', $loop, array (
				'container_album'	=> array (
					'type'		=> 'dropdown',
					'label'		=> 'The Global Album to store all Member Albums in:',
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
				// Ensure we are only passing the default fields.
				// May seem redundant at first, because the fields don't change
				// but avoids the "admin has apps installed" issue.
				
				// ALso, only do the important stuff. Anything else is done during Album Resync.
				$save = array (
					'album_parent_id'				=> $row['album_parent_id'],
					'album_owner_id'				=> $row['album_owner_id'],
					'album_name'					=> $row['album_name'],
					'album_name_seo'				=> $row['album_name_seo'],
					'album_description'				=> $row['album_description'],
					'album_is_public'				=> $row['album_is_public'],
					'album_is_global'				=> $row['album_is_global'],
					'album_is_profile'				=> $row['album_is_profile'],
					'album_count_imgs'				=> $row['album_count_imgs'],
					'album_count_comments'			=> $row['album_count_comments'],
					'album_count_imgs_hidden'		=> $row['album_count_imgs_hidden'],
					'album_count_comments_hidden'	=> $row['album_count_comments_hidden'],
					'album_allow_comments'			=> $row['album_allow_comments'],
					'album_g_rules'					=> $row['album_g_rules'],
					'album_g_container_only'		=> $row['album_g_container_only'],
				);
				
				$this->lib->convertAlbum($row['album_id'], $save, $us);
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
							'from' 		=> 'gallery_images',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_images', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('gallery_images', $loop, array('gallery_path' => array('type' => 'text', 'label' => 'The path to the folder where images are saved (no trailing slash - usually path_to_ipgallery/uploads):')), 'path');

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

			//-----------------------------------------
			// Prepare for reports conversion
			//-----------------------------------------

			$this->lib->prepareReports('gallery_images');

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Do the image
				//-----------------------------------------

				$save = array (
					'member_id'		=> $row['member_id'],
					'img_album_id'	=> $row['img_album_id'],
					'caption'		=> $row['caption'],
					'description'	=> $row['description'],
					'file_name'		=> $row['masked_file_name'],
					'file_size'		=> $row['file_size'],
					'approved'		=> $row['approved'],
					'idate'			=> $row['idate'],
					'copyright'		=> $row['copyright'],
					'caption_seo'	=> $row['caption_seo'],
					'views'			=> $row['views'],
					'pinned'		=> $row['pinned'],
					'media'			=> $row['media'],
					'credit_info'	=> $row['credit_info']
				);

				$this->lib->convertImage($row['id'], $info, $path);

				//-----------------------------------------
				// Ratings
				//-----------------------------------------

				$rates = array(	'select' 	=> '*',
								'from' 		=> 'gallery_ratings',
								'order'		=> 'id ASC',
								'where'		=> 'rating_foreign_id='.$row['id']
							);

				ipsRegistry::DB('hb')->build($rates);
				ipsRegistry::DB('hb')->execute();
				while ($rate = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertRating($rate['id'], $rate);
				}

				//-----------------------------------------
				// Report Center
				//-----------------------------------------

				$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='gallery'" ) );
				$rs = array(	'select' 	=> '*',
								'from' 		=> 'rc_reports_index',
								'order'		=> 'id ASC',
								'where'		=> 'exdat1='.$row['id']." AND exdat2=0 AND rc_class='{$rc['com_id']}'"
							);

				ipsRegistry::DB('hb')->build($rs);
				ipsRegistry::DB('hb')->execute();
				while ($report = ipsRegistry::DB('hb')->fetch())
				{
					$rs = array(	'select' 	=> '*',
									'from' 		=> 'rc_reports',
									'order'		=> 'id ASC',
									'where'		=> 'rid='.$report['id']
								);

					ipsRegistry::DB('hb')->build($rs);
					ipsRegistry::DB('hb')->execute();
					$reports = array();
					while ($r = ipsRegistry::DB('hb')->fetch())
					{
						$reports[] = $r;
					}
					$this->lib->convertReport('gallery_images', $report, $reports);
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
							'from' 		=> 'gallery_comments',
							'order'		=> 'pid ASC',
						);

			$loop = $this->lib->load('gallery_comments', $main);

			//-----------------------------------------
			// Prepare for reports conversion
			//-----------------------------------------

			$this->lib->prepareReports('gallery_comments');

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$row['comment'] = $this->fixPostData($row['comment']);
				$this->lib->convertComment($row['pid'], $row);

				//-----------------------------------------
				// Report Center
				//-----------------------------------------

				$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='gallery'" ) );
				$rs = array(	'select' 	=> '*',
								'from' 		=> 'rc_reports_index',
								'order'		=> 'id ASC',
								'where'		=> 'exdat2='.$row['pid']." AND rc_class='{$rc['com_id']}'"
							);

				ipsRegistry::DB('hb')->build($rs);
				ipsRegistry::DB('hb')->execute();
				while ($report = ipsRegistry::DB('hb')->fetch())
				{
					$rs = array(	'select' 	=> '*',
									'from' 		=> 'rc_reports',
									'order'		=> 'id ASC',
									'where'		=> 'rid='.$report['id']
								);

					ipsRegistry::DB('hb')->build($rs);
					ipsRegistry::DB('hb')->execute();
					$reports = array();
					while ($r = ipsRegistry::DB('hb')->fetch())
					{
						$reports[] = $r;
					}
					$this->lib->convertReport('gallery_comments', $report, $reports);
				}

			}

			$this->lib->next();

		}

		/**
		 * Convert media types
		 *
		 * @deprecated As of Gallery 4.x
		 * @access	private
		 * @return void
		 **/
		/*private function convert_gallery_media_types()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('gallery_media_types', array('media_opt'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'gallery_media_types',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_media_types', $main);

			//-----------------------------------------
			// We need to know what do do with duplicates
			//-----------------------------------------

			$this->lib->getMoreInfo('gallery_media_types', $loop, array('media_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate media types?')));

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertMediaType($row['id'], $row, $us['media_opt']);
			}

			$this->lib->next();

		}*/

		/**
		 * Convert Form Fields
		 *
		 * @deprecated As of Gallery 4.x
		 * @access	private
		 * @return void
		 **/
		/*private function convert_gallery_form_fields()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'gallery_form_fields',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('gallery_form_fields', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertFormField($row['id'], $row);
			}

			$this->lib->next();

		}*/

	}

