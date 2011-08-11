<?php
/**
 * IPS Converters
 * IP.Downloads 2.0 Converters
 * IIS Download Manager
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
		'key'	=> 'iisdm',
		'name'	=> 'IIS Download Manager 1.0',
		'login'	=> false,
	);

	class admin_convert_downloads_iisdm extends ipsCommand
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
				'downloads_categories'	=> array(),
				'downloads_files'		=> array('downloads_categories'),
				'downloads_comments'	=> array('downloads_files'),
				'downloads_downloads'	=> array('downloads_files',),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_downloads.php' );
			$this->lib =  new lib_downloads( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'IIS &rarr; Downloads Converter' );

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
				case 'downloads_categories':
					return $this->lib->countRows('dl_cats');
					break;

				case 'downloads_files':
					return $this->lib->countRows('dl_files');
					break;

				case 'downloads_comments':
					return $this->lib->countRows('dl_comments');
					break;

				case 'downloads_downloads':
					return $this->lib->countRows('dl_logs');
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

			$main = array(	'select' 	=> '*',
							'from' 		=> 'dl_cats',
							'order'		=> 'cat_id ASC',
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
				$perms['view_files']	= str_replace('|', ',', $row['perm_view']);
				$perms['show_files']	= str_replace('|', ',', $row['perm_view']);
				$perms['add_files']		= str_replace('|', ',', $row['perm_ul']);
				$perms['download']		= str_replace('|', ',', $row['perm_dl']);
				$perms['comment']		= str_replace('|', ',', $row['perm_dl']);
				$perms['rate']			= str_replace('|', ',', $row['perm_dl']);
				$perms['bypass_mod']	= str_replace('|', ',', $row['perm_mod']);

				//-----------------------------------------
				// And go
				//-----------------------------------------

				$save = array(
					'cparent'	=> $row['parent_id'],
					'cname'		=> $row['title'],
					'cdesc'		=> $row['description'],
					'cposition'	=> $row['sort_order'],
					);

				$this->lib->convertCategory($row['cat_id'], $save, $perms);
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
							'from' 		=> 'dl_files',
							'order'		=> 'file_id ASC',
						);

			$loop = $this->lib->load('downloads_files', $main, array('downloads_filebackup'));

			//-----------------------------------------
			// We need some info
			//-----------------------------------------

			$ask = array();
			$ask['idm_local'] = array('type' => 'text', 'label' => 'The path to your IP.Board root folder (no trailing slash - can usually be copied and pasted from the box at the bottom of this table):');
			$ask['idm_remote'] = array('type' => 'text', 'label' => 'The path to where your downloads are stored (no trailing slash - usually path_to_ipb/uploads):');

			$this->lib->getMoreInfo('downloads_files', $loop, $ask, 'path');

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$options = array(
				'local_path'	=> $us['idm_local'],
				'remote_path'	=> $us['idm_remote'],
				'remote_ss_path'=> $us['idm_remote'],
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
			if (!is_readable($options['remote_path']))
			{
				$this->lib->error('Your remote storage path is not readable. '.$this->settings['remote_path']);
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Need to match this to a mimetype
				$e = explode('.', $row['file_name']);
				$extension = array_pop( $e );
				$mime = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_mime', 'where' => "mime_extension='{$extension}'" ) );
				if (!$mime)
				{
					$this->lib->logError($row['file_id'], 'Invalid file extension');
					continue;
				}

				// And the image
				$imge = explode('.', $row['image']);
				$imgextension = array_pop( $imge );
				$imgmime = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_mime', 'where' => "mime_extension='{$imgextension}'" ) );

				$save = array(
					'file_name'			=> $row['title'],
					'file_cat'			=> $row['cat_id'],
					'file_open'			=> $row['active'],
					'file_filename'		=> $row['file_name'],
					'file_ssname'		=> $imgmime ? $row['image'] : '',
					'file_views'		=> $row['views'],
					'file_downloads'	=> $row['downloads'],
					'file_submitted'	=> $row['date_added'],
					'file_updated'		=> $row['date_updated'],
					'file_desc'			=> $row['description'],
					'file_size'			=> $row['file_size'],
					'file_mime'			=> $mime['mime_id'],
					'file_ssmime'		=> $imgmime ? $imgmime['mime_id'] : '',
					'file_submitter'	=> $row['author_id'],
					'file_topicid'		=> $row['topicid'],
					'file_ipaddress'	=> $row['submit_ip'],
					'file_storagetype'	=> 'web',
					'file_votes'		=> $row['num_ratings'],
					'file_rating'		=> $row['rating'],
					'file_realname'		=> $row['file_name'],
					);

				$this->lib->convertFile($row['file_id'], $save, $options, array());

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
							'from' 		=> 'dl_comments',
							'order'		=> 'comment_id ASC',
						);

			$loop = $this->lib->load('downloads_comments', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'comment_fid'	=> $row['file_id'],
					'comment_mid'	=> $row['author_id'],
					'comment_date'	=> $row['date_posted'],
					'comment_open'	=> '1',
					'comment_text'	=> $this->fixPostData($row['comment']),
					'ip_address'	=> $row['ip_address'],
					'use_sig'		=> '1',
					'use_emo'		=> '1',
					);

				$this->lib->convertComment($row['comment_id'], $save);
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
							'from' 		=> 'dl_logs',
							'order'		=> 'dl_time ASC',
						);

			$loop = $this->lib->load('downloads_downloads', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'dltime'	=> $row['dl_time'],
					'dfid'		=> $row['file_id'],
					'dip'		=> $row['ip_address'],
					'dmid'		=> $row['member_id'],
					);

				$this->lib->convertLog($row['dl_time'].'-'.$row['member_id'], $save);
			}

			$this->lib->next();

		}

	}

