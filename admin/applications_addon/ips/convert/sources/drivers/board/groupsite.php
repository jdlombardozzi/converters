<?php

$info = array( 'key'	=> 'groupsite',
			   'name'	=> 'Group Site',
			   'login'	=> FALSE,
                   'nodb' => TRUE );

$custom = array( 'members' => 'Input path to membership xml export',
                 'discussions' => 'Input path to discussions xml export' );

class admin_convert_board_groupsite extends ipsCommand
{
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// What can this thing do?
		//-----------------------------------------
    $this->actions = array( 'pfields'		=> array(),
                            'members'	=> array('pfields'),
                            'forums'	=> array('members'),
                            'topics'	=> array('forums'),
                            'posts'		=> array('topics') );

		//-----------------------------------------
	  // Load our libraries
	  //-----------------------------------------
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
		require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_board.php' );
		$this->lib =  new lib_board( $registry, $html, $this );

	  $this->html = $this->lib->loadInterface();
		$this->lib->sendHeader( 'Group site &rarr; IP.Board Converter' );

		$us = unserialize($this->settings['conv_extra']);
		$this->paths = $us[$this->lib->app['name']]['core'];

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

  public function sendOutput() {
    $this->registry->output->html .= $this->html->convertFooter();
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
		exit;
  }

	public function checkConf($action)
	{
		switch ($action)
		{
			case 'members':
				return TRUE;
				break;

			default:
				return FALSE;
				break;
		}
	}

	public function countRows($action)
	{
		switch ($action)
		{
			case 'members':
				return count($this->_parse_members_xml());
				break;

			case 'forums':
				$discussions = $this->_parse_discussions_xml();

        $forums = 0;
        foreach ( $discussions->childNodes as $node ) {
          if ( $node->nodeName != 'forum' ) { continue; }

          $forums += 1;
        }
        return (int)$forums;

				break;

			case 'topics':
				$discussions = $this->_parse_discussions_xml();

        $topics = 0;
        foreach ( $discussions->childNodes as $node ) {
          if ( $node->nodeName != 'forum' ) { continue; }

          foreach ( $node->childNodes as $forumChildNode ) {
            if ( $forumChildNode->nodeName != 'topics' ) { continue; }

            foreach ( $forumChildNode->childNodes as $topicChildNode ) {
              if ( $topicChildNode->nodeName != 'topic' ) { continue; }
              $topics += 1;
            }
          }
        }
        return (int)$topics;

				break;

			case 'posts':
				$discussions = $this->_parse_discussions_xml();

        $posts = 0;
        foreach ( $discussions->childNodes as $node ) {
          if ( $node->nodeName != 'forum' ) { continue; }

          foreach ( $node->childNodes as $forumChildNode ) {
            if ( $forumChildNode->nodeName != 'topics' ) { continue; }

            foreach ( $forumChildNode->childNodes as $topicsChildNode ) {
              if ( $topicsChildNode->nodeName != 'topic' ) { continue; }

              $posts += 1;

              foreach ( $topicsChildNode->childNodes as $topicChildNode ) {
                if ( $topicChildNode->nodeName != 'replies' ) { continue; }

                $nodes = array();
                foreach ( $topicChildNode->childNodes as $repliesChildNode ) {
                  if ( $repliesChildNode->nodeName != 'reply' ) { continue; }
                  $posts += 1;
                }
                if ( count($nodes) > 1 ) { var_dump($nodes);exit;}
              }
            }
          }
        }
        return (int)$posts;

        break;

			default:
				return 0;
				break;
		}
	}

