
/**
 * Convert topic subscription
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertTopicSubscription($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------
	$link = $this->getLink( $info['topic_id'], 'topics', true );

	if ( ! $link )
	{
		$this->logError($id, 'No topic ID provided');
		return false;
	}

	$this->convertFollow ( array (
		'like_app'			=> 'forums',
		'like_area'			=> 'topics',
		'like_rel_id'		=> $this->getLink( $info['topic_id'], 'topics' ),
		'like_member_id'	=> $this->getLink( $info['member_id'], 'members', true ),
	) );

	/*if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['member_id'])
	{
		$this->logError($id, 'No member ID provided');
		return false;
	}
	if (!$info['topic_id'])
	{
		$this->logError($id, 'No topic ID provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['member_id'] = $this->getLink($info['member_id'], 'members');
	$info['topic_id'] = $this->getLink($info['topic_id'], 'topics');

	if ( !$info['member_id'] )
	{
		$this->logError($id, 'Member does not exist.');
		return FALSE;
	}

	if ( !$info['topic_id'] )
	{
		$this->logError($id, 'Topic does not exist.');
		return FALSE;
	}

	unset($info['trid']);
	$this->DB->insert( 'tracker', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'tracker');*/

	return true;
}
