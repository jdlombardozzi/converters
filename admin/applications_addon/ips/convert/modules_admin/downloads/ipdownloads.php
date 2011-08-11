<?php
/**
 * IPS Converters
 * IP.Downloads 2.0 Converters
 * IP.Downloads Merge Tool
 * Last Update: $Date: 2010-03-19 11:03:12 +0100(ven, 19 mar 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 437 $
 */


	$info = array(
		'key'	=> 'ipdownloads',
		'name'	=> 'IP.Downloads 2.0',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'ipboard', 'newdb' => false),
		));

	class admin_convert_downloads_ipdownloads extends ipsCommand
	{
		/**
	    * Main class entry point
	    *
	    * @access	public
	    * @param	object		ipsRegistry
	    * @return	void
	    */
	    public function doExecute( ipsRegistry $registry )
		{
			$this->registry = $registry;
			//-----------------------------------------
			// What can this thing do?
			//-----------------------------------------

			$this->actions = array(
				'downloads_mimemask'	=> array(),
				'downloads_mime'		=> array(),
				'downloads_cfields'		=> array(),
				'downloads_categories'	=> array('members', 'downloads_mimemask', 'forums'),
				'downloads_files'		=> array('downloads_categories', 'downloads_mime', 'members', 'topics', 'downloads_cfields'),
				'downloads_comments'	=> array('downloads_files', 'members'),
				'downloads_downloads'	=> array('downloads_files', 'members'),
				'downloads_favorites'	=> array('downloads_files', 'members'),
				'downloads_mods'		=> array('members', 'groups', 'downloads_categories'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_downloads.php' );
			$this->lib =  new lib_downloads( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'IP.Downloads Merge Tool' );

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
			switch ($action)
			{
				case 'downloads_files':
					return true;
					break;

				default:
					return false;
					break;
			}
		}

		/**
		 * Fix post data
		 *
		 * @access	private
		 * @param 	string		raw post data
		 * @return 	string		parsed post data
		 **/
		private function fixPostData($post)
		{
			return $post;
		}

		/**
		 * Convert Categories
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_downloads_categories()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> 'c.*',
							'from' 		=> array('downloads_categories' => 'c'),
							'order'		=> 'c.cid ASC',
							'add_join'	=> array(
											array( 	'select' => 'p.*',
													'from'   =>	array( 'permission_index' => 'p' ),
													'where'  => "p.app='downloads' AND p.perm_type='cat' AND p.perm_type_id=c.cid",
													'type'   => 'left'
												),
											),
						);

			$loop = $this->lib->load('downloads_categories', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				//-----------------------------------------
				// Handle permissions
				//-----------------------------------------

				$perms = array();
				$perms['view_files']	= $row['perm_view'];
				$perms['show_files']	= $row['perm_2'];
				$perms['add_files']		= $row['perm_3'];
				$perms['download']		= $row['perm_4'];
				$perms['comment']		= $row['perm_5'];
				$perms['rate']			= $row['perm_6'];
				$perms['bypass_mod']	= $row['perm_7'];

				//-----------------------------------------
				// And go
				//-----------------------------------------

				$this->lib->convertCategory($row['cid'], $row, $perms);
			}

			$this->lib->next();

		}

		/**
		 * Convert Mime Masks
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_downloads_mimemask()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'downloads_mimemask',
							'order'		=> 'mime_maskid ASC',
						);

			$loop = $this->lib->load('downloads_mimemask', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertMimeMask($row['mime_maskid'], $row['mime_masktitle']);
			}

			$this->lib->next();

		}

		/**
		 * Convert Mime Types
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_downloads_mime()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'downloads_mime',
							'order'		=> 'mime_id ASC',
						);

			$loop = $this->lib->load('downloads_mime', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertMime($row['mime_id'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Custom Fields
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_downloads_cfields()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'downloads_cfields',
							'order'		=> 'cf_id ASC',
						);

			$loop = $this->lib->load('downloads_cfields', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertCField($row['cf_id'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Files
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_downloads_files()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('downloads_files', array('idm_local', 'idm_remote', 'idm_ss_remote'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'downloads_files',
							'order'		=> 'file_id ASC',
						);

			$loop = $this->lib->load('downloads_files', $main, array('downloads_filebackup'));

			//-----------------------------------------
			// We need some info
			//-----------------------------------------

			$ask = array();
			$ask['idm_local'] = array('type' => 'text', 'label' => 'The path to your IP.Board root folder (no trailing slash - can usually be copied and pasted from the box at the bottom of this table):');
			$ask['idm_remote'] = array('type' => 'text', 'label' => 'The path to where your downloads are stored on the remote system (no trailing slash - if you are using FTP or database storage, enter "."):');
			$ask['idm_ss_remote'] = array('type' => 'text', 'label' => 'The path to where your screenshots are stored on the remote system (no trailing slash - if you are using FTP or database storage, enter "."):');

			$this->lib->getMoreInfo('downloads_files', $loop, $ask, 'path');

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$options = array(
				'local_path'	=> $us['idm_local'],
				'remote_path'	=> $us['idm_remote'],
				'remote_ss_path'=> $us['idm_ss_remote'],
				);

			//-----------------------------------------
			// Check all is well
			//-----------------------------------------

			if (!is_readable($options['local_path']))
			{
				$this->lib->error('Your local path is not readable. '.$this->settings['local_path']);
			}
			$storage = str_replace('{root_path}', $options['local_path'], $this->settings['idm_localfilepath']);
			$screenies = str_replace('{root_path}', $options['local_path'], $this->settings['idm_localsspath']);
			if (!is_writable($storage))
			{
				$this->lib->error('Your local storage path is not writeable. '.$storage);
			}
			if (!is_writable($screenies))
			{
				$this->lib->error('Your local screenshots path is not writeable. '.$screenies);
			}
			if (!is_readable($options['remote_path']) and $options['remote_path'] != '.')
			{
				$this->lib->error('Your remote storage path is not readable. '.$this->settings['remote_path']);
			}
			if (!is_readable($options['remote_ss_path']) and $options['remote_ss_path'] != '.')
			{
				$this->lib->error('Your remote screenshots path is not readable. '.$this->settings['remote_ss_path']);
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Do the file
				//-----------------------------------------

				if ($row['file_storagetype'] == 'db')
				{
					$storage = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_filestorage', 'where' => "storage_id='{$row['file_id']}'" ) );
					$row['data'] = $storage['storage_file'];
					$row['ssdata'] = $storage['storage_ss'];
				}

				$cfields = array();
				$cfields = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_ccontent', 'where' => "file_id='{$row['file_id']}'" ) );

				$this->lib->convertFile($row['file_id'], $row, $options, $cfields);

				//-----------------------------------------
				// And all revisions
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'downloads_filebackup', 'where' => "b_fileid={$row['file_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($backup = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertRevision($backup['b_id'], $backup);
				}


			}

			$this->lib->next();

		}

		/**
		 * Convert Comments
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_downloads_comments()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'downloads_comments',
							'order'		=> 'comment_id ASC',
						);

			$loop = $this->lib->load('downloads_comments', $main);

			//-----------------------------------------
			// Prepare for reports conversion
			//-----------------------------------------

			$this->lib->prepareReports('downloads');

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$row['comment_text'] = $this->fixPostData($row['comment_text']);
				$this->lib->convertComment($row['comment_id'], $row);

				//-----------------------------------------
				// Report Center
				//-----------------------------------------

				$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='downloads'" ) );
				$rs = array(	'select' 	=> '*',
								'from' 		=> 'rc_reports_index',
								'order'		=> 'id ASC',
								'where'		=> 'exdat1='.$row['comment_fid']." AND exdat2={$row['comment_id']} AND rc_class='{$rc['com_id']}'"
							);

				ipsRegistry::DB('hb')->build($rs);
				ipsRegistry::DB('hb')->execute();
				while ($report = ipsRegistry::DB('hb')->fetch())
				{
					$rs = array(	'select' 	=> '*',
									'from' 		=> 'rc_reports',
									'order'		=> 'id ASC',
									'where'		=> 'rid='.$report['id']
								);

					ipsRegistry::DB('hb')->build($rs);
					ipsRegistry::DB('hb')->execute();
					$reports = array();
					while ($r = ipsRegistry::DB('hb')->fetch())
					{
						$reports[] = $r;
					}
					$this->lib->convertReport('downloads', $report, $reports);
				}

			}

			$this->lib->next();

		}

		/**
		 * Convert Log
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_downloads_downloads()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'downloads_downloads',
							'order'		=> 'did ASC',
						);

			$loop = $this->lib->load('downloads_downloads', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertLog($row['did'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Favourites
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_downloads_favorites()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'downloads_favorites',
							'order'		=> 'fid ASC',
						);

			$loop = $this->lib->load('downloads_favorites', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertFave($row['fid'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Moderators
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_downloads_mods()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'downloads_mods',
							'order'		=> 'modid ASC',
						);

			$loop = $this->lib->load('downloads_mods', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertMod($row['modid'], $row);
			}

			$this->lib->next();

		}

	}

