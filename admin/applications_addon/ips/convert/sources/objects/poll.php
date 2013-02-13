
/**
 * Convert a poll
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param	boolean		Load member IDs from parent app
 * @return 	boolean		Success or fail
 **/
public function convertPoll($id, $info, $parent=false)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['tid'])
	{
		$this->logError($id, 'No topic ID number provided');
		return false;
	}
	// if (!$info['starter_id'])
	// {
	// 	$this->logError($id, 'No author ID number provided');
	// 	return false;
	// }

	// The title '0' triggered the old check, do not revert to !
	if ( $info['poll_question'] == '' )
	{
		$this->logError($id, 'No poll title provided');
		return false;
	}
	if (!$info['choices'])
	{
		$this->logError($id, 'No questions provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['tid'] = $this->getLink($info['tid'], 'topics');
	$info['starter_id'] = ($info['starter_id']) ? $this->getLink($info['starter_id'], 'members', false, $parent) : 0;
	$info['forum_id'] = $this->getLink($info['forum_id'], 'forums');

	unset($info['pid']);
	$this->DB->insert( 'polls', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'polls');

	return true;
}