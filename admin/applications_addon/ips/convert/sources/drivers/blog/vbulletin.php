<?php
/**
 * IPS Converters
 * IP.Blog 2.0 Converters
 * vBulletin
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
		'key'	=> 'vbulletin',
		'name'	=> 'vBulletin 4.0',
		'login'	=> false,
	);

				$parent = array('required' => true, 'choices' => array(
					array('app' => 'board', 'key' => 'vbulletin', 'newdb' => false),
					));

	class admin_convert_blog_vbulletin extends ipsCommand
	{

		private $attachmentContentTypes = array();

		/**
		 * Bitwise settings - Mod permissions
		 *
		 * @access	private
		 * @var 	array
		 **/
		private $MOD_PERM = array( 'can_edit_entries'		=> 1,
								   'can_delete_entries'		=> 2,
								   'can_remove_entries'		=> 4,
								   'can_mod_entries'		=> 8,
								   'can_edit_comments'		=> 16,
								   'can_delete_comments'	=> 32,
								   'can_remove_comments'	=> 64,
								   'can_mod_comments'		=> 128,
								   'can_view_ips'			=> 256,
								   'can_edit_blocks'		=> 512,
								   'can_edit_cats'			=> 1024 );

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
				'blog_blogs'	=> array('members', 'forum_perms'),
				'blog_entries'	=> array('blog_blogs', 'members'),
				'blog_attachments' => array('attachments_type', 'blog_entries'),
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
			$this->lib->sendHeader( 'vBulletin &rarr; IP.Blog Converter' );

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
					return $this->lib->countRows('blog_user');
					break;

				case 'blog_entries':
					return $this->lib->countRows('blog');
					break;

				case 'blog_comments':
					return $this->lib->countRows('blog_text')-$this->lib->countRows('blog');
					break;

				case 'blog_moderators':
					return $this->lib->countRows('blog_moderator');
					break;

				case 'blog_attachments':
					$contenttype = ipsRegistry::DB ( 'hb' )->buildAndFetch ( array (
						'select'	=> 'contenttypeid',
						'from'		=> 'contenttype',
						'where'		=> 'class = \'BlogEntry\''
					) );
					return $this->lib->countRows('attachment', 'contenttypeid = ' . $contenttype['contenttypeid']);
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
			// Sort out newlines
			$post = nl2br($post);

			// And quote tags
			$post = preg_replace("#\[quote=(.+);\d\]#i", "[quote name='$1']", $post);
			$post = preg_replace("#\[quote=(.+)\](.+)\[/quote\]#i", "[quote name='$1']$2[/quote]", $post);

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

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_user',
							'order'		=> 'bloguserid ASC',
						);

			$loop = $this->lib->load('blog_blogs', $main, array('blog_tracker'));

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

				$private = 0;
				if ($row['options_member'] == 0 or $row['options_member'] == 2)
				{
					$private = 1;
				}
				$guests = 1;
				if ($row['options_guest'] == 0 or $row['options_guest'] == 2)
				{
					$guests = 0;
				}

				$perms = array();
				$perms['view']				= '*';
				$perms['owner_only']		= $private;
				$perms['authorized_users']	= '';

				//-----------------------------------------
				// Do the blog
				//-----------------------------------------

				$save = array(
					'member_id'			=> $row['bloguserid'],
					'blog_name'			=> ($row['title'] == '') ? "Blog {$row['bloguserid']}" : $row['title'],
					'blog_desc'			=> $row['description'],
					'blog_type'			=> 'local',
					'blog_private'		=> $private,
					'blog_allowguests'	=> $guests,
					'blog_rating_total'	=> $row['ratingtotal'],
					'blog_rating_count'	=> $row['ratingnum'],
					'blog_settings'		=> 'a:11:{s:8:"viewmode";s:4:"list";s:8:"allowrss";s:1:"1";s:14:"allowtrackback";s:1:"1";s:13:"trackcomments";i:0;s:14:"entriesperpage";s:2:"10";s:15:"commentsperpage";s:2:"20";s:18:"allowguestcomments";i:1;s:13:"defaultstatus";s:5:"draft";s:9:"eopt_mode";s:8:"autohide";s:9:"hidedraft";i:0;s:14:"blockslocation";s:5:"right";}',
					);

				$this->lib->convertBlog($row['bloguserid'], $save, $perms);

				//-----------------------------------------
				// Subscriptions?
				//-----------------------------------------

				$sub = array(	'select' 	=> '*',
								'from' 		=> 'blog_subscribeuser',
								'order'		=> 'blogsubscribeuserid ASC',
								'where'		=> 'bloguserid='.$row['bloguserid']
							);

				ipsRegistry::DB('hb')->build($sub);
				ipsRegistry::DB('hb')->execute();
				while ($tracker = ipsRegistry::DB('hb')->fetch())
				{
					$savetracker = array(
						'blog_id'	=> $row['bloguserid'],
						'member_id'	=> $row['userid'],
						);
					$this->lib->convertTracker($tracker['blogsubscribeuserid'], $savetracker);
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

			$main = array(	'select' 	=> 'b.*',
							'from' 		=> array('blog' => 'b'),
							'order'		=> 'b.blogid ASC',
							'add_join'	=> array(
											array( 	'select' => 't.*',
													'from'   =>	array( 'blog_text' => 't' ),
													'where'  => "b.firstblogtextid=t.blogtextid",
													'type'   => 'left'
												),
											),
						);

			$loop = $this->lib->load('blog_entries', $main);

			//-----------------------------------------
			// We need to log text ids
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			if (!$this->request['st'])
			{
				$us['blog_text_ids'] = array();
				IPSLib::updateSettings(array('conv_extra' => serialize($us)));
			}

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Odd issue...
				if ( !isset ( $row['blogtextid'] ) || empty ( $row['blogtextid'] ) )
				{
					continue;
				}

				//-----------------------------------------
				// Has this been editted?
				//-----------------------------------------

				$log = false;
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'blog_editlog', 'where' => "blogtextid={$row['blogtextid']}", 'order' => 'dateline ASC'));
				ipsRegistry::DB('hb')->execute();
				while ($editlog = ipsRegistry::DB('hb')->fetch())
				{
					$log = $editlog;
				}

				//-----------------------------------------
				// Carry on
				//-----------------------------------------

				$save = array(
					'blog_id'					=> $row['bloguserid'],
					'entry_author_id'			=> $row['userid'],
					'entry_author_name'			=> $row['username'],
					'entry_date'				=> $row['dateline'],
					'entry_name'				=> $row['title'],
					'entry'						=> $this->fixPostData($row['pagetext']),
					'entry_status'				=> ($row['state'] == 'visible') ? 'published' : 'draft',
					'entry_num_comments'		=> $row['comments_visible'],
					'entry_last_comment_date'	=> $row['lastcomment'],
					'entry_last_comment_name'	=> $row['lastcommenter'],
					'entry_queued_comments'		=> $row['comments_moderation'],
					'entry_has_attach'			=> $row['attach'],
					'entry_edit_time'			=> ($log) ? $log['dateline'] : '',
					'entry_edit_name'			=> ($log) ? $log['username'] : '',
					'entry_use_emo'				=> $row['allowsmilie'],
					'entry_trackbacks'			=> $row['trackback_visible'],
					'entry_last_update'			=> ($log) ? $log['dateline'] : $row['dateline'],
					);
				$this->lib->convertEntry($row['blogid'], $save);

				//-----------------------------------------
				// Save the entry ID so it's not confused with the comments
				//-----------------------------------------

				$us['blog_text_ids'][] = $row['blogtextid'];

			}

			// Save entry ids
			$get[$this->lib->app['name']] = $us;
			IPSLib::updateSettings(array('conv_extra' => serialize($get)));

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

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_text',
							'order'		=> 'blogtextid ASC',
						);

			$loop = $this->lib->load('blog_comments', $main);

			//-----------------------------------------
			// We need to log text ids
			//-----------------------------------------

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Don't confuse entries with comments
				// I know... trust Jelsoft to make this complicated
				//-----------------------------------------

				if (in_array($row['blogtextid'], $us['blog_text_ids']))
				{
					continue;
				}

				//-----------------------------------------
				// Has this been editted?
				//-----------------------------------------

				$log = false;
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'blog_editlog', 'where' => "blogtextid={$row['blogtextid']}", 'order' => 'dateline ASC'));
				ipsRegistry::DB('hb')->execute();
				while ($editlog = ipsRegistry::DB('hb')->fetch())
				{
					$log = $editlog;
				}

				//-----------------------------------------
				// Carry on
				//-----------------------------------------

				$save = array(
					'entry_id'			=> $row['blogid'],
					'member_id'			=> $row['userid'],
					'member_name'		=> $row['username'],
					'ip_address'		=> $row['ipaddress'],
					'comment_date'		=> $row['dateline'],
					'comment_use_emo'	=> $row['allowsmilie'],
					'comment_edit_time'	=> $log['dateline'],
					'comment_edit_name'	=> $log['username'],
					'comment_text'		=> $this->fixPostData($row['pagetext']),
					);

				$this->lib->convertComment($row['blogtextid'], $save);
			}

			$this->lib->next();
		}

		/**
		 * Convert Moderators
		 *
		 * @access	private
		 * @return 	void
		 **/
		private function convert_blog_moderators()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_moderator',
							'order'		=> 'blogmoderatorid ASC',
						);

			$loop = $this->lib->load('blog_moderators', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Silly bitwise permissions
				$perms = array();
				foreach( $this->MOD_PERM as $name => $bit ) {
					$perms[ $name ] = ( $row['permissions'] & $bit ) ? 1 : 0;
				}

				// Carry on
				$save = array(
					'moderate_type'					=> 'member',
					'moderate_mg_id'				=> $row['userid'],
					'moderate_can_edit_comments'	=> $perms['can_edit_comments'],
					'moderate_can_edit_entries'		=> $perms['can_edit_entries'],
					'moderate_can_del_comments'		=> $perms['can_delete_comments'],
					'moderate_can_del_entries'		=> $perms['can_delete_entries'],
					'moderate_can_lock'				=> $perms['can_mod_entries'],
					'moderate_can_publish'			=> $perms['can_mod_entries'],
					'moderate_can_approve'			=> $perms['can_mod_entries'],
					'moderate_can_editcblocks'		=> $perms['can_edit_blocks'],
					'moderate_can_del_trackback'	=> $perms['can_mod_entries'],
					'moderate_can_view_draft'		=> $perms['can_mod_entries'],
					'moderate_can_pin'				=> $perms['can_mod_entries'],
					);
				$this->lib->convertModerator($row['blogmoderatorid'], $save);
			}

			$this->lib->next();
		}

		/**
		 * Convert Trackbacks
		 *
		 * @access	private
		 * @return 	void
		 **/
		private function convert_blog_trackback()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'blog_trackback',
							'order'		=> 'blogtrackbackid ASC',
						);

			$loop = $this->lib->load('blog_trackback', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'blog_id'				=> $row['userid'],
					'entry_id'				=> $row['blogid'],
					'trackback_url'			=> $row['url'],
					'trackback_title'		=> $row['title'],
					'trackback_excerpt'		=> $row['snippet'],
					'trackback_blog_name'	=> $row['title'],
					'trackback_date'		=> $row['dateline'],
					'trackback_queued'		=> ($row['state'] == 'moderation') ? 1 : 0,
					);
				$this->lib->convertTrackback($row['blogtrackbackid'], $save);
			}

			$this->lib->next();
		}

		/**
		 * Convert Attachments
		 *
		 * @access	private
		 * @return 	void
		 **/
		private function convert_blog_attachments()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('attachments', array('attach_path'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'attachment',
							'order'		=> 'attachmentid ASC',
						);

			$loop = $this->lib->load('blog_attachments', $main);

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
				//-----------------------------------------
				// Init
				//-----------------------------------------

				$filedata = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'filedata', 'where' => "filedataid={$row['filedataid']}" ) );
				if ( array_key_exists( $row['contenttypeid'], $this->attachmentContentTypes ) )
				{
					$contenttype = $this->attachmentContentTypes[ $row['contenttypeid'] ];
				}
				else
				{
					$contenttype = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'contenttype', 'where' => "contenttypeid={$row['contenttypeid']}" ) );
					$this->attachmentContentTypes[ $row['contenttypeid'] ] = $contenttype;
				}

				if ( $contenttype['class'] != 'BlogEntry' )
				{
					continue;
				}

				// What's the mimetype?
				$type = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'attachments_type', 'where' => "atype_extension='{$filedata['extension']}'" ) );

				// Is this an image?
				$image = false;
				if (preg_match('/image/', $type['atype_mimetype']))
				{
					$image = true;
				}

				$save = array(
					'attach_ext'			=> $filedata['extension'],
					'attach_file'			=> $row['filename'],
					'attach_is_image'		=> $image,
					'attach_hits'			=> $row['counter'],
					'attach_date'			=> $row['dateline'],
					'attach_member_id'		=> $row['userid'],
					'attach_filesize'		=> $filedata['filesize'],
					'attach_rel_id'			=> $row['contentid'],
					'attach_rel_module'		=> 'blogentry',
					);

				//-----------------------------------------
				// Database
				//-----------------------------------------

				if ($filedata['filedata'])
				{
					$save['attach_location'] = $row['filename'];
					$save['data'] = $filedata['filedata'];

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
					$save['attach_location'] .= "/{$row['attachmentid']}.attach";

					$done = $this->lib->convertAttachment($row['attachmentid'], $save, $path);
				}

				//-----------------------------------------
				// Fix inline attachments
				//-----------------------------------------

				if ($done === true)
				{
					$aid = $this->lib->getLink($row['attachmentid'], 'attachments');
					$pid = $this->lib->getLink($save['attach_rel_id'], 'blog_entries');

					$attachrow = $this->DB->buildAndFetch( array( 'select' => 'entry', 'from' => 'blog_entries', 'where' => "entry_id={$pid}" ) );

					$rawaid = $row['attachmentid'];
					$update = preg_replace("/\[ATTACH\]".$rawaid."\[\/ATTACH\]/i", "[attachment={$aid}:{$save['attach_location']}]", $attachrow['post']);

					$this->DB->update('blog_entries', array('entry' => $update), "entry_id={$pid}");
				}
			}

			$this->lib->next();

		}

	}