  private function convert_pfields() {
    $custom_fields = array( 'first_name', 'last_name', 'company', 'addresses/professional_address/city', 'addresses/personal_address/city', 'phone' );

    $loop = $this->lib->load('pfields', FALSE, array('pfields_groups'));

    $get = unserialize($this->settings['conv_extra']);
    $us = $get[$this->lib->app['name']];
    if (!$this->request['st'])
    {
      $us['pfield_group'] = null;
      IPSLib::updateSettings(array('conv_extra' => serialize($us)));
    }

    //-----------------------------------------
    // Do we have a group
    //-----------------------------------------
    if (!$us['pfield_group'])
    {
      $group = $this->lib->convertPFieldGroup(1, array('pf_group_name' => 'Converted', 'pf_group_key' => 'groupsite'), true);
      if (!$group)
      {
        $this->lib->error('There was a problem creating the profile field group');
      }
      $us['pfield_group'] = $group;
      $get[$this->lib->app['name']] = $us;
      IPSLib::updateSettings(array('conv_extra' => serialize($get)));
    }

    $members_xml = $this->_load_members_xml();
		//---------------------------
		// Loop
		//---------------------------
    while ($members_xml->read() && $members_xml->name !== 'group_q_and_a' );

    // Set record as a DOMElement
    $group_q_and_a = $members_xml->expand();

    $questions = $group_q_and_a->getElementsByTagName('question');

    foreach ( $questions as $question ) {
      $value = $question->nodeValue;

      $answers = $question->getElementsByTagName('answer');
      if ( $answers ) {
        for($i=0; $i<$answers->length; $i++) {
          $value = str_replace($answers->item($i)->nodeValue, '', $value);
        }
      }

      $custom_fields[] = trim($value);
    }

    foreach( $custom_fields as $field )
    {
      $data = array( 'pf_type'		=> 'input',
                     'pf_title'  => $field,
                     'pf_member_hide'	=> 0,
                     'pf_member_edit'	=> 1,
                     'pf_key' => $field,
                     'pf_group_id' => 1 );

      $this->lib->convertPField($field, $data);
    }

		// Save that it's been completed
		$get = unserialize($this->settings['conv_completed']);
		$us = $get[$this->lib->app['name']];
		$us = is_array($us) ? $us : array();
		if (empty($this->lib->errors))
		{
			$us = array_merge($us, array('pfields' => true));
		}
		else
		{
			$us = array_merge($us, array('pfields' => 'e'));
		}
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_completed' => serialize($get)));

		// Display
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
		$info = $this->lib->menuRow('pfields');

		$this->registry->output->html .= $this->lib->html->convertComplete($info['pfields'].' Conversion Complete.', array($es, $info['finish']));
		$this->sendOutput();
  }

  private function convert_members()
	{
		//-----------------------------------------
		// Were we given more info?
		//-----------------------------------------
		$pcpf = array( 'gender'			=> 'Gender',
                   'first_name'			=> 'First Name',
                   'last_name'			=> 'Last Name',
                   'company'			=> 'Company',
                   'website'   => 'Website',
                   'bio' => 'Bio',
                   'interests' => 'Interests',
                   'skills' => 'Skills' );

		$this->lib->saveMoreInfo( 'members', array_keys($pcpf) );

		//---------------------------
		// Set up
		//---------------------------
		$loop = $this->lib->load('members', FALSE);

		//-----------------------------------------
		// Tell me what you know!
		//-----------------------------------------
		$get = unserialize($this->settings['conv_extra']);
		$us = $get[$this->lib->app['name']];
		$ask = array();

		// And those custom profile fields
		$options = array('x' => '-Skip-');
		$this->DB->build(array('select' => '*', 'from' => 'pfields_data'));
		$fieldRes = $this->DB->execute();
		while ($row = $this->DB->fetch($fieldRes))
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

		$members_xml = $this->_load_members_xml();
		//---------------------------
		// Loop
		//---------------------------
    while ($members_xml->read() && $members_xml->name !== 'user' );

    while ($members_xml->name === 'user') {
      // Set record as a DOMElement
      $user = $members_xml->expand();

      // Grab email nodes
      $email_nodes = $user->getElementsByTagName('email');
      $email = null;
      // If we have 1 email, then use it

      if ( $email_nodes->length == 1 ) {
        $email = $email_nodes->item(0)->nodeValue;
      } elseif ( $email_nodes->length > 1 ) {
        // We have more than one email, use first one
        $email = $email_nodes->item(0)->nodeValue;

        // If email is not primary, check the others
        if ( $email_nodes->item(0)->attributes->getNamedItem('primary')->value != 'true' ) {
          // Check if a later one is primary
          for ( $i=1; $i<$email_nodes->length; $i++ ) {
            if ( $email_nodes->item($i)->attributes->getNamedItem('primary')->value == 'true' ) {
              $email = $email_nodes->item($i)->nodeValue;
            }
          }
        }
      } else {
        print 'No emails';exit;
      }

			//-----------------------------------------
			// Set info
			//-----------------------------------------

			// Basic info
			$info = array( 'id' => $user->getAttribute('id'),
							'username'     	 	=> $email,
							'email'			 	=>  $email,
							'displayname' => $user->getElementsByTagName('first_name')->item(0)->nodeValue,
							'joined'			=> strtotime($user->getElementsByTagName('date_joined')->item(0)->nodeValue),
							'password'		 => md5(microtime()) );

			// Member info
			$members = array( 'title'				=> $user->getElementsByTagName('title')->item(0)->nodeValue,
                        'posts'				=> 0,
                        'time_offset'		=> 0,
                        'last_visit' => $user->getElementsByTagName('last_online')->item(0)->nodeValue,
                        'last_activity' => $user->getElementsByTagName('last_online')->item(0)->nodeValue,
                        'ip_address'		=> '127.0.0.1' );

			//-----------------------------------------
			// Custom Profile fields
			//-----------------------------------------
			foreach ($pcpf as $id => $name)
			{
        if ($id == 'gender')
				{
					switch ($user->getElementsByTagName($id)->item(0)->nodeValue) {
						case 'Male':
							$user->getElementsByTagName($id)->item(0)->nodeValue = 'm';
							break;

						case 'Female':
							$user->getElementsByTagName($id)->item(0)->nodeValue = 'f';
							break;

						default:
							$user->getElementsByTagName($id)->item(0)->nodeValue = 'u';
							break;
					}
        }

				if ($us[$id] != 'x')
				{
					$custom['field_'.$us[$id]] = $user->getElementsByTagName($id)->item(0)->nodeValue;
				}
			}

      // Prepare question and answer fields to be used for custom fields
      $question_and_answers = array();

      $questions = $user->getElementsByTagName('question');

      foreach ( $questions as $question ) {
        $value = $question->nodeValue;
        $values = array();

        $answers = $question->getElementsByTagName('answer');
        if ( $answers ) {
          for($i=0; $i<$answers->length; $i++) {
            $value = str_replace($answers->item($i)->nodeValue, '', $value);
            $values[] = $answers->item($i)->nodeValue;
          }

          $question_and_answers[trim($value)] = implode(', ', $values);
        }
      }

      // Actual
      foreach ($pfields as $field)
      {
        $user_field = $user->getElementsByTagName($field['pf_title']);
        // First level custom fields
        if ( $user_field->length > 0 ) {
          $custom['field_'.$field['pf_id']] = $user_field->item(0)->nodeValue;
          continue;
        }

        // Check question and answers
        $custom['field_'.$field['pf_id']] = $question_and_answers[$field['pf_title']];
      }

			//-----------------------------------------
			// Go
			//----------------------------------------
			$this->lib->convertMember($info, $members, array(), $custom );
      $members_xml->next('user');
		}

		// Save that it's been completed
		$get = unserialize($this->settings['conv_completed']);
		$us = $get[$this->lib->app['name']];
		$us = is_array($us) ? $us : array();
		if (empty($this->lib->errors))
		{
			$us = array_merge($us, array('members' => true));
		}
		else
		{
			$us = array_merge($us, array('members' => 'e'));
		}
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_completed' => serialize($get)));

		// Display
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
		$info = $this->lib->menuRow('members');

		$this->registry->output->html .= $this->lib->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
		$this->sendOutput();
	}

