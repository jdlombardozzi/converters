
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
