
/**
 * Convert a category
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param 	array 		Permissions index data
 * @return 	boolean		Success or fail
 **/
public function convertCategory($id, $info, $perms)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['name'])
	{
		$this->logError($id, 'No name provided');
		return false;
	}

	//-----------------------------------------
	// Link
	//-----------------------------------------

	$info['last_member_id'] = ($info['last_member_id']) ? $this->getLink($info['last_member_id'], 'members', false, $this->useLocalLink) : 0;

	// This will be sorted out in the rebuild
	unset($info['last_pic_id']);

	// We need to sort out the parent id
	if ($info['parent'] != 0)
	{
		$parent = $this->getLink($info['parent'], 'gallery_categories');
		if ($parent)
		{
			$info['parent'] = $parent;
		}
		else
		{
			$info['conv_parent'] = $info['parent'];
			unset($info['parent']);
		}
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	// Make sure we don't have any fields we shouldn't have
	foreach (array('perm_id', 'app', 'perm_type', 'perm_type_id', 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7', 'owner_only', 'friend_only', 'authorized_users') as $unset)
	{
		unset($info[$unset]);
	}

	unset($info['id']);
	$this->DB->insert( 'gallery_categories', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add permissions entry
	//-----------------------------------------

	foreach ($perms as $key => $value)
	{
		if ($value != '*')
		{
			$save = array();
			foreach (explode(',', $value) as $pset)
			{
				if ($pset)
				{
					$save[] = $this->getLink($pset, 'forum_perms');
				}
			}
			$perms[$key] = implode(',', $save);
		}
	}

	$this->addToPermIndex('cat', $inserted_id, $perms, $id);

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'gallery_categories');

	//-----------------------------------------
	// Sort out children
	//-----------------------------------------

	$this->DB->update('gallery_categories', array('parent' => $inserted_id), 'conv_parent='.$id);

	return true;
}
