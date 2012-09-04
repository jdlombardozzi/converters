<?php
/**
 * IPS Converters
 * IP.CCS 1.1 Converters
 * vBadvanced Dynamics Converter
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
		'key'	=> 'vbdynamics',
		'name'	=> 'vBadvanced Dynamics 1.2',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'vbulletin', 'newdb' => false),
		));

	class admin_convert_ccs_vbdynamics extends ipsCommand
	{
		private $database_id = 1;

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
				'ccs_database_categories'	=> array(),
				);

			if ( $this->database_id )
			{
				$this->actions['database_entries'] = array('ccs_database_categories');
			}

			$this->actions['ccs_database_comments'] = array('database_entries');
			$this->actions['ccs_attachments'] = array('database_entries');

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_ccs.php' );
			$this->lib =  new lib_ccs( $registry, $html, $this, true, $this->database_id );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'vBadvanced Dynamics &rarr; IP.Content Converter' );

			//-----------------------------------------
			// Are we connected?
			// (in the great circle of life...)
			//-----------------------------------------

			$this->HB = $this->lib->connect();

			//--------------------------------------
			// Do we have a database ID?
			//--------------------------------------

			if ( !$this->database_id )
			{
				echo 'No database ID';
				exit;
			}

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
					return $this->lib->countRows('adv_dyna_categories');
					break;

				case 'database_entries':
					return $this->lib->countRows('adv_dyna_entries');
					break;

				case 'ccs_database_comments':
					return $this->lib->countRows('adv_dyna_posts');
					break;

				case 'ccs_attachments':
					return $this->lib->countRows('adv_dyna_attachments');
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
			return false;
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

			$main = array(	'select' 	=> '*',
							'from' 		=> 'adv_dyna_categories',
							'order'		=> 'catid ASC',
						);

			$loop = $this->lib->load( 'ccs_database_categories', $main );

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'category_database_id'	=> $this->database_id,
					'category_name'			=> $row['title'],
					'category_parent_id'	=> $row['parent'],
					'category_description'	=> $row['description'],
					'category_position'		=> $row['displayorder'],
					'category_records'		=> $row['entrycount'],
					);

				$this->lib->convertDatabaseCategory($row['catid'], $save);
			}

			$this->lib->next();

		}

		/**
		 * Convert Database Entries
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_database_entries()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'adv_dyna_entries',
							'order'		=> 'entryid ASC',
						);

			$loop = $this->lib->load( 'database_entries', $main );

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Get entry text
				$entrytext = '';
				ipsRegistry::DB('hb')->build( array( 'select' => '*', 'from' => 'adv_dyna_entryparsed', 'where' => 'entryid='.$row['entryid'] ) );
				ipsRegistry::DB('hb')->execute();
				while( $entry = ipsRegistry::DB('hb')->fetch() )
				{
					$entrytext .= $entry['pagetext_parsed'];
				}

				$save = array(
					'member_id'			=> $row['userid'],
					'record_saved'		=> $row['dateline'],
					'record_updated'	=> $row['lastupdated'] ? $row['lastupdated'] : $row['dateline'],
					'category_id'		=> $row['catid'],
					'record_locked'		=> $row['open'] ? 0 : 1,
					'record_comments'	=> $row['posts'],
					'record_views'		=> $row['views'],
					'record_approved'	=> $row['draft'] ? 0 : 1,
					'record_pinned'		=> $row['sticky'],
					'field_1'			=> $row['title'],
					'field_2'			=> $row['keywords'],
					'field_3'			=> $row['hasattach'],
					'field_4'			=> $entrytext,
					);

				$this->lib->convertDatabaseEntry($row['entryid'], $save, $this->database_id);
			}

			$this->lib->next();

		}

		/**
		 * Convert Database Comments
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_ccs_database_comments()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'adv_dyna_posts',
							'order'		=> 'postid ASC',
						);

			$loop = $this->lib->load( 'ccs_database_comments', $main );

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'comment_user'			=> $row['userid'],
					'comment_database_id'	=> $this->database_id,
					'comment_record_id'		=> $row['entryid'],
					'comment_date'			=> $row['dateline'],
					'comment_ip_address'	=> $row['ipaddress'],
					'comment_post'			=> $row['pagetext'],
					'comment_approved'		=> $row['visible'],
					);

				$this->lib->convertDatabaseComment($row['postid'], $save);
			}

			$this->lib->next();

		}

		/**
		 * Convert Attachments
		 *
		 * @access	private
		 * @return 	void
		 **/
		private function convert_ccs_attachments()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('attachments', array('attach_path'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'adv_dyna_attachments',
							'order'		=> 'attachmentid ASC',
						);

			$loop = $this->lib->load('ccs_attachments', $main);

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

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// What's the mimetype?
				$type = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments_type', 'where' => "atype_extension='{$row['extension']}'" ) );

				// Is this an image?
				$image = false;
				if (preg_match('/image/', $type['atype_mimetype']))
				{
					$image = true;
				}

				// Sort out data
				$save = array(
					'attach_ext'			=> $row['extension'],
					'attach_file'			=> $row['filename'],
					'attach_location'		=> $row['filename'],
					'attach_is_image'		=> $image,
					'attach_hits'			=> $row['views'],
					'attach_date'			=> $row['dateline'],
					'attach_member_id'		=> $row['userid'],
					'attach_filesize'		=> $row['filesize'],
					'attach_rel_id'			=> $row['entryid'],
					'attach_rel_module'		=> 'ccs',
					);

				//-----------------------------------------
				// Database
				//-----------------------------------------

				if ($row['file_data'])
				{
					$save['attach_location'] = $row['filename'];
					$save['data'] = $row['file_data'];

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

					$save['attach_location'] = implode('/', preg_split('//', $row['userid'],  -1, PREG_SPLIT_NO_EMPTY));
					$save['attach_location'] .= "/{$row['filename']}_{$row['dateline']}.{$row['extension']}";

					$done = $this->lib->convertAttachment($row['attachmentid'], $save, $path);
				}

			}

			$this->lib->next();

		}

	}

