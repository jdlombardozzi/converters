
/**
 * Convert friends
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertFriend($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['friends_member_id'])
	{
		$this->logError($id, 'No member ID provided');
		return false;
	}
	if (!$info['friends_friend_id'])
	{
		$this->logError($id, 'No friend ID provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['friends_member_id'] = $this->getLink($info['friends_member_id'], 'members');
	$info['friends_friend_id'] = $this->getLink($info['friends_friend_id'], 'members');

	if (!$info['friends_member_id'])
	{
		$this->logError($id, 'No member ID found');
		return false;
	}
	if (!$info['friends_friend_id'])
	{
		$this->logError($id, 'No friend ID found');
		return false;
	}

	unset($info['friends_id']);
	$this->DB->insert( 'profile_friends', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Recache
	//-----------------------------------------

	$classToLoad            = IPSLib::loadLibrary( IPS_ROOT_PATH . '/applications/members/sources/friends.php', 'profileFriendsLib' );
	$profileFriendsLib      = new $classToLoad( ipsRegistry::instance() );

	$profileFriendsLib->recacheFriends( $info['friends_member_id'] );
	$profileFriendsLib->recacheFriends( $info['friends_friend_id'] );

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'profile_friends');

	return true;
}
