
/**
 * Convert a comment
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertComment($id, $info)
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
	/*if (!$info['member_id'])
	{
		$this->logError($id, 'No member ID number provided');
		return false;
	}*/
	if (!$info['comment_text'])
	{
		$this->logError($id, 'No comment provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['entry_id'] = $this->getLink($info['entry_id'], 'blog_entries');
	$info['member_id'] = $info['member_id'] > 0 ? $this->getLink($info['member_id'], 'members', false, true) : 0;

	// Unset 3.2 removed fields
	unset($info['comment_id']);
	unset($info['comment_use_emo']);
	unset($info['comment_edit_name']);

	$this->DB->insert( 'blog_comments', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'blog_comments');

	return true;
}
