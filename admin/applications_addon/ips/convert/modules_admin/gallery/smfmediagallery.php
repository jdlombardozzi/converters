<?php
/**
 * IPS Converters
 * IP.Gallery 3.0 Converters
 * SMF Media Gallery
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
		'key'	=> 'smfmediagallery',
		'name'	=> 'SMF Media Gallery 1.5',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'smf', 'newdb' => false),
		array('app' => 'board', 'key' => 'smf_legacy', 'newdb' => false),
		));

	class admin_convert_gallery_smfmediagallery extends ipsCommand
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
				'gallery_images'		=> array('members', 'gallery_albums'),
				'gallery_comments'		=> array('members', 'gallery_images'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_gallery.php' );
			$this->lib =  new lib_gallery( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'SMF Media Gallery &rarr; IP.Gallery Conversion Tool' );

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
				case 'gallery_albums':
					return $this->lib->countRows('mgallery_albums');
					break;

				case 'gallery_images':
					return $this->lib->countRows('mgallery_media');
					break;

				case 'gallery_comments':
					return $this->lib->countRows('mgallery_comments');
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
		private function fixPostData($text)
		{
			// Sort out the list tags
			$text = str_replace('[li]', '[*]', $text);
			$text = str_replace('[/li]', '', $text);

			// God knows why this is needed, but it is
			$text = $this->parser->preDbParse($this->parser->preDisplayParse($text));

			return $text;

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

			//$this->lib->saveMoreInfo('gallery_albums', array('orphans'));
			$this->lib->saveMoreInfo ( 'gallery_albums', array ( 'container_album' ) );

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'mgallery_albums',
							'order'		=> 'id_album ASC',
						);

			$loop = $this->lib->load('gallery_albums', $main);

			//---------------------------
			// Add conv_parent
			//---------------------------

			if(!$this->request['st'])
			{
				$value = array('gallery_albums_main', 'conv_album_parent_id', 'mediumint(5)');
				if (!$this->DB->checkForField($value[1], $value[0]))
				{
					$this->DB->addField( $value[0], $value[1], $value[2] );
				}
			}

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
				if($row['type'] == 'user')
				{
					$save = array(
						'album_name'		=> $row['name'],
						'album_description'	=> $row['description'],
						'album_owner_id'	=> $row['album_of'],
						'album_parent_id'	=> ( $row['parent'] ? $row['parent'] : 0 ),
						);

					$this->lib->convertAlbum($row['id_album'], $save, $us);
				}
				else
				{

					$save = array(
						'album_name'				=> $row['name'],
						'album_description'			=> $row['description'],
						'password'					=> $row['passwd'],
						'album_parent_id'			=> $row['parent'],
						'album_g_container_only'	=> 1,
						);

					$this->lib->convertAlbum($row['id_album'], $save, array(), true);
				}

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
							'from' 		=> 'mgallery_media',
							'order'		=> 'id_media ASC',
						);

			$loop = $this->lib->load('gallery_images', $main, array('gallery_favorites'));

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('gallery_images', $loop, array('gallery_path' => array('type' => 'text', 'label' => 'The path to the folder where images are saved (no trailing slash - usually path_to_smf/mgal_data):')), 'path');

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
			$new = $this->DB->buildAndFetch( array( 'select' => 'status', 'from' => 'rc_status', 'where' => 'is_new=1' ) );

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Do the image
				//-----------------------------------------

				$album_link = $this->lib->getLink($row['album_id'], 'gallery_albums', true);

				$file = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'mgallery_files', 'where' => "id_file={$row['id_file']}" ) );

				// Have a stab at the mimetype
				$explode = explode('.', $file['filename']);
				$ext = strtolower(array_pop($explode));
				$ext = ($ext == 'jpg') ? 'jpeg' : $ext;
				$mime = "image/{$ext}";

				$save = array(
					'member_id'		=> $row['id_member'],
					'img_album_id'		=> ($album_link) ? $row['album_id'] : 0,
					'caption'		=> $row['title'],
					'description'	=> $this->fixPostData($row['description']),
					'directory'		=> $file['directory'],
					'masked_file_name' => $this->_getEncryptedFilename($file['filename'], $file['id_file']),
					'file_name'		=> $file['filename'],
					'file_size'		=> $file['filesize'],
					'file_type'		=> $mime,
					'approved'		=> $row['approved'],
					'views'			=> $row['views'],
					'comments'		=> $row['num_comments'],
					'idate'			=> $row['time_added'],
					'ratings_total'	=> $row['rating'] * $row['voters'],
					'ratings_count'	=> $row['voters'],
					'rating'		=> $row['rating'],
					);

				$this->lib->convertImage($row['id_media'], $save, $path);

				//-----------------------------------------
				// Ratings
				//-----------------------------------------

				$rates = array(	'select' 	=> '*',
								'from' 		=> 'mgallery_log_ratings',
								'order'		=> 'id_media ASC',
								'where'		=> 'id_media='.$row['id_media']
							);

				ipsRegistry::DB('hb')->build($rates);
				ipsRegistry::DB('hb')->execute();
				while ($rate = ipsRegistry::DB('hb')->fetch())
				{
					$saverate = array(
						'member_id'	=> $rate['id_member'],
						'img_id'	=> $rate['id_media'],
						'rdate'		=> $rate['time'],
						'rate'		=> $rate['rating'],
						);

					$this->lib->convertRating($rate['id_media'].'-'.$rate['id_member'], $saverate);
				}

				//-----------------------------------------
				// Report Center
				//-----------------------------------------

				$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='gallery'" ) );

				$rs = array(	'select' 	=> '*',
								'from' 		=> 'mgallery_variables',
								'order'		=> 'id ASC',
								'where'		=> 'type=\'item_report\' AND val4='.$row['id_media']
							);

				$rget = ipsRegistry::DB('hb')->buildAndFetch($rs);

				if($rget)
				{
					$report = array(
						'id'			=> $rget['id'],
						'title'			=> 'Image Report',
						'status'		=> $new['status'],
						'rc_class'		=> $rc['com_id'],
						'updated_by'	=> $rget['val1'],
						'date_updated'	=> $rget['val2'],
						'date_created'	=> $rget['val2'],
						'exdat1'		=> $rget['val4'],
						'exdat2'		=> 0,
						'exdat3'		=> 0,
						'num_reports'	=> 1,
						'num_comments'	=> 0,
						);

					$reports[] = array(
							'id'			=> $rget['id_report'],
							'report'		=> $rget['val3'],
							'report_by'		=> $rget['val1'],
							'date_reported'	=> $rget['val2']
						);

					$this->lib->convertReport('gallery_images', $report, $reports, false, array());
				}


			}

			$this->lib->next();

		}

		/**
		 * Works out the SMF-style encrypted filename
		 *
		 * @access	private
		 * @param 	string		The file name
		 * @param	int			The file ID (foreign)
		 * @return void
		 **/
		private function _getEncryptedFilename($name, $id)
		{
			$clean_name = preg_replace(array('/\s/', '/[^\w_\.-]/'), array('_', ''), $name);
			$e = explode('.', $name);
			$ext = array_pop( $e );
			$enc_name = $id . '_' . strtr($clean_name, '.', '_') . md5($clean_name).'_ext'.strtolower($ext);
			$clean_name = preg_replace('~\.[\.]+~', '.', $clean_name);

			return $enc_name;
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
							'from' 		=> 'mgallery_comments',
							'order'		=> 'id_comment ASC',
						);

			$loop = $this->lib->load('gallery_comments', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'author_id'		=> $row['id_member'],
					'img_id'		=> $row['id_media'],
					'comment'		=> $this->fixPostData($row['message']),
					'post_date'		=> $row['posted_on'],
					'edit_time'		=> $row['last_edited'],
					'edit_name'		=> $row['last_edited_name'],
					'approved'		=> $row['approved'],
					);

				$this->lib->convertComment($row['id_comment'], $save);
			}

			$this->lib->next();

		}

	}

