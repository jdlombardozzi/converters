
/**
 * Convert entry
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param 	boolean		If true, will not get link for blog id
 * @return 	boolean		Success or fail
 **/
public function convertEntry($id, $info, $skip_blog_link=FALSE)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['blog_id'])
	{
		$this->logError($id, 'No blog ID provided');
		return false;
	}
	if (!$info['entry_author_id'])
	{
		$this->logError($id, 'No author ID provided');
		return false;
	}
	if (!$info['entry_name'])
	{
		$this->logError($id, 'No title provided');
		return false;
	}
	if (!$info['entry'])
	{
		$this->logError($id, 'No entry provided');
		return false;
	}
	if (!$info['entry_status'])
	{
		$info['entry_status'] = 'published';
	}

	//-----------------------------------------
	// Link
	//-----------------------------------------

	unset($info['entry_last_comment']);

	$info['blog_id'] = ($skip_blog_link) ? $info['blog_id'] : $this->getLink($info['blog_id'], 'blog_blogs');
	$info['entry_author_id'] = $this->getLink($info['entry_author_id'], 'members', false, true);
	$info['entry_last_comment_mid'] = ($info['entry_last_comment_mid']) ? $this->getLink($info['entry_last_comment_mid'], 'members', false, true) : 0;

	unset($info['entry_gallery_album']);

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['entry_id']);
	$this->DB->insert( 'blog_entries', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'blog_entries');

	return true;
}
