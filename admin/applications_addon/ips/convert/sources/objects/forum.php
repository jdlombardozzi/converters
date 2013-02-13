
/**
 * Convert a forum
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param 	array 		Permissions index data
 * @return 	boolean		Success or fail
 **/
public function convertForum($id, $info, $perms)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No Forum ID number provided');
		return false;
	}
	if (!$info['name'])
	{
		$this->logError($id, 'No name provided');
		return false;
	}
	if (!$info['parent_id'])
	{
		$this->logError($id, 'No parent ID number provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	// These things will be fixed on rebuild - probably best to have them blank now to prevent confusion
	unset($info['last_poster_id']);
	unset($info['last_id']);

	// We need to sort out the parent id
	if ($info['parent_id'] != -1)
	{
		$parent = $this->getLink($info['parent_id'], 'forums', true);
		if ($parent)
		{
			$info['parent_id'] = $parent;
		}
		else
		{
			$info['conv_parent'] = $info['parent_id'];
			unset($info['parent_id']);
		}
	}

	if ( $info['parent_id'] == -1 ) $info['permission_showtopic'] = 1;

	// Make sure we don't have any fields we shouldn't have
	foreach (array('perm_id', 'app', 'perm_type', 'perm_type_id', 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7', 'owner_only', 'friend_only', 'authorized_users') as $unset)
	{
		unset($info[$unset]);
	}

	// Make sure we have a cutoff date
	$info['prune'] = isset($info['prune']) ? $info['prune'] : 100;

	// Make sure we use BBCode
	$info['use_ibc'] = isset($info['use_ibc']) ? $info['use_ibc'] : '1';

	// MSSQL makes me want to cry...
	$info['min_posts_view'] = $info['min_posts_view'] ? $info['min_posts_view'] : 0;
	$info['min_posts_post'] = $info['min_posts_post'] ? $info['min_posts_post'] : 0;

	// Post count increment
	$info['inc_postcount']	= isset($info['inc_postcount']) ? $info['inc_postcount'] : 1;

	// Legacy 3.1 column. Just in case I miss removing them from somewhere.
	unset ( $info['status'] );

	// And do it!
	unset($info['id']);
	$this->DB->insert( 'forums', $info );
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

	$this->addToPermIndex('forum', $inserted_id, $perms, $id);

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'forums');

	//-----------------------------------------
	// Sort out children
	//-----------------------------------------

	$this->DB->update('forums', array('parent_id' => $inserted_id), "conv_parent='$id'");

	return true;
}
