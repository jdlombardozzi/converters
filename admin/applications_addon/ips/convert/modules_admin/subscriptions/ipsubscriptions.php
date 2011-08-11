<?php
/**
 * IPS Converters
 * IP.Subscriptions 1.0 Converters
 * IP.Subscriptions Merge Tool
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
		'key'	=> 'ipsubscriptions',
		'name'	=> 'IP.Subscriptions 1.0',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'ipboard', 'newdb' => false),
		));

	class admin_convert_subscriptions_ipsubscriptions extends ipsCommand
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
				'subscription_currency'	=> array(),
				'subscription_methods'	=> array('subscription_currency'),
				'subscriptions'			=> array('groups', 'subscription_methods'),
				'subscription_trans'	=> array('subscriptions', 'members', 'groups'),
				);

			//-----------------------------------------
	        // Load our libraries
	        //-----------------------------------------

			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_master.php' );
			require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/lib_subscriptions.php' );
			$this->lib =  new lib_subscriptions( $registry, $html, $this );

	        $this->html = $this->registry->output->loadTemplate( 'cp_skin_convert' );
			$this->lib->sendHeader( 'IP.Subscriptions Merge Tool' );

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
				case 'subscription_currency':
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
		 * Convert Currencies
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_subscription_currency()
		{

			//-----------------------------------------
			// Were we given more info?
			//-----------------------------------------

			$this->lib->saveMoreInfo('subscription_currency', array('subscription_currency_opt'));

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'subscription_currency',
						);

			$loop = $this->lib->load('subscription_currency', $main);

			//-----------------------------------------
			// We need to know what do do with duplicates
			//-----------------------------------------

			$this->lib->getMoreInfo('subscription_currency', $loop, array('subscription_currency_opt'  => array('type' => 'dupes', 'label' => 'How do you want to handle duplicate currencies?')));

			$get = unserialize($this->settings['conv_extra']);
			$us = $get[$this->lib->app['name']];

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertCurrency($row, $us['subscription_currency_opt']);
			}

			$this->lib->next();

		}

		/**
		 * Convert Gateways
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_subscription_methods()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'subscription_methods',
							'order'		=> 'submethod_id ASC',
						);

			$loop = $this->lib->load('subscription_methods', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertGateway($row['submethod_id'], $row);
			}

			$this->lib->next();

		}

		/**
		 * Convert Packages
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_subscriptions()
		{

			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'subscriptions',
							'order'		=> 'sub_id ASC',
						);

			$loop = $this->lib->load('subscriptions', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertPackage($row['sub_id'], $row);

				// And the extras
				ipsRegistry::DB('hb')->build(array('select' => '*', 'from' => 'subscription_extra', 'where' => "subextra_sub_id={$row['sub_id']}"));
				ipsRegistry::DB('hb')->execute();
				while ($extra = ipsRegistry::DB('hb')->fetch())
				{
					$this->lib->convertPackageExtra($extra['subextra_id'], $extra);
				}
			}

			$this->lib->next();

		}

		/**
		 * Convert Transactions
		 *
		 * @access	private
		 * @return void
		 **/
		private function convert_subscription_trans()
		{
			//---------------------------
			// Set up
			//---------------------------

			$main = array(	'select' 	=> '*',
							'from' 		=> 'subscription_trans',
							'order'		=> 'subtrans_id ASC',
						);

			$loop = $this->lib->load('subscription_trans', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$this->lib->convertTransaction($row['subtrans_id'], $row);
			}

			$this->lib->next();

		}

	}

