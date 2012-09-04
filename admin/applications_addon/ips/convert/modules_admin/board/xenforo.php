<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * XenForo
 * Last Updated By: $Author: AndyMillne $
 *
 * @package		IPS Converters
 * @author 		Andrew Millne
 * @copyright	(c) 2011 Invision Power Services, Inc.

 */


	$info = array(
		'key'	=> 'xenforo',
		'name'	=> 'XenForo',
		'login'	=> true,
	);

	class admin_convert_board_xenforo extends ipsCommand
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
			//-----------------------------------------
			// What can this thing do?
			//-----------------------------------------

			// array('action' => array('action that must be completed first'))
			$this->actions = array(
				'emoticons'		=> array(),
				'forum_perms' => array(),
				'groups'	=> array('forum_perms'),
				'members'	=> array('groups'),
				'forums'	=> array(),
				'topics'	=> array('forums'),
				'posts'		=> array('topics', 'emoticons'),
				'attachments'=> array('posts'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'XenForo &rarr; IP.Board Converter' );

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
				case 'members':
					return $this->lib->countRows('user');
					break;

				case 'groups':
				case 'forum_perms':
					return $this->lib->countRows('user_group');
					break;

				case 'forums':
					return $this->lib->countRows('node', "node_type_id = 'Category' OR node_type_id = 'Forum'");
					break;

				case 'topics':
					return $this->lib->countRows('thread');
					break;

				case 'posts':
					return $this->lib->countRows('post');
					break;

				case 'attachments':
					return $this->lib->countRows('attachment');
					break;

				case 'emoticons':
					return $this->lib->countRows('smilie');
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
				case 'members':
				case 'groups':
				case 'forum_perms':
				case 'emoticons':
				case 'attachments':
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
							'from' 		=> 'user_group',
							'order'		=> 'user_group_id ASC',
						);

			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'user_group_id', 'nf' => 'title'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['user_group_id'], $row['title']);
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
							'from' 		=> 'user_group',
							'order'		=> 'user_group_id ASC',
						);

			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'user_group_id', 'nf' => 'title'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{			

				$save = array(
					'g_title'				=> $row['title'],
					'g_mem_info'			=> 1,
					'g_invite_friend'		=> 1,
					'g_perm_id'				=> $row['user_group_id'],
					);
					
				//-----------------------------------------
				// Handle group settings
				//-----------------------------------------			
					
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'permission_entry', 'where' => "user_group_id={$row['user_group_id']} AND permission_group_id IN ('general', 'forum', 'conversation')"));
				$subRes = ipsRegistry::DB('hb')->execute();
				while ($settings = ipsRegistry::DB('hb')->fetch($subRes))	
				{
					switch($settings['permission_id']) {
						
						case 'view':
							$save['g_view_board'] = ($settings['permission_value'] == 'allow') ? 1 : 0;
							break;
		
						case 'deleteOwnPost' :
							$save['g_delete_own_posts'] = ($settings['permission_value'] == 'allow') ? 1 : 0;
							break;
		
						case 'editOwnPost':
							$save['g_edit_posts'] = ($settings['permission_value'] == 'allow') ? 1 : 0;
							break;
							
						case 'postThread':
							$save['g_post_new_topics'] = ($settings['permission_value'] == 'allow') ? 1 : 0;
							$save['g_post_polls'] = ($settings['permission_value'] == 'allow') ? 1 : 0;							
							break;

						case 'postReply':
							$save['g_reply_other_topics'] = ($settings['permission_value'] == 'allow') ? 1 : 0;
							$save['g_reply_own_topics'] = ($settings['permission_value'] == 'allow') ? 1 : 0;							
							break;

						case 'votePoll':
							$save['g_vote_polls'] = ($settings['permission_value'] == 'allow') ? 1 : 0;
							break;

						case 'deleteOwnThread':
							$save['g_delete_own_topics'] = ($settings['permission_value'] == 'allow') ? 1 : 0;
							break;	

						case 'maxRecipients':
							$save['g_max_mass_pm'] = $settings['permission_value_int'];
							break;	

						case 'bypassFloodCheck':
							$save['g_avoid_flood'] = ($settings['permission_value'] == 'allow') ? 1 : 0;
							break;																		
					}
					
				}
					
				$this->lib->convertGroup($row['user_group_id'], $save);
			}

			$this->lib->next();

		}

		/**
		 * Convert Members
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_members()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$pcpf = array(
				'homepage'		=> 'Website',
				'skype'			=> 'Skype',
				'icq'			=> 'ICQ Number',
				'aim'			=> 'AIM ID',
				'yahoo'			=> 'Yahoo ID',
				'msn'			=> 'MSN ID',			
				);

			$this->lib->saveMoreInfo( 'members', array_merge( array_keys($pcpf), array( 'avvy_path', 'pp_path' ) ) );

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> 'u.*',
							'from' 		=> array('user' => 'u'),
							'order'		=> 'u.user_id ASC',
							'add_join'	=> array(
											array( 	'select' => 'p.*',
													'from'   =>	array( 'user_profile' => 'p' ),
													'where'  => "u.user_id = p.user_id",
													'type'   => 'left'
												),
											array( 	'select' => 'a.*',
													'from'   =>	array( 'user_authenticate' => 'a' ),
													'where'  => "u.user_id = a.user_id",
													'type'   => 'left'
												),												
											),
						);


			$loop = $this->lib->load('members', $main);

			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();

			// We need to know the avatars path
			$ask['avvy_path'] = array('type' => 'text', 'label' => 'The path to the folder where custom avatars are saved (no trailing slash - usually /path_to_xf/data/avatars):');
			
			// And those custom profile fields
			$options = array('x' => '-Skip-');
			$this->DB->build(array('select' => '*', 'from' => 'pfields_data'));
			$fieldRes = $this->DB->execute();
			while ($row = $this->DB->fetch($fieldRes))
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
				
				// Identities	
				if ($row['identities']) {
					$row = array_merge(unserialize($row['identities']), $row);
				}	

				// Password
				$password = unserialize($row['data']);
			
				// Basic info
				$info = array(
								'id'             	=> $row['user_id'],
								'username'     	 	=> $row['username'],
								'email'			 	=> $row['email'],
								'group'			 	=> $row['user_group_id'],
								'secondary_groups'	=> $row['secondary_group_ids'],
								'joined'			=> $row['register_date'],
								'password'		 => $password['hash'],
								);

				$members = array(
								'title'				=> strip_tags($row['custom_title']),
								'last_visit'		=> $row['last_activity'],
								'last_activity'		=> $row['last_activity'],
								'posts'				=> $row['message_count'],
								'bday_day'			=> $row['dob_day'],
								'bday_month'		=> $row['dob_month'],
								'bday_year'			=> $row['dob_year'],
								'misc'				=> $password['salt'],
								'member_banned'		=> $row['is_banned'],
								);

				// Profile
				$profile = array(
								'signature'				=> $this->fixPostData($row['signature']),
								'pp_setting_count_friends' => 1,
								'pp_setting_count_comments' => 1,
								);



				//-----------------------------------------
				// Custom Profile fields
				//-----------------------------------------

				foreach ($pcpf as $id => $name)
				{
					if ($us[$id] != 'x')
					{
						$custom['field_'.$us[$id]] = $row[$id];
					}
				}
				
				//-----------------------------------------
				// Avatars 
				//-----------------------------------------
				
				$group = floor($row['user_id'] / 1000);
				
				if (file_exists($us['avvy_path'] . "l/{$group}/{$row['user_id']}.jpg")) {
					
					$profile['avatar_type'] = 'upload';

					$profile['avatar_location'] = "l/{$group}/{$row['user_id']}.jpg";
					$profile['avatar_size'] = $row['avatar_width'] . 'x' . $row['avatar_height'];
					$profile['avatar_filesize'] = filesize($us['avvy_path'] . "l/{$group}/{$row['user_id']}.jpg");
				}


				//-----------------------------------------
				// Go
				//-----------------------------------------

				$this->lib->convertMember($info, $members, $profile, $custom, $us['avvy_path']);

			}

			$this->lib->next();

		}

		/**
		 * Convert Forums
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_forums()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> 'n.node_id AS node, n.title, n.description, n.parent_node_id',
							'from' 		=> array('node' => 'n'),
							'order'		=> 'n.node_id ASC',
							'where'		=> "node_type_id = 'Category' OR node_type_id = 'Forum'",
							'add_join'	=> array(
											array( 	'select' => 'f.*',
													'from'   =>	array( 'forum' => 'f' ),
													'where'  => "n.node_id = f.node_id",
													'type'   => 'left'
												),
											
											),
						);

			$loop = $this->lib->load('forums', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Work stuff out
				//-----------------------------------------

				// Permissions will need to be reconfigured
				$perms = array();

				//-----------------------------------------
				// Save
				//-----------------------------------------

				$save = array(
					'topics'			=> $row['discussion_count'],
					'posts'			  	=> $row['message_count'],
					'last_post'		  	=> $row['last_post_date'],
					'last_poster_name'	=> $row['last_post_user_id'],
					'parent_id'		  	=> ($row['parent_node_id']) ? $row['parent_node_id'] : -1,
					'name'			  	=> $row['title'],
					'description'	  	=> $row['description'],
					'position'		  	=> $row['display_order'],
					'status'			=> $row['allow_posting'],
					'inc_postcount'	  	=> 1,
					'preview_posts'		=> $row['moderate_messages'],
					);

				$this->lib->convertForum($row['node'], $save, $perms);

			}

			$this->lib->next();

		}


		/**
		 * Convert Topics
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_topics()
		{
			//---------------------------
			// Set up
			//---------------------------
			$main = array( 'select' => '*',
						   'from'   => 'thread',
						   'order'  => 'thread_id ASC' );

			$loop = $this->lib->load('topics', $main);

			$this->lib->prepareDeletionLog('topics');

			//---------------------------
			// Loop
			//---------------------------
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				
				$save = array( 'title'			  => $row['title'],
							   'state'			  => ($row['discussion_open'] == 1) ? 'open' : 'closed',
							   'posts'			  => $row['reply_count'],
							   'starter_id'		  => $row['user_id'],
							   'starter_name'	  => $row['username'],
							   'start_date'		  => $row['post_date'],
							   'last_post'		  => $row['last_post_date'],
							   'last_poster_name' => $row['last_post_username'],
							   'views'			  => $row['view_count'],
							   'forum_id'		  => $row['node_id'],
							   'approved'		  => ( $row['discussion_state'] == 'visible' ) ? 1 : 0,
							   'pinned'			  => $row['sticky'],
							  );

				$this->lib->convertTopic($row['thread_id'], $save);

			}
			$this->lib->next();
		}


		/**
		 * Convert Posts
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_posts()
		{
			//---------------------------
			// Set up
			//---------------------------
			$main = array( 'select' => '*',
						   'from'   => 'post',
						   'order'  => 'post_id ASC' );

			
			$main = array(	'select' 	=> 'p.*',
							'from' 		=> array('post' => 'p'),
							'order'		=> 'p.post_id ASC',
							'add_join'	=> array(
											array( 	'select' => 'i.*',
													'from'   =>	array( 'ip' => 'i' ),
													'where'  => "p.ip_id = i.ip_id",
													'type'   => 'left'
												),
											
											),
						);			
			
			$loop = $this->lib->load('posts', $main);
		

			//---------------------------
			// Loop
			//---------------------------
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				//-----------------------------------------
				// Save
				//-----------------------------------------

				$save = array( 
							   'author_id'		=> $row['user_id'],
							   'author_name' 	=> $row['username'],
							   'use_sig'     	=> 1,
							   'use_emo'     	=> 1,
							   'ip_address' 	=> long2ip($row['ip']),
							   'post_date'   	=> $row['post_date'],
							   'post'		 	=> $this->fixPostData($row['message']),
							   'queued'      	=> ($row['message_state']) == 'visible' ? 0 : 2,
							   'topic_id'    	=> $row['thread_id'],
							   );

				$this->lib->convertPost($row['post_id'], $save);
			}
			$this->lib->next();
		}


		/**
		 * Convert Emoticons
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_emoticons()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('emoticons', array('emo_path', 'emo_opt'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'smilie',
							'order'		=> 'smilie_id ASC',
						);

			$loop = $this->lib->load('emoticons', $main);

			//-----------------------------------------
			// We need to know the path and how to handle duplicates
			//-----------------------------------------

			$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_xf/styles/default/xenforo/smilies):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			IPSLib::updateSettings(array('conv_extra' => serialize($get)));
			$path = $us['emo_path'];

			//-----------------------------------------
			// Check all is well
			//-----------------------------------------

			if (!is_writable(DOC_IPS_ROOT_PATH.'public/style_emoticons/'))
			{
				$this->lib->error('Your IP.Board emoticons path is not writeable. '.DOC_IPS_ROOT_PATH.'public/style_emoticons/');
			}
			if (!is_readable($path))
			{
				$this->lib->error('Your remote emoticons path is not readable.');
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'typed'		=> $row['smilie_text'],
					'image'		=> preg_replace('#^(.+)?xenforo/smilies/(.+?)$#', '$2', $row['image_url']),
					'clickable'	=> 0,
					'emo_set'	=> 'default',
					);
				$done = $this->lib->convertEmoticon($row['smilie_id'], $save, $us['emo_opt'], $path);
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
							'from' 		=> array('attachment' => 'a'),
							'order'		=> 'a.attachment_id ASC',
							'add_join'	=> array(
											array( 	'select' => 'd.*',
													'from'   =>	array( 'attachment_data' => 'd' ),
													'where'  => "a.data_id = d.data_id",
													'type'   => 'left'
												),
											
											),
							'where'		=> "content_type = 'post'",
						);

			$loop = $this->lib->load('attachments', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash, usually path_to_xf/internal_data/attachments):')), 'path');

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
				//-----------------------------------------
				// Init
				//-----------------------------------------

				// What's the extension?
				$e = explode('.', $row['filename']);
				$extension = array_pop( $e );
				
				// What's the mimetype?
				$type = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments_type', 'where' => "atype_extension='{$extension}'" ) );

				// Is this an image?
				$image = false;
				if (preg_match('/image/', $type['atype_mimetype']))
				{
					$image = true;
				}

				$save = array(
					'attach_ext'			=> $extension,
					'attach_file'			=> $row['filename'],
					'attach_is_image'		=> $image,
					'attach_hits'			=> $row['view_count'],
					'attach_date'			=> $row['attach_date'],
					'attach_member_id'		=> $row['user_id'],
					'attach_filesize'		=> $filedata['file_size'],
					'attach_rel_id'			=> $row['content_id'],
					'attach_rel_module'		=> 'post',
					);


					$tmpPath = "/" . floor($row['data_id'] / 1000);
					$save['attach_location'] = "{$row['data_id']}-{$row['file_hash']}.data";

					$done = $this->lib->convertAttachment($row['attachment_id'], $save, $path . $tmpPath);


				//-----------------------------------------
				// Fix inline attachments
				//-----------------------------------------

				if ($done === true)
				{
					$aid = $this->lib->getLink($row['attachment_id'], 'attachments');
					$pid = $this->lib->getLink($save['attach_rel_id'], 'posts');

					if ( $pid )
					{
						$attachrow = $this->DB->buildAndFetch( array( 'select' => 'post', 'from' => 'posts', 'where' => "pid={$pid}" ) );

						$rawaid = $row['attachment_id'];
						$update = preg_replace("/\[ATTACH(.+?)\]".$rawaid."\[\/ATTACH\]/i", "[attachment={$aid}:{$save['attach_file']}]", $attachrow['post']);

						$this->DB->update('posts', array('post' => $update), "pid={$pid}");
					}
				}

			}

			$this->lib->next();

		}


	}
