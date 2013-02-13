
/**
 * Convert display name history
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertDname($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['dname_member_id'])
	{
		$this->logError($id, 'No member ID provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['dname_member_id'] = $this->getLink($info['dname_member_id'], 'members');

	unset($info['dname_id']);
	$this->DB->insert( 'dnames_change', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'dnames_change');

	return true;
}
