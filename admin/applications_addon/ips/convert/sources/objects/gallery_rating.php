
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
