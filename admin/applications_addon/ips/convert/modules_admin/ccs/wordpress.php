<?php
/**
 * IPS Converters
 * IP.Content Converters
 * WordPress 3.1 Converter
 * Last Update: $Date: 2011-06-08 12:44:41 -0400 (Wed, 08 Jun 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Ryan Ashbrook
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 529 $
 */

$info = array (
	'key'	=> 'wordpress',
	'name'	=> 'WordPress 3.1',
	'login'	=> false,
);

class admin_convert_ccs_wordpress extends ipsCommand
{
	public function doExecute ( ipsRegistry $registry )
	{
		$this->registry = $registry;
		
		$this->actions = array (
			'members'					=> array ( ),
			'ccs_database_categories'	=> array ( ),
			'ccs_articles'				=> array ( 'members', 'ccs_database_categories' ),
			'ccs_database_comments'		=> array ( 'members', 'ccs_articles' ),
			'ccs_pages'					=> array ( ),
		);
		
		require_once ( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once ( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_ccs.php' );
		$this->lib = new lib_ccs ( $registry, $html, $this );
		
		$this->html = $this->lib->loadInterface ( );
		$this->lib->sendHeader ( 'WordPress 3.1 &rarr; IP.Content Converter' );
		
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
			case 'members':
				return $this->lib->countRows ( 'users' );
			break;
			
			case 'ccs_database_categories':
				return $this->lib->countRows ( 'term_taxonomy', 'taxonomy = \'category\'' );
			break;
			
			case 'ccs_articles':
				return $this->lib->countRows ( 'posts', 'post_type = \'post\'' );
			break;
			
			case 'ccs_database_comments':
				return $this->lib->countRows ( 'comments', 'comment_type != \'pingback\'' );
			break;
			
			case 'ccs_pages':
				return $this->lib->countRows ( 'posts', 'post_type = \'page\'' );
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
			case 'members':
				return true;
			break;
			default:
				return false;
			break;
		}
	}
	
	/**
	 * Convert Members
	 * 
	 * Adapted from admin_convert_blog_wordpress::convert_members()
	 * 
	 * @access	private
	 * @return	void
	 */
	private function convert_members ( )
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------

		$pcpf = array (
			'description'	=> 'Interests',
			'aim'			=> 'AIM ID',
			'yim'			=> 'Yahoo ID',
			'jabber'		=> 'Jabber ID',
			'user_url'		=> 'Website'
		);
			
		$this->lib->saveMoreInfo ( 'members', array_keys ( $pcpf ) );

		//---------------------------
		// Set up
		//---------------------------
			
		$main = array (
			'select'	=> 'u.*, UNIX_TIMESTAMP( u.user_registered ) AS TS_user_registered',
			'from'		=> array ( 'users' => 'u' ),
			'order'		=> 'u.ID ASC',
			'add_join'	=> array (
				array (
					'select'	=> 'um.meta_value as user_level',
					'from'		=> array ( 'usermeta' => 'um' ),
					'where'		=> "u.ID=um.user_id AND meta_key='wp_user_level'"
				)
			)
		);
			
		$loop = $this->lib->load('members', $main);
			
		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
			
		$get = unserialize ( $this->settings['conv_extra'] );
		$us = $get[$this->lib->app['name']];
		$ask = array ( );
			
		// And those custom profile fields
		$options = array ( 'x' => '-Skip-' );
		$this->DB->build ( array (
			'select'	=> '*',
			'from'		=> 'pfields_data'
		) );
		$this->DB->execute ( );
		while ( $row = $this->DB->fetch ( ) )
		{
			$options[$row['pf_id']] = $row['pf_title'];
		}
		foreach ( $pcpf as $id => $name )
		{
			$ask[$id] = array (
				'type'		=> 'dropdown',
				'label'		=> 'Custom profile field to store '.$name.': ',
				'options'	=> $options,
				'extra'		=> $extra
			);
		}
		
		$this->lib->getMoreInfo ( 'members', $loop, $ask, 'path' );
			
		//-----------------------------------------
		// Get our custom profile fields
		//-----------------------------------------
			
		if ( isset ( $us['pfield_group'] ) )
		{
			$this->DB->build ( array (
				'select'	=> '*',
				'from'		=> 'pfields_data',
				'where'		=> 'pf_group_id='.$us['pfield_group']
			) );
			$this->DB->execute ( );
			$pfields = array ( );
			while ( $row = $this->DB->fetch ( ) )
			{
				$pfields[] = $row;
			}
		}
		else
		{
			$pfields = array ( );
		}

		//---------------------------
		// Loop
		//---------------------------
			
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			//-----------------------------------------
			// Set info
			//-----------------------------------------

			// Basic info				
			$info = array (
				'id'				=> $row['ID'],
				'group'				=> ( $row['user_level'] > 9 ) ? $this->settings['admin_group'] : $this->settings['member_group'],
				'secondary_groups'	=> '',
				'joined'			=> $row['TS_user_registered'],
				'username'			=> $row['user_login'],
				'displayname'		=> $row['display_name'],
				'email'				=> $row['user_email'],
				'password'			=> $row['user_pass'],
			);
				
			// Member info
			$members = array (
				'ip_address'		=> '0.0.0.0',
				'conv_password'		=> $row['user_pass'],
				'bday_day'			=> '',
				'bday_month'		=> '',
				'bday_year'			=> '',
				'last_visit'		=> 0,
				'last_activity' 	=> 0,
				'posts'				=> 0,
				'email_pm'      	=> 1,
				'members_disable_pm'=> 0,
				'hide_email' 		=> 1,
				'allow_admin_mails' => 1,
			);
				
			// Profile - nothing here
		
			//-----------------------------------------
			// Avatars
			//-----------------------------------------
			// Wordpress handles avatars with a plugin
			// so not a default feature, gravatar only
			//-----------------------------------------
			
			//-----------------------------------------
			// Profile fields
			//-----------------------------------------
			// We couldn't join this because there are
			// several rows for the same user..
			//-----------------------------------------
			$userpfields = array ( );
				
			ipsRegistry::DB ( 'hb' )->build ( array (
				'select' => '*',
				'from'   => 'usermeta',
				'where'  => "user_id={$row['ID']} AND meta_key IN ('".implode ( "','", array_keys ( $pcpf ) )."')",
			 ) );
			ipsRegistry::DB ( 'hb' )->execute ( );
				
			while ( $wpf = ipsRegistry::DB ( 'hb' )->fetch ( ) )
			{
				$userpfields[ $wpf['meta_key'] ] = $wpf['meta_value'];
			}
				
			// Sort out also the website
			if ( $row['user_url'] )
			{
				$userpfields['user_url'] = $row['user_url'];
			}
				
			// Pseudo
			foreach ( $pcpf as $id => $name )
			{
				if ( $us[$id] != 'x' )
				{
					$custom['field_'.$us[$id]] = $row[$id];
				}
			}
				
			// Actual
			foreach ( $pfields as $field )
			{
				if ( $field['pf_type'] == 'drop' )
				{
					$custom['field_'.$field['pf_id']] = $us['pfield_data'][$field['pf_key']][$userpfields[$field['pf_key']]-1];
				}
				else
				{
					$custom['field_'.$field['pf_id']] = $userpfields[$field['pf_key']];
				}
			}
			
			//-----------------------------------------
			// And go!
			//-----------------------------------------

			$this->lib->convertMember ( $info, $members, array ( ), $custom, '', '', FALSE );
		}
			
