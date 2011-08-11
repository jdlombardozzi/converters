<?php
/**
 * IPS Converters
 * Application Files
 * Library functions for IP.Downloads 2.0 conversions
 * Last Update: $Date: 2009-10-31 12:52:02 +0100(sab, 31 ott 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 377 $
 */
	class lib_downloads extends lib_master
	{
		/**
	     * Information box to display on convert screen
	     *
	     * @access	public
	     * @return	string 		html to display
	     */
		public function getInfo()
		{
			return "<strong>Rebuild Content</strong><br />
				<a href='{$this->settings['base_url']}&app=downloads&module=tools&section=tools' target='_blank'>Click here</a> and run the following tools:
				<ul>
					<li>Category Latest File Information</li>
					<li>Rebuild All Thumbnails</li>
				</ul><br />
				<br /><br />
				<strong>Turn the application back online</strong><br />
				Visit your IP.Downloads settings and turn the application back online.";
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
				case 'downloads_categories':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'downloads_categories' ) );
					$return = array(
						'name'	=> 'Categories',
						'rows'	=> $count['count'],
						'cycle'	=> 1000,
					);
					break;
					
				case 'downloads_mimemask':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'downloads_mimemask' ) );
					$return = array(
						'name'	=> 'Mime Masks',
						'rows'	=> $count['count'],
						'cycle'	=> 100,
					);
					break;
						
				case 'downloads_mime':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'downloads_mime' ) );
					$return = array(
						'name'	=> 'Mime Types',
						'rows'	=> $count['count'],
						'cycle'	=> 500,
					);
					break;
					
				case 'downloads_files':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'downloads_files' ) );
					$return = array(
						'name'	=> 'Files',
						'rows'	=> $count['count'],
						'cycle'	=> 250,
					);
					break;
					
				case 'downloads_cfields':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'downloads_cfields' ) );
					$return = array(
						'name'	=> 'Custom Fields',
						'rows'	=> $count['count'],
						'cycle'	=> 500,
					);
					break;
					
				case 'downloads_comments':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'downloads_comments' ) );
					$return = array(
						'name'	=> 'Comments',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;
					
				case 'downloads_downloads':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'downloads_downloads' ) );
					$return = array(
						'name'	=> 'Download Logs',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;
					
				case 'downloads_favorites':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'downloads_favorites' ) );
					$return = array(
						'name'	=> 'Favorites',
						'rows'	=> $count['count'],
						'cycle'	=> 2000,
					);
					break;
					
					case 'downloads_mods':
						$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'downloads_mods' ) );
						$return = array(
							'name'	=> 'Moderators',
							'rows'	=> $count['count'],
							'cycle'	=> 500,
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
			
			$basic = array('section' => $this->app['app_key'], 'key' => $action, 'app' => 'downloads');
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
				case 'downloads_categories':
					return array( 'downloads_categories' => 'cid' );
					break;
					
				case 'downloads_mimemask':
					return array( 'downloads_mimemask' => 'mime_maskid' );
					break;
						
				case 'downloads_mime':
					return array( 'downloads_mime' => 'mime_id' );
					break;
					
				case 'downloads_files':
					return array( 'downloads_files' => 'file_id', 'downloads_filestorage' => 'storage_id', 'downloads_ccontent' => 'file_id' );
					break;
					
				case 'downloads_cfields':
					return array( 'downloads_cfields' => 'cf_id' );
					break;
					
				case 'downloads_comments':
					return array( 'downloads_comments' => 'comment_id' );
					break;
					
				case 'downloads_downloads':
					return array( 'downloads_downloads' => 'did' );
					break;
					
				case 'downloads_filebackup':
					return array( 'downloads_filebackup' => 'b_id' );
					break;
					
				case 'downloads_mods':
					return array( 'downloads_mods' => 'modid' );
					break;
				
				case 'downloads_favorites':
					return array ( );
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
				case 'downloads_categories':
					return array('addfield' => array('downloads_categories', 'conv_parent', 'mediumint(5)'));
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
			$report['exdat1'] = $this->getLink($report['exdat1'], 'downloads_files');
			$report['exdat2'] = $this->getLink($report['exdat2'], 'downloads_comments');
			$report['exdat3'] = $report['exdat3'];
			$report['url'] = "/index.php?app=downloads&amp;showfile={$report['exdat1']}&amp;st={$report['exdat3']}#{$report['exdat3']}";
			$report['seotemplate'] = '';
						
			return $report;
		}
		
		/**
		 * Convert a category
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @param 	array 		Permissions index data
		 * @return 	boolean		Success or fail
		 **/
		public function convertCategory($id, $info, $perms)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['cname'])
			{
				$this->logError($id, 'No name provided');
				return false;
			}
									
			//-----------------------------------------
			// Link
			//-----------------------------------------
						
			// This will be sorted out in the rebuild
			unset($info['cfileinfo']);
			
			// We need to sort out the parent id
			if ($info['cparent'] != 0)
			{
				$parent = $this->getLink($info['cparent'], 'downloads_categories');
				if ($parent)
				{
					$info['cparent'] = $parent;
				}
				else
				{
					$info['conv_parent'] = $info['cparent'];
					unset($info['cparent']);
				}
			}
			
			$opts = unserialize($info['coptions']);
			$opts['opt_mimemask'] = ($opts['opt_mimemask']) ? $this->getLink($opts['opt_mimemask'], 'downloads_mimemask') : 1;
			$opts['opt_topicf'] = ($opts['opt_topicf']) ? $this->getLink($opts['opt_topicf'], 'forums') : 0;
			$info['coptions'] = serialize($opts);
			
			if ($info['ccfields'])
			{
				$fields = explode(',', $info['ccfields']);
				foreach ($fields as $field)
				{
					$sfields[] = $this->getLink($field, 'downloads_cfields');
				}
				$info['ccfields'] = implode(',', $sfields);
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			// Make sure we don't have any fields we shouldn't have
			foreach (array('perm_id', 'app', 'perm_type', 'perm_type_id', 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7', 'owner_only', 'friend_only', 'authorized_users') as $unset)
			{
				unset($info[$unset]);
			}
			
			unset($info['cid']);
			$this->DB->insert( 'downloads_categories', $info );
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
							$save[] = $this->getLink($pset, 'forum_perms');
						}
					}
					$perms[$key] = implode(',', $save);
				}
			}
			
			$this->addToPermIndex('dcat', $inserted_id, $perms, $id);
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'downloads_categories');
			
			//-----------------------------------------
			// Sort out children
			//-----------------------------------------

			$this->DB->update('downloads_categories', array('cparent' => $inserted_id), 'conv_parent='.$id);
			
			return true;
		}
	
		/**
		 * Convert a Mime Mask
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	string 		Name
		 * @return 	boolean		Success or fail
		 **/
		public function convertMimeMask($id, $name)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$name)
			{
				$this->logError($id, 'No name provided');
				return false;
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$this->DB->insert( 'downloads_mimemask', array('mime_masktitle' => $name) );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'downloads_mimemask');
			
			return true;
		}
		
		/**
		 * Convert a Mime Type
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertMime($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['mime_extension'])
			{
				$this->logError($id, 'No extension provided');
				return false;
			}
			if (!$info['mime_mimetype'])
			{
				$this->logError($id, 'No mime type provided');
				return false;
			}
			if (!$info['mime_img'] or !file_exists(DOC_IPS_ROOT_PATH.'style_extra/'.$info['mime_img']))
			{
				$info['mime_img'] = 'mime_types/unknown.gif';
			}
			
			//-----------------------------------------
			// Handle duplicates
			//-----------------------------------------
			
			if ($this->DB->buildAndFetch( array( 'select' => 'mime_id', 'from' => 'downloads_mime', 'where' => "mime_mimetype = '{$info['mime_mimetype']}'" ) ))
			{
				$this->logError($id, 'Type already exists');
				return false;
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			unset($info['mime_id']);
			$this->DB->insert( 'downloads_mime', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'downloads_mime');
			
			return true;
		}
		
		/**
		 * Convert a Custom Field
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertCField($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['cf_title'])
			{
				$this->logError($id, 'No title provided');
				return false;
			}
			if (!$info['cf_type'])
			{
				$this->logError($id, 'No type provided');
				return false;
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			unset($info['cf_id']);
			$this->DB->insert( 'downloads_cfields', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// We need a column in downloads_ccontent
			//-----------------------------------------
			
			$this->DB->addField( 'downloads_ccontent', "field_$inserted_id", 'text' );
			$this->DB->optimize( 'downloads_ccontent' );
			
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'downloads_cfields');
			
			return true;
		}
	
		/**
		 * Convert a File
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @param 	array 		Extra data such as paths (local_path, remote_path, remote_ss_path)
		 * @param 	array 		Custom fields (Data to insert into ccontent table)
		 * @return 	boolean		Success or fail
		 **/
		public function convertFile($id, $info, $options, $cfields)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['file_name'])
			{
				$this->logError($id, 'No name provided');
				return false;
			}
			if (!$info['file_cat'])
			{
				$this->logError($id, 'No category provided');
				return false;
			}
			if (!$info['file_filename'])
			{
				$this->logError($id, 'No filename provided');
				return false;
			}
			if (!$info['file_storagetype'])
			{
				$this->logError($id, 'No storage type provided');
				return false;
			}
			if (!$options['local_path'])
			{
				$this->logError($id, 'No local path provided');
				return false;
			}
			if (!$this->settings['idm_localfilepath'])
			{
				$this->logError($id, 'Your IP.Downloads uploads path has not been configured');
				return false;
			}
			if (!$this->settings['idm_localsspath'])
			{
				$this->logError($id, 'Your IP.Downloads screeshots uploads path has not been configured');
				return false;
			}
			
			//-----------------------------------------
			// Move file
			//-----------------------------------------
			
			$save = str_replace('{root_path}', $options['local_path'], $this->settings['idm_localfilepath']);
			$sssave = str_replace('{root_path}', $options['local_path'], $this->settings['idm_localsspath']);
			
			if ($info['file_storagetype'] == 'web' or $info['file_storagetype'] == 'nonweb')
			{
				if (!$options['remote_path'])
				{
					$this->logError($id, 'No remote path provided');
					return false;
				}
				$this->moveFiles(array($info['file_filename']), $options['remote_path'], $save);
				if ($info['file_ssname'])
				{
					$this->moveFiles(array($info['file_ssname']), $options['remote_ss_path'], $sssave);
				}
			}
			elseif ($info['file_storagetype'] == 'db')
			{
				if (!$info['data'])
				{
					$this->logError($id, 'No file data provided');
					return false;
				}
				$this->createFile($info['file_filename'], $info['data'], $info['file_size'], $save);
				if ($info['file_ssname'])
				{
					$this->createFile($info['file_name'], $info['ssdata'], $info['file_size'], $sssave);
				}
			}
			
			//-----------------------------------------
			// Link
			//-----------------------------------------
			
			$info['file_cat'] = $this->getLink($info['file_cat'], 'downloads_categories');
			//$info['file_mime'] = $this->getLink($info['file_mime'], 'downloads_mime');
			//$info['file_ssmime'] = ($info['file_ssmime']) ? $this->getLink($info['file_ssmime'], 'downloads_mime') : 0;
			$info['file_submitter'] = $this->getLink($info['file_submitter'], 'members', false, true);
			$info['file_approver'] = ($info['file_approver']) ? $this->getLink($info['file_approver'], 'members', false, true) : 0;
			$info['file_topicid'] = ($info['file_topicid']) ? $this->getLink($info['file_topicid'], 'topics') : 0;
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$storage = array('storage_file' => $info['data'], 'storage_ss' => $info['ssdata']);
			
			unset($info['file_id']);
			unset($info['data']);
			unset($info['ssdata']);
			unset($info['file_filename']);
			unset($info['file_storagetype']);
			unset($info['file_mime']);
			$this->DB->insert( 'downloads_files', $info );
			$inserted_id = $this->DB->getInsertId();
			
			if ($info['file_storagetype'] == 'db')
			{
				$this->DB->insert( 'downloads_filestorage', array_merge( array('storage_id' => $inserted_id), $storage ) );
			}
			
			//-----------------------------------------
			// Sort out custom fields
			//-----------------------------------------
			
			$cf_updated = $cfields['updated'];
			unset($cfields['file_id']);
			unset($cfields['updated']);
			
			$save_cfields = array();
			
			foreach($cfields as $key => $value)
			{
				preg_match('/field_(.+)/', $key, $matches);
				$newKey = $this->getLink($matches[1], 'downloads_cfields');
				if ($newKey)
				{
					$save_cfields['field_'.$newKey] = $value;
				}
			}
			
			$this->DB->insert( 'downloads_ccontent', array_merge( array('file_id' => $inserted_id, 'updated' => $cf_updated), $save_cfields ) );
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'downloads_files');
			
			return true;
		}
		
		/**
		 * Convert a revision
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertRevision($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['b_fileid'])
			{
				$this->logError($id, 'No file ID provided');
				return false;
			}
			if (!$info['b_filetitle'])
			{
				$this->logError($id, 'No title provided');
				return false;
			}
			if (!$info['b_filename'])
			{
				$this->logError($id, 'No filename provided');
				return false;
			}
			if (!$info['b_storage'])
			{
				$this->logError($id, 'No storage type provided');
				return false;
			}
			
			//-----------------------------------------
			// Link
			//-----------------------------------------
			
			$info['b_fileid'] = $this->getLink($info['b_fileid'], 'downloads_files');
			$info['b_filemime'] = $this->getLink($info['b_filemime'], 'downloads_mime');
			$info['b_ssmime'] = ($info['b_ssmime']) ? $this->getLink($info['b_ssmime'], 'downloads_mime') : 0;
						
			//-----------------------------------------
			// Insert
			//-----------------------------------------
						
			unset($info['b_id']);
			$this->DB->insert( 'downloads_filebackup', $info );
			$inserted_id = $this->DB->getInsertId();
									
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'downloads_filebackup');
			
			return true;
		}
		
		
		/**
		 * Convert a Comment
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertComment($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['comment_fid'])
			{
				$this->logError($id, 'No file ID number provided');
				return false;
			}
			if (!$info['comment_mid'])
			{
				$this->logError($id, 'No member ID number provided');
				return false;
			}
			if (!$info['comment_text'])
			{
				$this->logError($id, 'No comment provided');
				return false;
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$info['comment_fid'] = $this->getLink($info['comment_fid'], 'downloads_files');
			$info['comment_mid'] = $this->getLink($info['comment_mid'], 'members', false, true);
			
			unset($info['comment_id']);
			$this->DB->insert( 'downloads_comments', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'downloads_comments');
			
			return true;
		}
		
		/**
		 * Convert a Download Log
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertLog($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['dfid'])
			{
				$this->logError($id, 'No file ID number provided');
				return false;
			}
			if (!$info['dmid'])
			{
				$this->logError($id, 'No member ID number provided');
				return false;
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$info['dfid'] = $this->getLink($info['dfid'], 'downloads_files');
			$info['dmid'] = $this->getLink($info['dmid'], 'members', false, true);
			
			unset($info['did']);
			$this->DB->insert( 'downloads_downloads', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'downloads_downloads');
			
			return true;
		}
		
		/**
		 * Convert a Favourite
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertFave($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			$this->convertFollow ( array (
				'like_app'			=> 'downloads',
				'like_area'			=> 'files',
				'like_rel_id'		=> $info['ffid'],
				'like_member_id'	=> $info['fmid'],
			) );
			
			/*if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['ffid'])
			{
				$this->logError($id, 'No file ID number provided');
				return false;
			}
			if (!$info['fmid'])
			{
				$this->logError($id, 'No member ID number provided');
				return false;
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$info['ffid'] = $this->getLink($info['ffid'], 'downloads_files');
			$info['fmid'] = $this->getLink($info['fmid'], 'members', false, true);
			
			unset($info['fid']);
			$this->DB->insert( 'downloads_favorites', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'downloads_favorites');*/
			
			return true;
		}
	
		/**
		 * Convert a Moderator
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertMod($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['modgmid'])
			{
				$this->logError($id, 'No moderator information provided');
				return false;
			}
			if (!$info['modcats'])
			{
				$this->logError($id, 'No categories provided');
				return false;
			}
			
			//-----------------------------------------
			// Link (this should be fun...)
			//-----------------------------------------
			
			// Member / Group ID
			$explode = explode(':', $info['modgmid']);
			if ($info['modtype'] == 1)
			{
				$newID = $this->getLink($explode[0], 'members', false, true);
			}
			else
			{
				$newID = $this->getLink($explode[0], 'groups', false, true);
			}
			$info['modgmid'] = $newID.':'.$explode[1];
			
			// Cats
			$cats = explode(',', $info['modcats']);
			$save_cats = array();
			foreach ($cats as $cat)
			{
				$save_cats[] = $this->getLink($cat, 'downloads_categories');
			}
			$info['modcats'] = implode(',', $save_cats);
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
						
			unset($info['modid']);
			$this->DB->insert( 'downloads_mods', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'downloads_mods');
			
			return true;
		}
	
		
	
	
	}
	
?>