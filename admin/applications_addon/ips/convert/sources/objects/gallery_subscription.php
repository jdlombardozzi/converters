
/**
 * Convert a subscription
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertSub($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['sub_mid'])
	{
		$this->logError($id, 'No member ID provided');
		return false;
	}
	if (!$info['sub_type'])
	{
		$this->logError($id, 'No type provided');
		return false;
	}
	if (!$info['sub_toid'])
	{
		$this->logError($id, 'No item ID provided');
		return false;
	}

	//-----------------------------------------
	// Link
	//-----------------------------------------

	$info['sub_mid'] = $this->getLink($info['sub_mid'], 'members', false, $this->useLocalLink);

	switch($info['sub_type'])
	{
		case 'image':
			$info['sub_toid'] = $this->getLink($info['sub_toid'], 'gallery_images');
			break;

		case 'cat':
			$info['sub_toid'] = $this->getLink($info['sub_toid'], 'gallery_categories');
			break;

		case 'album':
			$info['sub_toid'] = $this->getLink($info['sub_toid'], 'gallery_albums');
			break;

		default:
			$this->logError($id, 'Invalid type');
			return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['sub_id']);
	$this->DB->insert( 'gallery_subscriptions', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'gallery_subscriptions');

	return true;
}
