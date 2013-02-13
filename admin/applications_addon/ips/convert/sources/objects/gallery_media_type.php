
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
