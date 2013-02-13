
/**
 * Convert custom profile field group
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param 	boolean 	If true, will return error instead of logging
 * @return 	boolean		Success or fail
 **/
public function convertPFieldGroup($id, $info, $return=false)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$error = '(GROUP) No ID number provided';
		if ($return)
		{
			$this->error($error);
		}
		$this->logError($id, $error);
		return false;
	}
	if (!$info['pf_group_name'])
	{
		$error = '(GROUP) No name provided';
		if ($return)
		{
			$this->error($error);
		}
		$this->logError($id, $error);
		return false;
	}
	if (!$info['pf_group_key'])
	{
		$error = '(GROUP) No key provided';
		if ($return)
		{
			$this->error($error);
		}
		$this->logError($id, $error);
		return false;
	}

	//-----------------------------------------
	// Handle duplicates
	//-----------------------------------------

	$dupe = $this->DB->buildAndFetch( array( 'select' => 'pf_group_id', 'from' => 'pfields_groups', 'where' => "pf_group_key = '{$info['pf_group_key']}'" ) );
	if ($dupe)
	{
		$this->addLink($dupe['pf_group_id'], $id, 'pfields_groups');
		if ($return)
		{
			return $dupe['pf_group_id'];
		}
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['pf_group_id']);
	$this->DB->insert( 'pfields_groups', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'pfields_groups');

	if ($return)
	{
		return $inserted_id;
	}
	else
	{
		return true;
	}

}
