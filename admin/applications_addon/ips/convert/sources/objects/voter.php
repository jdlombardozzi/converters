

/**
 * Convert a poll voter
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param	boolean		Load member IDs from parent app
 * @return 	boolean		Success or fail
 **/
public function convertPollVoter($id, $info, $parent=false)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, '(VOTE) No ID number provided');
		return false;
	}
	if (!$info['tid'])
	{
		$this->logError($id, '(VOTE) No topic ID number provided');
		return false;
	}
	if (!$info['member_id'])
	{
		$this->logError($id, '(VOTE) No voter ID number provided');
		return false;
	}
	if (!$info['member_choices'])
	{
		$this->logError($id, '(VOTE) No answers provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['tid'] = $this->getLink($info['tid'], 'topics');
	$info['member_id'] = $this->getLink($info['member_id'], 'members', false, $parent);
	$info['forum_id'] = $this->getLink($info['forum_id'], 'forums');

	if (!$info['tid'])
	{
		$this->logError($id, '(VOTE) Topic not found.');
		return false;
	}

	if (!$info['member_id'])
	{
		$this->logError($id, '(VOTE) Member not found.');
		return false;
	}

	if (!$info['forum_id'])
	{
		$this->logError($id, '(VOTE) Forum not found.');
		return false;
	}

	unset($info['vid']);
	$this->DB->insert( 'voters', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'voters');

	return true;
}
