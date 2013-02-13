<?php
/**
 * IPS Converters
 * IP.Board 3.1 Converters
 * Kunena 1.6 Converter
 * Last Update: $Date: 2012-03-20 16:52:44 +0000 (Tue, 20 Mar 2012) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Ryan Ashbrook
 * @copyright	(c) 2011 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 629 $
 */

$info = array (
	'name'		=> 'Kunena 1.6',
	'key'		=> 'kunena',
	'login'		=> false,
);

//TODO:Investigate possibility of not requiring a parent, in case clients want to just get Kunena data and not Joomla.
// Highly doubt it's possible, though.
$parent = array (
	'required'	=> false, 
	'choices'	=> array (
		array (
			'app'	=> 'ccs',
			'key'	=> 'joomla',
			'newdb'	=> false,
		)
	)
);

class admin_convert_board_kunena extends ipsCommand
{
	protected $usingParent = TRUE;
	
	public function doExecute ( ipsRegistry $registry )
	{
		$this->kunena_prefix = 'kunena_';
		
		$forSome = array ( );
		
		// See if we specified a parent product
		$app = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'conv_apps', 'where' => "name='{$this->settings['conv_current']}'" ) );
		
		if ( !$app['parent'] )
		{
			$this->usingParent = FALSE;
			
			$forSome = array (
				'forum_perms'				=> array ( ),
				'groups'					=> array ( 'forum_perms' ),
				'members'					=> array ( 'groups', 'forum_perms' ),
			);
		}
		
		$forAll = array (
			'forums'			=> array ( 'forum_perms', 'groups' ),
			'moderators'		=> array ( 'members', 'forums' ),
			'topics'			=> array ( 'members', 'forums' ),
			'posts'				=> array ( 'members', 'topics' ),
			'reputation_index'	=> array ( 'members', 'posts' ),
			'polls'				=> array ( 'members', 'topics', 'forums' ),
			'attachments'		=> array ( 'members', 'posts' ),
		);
		
		$this->actions = array_merge ( $forSome, $forAll );
		
		require_once ( IPSLib::getAppDir ( 'convert' ) . '/sources/lib_master.php' );
		require_once ( IPSLib::getAppDir ( 'convert' ) . '/sources/lib_board.php' );
		$this->lib = new lib_board ( $registry, $html, $this );
		
		$this->html = $this->lib->loadInterface ( );
		$this->lib->sendHeader ( 'Kunena 1.6 &rarr; IP.Board Converter' );
		
		$this->HB = $this->lib->connect ( );
		
		if ( array_key_exists ( $this->request['do'], $this->actions ) )
		{
			call_user_func ( array ( $this, 'convert_' . $this->request['do'] ) );
		}
		else
		{
			$this->lib->menu ( );
		}
		
		$this->sendOutput ( );
	}
	
	private function sendOutput ( )
	{
		$this->registry->output->html		.= $this->html->convertFooter ( );
		$this->registry->output->html_main	.= $this->registry->output->global_template->global_frame_wrapper ( );
		$this->registry->output->sendOutput ( );
		exit;
	}
	
	public function countRows ( $action )
	{
		switch ( $action )
		{
			case 'forum_perms':
				return $this->lib->countRows ( 'usergroups' );
			break;
			
			case 'groups':
				return $this->lib->countRows ( 'usergroups' );
			break;
			
			case 'members':
				return $this->lib->countRows ( 'users' );
			break;
			
			case 'forums':
				return $this->lib->countRows ( $this->kunena_prefix . 'categories' );
			break;
			
			case 'moderators':
				return $this->lib->countRows ( $this->kunena_prefix . 'moderation' );
			break;
			
			case 'topics':
				return $this->lib->countRows ( $this->kunena_prefix . 'messages', 'parent = 0' );
			break;
			
			case 'posts':
				return $this->lib->countRows ( $this->kunena_prefix . 'messages' );
			break;
			
			case 'reputation_index':
				return $this->lib->countRows ( $this->kunena_prefix . 'thankyou' );
			break;
			
			case 'polls':
				return $this->lib->countRows ( $this->kunena_prefix . 'polls' );
			break;
			
			case 'announcements':
				return $this->lib->countRows ( $this->kunena_prefix . 'announcement' );
			break;
			
			case 'attachments':
				return $this->lib->countRows ( $this->kunena_prefix . 'attachments' );
			break;
			
			default:
				return $this->lib->countRows ( $this->kunena_prefix . $action );
			break;
		}
	}
	
	public function checkConf ( $action )
	{
		switch ( $action )
		{
			case 'attachments':
			case 'forum_perms':
			case 'groups':
				return true;
			break;
			
			default:
				return false;
			break;
		}
	}
	
	private function fixPostData ( $post )
	{
		return $post;
	}
	
	private function convert_forum_perms ( )
	{
		$this->lib->saveMoreInfo ( 'forum_perms', 'map' );
		
		$main = array (
			'select'	=> '*',
			'from'		=> 'usergroups',
			'order'		=> 'id ASC',
		);
		
		$loop = $this->lib->load ( 'forum_perms', $main, array(), array(), TRUE );
		
		$this->lib->getMoreInfo ( 'forum_perms', $loop, array (
			'new' 	=> '--Create New Set--',
			'ot'	=> 'Old Permission Set',
			'nt'	=> 'New Permission Set',
		), '', array (
			'idf'	=> 'id',
			'nf'	=> 'title',
		) );
		
		foreach ( $loop AS $row )
		{
			$this->lib->convertPermSet ( $row['id'], $row['title'] );
		}
		
		$this->lib->next ( );
	}

	private function convert_groups ( )
	{
		$this->lib->saveMoreInfo ( 'groups', 'map' );
		
		$main = array (
			'select'	=> '*',
			'from'		=> 'usergroups',
			'order'		=> 'id ASC',
		);
		
		$loop = $this->lib->load ( 'groups', $main, array(), array(), TRUE );
		
		$this->lib->getMoreInfo ( 'groups', $loop, array (
			'new'	=> '--Create New Group--',
			'ot'	=> 'Old Group',
			'nt'	=> 'New Group',
		), '', array (
			'idf'	=> 'id',
			'nf'	=> 'title',
		) );
		
		foreach ( $loop AS $row )
		{
			$save = array (
				'g_title'				=> $row['title'],
				'g_view_board'			=> 1,
				'g_mem_info'			=> 1,
				'g_other_topics'		=> 1,
				'g_use_search'			=> 1,
				'g_edit_profile'		=> 1,
				'g_post_new_topics'		=> 1,
				'g_reply_own_topics'	=> 1,
				'g_reply_other_topics'	=> 1,
				'g_edit_posts'			=> 1,
				'g_delete_own_posts'	=> 0,
				'g_open_close_posts'	=> 0,
				'g_delete_own_topics'	=> 0,
				'g_post_polls'		 	=> 1,
				'g_vote_polls'		 	=> 1,
				'g_use_pm'			 	=> 1,
				'g_perm_id'				=> $row['id'],
			);
			
			$this->lib->convertGroup ( $row['id'], $save );
		}
		
		$this->lib->next ( );
	}

	private function convert_members ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'users',
			'order'		=> 'id ASC',
		);
		
		$loop = $this->lib->load ( 'members', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			// Extract password and salt.
			$pass = explode ( ':', $row['password'] );
			
			// Figure out our usergroups. Go based on Highest ID (for now...)
			ipsRegistry::DB ( 'hb' )->build ( array (
				'select'	=> 'group_id',
				'from'		=> 'user_usergroup_map',
				'where'		=> 'user_id = ' . $row['id'],
				'order'		=> 'group_id ASC',
			) );
			$groupsQry = ipsRegistry::DB ( 'hb' )->execute ( );
			$groups = ipsRegistry::DB ( 'hb' )->fetch ( $groupsQry );
			
			// Snag primary group... go based on ID, since Joomla does not define a difference.
			$primary = array_pop ( $groups );
			
			$secondaries_array = array ( );
			// And compile the rest.
			foreach ( $groups AS $group )
			{
				$secondaries_array = array_merge ( $secondaries_array, array ( $group['group_id'] ) );
			}
			
			$secondaries = implode ( ',', $secondaries_array );
			
			// Store basic information.
			$info = array (
				'id'				=> $row['id'],
				'email'				=> $row['email'],
				'password'			=> $pass[0],
				'username'			=> $row['username'],
				'displayname'		=> $row['name'],
				'joined'			=> strtotime ( $row['registerDate'] ),
				'group'				=> $this->lib->getLink( $primary, 'groups', true, $this->usingParent ),
				'secondary_groups'	=> $secondaries,
			);
			
			// Store members information
			$members = array (
				'last_visit'	=> strtotime ( $row['lastVisitDate'] ),
				'last_activity'	=> strtotime ( $row['lastVisitDate'] ),
				'misc'			=> $pass[1],
			);
			
			if ( $row['block'] )
			{
				$member['member_banned'] = 1;
			}
			
			$this->lib->convertMember( $info, $members, array(), array(), '', !$this->usingParent );
		}
		
		$this->lib->next();
	}
	
	private function convert_forums ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> $this->kunena_prefix . 'categories',
			'order'		=> 'id ASC',
		);
		
		$loop = $this->lib->load ( 'forums', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'topics'				=> $row['numTopics'],
				'posts'					=> $row['numPosts'],
				'last_post'				=> $row['time_last_msg'],
				'parent_id'				=> ( !$row['parent'] ? -1 : $row['parent'] ),
				'name'					=> $row['name'],
				'description'			=> $row['description'],
				'position'				=> $row['ordering'],
				'use_ibc'				=> 1,
				'use_html'				=> 0,
				'status'				=> $row['pub_access'],
				'inc_postcount'			=> 1,
				'sub_can_post'			=> 1,
				'redirect_on'			=> 0,
				'forum_allow_rating'	=> 1,
			);
			
			$this->lib->convertForum ( $row['id'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_moderators ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> $this->kunena_prefix . 'moderation',
		);
		
		$loop = $this->lib->load ( 'moderators', $main );
		
		// Simulate an id. Kunena has no primary key here.
		$i = 1;
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'forum_id'		=> $row['catid'],
				'member_id'		=> $row['userid'],
				'edit_post'		=> 1,
				'edit_topic'	=> 1,
				'delete_post'	=> 1,
				'delete_topic'	=> 1,
				'view_ip'		=> 1,
				'open_topic'	=> 1,
				'close_topic'	=> 1,
				'mass_move'		=> 1,
				'mass_prune'	=> 1,
				'move_topic'	=> 1,
				'pin_topic'		=> 1,
				'unpin_topic'	=> 1,
				'post_q'		=> 1,
				'topic_q'		=> 1,
				'allow_warn'	=> 0,
				'edit_user'		=> 1,
				'is_group'		=> 0,
				'split_merge'	=> 1,
			);
			
			$this->lib->convertModerator ( $i, $save, true );
			$i++;
		}
		
		$this->lib->next ( );
	}
	
	private function convert_topics ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> $this->kunena_prefix . 'messages',
			'where'		=> 'parent = 0',
			'order'		=> 'id ASC',
		);
		
		$loop = $this->lib->load ( 'topics', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'title'			=> $row['subject'],
				'state'			=> ( $row['locked'] ? 'closed' : 'open' ),
				'views'			=> $row['hits'],
				'starter_id'	=> $row['userid'],
				'start_date'	=> $row['time'],
				'forum_id'		=> $row['catid'],
				'pinned'		=> ( $row['ordering'] > 0 ? 1 : 0 ),
				'approved'		=> 1,
			);
			
			// Check for a poll.
			if ( ipsRegistry::DB('hb')->buildAndFetch( array ( 'select' => 'id, threadid', 'from' => $this->kunena_prefix . 'polls', 'where' => 'threadid = ' . $row['id'] ) ) )
			{
				$save['poll_state'] = 1;
			}
			
			$this->lib->convertTopic ( $row['id'], $save, true );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_posts ( )
	{
		$main = array (
			'select'	=> 'm.*',
			'from'		=> array ( $this->kunena_prefix . 'messages' => 'm' ),
			'add_join'	=> array (
				array (
					'select'	=> 'mt.*',
					'from'		=> array ( $this->kunena_prefix . 'messages_text' => 'mt' ),
					'where'		=> 'm.id = mt.mesid',
					'type'		=> 'left',
				),
			),
			'order'		=> 'm.id ASC',
		);
		
		$loop = $this->lib->load ( 'posts', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'post'			=> $this->fixPostData ( $row['message'] ),
				'author_id'		=> $row['userid'],
				'author_name'	=> $row['name'],
				'use_sig'		=> 1,
				'use_emo'		=> 1,
				'ip_address'	=> $row['ip'],
				'post_date'		=> $row['time'],
				'queued'		=> 0,
				'topic_id'		=> $row['thread'],
			);
			
			$this->lib->convertPost ( $row['id'], $save, true );
		}
		
		$this->lib->next();
	}
	
	private function convert_reputation_index ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> $this->kunena_prefix . 'thankyou'
		);
		
		$loop = $this->lib->load ( 'reputation_index', $main );
		
		// Again, no primary key.
		$i = 1;
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'member_id'		=> $row['userid'],
				'app'			=> 'forums',
				'type'			=> 'pid',
				'type_id'		=> $row['postid'],
				'rep_date'		=> strtotime ( $row['time'] ),
				'rep_rating'	=> 1,
			);
			
			$this->lib->convertRep ( $i, $save, true );
			$i++;
		}
		
		$this->lib->next ( );
	}
	
	private function convert_polls ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> $this->kunena_prefix . 'polls',
			'order'		=> 'id ASC',
		);
		
		$loop = $this->lib->load ( 'polls', $main, array ( 'voters' ) );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$topic = ipsRegistry::DB ( 'hb' )->buildAndFetch ( array (
				'select'	=> 'id, catid, userid',
				'from'		=> $this->kunena_prefix . 'messages',
				'where'		=> "id = '{$row['threadid']}'"
			) );
			
			$votes = array();

			ipsRegistry::DB ( 'hb' )->build ( array (
				'select'	=> '*',
				'from'		=> $this->kunena_prefix . 'polls_users',
				'where'		=> "pollid = {$row['id']}"
			) );
			$voteRes = ipsRegistry::DB('hb')->execute ( );
			while ( $voter = ipsRegistry::DB ( 'hb' )->fetch ( $voteRes ) )
			{
				$choice = array ( );
					
				// Do we already have this user's votes
				if ( !$voter['userid'] or in_array ( $voter['userid'], $votes ) )
				{
					continue;
				}

				// And save
				$vsave = array (
					'vote_date'		=> strtotime ( $voter['lasttime'] ),
					'tid'			=> $row['threadid'],
					'member_id'		=> $voter['userid'],
					'forum_id'		=> $topic['catid'],
					'member_choices'=> serialize ( array ( 1 => array ( $voter['lastvote'] ) ) ),
				);
				$this->lib->convertPollVoter ( $row['id'], $vsave, true );
			}
			
			$options = array ( );
			$total_votes = array ( );
			// Get options
			ipsRegistry::DB ( 'hb' )->build ( array (
				'select'	=> '*',
				'from'		=> $this->kunena_prefix . 'polls_options',
				'where'		=> 'pollid = ' . $row['id'],
			) );
			$options_qry = ipsRegistry::DB ( 'hb' )->execute ( );
			while ( $option = ipsRegistry::DB ( 'hb' )->fetch ( $options_qry ) )
			{
				$options[$option['id']]		= $option['text'];
				$total_votes[$option['id']]	= $option['votes'];	
			}
			
			$poll_array = array (
				1 => array (
					'tid'		=> $row['threadid'],
					'question'	=> str_replace ( "'", "&#39;", $row['title'] ),
					'choice'	=> $options,
					'votes'	=> $total_votes
				)
			);
			
			$save = array (
				'tid'			=> $row['threadid'],
				'start_date'	=> time ( ),
				'choices'		=> serialize ( $poll_array ),
				'starter_id'	=> $topic['userid'],
				'forum_id'		=> $topic['catid'],
				'poll_question'	=> str_replace( "'" , '&#39;',$row['title'] ),
			);
			
			$this->lib->convertPoll ( $row['id'], $save, true );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_attachments ( )
	{
		$this->lib->saveMoreInfo ( 'attachments', array ( 'attach_path' ) );
		
		$main = array (
			'select'	=> '*',
			'from'		=> $this->kunena_prefix . 'attachments',
			'order'		=> 'id ASC',
		);
		
		$loop = $this->lib->load ( 'attachments', $main );
		
		$this->lib->getMoreInfo ( 'attachments', $loop, array (
			'attach_path'	=> array (
				'type'			=> 'text',
				'label'			=> 'The path to your Joomla/Kunena installation (no trailing slash):',
			)
		), 'path' );
		
		$get	= unserialize ( $this->settings['conv_extra'] );
		$us		= $get[$this->lib->app['name']];
		$path = $us['attach_path'];
		
		if ( !is_writable ( $this->settings['upload_dir'] ) )
		{
			$this->lib->error ( 'Your IP.Board upload path is not writeable. '.$this->settings['upload_dir'] );
		}
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			// Get the extension.
			$ext = array_pop ( explode ( '.', $row['filename'] ) );
			
			// Determine if image.
			$image = false;
			if ( preg_match ( '/image/', $row['filetype'] ) )
			{
				$image = true;
			}
			
			// And save.
			$save = array (
				'attach_ext'		=> $ext,
				'attach_file'		=> $row['filename'],
				'attach_is_image'	=> $image,
				'attach_date'		=> time ( ),
				'attach_member_id'	=> $row['userid'],
				'attach_filesize'	=> $row['size'],
				'attach_rel_id'		=> $row['mesid'],
				'attach_rel_module'	=> 'post',
				'attach_location'	=> $row['folder'] . '/' . $row['filename'],
			);
			
			// Grab the parent 'topic' ID
			$topic = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'parent', 'from' => $this->kunena_prefix . 'messages', 'where' => "id='" . intval( $row['mesid'] ) . "'" ) );
			
			if ( $topic['parent'] )
			{
				$save['attach_parent_id'] = $this->lib->getLink( $topic['parent'], 'topics', true );
			}
			
			$done = $this->lib->convertAttachment ( $row['id'], $save, $path );
			
			if ( $done === true )
			{
				$aid = $this->lib->getLink($row['id'], 'attachments');
				$pid = $this->lib->getLink($save['attach_rel_id'], 'posts');

				if ( $pid )
				{
					$attachrow = $this->DB->buildAndFetch( array( 'select' => 'post', 'from' => 'posts', 'where' => "pid={$pid}" ) );

					$rawaid = $row['attachmentid'];
					$update = preg_replace("/\[attachment={$rawaid}\](.+?)\[\/attachment\]/i", "[attachment={$aid}:{$save['attach_location']}]", $attachrow['post']);

					$this->DB->update('posts', array('post' => $update), "pid={$pid}");
				}
			}
		}
		
		$this->lib->next ( );
	}
}

