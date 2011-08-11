<?php
/**
 * IPS Converters
 * Application Files
 * Library functions for IP.CCS 1.0 conversions
 * Last Update: $Date: 2009-11-10 16:41:27 +0100(mar, 10 nov 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 381 $
 */
	class lib_ccs extends lib_master
	{
		/**
	     * Information box to display on convert screen
	     *
	     * @access	public
	     * @return	string 		html to display
	     */
		public function getInfo()
		{
			return "<strong>Recache Categoriesc</strong><br />
				<a href='{$this->settings['base_url']}&app=ccs&module=articles&section=categories&do=recache' target='_blank'>Click here</a> to rebuild categories:
				<br /><strong>Turn the application back online</strong><br />
				Visit your IP.CCS settings and turn the application back online.";
		}

		/**
		 * Return the information needed for a specific action
		 *
	     * @access	public
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @param	boolean		If true, will return false instead of printing error if encountered
		 * @return 	array 		info needed for html->convertMenuRow
		 **/
		public function menuRow($action='', $return=false)
		{
			switch ($action)
			{
				case 'ccs_containers':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ccs_containers' ) );
					$return = array(
						'name'	=> 'Containers',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;

				case 'ccs_folders':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ccs_folders' ) );
					$return = array(
						'name'	=> 'Folders',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;

				case 'ccs_blocks':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ccs_blocks' ) );
					$return = array(
						'name'	=> 'Blocks',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;

				case 'ccs_page_templates':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ccs_page_templates' ) );
					$return = array(
						'name'	=> 'Templates',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;

				case 'ccs_pages':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ccs_pages' ) );
					$return = array(
						'name'	=> 'Pages',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;

				case 'ccs_database_categories':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ccs_database_categories' ) );
					$return = array(
						'name'	=> 'Database Categories',
						'rows'	=> $count['count'],
						'cycle'	=> 1000,
					);
					break;

				case 'database_entries':
					$this->DB->build( array( 'select' => 'database_id', 'from' => 'ccs_databases' ) );
					$dbRes = $this->DB->execute();
					$count = array( 'count' => 0 );
					while ( $row = $this->DB->fetch($dbRes) )
					{
						$tmpCount = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ccs_custom_database_'.$row['database_id'] ) );
						$count['count'] += $tmpCount['count'];
					}
					$return = array(
						'name'	=> 'Database Entries',
						'rows'	=> $count['count'],
						'cycle'	=> 1000,
					);
					break;

				case 'ccs_articles':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ccs_custom_database_1' ) );
					$return = array(
						'name'	=> 'Articles',
						'rows'	=> $count['count'],
						'cycle'	=> 1000,
					);
					break;

				case 'ccs_database_comments':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ccs_database_comments' ) );
					$return = array(
						'name'	=> 'Database Comments',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;

				case 'ccs_attachments':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'attachments', 'where' => "attach_rel_module='ccs'" ) );
					$return = array(
						'name'	=> 'Attachments',
						'rows'	=> $count['count'],
						'cycle'	=> 100,
					);
					break;

				case 'ccs_databases':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'ccs_databases' ) );
					$return = array(
						'name'	=> 'Databases',
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

				default:
					if ($return)
					{
						return false;
					}
					$this->error("There is a problem with the converter: called invalid action {$action}");
					break;
			}

			$basic = array('section' => $this->app['app_key'], 'key' => $action, 'app' => 'ccs');
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
				case 'ccs_attachments':
					return array( 'attachments' => 'attach_id' );
					break;
				case 'ccs_blocks':
					return array( 'ccs_blocks' => 'block_id' );
					break;
				case 'ccs_containers':
					return array( 'ccs_containers' => 'container_id' );
					break;
				case 'ccs_database_categories':
					return array( 'ccs_database_categories' => 'category_id' );
					break;
				case 'ccs_database_comments':
					return array( 'ccs_database_comments' => 'comment_id' );
					break;
				case 'database_entries':
					return array( 'database_entries' => 'primary_id_field' );
					break;
				case 'ccs_articles':
					return array( 'ccs_custom_database_1' => 'primary_id_field' );
					break;
				case 'ccs_databases':
					return array( 'ccs_databases' => 'database_id' );
					break;
				case 'ccs_folders':
					return array( 'ccs_folders' => 'link_id' );
					break;
				case 'ccs_page_templates':
					return array( 'ccs_page_templates' => 'template_id' );
					break;
				case 'ccs_pages':
					return array( 'ccs_pages' => 'page_id' );
					break;

				case 'attachments_type':
					return array( 'attachments_type' => 'atype_id' );
					break;

				case 'attachments':
					return array( 'attachments' => 'attach_id' );
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
				case 'ccs_database_categories':
					return array('addfield' => array('ccs_database_categories', 'conv_parent', 'varchar(5)'));
					break;

				case 'ccs_folders':
					return array('addfield' => array('ccs_folders', 'link_id', 'mediumint(10)'));
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
			return $report;
		}

		/**
		 * Convert a Container
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertContainer($id, $info)
		{
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------

			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['container_name'])
			{
				$this->logError($id, 'No name provided');
				return false;
			}
			if (!$info['container_type'])
			{
				$this->logError($id, 'No type provided');
				return false;
			}

			//-----------------------------------------
			// Insert
			//-----------------------------------------

			unset($info['container_id']);
			$this->DB->insert( 'ccs_containers', $info );
			$inserted_id = $this->DB->getInsertId();

			//-----------------------------------------
			// Add link
			//-----------------------------------------

			$this->addLink($inserted_id, $id, 'ccs_containers');

			return true;
		}

		/**
		 * Convert a Block
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertBlock($id, $info)
		{
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------

			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['block_name'])
			{
				$this->logError($id, 'No name provided');
				return false;
			}
			if (!$info['block_type'])
			{
				$this->logError($id, 'No type provided');
				return false;
			}
			if (!$info['block_key'])
			{
				$this->logError($id, 'No key provided');
				return false;
			}

			//-----------------------------------------
			// Insert
			//-----------------------------------------

			$info['block_category'] = ($info['block_category']) ? $this->getLink($info['block_category'], 'ccs_containers') : 0;

			unset($info['block_id']);
			$this->DB->insert( 'ccs_blocks', $info );
			$inserted_id = $this->DB->getInsertId();

			//-----------------------------------------
			// Add link
			//-----------------------------------------

			$this->addLink($inserted_id, $id, 'ccs_blocks');

			return true;
		}

		/**
		 * Convert a Template
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertTemplate($id, $info)
		{
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------

			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['template_name'])
			{
				$this->logError($id, 'No name provided');
				return false;
			}
			if (!$info['template_key'])
			{
				$this->logError($id, 'No key provided');
				return false;
			}

			//-----------------------------------------
			// Insert
			//-----------------------------------------

			$info['template_category'] = ($info['template_category']) ? $this->getLink($info['template_category'], 'ccs_containers') : 0;

			unset($info['template_id']);
			$this->DB->insert( 'ccs_page_templates', $info );
			$inserted_id = $this->DB->getInsertId();

			//-----------------------------------------
			// Add link
			//-----------------------------------------

			$this->addLink($inserted_id, $id, 'ccs_page_templates');

			return true;
		}

		/**
		 * Convert a Folder
		 *
		 * @access	public
		 * @param 	string		Folder Path
		 * @param 	int	 		Last modified
		 * @param 	int	 		An ID number for the link table
		 * @return 	boolean		Success or fail
		 **/
		public function convertFolder($path, $last_modified, $count)
		{
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------

			if (!$path)
			{
				$this->logError($id, 'No path provided');
				return false;
			}

			//-----------------------------------------
			// Insert
			//-----------------------------------------

			$this->DB->insert( 'ccs_folders', array('folder_path' => $path, 'last_modified' => $last_modified, 'link_id' => $count) );

			//-----------------------------------------
			// Add link
			//-----------------------------------------

			$this->addLink($count, $count, 'ccs_folders');

			return true;
		}

		/**
		 * Convert a Page
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertPage($id, $info)
		{
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------

			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['page_name'])
			{
				$this->logError($id, 'No name provided');
				return false;
			}
			if (!$info['page_type'])
			{
				$this->logError($id, 'No type provided');
				return false;
			}
			if (!$info['page_content'])
			{
				$this->logError($id, 'No content provided');
				return false;
			}

			//-----------------------------------------
			// Link
			//-----------------------------------------

			$info['page_template_used'] = ($info['page_template_used']) ? $this->getLink($info['page_template_used'], 'ccs_page_templates') : 0;

			if ($info['page_view_perms'] != '*')
			{
				$explode = explode(',', $info['page_view_perms']);
				$perms = array();
				foreach ($explode as $perm)
				{
					$perms[] = $this->getLink($perm, 'forum_perms', false, true);
				}
				$info['page_view_perms'] = implode(',', $perms);
			}

			//-----------------------------------------
			// Insert
			//-----------------------------------------

			unset($info['page_id']);
			$this->DB->insert( 'ccs_pages', $info );
			$inserted_id = $this->DB->getInsertId();

			//-----------------------------------------
			// Add link
			//-----------------------------------------

			$this->addLink($inserted_id, $id, 'ccs_pages');

			return true;
		}

		/**
		 * Convert a Database
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertDatabase($id, $info)
		{
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}

			//-----------------------------------------
			// Just merging?
			//-----------------------------------------
			$us = unserialize($this->settings['conv_extra']);
			$extra = $us[$this->app['name']];

			if ($extra['ccs_databases'][$id] != 'x')
			{
				$this->addLink($extra['ccs_databases'][$id], $id, 'ccs_databases', 1);
			}
			//-----------------------------------------
			// Or creating one?
			//-----------------------------------------
			else
			{
				if (!$info['database_name'])
				{
					$this->logError($id, 'No name provided');
					return false;
				}
				if (!$info['database_key'])
				{
					$this->logError($id, 'No key provided');
					return false;
				}

				$_key	= $this->DB->buildAndFetch( array( 'select' => 'database_id', 'from' => 'ccs_databases', 'where' => "database_key='{$info['database_key']}'" ) );

				if ( $_key['database_id'] )
				{	$key = $info['database_key'].time();
					$this->logError($info['id'], "Database key {$info['database_key']} was already in use. Database was created with key {$key}");
					$info['database_key'] = $key;
				}
				//unset($info['database_comment_approve']);
				//-----------------------------------------
				// Insert
				//-----------------------------------------
				$this->DB->insert( 'ccs_databases', $info );
				$inserted_id = $this->DB->getInsertId();

				//-----------------------------------------
				// Add link
				//-----------------------------------------
				$this->addLink($inserted_id, $id, 'ccs_databases');

				//-----------------------------------------
				// Create new table
				//-----------------------------------------
				require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/' . strtolower($this->settings['sql_driver']) . '.php' );
				$_dbAbstraction	= new ccs_database_abstraction( $this->registry );
				$_dbAbstraction->createTable( $this->settings['sql_tbl_prefix'] . 'ccs_custom_database_' . $inserted_id );

				$this->DB->update( 'ccs_databases', array( 'database_database' => 'ccs_custom_database_' . $inserted_id ), 'database_id=' . $inserted_id );

				$this->databaseAddField( array( 'field_database_id' => $inserted_id,
												'field_name' => 'Title',
												'field_key' => 'article_title',
												'field_type' => 'input',
												'field_required' => '1',
												'field_user_editable' => '1',
												'field_max_length' => '500',
												'field_truncate' => '50' ) );

				$this->databaseAddField( array( 'field_database_id' => $inserted_id,
												'field_name' => 'Body',
												'field_key' => 'article_body',
												'field_type' => 'editor',
												'field_required' => '1',
												'field_user_editable' => '1',
												'field_extra' => 'short' ) );

				$this->databaseAddField( array( 'field_database_id' => $inserted_id,
												'field_name' => 'Public Date',
												'field_key' => 'article_date',
												'field_type' => 'date',
												'field_required' => '1',
												'field_user_editable' => '1',
												'field_extra' => 'short',
												'field_default_value' => 'Today' ) );
			}

			//-----------------------------------------
			// Rebuild cache
			//-----------------------------------------
			//$this->rebuildCache();

			return true;
		}

		public function databaseAddField( $fieldInfo )
		{
			$_save	= array( 'field_database_id'	 => $fieldInfo['field_database_id'],
							 'field_name'			 => trim($fieldInfo['field_name']),
							 'field_key'			 => md5( uniqid( microtime(), true ) ),
							 'field_description'	 => trim($fieldInfo['field_description']),
							 'field_type'			 => trim($fieldInfo['field_type']),
							 'field_required'		 => intval($fieldInfo['field_required']),
							 'field_user_editable'	 => intval($fieldInfo['field_user_editable']),
							 'field_max_length'		 => intval($fieldInfo['field_max_length']),
							 'field_extra'			 => IPSText::br2nl( trim($fieldInfo['field_extra']) ),
							 'field_html'			 => intval($fieldInfo['field_html']),
							 'field_is_numeric'		 => intval($fieldInfo['field_is_numeric']),
							 'field_truncate'		 => intval($fieldInfo['field_truncate']),
							 'field_default_value'	 => $fieldInfo['field_default_value'],
							 'field_display_listing' => intval($fieldInfo['field_display_listing']),
							 'field_display_display' => intval($fieldInfo['field_display_display']),
							 'field_format_opts'	 => ( is_array($fieldInfo['field_format_opts']) && count($fieldInfo['field_format_opts']) ) ? implode( ',', $fieldInfo['field_format_opts'] ) : '' );

			//-----------------------------------------
			// Get possible field types
			//-----------------------------------------
			require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/databases/fields.php' );
			$fields	= new ccs_database_fields( $this->registry );
			$types	= $fields->getTypes();

			//-----------------------------------------
			// Validator
			//-----------------------------------------
			$validators	= $fields->getValidators();

			if( $fieldInfo['field_validator'] != 'none' )
			{
				if( array_key_exists( $fieldInfo['field_validator'], $validators ) )
				{
					if( $fieldInfo['field_validator'] == 'custom' )
					{
						$_save['field_validator']	= $fieldInfo['field_validator'] . ';_;' . str_replace( '&#092;', '\\', $fieldInfo['field_validator_custom'] ) . ';_;' . $fieldInfo['field_validator_error'];
					}
					else
					{
						$_save['field_validator']	= $fieldInfo['field_validator'];
					}
				}
			}
			else
			{
				$_save['field_validator']	= '';
			}

			//-----------------------------------------
			// Verify field type
			//-----------------------------------------
			$_isOk	= false;

			foreach( $types as $_type )
			{
				if( $_type[0] == $_save['field_type'] )
				{
					$_isOk	= true;
					$_save	= $fields->preSaveField( $_save );
					break;
				}
			}

			if( !$_isOk )
			{
				$this->registry->output->showError( $this->lang->words['field_type_invalid'], '11CCS52' );
			}

			//-----------------------------------------
			// Check key
			//-----------------------------------------
			$_key	= $this->DB->buildAndFetch( array( 'select' => 'field_id', 'from' => 'ccs_database_fields', 'where' => "field_database_id={$_save['field_database_id']} AND field_key='{$_save['field_key']}'" ) );

			if( $_key['field_id'] )
			{
				//$this->registry->output->showError( $this->lang->words['field_key_in_use'] );
				$_save['field_key']	= md5( uniqid( microtime(), true ) );
			}

			//-----------------------------------------
			// Set position
			//-----------------------------------------
			$max	=  $this->DB->buildAndFetch( array( 'select' => 'MAX(field_position) as position', 'from' => 'ccs_database_fields', 'where' => "field_database_id={$_save['field_database_id']}" ) );

			$_save['field_position']	= $max['position'] + 1;

			$this->DB->insert( 'ccs_database_fields', $_save );

			$id	= $this->DB->getInsertId();

			//-----------------------------------------
			// Add field to db
			//-----------------------------------------
			$this->DB->addField( 'ccs_custom_database_' . $fieldInfo['field_database_id'], 'field_' . $id, 'TEXT' );

			//-----------------------------------------
			// Update database
			//-----------------------------------------
			$this->DB->update( 'ccs_databases', array( 'database_field_count' => 'database_field_count+1' ), 'database_id=' . $fieldInfo['field_database_id'] );
			$this->rebuildFieldCache();
		}

		/**
		 * Rebuild cache of fields
		 * Don't automatically use shortcuts, as they won't be setup if makeRegistryShortcuts() wasn't called
		 *
		 * @access	public
		 * @return	void
		 */
		public function rebuildFieldCache()
		{
			$fields	= array();

			ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'ccs_database_fields', 'order' => 'field_position ASC' ) );
			ipsRegistry::DB()->execute();

			while( $r = ipsRegistry::DB()->fetch() )
			{
				$fields[ $r['field_database_id'] ][ $r['field_id'] ]	= $r;
			}

			ipsRegistry::cache()->setCache( 'ccs_fields', $fields, array( 'array' => 1, 'deletefirst' => 1 ) );
		}

		public function rebuildDatabaseCache( $database )
		{
			$_databases	= array();

			if ( is_array($database) )
			{
				$_databases	= $database;
			}
			else
			{
				$_databases[]	= intval($database);
			}

			foreach( $_databases as $_database )
			{
				$records = $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'ccs_custom_database_' . $_database, 'where' => 'record_approved=1' ) );
				$fields = $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'ccs_database_fields', 'where' => "field_database_id='{$_database}'" ) );

				$this->DB->update( 'ccs_databases', array( 'database_field_count' => $fields['total'], 'database_record_count' => $records['total'] ), "database_id='{$_database}'" );
			}

			return;
		}

		/**
		 * Recache a category
		 *
		 * @access	public
		 * @param	int		Category ID
		 * @return	void
		 */
		public function rebuildCategoryCache( $category )
		{
			$_categories	= array();
			$category		= intval($category);

			if( !$category )
			{
				$_categories	= array_keys($this->categories);
			}
			else
			{
				$_categories[]	= $category;
			}

			foreach( $_categories as $_cat )
			{
				$_category	= $this->categories[ $_cat ];

				if( !$_category['category_database_id'] )
				{
					continue;
				}

				$_update	= array(
									'category_records'				=> 0,
									'category_last_record_id'		=> 0,
									'category_last_record_date'		=> 0,
									'category_last_record_member'	=> 0,
									'category_last_record_name'		=> '',
									'category_last_record_seo_name'	=> '',
									);

				$latest		= $this->DB->buildAndFetch( array(
															'select'	=> 'r.*',
															'from'		=> array( 'ccs_custom_database_' . $_category['category_database_id'] => 'r' ),
															'where'		=> 'r.record_approved=1 AND r.category_id=' . $_cat,
															'order'		=> 'r.record_saved DESC',
															'limit'		=> array( 0, 1 ),
															'add_join'	=> array(
																				array(
																					'select'	=> 'm.*',
																					'from'		=> array( 'members' => 'm' ),
																					'where'		=> 'm.member_id=r.member_id',
																					'type'		=> 'left',
																					)
																				)
													)		);

				$_update['category_last_record_id']			= intval($latest['primary_id_field']);
				$_update['category_last_record_date']		= intval($latest['record_saved']);
				$_update['category_last_record_member']		= intval($latest['member_id']);
				$_update['category_last_record_name']		= $latest['members_display_name'];
				$_update['category_last_record_seo_name']	= $latest['members_seo_name'];

				$count		= $this->DB->buildAndFetch( array( 'select' => 'count(*) as total', 'from' => 'ccs_custom_database_' . $_category['category_database_id'], 'where' => 'record_approved=1 AND category_id=' . $_cat ) );

				$_update['category_records']				= $count['total'];

				$this->DB->update( 'ccs_database_categories', $_update, 'category_id=' . $_cat );

				$this->categories[ $_cat ]	= array_merge( $this->categories[ $_cat ], $_update );
			}

			return;
		}

		/**
		 * Convert a Database Category
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertDatabaseCategory($id, $info)
		{
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------

			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['category_name'])
			{
				$this->logError($id, 'No name provided');
				return false;
			}

			//-----------------------------------------
			// Link
			//-----------------------------------------
			$info['category_database_id'] = 1;

			// We need to sort out the parent id
			if ($info['category_parent_id'] != 0)
			{
				$parent = $this->getLink($info['category_parent_id'], 'ccs_database_categories');
				if ($parent)
				{
					$info['category_parent_id'] = $parent;
				}
				else
				{
					$info['conv_parent'] = $info['category_parent_id'];
					unset($info['category_parent_id']);
				}
			}

			//-----------------------------------------
			// Insert
			//-----------------------------------------
			unset($info['category_id']);
			$this->DB->insert( 'ccs_database_categories', $info );
			$inserted_id = $this->DB->getInsertId();

			//-----------------------------------------
			// Add link
			//-----------------------------------------
			$this->addLink($inserted_id, $id, 'ccs_database_categories');

			//-----------------------------------------
			// Sort out children
			//-----------------------------------------
			$this->DB->update('ccs_database_categories', array('category_parent_id' => $inserted_id), "conv_parent='{$id}'");

			return true;
		}

		/**
		 * Convert a Database Entry
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @param	integer		Database ID
		 * @return 	boolean		Success or fail
		 **/
		public function convertDatabaseEntry($id, $info, $db)
		{
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}

			//-----------------------------------------
			// Link
			//-----------------------------------------
			$info['member_id'] = $this->getLink($info['member_id'], 'members');
			$info['category_id'] = $this->getLink($info['category_id'], 'ccs_database_categories');
			
			$databaseId = $this->getLink( $db, 'ccs_databases', TRUE );

			if ( $databaseId == FALSE )
			{
				$this->logError($id, "Invalid database id: {$db}");
				return FALSE;
			}

			//-----------------------------------------
			// Insert
			//-----------------------------------------
			unset($info['primary_id_field']);
			$this->DB->insert( 'ccs_custom_database_'.$databaseId, $info );
			$inserted_id = $this->DB->getInsertId();

			//-----------------------------------------
			// Add link
			//-----------------------------------------
			$this->addLink($databaseId.'_'.$inserted_id, $id, 'database_entries');

			$this->rebuildDatabaseCache($databaseId);

			return true;
		}

		/**
		 * Convert an Article
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertArticle($id, $info)
		{
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}

			//-----------------------------------------
			// Link
			//-----------------------------------------
			$info['member_id'] = $this->getLink($info['member_id'], 'members', TRUE);
			
			if ( $info['category_id'] )
			{
				$info['category_id'] = $this->getLink($info['category_id'], 'ccs_database_categories');
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------

			unset($info['primary_id_field']);
			$this->DB->insert( 'ccs_custom_database_1', $info );
			$inserted_id = $this->DB->getInsertId();

			//-----------------------------------------
			// Add link
			//-----------------------------------------
			$this->addLink($inserted_id, $id, 'ccs_articles');

			return true;
		}

		/**
		 * Convert a Database Comment
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertDatabaseComment($id, $info)
		{
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------

			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['comment_post'])
			{
				$this->logError($id, 'No comment provided');
				return false;
			}

			//-----------------------------------------
			// Link
			//-----------------------------------------

			$info['comment_user'] = $this->getLink($info['comment_user'], 'members');
			$info['comment_record_id'] = $this->getLink($info['comment_record_id'], 'database_entries');

			//-----------------------------------------
			// Insert
			//-----------------------------------------

			unset($info['category_id']);
			$this->DB->insert( 'ccs_database_comments', $info );
			$inserted_id = $this->DB->getInsertId();

			//-----------------------------------------
			// Add link
			//-----------------------------------------

			$this->addLink($inserted_id, $id, 'ccs_database_comments');

			return true;
		}

		/**
		 * Load data for mapping
		 *
		 * @access 	public
		 * @param 	string 		action (groups or forum_perms)
		 * @return 	array 		('id' => 'name', ...)
		 **/
		public function loadLocalInfo($table)
		{
			$return = array();
			$this->DB->build(array('select' => '*', 'from' => $table));
			$this->DB->execute();
			while ($row = $this->DB->fetch())
			{
				switch ($table)
				{
					case 'ccs_databases':
						if ( $row['database_is_articles'] ) {  $return[$row['database_id']] = $row['database_name']; }
						break;
				}
			}
			return $return;
		}
	}