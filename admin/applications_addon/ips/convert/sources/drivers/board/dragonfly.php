<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * XMB
 * Last Update: $Date: 2011-07-12 21:15:48 +0100 (Tue, 12 Jul 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 550 $
 */

	$info = array(
		'key'	=> 'dragonfly',
		'name'	=> 'Dragonfly CMS 9.2',
		'login'	=> false,
	);
	
	class admin_convert_board_dragonfly extends ipsCommand
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
				'members'		=> array(),
				'forums'		=> array(),
				'topics'		=> array('members', 'forums'),
				'posts'			=> array('members', 'topics'),
				'attachments'	=> array('posts'),
				);
					
			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );
	
	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'Dragonfly &rarr; IP.Board Converter' );
	
			//-----------------------------------------
			// Are we connected?
			// (in the great circle of life...)
			//-----------------------------------------
			
			$this->HB = $this->lib->connect();

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
						'single' => 'bbcategories',
						'multi'  => array( 'bbcategories', 'bbforums' )
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
				case 'members':
					return $this->lib->countRows( 'users', 'user_id > 1' );
					break;
					
				case 'forums':
					return $this->lib->countRows( 'bbcategories' ) + $this->lib->countRows( 'bbforums' );
					break;
					
				case 'boards':
					return $this->lib->countRows( 'bbforums' );
					break;
					
				case 'topics':
				case 'posts':
					return $this->lib->countRows( 'bb' . $action );
					break;
					
				case 'attachments':
					return $this->lib->countRows( 'bbattachments', 'post_id > 0' );
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
				'user_website'		=> 'Website',
				'user_icq'			=> 'ICQ Number',
				'user_occ'			=> 'Occupation',
				'user_from'			=> 'Location',
				'user_interests'	=> 'Interests',
				'user_aim'			=> 'AIM',
				'user_yim'			=> 'Yahoo Messenger',
				'user_skype'		=> 'Skype',
				'user_msnm'			=> 'MSN Messenger',
				'bio'				=> 'Extra Info',
				);
			
			$this->lib->saveMoreInfo('members', array_merge( array_keys( $pcpf ), array( 'gal_path' ) ) );

			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'users',
							'where'		=> 'user_id > 1',
							'order'		=> 'user_id ASC',
						);
						
			$loop = $this->lib->load('members', $main);
			
			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------
			
			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();
			
			$ask['gal_path'] 	= array('type' => 'text', 'label' => 'Path to avatars folder (no trailing slash, default /path_to_dragonfly/images/avatars): ');
							
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
																
				// Basic info				
				$info = array(
					'id'				=> $row['user_id'],
					'group'				=> $INFO['member_group'],
					'joined'			=> $row['user_regdate'],
					'username'			=> $row['username'],
					'email'				=> $row['user_email'],
					'md5pass'			=> $row['user_password'],
					);
				
				// Member info
				$birthday = explode('-', $row['bday']);
				
				$members = array(
					'posts'				=> $row['user_posts'],
					'last_visit'		=> $row['user_lastvisit'],
					);
					
				// Profile
				$profile = array(
					'signature'			=> $this->fixPostData( $row['user_sig'] ),
					);
				
				//-----------------------------------------
				// Avatars
				//-----------------------------------------
				
				if ( $row['user_avatar'] )
				{
					if ( substr( $row['user_avatar'], 0, 7 ) )
					{
						$profile['photo_type'] = 'url';
						$profile['photo_location'] = $row['user_avatar'];
					}
					else
					{
						$profile['photo_type'] = 'custom';
						$profile['photo_location'] = $row['user_avatar'];
						$path = $us['gal_path'];
					}
				}
																				
				//-----------------------------------------
				// And go!
				//-----------------------------------------

				$this->lib->convertMember($info, $members, $profile, array(), '');			
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
							'from' 		=> 'bbcategories',
							'order'		=> 'cat_id ASC',
						);

			$loop = $this->lib->load('forums', $main, array(), array( 'boards', 'bbforums' ) );
			
			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertForum('c'.$row['cat_id'], array('name' => $row['cat_title'], 'parent_id' => -1), array());
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
								'from' 		=> 'bbforums',
								'order'		=> 'forum_id ASC',
							);
								
			$this->start = intval($this->request['st']);
			$this->end = $this->start + intval($this->request['cycle']);
			
			$mainBuild['limit'] = array($this->start, $this->end);
						
			$this->lib->errors = unserialize($this->settings['conv_error']);
			
			ipsRegistry::DB('hb')->build($mainBuild);
			ipsRegistry::DB('hb')->execute();
			
			if (!ipsRegistry::DB('hb')->getTotalRows())
			{
				$action = 'forums';
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
				$get[$this->app['name']] = $us;
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
				// Work out what the parent is
				if ($row['parent_id'])
				{
					$parent = $row['parent_id'];
				}
				else
				{
					$parent = 'c'.$row['cat_id'];
				}
				
				$redirect_on = (bool) $row['redirect'];
				
				// Set info
				$save = array(
					'parent_id'			=> $parent,
					'position'			=> $row['forum_order'],
					'last_id'			=> $row['forum_last_post_id'],
					'name'				=> $row['forum_name'],
					'description'		=> $row['forum_desc'],
					'topics'			=> $row['forum_topics'],
					'posts'				=> $row['forum_posts'],
					);
					
				// Save
				$this->lib->convertForum($row['forum_id'], $save, array());
								
			}

			//-----------------------------------------
			// Next
			//-----------------------------------------
				
			$total = $this->request['total'];
			$pc = round((100 / $total) * $this->end);
			$message = ($pc > 100) ? 'Finishing...' : "{$pc}% complete";
			IPSLib::updateSettings(array('conv_error' => serialize($this->lib->errors)));
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
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'bbtopics',
							'order'		=> 'topic_id ASC',
						);
			
			$loop = $this->lib->load('topics', $main, array('tracker'));
						
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				
				// Ignore shadow topics
				if ($row['topic_status'] == 2)
				{
					continue;
				}
				
				$save = array(
					'title'				=> $row['topic_title'],                                      
					'state'		   	 	=> $row['topic_status'] == 0 ? 'open' : 'closed',            
					'posts'		    	=> $row['topic_replies'],                                    
					'starter_id'    	=> $row['topic_poster'],                                    
					'starter_name'  	=> $row['topic_first_poster_name'],                         
					'start_date'    	=> $row['topic_time'],                                      
					'last_post' 	    => $row['topic_last_post_time'],                            
					'last_poster_id'	=> $row['topic_last_poster_id'],                            
					'last_poster_name'	=> $row['topic_last_poster_name'],                          
					'poll_state'	 	=> ($row['poll_title'] != '') ? 'open' : 0,                 
					'last_vote'		 	=> $row['poll_last_vote'],                                   
					'views'			 	=> $row['topic_views'],                                      
					'forum_id'		 	=> $row['forum_id'],                                         
					'approved'		 	=> $row['topic_approved'],                                   
					'author_mode'	 	=> 1,                                                        
					'pinned'		 	=> $row['topic_type'] == 0 ? 0 : 1,
					'topic_hasattach'	=> $row['topic_attachment'],
					);
				
				$this->lib->convertTopic($row['topic_id'], $save);
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
			
			$main = array(	'select' 	=> 'p.*',
							'from' 		=> array('bbposts' => 'p'),
							'add_join'	=> array(
								array( 	'select' => 't.*',
										'from'   =>	array( 'bbposts_text' => 't' ),
										'where'  => "p.post_id=t.post_id",
										'type'   => 'inner'
									),
								),
							'order'		=> 'p.post_id ASC',
						);
			
			$loop = $this->lib->load('posts', $main);
						
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'author_id'   => $row['poster_id'],
					'author_name' => $row['username'] ? $row['username'] : $row['post_username'],
					'use_sig'     => $row['enable_sig'],
					'use_emo'     => $row['enable_smilies'],
					'ip_address'  => $row['poster_ip'],
					'post_date'   => $row['post_time'],
					'post'		  => $this->fixPostData($row['post_text']),
					'topic_id'    => $row['topic_id'],
					'post_title'  => $row['post_subject'],
					);
				
				$this->lib->convertPost($row['post_id'], $save);
				
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
							'from' 		=> array('bbattachments' => 'a'),
							'where'		=> 'a.post_id > 0',
							'add_join'	=> array(
								array( 	'select' => 'd.*',
										'from'   =>	array( 'bbattachments_desc' => 'd' ),
										'where'  => "a.attach_id=d.attach_id",
										'type'   => 'inner'
									),
								),
							'order'		=> 'a.attach_id ASC',
						);
						
			$loop = $this->lib->load('attachments', $main);
			
			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------
						
			$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_dragonfly/uploads/forums):')), 'path');
			
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
				// Is this an image?
				$image = false;
				if (preg_match('/image/', $row['mimetype']))
				{
					$image = true;
				}
				
				// Sort out data
				$save = array(
					'attach_ext'			=> $row['extension'],
					'attach_file'			=> $row['real_filename'],
					'attach_location'		=> $row['physical_filename'],
					'attach_is_image'		=> $image,
					'attach_hits'			=> $row['download_count'],
					'attach_date'			=> $row['filetime'],
					'attach_member_id'		=> $row['poster_id'],
					'attach_filesize'		=> $row['filesize'],
					'attach_rel_id'			=> $row['post_id'],
					'attach_rel_module'		=> $row['in_message'] ? 'msg' : 'post',
					);
				
				
				// Send em on
				$done = $this->lib->convertAttachment($row['attach_id'], $save, $path);
				
				// Fix inline attachments
				if ($done === true)
				{
					$aid = $this->lib->getLink($row['attach_id'], 'attachments');
					
					$field = 'post';
					$table = 'posts';
					$pid = $this->lib->getLink($save['attach_rel_id'], 'posts');
					$where = "pid={$pid}";
					
					if(!$pid)
					{
						continue;
					}
				
					$attachrow = $this->DB->buildAndFetch( array( 'select' => $field, 'from' => $table, 'where' => $where ) );
					
					$update = preg_replace('/\[attachment=\d+\]<!-- .+? -->' . preg_quote( $row['real_filename'] ) . '<!-- .+? -->\[\/attachment\]/i', "[attachment={$aid}:{$save['attach_location']}]", $attachrow[$field]);
					$this->DB->update($table, array($field => $update), $where);
					
				}
				
			}

			$this->lib->next();

		}
												
	}
	
