<?php
/**
 * IPS Converters
 * IP.Gallery 3.0 Converters
 * Coppermine Photo Gallery
 * Last Update: $Date: 2011-11-08 00:14:18 +0000 (Tue, 08 Nov 2011) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 593 $
 */


	$info = array(
		'key'	=> 'coppermine',
		'name'	=> 'Coppermine Photo Gallery 1.5',
		'login'	=> false,
	);

	class admin_convert_gallery_coppermine extends ipsCommand
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
				'forum_perms'	=> array(),
				'groups' 		=> array('forum_perms'),
				'members'		=> array('groups'),
				//'gallery_form_fields'	=> array(),
				'gallery_categories'	=> array('members'),
				'gallery_albums'		=> array('members', 'gallery_categories'),
				'gallery_images'		=> array('members', 'gallery_categories', 'gallery_albums'),
				'gallery_comments'		=> array('members', 'gallery_images'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_gallery.php' );
			$this->lib =  new lib_gallery( $this->registry, $html, $this, false );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'Coppermine Photo Gallery &rarr; IP.Gallery Converter' );

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
				case 'forum_perms':
				case 'groups':
					return $this->lib->countRows('usergroups');
					break;

				case 'members':
					return $this->lib->countRows('users');
					break;

				/*case 'gallery_form_fields':
					return count($this->_getCustomFields());
					break;*/

				case 'gallery_categories':
					return $this->lib->countRows('categories');
					break;

				case 'gallery_albums':
					return $this->lib->countRows('albums');
					break;

				case 'gallery_images':
					return $this->lib->countRows('pictures');
					break;

				case 'gallery_comments':
					return $this->lib->countRows('comments');
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
				case 'forum_perms':
				case 'groups':
				case 'members':
				case 'gallery_albums':
				case 'gallery_images':
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
		 * Convert forum permissions
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_forum_perms()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('forum_perms', 'map');

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'usergroups',
							'order'		=> 'group_id ASC',
						);

			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'group_id', 'nf' => 'group_name'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['group_id'], $row['group_name']);
			}

			$this->lib->next();

		}

		/**
		 * Convert groups
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_groups()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('groups', 'map');

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'usergroups',
							'order'		=> 'group_id ASC',
						);

			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'group_id', 'nf' => 'group_name'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$prefix = '';
				$suffix = '';
				if ($row['group_colour'])
				{
					$prefix = "<span style='color:{$row['group_color']}'>";
					$suffix = '</span>';
				}

				$save = array(
					'g_title'			=> $row['group_name'],
					'g_max_diskspace'	=> $row['group_quota'],
					'g_access_cp'		=> $row['has_admin_access'],
					'g_rate'			=> $row['can_rate_pictures'],
					//'g_ecard'			=> $row['can_send_ecards'],
					'g_comment'			=> $row['can_post_comments'],
					'g_create_albums'	=> $row['can_create_albums'],
					'g_perm_id'			=> $row['group_id'],
					);
				$this->lib->convertGroup($row['group_id'], $save);
			}

			$this->lib->next();

		}

		/**
		 * Convert members
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_members()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$pcpf = array();
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'config', 'where' => "name IN('user_profile1_name', 'user_profile2_name', 'user_profile3_name', 'user_profile4_name', 'user_profile5_name', 'user_profile6_name')"));
			ipsRegistry::DB('hb')->execute();
			while ($r = ipsRegistry::DB('hb')->fetch())
			{
				if (!$r['value'])
				{
					continue;
				}
				$key = str_replace('_name', '', $r['name']);
				$pcpf[$key] = $r['value'];
			}

			$this->lib->saveMoreInfo('members', array_keys($pcpf));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'users',
							'order'		=> 'user_id ASC',
						);

			$loop = $this->lib->load('members', $main);

			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();

			$options = array('x' => '-Skip-');
			$this->DB->build(array('select' => '*', 'from' => 'pfields_data'));
			$this->DB->execute();
			while ($row = $this->DB->fetch())
			{
				$options[$row['pf_id']] = $row['pf_title'];
			}
			foreach ($pcpf as $id => $name)
			{
				$ask[$id] = array('type' => 'dropdown', 'label' => 'Custom profile field to store '.$name.': ', 'options' => $options, 'extra' => $extra );
			}


			$this->lib->getMoreInfo('members', $loop, $ask, 'path');

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				//-----------------------------------------
				// Set info
				//-----------------------------------------

				$info = array(
					'id'				=> $row['user_id'],
					'group'				=> $row['user_group'],
					'joined'			=> strtotime($row['user_regdate']),
					'username'			=> $row['user_name'],
					'email'				=> $row['user_email'],
					'md5pass'			=> $row['user_password'],
					);

				$members = array('last_visit'		=> strtotime($row['user_regdate']));
				$profile = array();

				//-----------------------------------------
				// Custom Profile fields
				//-----------------------------------------

				$custom = array();
				foreach ($pcpf as $id => $name)
				{
					if ($us[$id] != 'x')
					{
						$custom['field_'.$us[$id]] = $row[$id];
					}
				}

				//-----------------------------------------
				// And go!
				//-----------------------------------------

				$this->lib->convertMember($info, $members, $profile, $custom, '');

			}

			$this->lib->next();

		}

		/**
		 * Convert Categories
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_gallery_categories()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'categories',
							'order'		=> 'cid ASC',
						);

			$loop = $this->lib->load('gallery_categories', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Convert categories to Albums, but disallow images to be uploaded directly (container mode)
				$save = array (
					'album_name'				=> $row['name'],
					'album_description'			=> $row['description'],
					'album_is_global'			=> 1,
					'album_g_container_only'	=> 1,
					'album_parent_id'			=> $row['parent'],
				);

				$this->lib->convertAlbum($row['cid'], $save, array());
			}

			$this->lib->next();

		}

		/**
		 * Convert Albums
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_gallery_albums()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('gallery_albums', array('container_album'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'albums',
							'order'		=> 'aid ASC',
						);

			$loop = $this->lib->load('gallery_albums', $main);

			//-----------------------------------------
			// We need to know how to handle orphans
			//-----------------------------------------

			$cats = array();
			$this->DB->build(array('select' => '*', 'from' => 'gallery_albums_main', 'where' => 'album_g_container_only = 1'));
			$this->DB->execute();
			while ($r = $this->DB->fetch())
			{
				$cats[$r['album_id']] = $r['album_name'];
			}

			$this->lib->getMoreInfo('container_album', $loop, array('orphans' => array('type' => 'dropdown', 'label' => 'To which category do you wish to put members albums, or albums with no category?', 'options' => $cats)));

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$skip_cat_link = false;
				if (!$this->lib->getLink($row['category'], 'gallery_albums', true))
				{
					$skip_cat_link = true;
					$member_id = str_replace('1000', '', $row['category']);
				}

				$member_id = ($member_id) ? $member_id : 1;

				$save = array(
					'album_name'		=> $row['title'],
					'album_description'	=> $row['description'],
					'album_is_public'	=> ($row['visibility']) ? 0 : 1,
					'album_parent_id'	=> ($skip_cat_link) ? $us['container_album'] : $row['category'],
					'album_owner_id'	=> $member_id,
					);

				$this->lib->convertAlbum($row['aid'], $save, $us, $skip_cat_link);
			}

			$this->lib->next();

		}

		/**
		 * Convert Images
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_gallery_images()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('gallery_images', array('gallery_path'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'pictures',
							'order'		=> 'pid ASC',
						);

			$loop = $this->lib->load('gallery_images', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('gallery_images', $loop, array('gallery_path' => array('type' => 'text', 'label' => 'The path to the folder where images are saved (no trailing slash - usually path_to_coppermine/albums):')), 'path');

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$path = $us['gallery_path'];

			//-----------------------------------------
			// Check all is well
			//-----------------------------------------

			if (!is_writable($this->settings['gallery_images_path']))
			{
				$this->lib->error('Your IP.Gallery upload path is not writeable. '.$this->settings['gallery_images_path']);
			}
			if (!is_readable($path))
			{
				$this->lib->error('Your remote upload path is not readable.');
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Do the image
				//-----------------------------------------

				// Have a stab at the mimetype
				$explode = explode('.', $row['filename']);
				$ext = strtolower(array_pop($explode));
				$ext = ($ext == 'jpg') ? 'jpeg' : $ext;
				$mime = "image/{$ext}";

				$rating = round($row['pic_rating'] / 2000, 1);

				// Basic info
				$save = array(
					'member_id'			=> $row['owner_id'],
					'img_album_id'		=> $row['aid'],
					'caption'			=> $row['title'],
					'description'		=> $row['caption'],
					'directory'			=> $row['filepath'],
					'file_name'			=> $row['filename'],
					'file_size'			=> $row['filesize'],
					'file_type'			=> $mime,
					'approved'			=> ($row['approved'] == 'YES') ? 1 : 0,
					'views'				=> $row['hits'],
					'comments'			=> $this->lib->countRows('comments', "pid={$row['pid']}"),
					'idate'				=> $row['ctime'],
					'ratings_total'		=> $rating * $row['votes'],
					'ratings_count'		=> $row['votes'],
					'rating'			=> $rating,
					);

				// 'Custom' fields
				/*$custom = array(
					'field_user_field1'	=> $row['user1'],
					'field_user_field2'	=> $row['user2'],
					'field_user_field3'	=> $row['user3'],
					'field_user_field4'	=> $row['user4'],
					);*/

				// Go!
				$this->lib->convertImage($row['pid'], $save, $path);

			}

			$this->lib->next();

		}

		/**
		 * Convert Comments
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_gallery_comments()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'comments',
							'order'		=> 'msg_id ASC',
						);

			$loop = $this->lib->load('gallery_comments', $main);


			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'img_id'			=> $row['pid'],
					'author_name'		=> $row['msg_author'],
					'comment'			=> $row['msg_body'],
					'post_date'			=> strtotime($row['msg_date']),
					'ip_address'		=> $row['msg_raw_up'],
					'author_id'			=> $row['author_id'],
					'approved'			=> 1,
					);

				$this->lib->convertComment($row['msg_id'], $save);
			}

			$this->lib->next();

		}


		/**
		 * Convert Form Fields
		 *
		 * @deprecated As of Gallery 4.x
		 * @access	private
		 * @return void
		 **/
		/*private function convert_gallery_form_fields()
		{
			if ($this->request['finish'])
			{
				$action = 'gallery_form_fields';
				// Save that it's been completed
				$get = unserialize($this->settings['conv_completed']);
				$us = $get[$this->lib->app['name']];
				$us = is_array($us) ? $us : array();
				if (empty($this->lib->errors))
				{
					$us = array_merge($us, array($action => true));
				}
				else
				{
					$us = array_merge($us, array($action => 'e'));
				}
				$get[$this->lib->app['name']] = $us;
				IPSLib::updateSettings(array('conv_completed' => serialize($get)));

				// Errors?
				if (!empty($this->lib->errors))
				{
					$es = 'The following errors occurred: <ul>';
					foreach ($this->lib->errors as $e)
					{
						$es .= "<li>{$e}</li>";
					}
					$es .= '</ul>';
				}
				else
				{
					$es = 'No problems found.';
				}

				// Display
				$this->registry->output->html .= $this->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
				$this->sendOutput();

			}

			$count = 0;
			foreach($this->_getCustomFields() as $k => $v)
			{
				$count++;
				$this->lib->convertFormField($k, array('name' => $v, 'type' => 'text'));
			}

			IPSLib::updateSettings(array('conv_error' => serialize($this->lib->errors)));
			$plusone = $count + 1;
			$this->registry->output->redirect("{$this->settings['base_url']}app=convert&module={$this->lib->app['sw']}&section={$this->lib->app['app_key']}&do={$this->request['do']}&finish=1", "{$count} of {$count} converted<br />Finishing..." );
		}*/

		/**
		 * Get the names and keys of custom image fields
		 *
		 * @deprecated As of Gallery 4.x
		 * @access	private
		 * @return  array 	Custom image fields
		 **/
		/*private function _getCustomFields()
		{
			$return = array();
			ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'config', 'where' => "name IN('user_field1_name', 'user_field2_name', 'user_field3_name', 'user_field4_name')"));
			ipsRegistry::DB('hb')->execute();
			while ($r = ipsRegistry::DB('hb')->fetch())
			{
				if (!$r['value'])
				{
					continue;
				}
				$key = str_replace('_name', '', $r['name']);
				$return[$key] = $r['value'];
			}
			return $return;
		}*/

	}

