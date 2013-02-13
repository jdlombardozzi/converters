
/**
 * Convert bad words
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertBadword($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['type'])
	{
		$this->logError($id, 'No word provided');
		return false;
	}
	if (!$info['swop'])
	{
		$this->logError($id, 'No replacement provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['wid']);
	$this->DB->insert( 'badwords', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'badwords');

	return true;
}

/**
 * Convert ban filters
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertBan($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['ban_type'])
	{
		$this->logError($id, 'No type provided');
		return false;
	}
	if (!$info['ban_content'])
	{
		$this->logError($id, 'No content provided');
		return false;
	}
	if ( !$info['ban_date'] )
	{
		$info['ban_date'] = time();
	}

	unset($info['ban_nocache']);
	unset($info['ban_id']);
	$this->DB->insert( 'banfilters', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'banfilters');

	return true;
}
