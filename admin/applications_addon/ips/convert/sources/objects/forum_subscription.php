
/**
 * Convert forum subscription
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertForumSubscription($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------
	$link = $this->getLink( $info['forum_id'], 'forums', true );

	if ( ! $link )
	{
		$this->logError($id, 'No forum ID provided');
		return false;
	}

	$this->convertFollow ( array (
		'like_app'			=> 'forums',
		'like_area'			=> 'forums',
		'like_rel_id'		=> $link,
		'like_member_id'	=> $this->getLink( $info['member_id'], 'members', true ),
	) );

	return true;
}
