
/**
 * Convert a poll
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertPoll($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['entry_id'])
	{
		$this->logError($id, 'No entry ID number provided');
		return false;
	}
	if (!$info['starter_id'])
	{
		$this->logError($id, 'No author ID number provided');
		return false;
	}
	if (!$info['poll_question'])
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

	$info['entry_id'] = $this->getLink($info['entry_id'], 'blog_entries');
	$info['starter_id'] = $this->getLink($info['starter_id'], 'members', false, true);

	unset($info['poll_id']);
	$this->DB->insert( 'blog_polls', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'blog_polls');

	return true;
}
