
/**
 * Convert a rank
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param 	string 		How to handle duplicates ('local' or 'remote')
 * @return 	boolean		Success or fail
 **/
public function convertRank($id, $info, $dupes)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['title'])
	{
		$this->logError($id, 'No title provided');
		return false;
	}

	//-----------------------------------------
	// Handle duplicates
	//-----------------------------------------

	$dupe = $this->DB->buildAndFetch( array( 'select' => 'id', 'from' => 'titles', 'where' => "posts = '{$info['posts']}'" ) );
	if ($dupe)
	{
		if ($dupes == 'local')
		{
			return false;
		}
		else
		{
			$this->DB->delete('titles', "id={$dupe['id']}");
		}
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['id']);
	$this->DB->insert( 'titles', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'ranks');

	return true;
}
