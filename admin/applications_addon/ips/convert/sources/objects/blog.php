
/**
 * Convert theme
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @return 	boolean		Success or fail
 **/
public function convertTheme($id, $info)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['theme_css'] and !$theme['images'])
	{
		$this->logError($id, 'No CSS or images provided');
		return false;
	}
	if (!$info['theme_name'])
	{
		$this->logError($id, 'No name provided');
		return false;
	}

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['theme_id']);
	$this->DB->insert( 'blog_themes', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'blog_themes');

	return true;
}

/**
 * Convert theme
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param 	string 		Path to headers
 * @return 	boolean		Success or fail
 **/
public function convertHeader($id, $info, $header_path)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['header_image'])
	{
		$this->logError($id, 'No image provided');
		return false;
	}
	if (!$info['header_tile'])
	{
		$this->logError($id, 'No tile provided');
		return false;
	}

	//-----------------------------------------
	// Move
	//-----------------------------------------

	$this->moveFiles(array($info['header_image'], $info['header_tile']), $header_path, DOC_IPS_ROOT_PATH.'blog/headers');

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['header_id']);
	$this->DB->insert( 'blog_headers', $info );
	$inserted_id = $this->DB->getInsertId();

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'blog_headers');

	return true;
}

/**
 * Convert blog
 *
 * @access	public
 * @param 	integer		Foreign ID number
 * @param 	array 		Data to insert to table
 * @param 	array 		Permissions index data
 * @return 	boolean		Success or fail
 **/
public function convertBlog($id, $info, $perms)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$id)
	{
		$this->logError($id, 'No ID number provided');
		return false;
	}
	if (!$info['member_id'])
	{
		$this->logError($id, 'No member ID provided');
		return false;
	}
	if (!$info['blog_name'])
	{
		$this->logError($id, 'No name provided');
		return false;
	}
	if (!$info['blog_type'])
	{
		$info['blog_type'] = 'local';
	}
	if ($info['blog_type'] == 'external' and !$info['blog_exturl'])
	{
		$this->logError($id, 'No external URL provided');
		return false;
	}

	//-----------------------------------------
	// Link
	//-----------------------------------------

	$info['member_id'] = $this->getLink($info['member_id'], 'members', false, true);

	//-----------------------------------------
	// Insert
	//-----------------------------------------

	unset($info['blog_id']);
	unset($info['blog_skin_id']);

	// Make sure we don't have any fields we shouldn't have
	foreach (array('perm_id', 'app', 'perm_type', 'perm_type_id', 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7', 'owner_only', 'friend_only', 'authorized_users') as $unset)
	{
		unset($info[$unset]);
	}

	$this->DB->insert( 'blog_blogs', $info );
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
					$save[] = $this->getLink($pset, 'forum_perms', false, true);
				}
			}
			$perms[$key] = implode(',', $save);
		}
	}

	$this->addToPermIndex('blog', $inserted_id, $perms, $id);

	//-----------------------------------------
	// Add link
	//-----------------------------------------

	$this->addLink($inserted_id, $id, 'blog_blogs');

	return true;
}
