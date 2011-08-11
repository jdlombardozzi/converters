<?php
/**
 * IPS Converters
 * Application Files
 * Library functions for IP.Gallery 3.0 conversions
 * Last Update: $Date: 2011-06-24 18:29:40 +0100 (Fri, 24 Jun 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 539 $
 */
class lib_gallery extends lib_master
{
	/**
	 * Information box to display on convert screen
	 *
	 * @access	public
	 * @return	string 		html to display
	 */
	public function getInfo()
	{
		return "<a href='{$this->settings['base_url']}&app=gallery&module=albums&section=manage&do=overview' target='_blank'>Click here</a> and confirm each albums Permissions and Settings are correct, then run the following Tools in the order specified.<br /><br />
		
		<ol>
			<li>Rebuild Node Tree</li>
			<li>Recount & Resync Albums</li>
			<li>Rebuild Images</li>
		</ol><br />
		
		After that, <a href='{$this->settings['base_url']}&app=gallery&module=overview&section=settings' target='_blank'>click here</a> and turn the application back on.";
	}

	/**
	 * Return the information needed for a specific action
	 *
	 * @access	public
	 * @param 	string		action (e.g. 'members', 'forums', etc.)
	 * @return 	array 		info needed for html->convertMenuRow
	 **/
	public function menuRow($action='', $return=false)
	{
		switch ($action)
		{
			case 'members':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'members' ) );
				$return = array(
					'name'	=> 'Members',
					'rows'	=> $count['count'],
					'cycle'	=> 2000,
				);
				break;

