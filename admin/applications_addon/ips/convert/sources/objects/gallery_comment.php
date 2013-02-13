
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
