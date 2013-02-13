
/**
 * Convert a personal conversation
 *
 * @access	public
 * @param 	array		Data to insert to topics table
 * @param 	array 		Data to insert to posts table
 * @param 	array 		Data to insert to maps table
 * @return 	boolean		Success or fail
 **/
public function convertPM($topic, $posts, $maps)
{
	//-----------------------------------------
	// Make sure we have everything we need
	//-----------------------------------------

	if (!$topic['mt_id'])
	{
		$this->logError($topic['mt_id'], 'No ID number provided');
		return false;
	}
	if (!$topic['mt_title'])
	{
		$this->logError($topic['mt_id'], 'No title provided');
		return false;
	}
	// if (!$topic['mt_starter_id'])
	// {
	// 	$this->logError($topic['mt_id'], 'No starter ID provided');
	// 	return false;
	// }
	// if (!$topic['mt_to_member_id'])
	// {
	// 	$this->logError($topic['mt_id'], 'No recipient ID provided');
	// 	return false;
	// }


	//-----------------------------------------
	// Insert topic
	//-----------------------------------------
	$oldMemberID			= $topic['mt_to_member_id'] ? $topic['mt_to_member_id'] : 'NULL';
	$topic['mt_starter_id'] = ($topic['mt_starter_id']) ? $this->getLink($topic['mt_starter_id'], 'members') : 0;
	$topic['mt_to_member_id'] = ($topic['mt_to_member_id']) ? $this->getLink($topic['mt_to_member_id'], 'members') : 0;

	if (!$topic['mt_starter_id'])
	{
		$this->logError($topic['mt_id'], 'Starter not found.');
		return false;
	}

	if (!$topic['mt_to_member_id'])
	{
		$this->logError($topic['mt_id'], 'Recipient (' . $oldMemberID . ') not found.');
		return false;
	}

	$tid = $topic['mt_id'];
	unset($topic['mt_id']);
	$this->DB->insert( 'message_topics', $topic );
	$topic_id = $this->DB->getInsertId();

	$this->addLink($topic_id, $tid, 'pms');

	//-----------------------------------------
	// Loop through the posts
	//-----------------------------------------

	foreach ($posts as $post)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------

		if (!$post['msg_id'])
		{
			$this->logError($post['msg_id'], '(PM POST) No ID number provided');
			continue;
		}
		if (!$post['msg_post'])
		{
			$this->logError($post['msg_id'], '(PM POST) No post provided');
			continue;
		}
		// if (!$post['msg_author_id'])
		// {
		// 	$this->logError($post['msg_id'], '(PM POST) No author ID number provided');
		// 	continue;
		// }

		//-----------------------------------------
		// Insert
		//-----------------------------------------
		$post['msg_topic_id'] = $topic_id;
		$post['msg_author_id'] = ($post['msg_author_id']) ? $this->getLink($post['msg_author_id'], 'members') : 0;

		if (!$post['msg_author_id'])
		{
			$this->logError($post['msg_id'], 'Author not found.');
			return false;
		}

		$pid = $post['msg_id'];
		unset($post['msg_id']);
		$this->DB->insert( 'message_posts', $post );
		$inserted_id = $this->DB->getInsertId();

		$this->addLink($inserted_id, $pid, 'pm_posts');

		//-----------------------------------------
		// Get first / last count
		//-----------------------------------------
		if ($post['msg_is_first_post'])
		{
			$first = $inserted_id;
		}
		$last = $inserted_id;
	}

	//-----------------------------------------
	// We need maps
	//-----------------------------------------
	foreach ($maps as $map)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------
		if (!$map['map_user_id'])
		{
			$this->logError($map['map_id'], '(PM MAP) No user ID number provided');
			continue;
		}
		if (!$map['map_topic_id'])
		{
			$this->logError($map['map_id'], '(PM MAP) No topic ID number provided');
			continue;
		}

		if (!$map['map_last_topic_reply'])
		{
			$this->logError($map['map_id'], '(PM MAP) Last topic reply not provided');
			continue;
		}

		if (!$map['map_folder_id'])
		{
			$map['map_folder_id'] = 'myconvo';
		}

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		$map['map_user_id'] = $this->getLink($map['map_user_id'], 'members');
		$map['map_topic_id'] = $this->getLink($map['map_topic_id'], 'pms');


		if (!$map['map_user_id'])
		{
			$this->logError($map['map_id'], '(PM MAP) No user ID link could be found');
			continue;
		}
		if (!$map['map_topic_id'])
		{
			$this->logError($map['map_id'], '(PM MAP) No topic ID link could be found');
			continue;
		}

		// Check if map already exists.
		$existingMap = $this->DB->buildAndFetch ( array (
			'select'	=> '*',
			'from'		=> 'message_topic_user_map',
			'where'		=> 'map_user_id = ' . $map['map_user_id'] . ' AND  map_topic_id = ' . $map['map_topic_id']
		) );

		if ( $existingMap )
		{
			$this->logError ( $existingMap['map_id'], '(PM MAP) PM Map Already Exists.' );
			continue;
		}

		unset($map['map_id']);
		$this->DB->insert( 'message_topic_user_map', $map );
		$inserted_id = $this->DB->getInsertId();

		$this->addLink($inserted_id, $map['map_id'], 'pm_maps');
	}

	//-----------------------------------------
	// Update topic
	//-----------------------------------------
	$this->DB->update( 'message_topics', array('mt_last_msg_id' => intval($last), 'mt_first_msg_id' => intval($first)), "mt_id={$topic_id}" );

	return true;
}