<?php
/**
 * IPS Converters
 * Application Files
 * Library functions for IP.Nexus 1.2 conversions
 * Last Update: $Date: 2011-06-27 23:48:37 +0100 (Mon, 27 Jun 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Andrew Millne / Ryan Ashbrook
 * @copyright	(c) 2011 Invision Power Services, Inc.
 * @version		$Revision: 543 $
 */

class lib_nexus extends lib_master
{
	/**
     * Information box to display on convert screen
     *
     * @access	public
     * @return	string 		html to display
     */
	public function getInfo()
	{
		return "<strong>Rebuild Content</strong><br />
				<a href='{$this->settings['base_url']}&app=core&module=tools&section=rebuild&do=rebuild_overview' target='_blank'>Click here</a> and run the following tools in the order given:
				<ul>
					<li>Recount Statistics</li>
					<li>Rebuild Attachment Thumbnails</li>
					<li>Rebuild Profile Photo Thumbnails</li>
				</ul><br />
				<strong>Rebuild Caches</strong><br />
				<a href='{$this->settings['base_url']}&app=core&&module=tools&section=cache' target='_blank'>Click here</a> and recache all.";
	}

	/**
	 * Return the information needed for a specific action
	 *
     * @access	public
	 * @param 	string		action (e.g. 'members', 'forums', etc.)
	 * @return 	array 		info needed for html->convertMenuRow
	 **/
	public function menuRow($action='', $return=false)
	{
		switch ($action)
		{
			case 'members':
				$count = $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'members' ) );
				$return = array (
					'name'	=> 'Members',
					'rows'	=> $count['count'],
					'cycle'	=> 250,
				);
			break;

