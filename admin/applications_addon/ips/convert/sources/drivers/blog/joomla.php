<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * Joomla
 * Last Update: $Date: 2011-04-01 13:22:34 -0400 (Fri, 01 Apr 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 517 $
 */

	$info = array(
		'key'	=> 'joomla',
		'name'	=> 'Joomla 1.5',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'joomla', 'newdb' => false),
		));

	class admin_convert_blog_joomla extends ipsCommand
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
				'blog_blogs'	=> array('members'),
				'blog_entries'		=> array('members'),
				'blog_comments'		=> array('blog_entries', 'members')
				);

			//-----------------------------------------
			// Load our libraries
			//-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_blog.php' );
			$this->lib =  new lib_blog( $registry, $html, $this );

	    $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( $info['name'] + ' &rarr; IP.Blog Converter' );

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
				case 'blog_blogs':
					$count = @ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'COUNT(DISTINCT user_id) as count', 'from' => 'blog_postings' ) );
					return $count['count'];

					break;

				case 'blog_entries':
					return $this->lib->countRows('blog_postings');
					break;

				case 'blog_comments':
					return $this->lib->countRows('blog_comment');
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
//				case 'blog_entries':
//					return true;
//					break;

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
			// Sort out newlines
			$post = nl2br($post);

			// Sort out the list tags
			$post = str_replace('<ul>', '[list]', $post);
			$post = str_replace('<ol>', '[list=1]', $post);
			$post = str_replace('<li>', '[*]', $post);
			$post = str_replace('</li>', '', $post);
			$post = str_replace( array( '</ul>', '</ol>' ), '[/list]', $post);

			// Sort out everything else..
			$post = preg_replace("#\<strong>(.*)\<\/strong>#i", "[b]$1[/b]", $post);
			$post = preg_replace("#\<em>(.*)\<\/em>#i", "[i]$1[/i]", $post);
			$post = preg_replace("#\<a href=(.+)>(.*)\<\/a>#i", "[url=$1]$2[/url]", $post);
			$post = preg_replace("#\<blockquote>(.*)\<\/blockquote>#i", "[quote]$1[/quote]", $post);
			$post = preg_replace('#\<del datetime="(.+)">(.*)\<\/del>#i', "[s]$2[/s]", $post);
			$post = preg_replace('#\<ins datetime="(.+)">(.*)\<\/ins>#i', "[u]$2[/u]", $post);
			$post = preg_replace('#\<img src=[\'"](.+)[\'"] alt=[\'"](.*)[\'"] \/>#i', "[img]$1[/img]", $post);
			$post = preg_replace("#\<code>(.*)\<\/code>#i", "[code]$1[/code]", $post);

			return $post;
		}

		/**
		 * Convert Blogs
		 *
		 * @access	private
		 * @return 	void
		 **/
		private function convert_blog_blogs()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array( 'select' => 'DISTINCT p.user_id',
                           'from' 		=> array( 'blog_postings' => 'p' ),
                           'add_join'	=> array( array( 'select'	=> 'u.name',
														 'from'		=> array( 'users' => 'u' ),
														 'where'	=> 'u.id = p.user_id' ) ),
                            'order'		=> 'p.id asc' );

			$loop = $this->lib->load('blog_blogs', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Handle permissions
				//-----------------------------------------

				$private = 0;
				$guests = 1;

				$perms = array();
				$perms['view']				= '*';
				$perms['owner_only']		= 0;
				$perms['authorized_users']	= '';

				//-----------------------------------------
				// Do the blog
				//-----------------------------------------

				$save = array(
					'member_id'			=> $row['user_id'],
					'blog_name'			=> "{$row['name']}'s Blog",
					'blog_desc'			=> '',
					'blog_type'			=> 'local',
					'blog_private'		=> 0,
					'blog_allowguests'	=> 1,
					'blog_rating_total'	=> 0,
					'blog_rating_count'	=> 0,
					'blog_settings'		=> 'a:11:{s:8:"viewmode";s:4:"list";s:8:"allowrss";s:1:"1";s:14:"allowtrackback";s:1:"1";s:13:"trackcomments";i:0;s:14:"entriesperpage";s:2:"10";s:15:"commentsperpage";s:2:"20";s:18:"allowguestcomments";i:1;s:13:"defaultstatus";s:5:"draft";s:9:"eopt_mode";s:8:"autohide";s:9:"hidedraft";i:0;s:14:"blockslocation";s:5:"right";}',
					);

				$this->lib->convertBlog($row['user_id'], $save, $perms);
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

			$main = array( 'select' 	=> 'p.*',
							'from' 		=> array( 'blog_postings' => 'p' ),
							'add_join'	=> array( array( 'select'	=> 'u.name',
														 'from'		=> array( 'users' => 'u' ),
														 'where'	=> 'u.id = p.user_id' ) ),
                            'order'		=> 'p.id asc' );

			$loop = $this->lib->load('blog_entries', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Carry on
				//-----------------------------------------

				$save = array(
					'blog_id'					=> $row['user_id'],
					'entry_author_id'			=> $row['user_id'],
					'entry_author_name'			=> $row['name'],
					'entry_date'				=> strtotime($row['post_date']),
					'entry_name'				=> $row['post_title'],
					'entry'						=> $this->fixPostData($row['post_desc']),
					'entry_status'				=> ($row['published'] == 1) ? 'published' : 'draft',
					'entry_num_comments'		=> $row['post_hits'],
					'entry_last_comment_date'	=> 0,
					'entry_last_comment_name'	=> '',
					'entry_queued_comments'		=> 0,
					'entry_has_attach'			=> 0,
					'entry_edit_time'			=> '', //intval($log['TS_post_modified_gmt']),
					'entry_edit_name'			=> '',
					'entry_use_emo'				=> 1,
					'entry_trackbacks'			=> 0,
					'entry_last_update'			=> strtotime($row['post_date']),
                    'entry_category'            => ',0,'
					);
                //print "<PRE>";print_r($save);print $this->lib->getLink($save['blog_id'], 'blog_blogs');exit;
				$this->lib->convertEntry($row['id'], $save);
			}
			// Next, please!
			$this->lib->next();
		}

		/**
		 * Convert Comments
		 *
		 * @access	private
		 * @return 	void
		 **/
		private function convert_blog_comments()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array( 'select' 	=> 'b.*',
							'from' 		=> array( 'blog_comment' => 'b' ),
							'add_join'	=> array( array( 'select'	=> 'u.name',
														 'from'		=> array( 'users' => 'u' ),
														 'where'	=> 'u.id = b.user_id' ) ),
                            'order'		=> 'b.id asc' );

            $loop = $this->lib->load('blog_comments', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Carry on
				//-----------------------------------------

				$save = array(
					'entry_id'			=> $row['id'],
					'member_id'			=> $row['user_id'],
					'member_name'		=> $row['name'],
					'ip_address'		=> $row['comment_ip'],
					'comment_date'		=> strtotime($row['comment_date']),
					'comment_use_emo'	=> 1,
					'comment_queued'	=> $row['published'] == 1 ? 0 : 1,
					'comment_edit_time'	=> strtotime($row['comment_update']),
					'comment_edit_name'	=> '',
					'comment_text'		=> $this->fixPostData($row['comment_desc']),
					);

				$this->lib->convertComment($row['id'], $save);
			}
			$this->lib->next();
		}
	}