
/**
 * Add link
 *
 * @access	public
 * @param	integer		IPB's ID
 * @param	integer		Foreign ID
 * @param	string		Type
 * @param	boolean		Duplicate?
 * @return	void
 **/
public function addLink($ipb_id, $foreign_id, $type, $dupe='0')
{
	// New table switching device - makes things LOTS faster.
	switch( $type )
	{
		case 'posts':
			$table = 'conv_link_posts';
			break;

		case 'topics':
			$table = 'conv_link_topics';
			break;

		case 'pms':
		case 'pm_posts':
		case 'pm_maps':
			$table = 'conv_link_pms';
			break;

		default:
			$table = 'conv_link';
			break;
	}

	// Setup the insert array with link values
	$insert_array = array( 'ipb_id'		=> $ipb_id,
						   'foreign_id' => $foreign_id,
						   'type'		=> $type,
						   'duplicate'	=> $dupe,
						   'app'		=> $this->app['app_id'] );

	// Insert the link into the database
	$this->DB->insert( $table, $insert_array );

	// Cache the link
	$this->linkCache[$type][$foreign_id] = $ipb_id;
}

/**
 * Get Link
 *
 * @access	public
 * @param	integer		Foreign ID
 * @param	string		Type
 * @param	boolean		If true, will return false on error, otherwise will display error
 * @param 	boolean		If true, will check parent app's history instead of own
 * @return 	integer		IPB's ID
 **/
public function getLink($foreign_id, $type, $ret=false, $parent=false)
{
	if (!$foreign_id or !$type)
	{
		if ($ret)
		{
			return false;
		}
		parent::sendError("There was a problem with the converter - could not get valid link: {$type}:{$foreign_id}");
	}
	if ( isset($this->linkCache[$type][$foreign_id]) )
	{
		return $this->linkCache[$type][$foreign_id];
	}
	else
	{
		// New table switching device - makes things LOTS faster.
		switch( $type )
		{
			case 'posts':
				$table = 'conv_link_posts';
				break;

			case 'topics':
				$table = 'conv_link_topics';
				break;

			case 'pms':
			case 'pm_posts':
			case 'pm_maps':
				$table = 'conv_link_pms';
				break;

			default:
				$table = 'conv_link';
				break;
		}

		// Parent?
		if ( $parent && $this->app['parent'] == 'self' )
		{
			$this->linkCache[$type][$foreign_id] = $foreign_id;
			return $foreign_id;
		}

		$appid = ($parent) ? $this->app['parent'] : $this->app['app_id'];
		$row = $this->DB->buildAndFetch( array( 'select' => 'ipb_id', 'from' => $table, 'where' => "foreign_id='{$foreign_id}' AND type='{$type}' AND app={$appid}" ) );

		if(!$row)
		{
			return false;
		}

		$this->linkCache[$type][$foreign_id] = $row['ipb_id'];
		return $row['ipb_id'];
	}
}
