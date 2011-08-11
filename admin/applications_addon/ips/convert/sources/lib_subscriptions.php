<?php
/**
 * IPS Converters
 * Application Files
 * Library functions for IP.Subscriptions 1.0 conversions
 * Last Update: $Date: 2010-03-19 11:03:12 +0100(ven, 19 mar 2010) $
 * Last Updated By: $Author: terabyte $
 *
 * @package		IPS Converters
 * @author 		Mark Wade
 * @copyright	(c) 2009 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 437 $
 */
	class lib_subscriptions extends lib_master
	{
		/**
	     * Information box to display on convert screen
	     *
	     * @access	public
	     * @return	string 		html to display
	     */
		public function getInfo()
		{
			return "";
		}
						
		/**
		 * Return the information needed for a specific action
		 *
	     * @access	public
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @param	boolean		If true, will return false instead of printing error if encountered
		 * @return 	array 		info needed for html->convertMenuRow
		 **/
		public function menuRow($action='', $return=false)
		{
			switch ($action)
			{
				case 'subscription_currency':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'subscription_currency' ) );
					$return = array(
						'name'	=> 'Currencies',
						'rows'	=> $count['count'],
						'cycle'	=> 100,
					);
					break;
					
				case 'subscription_methods':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'subscription_methods' ) );
					$return = array(
						'name'	=> 'Gateways',
						'rows'	=> $count['count'],
						'cycle'	=> 100,
					);
					break;
					
				case 'subscriptions':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'subscriptions' ) );
					$return = array(
						'name'	=> 'Packages',
						'rows'	=> $count['count'],
						'cycle'	=> 50,
					);
					break;
					
				case 'subscription_trans':
					$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'subscription_trans' ) );
					$return = array(
						'name'	=> 'Transactions',
						'rows'	=> $count['count'],
						'cycle'	=> 1500,
					);
					break;
													
				default:
					if ($return)
					{
						return false;
					}
					$this->error("There is a problem with the converter: called invalid action {$action}");
					break;
			}
			
			$basic = array('section' => $this->app['app_key'], 'key' => $action, 'app' => 'subscriptions');
			return array_merge($basic, $return);
		}
				
		/**
		 * Return the tables that need to be truncated for a given action
		 *
	     * @access	public
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @return 	array 		array('table' => 'id_field', ...)
		 **/
		public function truncate($action)
		{
			switch ($action)
			{
				case 'subscription_currency':
					return array( 'subscription_currency' => 'subcurrency_code' );
					break;
					
				case 'subscription_methods':
					return array( 'subscription_methods' => 'submethod_id' );
					break;
					
				case 'subscriptions':
					return array( 'subscriptions' => 'sub_id' );
					break;
					
				case 'subscription_trans':
					return array( 'subscription_trans' => 'subtrans_id' );
					break;
					
				default:
					$this->error('There is a problem with the converter: bad truncate command');
					break;
			}
		}
		
		/**
		 * Database changes
		 *
		 * @access	public
		 * @param 	string		action (e.g. 'members', 'forums', etc.)
		 * @return 	array 		Details of change - array('type' => array(info))
		 **/
		public function databaseChanges($action)
		{
			return null;
		}
		
		/**
		 * Process report links
		 *
		 * @access	protected
		 * @param 	string		type (e.g. 'post', 'pm')
		 * @param 	array 		Data for reports_index table with foreign IDs
		 * @return 	array 		Processed data for reports_index table
		 **/
		protected function processReportLinks($type, $report)
		{
			return $report;
		}
		
		/**
		 * Convert a currency
		 *
		 * @access	public
		 * @param 	array 		Data to insert to table
		 * @param 	string 		How to handle duplicates ('local' or 'remote')
		 * @return 	boolean		Success or fail
		 **/
		public function convertCurrency($info, $dupes)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$info['subcurrency_code'])
			{
				$this->logError($id, 'No code provided');
				return false;
			}
			if (!$info['subcurrency_desc'])
			{
				$this->logError($id, 'No description provided');
				return false;
			}
			if (!$info['subcurrency_exchange'])
			{
				$this->logError($id, 'No rate provided');
				return false;
			}
			
			//-----------------------------------------
			// Handle duplicates
			//-----------------------------------------
			
			$dupe = $this->DB->buildAndFetch( array( 'select' => 'subcurrency_code', 'from' => 'subscription_currency', 'where' => "subcurrency_code = '{$info['subcurrency_code']}'" ) );
			if ($dupe)
			{
				if ($dupes == 'local')
				{
					return false;
				}
				else
				{
					$this->DB->delete('subscription_currency', "subcurrency_code={$dupe['subcurrency_code']}");
				}
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			$this->DB->insert( 'subscription_currency', $info );
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($info['subcurrency_code'], $id, 'subscription_currency');
			
			return true;
		}
		
		/**
		 * Convert Gateway
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @param 	string 		How to handle duplicates ('local' or 'remote')
		 * @return 	boolean		Success or fail
		 **/
		public function convertGateway($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['submethod_title'])
			{
				$this->logError($id, 'No name provided');
				return false;
			}
			if (!$info['submethod_name'])
			{
				$this->logError($id, 'No code provided');
				return false;
			}
			if (!$info['submethod_use_currency'])
			{
				$this->logError($id, 'No currency provided');
				return false;
			}
			
			//-----------------------------------------
			// Handle duplicates
			//-----------------------------------------
			
			$dupe = $this->DB->buildAndFetch( array( 'select' => 'submethod_id', 'from' => 'subscription_methods', 'where' => "submethod_name = '{$info['submethod_name']}'" ) );
			if ($dupe)
			{
				$this->addLink($dupe['submethod_id'], $id, 'subscription_methods');
				return false;
			}
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			unset($info['submethod_id']);
			$this->DB->insert( 'subscription_methods', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'subscription_methods');
			
			return true;
		}
		
		
		/**
		 * Convert a package
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertPackage($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['sub_title'])
			{
				$this->logError($id, 'No title provided');
				return false;
			}
			if (!$info['sub_cost'])
			{
				$this->logError($id, 'No cost provided');
				return false;
			}
			
			//-----------------------------------------
			// Link
			//-----------------------------------------
			
			$info['sub_new_group'] = ($info['sub_new_group']) ? $this->getLink($info['sub_new_group'], 'groups', false, true) : 0;
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			unset($info['sub_id']);
			$this->DB->insert( 'subscriptions', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'subscriptions');
			
			return true;
		}
		
		/**
		 * Convert extra info
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertPackageExtra($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, '(EXTRA) No ID number provided');
				return false;
			}
			if (!$info['subextra_sub_id'])
			{
				$this->logError($id, '(EXTRA) No package ID provided');
				return false;
			}
			
			//-----------------------------------------
			// Link
			//-----------------------------------------
			
			$info['subextra_sub_id'] = $this->getLink($info['subextra_sub_id'], 'subscriptions');
			$info['subextra_method_id'] = $this->getLink($info['subextra_method_id'], 'subscription_methods');
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			unset($info['subextra_id']);
			$this->DB->insert( 'subscription_extra', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'subscription_extra');
			
			return true;
		}
		
		/**
		 * Convert transaction
		 *
		 * @access	public
		 * @param 	integer		Foreign ID number
		 * @param 	array 		Data to insert to table
		 * @return 	boolean		Success or fail
		 **/
		public function convertTransaction($id, $info)
		{	
			//-----------------------------------------
			// Make sure we have everything we need
			//-----------------------------------------
			
			if (!$id)
			{
				$this->logError($id, 'No ID number provided');
				return false;
			}
			if (!$info['subtrans_sub_id'])
			{
				$this->logError($id, 'No package ID provided');
				return false;
			}
			if (!$info['subtrans_member_id'])
			{
				$this->logError($id, 'No member ID provided');
				return false;
			}
			if (!$info['subtrans_start_date'])
			{
				$this->logError($id, 'No start date provided');
				return false;
			}
			if (!$info['subtrans_end_date'])
			{
				$this->logError($id, 'No end date provided');
				return false;
			}
			if (!$info['subtrans_state'])
			{
				$this->logError($id, 'No state provided');
				return false;
			}
			
			//-----------------------------------------
			// Link
			//-----------------------------------------
			
			$info['subtrans_sub_id'] = $this->getLink($info['subtrans_sub_id'], 'subscriptions');
			$info['subtrans_member_id'] = $this->getLink($info['subtrans_member_id'], 'members', false, true);
			$info['subtrans_old_group'] = ($info['subtrans_old_group']) ? $this->getLink($info['subtrans_old_group'], 'groups', false, true) : 0;
			
			//-----------------------------------------
			// Insert
			//-----------------------------------------
			
			unset($info['subtrans_id']);
			$this->DB->insert( 'subscription_trans', $info );
			$inserted_id = $this->DB->getInsertId();
			
			//-----------------------------------------
			// Add link
			//-----------------------------------------
			
			$this->addLink($inserted_id, $id, 'subscription_trans');
			
			return true;
		}
		
	
	}
	
?>