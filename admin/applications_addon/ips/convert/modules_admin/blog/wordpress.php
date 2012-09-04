<?php
/**
 * IPS Converters
 * WordPress 3.1 Converters
 * vBulletin
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
		'key'	=> 'wordpress',
		'name'	=> 'WordPress 3.1',
		'login'	=> false,
	);
	
	#$parent = array('required' => false);
	
	class admin_convert_blog_wordpress extends ipsCommand
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
				'members'			=> array(),
				'blog_entries'		=> array('members'),
				'blog_comments'		=> array('blog_entries', 'members'),
				'blog_trackback'	=> array('blog_comments')
				);
							
			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_blog.php' );
			$this->lib =  new lib_blog( $registry, $html, $this );
	
	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'WordPress &rarr; IP.Blog Converter' );
	
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
					return $this->lib->countRows('users');
					break;
					
				case 'blog_entries':
					return $this->lib->countRows('posts', "post_type='post'");
					break;
					
				case 'blog_comments':
					return $this->lib->countRows('comments', "comment_type != 'trackback'");
					break;
					
				case 'blog_trackback':
					return $this->lib->countRows('comments', "comment_type='trackback'");
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
				case 'blog_entries':
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
		 * Convert members
		 *
		 * @access	private
		 * @return	void
		 **/
		private function convert_members()
		{
			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------
			
			$pcpf = array(
				'description'	=> 'Interests',
				'aim'			=> 'AIM ID',
				'yim'			=> 'Yahoo ID',
				'jabber'		=> 'Jabber ID',
				'user_url'		=> 'Website'
				);
			
			$this->lib->saveMoreInfo('members', array_keys($pcpf));

			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select'	=> 'u.*, UNIX_TIMESTAMP( u.user_registered ) AS TS_user_registered',
							'from'		=> array( 'users' => 'u' ),
							'order'		=> 'u.ID ASC',
							'add_join'	=> array( array( 'select'	=> 'um.meta_value as user_level',
														 'from'		=> array( 'usermeta' => 'um' ),
														 'where'	=> "u.ID=um.user_id AND meta_key='wp_user_level'"
														) )
						  );
			
			$loop = $this->lib->load('members', $main);
			
			//-----------------------------------------
			// Tell me what you know!
			//-----------------------------------------
			
			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];
			$ask = array();
			
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

				// Basic info				
				$info = array(
					'id'				=> $row['ID'],
										// Admins in WP are level 10 so let's check > 9
					'group'				=> ($row['user_level'] > 9) ? $this->settings['admin_group'] : $this->settings['member_group'],
					'secondary_groups'	=> '',
					'joined'			=> $row['TS_user_registered'], // Let's use the timestamp and not the original field
					'username'			=> $row['user_login'],
					'displayname'		=> $row['display_name'],
					'email'				=> $row['user_email'],
					'password'			=> $row['user_pass'],
					);
				
				// Member info
				$members = array(
					'ip_address'		=> '0.0.0.0',
					'conv_password'		=> $row['user_pass'],
					'bday_day'			=> '',
					'bday_month'		=> '',
					'bday_year'			=> '',
					'last_visit'		=> 0,
					'last_activity' 	=> 0,
					'posts'				=> 0,
					'email_pm'      	=> 1,
					'members_disable_pm'=> 0,
					'hide_email' 		=> 1,
					'allow_admin_mails' => 1,
					);
				
				// Profile - nothing here
				
				//-----------------------------------------
				// Avatars
				//-----------------------------------------
				// Wordpress handles avatars with a plugin
				// so not a default feature, gravatar only
				//-----------------------------------------
				
				//-----------------------------------------
				// Profile fields
				//-----------------------------------------
				// We couldn't join this because there are
				// several rows for the same user..
				//-----------------------------------------
				$userpfields = array();
				
				ipsRegistry::DB('hb')->build( array( 'select' => '*',
													 'from'   => 'usermeta',
													 'where'  => "user_id={$row['ID']} AND meta_key IN ('".implode("','", array_keys($pcpf))."')",
							 ) );
				ipsRegistry::DB('hb')->execute();
				
				while ( $wpf = ipsRegistry::DB('hb')->fetch() )
				{
					$userpfields[ $wpf['meta_key'] ] = $wpf['meta_value'];
				}
				
				// Sort out also the website
				if ( $row['user_url'] )
				{
					$userpfields['user_url'] = $row['user_url'];
				}
				
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
						$custom['field_'.$field['pf_id']] = $us['pfield_data'][$field['pf_key']][$userpfields[$field['pf_key']]-1];
					}
					else
					{
						$custom['field_'.$field['pf_id']] = $userpfields[$field['pf_key']];
					}
				}
				
				//-----------------------------------------
				// And go!
				//-----------------------------------------

				$this->lib->convertMember($info, $members, array(), $custom, '', FALSE);
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
			$this->lib->saveMoreInfo('members', array('convert_to_blog'));
			
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> 'p.*, UNIX_TIMESTAMP( p.post_date_gmt ) AS TS_post_date_gmt, UNIX_TIMESTAMP( p.post_modified_gmt ) AS TS_post_modified_gmt',
							'from' 		=> array( 'posts' => 'p' ),
							'where'		=> "p.post_type='post'",
							'order'		=> 'p.ID ASC',
							'add_join'	=> array( array( 'select'	=> 'u.display_name',
														 'from'		=> array( 'users' => 'u' ),
														 'where'	=> 'u.ID=p.post_author'
														) )
						);
			
			$loop = $this->lib->load('blog_entries', $main);
			
			// Ask which blog to use...
			$options = array();
			$this->DB->build(array('select' => 'blog_id, blog_name', 'from' => 'blog_blogs', 'where' => "blog_type='local'"));
			$this->DB->execute();
			while ($row = $this->DB->fetch())
			{
				$options[$row['blog_id']] = $row['blog_name'];
			}
			
			$this->lib->getMoreInfo('members', $loop, array( 'convert_to_blog' => array('type' => 'dropdown', 'label' => 'Choose the blog where converted entries will be placed: ', 'options' => $options, 'extra' => $extra ) ), 'path');
			
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
				# Clean and get trackbacks
				$_trackbacks = ($row['pinged'] != '') ? explode( "\n", trim($row['pinged'], "\n" ) ) : array();
				
				//-----------------------------------------
				// Carry on
				//-----------------------------------------
				
				$save = array(
					'blog_id'					=> $us['convert_to_blog'],
					'entry_author_id'			=> $row['post_author'],
					'entry_author_name'			=> $row['display_name'],
					'entry_date'				=> $row['TS_post_date_gmt'],
					'entry_name'				=> $row['post_title'],
					'entry'						=> $this->fixPostData($row['post_content']),
					'entry_status'				=> ($row['post_status'] == 'publish') ? 'published' : 'draft',
					'entry_num_comments'		=> $row['comment_count'],
					'entry_last_comment_date'	=> 0,
					'entry_last_comment_name'	=> '',
					'entry_queued_comments'		=> 0,
					'entry_has_attach'			=> 0,
					'entry_edit_time'			=> intval($log['TS_post_modified_gmt']),
					'entry_edit_name'			=> '',
					'entry_use_emo'				=> 1,
					'entry_trackbacks'			=> count($_trackbacks),
					'entry_last_update'			=> $row['TS_post_date_gmt'],
					);
				
				$this->lib->convertEntry($row['ID'], $save, TRUE);
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
			
			$main = array(	'select' 	=> '*, UNIX_TIMESTAMP( comment_date_gmt ) AS TS_comment_date_gmt',
							'from' 		=> 'comments',
							'where'		=> "comment_type != 'trackback'",
							'order'		=> 'comment_ID ASC',
						);
			
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
					'entry_id'			=> $row['comment_post_ID'],
					'member_id'			=> $row['user_id'],
					'member_name'		=> $row['comment_author'],
					'ip_address'		=> $row['comment_author_IP'],
					'comment_date'		=> $row['TS_comment_date_gmt'],
					'comment_use_emo'	=> 1,
					'comment_approved'	=> $row['comment_approved'],
					'comment_edit_time'	=> 0,
					'comment_edit_name'	=> '',
					'comment_text'		=> $this->fixPostData($row['comment_content']),
					);
				
				$this->lib->convertComment($row['comment_ID'], $save);			
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
			
			$main = array(	'select' 	=> '*, UNIX_TIMESTAMP( comment_date_gmt ) AS TS_comment_date_gmt',
							'from' 		=> 'comments',
							'where'		=> "comment_type='trackback'",
							'order'		=> 'comment_ID ASC',
						);
			
			$loop = $this->lib->load('blog_trackback', $main);
			
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
				$save = array(
					'blog_id'				=> $us['convert_to_blog'],
					'entry_id'				=> $row['comment_post_ID'],
					'ip_address'			=> $row['comment_author_IP'],
					'trackback_url'			=> $row['comment_author_url'],
					'trackback_title'		=> $row['comment_author'],
					'trackback_excerpt'		=> $row['comment_content'],
					'trackback_blog_name'	=> $row['comment_author'],
					'trackback_date'		=> $row['TS_comment_date_gmt'],
					'trackback_queued'		=> $row['comment_approved'] == 1 ? 0 : 1,
					);
				$this->lib->convertTrackback($row['comment_ID'], $save, TRUE);
			}
			
			$this->lib->next();
		}										
	}
