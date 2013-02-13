
/**
 * Return the information needed for a specific action
 *
 * @access	public
 * @param 	string		action (e.g. 'members', 'forums', etc.)
 * @return 	array 		info needed for html->convertMenuRow
 **/
public function menuRow($action='', $return=false)
{
	switch ($action)
	{
		case 'members':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'members' ) );
			$return = array(
				'name'	=> 'Members',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'groups':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'groups' ) );
			$return = array(
				'name'	=> 'Member Groups',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'forum_perms':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'forum_perms' ) );
			$return = array(
				'name'	=> 'Permission Sets',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'gallery_categories':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_categories' ) );
			$return = array(
				'name'	=> 'Categories',
				'rows'	=> $count['count'],
				'cycle'	=> 1000,
			);
			break;

		case 'gallery_albums':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_albums' ) );
			$return = array(
				'name'	=> 'Albums',
				'rows'	=> $count['count'],
				'cycle'	=> 1000,
			);
			break;

		case 'gallery_images':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_images' ) );
			$return = array(
				'name'	=> 'Images',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'gallery_comments':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_comments' ) );
			$return = array(
				'name'	=> 'Comments',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'gallery_ratings':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_ratings' ) );
			$return = array(
				'name'	=> 'Ratings',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'gallery_ecardlog':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_ecardlog' ) );
			$return = array(
				'name'	=> 'eCard Log',
				'rows'	=> $count['count'],
				'cycle'	=> 1000,
			);
			break;

		case 'gallery_subscriptions':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_subscriptions' ) );
			$return = array(
				'name'	=> 'Subscriptions',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'gallery_media_types':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_media_types' ) );
			$return = array(
				'name'	=> 'Media Types',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'gallery_form_fields':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'gallery_form_fields' ) );
			$return = array(
				'name'	=> 'Form Fields',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		default:
			if ($return)
			{
				return false;
			}
			$this->error("There is a problem with the converter: called invalid action {$action}");
			break;
	}

	$basic = array('section' => $this->app['app_key'], 'key' => $action, 'app' => 'gallery');
	return array_merge($basic, $return);
}

/**
 * Return the tables that need to be truncated for a given action
 *
 * @access	public
 * @param 	string		action (e.g. 'members', 'forums', etc.)
 * @return 	array 		array('table' => 'id_field', ...)
 **/
public function truncate($action)
{
	switch ($action)
	{
		case 'members':
			return array( 'members' => 'member_id', 'pfields_content' => 'member_id', 'profile_portal' => 'pp_member_id', 'rc_modpref' => 'mem_id' );
			break;

		case 'groups':
			return array( 'groups' => 'g_id' );
			break;

		case 'forum_perms':
			return array( 'forum_perms' => 'perm_id' );
			break;

		case 'gallery_categories':
			return array( 'gallery_categories' => 'id' );
			break;

		case 'gallery_albums':
			return array( 'gallery_albums' => 'id' );
			break;

		case 'gallery_images':
			return array( 'gallery_images' => 'id' );
			break;

		case 'gallery_comments':
			return array( 'gallery_comments' => 'pid' );
			break;

		case 'gallery_ecardlog':
			return array( 'gallery_ecardlog' => 'id' );
			break;

		case 'gallery_favorites':
			return array( 'gallery_favorites' => 'id' );
			break;

		case 'gallery_subscriptions':
			return array( 'gallery_subscriptions' => 'sub_id' );
			break;

		case 'gallery_ratings':
			return array( 'gallery_ratings' => 'id' );
			break;

		case 'gallery_media_types':
			return array( 'gallery_media_types' => 'id' );
			break;

		case 'gallery_form_fields':
			return array( 'gallery_form_fields' => 'id' );
			break;

		default:
			$this->error('There is a problem with the converter: bad truncate command');
			break;
	}
}

/**
 * Database changes
 *
 * @access	public
 * @param 	string		action (e.g. 'members', 'forums', etc.)
 * @return 	array 		Details of change - array('type' => array(info))
 **/
public function databaseChanges($action)
{
	switch ($action)
	{
		case 'gallery_categories':
			return array('addfield' => array('gallery_categories', 'conv_parent', 'mediumint(5)'));
			break;

		default:
			return null;
			break;
	}
}

/**
 * Process report links
 *
 * @access	protected
 * @param 	string		type (e.g. 'post', 'pm')
 * @param 	array 		Data for reports_index table with foreign IDs
 * @return 	array 		Processed data for reports_index table
 **/
protected function processReportLinks($type, $report)
{
	switch ($type)
	{
		case 'gallery_images':
			$report['exdat1'] = $this->getLink($report['exdat1'], 'gallery_images');
			$report['exdat2'] = 0;
			$report['exdat3'] = 0;
			$report['url'] = "/index.php?app=gallery&amp;module=images&amp;section=viewimage&amp;img={$report['exdat1']}";
			$report['seotemplate'] = '';
			break;

		case 'gallery_comments':
			$report['exdat1'] = $this->getLink($report['exdat1'], 'gallery_images');
			$report['exdat2'] = $this->getLink($report['exdat2'], 'gallery_comments');
			$report['exdat3'] = $report['exdat3'];
			$report['url'] = "/index.php?app=gallery&amp;module=images&amp;section=viewimage&amp;img={$report['exdat1']}&amp;st={$report['exdat3']}#{$report['exdat2']}";
			$report['seotemplate'] = '';
			break;
	}
	return $report;
}

/**
 * Loads the media cache
 *
 * @access	public
 * @return	void
 */
private function _loadMediaCache()
{
	 if( ! is_array($this->media_thumb_cache) OR ! count($this->media_thumb_cache) )
	 {
		if( !is_array( $this->caches['gallery_media_types'] ) OR !count( $this->caches['gallery_media_types'] ) )
		{
			$this->cache->getCache( 'gallery_media_types' );

			if( !is_array( $this->caches['gallery_media_types'] ) OR !count( $this->caches['gallery_media_types'] ) )
			{
				$this->cache->rebuildCache( 'gallery_media_types', 'gallery' );
			}
		}

		if( count($this->caches['gallery_media_types']) AND is_array($this->caches['gallery_media_types']) )
		{
			foreach( $this->caches['gallery_media_types'] as $j )
			{
				$exts = explode( ",", $j['extension'] );

				foreach( $exts as $ext )
				{
					$this->media_thumb_cache[$ext] = $j;
				}
			}
		}
	}
}