
/**
 * Convert an emoticon
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param 	string 		How to handle duplicates ('local' or 'remote')
 * @param 	string 		Path to emoticons folder
 * @return 	boolean		Success or fail
 **/
public function convertEmoticon($id, $info, $dupes, $path)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['typed'])
	{
		$this->logError($id, 'No code provided');
		return false;
	}
	if (!$info['image'])
	{
		$this->logError($id, 'No code provided');
		return false;
	}
	if (!$info['emo_set'])
	{
		$info['emo_set'] = 'default';
	}

	//-----------------------------------------
	// Handle duplicates
	//-----------------------------------------

	$dupe = $this->DB->buildAndFetch( array( 'select' => 'id', 'from' => 'emoticons', 'where' => "typed = '".addslashes($info['typed'])."' AND emo_set='{$info['emo_set']}'" ) );
	if ($dupe)
	{
		if ($dupes == 'local')
		{
			return false;
		}
		else
		{
			$this->DB->delete('emoticons', "id={$dupe['id']}");
		}
	}

	//-----------------------------------------
	// Move the file
	//-----------------------------------------

	$emo_dir = DOC_IPS_ROOT_PATH.'public/style_emoticons/'.$info['emo_set'];

	// Check we have a path
	if (!is_dir($emo_dir) and !mkdir($emo_dir))
	{
		$this->logError($id, 'Bad directory:'.$emo_dir);
		return false;
	}

	$this->moveFiles(array($info['image']), $path, $emo_dir);

	// Convert special chars
	$info['typed'] = IPSText::htmlspecialchars($info['typed']);

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['id']);
	$this->DB->insert( 'emoticons', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'emoticons');

	return true;
}
