<?php
/**
 * IPS Converters
 * IP.Downloads 2.0 Converters
 * vBulletin Downloads II
 * Last Updated By: $Author: Andrew Millne $
 *
 * @package		IPS Converters
 * @author 		Andrew Millne
 * @copyright           (c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 */


$info = array(
        'key'	=> 'dl2downloads',
        'name'	=> 'vBulletin Downloads II',
        'login'	=> false,
);

$parent = array (
	'required'	=> true,
	'choices'	=> array (
		array (
			'app'	=> 'board',
			'key'	=> 'vbulletin',
			'newdb'	=> false,
		),
	),
);

class admin_convert_downloads_dl2downloads extends ipsCommand
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

        $this->actions = array(
			'downloads_categories'	=> array(),
			'downloads_files'		=> array('downloads_categories'),
			'downloads_comments'	=> array('downloads_files'),
        );

        //-----------------------------------------
        // Load our libraries
        //-----------------------------------------

        require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
        require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_downloads.php' );
        $this->lib =  new lib_downloads( $registry, $html, $this );

        $this->html = $this->lib->loadInterface();
        $this->lib->sendHeader( 'Downloads II &raquo; IP.Downloads Converter' );

        //-----------------------------------------
        // Are we connected?
        // (in the great circle of life...)
        //-----------------------------------------

        $this->HB = $this->lib->connect();

        //-----------------------------------------
        // What are we doing?
        //-----------------------------------------

        if (array_key_exists($this->request['do'], $this->actions)) {
            call_user_func(array($this, 'convert_'.$this->request['do']));
        }
        else {
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
            case 'downloads_categories':
                return $this->lib->countRows('dl2_categories');
                break;

            case 'downloads_files':
                return $this->lib->countRows('dl2_files');
                break;

            case 'downloads_comments':
                return $this->lib->countRows('dl2_comments');
                break;

            case 'downloads_screenshots':
                return $this->lib->countRows('dl2_images');
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
            case 'downloads_files':
                return true;
                break;
            case 'downloads_screenshots':
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
		$post = nl2br ( $post );
		// And quote tags
		$post = preg_replace("#\[quote=(.+);\d\]#i", "[quote name='$1']", $post);
		$post = preg_replace("#\[quote=(.+)\](.+)\[/quote\]#i", "[quote name='$1']$2[/quote]", $post);
        return $post;
    }

    /**
     * Convert Categories
     *
     * @access	private
     * @return void
     **/
    private function convert_downloads_categories() {

        //---------------------------
        // Set up
        //---------------------------

        $main = array(	'select' 	=> '*',
                'from' 		=> 'dl2_categories',
                'order'		=> 'id ASC',

        );

        $loop = $this->lib->load('downloads_categories', $main);

        //---------------------------
        // Loop
        //---------------------------

        while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) ) {

            //-----------------------------------------
            // Handle permissions
            //-----------------------------------------

            $perms = array();
            $perms['view_files']	= "*";
            $perms['show_files']	= "*";
            $perms['add_files']		= "*";
            $perms['download']		= "*";
            $perms['comment']		= "*";
            $perms['rate']		= "*";
            $perms['bypass_mod']	= "*";


            //-----------------------------------------
            // And go
            //-----------------------------------------

            $save = array(
                    'cparent'	=> $row['parent'],
                    'cname'		=> $row['name'],
                    'cdesc'		=> $row['description'],
                    'cposition'	=> $row['weight'],
                    'cname_furl' => IPSText::makeSeoTitle($row['name']),
                    'copen'     => 1,
                    'coptions' => 'a:24:{s:12:"opt_mimemask";s:1:"1";s:10:"opt_bbcode";i:0;s:8:"opt_html";i:0;s:9:"opt_catss";i:0;s:10:"opt_filess";i:0;s:12:"opt_comments";i:1;s:11:"opt_allowss";i:1;s:9:"opt_reqss";i:0;s:13:"opt_sortorder";s:9:"submitted";s:10:"opt_sortby";s:3:"Z-A";s:11:"opt_maxfile";i:0;s:9:"opt_maxss";i:0;s:11:"opt_thumb_x";i:0;s:11:"opt_thumb_y";i:0;s:10:"opt_topice";i:0;s:10:"opt_topicf";i:1;s:10:"opt_topicp";s:0:"";s:10:"opt_topics";s:0:"";s:10:"opt_topicd";i:0;s:11:"opt_topicss";i:0;s:12:"opt_disfiles";i:1;s:15:"opt_noperm_view";s:0:"";s:14:"opt_noperm_add";s:0:"";s:13:"opt_noperm_dl";s:0:"";}',
            );

            $this->lib->convertCategory($row['id'], $save, $perms);
        }

        $this->lib->next();

    }


    /**
     * Convert Files
     *
     * @access	private
     * @return void
     **/
    private function convert_downloads_files() {
        //-----------------------------------------
        // Were we given more info?
        //-----------------------------------------

        $this->lib->saveMoreInfo('downloads_files', array('idm_local', 'idm_remote', 'idm_remote_ss'));

        //---------------------------
        // Set up
        //---------------------------


        $main = array(	'select' 	=> 'file.*',
                'from' 		=> array( 'dl2_files' => 'file' ),
                'add_join'	=> array(
                	array(
                		'select'	=> 'img.name, img.thumb',
                		'from'		=> array( 'dl2_images' => 'img' ),
                		'where'		=> 'img.file=file.id',
                		'type'		=> 'left'
                	)
                ),
                'order'		=> 'file.id ASC',
        );

        $loop = $this->lib->load('downloads_files', $main/*, array('downloads_filebackup')*/);

        //-----------------------------------------
        // We need some info
        //-----------------------------------------

        $ask = array();
        $ask['idm_local'] = array('type' => 'text', 'label' => 'The path to your IP.Board root folder (no trailing slash - can usually be copied and pasted from the box at the bottom of this table):');
        $ask['idm_remote'] = array('type' => 'text', 'label' => 'The path to where your source downloads are stored (no trailing slash):');
  		$ask['idm_remote_ss']	= array('type' => 'text', 'label' => 'The path to where your source download screenshots are stored (no trailing slash):');
  		
        $this->lib->getMoreInfo('downloads_files', $loop, $ask, 'path');

        $get = unserialize($this->settings['conv_extra']);
        $us = $get[$this->lib->app['name']];
        $options = array(
                'local_path'		=> $us['idm_local'],
                'remote_path'		=> $us['idm_remote'],
				'remote_ss_path'	=> $us['idm_remote_ss']
        );

        //-----------------------------------------
        // Check all is well
        //-----------------------------------------

        if (!is_readable($options['local_path'])) {
            $this->lib->error('Your local path is not readable. '.$this->settings['local_path']);
        }
        $storage = str_replace('{root_path}', $options['local_path'], $this->settings['idm_localfilepath']);
        if (!is_writable($storage)) {
            $this->lib->error('Your local storage path is not writeable. '.$storage);
        }
        if (!is_readable($options['remote_path'])) {
            $this->lib->error('Your remote storage path is not readable. '.$this->settings['remote_path']);
        }


        //---------------------------
        // Loop
        //---------------------------

        while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) ) {
            // Need to match this to a mimetype
            $e = explode('.', $row['url']);
            $extension = array_pop( $e );
            $mime = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'downloads_mime', 'where' => "mime_extension='{$extension}'" ) );
            if (!$mime) {
                $this->lib->logError($row['id'], 'Invalid file extension');
                continue;
            }


            $file = array(
                    'file_name'			=> $row['title'],
                    'file_cat'			=> $row['category'],
                    //'file_open'			=> $row['modqueue'],
                    'file_open'			=> 1,
                    'file_downloads'            => $row['totaldownloads'],
                    'file_submitted'            => $row['dateadded'],
                    'file_updated'		=> $row['lastedit'],
                    'file_desc'			=> $this->fixPostData($row['description']),
                    'file_size'			=> $row['size'],
                    'file_submitter'    => $row['uploaderid'],
                    'file_name_furl'            => IPSText::makeSeoTitle($row['title']),
                    'file_filename'		=> $row['url'],
                    'file_storagetype'	=> 'web',
                    'record_type'		=> preg_match('/http/', $row['url']) ? 'link' : 'upload',
                    'file_mime'			=> $mime,
                    'file_post_key'		=> md5 ( $row['url'] ),
                    'file_ssname'		=> $row['name'],
                    'file_ssthumb'		=> $row['thumb'],

            );
            
            $record = array(
                    'record_db_id'               => 0,
                    'record_storagetype'               => 'web',
                    'record_mime'               => $mime['mime_id'],
                    'record_size'               => $row['size'],
                    'record_backup'               => 0,
                    'record_location'           => $row['url'],
                    'record_realname'          => $row['url'],
					'record_post_key'			=> md5 ( $row['url'] ),
            );

            if (preg_match('/http/', $row['url']))
            {
            	$record['record_type'] = 'link';
            }
            else
            {
            	$record['record_type'] = 'upload';
            }

            $this->lib->convertFile($row['id'], $file, $options, array());

