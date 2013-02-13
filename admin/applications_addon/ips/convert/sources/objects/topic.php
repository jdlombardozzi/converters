
/**
 * Convert a topic
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param	boolean		Load member IDs from parent app
 * @return 	boolean		Success or fail
 **/
public function convertTopic($id, $info, $parent=false)
{
	//-----------------------------------------
	// We don't bother with shadow topics
	//-----------------------------------------

	if ($info['topic_status'] == 'link')
	{
		continue;
	}

	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No Topic ID number provided');
		return false;
	}
	if (!$info['title'])
	{
		$this->logError($id, 'No title provided');
		return false;
	}
	// if (!$info['starter_id'])
	// {
	// 	$this->logError($id, 'No topic starter ID number provided');
	// 	return false;
	// }
	if (!$info['forum_id'])
	{
		$this->logError($id, 'No forum ID number provided');
		return false;
	}

	//-----------------------------------------
	// Link
	//-----------------------------------------

	$info['starter_id'] = ($info['starter_id']) ? $this->getLink($info['starter_id'], 'members', false, $parent) : 0;
	$info['last_poster_id'] = ($info['last_poster_id']) ? $this->getLink($info['last_poster_id'], 'members', false, $parent) : 0;
	$info['forum_id'] = $this->getLink($info['forum_id'], 'forums');

	//-----------------------------------------
	// Build SEO title
	//-----------------------------------------

	if (!$info['title_seo'])
	{
		$info['title_seo'] = IPSText::makeSeoTitle($info['title']);
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['approved']	= isset($info['approved']) ? $info['approved'] : 1;
	$info['pinned']		= isset($info['pinned']) ? $info['pinned'] : 0;

	// Fix integers since STRICT likes to complain...
	$info['starter_id']		= intval($info['starter_id']);
	$info['last_poster_id']	= intval($info['last_poster_id']);

	unset($info['tid']);
	$this->DB->insert( 'topics', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'topics');

	return true;
}
