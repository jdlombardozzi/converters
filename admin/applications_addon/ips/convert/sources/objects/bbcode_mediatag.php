
/**
 * Convert a [media] option
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param 	string 		How to handle duplicates ('local' or 'remote')
 * @return 	boolean		Success or fail
 **/
public function convertMediaTag($id, $info, $dupes)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, '(MEDIA) No ID number provided');
		return false;
	}
	if (!$info['mediatag_name'])
	{
		$this->logError($id, '(MEDIA) No title provided');
		return false;
	}
	if (!$info['mediatag_match'])
	{
		$this->logError($id, '(MEDIA) No match provided');
		return false;
	}
	if (!$info['mediatag_replace'])
	{
		$this->logError($id, '(MEDIA) No replacement provided');
		return false;
	}

	//-----------------------------------------
	// Handle duplicates
	//-----------------------------------------

	$dupe = $this->DB->buildAndFetch( array( 'select' => 'mediatag_id', 'from' => 'bbcode_mediatag', 'where' => "mediatag_match = '{$info['mediatag_match']}'" ) );
	if ($dupe)
	{
		if ($dupes == 'local')
		{
			return false;
		}
		else
		{
			$this->DB->delete('bbcode_mediatag', "id={$dupe['mediatag_id']}");
		}
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['mediatag_id']);
	$this->DB->insert( 'bbcode_mediatag', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'bbcode_mediatag');

	return true;
}