//     Added for another custom
//
//                          if ($row['author'] != '' && $row['version'] != '' && $this->lib->getLink($row['did'], 'downloads_files') != "") {
//                $fields = array('file_id' => $this->lib->getLink($row['did'], 'downloads_files'),
//                        'field_1' => $row['author'],
//                        'field_2' => $row['version'],
//                );
//
//                $this->DB->insert( 'downloads_ccontent', $fields );
//            }


        }

        $this->lib->next();

    }
    
    private function isValidURL($url)
	{
		return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
	}

    /**
     * Convert Comments
     *
     * @access	private
     * @return void
     **/
    private function convert_downloads_comments()
    {

        //---------------------------
        // Set up
        //---------------------------

        $main = array(	'select' 	=> '*',
                'from' 		=> 'dl2_comments',
                'order'		=> 'id ASC',
        );

        $loop = $this->lib->load('downloads_comments', $main);

        //---------------------------
        // Loop
        //---------------------------

        while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) ) {
            $save = array(
                    'comment_fid'	=> $row['fileid'],
                    'comment_mid'	=> $row['authorid'],
                    'comment_date'	=> $row['date'],
                    'comment_open'	=> '1',
                    'comment_text'	=> $this->fixPostData($row['message']),
                    //   'ip_address'	=> $row['ip_address'],
                    'use_sig'		=> '1',
                    'use_emo'		=> '1',
            );

            $this->lib->convertComment($row['id'], $save);
        }

        $this->lib->next();

    }

	/**
	 * Convert Log
	 *
	 * @access	private
	 * @return void
	 **/
	private function convert_downloads_downloads()
	{

		//---------------------------
		// Set up
		//---------------------------

		$main = array(	'select' 	=> '*',
						'from' 		=> 'dl2_downloads',
						'order'		=> 'id ASC',
					);

		$loop = $this->lib->load('downloads_downloads', $main);

		//---------------------------
		// Loop
		//---------------------------

		while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
		{
			$save = array(
				'dtime'	=> $row['time'],
				'dfid'		=> $row['fileid'],
				'dmid'		=> $row['userid'],
				);

			$this->lib->convertLog($row['id'], $save);
		}

		$this->lib->next();

	}
}