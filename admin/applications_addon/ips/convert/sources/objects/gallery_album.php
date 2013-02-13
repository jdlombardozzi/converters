
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
