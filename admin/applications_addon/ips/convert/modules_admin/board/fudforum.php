<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * FudForum
 * Last Update: $Date: 2010-03-19 11:03:12 +0100(ven, 19 mar 2010) $
 * Last Updated By: $Author: Andy Millne $
 *
 * @package		IPS Converters
 * @author 		Andy Millne
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 */

	$info = array(
		'key'	=> 'fudforum',
		'name'	=> 'FUDForum',
		'login'	=> true,
	);

	class admin_convert_board_fudforum extends ipsCommand
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

			// array('action' => array('action that must be completed first'))
			$this->actions = array(
				'forum_perms'	=> array(),
				'groups' 		=> array('forum_perms'),
				'members'		=> array('groups'),
				'ignored_users'	=> array('members'),
				'forums'		=> array(),
				'topics'		=> array('members', 'forums'),
				'posts'			=> array('members', 'topics'),
			//	'polls'			=> array('topics', 'members', 'forums'),
			//	'pms'			=> array('members'),
				'attachments'	=> array('posts'),	

				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'FUDforum &rarr; IP.Board Converter' );

			//-----------------------------------------
			// Are we connected?
			// (in the great circle of life...)
			//-----------------------------------------

			$this->HB = $this->lib->connect();

			//-----------------------------------------
			// Parser
			//-----------------------------------------

			require_once( IPS_ROOT_PATH . 'sources/handlers/han_parse_bbcode.php' );
			$this->parser           =  new parseBbcode( $registry );
			$this->parser->parse_smilies = 1;
		 	$this->parser->parse_bbcode  = 1;
		 	$this->parser->parsing_section = 'convert';


		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------

		if (array_key_exists($this->request['do'], $this->actions) or $this->request['do'] == 'boards')
		{
			call_user_func(array($this, 'convert_'.$this->request['do']));
		}
		else
		{
			$this->lib->menu( array(
				'forums' => array(
					'single' => 'cat',
					'multi'  => array( 'cat', 'forum' )
				) )	);
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
					return  $this->lib->countRows('level');
					break;
					
				case 'groups':
					return  $this->lib->countRows('level');
					break;

				case 'forums':
					return  $this->lib->countRows('forum') + $this->lib->countRows($this->prefixFull . 'cat');
					break;

				case 'topics':
					return  $this->lib->countRows('thread');
					break;
				
				case 'posts':
					return  $this->lib->countRows('msg');
					break;					

				case 'members':
					return  $this->lib->countRows('users');
					break;

				case 'ignored_users':
					return  $this->lib->countRows('user_ignore');
					break;

				case 'attachments':
					return  $this->lib->countRows('attach');
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
				case 'ranks':
				case 'attachments':
				case 'posts':
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
		private function fixPostData($text)
		{
			return $text;
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
							'from' 		=> 'level',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'id', 'nf' => 'name'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['id'], $row['name']);
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
							'from' 		=> 'level',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------

			$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'id', 'nf' => 'name'));

			//---------------------------
			// Loop
			//---------------------------

			foreach( $loop as $row )
			{


				$save = array(
					'g_title'			=> $row['name'],
				    'g_perm_id'			=> $row['id'],
					
					);
				$this->lib->convertGroup($row['id'], $save);
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

			$pcpf = array(
				'location'		=> 'Location',
				'icq'		=> 'ICQ Number',
				'aim'		=> 'AIM ID',
				'yahoo'		=> 'Yahoo ID',
				'msnm'		=> 'MSN ID',
				'jabber'	=> 'Jabber ID',
				'home_page'	=> 'Website',
				'occupation'		=> 'Occupation',
				'interests'=> 'Interests',
				);

			$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('pp_path')));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'users',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('members', $main);

			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();

			// We need to know how to the avatar paths
			$ask['pp_path']  	= array('type' => 'text', 'label' => 'Path to site root (no trailing slash, no /forum/, default /): ');
		
			// And those custom profile fields
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

			//-----------------------------------------
			// Get our custom profile fields
			//-----------------------------------------

			if (isset($us['pfield_group']))
			{
				$this->DB->build(array('select' => '*', 'from' => 'pfields_data', 'where' => 'pf_group_id='.$us['pfield_group']));
				$this->DB->execute();
				$pfields = array();
				while ($row = $this->DB->fetch())
				{
					$pfields[] = $row;
				}
			}
			else
			{
				$pfields = array();
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{



				
				// Basic info
				$info = array(
					'id'				=> $row['id'],
					'group'				=> $row['level_id'],
					'joined'			=> $row['join_date'],
					'username'			=> $row['login'],
					'email'				=> $row['email'],
					'password'			=> $row['passwd'],
					);

				//Birthday
				$birthday = false;
					
					if(strlen($row['birthday']) == 8){
						
						$bday_day = substr($row['birthday'], 0, 4);
						$bday_month = substr($row['birthday'], 4, 2);
						$bday_year = substr($row['birthday'], 6, 2);
						$birthday = true;
					}
					
				$members = array(
					'ip_address'		=> long2ip($row['last_known_ip']),
					'misc'				=> $row['salt'],
					'bday_day'			=> ($birthday) ? $bday_day : '',
					'bday_month'		=> ($birthday) ? $bday_month : '',
					'bday_year'			=> ($birthday) ? $bday_year : '',
					'last_visit'		=> $row['last_visit'],
					'posts'				=> $row['posted_msg_count'],
					
					);

				// Profile
				$profile = array(
					'signature'			=> $this->fixPostData($row['sig']),
					);

				//-----------------------------------------
				// Avatars
				//-----------------------------------------

									
				$avatar_url = preg_replace('/.*src=([\'"])((?:(?!\1).)*)\1.*/si','$2',$row['avatar_loc']);
				

				$avatar_parsed = parse_url($avatar_url, PHP_URL_PATH);
				
				if($row['avatar_loc']) {
					$profile['avatar_type'] = 'upload';
					$profile['avatar_location'] = $avatar_parsed;
					$path = $us['pp_path'];
				}

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
				// And go!
				//-----------------------------------------

				$this->lib->convertMember($info, $members, $profile, $custom, $path);
			}

			$this->lib->next();

		}

	/**
	 * Convert Categories
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_forums()
	{
		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> $this->prefixFull . 'cat',
						'order'		=> 'id ASC',
					);

		$loop = $this->lib->load('forums', $main, array(), array('boards', 'forum') );

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			
			$save = array(
			'name' => $row['name'],
			'position'	  => $row['view_order'],	
			'parent_id' => -1,
			
			);
			
			$this->lib->convertForum('c'.$row['id'], $save , array());
		}

		$this->lib->next();

	}

	/**
	 * Convert Forums
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_boards()
	{

		//---------------------------
		// Set up
		//---------------------------

		$mainBuild = array(	'select' 	=> '*',
							'from' 		=> $this->prefixFull . 'forum',
							'order'		=> 'id ASC',
						);

		$this->start = intval($this->request['st']);
		$this->end = $this->start + intval($this->request['cycle']);

		$mainBuild['limit'] = array($this->start, $this->end);

		$this->errors = unserialize($this->settings['conv_error']);

		ipsRegistry::DB('hb')->build($mainBuild);
		ipsRegistry::DB('hb')->execute();

		if (!ipsRegistry::DB('hb')->getTotalRows())
		{
			$action = 'forums';
			// Save that it's been completed
			$get = unserialize($this->settings['conv_completed']);
			$us = $get[$this->lib->app['name']];
			$us = is_array($us) ? $us : array();
			if (empty($this->errors))
			{
				$us = array_merge($us, array($action => true));
			}
			else
			{
				$us = array_merge($us, array($action => 'e'));
			}
			$get[$this->app['name']] = $us;
			IPSLib::updateSettings(array('conv_completed' => serialize($get)));

			// Errors?
			if (!empty($this->errors))
			{
				$es = 'The following errors occurred: <ul>';
				foreach ($this->errors as $e)
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

		while ( $row = ipsRegistry::DB('hb')->fetch() )
		{
			$records[] = $row;
		}

		$loop = $records;

		//---------------------------
		// Loop
		//---------------------------

		foreach( $loop as $row )
		{

				
			// Set info
			$save = array(
				'parent_id'			=> 'c'.$row['cat_id'],
				'position'			=> $row['view_order'],
			
				'name'				=> $row['name'],
				'description'		=> $row['descr'],
				'topics'			=> $row['thread_count'],
				'posts'				=> $row['post_count'],
				'inc_postcount'		=> 1,
				);

			// Save
			$this->lib->convertForum($row['id'], $save, array());



		}

		//-----------------------------------------
		// Next
		//-----------------------------------------

		$total = $this->request['total'];
		$pc = round((100 / $total) * $this->end);
		$message = ($pc > 100) ? 'Finishing...' : "{$pc}% complete";
		IPSLib::updateSettings(array('conv_error' => serialize($this->errors)));
		$end = ($this->end > $total) ? $total : $this->end;
		$this->registry->output->redirect("{$this->settings['base_url']}app=convert&module={$this->lib->app['sw']}&section={$this->lib->app['app_key']}&do={$this->request['do']}&st={$this->end}&cycle={$this->request['cycle']}&total={$total}", "{$end} of {$total} converted<br />{$message}" );

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

		$main = array(	'select' 	=> 't.*',
						'from' 		=> array( $this->prefixFull . 'thread' => 't'),
						'order'		=> 't.id ASC',
						'add_join'	=> array(
										array( 	'select' => 'p.subject, p.post_stamp, p.poster_id',
												'from'   =>	array( $this->prefixFull . 'msg' => 'p' ),
												'where'  => "t.root_msg_id=p.id",
												'type'   => 'left'
											),
										array( 	'select' => 'l.poster_id as last_poster_id',
												'from'   =>	array( $this->prefixFull . 'msg' => 'l' ),
												'where'  => "t.last_post_id=l.id",
												'type'   => 'left'
											),
										),

					);

			$loop = $this->lib->load('topics', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				$save = array(
					'title'				=> $row['subject'],
					'state'		   	 	=> 'open',
					'posts'		    	=> $row['replies'],
					'starter_id'    	=> $row['poster_id'],
					'start_date'    	=> $row['post_stamp'],
					'last_post' 	    => $row['last_post_date'],
					'last_poster_id'	=> $row['last_poster_id'],
					'views'			 	=> $row['views'],
					'forum_id'		 	=> $row['forum_id'],
					'approved'		 	=> 1,
					'pinned'		 	=> 0,
					);

				$this->lib->convertTopic($row['id'], $save);

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
			
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('posts', array('message_path'));
			
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'msg',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('posts', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('posts', $loop, array('message_path' => array('type' => 'text', 'label' => 'The path to the folder where the messages are stored (no trailing slash - usually path_to_forum_data/messages):')), 'path');

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$path = $us['message_path'];
			
		
			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				
				// Get the post from the big bad file
				
				if (!file_exists( $path . '/msg_' . $row['file_id']))
				continue;

				if (!($row['length']))
				continue;
				
				$fp = fopen($path . '/msg_' . $row['file_id'], 'rb');
				fseek($fp, $row['foff']);

				$post = substr(strtr(fread($fp, $row['length']), array("\n" => '')), 0, 65534);

				fclose($fp);
				
				
				$save = array(
					'author_id'   => $row['poster_id'],
					'use_sig'     => 1,
					'use_emo'     => 1,
					'ip_address'  => $row['ip_addr'],
					'post_date'   => $row['post_stamp'],
					'post'		  => $this->fixPostData($post),
					'queued'      => $row['apr'] == 1 ? 0 : 1,
					'topic_id'    => $row['thread_id']
					);

				$this->lib->convertPost($row['id'], $save);

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

			$main = array(	'select' 	=> '*',
							'from' 		=> 'attach',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('attachments', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_forum_data/files):')), 'path');

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
			if (!is_readable($path))
			{
				$this->lib->error('Your remote upload path is not readable.');
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// get filename
				$file_array = explode("/", $row['location']);	
				$filename = end($file_array);
				
				// Is this an image?
				$explode = explode('.', $row['original_name']);
				$extension = array_pop($explode);
				$image = false;
					
				if ( in_array( $extension, array('jpeg', 'jpg', 'png', 'gif') ) )
				{
					$image = true;
				}
				
				// Sort out data
				$save = array(
					'attach_ext'			=> $extension,
					'attach_file'			=> $row['original_name'],
					'attach_location'		=> $filename,
					'attach_is_image'		=> $image,
					'attach_hits'			=> $row['dlcount'],
					'attach_member_id'		=> $row['owner'],
					'attach_filesize'		=> $row['fsize'],
					'attach_rel_id'			=> $row['message_id'],
					'attach_rel_module'		=> 'post',
					);

				// Send em on
				$done = $this->lib->convertAttachment($row['id'], $save, $path);

			}

			$this->lib->next();

		}

		/**
		 * Convert Ignored Users
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_ignored_users()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'user_ignore',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('ignored_users', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'ignore_owner_id'	=> $row['user_id'],
					'ignore_ignore_id'	=> $row['ignore_id'],
					'ignore_messages'	=> '1',
					'ignore_topics'		=> '1',
					);
				$this->lib->convertIgnore($row['user_id'].'-'.$row['ignore_id'], $save);
			}

			$this->lib->next();

		}

	

	}
