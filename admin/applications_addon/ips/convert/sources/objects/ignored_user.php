
/**
 * Convert ignore
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertIgnore($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['ignore_owner_id'])
	{
		$this->logError($id, 'No owner ID provided');
		return false;
	}
	if (!$info['ignore_ignore_id'])
	{
		$this->logError($id, 'No ignoring ID provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['ignore_owner_id'] = $this->getLink($info['ignore_owner_id'], 'members');
	$info['ignore_ignore_id'] = $this->getLink($info['ignore_ignore_id'], 'members');

	unset($info['ignore_id']);
	$this->DB->insert( 'ignored_users', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'ignored_users');

	return true;
}