  private function convert_forums()
	{
		//---------------------------
		// Set up
		//---------------------------
		$loop = $this->lib->load('forums', FALSE);

    $xml = $this->_load_discussions_xml();

		//---------------------------
		// Loop
		//---------------------------
    while ($xml->read() && $xml->name !== 'forums' );

    $position=1;

    // Convert categories
    while ($xml->name === 'forums') {
      // Set record as a DOMElement
      $category = $xml->expand();

      $save = array( 'topics'			=> 0,
                     'posts'			  	=> 0,
                     'parent_id'		  	=> '-1',
                     'name'			  	=> $category->getAttribute('group'),
                     'position'		  	=> $position,
                     'use_ibc'		  	=> 1,
                     'use_html'		  	=> 0,
                     'status'			=> 1,
                     'inc_postcount'	  	=> 1,
                     'sub_can_post'		=> 0,
                     'redirect_on'		=> 0 );

      $this->lib->convertForum( addslashes($category->getAttribute('group')), $save, array());

      $forums = $category->getElementsByTagName('forum');

      if ( $forums != null ) {
       foreach ( $forums as $index => $forum ) {
        $save = array ( 'topics' => 0, 'posts' => 0,
                        'parent_id' => addslashes($category->getAttribute('group')),
                        'name' => $forum->getAttribute('name'),
                        'description' => $forum->getElementsByTagName('description')->item(0)->nodeValue,
                        'position' => (int)$index,
                        'use_ibc' => 1, 'use_html' => 0, 'status' => 1, 'inc_postcount' => 1, 'sub_can_post' => 1, 'redirect_on' => 0);

        $this->lib->convertForum( $forum->getAttribute('id'), $save, array());
       }
      }

      $xml->next('forums');
      $position++;
    }

		// Save that it's been completed
		$get = unserialize($this->settings['conv_completed']);
		$us = $get[$this->lib->app['name']];
		$us = is_array($us) ? $us : array();
		if (empty($this->lib->errors))
		{
			$us = array_merge($us, array('forums' => true));
		}
		else
		{
			$us = array_merge($us, array('forums' => 'e'));
		}
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_completed' => serialize($get)));

		// Display
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
		$info = $this->lib->menuRow('forums');

		$this->registry->output->html .= $this->lib->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
		$this->sendOutput();
  }

