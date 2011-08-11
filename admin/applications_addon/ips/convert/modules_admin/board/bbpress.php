<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * bbPress
 * Last Update: $Date: 2010-07-22 11:29:06 +0200(gio, 22 lug 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 447 $
 */

	$info = array(
		'key'	=> 'bbpress',
		'name'	=> 'bbPress 1.0',
		'login'	=> true,
	);
	
	class admin_convert_board_bbpress extends ipsCommand
	{
		/*
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
				'topics'		=> array('forums'),
				'posts'			=> array('topics')
				);
					
			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );
	
	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'bbPress &rarr; IP.Board Converter' );
	
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
					return $this->lib->countRows('wp_users');
					break;
					
				case 'forums':
				case 'topics':
				case 'posts':
					return $this->lib->countRows('bb_'.$action);
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

			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'users',
							'order'		=> 'ID ASC',
						);
						
			$loop = $this->lib->load('members', $main);
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$info = array(
					'id'				=> $row['ID'],
					'group'				=> $INFO['member_group'],
					'joined'			=> strtotime( $row['user_registered'] ),
					'username'			=> $row['user_login'],
					'displayname'		=> $row['display_name'],
					'email'				=> $row['user_email'],
					'password'			=> $row['user_pass'],
					);
					
				$this->lib->convertMember($info, array(), array(), array(), '');			
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
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'forums',
							'order'		=> 'forum_id ASC',
						);
									
			$loop = $this->lib->load('forums', $main);
									
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'name'			=> $row['forum_name'],
					'description'	=> $row['forum_desc'],
					'position'		=> $row['forum_order'],
					'posts'			=> $row['posts'],
					'topics'		=> $row['topics'],
					'parent_id'		=> ($row['forum_parent']) ? $row['forum_parent'] : -1,
					);
				
				$this->lib->convertForum($row['forum_id'], $save, array());				
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
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'topics',
							'order'		=> 'topic_id ASC',
						);
			
			$loop = $this->lib->load('topics', $main);
						
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'title'		 		=> $row['topic_title'],
					'posts'			 	=> $row['topic_posts'],
					'starter_name'	 	=> $row['topic_poster_name'],
					'starter_id'		=> $row['topic_poster'],
					'forum_id'		 	=> $row['forum_id'],
					'state'				=> $row['topic_open'] ? 'open' : 'closed',
					'approved'			=> 1,
					'start_date'		=> strtotime( $row['topic_start_time'] ),  
					'pinned'			=> $row['topic_sticky'],                   
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
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'posts',
							'order'		=> 'post_id ASC',
						);
			
			$loop = $this->lib->load('posts', $main);
						
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// We need to get some info
				$author = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'display_name', 'from' => 'users', 'where' => "ID='{$row['poster_id']}'" ) );

				$save = array(
					'author_name'	 	=> $author['display_name'],
					'author_id'			=> $row['poster_id'],
					'topic_id'			=> $row['topic_id'],
					'post'				=> $this->fixPostData( $row['post_text'] ),
					'post_date'			=> strtotime( $row['post_time'] ),
					'ip_address'		=> $row['poster_ip'],
					);
				
				$this->lib->convertPost($row['post_id'], $save);
				
			}

			$this->lib->next();
						
		}				
	}
	
