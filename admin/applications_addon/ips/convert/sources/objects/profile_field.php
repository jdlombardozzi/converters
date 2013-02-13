
/**
 * Convert custom profile field
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertPField($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['pf_title'])
	{
		$this->logError($id, 'No title provided');
		return false;
	}
	if (!$info['pf_key'])
	{
		$this->logError($id, 'No key provided');
		return false;
	}

	//-----------------------------------------
	// Handle duplicates
	//-----------------------------------------

	$dupe = $this->DB->buildAndFetch( array( 'select' => 'pf_id', 'from' => 'pfields_data', 'where' => "pf_key = '{$info['pf_key']}'" ) );
	if ($dupe)
	{
		$this->addLink($dupe['pf_id'], $id, 'pfields', 1);
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	$info['pf_group_id'] = $this->getLink($info['pf_group_id'], 'pfields_groups');

	unset($info['pf_id']);
	$this->DB->insert( 'pfields_data', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// We need a column in pfields_content
	//-----------------------------------------

	$this->DB->addField( 'pfields_content', "field_$inserted_id", 'text' );
	$this->DB->optimize( 'pfields_content' );

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'pfields');

	return true;
}
