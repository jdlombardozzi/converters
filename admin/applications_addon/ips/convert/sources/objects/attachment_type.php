
/**
 * Convert a mimetype
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertAttachType($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['atype_extension'])
	{
		$this->logError($id, 'No extension provided');
		return false;
	}
	if (!$info['atype_mimetype'])
	{
		$this->logError($id, 'No mime type provided');
		return false;
	}
	if (!$info['atype_img'] or !file_exists(DOC_IPS_ROOT_PATH.$info['atype_img']))
	{
		$info['atype_img'] = 'style_extra/mime_types/unknown.gif';
	}

	//-----------------------------------------
	// Handle duplicates
	//-----------------------------------------

	if ($this->DB->buildAndFetch( array( 'select' => 'atype_id', 'from' => 'attachments_type', 'where' => "atype_extension = '{$info['atype_extension']}'" ) ))
	{
		// $this->logError($id, 'Type already exists');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['atype_id']);
	$this->DB->insert( 'attachments_type', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'attachments_type');

	return true;
}
