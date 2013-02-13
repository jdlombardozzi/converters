
/**
 * Convert a poll voter
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertPollVoter($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, '(VOTE) No ID number provided');
		return false;
	}
	if (!$info['entry_id'])
	{
		$this->logError($id, '(VOTE) No entry ID number provided');
		return false;
	}
	if (!$info['member_id'])
	{
		$this->logError($id, '(VOTE) No voter ID number provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['entry_id'] = $this->getLink($info['entry_id'], 'blog_entries');
	$info['member_id'] = $this->getLink($info['member_id'], 'members', false, true);

	unset($info['vote_id']);
	$this->DB->insert( 'blog_voters', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'blog_voters');

	return true;
}
