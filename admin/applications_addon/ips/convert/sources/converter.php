<?php
class converter
{
  /**
   * Connect to external database
   *
   * @access   public
   * @return   void
   **/
  public function connect()
  {
    //-----------------------------------------
    // Turn the board (or whatever) offline
    //-----------------------------------------

    $doNothing = true; // Remove the unnecessary board offline for everything feature
    $offlineSetting = 'board_offline';
    $offlineSetTo = true;
    $offlineMessage = 'offline_msg';

    switch ($this->app['sw']) {
      case 'blog':
        $offlineSetting = 'blog_online';
        $offlineSetTo = false;
        $offlineMessage = 'blog_offline_text';
        break;

      case 'calendar':
        $doNothing = true;
        break;

      case 'ccs':
        $offlineSetting = 'ccs_online';
        $offlineSetTo = false;
        $offlineMessage = 'ccs_offline_message';
        break;

      case 'downloads':
        $offlineSetting = 'idm_online';
        $offlineSetTo = false;
        $offlineMessage = 'idm_offline_msg';
        break;

      case 'gallery':
        $offlineSetting = 'gallery_offline';
        $offlineMessage = 'gallery_offline_text';
        break;

      case 'subscriptions':
        $doNothing = true;
        break;

      case 'tracker':
        $offlineSetting = 'tracker_is_online';
        $offlineSetTo = false;
        $offlineMessage = 'tracker_offline_message';
        break;
    }

    if (!$doNothing) {
      if (($offlineSetTo and !$this->settings[$offlineSetting]) or (!$offlineSetTo and $this->settings[$offlineSetting])) {
        IPSLib::updateSettings(array($offlineSetting => $offlineSetTo, $offlineMessage => 'Conversion in process'));
      }
    }

    //-----------------------------------------
    // And connect
    //-----------------------------------------

    return $this->registry->dbFunctions()->setDB($this->app['db_driver'], 'hb',
      array(
           'sql_database' => $this->app['db_db'],
           'sql_user' => $this->app['db_user'],
           'sql_pass' => $this->app['db_pass'],
           'sql_host' => $this->app['db_host'],
           'sql_tbl_prefix' => $this->app['db_prefix'],
           'sql_charset' => $this->app['db_charset'],
      )
    );
  }

  /**
   * Show Error Message
   *
   * @access  private
   * @param  string    Error message
   * @return  void
   */
  public function error($message)
  {
    parent::sendError($message);
  }

  /**
   * Information box to display on convert screen
   *
   * @access  public
   * @return  string     html to display
   */
  public function getInfo()
  {
    return "<strong>Recount Albums</strong><br />
 			<a href='{$this->settings['base_url']}&app=gallery&module=albums&section=manage&do=recountallalbums' target='_blank'>Click here</a> to recount all albums.
 			<br /><br />
 			<strong>Rebuild Categories</strong><br />
 			<a href='{$this->settings['base_url']}&app=gallery&module=cats&section=manage&do=recount&cat=all' target='_blank'>Click here</a> to rebuild all categories.
 			<br /><br />
 			<strong>Rebuild Images</strong><br />
 			<a href='{$this->settings['base_url']}&app=gallery&module=tools&section=tools&do=rethumbs' target='_blank'>Click here</a> and rebuild images in all categories.<br />
 			<br /><br />
 			<strong>Turn the application back online</strong><br />
 			Visit your IP.Gallery settings and turn the application back online.";
  }

  /**
   * Ask for More Info
   *
   * @access  public
   * @param  string    action (e.g. 'members', 'forums', etc.)
   * @param  array     values from self::load()
   * @param  array     Things to ask for
   * @param   string     key for hint box (optional)
   * @param  array     Map data
   * @return   void
   **/
  public function getMoreInfo($action, $loop, $custom = array(), $hint = '', $mapfields = array())
  {
    $get = unserialize($this->settings['conv_extra']);
    $us = $get[$this->app['name']];
    $us = is_array($us) ? $us : array();
    $extra = is_array($us[$action]) ? $us : array_merge($us, array($action => array()));

    if (!empty($mapfields)) {
      $ask = array();
      foreach ($loop as $loop) {
        if (!array_key_exists($loop[$mapfields['idf']], $extra[$action])) {
          $ask[$loop[$mapfields['idf']]] = $loop[$mapfields['nf']];
        }
      }
      if (!empty($ask)) {
        $ourrows = $this->loadLocalInfo($action);
        $options = "<option value='x'>{$custom['new']}</option>";
        foreach ($ourrows as $id => $name) {
          $options .= "<option value='{$id}'>{$name}</option>";
        }

        $row = '';
        foreach ($ask as $id => $name) {
          $select = "<select name='{$action}[$id]'>{$options}</select>";
          $rows .= $this->html->convertMoreInfoRow($name, $select);
        }
        $this->registry->output->html .= $this->html->convertMoreInfo($rows, $custom['ot'], $custom['nt']);
        $this->sendOutput();
      }
    } else {
      $this->generateMoreInfoPage($custom, $hint);
    }

  }

