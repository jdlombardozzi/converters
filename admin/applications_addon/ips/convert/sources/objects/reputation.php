
/**
 * Convert a reputation vote
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertRep($id, $info)
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
	if (!$info['type_id'])
	{
		$this->logError($id, 'No post ID provided');
		return false;
	}
	if (!$info['rep_rating'])
	{
		$this->logError($id, 'No rep provided');
		return false;
	}
	if (!$info['app'] or !$info['type'])
	{
		$info['app'] = 'forums';
		$info['type'] = 'pid';
	}

	//-----------------------------------------
	// Link
	//-----------------------------------------

	$info['member_id'] = $this->getLink($info['member_id'], 'members');
	$info['type_id'] = $this->getLink($info['type_id'], 'posts');

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['id']);
	$this->DB->insert( 'reputation_index', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'reputation_index');

	return true;
}
