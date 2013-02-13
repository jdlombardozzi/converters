
/**
 * Convert a moderator
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertModerator($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$info['forum_id'])
	{
		$this->logError($id, 'No forum ID number provided');
		return false;
	}
	if (!$info['member_name'] and !$info['group_name'])
	{
		$this->logError($id, 'No member or group name provided');
		return false;
	}
	if (!$info['member_id'] and !$info['group_id'])
	{
		$this->logError($id, 'No member or group ID number provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	// Convert those forum ids
	$exploded = explode(',', $info['forum_id']);
	foreach ($exploded as $id)
	{
		if (!$id)
		{
			continue;
		}
		$linked[] = $this->getLink($id, 'forums');
	}
	if (empty($linked))
	{
		$this->logError($id, 'No valid forum ID numbers found');
		return false;
	}
	$info['forum_id'] = implode(',', $linked);

	// Is this a member or a group?
	if ($info['member_id'] and $info['member_id'] != -1)
	{
		$info['member_id'] = $this->getLink($info['member_id'], 'members');
		unset($info['group_id']);
		unset($info['group_name']);
	}
	else
	{
		$info['group_id'] = $this->getLink($info['group_id'], 'groups');
		$info['member_id'] = -1;
		$info['member_name'] = -1;
	}

	unset($info['mid']);
	unset($info['edit_user']);

	$this->DB->insert( 'moderators', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'moderators');

	return true;
}
