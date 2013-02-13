
/**
    * Information box to display on convert screen
    *
    * @access	public
    * @return	string 		html to display
    */
public function getInfo()
{
	return "<strong>Rebuild Content</strong><br />
		<a href='{$this->settings['base_url']}&app=core&module=tools&section=rebuild&do=rebuild_overview' target='_blank'>Click here</a> and run the following tools in the order given:
		<ul>
			<li>Recount Statistics</li>
			<li>Resynchronize Topics</li>
			<li>Resynchronize Forums</li>
			<li>Rebuild Attachment Thumbnails</li>
			<li>Rebuild Profile Photo Thumbnails</li>
		</ul><br />
		<strong>Rebuild Caches</strong><br />
		<a href='{$this->settings['base_url']}&app=core&&module=tools&section=cache' target='_blank'>Click here</a> and recache all.";
}

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
		case 'groups':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'groups' ) );
			$return = array(
				'name'	=> 'Member Groups',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'members':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'members' ) );
			$return = array(
				'name'	=> 'Members',
				'rows'	=> $count['count'],
				'cycle'	=> 250,
			);
			break;

		case 'dnames_change':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'dnames_change' ) );
			$return = array(
				'name'	=> 'Display Name History',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'tags':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'core_tags' ) );
			$return = array(
				'name'	=> 'Tags',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
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

		case 'forums':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'forums' ) );
			$return = array(
				'name'	=> 'Categories &amp; Forums',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
				'finish'=> "You will now need to <a href='{$this->settings['base_url']}&amp;app=members&amp;module=groups&amp;section=permissions' target='_blank'>configure permissions</a>."
			);
			break;

		case 'moderators':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'moderators' ) );
			$return = array(
				'name'	=> 'Forum Moderators',
				'rows'	=> $count['count'],
				'cycle'	=> 500,
			);
			break;

		case 'custom_bbcode':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'custom_bbcode' ) );
			$return = array(
				'name'	=> 'Custom BBCode',
				'rows'	=> $count['count'],
				'cycle'	=> 50,
			);
			break;

		case 'topics':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'topics' ) );
			$return = array(
				'name'	=> 'Topics',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'posts':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'posts' ) );
			$return = array(
				'name'	=> 'Posts',
				'rows'	=> $count['count'],
				'cycle'	=> 1500,
			);
			break;

		case 'polls':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'polls' ) );
			$return = array(
				'name'	=> 'Polls',
				'rows'	=> $count['count'],
				'cycle'	=> 1000,
			);
			break;

		case 'pms':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'message_topics' ) );
			$return = array(
				'name'	=> 'Personal Conversations',
				'rows'	=> $count['count'],
				'cycle'	=> 1000,
			);
			break;

		case 'ranks':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'titles' ) );
			$return = array(
				'name'	=> 'Ranks',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'attachments_type':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'attachments_type' ) );
			$return = array(
				'name'	=> 'File Types',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'attachments':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'attachments', 'where' => "attach_rel_module='post' OR attach_rel_module='msg'" ) );
			$return = array(
				'name'	=> 'Attachments',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'emoticons':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'emoticons' ) );
			$return = array(
				'name'	=> 'Emoticons',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'announcements':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'announcements' ) );
			$return = array(
				'name'	=> 'Announcements',
				'rows'	=> $count['count'],
				'cycle'	=> 1500,
			);
			break;

		case 'badwords':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'badwords' ) );
			$return = array(
				'name'	=> 'Bad Word Filters',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'banfilters':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'banfilters' ) );
			$return = array(
				'name'	=> 'Ban Filters',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'ignored_users':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ignored_users' ) );
			$return = array(
				'name'	=> 'Ignored Users',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'pfields':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'pfields_data' ) );
			$return = array(
				'name'	=> 'Custom Profile Fields',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'profile_comments':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'member_status_updates' ) );
			$return = array(
				'name'	=> 'Profile Comments and Status Updates',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'profile_comment_replies':
			$count = $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'member_status_replies' ) );
			$return = array (
				'name'	=> 'Profile Comment/Status Replies',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
		break;

		case 'profile_friends':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'profile_friends' ) );
			$return = array(
				'name'	=> 'Friendships',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'profile_ratings':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'profile_ratings' ) );
			$return = array(
				'name'	=> 'Profile Ratings',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'rc_status':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'rc_status' ) );
			$return = array(
				'name'	=> 'Report Center: Statuses',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'rc_status_sev':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'rc_status_sev' ) );
			$return = array(
				'name'	=> 'Report Center: Severities',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'reputation_index':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'reputation_cache' ) );
			$return = array(
				'name'	=> 'Reputations',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
				'conf'	=> false,
			);
			break;

		case 'rss_import':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'rss_import' ) );
			$return = array(
				'name'	=> 'RSS Imports',
				'rows'	=> $count['count'],
				'cycle'	=> 1,
			);
			break;

		case 'topic_mmod':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'topic_mmod' ) );
			$return = array(
				'name'	=> 'Multi-Moderation',
				'rows'	=> $count['count'],
				'cycle'	=> 100,
			);
			break;

		case 'topic_ratings':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'topic_ratings' ) );
			$return = array(
				'name'	=> 'Topic Ratings',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
			);
			break;

		case 'warn_logs':
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'warn_logs' ) );
			$return = array(
				'name'	=> 'Warning Logs',
				'rows'	=> $count['count'],
				'cycle'	=> 2000,
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

	$basic = array('section' => $this->app['app_key'], 'key' => $action, 'app' => 'board');

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
		case 'tags':
			return array();
			break;

		case 'members':
			return array( 'members' => 'member_id', 'pfields_content' => 'member_id', 'profile_portal' => 'pp_member_id', 'rc_modpref' => 'mem_id' );
			break;

		case 'dnames_change':
			return array( 'dnames_change' => 'dname_id' );
			break;

		case 'groups':
			return array( 'groups' => 'g_id' );
			break;

		case 'forum_perms':
			return array( 'forum_perms' => 'perm_id' );
			break;

		case 'forums':
			return array( 'forums' => 'id', 'permission_index' => 'perm_id' );
			break;

		case 'moderators':
			return array( 'moderators' => 'mid' );
			break;

		case 'custom_bbcode':
			return array( 'custom_bbcode' => 'bbcode_id' );
			break;

		case 'bbcode_media':
			return array( 'bbcode_mediatag' => 'mediatag_id' );
			break;

		case 'topic_icons':
			return array();
			break;

		case 'topics':
			return array( 'topics' => 'tid', 'core_like' => 'like_rel_id', 'core_like_cache' => 'like_cache_rel_id' );
			break;

		case 'posts':
			return array( 'posts' => 'pid' );
			break;

		case 'reputation_cache':
			return array( 'reputation_cache' => 'id' );
			break;

		case 'polls':
			return array( 'polls' => 'pid' );
			break;

		case 'voters':
			return array( 'voters' => 'vid' );
			break;

		case 'pms':
			return array( 'message_topics' => 'mt_id' );
			break;

		case 'pm_posts':
			return array( 'message_posts' => 'msg_id' );
			break;

		case 'pm_maps':
			return array( 'message_topic_user_map' => 'map_id' );
			break;

		case 'ranks':
			return array( 'titles' => 'id' );
			break;

		case 'attachments_type':
			return array( 'attachments_type' => 'atype_id' );
			break;

		case 'attachments':
			return array( 'attachments' => 'attach_id' );
			break;

		case 'emoticons':
			return array( 'emoticons' => 'id' );
			break;

		case 'announcements':
			return array( 'announcements' => 'announce_id' );
			break;

		case 'ranks':
			return array( 'titles' => 'id' );
			break;

		case 'badwords':
			return array( 'badwords' => 'wid' );
			break;

		case 'banfilters':
			return array( 'banfilters' => 'ban_id' );
			break;

		case 'ignored_users':
			return array( 'ignored_users' => 'ignore_id' );
			break;

		case 'pfields':
			return array( 'pfields_data' => 'pf_id' );
			break;

		case 'pfields_groups':
			return array( 'pfields_groups' => 'pf_group_id' );
			break;

		case 'profile_comments':
			return array( 'member_status_updates' => 'status_id' );
			break;

		case 'profile_comment_replies':
			return array ( 'member_status_replies' => 'reply_id' );
		break;

		case 'profile_friends':
			return array( 'profile_friends' => 'friends_id' );
			break;

		case 'profile_ratings':
			return array( 'profile_ratings' => 'rating_id' );
			break;

		case 'rc_status':
			return array( 'rc_status' => 'status' );
			break;

		case 'rc_status_sev':
			return array( 'rc_status_sev' => 'id' );
			break;

		case 'reputation_index':
			return array( 'reputation_index' => 'id' );
			break;

		case 'rss_export':
			return array( 'rss_export' => 'rss_export_id' );
			break;

		case 'rss_import':
			return array( 'rss_import' => 'rss_import_id' );
			break;

		case 'topic_mmod':
			return array( 'topic_mmod' => 'mm_id' );
			break;

		case 'topic_ratings':
			return array( 'topic_ratings' => 'rating_id' );
			break;

		case 'warn_logs':
			return array( 'warn_logs' => 'wlog_id' );
			break;

		case 'forum_tracker':
		case 'tracker':
			return array();
		break;

		default:
			$this->error('There is a problem with the converter: bad truncate command ('.$action.')');
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
		case 'forums':
			return array('addfield' => array('forums', 'conv_parent', 'varchar(5)'));
			break;

		case 'members':
			return array('addfield' => array('members', 'conv_password', 'varchar(128)'));
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
	# Added the "return false" to avoid errors while converting reports from deleted forums/topics/posts/pms/members

	switch ($type)
	{
		case 'post':
			if ( $this->getLink($report['exdat1'], 'forums', true) || $this->getLink($report['exdat2'], 'topics', true) || $this->getLink($report['exdat3'], 'posts', true) )
			{
				return false;
			}
			$report['exdat1'] = $this->getLink($report['exdat1'], 'forums');
			$report['exdat2'] = $this->getLink($report['exdat2'], 'topics');
			$report['exdat3'] = $this->getLink($report['exdat3'], 'posts');
			$report['url'] = "/index.php?showtopic={$report['exdat2']}&amp;view=findpost&amp;p={$report['exdat3']}";
			$report['seotemplate'] = 'showtopic';
			break;

		case 'pm':
			if ( $this->getLink($report['exdat1'], 'pms', true) || $this->getLink($report['exdat2'], 'pm_posts', true) )
			{
				return false;
			}
			$report['exdat1'] = $this->getLink($report['exdat1'], 'pms');
			$report['exdat2'] = $this->getLink($report['exdat2'], 'pm_posts');
			$report['url'] = "/index.php?app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID={$report['exdat1']}";
			break;

		case 'member':
			if ( $this->getLink($report['exdat1'], 'members', true) )
			{
				return false;
			}
			$report['exdat1'] = $this->getLink($report['exdat1'], 'members');
			$report['url'] = "/index.php?showuser={$report['exdat1']}";
			break;
	}
	return $report;
}
