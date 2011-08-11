<?php
/**
 * IPS Converters
 * IP.CCS 1.1 Converters
 * vBulletin CMS Converter
 * Last Update: $Date: 2009-06-10 15:20:15 +0100 (Wed, 10 Jun 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 317 $
 */

$info = array( 'key'   => 'vbulletin',
			   'name'  => 'vBulletin 4.0',
			   'login' => false );

$parent = array( 'required' => true, 'choices' => array( array('app' => 'board', 'key' => 'vbulletin', 'newdb' => false) ) );

class admin_convert_ccs_vbulletin extends ipsCommand
{
	private $attachmentContentTypes = array();
	/**
	* Main class entry point
	*
	* @access	public
	* @param	object		ipsRegistry
	* @return	void
	*/

	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// What can this thing do?
		//-----------------------------------------

		$this->actions = array( 'ccs_database_categories' => array(),
								'ccs_articles'			  => array('ccs_database_categories'),
								'attachments' => array('ccs_articles') );

		//-----------------------------------------
	    // Load our libraries
	    //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_ccs.php' );
		$this->lib =  new lib_ccs( $registry, $html, $this, true, $this->database_id );

	    $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'vBulletin CMS &rarr; IP.Content Converter' );

		//-----------------------------------------
		// Are we connected?
		// (in the great circle of life...)
		//-----------------------------------------
		$this->HB = $this->lib->connect();

		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		if (array_key_exists($this->request['do'], $this->actions))
		{
			call_user_func(array($this, 'convert_'.$this->request['do']));
		}
		else
		{
			$this->lib->menu();
		}

		//-----------------------------------------
	    // Pass to CP output hander
	    //-----------------------------------------
		$this->sendOutput();
	}

	/**
	* Output to screen and exit
	*
	* @access	private
	* @return	void
	*/
	private function sendOutput()
	{
		$this->registry->output->html .= $this->html->convertFooter();
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
		exit;
	}

	/**
	 * Count rows
	 *
	 * @access	private
	 * @param 	string		action (e.g. 'members', 'forums', etc.)
	 * @return 	integer 	number of entries
	 **/
	public function countRows($action)
	{
		switch ($action)
		{
			case 'ccs_database_categories':
				$count = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' 	=> 'count(d.nodeid) as count',
															 'from' 		=> array( 'cms_node' => 'd' ),
															 'add_join' => array( array( 'from' => array( 'cms_nodeinfo' => 'i' ),
															 							 'where' => 'd.nodeid = i.nodeid',
															 							 'type' => 'inner' ) ),
															 'where'		=> 'd.issection=1' ) );
				return $count['count'];
				break;

			case 'ccs_articles':
				return $this->lib->countRows('cms_article');
				break;

			case 'attachments_type':
				return $this->lib->countRows('attachmenttype');
				break;

			case 'attachments':
				$contenttype = ipsRegistry::DB ( 'hb' )->buildAndFetch ( array (
					'select'	=> 'contenttypeid',
					'from'		=> 'contenttype',
					'where'		=> 'class = \'Article\''
				) );
				return $this->lib->countRows('attachment', 'contenttypeid = ' . $contenttype['contenttypeid']);
				break;

			default:
				return $this->lib->countRows($action);
				break;
		}
	}

	/**
	 * Check if section has configuration options
	 *
	 * @access	private
	 * @param 	string		action (e.g. 'members', 'forums', etc.)
	 * @return 	boolean
	 **/
	public function checkConf($action)
	{
		switch ( $action )
		{
			case 'ccs_databases':
			case 'attachments':
				return true;

			default:
				return false;
		}
	}

	/**
	 * Convert Databases
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_ccs_databases()
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		/*$templates = array();
		ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_database=1', 'order' => 'template_id ASC' ) );
		ipsRegistry::DB()->execute();
		while ( $t = ipsRegistry::DB()->fetch() )
		{
			$templates[ $t['template_id'] ] = $t['template_name'];
		}

		$ask = array( 'database_template_listing'		=> array( 'type' => 'dropdown', 'label' => "<strong>Listing Template</strong><br />Select 'Database Listing' if unsure", 'options' => $templates ),
					  'database_template_display'		=> array( 'type' => 'dropdown', 'label' => "<strong>Display Template</strong><br />Select 'Database Display' if unsure", 'options' => $templates ),
					  'database_template_categories'	=> array( 'type' => 'dropdown', 'label' => "<strong>Category Template</strong><br />Select 'Database Categories' if unsure", 'options' => $templates ) );

		$this->lib->saveMoreInfo( 'ccs_databases', array_keys( $ask ) );*/

		$this->lib->saveMoreInfo('ccs_databases', 'map');

		//$articleDatabase = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_databases', 'where' => 'database_is_articles=1' ) );

		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> 'd.*',
						'from' 		=> array( 'cms_node' => 'd' ),
						'add_join' => array( array( 'select' => 'i.*',
													'from' => array( 'cms_nodeinfo' => 'i' ),
													'where' => 'd.nodeid = i.nodeid',
													'type' => 'inner' ) ),
						'where'		=> 'd.issection=1',
						'order' => 'd.nodeid ASC',
					);

		$loop = $this->lib->load('ccs_databases', $main, array(), array(), TRUE );

		$this->lib->getMoreInfo('ccs_databases', $loop, array('new' => '--Create new database--', 'ot' => 'Old database', 'nt' => 'New database'), '', array('idf' => 'nodeid', 'nf' => 'title'));

		//--------------------------------------
		// Insert Database
		//--------------------------------------

		foreach ( $loop as $row )
		{
			$this->lib->convertDatabase( $row['nodeid'], array( 'database_name' => $row['title'],
																'database_key'					=> $row['url'],
																'database_description' => $row['description'],
																//'database_database'
																//'database_template_listing'		=> $us['database_template_listing'],
																//'database_template_display'		=> $us['database_template_display'],
																'database_user_editable'		=> 1,
																'database_all_editable'			=> 0,
																'database_open'					=> 1,
																'database_comments'				=> $row['comments_enabled'],
																'database_rate'					=> $row['showrating'],
																'database_revisions'			=> 0,
																//'database_template_categories'	=> $us['database_template_categories'],
																'database_field_title'			=> 'primary_id_field',
																'database_field_sort'			=> 'record_updated',
																'database_field_direction'		=> 'desc',
																'database_field_perpage'		=> 25,
																'database_record_approve'		=> 0,
																'database_comment_approve'		=> 0 ), NULL);



			/*$this->lib->convertDatabase( $row['nodeid'], array( 'database_name' => $row['url'],
																'database_key'					=> $row['url'],
																'database_template_listing'		=> $us['database_template_listing'],
																'database_template_display'		=> $us['database_template_display'],
																'database_user_editable'		=> 1,
																'database_all_editable'			=> 0,
																'database_open'					=> 1,
																'database_comments'				=> 1,
																'database_rate'					=> 1,
																'database_revisions'			=> 0,
																'database_template_categories'	=> $us['database_template_categories'],
																'database_field_title'			=> 'primary_id_field',
																'database_field_sort'			=> 'record_updated',
																'database_field_direction'		=> 'desc',
																'database_field_perpage'		=> 25,
																'database_record_approve'		=> 0,
																'database_comment_approve'		=> 0 ), array( array( 'field_name'			=> 'Title',
																													  'field_description'		=> '',
																													  'field_type'			=> 'input',
																													  'field_required'		=> 1,
																													  'field_user_editable'	=> 1,
																													  'field_position'		=> 1,
																													  'field_max_length'		=> 0,
																													  'field_html'			=> 0,
																													  'field_is_numeric'		=> 0,
																													  'field_truncate'		=> 0 ),
																											   array( 'field_name'			=> 'Content',
																										 			  'field_description'		=> '',
																													  'field_type'			=> 'editor',
																													  'field_required'		=> 1,
																													  'field_user_editable'	=> 1,
																													  'field_position'		=> 2,
																													  'field_max_length'		=> 0,
																													  'field_html'			=> 0,
																													  'field_is_numeric'		=> 0,
																													  'field_truncate'		=> 0 ),
																											   array( 'field_name'			=> 'Attachments',
																													  'field_description'		=> '',
																													  'field_type'			=> 'attachments',
																													  'field_required'		=> 0,
																													  'field_user_editable'	=> 1,
																													  'field_position'		=> 3,
																													  'field_max_length'		=> 0,
																													  'field_html'			=> 0,
																													  'field_is_numeric'		=> 0,
																													  'field_truncate'		=> 0 ) ) );*/

		}
		//--------------------------------------
		// Finish
		//--------------------------------------
		$this->lib->next();
	}

	/**
	 * Convert Database Categories
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_ccs_database_categories()
	{
		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> 'd.nodeid, d.parentnode, d.url, d.showall',
						'from' 		=> array( 'cms_node' => 'd' ),
						'add_join' => array( array( 'select' => 'i.description, i.title, i.keywords',
													'from' => array( 'cms_nodeinfo' => 'i' ),
													'where' => 'd.nodeid = i.nodeid',
													'type' => 'inner' ) ),
						'where'		=> 'd.issection=1',
						'order' => 'd.nodeid ASC',
					);

		$loop = $this->lib->load( 'ccs_database_categories', $main );

		//---------------------------
		// Loop
		//---------------------------
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array( 'category_database_id'	=> 1,
						   'category_name'			=> $row['title'],
						   'category_description'	=> $row['description'],
						   'category_parent_id'		=> $row['parentnode'] > 0 ? $row['parentnode'] : 0,
						   'category_show_records' => $row['showall'],
						   'category_furl_name' => $row['url'],
						   'category_meta_keywords' => $row['keywords'] );

			$this->lib->convertDatabaseCategory($row['nodeid'], $save);
		}

		$this->lib->next();
	}

	/**
	 * Convert Database Entries
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_ccs_articles()
	{
		//---------------------------
		// Set up
		//---------------------------
		$main = array( 'select' => 'a.contentid, a.pagetext',
					   'from' => array('cms_article' => 'a'),
					   'add_join' => array( array( 'select' => 'n.nodeid, n.userid, n.publishdate, n.lastupdated, n.comments_enabled, n.parentnode, n.setpublish',
					   							   'from' => array( 'cms_node' => 'n' ),
					   							   'where'  => "n.contentid = a.contentid",
					   							   'type' => 'inner' ),
					   						array( 'select' => 'i.title',
					   							   'from' => array('cms_nodeinfo' => 'i'),
					   							   'where'  => "n.nodeid = i.nodeid",
					   							   'type' => 'inner' ) ),
					   'order' => 'a.contentid ASC' );

		/*$main = array( 'select' => 'n.contentid, n.nodeid, n.userid, n.publishdate, n.lastupdated, n.comments_enabled, n.parentnode, n.setpublish',
					   'from' => array( 'cms_node' => 'n' ),
					   'add_join' => array( array( 'select' => 'a.pagetext',
					   							   'from' => array('cms_article' => 'a'),
					   							   'where'  => "n.contentid = a.contentid",
					   							   'type' => 'inner' ),
					   						array( 'select' => 'i.title',
					   							   'from' => array('cms_nodeinfo' => 'i'),
					   							   'where'  => "n.nodeid = i.nodeid",
					   							   'type' => 'inner' ) ),
					 //  'where' => 'n.issection=0',
					   'order' => 'n.nodeid ASC' );*/

		$loop = $this->lib->load( 'ccs_articles', $main );

		//--------------------------------------
		// Get fields
		//--------------------------------------

		$fields = array();
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_database_fields', 'where' => 'field_database_id=1' ) );
		$this->DB->execute();
		while ( $row = $this->DB->fetch() )
		{
			$fields[ $row['field_key'] ] = $row['field_id'];
		}

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//--------------------------------------
			// Convert
			//--------------------------------------
			$save = array( 'member_id'						=> $row['userid'],
						   'record_saved'					=> $row['publishdate'],
						   'record_updated'					=> $row['lastupdated'],
						   'category_id'					=> $this->lib->getLink( $row['parentnode'], 'ccs_database_categories' ),
						   'record_locked'					=> $row['comments_enabled'] ? 0 : 1,
						   'record_approved'				=> $row['setpublish'],
						   'record_dynamic_furl'			=> IPSText::makeSeoTitle($row['title'])
						   //'field_'.$fields[ $this->lib->getLink( $row['parentnode'], 'ccs_databases' ) ]['Attachments']	=> $row['hasattach'],
						   );

			if ( $fields['article_title'] )
			{
				$save['field_'.$fields['article_title']] = htmlspecialchars($row['title']);
			}
			if ( $fields['article_body'] )
			{
				IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
				IPSText::getTextClass('bbcode')->parse_html			= 0;
				IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
				IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
				IPSText::getTextClass('bbcode')->parsing_section	= 'global';
				
				// Video tag
				$row['pagetext']	= preg_replace( "#video=youtube;(.+?)]#is", 'media]', $row['pagetext'] );
				$row['pagetext']	= str_replace( '[/video]', '[/media]', $row['pagetext'] );
				
				$save['field_'.$fields['article_body']] = IPSText::getTextClass('bbcode')->preDbParse( $this->fixPostData($row['pagetext']) );
			}
			if ( $fields['article_date'] )
			{
				$save['field_'.$fields['article_date']] = $row['publishdate'];

			}

			if ( $fields['article_homepage'] ) { $save['field_'.$fields['article_homepage']] = ',1,'; }
			if ( $fields['article_comments'] ) { $save['field_'.$fields['article_comments']] = 1; }
			// Fields not yet used: article_expiry, article_cutoff, article_image

			$this->lib->convertArticle( $row['nodeid'], $save );
		}
		$this->lib->next();
	}

	/**
	 * Convert Mime Types
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_attachments_type()
	{
		//---------------------------
		// Set up
		//---------------------------
		$main = array(	'select' 	=> '*',
						'from' 		=> 'attachmenttype',
					);

		$loop = $this->lib->load('attachments_type', $main);

		//---------------------------
		// Loop
		//---------------------------
		$count = $this->request['st'];

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$count++;

			$rm = unserialize($row['mimetype']);
			$mime = str_replace('Content-type: ', '', $rm[0]);

			$save = array(
				'atype_extension'	=> $row['extension'],
				'atype_mimetype'	=> $mime,
				);

			$this->lib->convertAttachType($count, $save);
		}
		$this->lib->next();
	}

	/**
	 * Convert Attachments
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_attachments()
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$this->lib->saveMoreInfo('attachments', array('attach_path'));

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> 'a.*',
						'from' 		=> array( 'attachment' => 'a' ),
						'add_join' => array( array( 'select' => 'c.class',
													 'from' => array( 'contenttype' => 'c' ),
													 'where' => 'c.contenttypeid=a.contenttypeid',
													 'type' => 'left' ) ),
						'where' => "c.class='Article'",
						'order' => 'a.attachmentid ASC' );

		$loop = $this->lib->load('attachments', $main);

		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------
		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - if using database storage, enter "."):')), 'path');

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$path = $us['attach_path'];

		//-----------------------------------------
		// Check all is well
		//-----------------------------------------
		if (!is_writable($this->settings['upload_dir']))
		{
			$this->lib->error('Your IP.Board upload path is not writeable. '.$this->settings['upload_dir']);
		}

		//---------------------------
		// Loop
		//---------------------------
		$postField = NULL;
		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			//-----------------------------------------
			// Init
			//-----------------------------------------
			$filedata = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'filedata', 'where' => "filedataid='" .intval($row['filedataid'])."'" ) );

			// What's the mimetype?
			$type = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments_type', 'where' => "atype_extension='{$filedata['extension']}'" ) );

			// Is this an image?
			$image = false;
			if (preg_match('/image/', $type['atype_mimetype']))
			{
				$image = true;
			}
			
			// Need to grab the topic ID
			$category 		= ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'parentnode', 'from' => 'cms_node', 'where' => "contentid='" . intval( $row['contentid'] ) . "'" ) );
			
			// Grab our real id
			if ( $category['parentnode'] )
			{
				$realCategory	= $this->lib->getLink( $category['parentnode'], 'ccs_database_categories' );
			}
			
			$save = array(
				'attach_ext'			=> $filedata['extension'],
				'attach_file'			=> $row['filename'],
				'attach_is_image'		=> $image,
				'attach_hits'			=> $row['counter'],
				'attach_date'			=> $row['dateline'],
				'attach_member_id'		=> $row['userid'],
				'attach_filesize'		=> $filedata['filesize'],
				'attach_rel_id'			=> $row['contentid'],
				'attach_rel_module'		=> 'ccs',
				'attach_parent_id'		=> $realCategory
				);

			//-----------------------------------------
			// Database
			//-----------------------------------------
			if ($filedata['filedata'])
			{
				$save['attach_location'] = $row['filename'];
				$save['data'] = $filedata['filedata'];

				$done = $this->lib->convertAttachment($row['attachmentid'], $save, '', true);
			}
			//-----------------------------------------
			// File storage
			//-----------------------------------------
			else
			{
				if ($path == '.')
				{
					$this->lib->error('You entered "." for the path but you have some attachments in the file system');
				}

				$tmpPath = '/' . implode('/', preg_split('//', $filedata['userid'],  -1, PREG_SPLIT_NO_EMPTY));
				$save['attach_location'] = "{$row['filedataid']}.attach";

				$done = $this->lib->convertAttachment($row['attachmentid'], $save, $path . $tmpPath);
			}

			//-----------------------------------------
			// Fix inline attachments
			//-----------------------------------------
			if ($done === true)
			{
				$aid = $this->lib->getLink($row['attachmentid'], 'attachments');
				$pid = $this->lib->getLink($save['attach_rel_id'], 'ccs_articles');

				if ( $pid )
				{
					if ( $postField == NULL )
					{
						$field = $this->DB->buildAndFetch( array( 'select' => 'field_id', 'from' => 'ccs_database_fields', 'where' => "field_database_id='1' AND field_key='article_body'" ) );
						$postField = 'field_'.$field['field_id'];
					}
					$attachrow = $this->DB->buildAndFetch( array( 'select' => $postField, 'from' => 'ccs_custom_database_1', 'where' => "primary_id_field={$pid}" ) );

					$rawaid = $row['attachmentid'];
					$update = preg_replace("/\[ATTACH=CONFIG\]".$rawaid."\[\/ATTACH\]/i", "[attachment={$aid}:{$save['attach_location']}]", $attachrow[$postField]);

					$this->DB->update('ccs_custom_database_1', array($postField => $update), "primary_id_field={$pid}");
				}
			}

		}
		$this->lib->next();
	}

	private function fixPostData($post)
	{
		$post = str_replace("<", "&lt;", $post);
		$post = str_replace(">", "&gt;", $post);

		// Sort out newlines
		$post = nl2br($post);

		// And quote tags
		$post = preg_replace("#\[quote=([^\];]+?)\]#i", "[quote name='$1']", $post);
		$post = preg_replace("#\[quote=([^\];]+?);\d+\]#i", "[quote name='$1']", $post);
		//$post = preg_replace("#\[quote=(.+)\](.+)\[/quote\]#i", "[quote name='$1']$2[/quote]", $post);



		return $post;
	}
}