	private function convert_topics()
	{
		//---------------------------
		// Set up
		//---------------------------
		$loop = $this->lib->load('topics', FALSE);

    $xml = $this->_load_discussions_xml();

		//---------------------------
		// Loop
		//---------------------------
    while ($xml->read() && $xml->name !== 'forum' );

    while ($xml->name === 'forum') {
      // Set record as a DOMElement
      $forum = $xml->expand();

      $forum_id = $forum->getAttribute('id');

      $topics = $forum->getElementsByTagName('topic');

      if ( $topics != null ) {
        foreach ( $topics as $topic ) {
          $save = array( 'title'		  => $topic->getAttribute('title'),
                   'state'		  => 'open',
                   'posts'		  => 0,
                   'starter_id'   => $topic->getElementsByTagName('author')->item(0)->getAttribute('id'),
                   'starter_name' => $topic->getElementsByTagName('author')->item(0)->nodeValue,
                   'start_date'	  => strtotime($topic->getAttribute('created_at')),
                   'poll_state'	  => 0,
                   'views'		  => 0,
                   'forum_id'	  => $forum_id,
                   'approved'	  => 1,
                   'pinned'		  => $topic->getAttribute('highlighted') == 'true' ? 1 : 0 );
          $this->lib->convertTopic($topic->getAttribute('id'), $save);
        }
      }
      $xml->next('forum');
    }

		// Save that it's been completed
		$get = unserialize($this->settings['conv_completed']);
		$us = $get[$this->lib->app['name']];
		$us = is_array($us) ? $us : array();
		if (empty($this->lib->errors))
		{
			$us = array_merge($us, array('topics' => true));
		}
		else
		{
			$us = array_merge($us, array('topics' => 'e'));
		}
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_completed' => serialize($get)));

		// Display
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
		$info = $this->lib->menuRow('topics');

		$this->registry->output->html .= $this->lib->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
		$this->sendOutput();
  }

	private function convert_posts()
	{
		//---------------------------
		// Set up
		//---------------------------
		$loop = $this->lib->load('posts', FALSE);

    $xml = $this->_load_discussions_xml();

		//---------------------------
		// Loop
		//---------------------------
    while ($xml->read() && $xml->name !== 'forum' );

    while ($xml->name === 'forum') {
      // Set record as a DOMElement
      $forum = $xml->expand();

      $topics = $forum->getElementsByTagName('topic');

      if ( $topics == null ) { continue; }

      foreach ( $topics as $topic ) {
        $topic_id = $topic->getAttribute('id');

        // Convert topic first post
        $save = array( 'author_id' => $topic->getElementsByTagName('author')->item(0)->getAttribute('id'),
                 'author_name' 	=> $topic->getElementsByTagName('author')->item(0)->nodeValue,
                 'use_sig'     	=> 1,
                 'use_emo'     	=> 1,
                 'ip_address' 	=> '127.0.0.1',
                 'post_date'   	=> strtotime($topic->getAttribute('created_at')),
                 'post'		 	=> $this->fixPostData($topic->getElementsByTagName('content')->item(0)->nodeValue),
                 'queued'      	=> 0,
                 'topic_id'    	=> $topic_id );

        $this->lib->convertPost($topic_id, $save);



        $replies = $topic->getElementsByTagName('reply');

        if ( $replies != null ) {
          foreach ( $replies as $reply ) {
            $save = array( 'author_id'		=> $reply->getElementsByTagName('author')->item(0)->getAttribute('id'),
                     'author_name' 	=> $reply->getElementsByTagName('author')->item(0)->nodeValue,
                     'use_sig'     	=> 1,
                     'use_emo'     	=> 1,
                     'ip_address' 	=> '127.0.0.1',
                     'post_date'   	=> strtotime($reply->getAttribute('created_at')),
                     'post'		 	=> $this->fixPostData($reply->getElementsByTagName('content')->item(0)->nodeValue),
                     'queued'      	=> 0,
                     'topic_id'    	=> $topic_id );

            $this->lib->convertPost($reply->getAttribute('id'), $save);
          }
        }
      }
      $xml->next('forum');
    }

		// Save that it's been completed
		$get = unserialize($this->settings['conv_completed']);
		$us = $get[$this->lib->app['name']];
		$us = is_array($us) ? $us : array();
		if (empty($this->lib->errors))
		{
			$us = array_merge($us, array('posts' => true));
		}
		else
		{
			$us = array_merge($us, array('posts' => 'e'));
		}
		$get[$this->lib->app['name']] = $us;
		IPSLib::updateSettings(array('conv_completed' => serialize($get)));

		// Display
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
		$info = $this->lib->menuRow('posts');

		$this->registry->output->html .= $this->lib->html->convertComplete($info['name'].' Conversion Complete.', array($es, $info['finish']));
		$this->sendOutput();
	}

