
/**
 * Convert profile comment
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertProfileComment($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['status_member_id'])
	{
		$this->logError($id, 'No member ID provided');
		return false;
	}
	if (!$info['status_author_id'])
	{
		$this->logError($id, 'No author ID provided');
		return false;
	}
	if (!$info['status_content'])
	{
		$this->logError($id, 'No comment provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['status_member_id'] = $this->getLink($info['status_member_id'], 'members');
	$info['status_author_id'] = $this->getLink($info['status_author_id'], 'members');

	unset($info['status_id']);
	$this->DB->insert( 'member_status_updates', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'profile_comments');

	return true;
}

/**
 * Convert profile comment replies
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertProfileCommentReply ( $id, $info )
{
	if ( !$id )
	{
		$this->logError ( $id, 'No Reply ID provided.' );
		return false;
	}

	if ( !$info['reply_status_id'] )
	{
		$this->logError ( $id, 'No Comment/Status ID Provided.' );
		return false;
	}

	if ( !$info['reply_member_id'] )
	{
		$this->logError ( $id, 'No Reply Member ID provided.' );
		return false;
	}

	if ( !$info['reply_content'] )
	{
		$this->logError ( $id, 'No Reply Content provided.' );
		return false;
	}

	// Yay links!
	$info['reply_status_id']	= $this->getLink ( $info['reply_status_id'], 'profile_comments' );
	$info['reply_member_id']	= $this->getLink ( $info['reply_member_id'], 'members' );

	// ... aaaaaaand insert.
	unset ( $info['reply_id'] );
	$this->DB->insert ( 'member_status_replies', $info );
	$inserted_id = $this->DB->getInsertId ( );

	// Add our link.
	$this->addLink ( $inserted_id, $id, 'profile_comment_replies' );

	// And we're done!
	return true;
}
