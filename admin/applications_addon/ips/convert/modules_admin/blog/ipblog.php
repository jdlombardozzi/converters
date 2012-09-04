<?php
/**
 * IPS Converters
 * IP.Blog 2.0 Converters
 * IP.Blog Merge Tool
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
		'key'	=> 'ipblog',
		'name'	=> 'IP.Blog 2.0',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'ipboard', 'newdb' => false),
		));

	class admin_convert_blog_ipblog extends ipsCommand
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
				'blog_bookmarks' => array(),
				'blog_pingservices' => array(),
				'blog_themes'	=> array(),
				'blog_headers'	=> array(),
				'blog_blogs'	=> array('members', 'blog_themes', 'blog_headers', 'forum_perms'),
				'blog_entries'	=> array('blog_blogs', 'members'),
				'blog_attachments' => array('attachments_type', 'blog_entries'),
				'blog_polls'	=> array('blog_entries', 'members'),
				'blog_comments'	=> array('blog_entries', 'members'),
				'blog_moderators' => array('members', 'groups'),
				'blog_trackback' => array('blog_blogs', 'blog_entries')
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_blog.php' );
			$this->lib =  new lib_blog( $registry, $html, $this );

	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'IP.Blog Merge Tool' );

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
				case 'blog_attachments':
					return $this->lib->countRows('attachments', "attach_rel_module='blogentry'");
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
				case 'blog_headers':
				case 'blog_attachments':
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
		 * Convert Blog Themes
		 *
		 * @access	private
		 * @return 	void
		 **/
		private function convert_blog_themes()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_themes',
							'order'		=> 'theme_id ASC',
						);

			$loop = $this->lib->load('blog_themes', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertTheme($row['theme_id'], $row);
			}

			$this->lib->next();
		}

		/**
		 * Convert Blog Headers
		 *
		 * @access	private
		 * @return 	void
		 **/
		private function convert_blog_headers()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('blog_headers', array('header_path'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_headers',
							'order'		=> 'header_id ASC',
						);

			$loop = $this->lib->load('blog_headers', $main);

			//-----------------------------------------
			// We need to know how to the path
			//-----------------------------------------

			$this->lib->getMoreInfo('blog_headers', $loop, array('header_path' => array('type' => 'text', 'label' => 'Path to header folder (no trailing slash - usually /pathtoyourforums/blog/headers): ')), 'path');

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Move that font
				//-----------------------------------------

				$opts = unserialize($row['header_opts']);
				preg_match("/(.+)\/(.+)\.(.+)/", $opts['font'], $matches);

				$filename = $matches[2].'.'.$matches[3];

				if (file_exists(DOC_IPS_ROOT_PATH.'blog/fonts/'.$filename))
				{
					$opts['font'] = DOC_IPS_ROOT_PATH.'blog/fonts/'.$filename;
				}
				else
				{
					while (file_exists($this->settings['upload_dir'].'/'.$filename))
					{
						$filename = '_'.$filename;
					}
					$this->lib->moveFiles(array($filename), $matches[1], $this->settings['upload_dir']);
					$opts['font'] = $this->settings['upload_dir'].'/'.$filename;
				}

				//-----------------------------------------
				// And carry on...
				//-----------------------------------------

				$this->lib->convertHeader($row['header_id'], $row, $us['header_path']);
			}

			$this->lib->next();
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

			$main = array(	'select' 	=> 'b.*',
							'from' 		=> array('blog_blogs' => 'b'),
							'order'		=> 'blog_id ASC',
							'add_join'	=> array(
											array( 	'select' => 'p.*',
													'from'   =>	array( 'permission_index' => 'p' ),
													'where'  => "p.perm_type='blog' AND p.perm_type_id=b.blog_id",
													'type'   => 'left'
												),
											),
						);

			$loop = $this->lib->load('blog_blogs', $main, array('blog_cblocks', 'blog_custom_cblocks', 'blog_ratings', 'blog_tracker'));

			//-----------------------------------------
			// Prepare for permissions conversion
			//-----------------------------------------

			$this->lib->preparePermissions('blog');

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Handle permissions
				//-----------------------------------------

				$perms = array();
				$perms['view']				= $row['perm_view'];
				$perms['owner_only']		= $row['owner_only'];
				$perms['authorized_users']	= $row['authorized_users'];

				//-----------------------------------------
				// Do the blog
				//-----------------------------------------

				$this->lib->convertBlog($row['blog_id'], $row, $perms);

				//-----------------------------------------
				// And content blocks
				//-----------------------------------------

				$cb = array(	'select' 	=> '*',
								'from' 		=> 'blog_cblocks',
								'order'		=> 'cblock_id ASC',
								'where'		=> 'blog_id='.$row['blog_id']
							);

				ipsRegistry::DB('hb')->build($cb);
				ipsRegistry::DB('hb')->execute();
				while ($block = ipsRegistry::DB('hb')->fetch())
				{
					if ($block['cblock_type'] == 'custom')
					{
						$custom = ipsRegistry::DB('hb')->buildAndFetch(array('select' => '*', 'from' => 'blog_custom_cblocks', 'where' => 'cbcus_id='.$block['cblock_ref_id']));
						$this->lib->convertCustomCBlock($custom['cbcus_id'], $custom);
					}
					elseif (!$this->lib->getLink($block['cblock_ref_id'], 'blog_default_cblocks'))
					{
						$default = ipsRegistry::DB('hb')->buildAndFetch(array('select' => '*', 'from' => 'blog_default_cblocks', 'where' => 'cbdef_id='.$block['cblock_ref_id']));
						$this->lib->convertDefaultCBlock($default['cbdef_id'], $default);
					}
					$this->lib->convertCBlock($block['cblock_id'], $block);
				}

				//-----------------------------------------
				// Ratings?
				//-----------------------------------------

				$rat = array(	'select' 	=> '*',
								'from' 		=> 'blog_ratings',
								'order'		=> 'rating_id ASC',
								'where'		=> 'blog_id='.$row['blog_id']
							);

				ipsRegistry::DB('hb')->build($rat);
				ipsRegistry::DB('hb')->execute();
				while ($rating = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertRating($rating['rating_id'], $rating);
				}

				//-----------------------------------------
				// Subscriptions?
				//-----------------------------------------

				$sub = array(	'select' 	=> '*',
								'from' 		=> 'blog_tacker',
								'order'		=> 'tracker_id ASC',
								'where'		=> 'blog_id='.$row['blog_id']
							);

				ipsRegistry::DB('hb')->build($sub);
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertTracker($tracker['tracker_id'], $tracker);
				}

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
							'from' 		=> 'blog_entries',
							'order'		=> 'entry_id ASC',
						);

			$loop = $this->lib->load('blog_entries', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$row['entry'] = $this->fixPostData($row['entry']);
				$this->lib->convertEntry($row['entry_id'], $row);
			}

			$this->lib->next();
		}

		/**
		 * Convert Polls
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_blog_polls()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_polls',
							'order'		=> 'poll_id ASC',
						);

			$loop = $this->lib->load('blog_polls', $main, array('blog_voters'));

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// We need to do voters...
				//-----------------------------------------

				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'blog_voters', 'where' => "entry_id={$row['entry_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($voter = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertPollVoter($voter['vote_id'], $voter);
				}

				//-----------------------------------------
				// Then we can do the actual poll
				//-----------------------------------------

				$this->lib->convertPoll($row['poll_id'], $row);
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
							'from' 		=> 'blog_comments',
							'order'		=> 'comment_id ASC',
						);

			$loop = $this->lib->load('blog_comments', $main);

			//-----------------------------------------
			// Prepare for reports conversion
			//-----------------------------------------

			$this->lib->prepareReports('blog');

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$row['comment_text'] = $this->fixPostData($row['comment_text']);
				$this->lib->convertComment($row['comment_id'], $row);

				//-----------------------------------------
				// Report Center
				//-----------------------------------------

				$rc = $this->DB->buildAndFetch( array( 'select' => 'com_id', 'from' => 'rc_classes', 'where' => "my_class='blog'" ) );
				$rs = array(	'select' 	=> '*',
								'from' 		=> 'rc_reports_index',
								'order'		=> 'id ASC',
								'where'		=> 'exdat3='.$row['comment_id']." AND rc_class='{$rc['com_id']}'"
							);

				ipsRegistry::DB('hb')->build($rs);
				ipsRegistry::DB('hb')->execute();
				while ($report = ipsRegistry::DB('hb')->fetch())
				{
					$rs = array(	'select' 	=> '*',
									'from' 		=> 'rc_reports',
									'order'		=> 'id ASC',
									'where'		=> 'rid='.$report['id']
								);

					ipsRegistry::DB('hb')->build($rs);
					ipsRegistry::DB('hb')->execute();
					$reports = array();
					while ($r = ipsRegistry::DB('hb')->fetch())
					{
						$reports[] = $r;
					}
					$this->lib->convertReport('blog', $report, $reports);
				}
			}

			$this->lib->next();
		}

		/**
		 * Convert Bookmarks
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_blog_bookmarks()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_bookmarks',
							'order'		=> 'bookmark_id ASC',
						);

			$loop = $this->lib->load('blog_bookmarks', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertBookmark($row['bookmark_id'], $row);
			}

			$this->lib->next();
		}

		/**
		 * Convert Moderators
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_blog_moderators()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_moderators',
							'order'		=> 'moderate_id ASC',
						);

			$loop = $this->lib->load('blog_moderators', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertModerator($row['moderate_id'], $row);
			}

			$this->lib->next();
		}

		/**
		 * Convert Ping Services
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_blog_pingservices()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_pingservices',
							'order'		=> 'blog_service_id ASC',
						);

			$loop = $this->lib->load('blog_pingservices', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertPingService($row['blog_service_id'], $row);
			}

			$this->lib->next();
		}

		/**
		 * Convert Trackbacks
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_blog_trackback()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_trackback',
							'order'		=> 'trackback_id ASC',
						);

			$loop = $this->lib->load('blog_trackback', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertTrackback($row['trackback_id'], $row);
			}

			$this->lib->next();
		}

		/**
		 * Convert Attachments
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_blog_attachments()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('blog_attachments', array('attach_path'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'attachments',
							'where'		=> "attach_rel_module='blogentry'",
							'order'		=> 'attach_id ASC',
						);

			$loop = $this->lib->load('blog_attachments', $main);

			//-----------------------------------------
			// We need to know the path
			//-----------------------------------------

			$this->lib->getMoreInfo('blog_attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to_ipblog/uploads):')), 'path');

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
				// Send em on
				$done = $this->lib->convertAttachment($row['attach_id'], $row, $path, false, true);

				// Fix inline attachments
				if ($done === true)
				{
					$aid = $this->lib->getLink($row['attach_id'], 'attachments');

					$pid = $this->lib->getLink($row['attach_rel_id'], 'blog_entries');
					$attachrow = $this->DB->buildAndFetch( array( 'select' => 'entry', 'from' => 'blog_entries', 'where' => "entry_id={$pid}" ) );
					$save = preg_replace("#(\[attachment=)({$row['attach_id']}+?)\:([^\]]+?)\]#ie", "'$1'. $aid .':$3]'", $attachrow['entry']);
					$this->DB->update('blog_entries', array('entry' => $save), "entry_id={$pid}");
				}

			}

			$this->lib->next();

		}

	}
	