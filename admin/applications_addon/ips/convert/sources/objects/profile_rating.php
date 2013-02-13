
/**
 * Convert profile ratings
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertProfileRating($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['rating_for_member_id'])
	{
		$this->logError($id, 'No member ID provided');
		return false;
	}
	if (!$info['rating_by_member_id'])
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

	$info['rating_for_member_id'] = $this->getLink($info['rating_for_member_id'], 'members');
	$info['rating_by_member_id'] = $this->getLink($info['rating_by_member_id'], 'members');

	unset($info['rating_id']);
	$this->DB->insert( 'profile_ratings', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'profile_ratings');

	return true;
}