			case 'groups':
				$count = $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'groups' ) );
				$return = array (
					'name'	=> 'Member Groups',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
			break;

			case 'forum_perms':
				$count = $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'forum_perms' ) );
				$return = array (
					'name'	=> 'Permission Sets',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
			break;
			
			case 'nexus_attachments':
				$count = $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'attachments', 'attach_rel_module = \'support\'' ) );
				$return = array (
					'name'	=> 'Attachments',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
			break;
			
			case 'nexus_gateways':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'nexus_gateways' ) );
				$return = array(
					'name'	=> 'Payment Gateways',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;
								
			case 'nexus_package_groups':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'nexus_package_groups' ) );
				$return = array(
					'name'	=> 'Package Groups',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;
					
			case 'nexus_packages':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'nexus_packages' ) );
				$return = array(
					'name'	=> 'Packages',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;

			case 'nexus_purchases':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'nexus_purchases' ) );
				$return = array(
					'name'	=> 'Purchases',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;
					
			case 'nexus_invoices':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'nexus_invoices' ) );
				$return = array(
					'name'	=> 'Invoices',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;

			case 'nexus_support_departments':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'nexus_support_departments' ) );
				$return = array(
					'name'	=> 'Support Departments',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;
					
			case 'nexus_support_statuses':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'nexus_support_statuses' ) );
				$return = array(
					'name'	=> 'Support Statuses',
					'rows'	=> $count['count'],
					'cycle'	=> 100,
				);
				break;					
					
			case 'nexus_support_requests':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'nexus_support_requests' ) );
				$return = array(
					'name'	=> 'Support Requests',
					'rows'	=> $count['count'],
					'cycle'	=> 2000,
				);
				break;
					
			case 'nexus_support_replies':
				$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'nexus_support_replies' ) );
				$return = array(
					'name'	=> 'Support Replies',
					'rows'	=> $count['count'],
					'cycle'	=> 1500,
				);										
				break;	
			
			case 'nexus_customers':
				$count = $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'nexus_customers' ) );
				$return = array (
					'name'	=> 'Customers',
					'rows'	=> $count['count'],
					'cycle'	=> 250,
				);
			break;
			
			case 'nexus_notes':
				$count = $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'nexus_notes' ) );
				$return = array (
					'name'	=> 'Customer Notes',
					'rows'	=> $count['count'],
					'cycle'	=> 1500,
				);
			break;
			
			case 'nexus_support_severities':
				$count = $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'nexus_support_severities' ) );
				$return = array (
					'name'	=> 'Support Severities',
					'rows'	=> $count['count'],
					'cycle'	=> 2000,
				);
			break;
			
			case 'nexus_support_staff':
				$count = $this->DB->buildAndFetch ( array ( 'select' => 'COUNT(*) as count', 'from' => 'nexus_support_staff' ) );
				$return = array (
					'name'	=> 'Support Staff',
					'rows'	=> $count['count'],
					'cycle'	=> 2000,
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

		$basic = array('section' => $this->app['app_key'], 'key' => $action, 'app' => 'nexus');

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
			case 'members':
					return array ( 'members' => 'member_id', 'pfields_content' => 'member_id', 'profile_portal' => 'pp_member_id', 'rc_modpref' => 'mem_id' );
			break;

			case 'groups':
				return array ( 'groups' => 'g_id' );
			break;

			case 'forum_perms':
				return array ( 'forum_perms' => 'perm_id' );
			break;
			
			case 'nexus_attachments':
				return array ( 'attachments' => 'attach_id' );
			break;
			
			case 'nexus_gateways':
				return array( 'nexus_gateways' => 'g_id' );
				break;
					
			case 'nexus_package_groups':
				return array( 'nexus_package_groups' => 'pg_id' );
				break;

			case 'nexus_purchases':
				return array( 'nexus_purchases' => 'ps_id' );
				break;
					
			case 'nexus_packages':
				return array( 'nexus_packages' => 'p_id' );
				break;
				
			case 'nexus_invoices':
				return array( 'nexus_invoices' => 'i_id' );
				break;

			case 'nexus_support_departments':
				return array( 'nexus_support_departments' => 'dpt_id' );
				break;
					
			case 'nexus_support_statuses':
				return array( 'nexus_support_statuses' => 'status_id' );
				break;					

			case 'nexus_support_requests':
				return array( 'nexus_support_requests' => 'r_id' );
				break;

			case 'nexus_support_replies':
				return array( 'nexus_support_replies' => 'reply_id' );									
				break;	
				
			case 'nexus_customers':
				return array ( 'nexus_customers' => 'member_id' );
			break;
			
			case 'nexus_notes':
				return array ( 'nexus_notes' => 'note_id' );
			break;
			
			case 'nexus_support_severities':
				return array ( 'nexus_support_severities' => 'sev_id' );
			break;
			
			case 'nexus_support_staff':
				return array ( 'nexus_support_staff' => 'staff_id' );
			break;

			default:
				$this->error('There is a problem with the converter: bad truncate command ('.$action.')');
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
		switch ($action)
		{
			case 'nexus_package_groups':
				return array ( 'addfield' => array ( 'nexus_package_groups', 'conv_pg_parent', 'varchar(5)' ) );
			break;
				
			case 'nexus_packages':
				return array ( 'addfield' => array ( 'nexus_packages', 'conv_p_associable', 'varchar(5)' ) );
			break;
				
			case 'nexus_purchases':
				return array ( 'addfield' => array ( 'nexus_purchases', 'conv_ps_parent', 'varchar(5)' ) );
			break;
				
			default:
				return null;
				break;
		}
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
		# Added the "return false" to avoid errors while converting reports from deleted forums/topics/posts/pms/members
		/*switch ($type)
		{

		}
		return $report;*/
		return false;
	}
	
	/**
	 * Convert a customer
	 * 
	 * @access	public
	 * @param	integer		Foreign ID number
	 * @param	array		Data to insert
	 * @return	boolean		Success or fail
	 */
	public function convertCustomer ( $id, $info )
	{
		if ( !$id )
		{
			$this->logError ( $id, 'No ID specified.' );
			return false;
		}
		
		unset ( $info['member_id'] );
		$info['member_id'] = $this->getLink ( $id, 'members', FALSE, $this->useLocalLink );
		
		$this->DB->insert ( 'nexus_customers', $info );
		//$inserted_id = $this->getInsertId ( );
		
		$this->addLink ( $info['member_id'], $id, 'nexus_customers' );
		
		return true;
	}
	
	/**
	 * Convert a customer note
	 * 
	 * @access	public
	 * @param	integer		Foreign ID number
	 * @param	array		Data to insert
	 * @return	boolean		Success or fail
	 */
	public function convertCustomerNote ( $id, $info )
	{
		if ( !$id )
		{
			$this->logError ( $id, 'No ID specified.' );
			return false;
		}
		
		if ( !$info['note_member'] )
		{
			$this->logError ( $id, 'No Member ID specified.' );
			return false;
		}
		
		if ( !$info['note_author'] )
		{
			$this->logError ( $id, 'No Author ID specified.' );
			return false;
		}
		
		if ( !$info['note_text'] )
		{
			$this->logError ( $id, 'No Note Text specified.' );
			return false;
		}
		
		$info['note_member']	= $this->getLink ( $info['note_member'], 'members', FALSE, $this->useLocalLink );
		$info['note_author']	= $this->getLink ( $info['note_author'], 'members', FALSE, $this->useLocalLink );
		
		$this->DB->insert ( 'nexus_notes', $info );
		$inserted_id = $this->DB->getInsertId ( );
		
		$this->addLink ( $inserted_id, $id, 'nexus_notes' );
		
		return true;
	}

	/**
	 * Convert a package group
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertPackageGroup($id, $info, $parentLink = true)
	{	
		if ( !$id )
		{
			$this->logError ( $id, 'No ID specified.' );
			return false;
		}
		
		if ( !$info['pg_name'] )
		{
			$this->logError ( $id, 'No Package Group Name specified.' );
			return false;
		}
			
		// Parent Stuff.
		if ( $parentLink AND isset ( $info['parent'] ) )
		{
			$parent = $this->getLink ( $id, 'nexus_package_groups', true );
			if ( $parent )
			{
				$info['pg_parent']		= $parent;
			}
			else
			{
				$info['conv_pg_parent']	= $id;
			}
		}
			
		// SEO Names auto-change when edited... so make sure the one added is correct according to Nexus.
		unset ( $info['pg_seo_name'] );
		$info['pg_seo_name'] = IPSText::makeSeoTitle ( $info['pg_name'] );
		
		unset ( $info['pg_id'] );
		unset ( $info['parent'] );
			
		$this->DB->insert ( 'nexus_package_groups', $info );
		$inserted_id = $this->DB->getInsertId ( );
		
		$this->addLink ( $inserted_id, $id, 'nexus_package_groups' );
			
		// More parent handling.
		$this->DB->update ( 'nexus_package_groups', array ( 'pg_parent' => $inserted_id ), 'conv_pg_parent = \'' . $id . '\'' );
		return true;
	}
		
	/**
	 * Convert a package
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @param	array		Discounts array
	 * @return 	boolean		Success or fail
	 **/
	public function convertPackage($id, $info, $discounts = array ( ))
	{	
		if ( !$id )
		{
			$this->logError ( $id, 'No ID specified.' );
			return false;
		}
			
		if ( !$info['p_name'] )
		{
			$this->logError ( $id, 'No Name specified.' );
			return false;
		}
		
		if ( $info['p_member_groups'] != '*' )
		{
			$oldMemberGroups = explode ( ',', $row['p_member_groups'] );
			$newMemberGroups = array ( );
			foreach ( $oldMemberGroups AS $oldMemberGroup )
			{
				$newMemberGroups = array_merge ( $newMemberGroups, array ( $this->getLink ( $oldMemberGroup, 'groups', TRUE, $this->useLocalLink ) ) );
			}
			$info['p_member_groups'] = implode ( ',', $newMemberGroups );
		}
		
		/*if ( $info['p_support_department'] )
		{
			$info['p_support_department']	= $this->getLink ( $info['p_support_department'], 'nexus_support_departments' );
		}*/
		
		if ( $info['p_support_severity'] )
		{
			$info['p_support_severity']		= $this->getLink ( $info['p_support_severity'], 'nexus_support_severities' );
		}
		
		if ( $info['p_primary_group'] )
		{
			$info['p_primary_group']		= $this->getLink ( $info['p_primary_group'], 'groups', true, $this->useLocalLink );
		}
		
		if ( $info['p_secondary_group'] )
		{
			$oldSecondaryGroups = explode ( ',', $info['p_secondary_group'] );
			$newSecondaryGroups = array ( );
			foreach ( $oldSecondaryGroups AS $oldSecondaryGroup )
			{
				$newSecondaryGroups = array_merge ( $newSecondaryGroups, array ( $this->getLink ( $oldSecondaryGroup, 'groups', TRUE, $this->useLocalLink ) ) );
			}
			$info['p_secondary_group'] = implode ( ',', $oldSecondaryGroups );
		}
		
		if ( $info['p_perm_set'] )
		{
			$oldPermSets = explode ( ',', $info['p_perm_set'] );
			$newPermSets = array ( );
			foreach ( $oldPermSets AS $oldPermSet )
			{
				$newPermSets = array_merge ( $newPermSets, array ( $this->getLink ( $oldPermSet, 'forum_perms', TRUE, $this->useLocalLink ) ) );
			}
			$info['p_perm_set'] = implode ( ',', $newPermSets );
		}
		
		if ( $info['p_group'] )
		{
			$info['p_group'] = $this->getLink ( $info['p_group'], 'nexus_package_groups' );
		}
			
		if ( $info['associable'] )
		{
			$associable = $this->getLink ( $info['associable'], 'nexus_packages', TRUE );
			if ( $associable )
			{
				$info['p_associable'] = $associable;
			}
			else
			{
				$info['conv_p_associable'] = $info['associable'];
			}
		}
		
		if ( !isset ( $discounts['loyalty']		) ) { $discounts['loyalty']		= array ( ); }
		if ( !isset ( $discounts['bundle']		) ) { $discounts['bundle']		= array ( ); }
		if ( !isset ( $discounts['usergroup']	) ) { $discounts['usergroups']	= array ( ); }
		
		if ( count ( $discounts['loyalty'] ) > 0 )
		{
			$newLoyalties = array ( );
			foreach ( $discounts['loyalty'] AS $key => $loyalty )
			{
				$newLoyalties[] = array (
					'owns'		=> $loyalty['owns'],
					'package'	=> $this->getLink ( $loyalty['package'], 'nexus_packages', TRUE ),
					'price'		=> $loyalty['price'],
					'active'	=> $loyalty['active'],
				);
			}
			$discounts['loyalty'] = $newLoyalties;
		}
		
		if ( count ( $discounts['bundle'] ) > 0 )
		{
			$newBundles = array ( );
			foreach ( $discounts['bundle'] AS $key => $bundle )
			{
				$newBundles[] = array (
					'package'	=> $this->getLink ( $bundle['package'], 'nexus_packages' ),
					'discount'	=> $bundle['discount'],
					'combine'	=> $bundle['combine'],
				);
			}
			$discounts['bundle'] = $newBundles;
		}
		
		if ( count ( $discounts['usergroup'] ) > 0 )
		{
			$newGroupDiscounts = array ( );
			foreach ( $discounts['usergroup'] AS $key => $groupDiscount )
			{
				$newGroupDiscounts[] = array (
					'group'		=> $this->getLink ( $groupDiscount['group'], 'groups', TRUE, $this->useLocalLink ),
					'price'		=> $groupDiscount['price'],
					'secondary'	=> $groupDiscount['secondary'],
				);
			}
			$discounts['usergroup'] = $newGroupDiscounts;
		}
		
		$info['p_discounts'] = $discounts;
			
		// More SEO name stuff.
		unset ( $info['p_seo_name'] );
		$info['p_seo_name'] = IPSText::makeSeoTitle ( $info['p_name'] );
		
		unset ( $info['associable'] );
		unset ( $info['p_id'] );
			
		$this->DB->insert ( 'nexus_packages', $info );
		$inserted_id = $this->DB->getInsertId ( );
		
		$this->addLink ( $inserted_id, $id, 'nexus_packages' );
			
		// Handle associable packages.
		$this->DB->update ( 'nexus_packages', array ( 'p_associable' => $inserted_id ), 'conv_p_associable = \'' . $id . '\'' );
		return true;
	}

	/**
	 * Convert a purchase
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertPurchase($id, $info, $extra)
	{
		if ( !$id )
		{
			$this->logError ( $id, 'No ID specified.' );
			return false;
		}
		
		if ( !$info['ps_member'] )
		{
			$this->logError ( $id, 'No Member specified.' );
			return false;
		}
			
		if ( !$info['ps_name'] )
		{
			$this->logError ( $id, 'No Purchase Name specified.' );
			return false;
		}
			
		if ( !$info['ps_item_id'] )
		{
			$this->logError ( $id, 'No Item ID specified.' );
			return false;
		}
			
		if ( !$info['ps_original_invoice'] )
		{
			$this->logError ( $id, 'No Invoice ID specified.' );
			return false;
		}
			
		$info['ps_member']				= $this->getLink ( $info['ps_member'], 'members', FALSE, $this->useLocalLink );
		$info['ps_item_id']				= $this->getLink ( $info['ps_item_id'], 'nexus_packages' );
		$info['ps_original_invoice']	= $this->getLink ( $info['ps_original_invoice'], 'nexus_invoices' );
		
		if ( $info['ps_pay_to'] )
		{
			$info['ps_pay_to']				= $this->getLink ( $info['ps_pay_to'], 'members', FALSE, $this->useLocalLink );
		}
			
		if ( $info['parent'] )
		{
			$parent = $this->getLink ( $info['parent'], 'nexus_purchases', TRUE );
			if ( $parent )
			{
				$info['ps_parent'] = $parent;
			}
			else
			{
				$info['conv_ps_parent'] = $info['parent'];
			}
		}
		$newSecondaryGroups	= array ( );
		$newPermSets		= array ( );
		foreach ( $extra AS $key => $val )
		{
			// Other apps go here, when supported (downloads, etc.)
			if ( $key == 'nexus' )
			{
				$oldSecondaryGroups = explode ( ',', $val['old_secondary_groups'] );
				foreach ( $oldSecondaryGroups AS $oldSecondaryGroup )
				{
					$newSecondaryGroups = array_merge ( $newSecondaryGroups, array ( $this->getLink ( $oldSecondaryGroup, 'groups', TRUE, $this->useLocalLink ) ) );
				}
				
				$oldPermSets = explode ( ',',  $val['old_perm_masks'] );
				foreach ( $oldPermSets AS $oldPermSet )
				{
					$newPermSets = array_merge ( $newPermSets, array ( $this->getLink ( $oldPermSet, 'forum_perms', TRUE, TRUE ) ) );
				}
			}
		}
		
		$extra['nexus']['old_secondary_groups'] = implode ( ',', $newSecondaryGroups ); // Confused yet?
		$extra['nexus']['old_perm_masks']		= implode ( ',', $newPermSets );
		
		$info['ps_extra'] = serialize ( $extra );
			
		unset ( $info['parent'] );
		unset ( $info['ps_id'] );
		$this->DB->insert ( 'nexus_purchases', $info );
		$inserted_id = $this->DB->getInsertId ( );
		
		$this->addLink ( $inserted_id, $id, 'nexus_purchases' );
			
		// Handle parents
		$this->DB->update ( 'nexus_purchases', array ( 'ps_parent' => $inserted_id ), 'conv_ps_parent = \'' . $id . '\'' );
			
		return true;
	}
		
	/**
	 * Convert an invoice
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @param	array		Associated Items
	 * @param	array		Extra information for i_status_extra
	 * @return 	boolean		Success or fail
	 **/
	public function convertInvoice($id, $info, $items, $extra)
	{
		if ( !$id )
		{
			$this->logError ( $id, 'No ID specified.' );
			return false;
		}
		
		if ( !$info['i_title'] )
		{
			$this->logError ( $id, 'No Invoice Title spcified.' );
			return false;
		}
			
		if ( !$info['i_member'] )
		{
			$this->logError ( $id, 'No Member ID specified.' );
			return false;
		}
			
		if ( count ( $items ) < 1 )
		{
			$this->logError ( $id, 'No Items Associated.' );
			return false;
		}
			
		$info['i_member'] = $this->getLink ( $info['i_member'], 'members', FALSE, $this->useLocalLink );
		
		// Item array format should be the same format as stored in nexus_invoices table, i_items column when passed to this method.
		$storeItems = array ( );
		foreach ( $items AS $key => $item )
		{
			$item['itemID'] = $this->getLink ( $item['itemID'], 'nexus_packages' );
			$storeItems[]	= $item;
		}
			
		if ( count ( $extra ) > 0 )
		{
			$extra['setByID'] = $this->getLink ( $extra['setByID'], 'members', TRUE, $this->useLocalLink );
		}
			
		$info['i_status_extra']	= serialize ( $extra );
		$info['i_items']		= serialize ( $storeItems );
			
		unset ( $info['i_id'] );
			
		$this->DB->insert ( 'nexus_invoices', $info );
		$inserted_id = $this->DB->getInsertId ( );
			
		$this->addLink ( $inserted_id, $id, 'nexus_invoices' );
			
		return true;
	}
		
	/**
	 * Convert a support department
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @param	array		Array of Packages for this department
	 * @return 	boolean		Success or fail
	 **/
	public function convertSupportDepartment($id, $info, $packages)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------
		if (!$id)
		{
			$this->logError($id, 'No department ID number provided');
			return false;
		}
		if (!$info['dpt_name'])
		{
			$this->logError($id, 'No department name provided');
			return false;
		}
		
		// We don't want to merge dupes here (due to Packages), so append a suffix to the name for the admin to deal with later.
		$dupe = $this->DB->buildAndFetch ( array (
			'select'	=> '*',
			'from'		=> 'nexus_support_departments',
			'where'		=> 'dpt_name = \'' . $info['dpt_name'] . '\'',
		) );
		
		if ( $dupe )
		{
			$info['dpt_name'] = $info['dpt_name'] . '_converted';
		}
		
		$newPackages = array ( );
		foreach ( $packages AS $package )
		{
			$newPackages = array_merge ( $newPackages, array ( $this->getLink ( $package, 'nexus_packages', TRUE ) ) );
		}
		$info['dpt_packages'] = implode ( ',', $newPackages );
		
		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['dpt_id']);
		$this->DB->insert( 'nexus_support_departments', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'nexus_support_departments');
		return true;
	}
	
	/**
	 * Convert a support staff group
	 * 
	 * @access	public
	 * @param	integer		Foreign ID number
	 * @param	array		Data to insert to table
	 * @return	boolean		Success or fail
	 */
	public function convertSupportStaff ( $id, $info, $departments = array ( ) )
	{
		if ( !$id )
		{
			$this->logError ( $id, 'No ID specified.' );
			return false;
		}
		
		if ( !$info['staff_id'] )
		{
			$this->logError ( $id, 'No Group ID specified.' );
			return false;
		}
		
		if ( $info['staff_type'] == 'm' )
		{
			$info['staff_id']	= $this->getLink ( $info['staff_id'], 'members', FALSE, $this->useLocalLink );
		}
		else
		{
			$info['staff_id']	= $this->getLink ( $info['staff_id'], 'groups', FALSE, $this->useLocalLink );
		}
		
		if ( count ( $departments ) > 0 )
		{
			$staffDepartments = array ( );
			foreach ( $departments AS $department )
			{
				$staffDepartments = array_merge ( $staffDepartments, array ( $this->getLink ( $department, 'nexus_support_departments' ) ) );
			}
			$info['staff_departments'] = implode ( ',', $staffDepartments );
		}
		else
		{
			$info['staff_departments'] = '*';
		}
		
		$dupe = $this->DB->buildAndFetch ( array (
			'select'	=> '*',
			'from'		=> 'nexus_support_staff',
			'where'		=> 'staff_id = ' . $info['staff_id'] . ' AND staff_type = \'' . $info['staff_type'] . '\'',
		) );
		
		if ( $dupe )
		{
			$this->logError ( $id, 'Duplicate staff entry. Could not convert.' );
			return false;
		}
		
		$this->DB->insert ( 'nexus_support_staff', $info );
		
		$this->addLink ( $info['staff_id'], $info['staff_type'] . $id, 'nexus_support_staff' );
		
		return true;
	}
		
	/**
	 * Convert a support status
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertSupportStatus($id, $info)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------
		if (!$id)
		{
			$this->logError($id, 'No status ID number provided');
			return false;
		}
		if (!$info['status_name'])
		{
			$this->logError($id, 'No status name provided');
			return false;
		}
		
		$dupe = $this->DB->buildAndFetch ( array (
			'select'	=> '*',
			'from'		=> 'nexus_support_statuses',
			'where'		=> 'status_name = \'' . $info['status_name'] . '\'',
		) );
		
		if ( $dupe )
		{
			$this->addLink ( $dupe['status_id'], $id, 'nexus_support_statuses', 1 );
			return false;
		}

		//-----------------------------------------
		// Insert
		//-----------------------------------------

		unset($info['status_id']);
		$this->DB->insert( 'nexus_support_statuses', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'nexus_support_statuses');

		return true;
	}

	/**
	 * Convert a support request
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertSupportRequest($id, $info)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------
		if (!$id)
		{
			$this->logError($id, 'No support request ID number provided');
			return false;
		}
		if (!$info['r_member'])
		{
			$this->logError($id, 'No member ID provided');
			return false;
		}
		
		//-----------------------------------------
		// Link
		//-----------------------------------------

		$info['r_member'] = ($info['r_member']) ? $this->getLink($info['r_member'], 'members', TRUE, $this->useLocalLink) : 0;
		
		//-----------------------------------------
		// Insert
		//-----------------------------------------

		if ( !$info['r_member'] )
		{
			$this->logError($id, 'Member does not exist.');
			return FALSE;
		}
			
		$info['r_status']			= $this->getLink ( $info['r_status'], 'nexus_support_statuses' );
		$info['r_last_reply_by']	= $this->getLink ( $info['r_last_reply_by'], 'members', FALSE, $this->useLocalLink );
		$info['r_staff']			= $this->getLink ( $info['r_staff'], 'members', TRUE, $this->useLocalLink );
		if ( $info['purchase'] )
		{
			$info['r_purchase']			= $this->getLink ( $info['r_purchase'], 'nexus_purchases' );
		}
		$info['r_department']		= $this->getLink ( $info['r_department'], 'nexus_support_departments' );
		$info['r_severity']			= $this->getLink ( $info['r_severity'], 'nexus_support_severities' );
	
		$this->DB->insert( 'nexus_support_requests', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'nexus_support_requests');

		return true;
	}
	
	/**
	 * Convert a support severity
	 * 	
	 * @access	public
	 * @param	integer		Foreign ID number
	 * @param	array		Data to insert to table
	 * @return	boolean		Success or fail
	 */
	public function convertSupportSeverity ( $id, $info )
	{
		if ( !$id )
		{
			$this->logError ( $id, 'No ID specified.' );
			return false;
		}
		
		if ( !$info['sev_name'] )
		{
			$this->logError ( $id, 'No Severity Name specified.' );
			return false;
		}
		
		$dupe = $this->DB->buildAndFetch ( array (
			'select'	=> '*',
			'from'		=> 'nexus_support_severities',
			'where'		=> 'sev_name = \'' . $info['sev_name'] . '\'',
		) );
		
		if ( $dupe )
		{
			$this->addLink ( $dupe['sev_id'], $id, 'nexus_support_severities', 1 );
			return false;
		}
		
		unset ( $info['sev_id'] );
		$this->DB->insert ( 'nexus_support_severity', $info );
		$inserted_id = $this->DB->getInsertId ( );
		
		$this->addLink ( $inserted_id, $id, 'nexus_support_severities' );
		
		return true;
	}

	/**
	 * Convert a support reply
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @return 	boolean		Success or fail
	 **/
	public function convertSupportReply($id, $info)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------
		if (!$id)
		{
			$this->logError($id, 'No support reply ID number provided');
			return false;
		}
		if (!$info['reply_post'])
		{
			$this->logError($id, 'No post provided');
			return false;
		}
		if (!$info['reply_request'])
		{
			$this->logError($id, 'No support request ID provided');
			return false;
		}
		if (!$info['reply_member'])
		{
			$this->logError($id, 'No member ID provided');
			return false;
		}			
		
		//-----------------------------------------
		// Insert
		//-----------------------------------------

		$info['reply_member'] = ($info['reply_member']) ? $this->getLink($info['reply_member'], 'members', FALSE, $this->useLocalLink) : 0;
		$info['reply_request'] = $this->getLink($info['reply_request'], 'nexus_support_requests');

		if (!$info['reply_request'])
		{
			$this->logError($id, 'Support request not found.');
			return FALSE;
		}

		$this->DB->insert( 'nexus_support_replies', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'nexus_support_replies');

		return true;
	}	
		
	/**
	 * Convert a payment gateway
	 *
	 * @access	public
	 * @param 	integer		Foreign ID number
	 * @param 	array 		Data to insert to table
	 * @param	array		Array of Settings for the Department
	 * @return 	boolean		Success or fail
	 **/
	public function convertGateway($id, $info, $settings)
	{
		//-----------------------------------------
		// Make sure we have everything we need
		//-----------------------------------------
		if (!$id)
		{
			$this->logError($id, 'No payment gateway ID number provided');
			return false;
		}
		if (!$info['g_name'])
		{
			$this->logError($id, 'No payment gateway name provided');
			return false;
		}
		
		// Handle duplicates
		$dupe = $this->DB->buildAndFetch ( array (
			'select'	=> '*',
			'from'		=> 'nexus_gateways',
			'where'		=> 'g_key = \'' . $info['g_key'] . '\'',
		) );
		
		if ( $dupe )
		{
			$this->addLink ( $dupe['g_id'], $id, 'nexus_gateways', 1 );
			return false;
		}

		//-----------------------------------------
		// Insert
		//-----------------------------------------
		$info['g_settings'] = serialize ( $settings );
		
		$this->DB->insert( 'nexus_gateways', $info );
		$inserted_id = $this->DB->getInsertId();

		//-----------------------------------------
		// Add link
		//-----------------------------------------

		$this->addLink($inserted_id, $id, 'nexus_gateways');

		return true;
	}
}

?>