		$this->lib->next ( );
	}
	
	private function convert_ccs_database_categories ( )
	{
		$main = array (
			'select'	=> 'tt.*',
			'from'		=> array ( 'term_taxonomy' => 'tt' ),
			'add_join'	=> array (
				array (
					'select'	=> 't.*',
					'from'		=> array ( 'terms'	=> 't' ),
					'where'		=> 'tt.term_id = t.term_id',
				),
			),
			'where'		=> 'taxonomy = \'category\'',
			'order'		=> 'term_taxonomy_id ASC',
		);
		
		$loop = $this->lib->load ( 'ccs_database_categories', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'category_database_id'	=> 1,
				'category_name'			=> $row['name'],
				'category_parent_id'	=> 0,
				'category_show_records'	=> 1,
				'category_furl_name'	=> $row['slug'],
				'category_template'		=> 4,
			);
			
			$this->lib->convertDatabaseCategory ( $row['term_taxonomy_id'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_ccs_articles ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'posts',
			'where'		=> 'post_type = \'post\'',
		);
		
		$loop = $this->lib->load ( 'ccs_articles', $main );
		
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
			$categories = $this->_fetchArticleCategories ( );
			$save = array (
				'member_id'			=> $row['post_author'],
				'record_saved'		=> strtotime ( $row['post_date'] ),
				'record_updated'	=> strtotime ( $row['post_modified'] ),
				'category_id'		=> ( array_key_exists ( $row['ID'], $categories ) ? $categories[$row['ID']] : 1 ),
				'record_locked'		=> ( $row['comment_status'] == 'open' ? 0 : 1 ),
				'record_approved'	=> ( $row['post_status'] != 'publish' ? 0 : 1 ),
			);
			
			if ( $fields['article_title'] )
			{
				$save['field_' . $fields['article_title']] = $row['post_title'];
			}
			
			if ( $fields['article_body'] )
			{
				IPSText::getTextClass ( 'bbcode' )->parse_bbcode	= 0;
				IPSText::getTextClass ( 'bbcode' )->parse_html		= 1;
				IPSText::getTextClass ( 'bbcode' )->parse_emoticons	= 1;
				IPSText::getTextClass ( 'bbcode' )->parse_nl2br		= 0;
				IPSText::getTextClass ( 'bbcode' )->parsing_section	= 'global';
				$save['field_' . $fields['article_body']] = IPSText::getTextClass ( 'bbcode' )->preDisplayParse ( $this->fixPostData ( $row['post_content'] ) );
			}
			
			if ( $fields['article_date'] )
			{
				$save['field_' . $fields['article_date']] = strtotime ( $row['post_date'] );
			}
			
			if ( $fields['article_homepage']	) { $save['field_' . $fields['article_homepage']]	= ',1,';	}
			if ( $fields['article_comments']	) { $save['field_' . $fields['article_comments']]	= 1;		}
			if ( $fields['article_expiry']		) { $save['field_' . $fields['article_expiry']]		= '';		}
			if ( $fields['article_cutoff']		) { $save['field_' . $fields['article_cutoff']]		= '';		}
			if ( $fields['article_image']		) { $save['field_' . $fields['article_image']]		= '';		}
			
			
			
			$this->lib->convertArticle ( $row['ID'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_ccs_database_comments ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'comments',
			'where'		=> 'comment_type != \'pingback\'',
			'order'		=> 'comment_ID ASC',
		);
		
		$loop = $this->lib->load ( 'ccs_database_comments', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'comment_user'			=> $row['user_id'],
				'comment_database_id'	=> 1,
				'comment_record_id'		=> $row['comment_post_ID'],
				'comment_date'			=> strtotime ( $row['comment_date'] ),
				'comment_ip_address'	=> $row['comment_author_IP'],
				'comment_post'			=> $row['comment_content'],
				'comment_approved'		=> $row['comment_approved'],
			);
			
			$this->lib->convertDatabaseComment ( $row['comment_ID'], $save );
		}
		$this->lib->next ( );
	}
	
	private function convert_ccs_pages ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'posts',
			'where'		=> 'post_type = \'page\'',
			'order'		=> 'ID ASC',
		);
		
		$loop = $this->lib->load ( 'ccs_pages', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'page_name'				=> $row['post_title'],
				'page_seo_name'			=> $row['post_name'],
				'page_type'				=> 'html',
				'page_template_used'	=> 0,
				'page_content'			=> $row['post_content'],
				'page_view_perms'		=> '*',
				'page_cache_ttl'		=> 0,
				'page_cache_last'		=> 0,
				'page_content_only'		=> 1,
				'page_content_type'		=> 'page',
				'page_ipb_wrapper'		=> 1,
				'page_folder'			=> '',
			);
			$this->lib->convertPage ( $row['ID'], $save );
		}
		$this->lib->next ( );
	}
	
	private function fixPostData ( $post )
	{
		return nl2br ( $post );
	}
	
	private function _fetchArticleCategories ( )
	{
		$cats = array ( );
		ipsRegistry::DB ( 'hb' )->build ( array (
			'select'	=> 'tt.*',
			'from'		=> array ( 'term_taxonomy' => 'tt' ),
			'add_join'	=> array (
				array (
					'select'	=> 't.*',
					'from'		=> array ( 'terms' => 't' ),
					'where'		=> 'tt.term_id = t.term_id',
					'type'		=> 'inner',
				),
				array (
					'select'	=> 'tr.*',
					'from'		=> array ( 'term_relationships' => 'tr' ),
					'where'		=> 'tt.term_taxonomy_id = tr.term_taxonomy_id',
					'type'		=> 'inner',
				),
			),
			'where'		=> 'tt.taxonomy = \'category\'',
		) );
		$termQry = ipsRegistry::DB ( 'hb' )->execute ( );
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $termQry ) )
		{
			// If we already have a category for this object, skip it.
			if ( array_key_exists ( $row['object_id'], $cats ) )
			{
				continue;
			}
			$cats[$row['object_id']] = $row['term_taxonomy_id'];
		}
		return $cats;
	}
}

?>