<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * SimplePress Forum
 * Last Update: $Date: 2010-07-22 11:29:06 +0200(gio, 22 lug 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Terabyte
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 447 $
 */

	$info = array(
		'key'	=> 'simplepress',
		'name'	=> 'SimplePress Forum 4.x',
		'login'	=> true,
	);
		
	class admin_convert_board_simplepress extends ipsCommand
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
				'ranks'			=> array(),
				'forum_perms'	=> array(),
				'members'		=> array('ranks','forum_perms'),
				'profile_friends' => array('members'),
				'forums'		=> array(),
				'topics'		=> array('members', 'forums'),
				'posts'			=> array('members', 'topics', 'emoticons'),
				'pms'			=> array('members', 'emoticons'),
				);
					
			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------
			
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
			$this->lib =  new lib_board( $registry, $html, $this );
	
	        $this->html = $this->lib->loadInterface();
			$this->lib->sendHeader( 'SimplePress Forum &rarr; IP.Board Converter' );
	
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
						'single' => 'sfgroups',
						'multi'  => array( 'sfgroups', 'sfforums' ) )
					)	);
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
				case 'emoticons':
					$temp = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => '*', 'from' => 'sfmeta', 'where' => "meta_type='smileys'" ) );
					$temp['_value'] = @unserialize($temp['meta_value']);
					return is_array($temp['_value']) ? count($temp['_value']) : 0;
					break;
					
				case 'ranks':
					return  $this->lib->countRows('sfmeta', "meta_type='forum_rank'");
					break;
					
				case 'forum_perms':
					return  $this->lib->countRows('sfusergroups');
					break;
					
				case 'members':
					return  $this->lib->countRows('sfmembers');
					break;
					
				case 'profile_friends':
					return  $this->lib->countRows('sfmembers', "buddies IS NOT NULL OR buddies != 'a:0:{}'");
					break;
					
				case 'forums':
					return  $this->lib->countRows('sfgroups') + $this->lib->countRows('sfforums');
					break;
					
				case 'topics':
					return  $this->lib->countRows('sftopics');
					break;
					
				case 'posts':
					return  $this->lib->countRows('sfposts');
					break;
					
				case 'pms':
					return  $this->lib->countRows('sfmessages');
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
				case 'emoticons':
				case 'ranks':
				case 'forum_perms':
				case 'members':
					return true;
					break;
				
				default:
					return false;
					break;
			}
		}
		
		private function escapeCodeBbcode($matches)
		{
			return '[code]'.htmlspecialchars_decode($matches[1]).'[/code]';
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
			$text = trim($text);
		
			$text = stripslashes($text);
		
			$text = str_replace("<p>", "", $text);
			$text = str_replace("</p>", "\r\r", $text);
			$text = str_replace("<br />", "\r", $text);
		
			$text = str_replace('<div class="sfcode">', "<code>", $text);
			$text = str_replace('</div>', "</code>", $text);
		
			# BBCode [code]
			$text = preg_replace_callback('/\<code\>(.*?)\<\/code\>/ms', array( &$this, 'escapeCodeBbcode'), $text);
		
			# Tags to Find
			$htmltags = array(
				'/\<b\>(.*?)\<\/b\>/is',
				'/\<em\>(.*?)\<\/em\>/is',
				'/\<u\>(.*?)\<\/u\>/is',
				'/\<ul\>(.*?)\<\/ul\>/is',
				'/\<li\>(.*?)\<\/li\>/is',
				'/\<img(.*?) src=\"(.*?)\" (.*?)\>/is',
				'/\<blockquote\>(.*?)\<\/blockquote\>/is',
				'/\<strong\>(.*?)\<\/strong\>/is',
				'/\<a href=\"(.*?)\"(.*?)\>(.*?)\<\/a\>/is',
			);
		
			# Replace with
			$bbtags = array(
				'[b]$1[/b]',
				'[i]$1[/i]',
				'[u]$1[/u]',
				'[list]$1[/list]',
				'[*]$1',
				'[img]$2[/img]',
				'[quote]$1[/quote]',
				'[b]$1[/b]',
				'[url=$1]$3[/url]',
			);
		
			# Replace $htmltags in $text with $bbtags
			$text = preg_replace($htmltags, $bbtags, $text);
		
			return $text;
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
							'from' 		=> 'sfmeta',
							'where'		=> "meta_type='smileys'",
						);
						
			$temp = $this->lib->load('emoticons', $main, array(), array(),true);
			$loop = @unserialize($temp[0]['meta_value']);
			$loop = is_array($loop) ? $loop : array();
			
			//-----------------------------------------
			// We need to know the path and how to handle duplicates
			//-----------------------------------------
			
			$this->lib->getMoreInfo('emoticons', $loop, array('emo_path' => array('type' => 'text', 'label' => 'The path to the folder where emoticons are saved (no trailing slash - usually path_to_wordpress/wp-content/forum-smileys"):'), 'emo_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate emoticons?') ), 'path' );
			
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
			
			$count = 0;
			
			foreach ( $loop as $name => $row )
			{
				$count++;
				
				$save = array(
					'typed'		=> $row[1],
					'image'		=> $row[0],
					'clickable'	=> 0,
					'emo_set'	=> 'default',
					);
				
				$done = $this->lib->convertEmoticon($count, $save, $us['emo_opt'], $path);				
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
							'from' 		=> 'sfmeta',
							'where'		=> "meta_type='forum_rank'",
							'order'		=> 'meta_id ASC',
						);
						
			$loop = $this->lib->load('ranks', $main);
			
			//-----------------------------------------
			// We need to know what do do with duplicates
			//-----------------------------------------
			
			$this->lib->getMoreInfo('ranks', $loop, array('rank_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate ranks?')));
			
			$get[$this->lib->app['name']] = $us;
			IPSLib::updateSettings(array('conv_extra' => serialize($get)));
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Unconvert data, SPF likes to store everything serialized damn!
				$row['_value'] = @unserialize($row['meta_value']);
				
				$save = array(
					'posts'	=> intval($row['_value']['posts']),
					'title'	=> $row['meta_key'],
					);
				
				$this->lib->convertRank($row['meta_id'], $save, $us['rank_opt']);			
			}
			
			$this->cache->rebuildCache('ranks', 'global'); 
			
			$this->lib->next();
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
							'from' 		=> 'sfusergroups',
							'order'		=> 'usergroup_id ASC',
						);
						
			$loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

			//-----------------------------------------
			// We need to know how to map these
			//-----------------------------------------
						
			$this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'usergroup_id', 'nf' => 'usergroup_name'));

			//---------------------------
			// Loop
			//---------------------------
			
			foreach( $loop as $row )
			{
				$this->lib->convertPermSet($row['usergroup_id'], $row['usergroup_name']);			
			}
			
			$this->lib->next();
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
				'location'		=> 'Location',
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
			
			$main = array(	'select'	=> 'fm.*, fm.display_name as fixed_display_name, UNIX_TIMESTAMP( fm.lastvisit ) AS TS_lastvisit',
							'from'		=> array( 'sfmembers' => 'fm' ),
							'order'		=> 'fm.user_id ASC',
							'add_join'	=> array( array( 'select'	=> 'wm.*, UNIX_TIMESTAMP( wm.user_registered ) AS TS_user_registered',
														 'from'		=> array( 'users' => 'wm' ),
														 'where'	=> 'wm.ID=fm.user_id'
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
					'id'				=> $row['user_id'],
					'group'				=> $row['admin'] ? $this->settings['admin_group'] : $this->settings['member_group'],
					'secondary_groups'	=> '',
					'joined'			=> $row['TS_user_registered'], // Let's use the timestamp and not the original field
					'username'			=> $row['user_login'],
					'displayname'		=> $row['fixed_display_name'],
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
					'last_visit'		=> $row['TS_lastvisit'],
					'last_activity' 	=> $row['TS_lastvisit'],
					'posts'				=> $row['posts'],
					//'title'				=> $rank['rank_title'],
					'email_pm'      	=> $row['pm'],
					'members_disable_pm'=> ($row['pm'] == 0) ? 1 : 0,
					'hide_email' 		=> 1,
					'allow_admin_mails' => 1,
					);
				
				// Sort rank
				$_posts = $members['posts'];
				
				foreach ( $this->cache->getCache('ranks') as $_rank )
				{
					if ( $members['posts'] >= $_rank['POSTS'] && $_rank['POSTS'] > $_posts )
					{
						$members['title'] = $_rank['TITLE'];
						// reset our posts check
						$_posts = $_rank['POSTS'];
					}
				}
				
				// Profile
				$profile = array(
					'signature'			=> $this->fixPostData($row['signature']),
					);
				
				//-----------------------------------------
				// Avatars
				//-----------------------------------------
				
				$path = '';
				// Uploaded
				if ($row['user_avatar_type'] == 1)
				{
					$ex	= substr(strrchr($row['user_avatar'], '.'), 1);
					$profile['avatar_type'] = 'upload';
					$profile['avatar_location'] = $us['avatar_salt'].'_'.$row['user_id'].'.'.$ex;
					$profile['avatar_size'] = $row['user_avatar_width'].'x'.$row['user_avatar_height'];
					$path = $us['pp_path'];
				}
				// URL
				elseif ($row['user_avatar_type'] == 2)
				{
					$profile['avatar_type'] = 'url';
					$profile['avatar_location'] = $row['user_avatar'];
					$profile['avatar_size'] = $row['user_avatar_width'].'x'.$row['user_avatar_height'];
				}
				// Gallery
				elseif ($row['user_avatar_type'] == 3)
				{
					$profile['avatar_type'] = 'upload';
					$profile['avatar_location'] = $row['user_avatar'];
					$profile['avatar_size'] = $row['user_avatar_width'].'x'.$row['user_avatar_height'];
					$path = $us['gal_path'];
				}
				
				//-----------------------------------------
				// Profile fields
				//-----------------------------------------
				// We couldn't join this because there are
				// several rows for the same user..
				//-----------------------------------------
				$userpfields = array();
				
				ipsRegistry::DB('hb')->build( array( 'select' => '*',
													 'from'   => 'usermeta',
													 'where'  => "user_id={$row['user_id']} AND meta_key IN ('".implode("','", array_keys($pcpf))."')",
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

				$this->lib->convertMember($info, $members, $profile, $custom, $path, '', FALSE);
			}
			
			$this->lib->next();
		}
		
		/**
		 * Convert friends
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_profile_friends()
		{
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> 'user_id, buddies',
							'from' 		=> 'sfmembers',
							'where'		=> "buddies IS NOT NULL AND buddies != 'a:0:{}'",
							'order'		=> 'user_id ASC',
						);
			
			$loop = $this->lib->load('profile_friends', $main);
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				// Again SPF loves serialized data...
				$data = @unserialize( $row['buddies'] );
				
				// Got them?
				if ( is_array($data) && count($data) )
				{
					foreach( $data as $idx => $fid )
					{
						$save = array(
							'friends_member_id'	=> $row['user_id'],
							'friends_friend_id'	=> $fid,
							'friends_approved'	=> '1',
							);
						
						$this->lib->convertFriend($row['user_id'].'-'.$row['zebra_id'], $save);
					}
				}
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
							'from' 		=> 'sfgroups',
							'order'		=> 'group_id ASC',
						);

			$loop = $this->lib->load('forums', $main, array(), array( 'boards', 'sfforums' ));
						
			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertForum('c'.$row['group_id'], array('name' => $row['group_name'], 'parent_id' => -1, 'position' => $row['group_seq']), array());
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
								'from'   => 'sfforums',
								'order'  => 'forum_id ASC',
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
					'parent_id'		=> $row['group_id'] ? 'c'.$row['group_id'] : -1,
					'position'		=> $row['forum_seq'],
					'name'			=> $row['forum_name'],
					'description'	=> $row['forum_desc'],
					'password'		=> '',
					'show_rules'	=> $row['forum_message'] ? 2 : 0,
					'rules_text'	=> $row['forum_message'],
					'rules_title'	=> $row['forum_name'],
					'sub_can_post'	=> $row['parent'] ? 1 : 0,
					'redirect_on'	=> 0,
					'redirect_url'	=> '',
					'redirect_hits' => 0,
					'status'		=> ($row['forum_status'] == 1) ? 0 : 1,
					'posts'			=> $row['post_count'],
					'topics'		=> $row['topic_count'],
					);
				
				$this->lib->convertForum($row['forum_id'], $save, $perms);
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
			
			$main = array(	'select'	=> 't.*, UNIX_TIMESTAMP( t.topic_date ) AS TS_topic_date',
							'from'		=> array( 'sftopics' => 't' ),
							'order'		=> 't.topic_id ASC',
							'add_join'	=> array( array( 'select'	=> 'm.display_name',
														 'from'		=> array( 'sfmembers' => 'm' ),
														 'where'	=> 'm.user_id=t.user_id'
														) )
							);
			
			$loop = $this->lib->load('topics', $main, array('tracker'));
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				
				
				$save = array(
					'title'				=> $row['topic_name'],
					'state'		   	 	=> $row['topic_status'] == 1 ? 'closed' : 'open',
					'posts'		    	=> $row['post_count'],
					'starter_id'    	=> $row['user_id'],
					'starter_name'  	=> $row['display_name'],
					'start_date'    	=> $row['TS_topic_date'],
					'poll_state'	 	=> 0, // No polls from this script                                   
					'views'			 	=> $row['topic_opened'],
					'forum_id'		 	=> $row['forum_id'],
					'approved'		 	=> 1,
					'author_mode'	 	=> 1,
					'pinned'		 	=> $row['topic_pinned'] == 1 ? 1 : 0,
					'topic_hasattach'	=> 0,
					);
				
				$this->lib->convertTopic($row['topic_id'], $save);
				
				// Subscriptions time! - And here we go (again) with serialized data...
				$tracker = @unserialize($row['topic_subs']);
				
				// Got them?
				if ( is_array($tracker) && count($tracker) )
				{
					foreach( $tracker as $idx => $mid )
					{
						$savetracker = array(
							'member_id'	=> $mid,
							'topic_id'	=> $row['topic_id'],
							);
						
						$this->lib->convertTopicSubscription($row['topic_id'].'-'.$mid, $savetracker);
					}
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
			
			$main = array(	'select'	=> 'p.*, UNIX_TIMESTAMP( p.post_date ) AS TS_post_date',
							'from'		=> array( 'sfposts' => 'p' ),
							'order'		=> 'p.post_id ASC',
							'add_join'	=> array( array( 'select'	=> 'm.display_name',
														 'from'		=> array( 'sfmembers' => 'm' ),
														 'where'	=> 'm.user_id=p.user_id'
														) )
							);
			
			$loop = $this->lib->load('posts', $main);
			
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$save = array(
					'author_id'   => $row['user_id'],
					'author_name' => $row['display_name'] ? $row['display_name'] : $row['guest_name'],
					'use_sig'     => 1,
					'use_emo'     => 1,
					'ip_address'  => $row['poster_ip'],
					'post_date'   => $row['TS_post_date'],
					'post'		  => $this->fixPostData($row['post_content']),
					'queued'      => 0,
					'topic_id'    => $row['topic_id'],
					);
				
				// Post has been edited?
				$edit = @unserialize($row['post_edit']);
				
				// Got them?
				if ( is_array($edit) && count($edit) )
				{
					// Grab the last one
					$_edit = array_pop($edit);
					
					$save['append_edit'] = 1;
					$save['edit_name'] = $_edit['by'];
					$save['edit_time'] = $_edit['at'];
				}
				
				$this->lib->convertPost($row['post_id'], $save);
			}

			$this->lib->next();
		}
													
		/**
		 * Convert PMs
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_pms()
		{
			//---------------------------
			// Set up
			//---------------------------
			
			$main = array(	'select' 	=> '*, UNIX_TIMESTAMP( sent_date ) AS TS_sent_date',
							'from' 		=> 'sfmessages',
							'order'		=> 'message_id ASC',
						);
			
			$loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));
						
			//---------------------------
			// Loop
			//---------------------------
			
			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				//-----------------------------------------
				// Post Data
				//-----------------------------------------
				
				$post = array(
					'msg_id'			=> $row['message_id'],
					'msg_topic_id'      => $row['message_id'],
					'msg_date'          => $row['TS_sent_date'],
					'msg_post'          => $this->fixPostData($row['message']),
					'msg_post_key'      => md5(microtime()),
					'msg_author_id'     => $row['from_id'],
					'msg_is_first_post' => 1
					);	
					
				//-----------------------------------------
				// Map Data
				//-----------------------------------------
				
				$maps = array();
				
				$map_master = array(
					'map_topic_id'    => $row['message_id'],
					'map_folder_id'   => 'myconvo',
					'map_read_time'   => 0,
					'map_last_topic_reply' => $row['TS_sent_date'],
					'map_user_active' => 1,
					'map_user_banned' => 0,
					'map_has_unread'  => 0,
					'map_is_system'   => 0,
					);
					
				$maps[] = array_merge( $map_master, array('map_user_id' => $row['to_id'], 'map_has_unread'  => ($row['message_status'] ? 0 : 1), 'map_is_starter' => 0) );
				
				if ($row['to_id'] != $row['from_id'])
				{
					$maps[] = array_merge( $map_master, array('map_user_id' => $row['from_id'], 'map_is_starter' => 1) );
				}
								
				//-----------------------------------------
				// Topic Data
				//-----------------------------------------
				
				$topic = array(
					'mt_id'			     => $row['message_id'],
					'mt_date'		     => $row['TS_sent_date'],
					'mt_title'		     => $row['title'],
					'mt_starter_id'	     => $row['from_id'],
					'mt_start_time'      => $row['TS_sent_date'],
					'mt_last_post_time'  => $row['TS_sent_date'],
					'mt_invited_members' => serialize( array( $row['to_id'] ) ),
					'mt_to_count'		 => 1,
					'mt_to_member_id'	 => $row['to_id'],
					'mt_replies'		 => 0,
					'mt_is_draft'		 => 0,
					'mt_is_deleted'		 => 0,
					'mt_is_system'		 => 0
					);
				
				//-----------------------------------------
				// Go
				//-----------------------------------------
				
				$this->lib->convertPM($topic, array($post), $maps);
			}
			
			$this->lib->next();
		}
	}