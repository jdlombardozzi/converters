<?php
/**
 * IPS Converters
 * Application Files
 * Library functions for IP.Gallery 3.0 conversions
 * Last Update: $Date: 2010-03-11 11:26:15 +0100(gio, 11 mar 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 434 $
 */
class convertGallery extends lib_master
{
	public $media_thumb_cache = array();

	/**
	 * Information box to display on convert screen
	 *
	 * @access	public
	 * @return	string 		html to display
	 */
	public function getInfo()
	{
		return "<strong>Recount Albums</strong><br />
			<a href='{$this->settings['base_url']}&app=gallery&module=albums&section=manage&do=recountallalbums' target='_blank'>Click here</a> to recount all albums.
			<br /><br />
			<strong>Rebuild Categories</strong><br />
			<a href='{$this->settings['base_url']}&app=gallery&module=cats&section=manage&do=recount&cat=all' target='_blank'>Click here</a> to rebuild all categories.
			<br /><br />
			<strong>Rebuild Images</strong><br />
			<a href='{$this->settings['base_url']}&app=gallery&module=tools&section=tools&do=rethumbs' target='_blank'>Click here</a> and rebuild images in all categories.<br />
			<br /><br />
			<strong>Turn the application back online</strong><br />
			Visit your IP.Gallery settings and turn the application back online.";
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
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_categories' ) );
				$return = array(
					'name'	=> 'Categories',
					'rows'	=> $count['count'],
					'cycle'	=> 1000,
				);
				break;

			case 'gallery_albums':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_albums' ) );
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

			case 'gallery_ecardlog':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_ecardlog' ) );
				$return = array(
					'name'	=> 'eCard Log',
					'rows'	=> $count['count'],
					'cycle'	=> 1000,
				);
				break;

			case 'gallery_subscriptions':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_subscriptions' ) );
				$return = array(
					'name'	=> 'Subscriptions',
					'rows'	=> $count['count'],
					'cycle'	=> 2000,
				);
				break;

			case 'gallery_media_types':
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
				break;

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

			case 'gallery_categories':
				return array( 'gallery_categories' => 'id' );
				break;

			case 'gallery_albums':
				return array( 'gallery_albums' => 'id' );
				break;

			case 'gallery_images':
				return array( 'gallery_images' => 'id' );
				break;

			case 'gallery_comments':
				return array( 'gallery_comments' => 'pid' );
				break;

			case 'gallery_ecardlog':
				return array( 'gallery_ecardlog' => 'id' );
				break;

			case 'gallery_favorites':
				return array( 'gallery_favorites' => 'id' );
				break;

			case 'gallery_subscriptions':
				return array( 'gallery_subscriptions' => 'sub_id' );
				break;

			case 'gallery_ratings':
				return array( 'gallery_ratings' => 'id' );
				break;

			case 'gallery_media_types':
				return array( 'gallery_media_types' => 'id' );
				break;

			case 'gallery_form_fields':
				return array( 'gallery_form_fields' => 'id' );
				break;

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
	public function databaseChanges($action)
	{
		switch ($action)
		{
			case 'gallery_categories':
				return array('addfield' => array('gallery_categories', 'conv_parent', 'mediumint(5)'));
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
	 * Convert a category
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @param 	array 		Permissions index data
	 * @return 	boolean		Success or fail
	 **/
	public function convertCategory($id, $info, $perms)
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

		//-----------------------------------------
		// Link
		//-----------------------------------------

		$info['last_member_id'] = ($info['last_member_id']) ? $this->getLink($info['last_member_id'], 'members', false, $this->useLocalLink) : 0;

		// This will be sorted out in the rebuild
		unset($info['last_pic_id']);

		// We need to sort out the parent id
		if ($info['parent'] != 0)
		{
			$parent = $this->getLink($info['parent'], 'gallery_categories');
			if ($parent)
			{
				$info['parent'] = $parent;
			}
			else
			{
				$info['conv_parent'] = $info['parent'];
				unset($info['parent']);
			}
		}

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		// Make sure we don't have any fields we shouldn't have
		foreach (array('perm_id', 'app', 'perm_type', 'perm_type_id', 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7', 'owner_only', 'friend_only', 'authorized_users') as $unset)
		{
			unset($info[$unset]);
		}

		unset($info['id']);
		$this->DB->insert( 'gallery_categories', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add permissions entry
		//-----------------------------------------

		foreach ($perms as $key => $value)
		{
			if ($value != '*')
			{
				$save = array();
				foreach (explode(',', $value) as $pset)
				{
					if ($pset)
					{
						$save[] = $this->getLink($pset, 'forum_perms');
					}
				}
				$perms[$key] = implode(',', $save);
			}
		}

		$this->addToPermIndex('cat', $inserted_id, $perms, $id);

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'gallery_categories');

		//-----------------------------------------
		// Sort out children
		//-----------------------------------------

		$this->DB->update('gallery_categories', array('parent' => $inserted_id), 'conv_parent='.$id);

		return true;
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
	public function convertAlbum($id, $info, $skip_cat_link=false)
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

		//-----------------------------------------
		// Link
		//-----------------------------------------

		$info['member_id'] = $info['member_id'] ? $this->getLink($info['member_id'], 'members', false, $this->useLocalLink) : 0;
		$info['category_id'] = ($skip_cat_link) ? $info['category_id'] : $this->getLink($info['category_id'], 'gallery_categories');

		// This will be sorted out in the rebuild
		unset($info['last_pic_id']);

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['id']);
		$this->DB->insert( 'gallery_albums', $info );
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
	 * @param 	array 		Custom field data to insert to table
	 * @param	boolean		If true, loads file data from database, rather than move file
	 * @return 	boolean		Success or fail
	 **/
	public function convertImage($id, $info, $path, $custom_fields, $db=false)
	{
		// Check we have a path
		if (!$this->settings['gallery_images_path'])
		{
			$this->logError($id, 'Your IP.Gallery uploads path has not been configured');
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
		if (!$info['category_id'] and !$info['album_id'])
		{
			$this->logError($id, 'No category or album ID provided');
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
							 'category_id'	 =>($info['category_id']) ? $this->getLink($info['category_id'], 'gallery_categories') : 0,
							 'album_id'		 => ($info['album_id']) ? $this->getLink($info['album_id'], 'gallery_albums') : 0,
							 'caption'		 => $info['caption'] ? $info['caption'] : 'No caption',
							 'file_size'	 => $info['file_size'] ? $info['file_size'] : 2,
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

		$this->_loadMediaCache();

		require_once IPS_KERNEL_PATH . 'classUpload.php';
		$upload = new classUpload();

		$allowed_ext = array();

		foreach( $this->media_thumb_cache as $k => $v )
		{
			if( !$v['allowed'] )
			{
				continue;
			}

			if( $v['default_type'] == 0 AND !$allow_media )
			{
				continue;
			}

			$allowed_ext[] = str_replace( ".", "", $k );
		}

		$dir = "";

		if ( $this->settings['gallery_dir_images'] )
		{
			$dir = $this->DB->buildAndFetch( array( 'select' => 'directory',
													'from'	 => 'gallery_images',
													'order'  => "id DESC",
													'limit'  => array( 0, 1 ) ) );

		  	$dir = $dir['directory'];

		  	if ( !is_dir( $this->settings['gallery_images_path'].'/'.$dir ) )
		  	{
			  	$dir = '';
		  	}

		  	$total = $this->DB->buildAndFetch( array( 'select' 	=> 'COUNT(directory) AS files',
		  											  'from'		=> 'gallery_images',
		  											  'where'  	=> "directory='{$dir}'" ) );

		  	if( $total['files'] >= $this->settings['gallery_dir_images'] || ! $total['files'] )
		  	{
			 	$dir = time();

			 	@mkdir( $this->settings['gallery_images_path'].'/'.$dir, 0777 );
			 	@chmod( $this->settings['gallery_images_path'].'/'.$dir, 0777 );

			 	@touch( $this->settings['gallery_images_path'].'/'.$dir.'/index.html' );
		  	}

		  	$dir = ( $dir ) ? "{$dir}/" : "";
		  	$imageArray['directory'] = str_replace( "/", "", $dir );
		}

		$ext = $upload->_getFileExtension( $info['file_name'] );

		if( !in_array( $ext, $allowed_ext ) )
		{
			$this->logError($id, "Invalid_mime_type for file name: {$info['file_name']}" );
			return false;
		}

		$new_name = "gallery_{$info['member_id']}_" . ($info['album_id'] > 0 ? $info['album_id'] : $info['category_id']) . "_" . time()%$imageArray['file_size'] . '.' . $ext;
		$imageArray['masked_file_name'] = $new_name;
		$new_file = $this->settings['gallery_images_path'] . '/' . $dir . $new_name;

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
			$meta_data = array_merge( $meta_data, $this->registry->glib->extractExif( $new_file ) );
		}

		if ( $this->settings['gallery_iptc'] )
		{
			$meta_data = array_merge( $meta_data, $this->registry->glib->extractIptc( $new_file ) );
		}
		$imageArray['metadata'] = serialize($meta_data);

		//-------------------------------------------------------------
		// Pass to library
		//-------------------------------------------------------------
		$media 	= 0;
		$ext	= '.' . $ext;
		$imageArray['media'] = $this->media_thumb_cache[ $ext ]['default_type'] ? 0 : 1;

		$image = array(	'media'				=> $imageArray['media'],
						'directory'			=> $dir,
						'masked_file_name'	=> $new_name );

		if ( !$imageArray['media'] )
		{
			$this->registry->glib->rebuildImage( $image, FALSE, TRUE );
		}

		$imageArray['medium_file_name'] = $this->registry->glib->did_medium ? 'med_' . $new_name : '';
		$imageArray['file_type'] = $this->registry->glib->getImageType( $new_file );
		$imageArray['thumbnail'] = $this->registry->glib->did_thumb ? $this->registry->glib->did_thumb : 0;

		//-----------------------------------------
		// Insert
		//-----------------------------------------
		foreach($custom_fields as $key => $value)
		{
			if(preg_match('/field_(.+)/', $key, $matches))
			{
				$newKey = $this->getLink($matches[1], 'gallery_form_fields');
				if ($newKey)
				{
					$imageArray['field_'.$newKey] = $value;
				}
			}
		}

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
	 * Convert an eCard Log
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertECard($id, $info)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------

		if (!$id)
		{
			$this->logError($id, 'No ID number provided');
			return false;
		}
		if (!$info['member_id'])
		{
			$this->logError($id, 'No member ID provided');
			return false;
		}
		if (!$info['title'])
		{
			$this->logError($id, 'No title provided');
			return false;
		}
		if (!$info['msg'])
		{
			$this->logError($id, 'No message provided');
			return false;
		}

		//-----------------------------------------
		// Link
		//-----------------------------------------

		$info['member_id'] = $this->getLink($info['member_id'], 'members', false, $this->useLocalLink);
		$info['img_id'] = $this->getLink($info['img_id'], 'gallery_images');

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['id']);
		$this->DB->insert( 'gallery_ecardlog', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'gallery_ecardlog');

		return true;
	}

	/**
	 * Convert a fave
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertFav($id, $info)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------

		if (!$id)
		{
			$this->logError($id, '(FAVOURITE) No ID number provided');
			return false;
		}
		if (!$info['member_id'])
		{
			$this->logError($id, '(FAVOURITE) No member ID provided');
			return false;
		}
		if (!$info['img_id'])
		{
			$this->logError($id, '(FAVOURITE) No image ID provided');
			return false;
		}

		//-----------------------------------------
		// Link
		//-----------------------------------------

		$info['member_id'] = $this->getLink($info['member_id'], 'members', false, $this->useLocalLink);
		$info['img_id'] = $this->getLink($info['img_id'], 'gallery_images');

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['id']);
		$this->DB->insert( 'gallery_favorites', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'gallery_favorites');

		return true;
	}

	/**
	 * Convert a subscription
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertSub($id, $info)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------

		if (!$id)
		{
			$this->logError($id, 'No ID number provided');
			return false;
		}
		if (!$info['sub_mid'])
		{
			$this->logError($id, 'No member ID provided');
			return false;
		}
		if (!$info['sub_type'])
		{
			$this->logError($id, 'No type provided');
			return false;
		}
		if (!$info['sub_toid'])
		{
			$this->logError($id, 'No item ID provided');
			return false;
		}

		//-----------------------------------------
		// Link
		//-----------------------------------------

		$info['sub_mid'] = $this->getLink($info['sub_mid'], 'members', false, $this->useLocalLink);

		switch($info['sub_type'])
		{
			case 'image':
				$info['sub_toid'] = $this->getLink($info['sub_toid'], 'gallery_images');
				break;

			case 'cat':
				$info['sub_toid'] = $this->getLink($info['sub_toid'], 'gallery_categories');
				break;

			case 'album':
				$info['sub_toid'] = $this->getLink($info['sub_toid'], 'gallery_albums');
				break;

			default:
				$this->logError($id, 'Invalid type');
				return false;
		}

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['sub_id']);
		$this->DB->insert( 'gallery_subscriptions', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'gallery_subscriptions');

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
		if (!$info['img_id'])
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
		$info['img_id'] = $this->getLink($info['img_id'], 'gallery_images');

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
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @param 	string 		How to handle duplicates ('local' or 'remote')
	 * @return 	boolean		Success or fail
	 **/
	public function convertMediaType($id, $info, $dupes)
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
	}

	/**
	 * Convert a form field
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertFormField($id, $info)
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
	}

	/**
	 * Loads the media cache
	 *
	 * @access	public
	 * @return	void
	 */
	private function _loadMediaCache()
	{
		 if( ! is_array($this->media_thumb_cache) OR ! count($this->media_thumb_cache) )
		 {
			if( !is_array( $this->caches['gallery_media_types'] ) OR !count( $this->caches['gallery_media_types'] ) )
			{
				$this->cache->getCache( 'gallery_media_types' );

				if( !is_array( $this->caches['gallery_media_types'] ) OR !count( $this->caches['gallery_media_types'] ) )
				{
					$this->cache->rebuildCache( 'gallery_media_types', 'gallery' );
				}
			}

			if( count($this->caches['gallery_media_types']) AND is_array($this->caches['gallery_media_types']) )
			{
				foreach( $this->caches['gallery_media_types'] as $j )
				{
					$exts = explode( ",", $j['extension'] );

					foreach( $exts as $ext )
					{
						$this->media_thumb_cache[$ext] = $j;
					}
				}
			}
		}
	}
}