			case 'groups':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'groups' ) );
				$return = array(
					'name'	=> 'Member Groups',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;

			case 'forum_perms':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'forum_perms' ) );
				$return = array(
					'name'	=> 'Permission Sets',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;
			
			case 'gallery_categories':
				$count = $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'gallery_albums_main' ) );
				$return = array (
					'name'	=> 'Categories',
					'rows'	=> $count['count'],
					'cycle'	=> 1000,
				);

			case 'gallery_albums':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_albums_main' ) );
				$return = array(
					'name'	=> 'Albums',
					'rows'	=> $count['count'],
					'cycle'	=> 1000,
				);
				break;

			case 'gallery_images':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_images' ) );
				$return = array(
					'name'	=> 'Images',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;

			case 'gallery_comments':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_comments' ) );
				$return = array(
					'name'	=> 'Comments',
					'rows'	=> $count['count'],
					'cycle'	=> 2000,
				);
				break;

			case 'gallery_ratings':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_ratings' ) );
				$return = array(
					'name'	=> 'Ratings',
					'rows'	=> $count['count'],
					'cycle'	=> 2000,
				);
				break;

			/*case 'gallery_media_types':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_media_types' ) );
				$return = array(
					'name'	=> 'Media Types',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;

			case 'gallery_form_fields':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_form_fields' ) );
				$return = array(
					'name'	=> 'Form Fields',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;*/

			default:
				if ($return)
				{
					return false;
				}
				$this->error("There is a problem with the converter: called invalid action {$action}");
				break;
		}

		$basic = array('section' => $this->app['app_key'], 'key' => $action, 'app' => 'gallery');
		return array_merge($basic, $return);
	}

	/**
	 * Return the tables that need to be truncated for a given action
	 *
	 * @access	public
	 * @param 	string		action (e.g. 'members', 'forums', etc.)
	 * @return 	array 		array('table' => 'id_field', ...)
	 **/
	public function truncate($action)
	{
		switch ($action)
		{
			case 'members':
				return array( 'members' => 'member_id', 'pfields_content' => 'member_id', 'profile_portal' => 'pp_member_id', 'rc_modpref' => 'mem_id' );
				break;

			case 'groups':
				return array( 'groups' => 'g_id' );
				break;

			case 'forum_perms':
				return array( 'forum_perms' => 'perm_id' );
				break;

			case 'gallery_albums':
			case 'gallery_categories':
				return array( 'gallery_albums_main' => 'album_id' );
				break;

			case 'gallery_images':
				return array( 'gallery_images' => 'id' );
				break;

			case 'gallery_comments':
				return array( 'gallery_comments' => 'pid' );
				break;

			case 'gallery_ratings':
				return array( 'gallery_ratings' => 'id' );
				break;

			/*case 'gallery_media_types':
				return array( 'gallery_media_types' => 'id' );
				break;

			case 'gallery_form_fields':
				return array( 'gallery_form_fields' => 'id' );
				break;*/

			default:
				$this->error('There is a problem with the converter: bad truncate command');
				break;
		}
	}

	/**
	 * Database changes
	 *
	 * @access	public
	 * @param 	string		action (e.g. 'members', 'forums', etc.)
	 * @return 	array 		Details of change - array('type' => array(info))
	 **/
	public function databaseChanges ( $action )
	{
		switch ($action)
		{
			case 'gallery_albums':
				return array ( 'addfield' => array('gallery_albums_main', 'conv_album_parent_id', 'mediumint(5)' ) );
				break;

			default:
				return null;
				break;
		}
	}

	/**
	 * Process report links
	 *
	 * @access	protected
	 * @param 	string		type (e.g. 'post', 'pm')
	 * @param 	array 		Data for reports_index table with foreign IDs
	 * @return 	array 		Processed data for reports_index table
	 **/
	protected function processReportLinks($type, $report)
	{
		switch ($type)
		{
			case 'gallery_images':
				$report['exdat1'] = $this->getLink($report['exdat1'], 'gallery_images');
				$report['exdat2'] = 0;
				$report['exdat3'] = 0;
				$report['url'] = "/index.php?app=gallery&amp;module=images&amp;section=viewimage&amp;img={$report['exdat1']}";
				$report['seotemplate'] = '';
				break;

			case 'gallery_comments':
				$report['exdat1'] = $this->getLink($report['exdat1'], 'gallery_images');
				$report['exdat2'] = $this->getLink($report['exdat2'], 'gallery_comments');
				$report['exdat3'] = $report['exdat3'];
				$report['url'] = "/index.php?app=gallery&amp;module=images&amp;section=viewimage&amp;img={$report['exdat1']}&amp;st={$report['exdat3']}#{$report['exdat2']}";
				$report['seotemplate'] = '';
				break;
		}
		return $report;
	}

	/**
	 * Convert an album
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @param 	boolean		If true, will not get link for category id
	 * @return 	boolean		Success or fail
	 **/
	public function convertAlbum ( $id, $info, $us = array ( ), $skip_global_link = false )
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------

		if (!$id)
		{
			$this->logError($id, 'No ID number provided');
			return false;
		}
		if (!$info['album_name'])
		{
			$this->logError($id, 'No name provided');
			return false;
		}

		//-----------------------------------------
		// Link
		//-----------------------------------------

		$info['album_owner_id']		= $info['album_owner_id']	? $this->getLink (
			$info['album_owner_id'],
			'members',
			false,
			$this->useLocalLink
		) : 0;
		
		// Put Albums with no parent into a global album.
		$info['is_root_album'] = FALSE;
		if ( ( $info['album_parent_id'] == 0 || !isset ( $info['album_parent_id'] ) ) && !$skip_global_link )
		{
			$info['album_parent_id'] = $us['container_album'];
			$info['is_root_album'] = TRUE;
		}
		
		if ( $info['album_parent_id'] != 0 && !$info['is_root_album'] )
		{
			$parent = $this->getLink ( $info['album_parent_id'], 'gallery_albums' );
			if ( $parent )
			{
				$info['album_parent_id'] = $parent;
			}
			else
			{
				$info['conv_album_parent_id'] = $info['album_parent_id'];
				unset ( $info['album_parent_id'] );
			}
		}

		// This will be sorted out in the rebuild
		unset($info['last_pic_id']);
		
		// No longer needed.
		unset ( $info['is_root_album'] );

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['id']);
		$this->DB->insert( 'gallery_albums_main', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'gallery_albums');

		return true;
	}

	/**
	 * Convert an image
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @param 	string 		Path to where images are stores
	 * @param	boolean		If true, loads file data from database, rather than move file
	 * @return 	boolean		Success or fail
	 **/
	public function convertImage($id, $info, $path, $db=false)
	{
		// Check we have a path
		//if (!$this->settings['gallery_images_path'])
		//{
		//	$this->logError($id, 'Your IP.Gallery uploads path has not been configured');
		//	return false;
		//}
		
		if ( !file_exists ( $this->settings['gallery_images_path'] . '/gallery' ) )
		{
			if ( !mkdir( $this->settings['gallery_images_path'].'/gallery', 0777 ) )
			{
				$this->error ( '"gallery" folder does not exist in the uploads directory.' );
				return false;
			}
		}
		
		if ( !is_writable ( $this->settings['gallery_images_path'] ) )
		{
			$this->error ( '"gallery" folder is not writable.' );
			return false;
		}

		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------
		if (!$id)
		{
			$this->logError($id, 'No ID number provided');
			return false;
		}
		// Need image path if was not stored in database
		if (!$path and !$db)
		{
			$this->logError($id, 'No path provided');
			return false;
		}

		// Be sure to have member id
		if (!$info['member_id'])
		{
			$this->logError($id, 'No member ID provided');
			return false;
		}

		// Need to store in either category or album
		if (!$info['img_album_id'])
		{
			$this->logError($id, 'No album ID provided');
			return false;
		}

		// Check if a masked name was provided. If not, just use the filename.
		$info['masked_file_name'] = ($info['masked_file_name']) ? $info['masked_file_name'] : $info['file_name'];
		if (!$db and !$info['masked_file_name'])
		{
			$this->logError($id, 'No filename provided');
			return false;
		}

		// Make sure image data was provided if stored in database.
		if ($db && !$info['data'])
		{
			$this->logError($id, 'No file data provided');
			return false;
		}

		if ( isset($info['directory']) && $info['directory'] != '' )
		{
			$path = $path . '/' . trim($info['directory'], '/');
		}

		// Check the file actually exists
		if (!$db && !file_exists($path.'/'.$info['masked_file_name']))
		{
			$this->logError($id, 'Could not locate file '.$path.'/'.$info['masked_file_name']);
			return false;
		}

		//-----------------------------------------
		// Set up array
		//-----------------------------------------
		$imageArray = array( 'member_id'      => $this->getLink($info['member_id'], 'members', false, $this->useLocalLink),
							 'img_album_id'	 => ($info['img_album_id']) ? $this->getLink($info['img_album_id'], 'gallery_albums') : 0,
							 'caption'		 => $info['caption'] ? $info['caption'] : 'No caption',
							 //'file_size'	 => $info['file_size'] ? $info['file_size'] : 2,
							 'description'	 => $info['description'],
							 'directory'	 => '',
							 'file_name'     => $info['file_name'],
							 'approved'		 => $info['approved'],
							 'thumbnail'	 => $info['thumbnail'], // Revisit
							 'views'		 => intval($info['views']),
							 'comments'		 => intval($info['comments']),
							 'idate'		 => intval($info['idate']),
							 'ratings_total' => intval($info['ratings_total']),
							 'ratings_count' => intval($info['ratings_count']),
							 'caption_seo'	 => IPSText::makeSeoTitle( $info['caption'] ),
							 'image_notes'	 => $info['image_notes'],
							 'rating'		 => intval($info['ratings_total']) > 0 ? intval($info['ratings_total']) / intval($info['ratings_count']) : 0 );
		
		if ( !isset ( $info['file_size'] ) )
		{
			$imageArray['file_size'] = @filesize ( $path . '/' . $info['masked_file_name'] );
		}
		else
		{
			$imageArray['file_size'] = $info['file_size'];
		}
			 
		$imageArray['directory'] = 'gallery/album_' . $imageArray['img_album_id'];

		// Fields still required = array( 'file_name', 'file_type', 'masked_file_name', 'medium_file_name');
		// Fields optional = array( 'file_size', 'pinned', 'media', 'credit_info', 'metadata', 'media_thumb');

		$_file = IPSLib::getAppDir(  'gallery' ) . '/app_class_gallery.php';
		$_name = 'app_class_gallery';

		$galleryLibObject;
		if ( file_exists( $_file ) )
		{
			$classToLoad = IPSLib::loadLibrary( $_file, $_name );

			 $galleryLibObject = new $classToLoad( $this->registry );
		}

		require_once IPS_KERNEL_PATH . 'classUpload.php';
		$upload = new classUpload();

		$dir = $this->registry->gallery->helper ( 'upload' )->createDirectoryName ( $imageArray['img_album_id'] );
		
		$ext = $upload->_getFileExtension( $info['file_name'] );

		$new_name = "gallery_{$info['member_id']}_" . $info['img_album_id'] . "_" . time()%$imageArray['file_size'] . '.' . $ext;
		$imageArray['masked_file_name'] = $new_name;
		$new_file = $this->settings['gallery_images_path'] . '/' . $dir . '/' . $new_name;

		// Create the file from the db if that's the case
		if ($db)
		{
			$this->createFile($new_name, $info['data'], $info['file_size'], $this->settings['gallery_images_path'] . '/' . substr($dir,0,-1));
		}
		else
		{
			// Copy the file to its end IP.Gallery location
			if(!@copy( $path.'/'.$info['masked_file_name'], $new_file))
			{
				$e = error_get_last();
				$this->logError($id, 'Could not move file - attempted to move '.$path.'/'.$info['masked_file_name'].' to '.$new_file.'<br />'.$e['message'].'<br /><br />');
				return false;
			}
		}

		@chmod( $new_file, 0777 );

		if( method_exists( $upload, 'check_xss_infile' ) )
		{
			$upload->saved_upload_name = $new_file;
			$upload->check_xss_infile();

			if( $upload->error_no == 5 )
			{
				$this->logError($id, 'Invalid XSS file: '.$info['file_name'].'<br /><br />');
				return false;
			}
		}

		//-------------------------------------------------------------
		// Exif/IPTC support?
		//-------------------------------------------------------------
		$meta_data = array();

		if ( $this->settings['gallery_exif'] )
		{
			$meta_data = array_merge( $meta_data, $this->registry->gallery->helper ( 'image' )->extractExif( $new_file ) );
		}

		if ( $this->settings['gallery_iptc'] )
		{
			$meta_data = array_merge( $meta_data, $this->registry->gallery->helper ( 'image' )->extractIptc( $new_file ) );
		}
		$imageArray['metadata'] = serialize($meta_data);

		//-------------------------------------------------------------
		// Pass to library
		//-------------------------------------------------------------
		$media 	= 0;
		$imageArray['media'] = $this->_isImage ( $ext ) ? 0 : 1;

		$ext	= '.' . $ext;

		$image = array(	'media'				=> $imageArray['media'],
						'directory'			=> $dir,
						'masked_file_name'	=> $new_name );


		$imageArray['medium_file_name'] = 'med_' . $new_name;
		$imageArray['file_type'] = $this->registry->gallery->helper ( 'image' )->getImageType( $new_file );

		// Go
		$this->DB->insert( 'gallery_images', $imageArray );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------
		$this->addLink($inserted_id, $id, 'gallery_images');

		return true;
	}

	/**
	 * Convert a comment
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertComment($id, $info)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------

		if (!$id)
		{
			$this->logError($id, 'No ID number provided');
			return false;
		}
		if (!$info['author_id'])
		{
			$this->logError($id, 'No member ID provided');
			return false;
		}
		if (!$info['img_id'])
		{
			$this->logError($id, 'No image ID provided');
			return false;
		}
		if (!$info['comment'])
		{
			$this->logError($id, 'No comment provided');
			return false;
		}

		//-----------------------------------------
		// Link
		//-----------------------------------------

		$info['author_id'] = $this->getLink($info['author_id'], 'members', false, $this->useLocalLink);
		$info['img_id'] = $this->getLink($info['img_id'], 'gallery_images');

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['pid']);
		$this->DB->insert( 'gallery_comments', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'gallery_comments');

		return true;
	}

	/**
	 * Convert a rating
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertRating($id, $info)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------

		if (!$id)
		{
			$this->logError($id, '(RATING) No ID number provided');
			return false;
		}
		if (!$info['member_id'])
		{
			$this->logError($id, '(RATING) No member ID provided');
			return false;
		}
		if (!$info['rating_foreign_id'])
		{
			$this->logError($id, '(RATING) No image ID provided');
			return false;
		}
		if (!$info['rate'])
		{
			$this->logError($id, '(RATING) No rating provided');
			return false;
		}

		//-----------------------------------------
		// Link
		//-----------------------------------------

		$info['member_id'] = $this->getLink($info['member_id'], 'members', false, $this->useLocalLink);
		$info['rating_foreign_id'] = $this->getLink($info['rating_foreign_id'], 'gallery_images');

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['id']);
		$this->DB->insert( 'gallery_ratings', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'gallery_ratings');

		return true;
	}

	/**
	 * Convert a media type
	 *
	 * @deprecated As of Gallery 4.x
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @param 	string 		How to handle duplicates ('local' or 'remote')
	 * @return 	boolean		Success or fail
	 **/
	/*public function convertMediaType($id, $info, $dupes)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------

		if (!$id)
		{
			$this->logError($id, 'No ID number provided');
			return false;
		}
		if (!$info['title'])
		{
			$this->logError($id, 'No title provided');
			return false;
		}
		if (!$info['mime_type'])
		{
			$this->logError($id, 'No mime type provided');
			return false;
		}
		if (!$info['extension'])
		{
			$this->logError($id, 'No extension provided');
			return false;
		}
		if (!$info['display_code'])
		{
			$this->logError($id, 'No display code provided');
			return false;
		}

		//-----------------------------------------
		// Handle duplicates
		//-----------------------------------------

		$dupe = $this->DB->buildAndFetch( array( 'select' => 'id', 'from' => 'gallery_media_types', 'where' => "extension = '{$info['extension']}'" ) );
		if ($dupe)
		{
			if ($dupes == 'local')
			{
				return false;
			}
			else
			{
				$this->DB->delete('gallery_media_types', "id={$dupe['id']}");
			}
		}

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['id']);
		$this->DB->insert( 'gallery_media_types', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'gallery_media_types');

		return true;
	}*/

	/**
	 * Convert a form field
	 *
	 * @deprecated As of Gallery 4.x
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	/*public function convertFormField($id, $info)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------

		if (!$id)
		{
			$this->logError($id, 'No ID number provided');
			return false;
		}
		if (!$info['name'])
		{
			$this->logError($id, 'No name provided');
			return false;
		}
		if (!$info['type'])
		{
			$this->logError($id, 'No type provided');
			return false;
		}

		//-----------------------------------------
		// Handle duplicates
		//-----------------------------------------

		$dupe = $this->DB->buildAndFetch( array( 'select' => 'id', 'from' => 'gallery_form_fields', 'where' => "name = '{$info['name']}'" ) );
		if ($dupe)
		{
			$this->addLink($dupe['id'], $id, 'gallery_form_fields');
			return false;
		}

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['id']);
		$this->DB->insert( 'gallery_form_fields', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add a column
		//-----------------------------------------

		$this->DB->addField( 'gallery_images', "field_$inserted_id", 'text' );
		$this->DB->optimize( 'gallery_images' );

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'gallery_form_fields');

		return true;
	}*/

	/**
	 * Check for Media files.
	 *
	 * @access	private
	 * @param	File Extension
	 * @return	boolean		True if Image, False if Media.
	 */
	private function _isImage ( $extension )
	{
		$valid_image_ext = array ( 'jpeg', 'jpg', 'jpe', 'png', 'gif', 'bmp' );
		
		if ( in_array ( $extension, $valid_image_ext ) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}
