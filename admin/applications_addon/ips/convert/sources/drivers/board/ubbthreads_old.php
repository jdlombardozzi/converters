<?php
/**
 * IPS Converters
 * IP.Board 3.0 Converters
 * UBB.Threads
 * Last Update: $Date: 2011-07-31 13:28:48 +0100 (Sun, 31 Jul 2011) $
 * Last Updated By: $Author: AlexHobbs $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 571 $
 */
//print crypt(trim('password'),base64_encode(CRYPT_STD_DES));exit;
$info = array( 'key' => 'ubbthreads_old',
               'name'	=> 'UBB.Threads pre 7.5',
               'login' => false );

class admin_convert_board_ubbthreads_old extends ipsCommand
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
    $this->actions = array( 'forum_perms'	=> array(),
                            'groups' 		=> array('forum_perms'),
                            'members'		=> array('groups'),
                            'profile_friends' => array('members'),
                            'forums'		=> array('forum_perms'),
                            'moderators'	=> array('members', 'forums'),
                            'topics'		=> array('members', 'forums'),
                            'posts'			=> array('members', 'topics'),
                            'pms'			=> array('members') );

    //-----------------------------------------
    // Load our libraries
    //-----------------------------------------
    require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
    require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
    $this->lib =  new lib_board( $registry, $html, $this );

    $this->html = $this->lib->loadInterface();
    $this->lib->sendHeader( 'UBB.Threads &rarr; IP.Board Converter' );

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
      $this->lib->menu( array( 'forums' => array( 'single' => 'Category',
                                                  'multi'  => array( 'Category', 'Boards' ) ) ) );
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
        return $this->lib->countRows('Groups');
        break;

      case 'members':
        return $this->lib->countRows('Users');
        break;

      case 'forums':
        return $this->lib->countRows('Boards') + $this->lib->countRows('Category');
        break;

      case 'topics':
        return $this->lib->countRows('Posts', "B_Topic = '1'");
        break;

      case 'pms':
        return $this->lib->countRows('Messages');
        break;

      case 'profile_friends':
        return $this->lib->countRows('AddressBook', "Add_Owner != '' AND Add_Member != ''");
        break;

      default:
        return $this->lib->countRows(ucfirst($action));
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
    $main = array( 'select' => '*',
                   'from' 	=> 'Groups',
                   'order'	=> 'G_Id ASC' );

    $loop = $this->lib->load( 'forum_perms', $main, array(), array(), TRUE );

    //-----------------------------------------
    // We need to know how to map these
    //-----------------------------------------
    $this->lib->getMoreInfo('forum_perms', $loop, array('new' => '--Create new set--', 'ot' => 'Old permission set', 'nt' => 'New permission set'), '', array('idf' => 'G_Id', 'nf' => 'G_Name'));

    //---------------------------
    // Loop
    //---------------------------
    foreach( $loop as $row )
    {
      $this->lib->convertPermSet($row['G_Id'], $row['G_Name']);
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
    $main = array( 'select' 	=> '*',
                   'from' 		=> 'Groups',
                   'order'		=> 'G_Id ASC' );

    $loop = $this->lib->load( 'groups', $main, array(), array(), TRUE );

    //-----------------------------------------
    // We need to know how to map these
    //-----------------------------------------
    $this->lib->getMoreInfo('groups', $loop, array('new' => '--Create new group--', 'ot' => 'Old group', 'nt' => 'New group'), '', array('idf' => 'G_Id', 'nf' => 'G_Name'));

    //---------------------------
    // Loop
    //---------------------------
    $groups = array();

    // Loop
    foreach( $loop as $row )
    {
      $save = array( 'g_title' => $row['G_Name'],
                     'g_perm_id' => $row['G_Id'] );

      $this->lib->convertGroup($row['G_Id'], $save);
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
    $pcpf = array( 'U_Homepage' => 'Homepage',
                   'U_Occupation' => 'Occupation',
                   'U_Hobbies' => 'Hobbies',
                   'U_Location' => 'Location',
                   'U_Bio' => 'Bio' );

    $this->lib->saveMoreInfo('members', $pcpf);

    //---------------------------
    // Set up
    //---------------------------
    $main = array( 'select' => '*',
                   'from' => 'Users',
                   'order' => 'U_Number ASC' );

    $loop = $this->lib->load('members', $main);

    //-----------------------------------------
    // Tell me what you know!
    //-----------------------------------------
    $get = unserialize($this->settings['conv_extra']);
    $us = $get[$this->lib->app['name']];
    $ask = array();

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
      $groups = explode('-', $row['U_Groups']);
      $group = array_shift($groups);

      // Basic info
      $info = array( 'id' => $row['U_Number'],
                     'group' => $group,
                     'secondary_groups'	=> implode(',', $groups),
                     'joined' => $row['U_Registered'] != '' ? $row['U_Registered'] : time(),
                     'username' => $row['U_Username'],
                     'displayname' => $row['U_Name'] != '' ? $row['U_Name'] : $row['U_Username'],
                     'email' => $row['U_Email'],
                     'password' => $row['U_Password'] );

      $members = array( 'posts' => $row['U_Totalposts'],
                        'hide_email' => $row['U_Fakeemail'] == $row['U_Email'] ? 0 : 1,
                        'time_offset' => intval($row['U_TimeOffset']),
                        'title' => $row['U_Title'],
                        'ip_address' => $row['U_RegIP'],
                        'last_visit' => $row['U_Laston'],
                        'email_pm' => ($row['U_Notify'] == 'yes') ? 1 : 0,
                        'view_sigs' => ($row['U_ShowSigs'] == 'no') ? 0 : 1 );

      // Profile
      $profile = array( 'signature'			=> $this->fixPostData($row['U_Signature']) );

      //-----------------------------------------
      // Avatars
      //-----------------------------------------
//      if ($row['U_Picture'])
//      {
//        $profile['photo_type'] = 'url';
//        $profile['photo_location'] = $row['U_Picture'];
//      }

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
    $main = array( 'select' => '*',
                   'from' => 'Category',
                   'order' => 'Cat_Number ASC' );

    $loop = $this->lib->load('forums', $main, array(), array('boards', 'Category'));

    //---------------------------
    // Loop
    //---------------------------
    while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
    {
      $this->lib->convertForum( $row['Cat_Number'], array( 'name' => $row['Cat_Title'],
                                                           'description' => $row['Cat_Description'],
                                                           'position' => $row['Cat_Number'],
                                                           'parent_id'		=> -1 ), array());
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
                        'from' => 'Boards',
                        'order' => 'Bo_Number ASC' );

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
      $get[$this->lib->app['name']] = $us;
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

    $i = 1;
    while ( $row = ipsRegistry::DB('hb')->fetch() )
    {
      $records[] = $row;
    }

    $loop = $records;

    //---------------------------
    // Loop
    //---------------------------
    foreach ( $loop as $row )
    {
      // Set info
      $save = array( 'parent_id' => $row['Bo_Cat'],
                     'position' => $row['Bo_Sorter'],
                     'name' => $row['Bo_Title'],
                     'description' => $row['Bo_Description'],
                     'topics' => $row['Bo_Threads'],
                     'posts' => $row['Bo_Total'],
                     'inc_postcount' => 1,
                     'status' => $row['Bo_Expire'] == 1 ? 0 : 1 );

      // Save
      $this->lib->convertForum($row['Bo_Keyword'], $save, array());

      //-----------------------------------------
			// Handle subscriptions
			//-----------------------------------------
      ipsRegistry::DB('hb')->build( array( 'select' => 's.*',
                                           'from' => array('Subscribe' => 's'),
                                           'add_join' => array( array( 'select' => 'm.U_Number',
                                                                       'from' => array( 'Users' => 'm' ),
                                                                       'where' => 's.S_Username = m.U_Username',
                                                                       'type' => 'inner' ) ),
                                           'where' => "s.S_Board='{$row['Bo_Keyword']}'"));
      ipsRegistry::DB('hb')->execute();

        // Can't convert until I find a way to handle users being able to subscribe to same forum more than once
//      while ($tracker = ipsRegistry::DB('hb')->fetch())
//      {
//        // There is no tracker type
//        $savetracker = array( 'member_id'	=> $tracker['U_Number'],
//                              'forum_id'	=> $tracker['S_Board'],
//                              'forum_track_type' => 'none' );
//        $this->lib->convertForumSubscription($tracker['S_Board'].'-'.$tracker['U_Number'], $savetracker);
//      }
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
    $main = array( 'select' => 'p.*',
                   'from' => array( 'Posts' => 'p' ),
                   'add_join' => array( array( 'select' => 'm.U_Number',
                                               'from' => array( 'Users' => 'm' ),
                                               'where' => 'p.B_Username = m.U_Username',
                                               'type' => 'left' ) ),
                   'where' => "p.B_Topic = '1'",
                   'order' => 'p.B_Main ASC' );

    $loop = $this->lib->load('topics', $main);

    //---------------------------
    // Loop
    //---------------------------

    while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
    {
      $save = array( 'forum_id' => $row['B_Board'],
                     'title' => $row['B_Subject'],
                     'views' => $row['B_Counter'],
                     'posts' => $row['B_Replies'],
                     'starter_name' => $row['B_Username'],
                     'starter_id' => $row['U_Number'],
                     'state' => $row['B_Status'] == 'C' ? 'closed' : 'open',
                     'approved' => $row['B_Approved'] == 'no' ? 0 : 1,
                     'start_date' => $row['B_Posted'],
                     'pinned'			=> $row['B_Sticky'] == 'yes' ? 1 : 0,
                     'topic_hasattach' => 0,
                     'poll_state' => $row['B_Poll'] != '' ? 1 : 0 );

      $this->lib->convertTopic($row['B_Main'], $save);

      //-----------------------------------------
      // Handle subscriptions
      //-----------------------------------------
      // Can't convert until I find a way to handle users being able to subscribe to same forum more than once
//      ipsRegistry::DB('hb')->build( array( 'select' => 't.*',
//                                           'from' => array('Favorites' => 't'),
//                                           'add_join' => array( array( 'select' => 'm.U_Number',
//                                                                       'from' => array( 'Users' => 'm' ),
//                                                                       'where' => 't.F_Owner = m.U_Username',
//                                                                       'type' => 'inner' ) ),
//                                           'where' => "t.F_Thread='{$row['B_Main']}'"));
//      ipsRegistry::DB('hb')->execute();
//      while ($tracker = ipsRegistry::DB('hb')->fetch())
//      {
//        // Not sure what F_Type is: r, f
//        $savetracker = array( 'member_id'	=> $tracker['U_Number'],
//                              'topic_id'	=> $tracker['F_Thread'],
//                              'topic_track_type' => $tracker['F_Type'] ? 'none' : 'none' );
//        $this->lib->convertTopicSubscription($tracker['F_Thread'].'-'.$tracker['U_Number'], $savetracker);
//      }
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
    $main = array( 'select' => 'p.*',
                   'from' => array( 'Posts' => 'p' ),
                   'add_join' => array( array( 'select' => 'm.U_Number',
                                               'from' => array( 'Users' => 'm' ),
                                               'where' => 'p.B_Username = m.U_Username',
                                               'type' => 'left' ) ),
                   'order' => 'p.B_Number ASC' );

    $loop = $this->lib->load('posts', $main);

    //---------------------------
    // Loop
    //---------------------------
    while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
    {
      $save = array( 'author_name' => $row['B_Username'],
        'author_id' => $row['U_Number'],
        'topic_id' => $row['B_Main'],
        'post' => $this->fixPostData( $row['B_Body'] ),
        'post_date' => $row['B_Posted'],
        'use_sig' => $row['B_Signature'] == '' ? 0 : 1,
        'ip_address' => $row['B_IP'],
        'use_emo' => 1,
        'queued' => $row['B_Approved'] == 'no' ? 1 : 0 );

      $this->lib->convertPost($row['B_Number'], $save);
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
    $main = array( 'select' 	=> 'm.*',
                   'from' 		=> array ( 'Messages' => 'm' ),
                   'add_join' => array( array( 'select' => 'u.U_Number',
                                               'from' => array( 'Users' => 'u' ),
                                               'where' => 'm.M_Sender = u.U_Username',
                                               'type' => 'inner' ),
                                        array( 'select' => 'fu.U_Number as M_Receiver_Id',
                                               'from' => array( 'Users' => 'fu' ),
                                               'where' => 'm.M_Username = fu.U_Username',
                                               'type' => 'inner' ) ),
                    'order'		=> 'm.M_Number ASC' );

    $loop = $this->lib->load('pms', $main, array('pm_posts', 'pm_maps'));

    //---------------------------
    // Loop
    //---------------------------

    while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
    {
      //-----------------------------------------
      // Posts
      //-----------------------------------------
      $posts = array();
      $posts[] = array( 'msg_id'			=> $row['M_Number'],
                        'msg_topic_id'      => $row['M_Number'],
                        'msg_date'          => $row['M_Sent'],
                        'msg_post'          => $this->fixPostData($row['M_Message']),
                        'msg_post_key'      => md5(microtime()),
                        'msg_author_id'     => $row['U_Number'] );

      //-----------------------------------------
      // Map Data starter then receiver
      //-----------------------------------------
      $maps = array( array( 'map_user_id' => $row['U_Number'],
                            'map_topic_id' => $row['M_Number'],
                            'map_folder_id' => 'myconvo',
                            'map_read_time' => $row['M_Status'] == 'N' ? '' : time(),
                            'map_last_topic_reply' => $row['M_Sent'],
                            'map_user_active' => 1,
                            'map_user_banned' => 0,
                            'map_has_unread'  => $row['M_Status'] == 'N' ? 1 : 0,
                            'map_is_system'   => 0,
                            'map_is_starter'  => 1 ),
                     array( 'map_user_id' => $row['M_Receiver_Id'],
                            'map_topic_id' => $row['M_Number'],
                            'map_folder_id' => 'myconvo',
                            'map_read_time' => $row['M_Status'] == 'N' ? '' : time(),
                            'map_last_topic_reply' => $row['M_Sent'],
                            'map_user_active' => 1,
                            'map_user_banned' => 0,
                            'map_has_unread'  => $row['M_Status'] == 'N' ? 1 : 0,
                            'map_is_system'   => 0,
                            'map_is_starter'  => 0 ) );

      $topic = array( 'mt_id' => $row['M_Number'],
                      'mt_date' => $row['M_Sent'],
                      'mt_title' => $row['M_Subject'],
                      'mt_starter_id' => $row['U_Number'],
                      'mt_start_time' => $row['M_Sent'],
                      'mt_last_post_time' => $row['M_Sent'],
                      'mt_invited_members' => serialize( array( $row['U_Number'], $row['M_Receiver_Id'] ) ),
                      'mt_to_count' => 2,
                      'mt_to_member_id' => $row['M_Receiver_Id'],
                      'mt_replies' => 0,
                      'mt_is_draft' => 0,
                      'mt_is_deleted' => 0,
                      'mt_is_system' => 0 );
//print "<PRE>";print_r($topic);print_r($posts);print_r($maps);exit;
      //-----------------------------------------
      // Go
      //-----------------------------------------
      $this->lib->convertPM($topic, $posts, $maps);
    }

    $this->lib->next();
  }

  /**
   * Convert Moderators
   *
   * @access	private
   * @return void
   **/
  private function convert_moderators()
  {
    //---------------------------
    // Set up
    //---------------------------
    $main = array( 'select' => 'm.*',
                   'from' => array( 'Moderators' => 'm' ),
                   'add_join' => array( array( 'select' => 'u.U_Number',
                                               'from' => array( 'Users' => 'u' ),
                                               'where' => 'm.Mod_Username = u.U_Username',
                                               'type' => 'inner' ) ),
                   'order' => 'm.Mod_Board ASC' );

    $loop = $this->lib->load('moderators', $main);

    //---------------------------
    // Loop
    //---------------------------

    while( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
    {
      $save = array( 'forum_id'	  => $row['Mod_Board'],
                 'member_name'  => $row['Mod_Username'],
                 'member_id'	  => $row['U_Number'] );
      $this->lib->convertModerator($row['Mod_Board'].'-'.$row['U_Number'], $save);
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
    $main = array( 'select' 	=> 'a.*',
                   'from' => array( 'AddressBook' => 'a' ),
                   'add_join' => array( array( 'select' => 'u.U_Number',
                                               'from' => array( 'Users' => 'u' ),
                                               'where' => 'a.Add_Owner = u.U_Username',
                                               'type' => 'inner' ),
                                        array( 'select' => 'fu.U_Number as FU_Number',
                                               'from' => array( 'Users' => 'fu' ),
                                               'where' => 'a.Add_Member = fu.U_Username',
                                               'type' => 'inner' ) ),
            'where'		=> "a.Add_member != '' AND a.Add_Owner != ''" );

    $loop = $this->lib->load('profile_friends', $main);

    //---------------------------
    // Loop
    //---------------------------
    while( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
    {
      $save = array( 'friends_member_id' => $row['U_Number'],
                     'friends_friend_id'	=> $row['FU_Number'],
                     'friends_approved'	=> '1' );
      $this->lib->convertFriend($row['U_Number'].'-'.$row['FU_Number'], $save);
    }
    $this->lib->next();
  }
}
