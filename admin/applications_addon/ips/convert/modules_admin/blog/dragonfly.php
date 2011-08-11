<?php
/**
 * IPS Converters
 * IP.Blog 2.0 Converters
 * IP.Blog Merge Tool
 * Last Update: $Date: 2009-11-16 18:18:17 +0100(lun, 16 nov 2009) $
 * Last Updated By: $Author: mark $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 384 $
 */

	$info = array(
		'key'	=> 'dragonfly',
		'name'	=> 'Dragonfly CMS 9.2',
		'login'	=> false,
	);
	
	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'dragonfly', 'newdb' => false),
		));
	
	class admin_convert_blog_dragonfly extends ipsCommand
	{
		const BLOG_ID = 1;
	
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
				'blog_categories'	=> array(),
				'blog_entries'		=> array('members'),
				'blog_comments'		=> array('members'),
				);
							
			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_blog.php' );
			$this->lib =  new lib_blog( $registry, $html, $this, FALSE );
	
	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'Dragonfly &rarr; IP.Blog Converter' );
	
			//-----------------------------------------
			// Are we connected?
			// (in the great circle of life...)
			//-----------------------------------------
			
			$this->HB = $this->lib->connect();
			
			if ( ! self::BLOG_ID )
			{
				$this->registry->output->showError( "You must enter a Blog ID into the converter file" );
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
				case 'blog_categories':
					return $this->lib->countRows('topics');
					break;
					
				case 'blog_entries':
					return $this->lib->countRows('stories');
					break;
					
				case 'blog_comments':
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
		 * Convert Blog Categories
		 *
		 * @access	private
		 * @return 	void
		 **/
		private function convert_blog_categories()
		{
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'topics',
							'order'		=> 'topicid ASC',
						);
			
			$loop = $this->lib->load('blog_categories', $main);
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'category_blog_id'	=> self::BLOG_ID,
					'category_title'	=> $row['topictext']
					);
			
				$this->lib->convertCategory( $row['topicid'], $save, TRUE );			
			}
			
			$this->lib->next();
		}
		
		/**
		 * Convert Entries
		 *
		 * @access	private
		 * @return 	void
		 **/
		private function convert_blog_entries()
		{
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'stories',
							'order'		=> 'sid ASC',
						);
			
			$loop = $this->lib->load('blog_entries', $main);
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$author = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'user_id', 'from' => 'users', 'where' => "username='{$row['informant']}'" ) );
			
				$save = array(
					'blog_id'			=> self::BLOG_ID,
					'entry_author_id'	=> $author['user_id'],
					'entry_author_name'	=> $row['informant'],
					'entry_date'		=> $row['time'],
					'entry_name'		=> $row['title'],
					'entry'				=> $row['hometext'] . '<br /><br />' . $row['bodytext'],
					'entry_status'		=> 'published',
					'entry_locked'		=> $row['acomm'] ? 0 : 1,
					'entry_num_comments'=> $row['comments'],
					'entry_category'	=> $row['topic'],
					'entry_rating_total'=> $row['score'],
					'entry_rating_count'=> $row['ratings'],
					);
				
				$this->lib->convertEntry( $row['sid'], $save, TRUE );
			}
			
			$this->lib->next();
		}
		
		/**
		 * Convert Comments
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_blog_comments()
		{
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*',
							'from' 		=> 'comments',
							'order'		=> 'tid ASC',
						);
			
			$loop = $this->lib->load('blog_comments', $main);
						
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$author = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'user_id', 'from' => 'users', 'where' => "username='{$row['name']}'" ) );
			
				$save = array(
					'entry_id'		=> $row['sid'],
					'member_id'		=> $author['user_id'],
					'member_name'	=> $row['name'],
					'comment_date'	=> $row['date'],
					'comment_text'	=> $row['comment'],
					);
				
				$this->lib->convertComment( $row['tid'], $save );
			}
			
			$this->lib->next();
		}
		
	}
	