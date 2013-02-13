
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
