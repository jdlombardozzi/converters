
/**
 * Convert an announcement
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertAnnouncement($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['announce_title'])
	{
		$this->logError($id, 'No title provided');
		return false;
	}
	if (!$info['announce_post'])
	{
		$this->logError($id, 'No content provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	// Linky-loo
	if ($info['announce_forum'] != '*')
	{
		foreach (explode(',', $info['announce_forum']) as $fid)
		{
			$forums[] = $this->getLink($fid, 'forums');
		}
		$info['announce_forum'] = implode(',', $forums);
	}
	$info['announce_member_id'] = $this->getLink($info['announce_member_id'], 'members');

	// Go go go
	unset($info['announce_id']);
	$this->DB->insert( 'announcements', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'announcements');

	return true;
}
