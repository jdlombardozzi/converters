<?php
/**
 * IPS Converters
 * IP.Subscriptions 1.0 Converters
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
		'name'	=> 'vBulletin 3.8',
		'login'	=> false,
	);

	$parent = array('required' => true, 'choices' => array(
		array('app' => 'board', 'key' => 'vbulletin_legacy', 'newdb' => false),
		array('app' => 'board', 'key' => 'vbulletin', 'newdb' => false),
		));

	class admin_convert_subscriptions_vbulletin extends ipsCommand
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
				'subscriptions'			=> array('groups'),
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
				case 'subscriptions':
					return $this->lib->countRows('subscription');
					break;

				case 'subscription_trans':
					return $this->lib->countRows('subscriptionlog');
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
							'from' 		=> 'subscription',
							'order'		=> 'subscriptionid ASC',
						);

			$loop = $this->lib->load('subscriptions', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{

				// Get local stuff
				$default_currency = $this->DB->buildAndFetch( array( 'select' => 'subcurrency_code', 'from' => 'subscription_currency', 'where' => "subcurrency_default=1" ) );
				$tco = $this->DB->buildAndFetch( array( 'select' => 'submethod_id', 'from' => 'subscription_methods', 'where' => "submethod_name='2checkout'" ) );

				// Get remote stuff
				$title = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'text', 'from' => 'phrase', 'where' => "varname = 'sub{$row['subscriptionid']}_title'" ) );
				$desc = ipsRegistry::DB('hb')->buildAndFetch( array( 'select' => 'text', 'from' => 'phrase', 'where' => "varname = 'sub{$row['subscriptionid']}_desc'" ) );

				// Loop through costs
				$costs = unserialize($row['cost']);

				foreach ($costs as $cost)
				{
					// Check we have a matching currency
					if (!in_array(strtolower($default_currency['subcurrency_code']), array_keys($cost['cost'])))
					{
						$this->lib->logError($row['subscriptionid'], "No price for default currnecy ({$default_currency['subcurrency_code']})");
						continue 2;
					}

					// Save to subscriptions
					$save = array(
						'sub_title'			=> $title['text'],
						'sub_desc'			=> $desc['text'],
						'sub_new_group'		=> $row['nusergroupid'],
						'sub_length'		=> $cost['length'],
						'sub_unit'			=> strtolower($cost['units']),
						'sub_cost'			=> $cost['cost'][strtolower($default_currency['subcurrency_code'])],
						);

					$this->lib->convertPackage($row['subscriptionid'], $save);

					// 2CO Product ID?
					if ($cost['twocheckout_prodid'] and $tco)
					{
						$extra = array(
							'subextra_sub_id'		=> $row['subscriptionid'],
							'subextra_method_id'	=> $tco['submethod_id'],
							'subextra_product_id'	=> $cost['twocheckout_prodid'],
							);
						$this->lib->convertPackageExtra($row['subscriptionid'], $extra);
					}

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
							'from' 		=> 'subscriptionlog',
							'order'		=> 'subscriptionlogid ASC',
						);

			$loop = $this->lib->load('subscription_trans', $main);

			//---------------------------
			// Loop
			//---------------------------

			while ( $row = ipsRegistry::DB('hb')->fetch($this->lib->queryRes) )
			{
				$cost = $this->DB->buildAndFetch( array( 'select' => 'sub_cost', 'from' => 'subscriptions', 'where' => "sub_id={$this->lib->getLink($row['subscriptionid'], 'subscriptions')}" ) );

				$save = array(
					'subtrans_sub_id'		=> $row['subscriptionid'],
					'subtrans_member_id'	=> $row['userid'],
					'subtrans_old_group'	=> $row['pusergroupid'],
					'subtrans_paid'			=> $cost['sub_cost'],
					'subtrans_cumulative'	=> $cost['sub_cost'],
					'subtrans_start_date'	=> $row['regdate'],
					'subtrans_end_date'		=> $row['expirydate'],
					'subtrans_state'		=> $row['status'] ? 'paid' : 'expired',
					);


				$this->lib->convertTransaction($row['subscriptionlogid'], $save);
			}

			$this->lib->next();

		}

	}

