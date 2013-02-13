
/**
 * Convert topic ratings
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertTopicRating($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['rating_tid'])
	{
		$this->logError($id, 'No topic ID provided');
		return false;
	}
	if (!$info['rating_member_id'])
	{
		$this->logError($id, 'No rater ID provided');
		return false;
	}
	if (!$info['rating_value'])
	{
		$this->logError($id, 'No rating provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['rating_tid'] = $this->getLink($info['rating_tid'], 'topics');
	$info['rating_member_id'] = $this->getLink($info['rating_member_id'], 'members');

	unset($info['rating_id']);
	$this->DB->insert( 'topic_ratings', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'topic_ratings');

	return true;
}