  /**
   * Generate a page for arbitrary questions
   *
   * @access  private
   * @param  array     Things to ask for
   * @param   string     key for hint box
   * @return   void
   **/
  private function generateMoreInfoPage($input_array, $hint)
  {
    $get = unserialize($this->settings['conv_extra']);
    $us = $get[$this->app['name']];

    foreach ($input_array as $key => $question) {
      if ($question['override']) {
        if (!$us[$question['override']['name']][$question['override']['id']]) {
          $ask[$key] = $question;
        }
      } else {
        if (!$us[$key]) {
          $ask[$key] = $question;
        }
      }
    }

    if (!empty($ask)) {
      $rows = '';
      foreach ($ask as $key => $qinfo) {
        if ($qinfo['type'] == 'text') {
          if ($qinfo['override']) {
            $key = $qinfo['override']['name'] . '][' . $qinfo['override']['id'];
          }
          $input = "<input name='input[{$key}]' size='50' />";
        } elseif ($qinfo['type'] == 'dupes') {
          $input = "<select name='input[{$key}]'>
							<option value='local'>Keep IP.Board settings</option>
							<option value='remote'>Overwrite with remote settings</option>
						</select>";
        }
        elseif ($qinfo['type'] == 'dropdown') {
          $input = "<select name='input[{$key}]'>";
          foreach ($qinfo['options'] as $key => $value) {
            $input .= "<option value='{$key}'>{$value}</option>";
          }
          $input .= '</select>';
        }
        else {
          $this->sendError('There is a problem with the converter: bad more info type');
        }
        if ($qinfo['extra']) {
          $input .= " {$qinfo['extra']}";
        }
        $rows .= $this->html->convertMoreInfoRow($qinfo['label'], $input);
      }
      $this->registry->output->html .= $this->html->convertMoreInfo($rows, '&nbsp;', '&nbsp;');

      if ($hint) {
        switch ($hint) {
          case 'path':
            $hint = 'The path to your IP.Board is: ' . DOC_IPS_ROOT_PATH;
            break;

          case 'database':
            $hint = 'You must first create a database within IP.Content';
            break;
        }
        $this->registry->output->html .= $this->html->convertHint($hint);
      }

      $this->sendOutput();
    }
  }

  /**
   * Log an error so they can be displayed at the end
   *
   * @access   public
   * @param   integer   ID number
   * @param   string    Error
   * @return   void
   **/
  public function logError($id, $error)
  {
    $this->errors[] = "{$id}: {$error}";

    // log to file
    if ($FH = @fopen(DOC_IPS_ROOT_PATH . 'cache/converter_error_log_' . date('m_d_y') . '.cgi', 'a')) {
      @fwrite($FH, "{$id}: {$error}\n");
      @fclose($FH);
    }
  }

  /**
   * Loads the next cycle
   *
   * @access   public
   * @return   void
   **/
  public function next()
  {
    $total = $this->request['total'];
    $pc = round((100 / $total) * $this->end);
    $message = ($pc > 100) ? 'Finishing...' : "{$pc}% complete";
    IPSLib::updateSettings(array('conv_error' => serialize($this->errors)));
    $end = ($this->end > $total) ? $total : $this->end;
    $this->registry->output->html .= $this->registry->output->global_template->temporaryRedirect("{$this->settings['base_url']}app=convert&module={$this->app['sw']}&section={$this->app['app_key']}&do={$this->request['do']}&st={$this->end}&cycle={$this->request['cycle']}&total={$total}", "<strong>{$end} of {$total} converted</strong><br />{$message}<br /><br /><strong><a href='{$this->settings['base_url']}app=convert&module={$this->app['sw']}&section={$this->app['app_key']}&do={$this->request['do']}&st={$this->end}&cycle={$this->request['cycle']}&total={$total}'>Click here if you are not redirected.</a></strong>");
    $this->sendOutput();
  }

  /**
   * Test connect to external database
   *
   * @access   public
   * @param   array       Database details
   * @return   Error, or true on success
   **/
  public function test_connect($app)
  {
    if (!file_exists(IPS_KERNEL_PATH . 'classDb' . ucwords($app['db_driver']) . '.php')) {
      return 'Invalid driver';
    }

    require_once(IPS_KERNEL_PATH . 'classDb' . ucwords($app['db_driver']) . '.php');

    $classname = "db_driver_" . $app['db_driver'];

    $DB = new $classname;

    $DB->obj['sql_database'] = $app['db_db'];
    $DB->obj['sql_user'] = $app['db_user'];
    $DB->obj['sql_pass'] = $app['db_pass'];
    $DB->obj['sql_host'] = $app['db_host'];
    $DB->obj['sql_charset'] = $app['db_charset'];

    define('SQL_DRIVER', $app['db_driver']);
    define('IPS_MAIN_DB_CLASS_LOADED', TRUE);

    /* Required vars? */
    if (is_array($DB->connect_vars) and count($DB->connect_vars)) {
      foreach ($DB->connect_vars as $k => $v) {
        $DB->connect_vars[$k] = (isset($app[$k])) ? $app[$k] : ipsRegistry::$settings[$k];
      }
    }

    $DB->return_die = true;

    if (!$DB->connect()) {
      return $DB->error;
    } else {
      return true;
    }

  }


  /**
   * Return the tables that need to be truncated for a given action
   *
   * @abstract
   * @access  public
   * @param   string    action (e.g. 'members', 'forums', etc.)
   * @return   array     array('table' => 'id_field', ...)
   **/
//  public abstract function truncate($action);

  public function doExecute(ipsRegistry $registry)
  {

    $this->html = $this->registry->output->loadTemplate('cp_skin_convert');

    if (@file_put_contents(DOC_IPS_ROOT_PATH . 'cache/converter_lock.php', 'Just out of interest, what did you expect to see here?')) {
      $this->registry->output->html .= $this->html->convertError('The converters have been locked.');
    } else {
      $this->registry->output->html .= $this->html->convertError('The converters were <strong>NOT</strong> locked - you should uninstall the application and delete the admin/applications_addon/ips/convert folder.');
    }

    $this->sendOutput();
    exit;

  }
}