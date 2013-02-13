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
$info = array( 'key' => 'huddler',
               'name'	=> 'Huddler',
               'login' => true );

class admin_convert_board_huddler extends ipsCommand
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
    $this->actions = array( 'members'		=> array(),
                            'forums'		=> array(),
                            'topics'		=> array('members', 'forums'),
                            'posts'			=> array('members', 'topics') );

    //-----------------------------------------
    // Load our libraries
    //-----------------------------------------
    require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
    require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
    $this->lib =  new lib_board( $registry, $html, $this );

    $this->html = $this->lib->loadInterface();
    $this->lib->sendHeader( 'Huddler &rarr; IP.Board Converter' );

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

      case 'forums':
        return $this->lib->countRows('forums');
        break;

      case 'topics':
        return $this->lib->countRows('threads');
        break;

      case 'posts':
        return $this->lib->countRows('posts');
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
      // Remove strange preceding characters
      $text = preg_replace('/^(\x7F\x41)/', '', $text);

			$text = trim($text);

			$text = stripslashes($text);

			$text = str_replace("<p>", "", $text);
			$text = str_replace("</p>", "\r\r", $text);
			$text = str_replace("<br />", "\r", $text);

      $text = preg_replace("'\s+'", ' ', $text);

      $text = preg_replace('/\<div\>(.*?)\<\/div\>/is', '$1', $text);
      $text = preg_replace("/<div class=\"quote-container\">\s<span>Quote:<\/span>\s<div class=\"quote-block\">\s(.*?)<\/div>\s<\/div>/is", "[quote]$1[/quote]", $text);

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

      # Remove smilies
      $text = preg_replace('/\[img\].*\/images\/smilies.*\[\/img\]/i', '', $text);

			return $text;
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
    $pcpf = array( 'location' => 'Location',
                   'country' => 'Country',
                    'postcode' => 'Postal Code');

    $this->lib->saveMoreInfo('members', $pcpf, array('pp_path'));

    //---------------------------
    // Set up
    //---------------------------
    $main = array( 'select' => 'u.*',
                   'from' => array( 'users' => 'u'),
                   'add_join' => array( array( 'select' => 'a.path, a.width, a.height',
                                               'from' => array( 'images' => 'a' ),
                                               'where' => 'u.avatar_image_id = a.image_id',
                                                'type' => 'left' ) ),
                   'order' => 'u.id ASC' );

    $loop = $this->lib->load('members', $main);

    //-----------------------------------------
    // Tell me what you know!
    //-----------------------------------------
    $get = unserialize($this->settings['conv_extra']);
    $us = $get[$this->lib->app['name']];
    $ask = array();

    // We need to know how to the avatar paths
    $ask['pp_path']  	= array('type' => 'text', 'label' => 'Path to avatars uploads folder (no trailing slash, default /pathtophpbb/images/avatars/upload): ');

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
      $info = array( 'id' => $row['id'],
                     'joined' => strtotime($row['created_timestamp']),

                     'username' => $row['username'],
                     'email' => $row['email'],
                     'pass_hash' => $row['password'],
                      'pass_salt' => $row['salt'] );

      $members = array( 'posts' => intval($row['num_posts']),
                        'hide_email' => 0,
                        'time_offset' => intval(substr($row['created_timestamp'], -3)),
                        'last_visit' => time(),
                        'email_pm' => intval($row['email_opt_in']),
                        'view_sigs' => 1 );

      // Profile
      $profile = array( 'signature'			=> $this->fixPostData($row['signature']) );

      //-----------------------------------------
			// Avatars
			//-----------------------------------------

      $path = '';
      // Uploaded
      if ($row['user_avatar_type'] == 1)
      {
        $profile['photo_type'] = 'custom';
        $profile['photo_location'] = $row['path'];
				$profile['pp_main_width'] = $row['width'];
				$profile['pp_main_height'] = $row['height'];
      }

      //-----------------------------------------
      // And go!
      //-----------------------------------------
      $this->lib->convertMember($info, $members, $profile, array(), $us['pp_path']);
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
                   'from' => 'forums',
                   'order' => 'id ASC' );

    $loop = $this->lib->load('forums', $main);

    //---------------------------
    // Loop
    //---------------------------
    while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
    {
      $this->lib->convertForum( $row['id'], array( 'name' => $row['forum_name'],
                                                 'description' => $row['description'],
                                                 'position' => $row['forum_order'],
                                                 'parent_id'		=> $row['id'] == $row['parent_id'] ? -1 : $row['parent_id'],
                                                  'sub_can_post' => 1,
                                                  'topics' => $row['num_threads'],
                                                  'posts' => $row['num_posts'],
                                                  'inc_postcount' => 1 ), array() );
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
    $main = array( 'select' => 't.*',
                   'from' => array( 'threads' => 't' ),
                   'add_join' => array( array( 'select' => 'p.title, p.post_timestamp',
                                               'from' => array( 'posts' => 'p' ),
                                               'where' => 't.first_post_id = p.id',
                                               'type' => 'inner' ),
                                        array( 'select' => 'u.username',
                                               'from' => array( 'users' => 'u' ),
                                                'where' => 'p.posted_by_uid = u.id',
                                                'type' => 'left' ) ),
                   'order' => 't.id ASC' );

    $loop = $this->lib->load('topics', $main);

    //---------------------------
    // Loop
    //---------------------------

    while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
    {
      $save = array( 'forum_id' => $row['forum_id'],
                     'title' => $row['title'],
                     'views' => $row['num_views'],
                     'posts' => intval($row['num_posts']) - 1,
                     'starter_name' => $row['username'],
                     'starter_id' => $row['posted_by_uid'],
                     'state' => $row['status'] == '2' ? 'closed' : 'open',
                     'approved' => $row['status'] == '3' ? 1 : 0,
                     'start_date' => strtotime($row['post_timestamp']),
                     'pinned'			=> 0,
                     'topic_hasattach' => 0,
                     'poll_state' => 0 );

      $this->lib->convertTopic($row['id'], $save);
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
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$this->lib->saveMoreInfo('posts', array('attach_path'));

    //---------------------------
    // Set up
    //---------------------------
    $main = array( 'select' => 'p.*',
                   'from' => array( 'posts' => 'p' ),
                   'add_join' => array( array( 'select' => 'u.username',
                                               'from' => array( 'users' => 'u' ),
                                               'where' => 'p.posted_by_uid = u.id',
                                               'type' => 'left' ) ),
                   'order' => 'p.id ASC' );

    $loop = $this->lib->load('posts', $main, array('attachments'));

		//-----------------------------------------
		// We need to know the path
		//-----------------------------------------
		$this->lib->getMoreInfo('attachments', $loop, array('attach_path' => array('type' => 'text', 'label' => 'The path to the folder where attachments are saved (no trailing slash - usually path_to/uploads):')), 'path');

		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$path = $us['attach_path'];

    //---------------------------
    // Loop
    //---------------------------
    $count = 0;
    while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
    {
      // 6 has images
      //if ( $count++ <= 11 ) continue;

      $save = array( 'author_name' => $row['username'],
        'author_id' => $row['posted_by_uid'],
        'topic_id' => $row['thread_id'],
        'post' => $this->fixPostData( $row['content'] ),
        'post_date' => strtotime($row['post_timestamp']),
        'use_sig' => 1,
        'ip_address' => null,
        'use_emo' => 1,
        'queued' => $row['status'] == '1' ? 1 : 0 );

      //$image_ids = $this->find_images($save['post']);

      $this->lib->convertPost($row['id'], $save);
    }

    $this->lib->next();
  }

  private function convert_attachments()
  {
     //<a href="http://static.blossomswap.com/imgrepo/2/23/vbattach5957.gif"><img src="http://static.blossomswap.com/imgrepo/thumbs/2/23/vbattach5957.gif/525x525px-LL-vbattach5957.gif"></a>
    //2/23/vbattach5957.gif
  }
}