  private function _load_members_xml() {
    if ( $this->parsed_members_xml != null ) { return $this->parsed_members_xml; }

    $reader = new XMLReader();
    if(!$reader->open($this->paths['members'])){ print "can't open file";}

    return $this->parsed_members_xml = $reader;
  }

  private function _load_discussions_xml() {
    if ( $this->parsed_discussions_xml != null ) { return $this->parsed_members_xml; }

    $reader = new XMLReader();
    if(!$reader->open($this->paths['discussions'])){ print "can't open file";}

    return $this->parsed_discussions_xml = $reader;
  }

	private function _parse_members_xml()
	{
    if ( $this->parsed_members_xml != null ) { return $this->parsed_members_xml; }
    
    $reader = new XMLReader();
    if(!$reader->open($this->paths['members'])){ print "can't open file";}

    while ($reader->read() && $reader->name !== 'user' );

    $users = array();
    while ($reader->name === 'user') {
      $user = $reader->expand();
      $node_array = array();

      foreach ( $user->attributes as $attribute ) {
        $node_array[$attribute->name] = $attribute;
      }

      foreach( $user->childNodes as $node ) {
        $node_array[$node->nodeName] = $node;
      }

      $users[$node_array['id']->value] = $node_array;
      $reader->next('user');
    }

    $reader->close();

		// Returns an array of member records
    $this->parsed_members_xml = $users;
		return $this->parsed_members_xml;
	}

	private function _parse_discussions_xml()
	{
    if ( $this->parsed_discussions_xml != null ) { return $this->parsed_discussions_xml; }

    $reader = new XMLReader();
    if(!$reader->open($this->paths['discussions'])){ print "can't open file";}

    while ($reader->read() && $reader->name !== 'forums' );

		// Returns an array of member records
    $this->parsed_discussions_xml = $reader->expand();

    $reader->close();

		return $this->parsed_discussions_xml;
	}

	private function fixPostData($text)
		{
			$text = trim($text);

			$text = stripslashes($text);

			$text = str_replace("<p>", "", $text);
			$text = str_replace("</p>", "<br />", $text);
//			$text = str_replace("<br />", "\r", $text);

      // Sort out newlines
		  $text = nl2br($text);

			# Tags to Find
			$htmltags = array(
				'/\<b\>(.*?)\<\/b\>/is',
        '/\<span style=\"text-decoration: underline;\"\>(.*?)\<\/span\>/is',
        '/\<span style=\"font-size: small;\"\>(.*?)\<\/span\>/is',
        '/\<span style=\"color: #(.*?);\"\>(.*?)\<\/span\>/is',
        '/\<img  src=\"(.*?)\"(.*?)\/\>/is',
				'/\<em\>(.*?)\<\/em\>/is',
				'/\<u\>(.*?)\<\/u\>/is',
				'/\<li\>(.*?)\<\/li\>/is',
				'/\<img(.*?) src=\"(.*?)\" (.*?)\>/is',
				'/\<blockquote\>(.*?)\<\/blockquote\>/is',
				'/\<strong\>(.*?)\<\/strong\>/is',
				'/\<a href=\"(.*?)\"(.*?)\>(.*?)\<\/a\>/is',
        '/\<ol\>(.*?)\<\/ol\>/is',
        '/\<ul\>(.*?)\<\/ul\>/is',
			);

			# Replace with
			$bbtags = array(
				'[b]$1[/b]',
        '[u]$1[/u]',
        '[size=1]$1[/size]',
        '[color=#$1]$2[/color]',
        '[img]$1[/img]',
				'[i]$1[/i]',
				'[list]$1[/list]',
				'[*]$1',
				'[img]$2[/img]',
				'[quote]$1[/quote]',
				'[b]$1[/b]',
				'[url=$1]$3[/url]',
        '[list]$1[/list]'
			);

			# Replace $htmltags in $text with $bbtags
			$text = preg_replace($htmltags, $bbtags, $text);

			return $text;
		}
}