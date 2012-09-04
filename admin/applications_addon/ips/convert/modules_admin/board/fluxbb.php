<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * phpBB
 * Last Update: $Date: 2011-05-16 11:37:39 -0400 (Mon, 16 May 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 528 $
 */

	$info = array(
		'key'	=> 'fluxbb',
		'name'	=> 'FluxBB 1.4',
		'login'	=> true,
	);
		
	class admin_convert_board_fluxbb extends ipsCommand
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
				'forums'		=> array(),
				'topics'		=> array('members', 'forums'),
				'posts'			=> array('members', 'topics'),
				'ranks'			=> array(),
				'badwords'		=> array(),
				'banfilters'	=> array(),
				);
					
			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );
	
	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'punBB &rarr; IP.Board Converter' );
	
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
			
			if ( array_key_exists($this->request['do'], $this->actions) || $this->request['do'] == 'boards' )
			{
				call_user_func(array($this, 'convert_'.$this->request['do']));
			}
			else
			{
				$this->lib->menu( array(
					'forums' => array(
						'single' => 'categories',
						'multi'  => array( 'categories', 'forums' )
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
					return  $this->lib->countRows('groups');
					break;
					
				case 'members':
					return  $this->lib->countRows('users');
					break;
					
				case 'forums':
					return  $this->lib->countRows('categories') + $this->lib->countRows('forums');
					break;
					
				case 'ranks':
					return  $this->lib->countRows('ranks');
					break;
					
				case 'badwords':
					return  $this->lib->countRows('censoring');
					break;
					
				case 'banfilters':
					return  $this->lib->countRows('bans');
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
			
			$text = str_replace("<", "&lt;", $text);
            $text = str_replace(">", "&gt;", $text);
			
            $text = nl2br($text);	
			
			// We need to rework quotes a little (there's no standard on [quote]'s attributes)
			$text = preg_replace("#\[quote=(.+)\]#", "[quote name=$1]", $text);
			
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
							'from' 		=> 'groups',
							'order'		=> 'g_id ASC',
						);
						
			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------
						
			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'g_id', 'nf' => 'g_title'));

			//---------------------------
			// Loop
			//---------------------------
			
			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['g_id'], $row['g_title']);			
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
							'from' 		=> 'groups',
							'order'		=> 'g_id ASC',
						);
						
			$loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------
						
			$this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'g_id', 'nf' => 'g_title'));

			//---------------------------
			// Loop
			//---------------------------
			
			foreach( $loop as $row )
			{
				$save = array(
					'g_title'				=> $row['g_title'],
					'g_view_board'			=> ($row['g_read_board'] == 1) ? 1 : 0,
					'g_mem_info'			=> $row['g_view_users'],
					'g_delete_own_posts'	=> $row['g_delete_posts'],
					'g_delete_own_topics'	=> $row['g_delete_topics'],
					'g_use_search'			=> $row['g_search'],
					'g_search_flood'		=> $row['g_search_flood'],
					'g_perm_id'				=> $row['g_id'],
					);
				$this->lib->convertGroup($row['g_id'], $save);			
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
				'location'	=> 'Location',
				'icq'		=> 'ICQ Number',
				'aim'		=> 'AIM ID',
				'yahoo'		=> 'Yahoo ID',
				'msn'		=> 'MSN ID',
				'jabber'	=> 'Jabber ID',
				'url'		=> 'Website',
				);
			
			$this->lib->saveMoreInfo('members', array_merge(array_keys($pcpf), array('pp_path')));

			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'users',
							'order'		=> 'id ASC'
						);
						
			$loop = $this->lib->load('members', $main);
			
			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------
			
			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();
			
			// We need to know how to the avatar paths
			$ask['pp_path']  	= array('type' => 'text', 'label' => 'Path to avatars uploads folder (no trailing slash, default /path_to_punbb/img/avatars): ');
				
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
				
				//-----------------------------------------
				// Set info
				//-----------------------------------------
				
				/* Sort group id */
				switch ( $row['group_id'] )
				{
					case 0:
						$group = $this->settings['auth_group'];
						break;
					case 1:
						$group = $this->settings['admin_group'];
						break;
					case 2:
						$group = $this->settings['guest_group'];
						break;
					default:
						$group = $this->settings['member_group'];
						break;
				}
				
				// Basic info				
				$info = array(
					'id'				=> $row['id'],
					'group'				=> $group,
					'secondary_groups'	=> '',
					'joined'			=> $row['registered'],
					'username'			=> $row['username'],
					'email'				=> $row['email'],
					'password'			=> $row['password']
					);
				
				// Member info
				$time_arry = explode( ".", $row['user_timezone']);
				$time_offset  = $time_arry[0];
				
				$members = array(
					'ip_address'		=> $row['registration_ip'],
					'misc'				=> $row['salt'], # salt for our custom login method
					'title'				=> $row['title'],
					'posts'				=> $row['num_posts'],
					'time_offset'		=> $row['timezone'],
					'dst_in_use'		=> $row['dst'],
					'hide_email' 		=> ($row['email_setting'] == 2) ? 0 : 1,
					'email_full'		=> $row['notify_with_post'],
					'auto_track'		=> ($row['auto_notify'] == 1) ? 'immediate' : 0,
					'view_sigs'			=> $row['show_sig'],
					'view_img'			=> $row['show_img'],
					'view_avs'			=> $row['show_avatars'],
					'last_visit'		=> $row['last_visit'],
					'last_activity' 	=> $row['last_visit'],
					'last_post'			=> $row['last_post'],
					'email_pm'      	=> 0,
					'members_disable_pm'=> 0,
					'allow_admin_mails' => 1,
					);
					
				// Profile
				$profile = array(
					'signature'			=> $this->fixPostData($row['signature']),
					);
				
				//-----------------------------------------
				// Avatars - punBB likes to make odd things
				//-----------------------------------------
				
				$path = '';
				
				foreach ( array('jpg', 'gif', 'png') as $cur_type)
				{
					$temp_path = $us['pp_path'].'/'.$row['id'].'.'.$cur_type;
			
					if ( file_exists($temp_path) && $imgSize = @getimagesize($temp_path) )
					{
						$profile['avatar_type']		= 'upload';
						$profile['avatar_location']	= $row['id'].'.'.$cur_type;
						$profile['avatar_size']		= $imgSize[0].'x'.$imgSize[1];
						$path = $us['pp_path'];
						break;
					}
				}
				
				//-----------------------------------------
				// Custom Profile fields
				//-----------------------------------------
				
				// Pseudo
				foreach ($pcpf as $id => $name)
				{
					if ($us[$id] != 'x')
					{
						$custom['field_'.$us[$id]] = $row[$id];
					}
				}
				
				// Actual
				foreach ($pfields as $field)
				{
					if ($field['pf_type'] == 'drop')
					{
						$custom['field_'.$field['pf_id']] = $us['pfield_data'][$field['pf_key']][ $row[$field['pf_key']]-1 ];
					}
					else
					{
						$custom['field_'.$field['pf_id']] = $row[ $field['pf_key'] ];
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
							'from' 		=> 'categories',
							'order'		=> 'id ASC',
						);

			$loop = $this->lib->load('forums', $main, array(), array( 'boards', 'forums' ));
						
			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertForum('c'.$row['id'], array('name' => $row['cat_name'], 'parent_id' => -1, 'position' => $row['disp_position']), array());
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
			
			$mainBuild = array(	'select' => '*',
								'from'   => 'forums',
								'order'  => 'id ASC',
								);
									
			$this->start = intval($this->request['st']);
			$this->end = $this->start + intval($this->request['cycle']);
			
			$mainBuild['limit'] = array($this->start, $this->end);
						
			$this->lib->errors = array();# unserialize($this->settings['conv_error']);
			
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
				// Permissions will need to be reconfigured
				$perms = array();
				
				//-----------------------------------------
				// And go
				//-----------------------------------------
				
				$save = array(
					'parent_id'		=> $row['cat_id'] ? 'c'.$row['cat_id'] : -1,
					'position'		=> $row['disp_position'],
					'name'			=> $row['forum_name'],
					'description'	=> $row['forum_desc'],
					'sub_can_post'	=> $row['cat_id'] ? 1 : 0,
					'redirect_on'	=> $row['redirect_url'] ? 1 :0,
					'redirect_url'	=> $row['redirect_url'],
					'redirect_hits' => 0, # Hits not saved in punBB so reset to 0..
					'status'		=> 1,
					'posts'			=> $row['num_posts'],
					'topics'		=> $row['num_topics'],
					);
				
				$this->lib->convertForum($row['id'], $save, $perms);
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
							'from' 		=> 'topics',
							'order'		=> 'id ASC',
						);
			
			$loop = $this->lib->load('topics', $main, array('tracker'));
						
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Ignore moved topics
				if ( !is_null($row['moved_to']) || is_numeric($row['moved_to']) )
				{
					continue;
				}
				
				$save = array(
					'title'				=> $row['subject'],
					'state'		   	 	=> ($row['closed'] == 1) ? 'closed' : 'open',
					'posts'		    	=> $row['num_replies'],
					'starter_id'    	=> $row['first_post_id'],
					'starter_name'  	=> $row['poster'],
					'start_date'    	=> $row['posted'],                                      
					'last_post' 	    => $row['last_post'],
					'last_poster_id'	=> $row['last_post_id'],
					'last_poster_name'	=> $row['last_poster'],
					'poll_state'	 	=> 0,
					'last_vote'		 	=> 0,
					'views'			 	=> $row['num_views'],
					'forum_id'		 	=> $row['forum_id'],
					'approved'		 	=> 1,
					'author_mode'	 	=> 1,
					'pinned'		 	=> ($row['sticky'] == 1) ? 1 : 0,
					'topic_hasattach'	=> 0, // attachments not supported
					);
				
				$this->lib->convertTopic($row['id'], $save);
				
				//-----------------------------------------
				// Handle subscriptions
				//-----------------------------------------
				
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'topic_subscriptions', 'where' => "topic_id={$row['id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$savetracker = array(
						'member_id'	=> $tracker['user_id'],
						'topic_id'	=> $tracker['topic_id'],
						);
					
					$this->lib->convertTopicSubscription($tracker['topic_id'].'-'.$tracker['user_id'], $savetracker);
				}
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
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'posts',
							'order'		=> 'id ASC',
						);
			
			$loop = $this->lib->load('posts', $main);
			
			//-----------------------------------------
			// Prepare for reports conversion
			//-----------------------------------------
			
			$this->lib->prepareReports('post');
			
			$new = $this->DB->buildAndFetch( array( 'select' => 'status', 'from' => 'rc_status', 'where' => 'is_new=1' ) );
			$complete = $this->DB->buildAndFetch( array( 'select' => 'status', 'from' => 'rc_status', 'where' => 'is_complete=1' ) );
			$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='post'" ) );
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'author_id'   => $row['poster_id'],
					'author_name' => $row['poster'] ? $row['poster'] : ($row['poster_email'] ? $row['poster_email'] : 'Guest'),
					'use_sig'     => 1,
					'use_emo'     => ($row['hide_smilies'] == 1) ? 0 : 1,
					'ip_address'  => $row['poster_ip'],
					'post_date'   => $row['posted'],
					'post'		  => $this->fixPostData($row['message']),
					'queued'      => 0,
					'topic_id'    => $row['topic_id'],
					);
				
				if ( $row['edited'] && $row['edited_by'] )
				{
					$save['append_edit'] = 1;
					$save['edit_time']   = $row['edited'];
					$save['edit_name']   = $row['edited_by'];
				}
				
				$this->lib->convertPost($row['id'], $save);
				
				//-----------------------------------------
				// Report Center
				//-----------------------------------------
				
				$link = $this->lib->getLink($row['topic_id'], 'topics');
				if(!$link)
				{
					continue;
				}
				
				$forum = $this->DB->buildAndFetch( array( 'select' => 'forum_id, title', 'from' => 'topics', 'where' => 'tid='.$link ) );
							
				$rs = array(	'select' 	=> '*',
								'from' 		=> 'reports',
								'order'		=> 'id ASC',
								'where'		=> 'post_id='.$row['id']								
							);
				
				ipsRegistry::DB('hb')->build($rs);
				ipsRegistry::DB('hb')->execute();
				
				while ($rget = ipsRegistry::DB('hb')->fetch())
				{
					$report = array(
						'id'			=> $rget['id'],
						'title'			=> "Reported post #{$row['id']}",
						'status'		=> $rget['zapped_by'] ? $complete['status'] : $new['status'],
						'rc_class'		=> $rc['com_id'],
						'updated_by'	=> $rget['zapped_by'],
						'date_updated'	=> $rget['zapped'],
						'date_created'	=> $rget['created'],
						'exdat1'		=> $forum['forum_id'],
						'exdat2'		=> $link,
						'exdat3'		=> $this->lib->getLink($row['id'], 'posts'),
						'num_reports'	=> '1',
						'num_comments'	=> '0',
						'seoname'		=> IPSText::makeSeoTitle( $forum['title'] ),
						);
						
					$reports = array(
						array(
								'id'			=> $rget['id'],
								'report'		=> $rget['message'],
								'report_by'		=> $rget['reported_by'],
								'date_reported'	=> $rget['created']
							)						
						);
					
					$this->lib->convertReport('post', $report, $reports, false);
				}
			}
			
			$this->lib->next();
		}
		
		/**
		 * Convert Ranks
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_ranks()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------
			
			$this->lib->saveMoreInfo('ranks', array('rank_opt'));
			
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'ranks',
							'order'		=> 'id ASC',
						);
						
			$loop = $this->lib->load('ranks', $main);
			
			//-----------------------------------------
			// We need to know what do do with duplicates
			//-----------------------------------------
			
			$this->lib->getMoreInfo('ranks', $loop, array('rank_opt' => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate ranks?')));
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'posts'	=> $row['min_posts'],
					'title'	=> $row['rank'],
					);
				$this->lib->convertRank($row['id'], $save, $us['rank_opt']);			
			}
			
			$this->lib->next();
		}
		
		/**
		 * Convert Bad Words
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_badwords()
		{
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'censoring',
							'order'		=> 'id ASC',
						);
			
			$loop = $this->lib->load('badwords', $main);
						
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$exact = '1';
				if ( strpos( $row['search_for'], '*' ) !== FALSE )
				{
					$row['search_for'] = str_replace( '*', '', $row['search_for'] );
					$exact = '0';
				}
			
				$save = array(
					'type'		=> $row['search_for'],
					'swop'		=> $row['replace_with'],
					'm_exact'	=> $exact,
					);
				$this->lib->convertBadword($row['id'], $save);			
			}
			
			$this->lib->next();
		}
	
		/**
		 * Convert Ban Filters
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_banfilters()
		{
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(		'select' 	=> '*',
								'from' 		=> 'bans',
								'order'		=> 'id ASC',
							);
			
			$loop = $this->lib->load('banfilters', $main);
						
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				if ($row['username'])
				{
					$save = array(
						'ban_type'		=> 'name',
						'ban_content'	=> $row['username'],
						'ban_date'		=> time(),
						);
					$this->lib->convertBan('u'.$row['id'], $save);
				}
				
				if ($row['ip'])
				{
					$save = array(
						'ban_type'		=> 'ip',
						'ban_content'	=> $row['ip'],
						'ban_date'		=> time(),
						);
					$this->lib->convertBan('i'.$row['id'], $save);
				}
				
				if ($row['email'])
				{
					$save = array(
						'ban_type'		=> 'email',
						'ban_content'	=> $row['email'],
						'ban_date'		=> time(),
						);
					$this->lib->convertBan('e'.$row['id'], $save);
				}
			}
			
			$this->lib->next();
		}
	}