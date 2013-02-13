<?php
/**
 * IPS Converters
 * IP.Content 2.2 Converters
 * Joomla! 1.6 Converter
 * Last Update: $Date: 2011-12-09 17:27:11 +0000 (Fri, 09 Dec 2011) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Ryan Ashbrook
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 607 $
 */

$info = array (
	'key'   => 'joomla',
	'name'  => 'Joomla! 1.6',
	'login' => true
);

class admin_convert_ccs_joomla extends ipsCommand
{
	public function doExecute ( ipsRegistry $registry )
	{
		$this->registry = $registry;
		
		$this->actions = array (
			'forum_perms'				=> array ( ),
			'groups'					=> array ( 'forum_perms' ),
			'members'					=> array ( 'groups', 'forum_perms' ),
			'ccs_database_categories'	=> array ( ),
			'ccs_articles'				=> array ( 'members', 'ccs_database_categories' ),
		);
		
		require_once ( IPSLib::getAppDir ( 'convert' ) . '/sources/lib_master.php' );
		require_once ( IPSLib::getAppDir ( 'convert' ) . '/sources/lib_ccs.php' );
		$this->lib = new lib_ccs ( $registry, $html, $this );
		
		$this->html = $this->lib->loadInterface ( );
		$this->lib->sendHeader ( 'Joomla! 1.6 &rarr; IP.Content Converter' );
		
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
			
			case 'ccs_database_categories':
				return $this->lib->countRows ( 'categories' );
			break;
			
			case 'ccs_articles':
				return $this->lib->countRows ( 'content' );
			break;
			
			default:
				return $this->lib->countRows ( $action );
			break;
		}
	}
	
	public function checkConf ( $action )
	{
		switch ( $action )
		{
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
		
		$loop = $this->lib->load ( 'forum_perms', $main );
		
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
		
		$loop = $this->lib->load ( 'groups', $main );
		
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
				'group'				=> $this->lib->getLink( $primary, 'groups', false, true ),
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
			
			$this->lib->convertMember( $info, $members, array(), array(), '', false );
		}
		
		$this->lib->next();
	}
	
	private function convert_ccs_database_categories ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'categories',
			'order'		=> 'id ASC',
		);
		
		$loop = $this->lib->load ( 'ccs_database_categories', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'category_database_id'		=> 1,
				'category_name'				=> $row['title'],
				'category_description'		=> $row['description'],
				'category_parent_id'		=> $row['parent_id'],
				'category_show_records'		=> 1,
				'category_furl_name'		=> $row['alias'],
				'category_template'			=> 4,
			);
			
			$this->lib->convertDatabaseCategory ( $row['id'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_ccs_articles ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'content',
			'order'		=> 'id ASC',
		);
		
		$this->lib->load ( 'ccs_articles', $main );

		$fields = array ( );
		$this->DB->build ( array (
			'select'	=> '*',
			'from'		=> 'ccs_database_fields',
			'where'		=> 'field_database_id = 1'
		) );
		$this->DB->execute ( );
		while ( $row = $this->DB->fetch ( ) )
		{
			$fields[$row['field_key']] = $row['field_id'];
		}

		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'member_id'			=> $row['created_by'],
				'record_saved'		=> strtotime ( $row['created'] ),
				'category_id'		=> $row['catid'],
				'record_locked'		=> 0,
				'record_approved'	=> ( $row['state'] != 0 ? 1 : 0 ),
			);
			
			if ( strtotime( $row['modified'] ) > 0 )
			{
				$save['record_updated']	= strtotime( $row['modified'] );
			}
			else
			{
				$save['record_updated'] = $save['record_saved'];
			}
			
			if ( $fields['article_title'] )
			{
				$save['field_' . $fields['article_title']] = $row['title'];
			}
			
			if ( $fields['article_body'] )
			{
				IPSText::getTextClass ( 'bbcode' )->parse_bbcode	= 0;
				IPSText::getTextClass ( 'bbcode' )->parse_html		= 1;
				IPSText::getTextClass ( 'bbcode' )->parse_emoticons	= 1;
				IPSText::getTextClass ( 'bbcode' )->parse_nl2br		= 0;
				IPSText::getTextClass ( 'bbcode' )->parsing_section	= 'global';
				
				// Joomla saves half in intro and half in full
				if ( isset( $row['introtext'] ) && $row['introtext'] )
				{
					if ( ! $row['fulltext'] )
					{
						$row['fulltext'] = $row['introtext'];
					}
					else
					{
						$row['fulltext'] = $row['introtext'] . '<br /><br />' . $row['fulltext'];
					}
				}
				
				$save['field_' . $fields['article_body']] = IPSText::getTextClass ( 'bbcode' )->preDisplayParse ( $this->fixPostData ( $row['fulltext'] ) );
			}
			
			if ( $fields['article_date'] )
			{
				$save['field_' . $fields['article_date']] = strtotime ( $row['created'] );
			}
			
			if ( $fields['article_homepage']	) { $save['field_' . $fields['article_homepage']]	= ',1,';	}
			if ( $fields['article_comments']	) { $save['field_' . $fields['article_comments']]	= 1;		}
			if ( $fields['article_expiry']		) { $save['field_' . $fields['article_expiry']]		= '';		}
			if ( $fields['article_cutoff']		) { $save['field_' . $fields['article_cutoff']]		= '';		}
			if ( $fields['article_image']		) { $save['field_' . $fields['article_image']]		= '';		}
			
			$this->lib->convertArticle ( $row['id'], $save );
		}
		
		$this->lib->next ( );
	}
}