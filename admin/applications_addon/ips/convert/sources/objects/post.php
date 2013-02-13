
/**
 * Convert a post
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param	boolean		Load member IDs from parent app
 * @return 	boolean		Success or fail
 **/
public function convertPost($id, $info, $parent=false)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No Post ID number provided');
		return false;
	}
	if (!$info['post'])
	{
		$this->logError($id, 'No post provided');
		return false;
	}
	if (!$info['topic_id'])
	{
		$this->logError($id, 'No topic ID provided (Post)');
		return false;
	}
	// if (!$info['author_id'])
	// {
	// 	$this->logError($id, 'No poster ID number provided');
	// 	return false;
	// }

	// Convert to entities
	$info['post'] = str_replace("\\", "&#092;", $info['post']);

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['author_id'] = ($info['author_id']) ? $this->getLink($info['author_id'], 'members', false, $parent) : 0;
	$info['topic_id'] = $this->getLink($info['topic_id'], 'topics');

	// Fix integers since STRICT likes to complain...
	$info['author_id'] = intval($info['author_id']);

	unset($info['icon_id']);
	unset($info['post_title']);

	if (!$info['topic_id'])
	{
		$this->logError($id, 'Topic not found.');
		return FALSE;
	}

	//if (!$info['author_id'])
	//{
	//	$this->logError($id, 'Author not found.');
	//	return FALSE;
	//}

	$rep_points = $info['rep_points'];
	unset($info['rep_points']);

	unset($info['pid']);
	$this->DB->insert( 'posts', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// We've got a reputation to think about here!
	//-----------------------------------------

	if ($rep_points)
	{
		$rep_cache = array(
			'app' => 'forums',
			'type' => 'pid',
			'type_id' => $inserted_id,
			'rep_points' => $rep_points,
			);
		$this->DB->insert( 'reputation_cache', $rep_cache );
		$_inserted_id = $this->DB->getInsertId();
		$this->addLink($_inserted_id, $id, 'reputation_cache');
	}

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'posts');

	return true;
}
