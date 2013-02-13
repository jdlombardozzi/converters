<?php
/**
 * IPS Converters
 * IP.Nexus 1.2 Converters
 * IP.Nexus Merge Tool
 * Last Update: $Date: 2011-06-27 23:48:37 +0100 (Mon, 27 Jun 2011) $
 * Last Updated By: $Author: rashbrook $
 *
 * @package		IPS Converters
 * @author 		Ryan Ashbrook
 * @copyright	(c) 2011 Invision Power Services, Inc.
 * @link		http://external.ipslink.com/ipboard30/landing/?p=converthelp
 * @version		$Revision: 543 $
 */

$info = array (
	'key'	=> 'ipnexus',
	'name'	=> 'IP.Nexus 1.2',
	'login'	=> false,
);

$parent = array (
	'required'	=> true,
	'choices'	=> array (
		array (	'app' => 'board', 'key' => 'ipboard', 'newdb' => false ),
	),
);

class admin_convert_nexus_ipnexus extends ipsCommand
{
	public function doExecute ( ipsRegistry $registry )
	{
		$this->registry = $registry;
		
		$this->actions = array (
			'nexus_gateways'			=> array (	),
			'nexus_customers'			=> array (	'members' ),
			'nexus_notes'				=> array (	'members' ),
			'nexus_support_statuses'	=> array (	),
			'nexus_support_severities'	=> array (	),
			'nexus_package_groups'		=> array (	),
			'nexus_packages'			=> array (	'nexus_package_groups',
													'nexus_support_severities',
													//'nexus_support_departments',
													'groups'
			),
			'nexus_support_departments'	=> array (	'nexus_packages' ),
			'nexus_support_staff'		=> array (	'nexus_support_departments',
													'members',
													'groups'
			),
			'nexus_invoices'			=> array (	'nexus_packages',
													'members'
			),
			'nexus_purchases'			=> array (	'nexus_packages',
													'nexus_invoices',
													'members'
			),
			'nexus_support_requests'	=> array (	'nexus_support_departments',
													'nexus_purchases',
													'nexus_support_severities',
													'nexus_support_statuses',
													'members'
			),
			'nexus_support_replies'		=> array (	'nexus_support_requests',
													'members'
			),
			'nexus_attachments'			=> array (	'members',
													'nexus_support_requests',
													'nexus_support_replies'
			),
		);
		
		require_once ( IPSLib::getAppDir ( 'convert' ) . '/sources/lib_master.php' );
		require_once ( IPSLib::getAppDir ( 'convert' ) . '/sources/lib_nexus.php' );
		$this->lib = new lib_nexus ( $registry, $html, $this );
		
		$this->html = $this->lib->loadInterface ( );
		$this->lib->sendHeader ( 'IP.Nexus Merge Tool' );
		
		$this->HB = $this->lib->connect ( );
		
		if ( array_key_exists ( $this->request['do'], $this->actions ) )
		{
			call_user_func ( array ( $this, 'convert_' . $this->request['do'] ) );
		}
		else
		{
			$this->lib->menu ( );
		}
		
		$this->sendOutput ( );
	}
	
	private function sendOutput ( )
	{
		$this->registry->output->html		.= $this->html->convertFooter ( );
		$this->registry->output->html_main	.= $this->registry->output->global_template->global_frame_wrapper ( );
		$this->registry->output->sendOutput ( );
		exit;
	}
	
	public function countRows ( $action )
	{
		// Hoping I named them all right...
		if ( $action == 'nexus_attachments' )
		{
			return $this->lib->countRows ( 'attachments', 'attach_rel_module = \'support\'' );
		}
		else
		{
			return $this->lib->countRows ( $action );
		}
	}
	
	public function checkConf ( $action )
	{
		switch ( $action )
		{
			case 'nexus_attachments':
				return true;
			break;
			
			default:
				return false;
			break;
		}
	}
	
	private function fixPostData ( $post )
	{
		return $post;
	}
	
	private function convert_nexus_gateways ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_gateways',
			'order'		=> 'g_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_gateways', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$settings = unserialize ( $row['g_settings'] );
			$save = array (
				'g_key'			=> $row['g_key'],
				'g_name'		=> $row['g_name'],
				'g_testmode'	=> $row['g_testmode'],
				'g_position'	=> $row['g_position'],
				'g_payout'		=> $row['g_payout'],
			);
			$this->lib->convertGateway ( $row['g_id'], $save, $settings );
		}
		
		$this->lib->next ( );
	}
	
	// Probably will need redone for customer fields.
	private function convert_nexus_customers ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_customers',
			'order'		=> 'member_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_customers', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'cm_first_name'	=> $row['cm_first_name'],
				'cm_last_name'	=> $row['cm_last_name'],
				'cm_address_1'	=> $row['cm_address_1'],
				'cm_address_2'	=> $row['cm_address_2'],
				'cm_city'		=> $row['cm_city'],
				'cm_state'		=> $row['cm_state'],
				'cm_zip'		=> $row['cm_zip'],
				'cm_country'	=> $row['cm_country'],
				'cm_phone'		=> $row['cm_phone'],
			);
			$this->lib->convertCustomer ( $row['member_id'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_notes ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_notes',
			'order'		=> 'note_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_notes', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'note_member'	=> $row['note_member'],
				'note_text'		=> $this->fixPostData ( $row['note_text'] ),
				'note_author'	=> $row['note_author'],
				'note_date'		=> $row['note_date'],
			);
			$this->lib->convertCustomerNote ( $row['note_id'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_package_groups ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_package_groups',
			'order'		=> 'pg_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_package_groups', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'pg_name'		=> $row['pg_name'],
				'pg_position'	=> $row['pg_position'],
				'parent'		=> $row['pg_parent'],
			);
			$this->lib->convertPackageGroup ( $row['pg_id'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_packages ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_packages',
			'order'		=> 'p_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_packages', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$discounts = unserialize ( $row['p_discounts'] );
			
			$save = array (
				'p_name'				=> $row['p_name'],
				'p_desc'				=> $row['p_desc'],
				'p_group'				=> $row['p_group'],
				'p_stock'				=> $row['p_stock'],
				'p_reg'					=> $row['p_reg'],
				'p_member_groups'		=> $row['p_member_groups'],
				'p_allow_upgrading'		=> $row['p_allow_upgrading'],
				'p_upgrade_charge'		=> $row['p_upgrade_charge'],
				'p_allow_downgrading'	=> $row['p_allow_downgrading'],
				'p_downgrade_refund'	=> $row['p_downgrade_refund'],
				'p_base_price'			=> $row['p_base_price'],
				'p_tax'					=> $row['p_tax'],
				'p_renewals'			=> $row['p_renewals'],
				'p_renewal_price'		=> $row['p_renewal_price'],
				'p_renewal_unit'		=> $row['p_renewal_unit'],
				'p_renewal_days'		=> $row['p_renewal_days'],
				'p_primary_group'		=> $row['p_primary_group'],
				'p_secondary_group'		=> $row['p_secondary_group'],
				'p_perm_set'			=> $row['p_perm_set'],
				'p_return_primary'		=> $row['p_return_primary'],
				'p_return_secondary'	=> $row['p_return_secondary'],
				'p_return_perm'			=> $row['p_return_perm'],
				'p_module'				=> $row['p_module'],
				'p_position'			=> $row['p_position'],
				'associable'			=> $row['p_associable'],
				'p_force_assoc'			=> $row['p_force_assoc'],
				'p_assoc_error'			=> $row['p_assoc_error'],
				'p_page'				=> $row['p_page'],
				'p_support'				=> $row['p_support'],
				'p_support_department'	=> $row['p_support_department'],
				'p_support_severity'	=> $row['p_support_severity'],
				'p_image'				=> $row['p_image'],
				'p_featured'			=> $row['p_featured'],
				'p_upsell'				=> $row['p_upsell'],
				'p_notify'				=> $row['p_notify'],
				'p_type'				=> $row['p_type'],
				'p_custom'				=> $row['p_custom'],
			);
			$this->lib->convertPackage ( $row['p_id'], $save, $discounts );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_invoices ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_invoices',
			'order'		=> 'i_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_invoices', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$items = unserialize ( $row['i_items'] );
			$extra = unserialize ( $row['i_status_extra'] );
			
			$save = array (
				'i_status'		=> $row['i_status'],
				'i_title'		=> $row['i_title'],
				'i_member'		=> $row['i_member'],
				'i_total'		=> $row['i_total'],
				'i_date'		=> $row['i_date'],
				'i_return_uri'	=> $row['i_return_uri'],
				'i_paid'		=> $row['i_paid'],
				'i_discount'	=> $row['i_discount'],
				'i_temp'		=> $row['i_temp'],
				'i_ordersteps'	=> $row['i_ordersteps'],
				'i_noreminder'	=> $row['i_noreminder'],
				'i_renewal_ids'	=> $row['i_renewal_ids'],
			);
			
			$this->lib->convertInvoice ( $row['i_id'], $save, $items, $extra );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_purchases ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_purchases',
			'order'		=> 'ps_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_purchases', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$extra	= unserialize ( $row['ps_extra'] );
			$save	= array (
				'ps_member'				=> $row['ps_member'],
				'ps_name'				=> $row['ps_name'],
				'ps_active'				=> $row['ps_active'],
				'ps_cancelled'			=> $row['ps_cancelled'],
				'ps_start'				=> $row['ps_start'],
				'ps_expire'				=> $row['ps_expire'],
				'ps_renewals'			=> $row['ps_renewals'],
				'ps_renewal_price'		=> $row['ps_renewal_price'],
				'ps_renewal_unit'		=> $row['ps_renewal_unit'],
				'ps_app'				=> 'nexus',
				'ps_type'				=> $row['ps_type'],
				'ps_item_id'			=> $row['ps_item_id'],
				'ps_item_uri'			=> $row['ps_item_uri'],
				'ps_admin_uri'			=> $row['ps_admin_uri'],
				'ps_custom_fields'		=> serialize ( array ( ) ), //TODO
				'parent'				=> $row['ps_parent'],
				'ps_invoice_pending'	=> $row['ps_invoice_pending'],
				'ps_pay_to'				=> $row['ps_pay_to'],
				'ps_commission'			=> $row['ps_commission'],
				'ps_original_invoice'	=> $row['ps_original_invoice'],
				'ps_tax'				=> $row['ps_tax'],
			);
			
			$this->lib->convertPurchase ( $row['ps_id'], $save, $extra );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_support_severities ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_support_severities',
			'order'		=> 'sev_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_support_severities', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'sev_name'		=> $row['sev_name'],
				'sev_icon'		=> $row['sev_icon'],
				'sev_color'		=> $row['sev_color'],
				'sev_default'	=> $row['sev_default'],
				'sev_public'	=> $row['sev_public'],
				'sev_position'	=> $row['sev_position'],
				'sev_action'	=> $row['sev_action'],
			);
			$this->lib->convertSupportSeverity ( $row['sev_id'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_support_statuses ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_support_statuses',
			'order'		=> 'status_id ASC'
		);
		
		$loop = $this->lib->load ( 'nexus_support_statuses', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'status_name'			=> $row['status_name'],
				'status_public_name'	=> $row['status_public_name'],
				'status_public_set'		=> $row['status_public_set'],
				'status_default_member'	=> $row['status_default_member'],
				'status_default_staff'	=> $row['status_default_staff'],
				'status_is_locked'		=> $row['status_is_locked'],
				'status_assign'			=> $row['status_assign'],
				'status_position'		=> $row['status_position'],
				'status_open'			=> $row['status_open'],
				'status_color'			=> $row['status_color'],
			);
			$this->lib->convertSupportStatus ( $row['status_id'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_support_departments ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_support_departments',
			'order'		=> 'dpt_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_support_departments', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$packages	= explode ( ',', $row['dpt_packages'] );
			$save		= array (
				'dpt_name'				=> $row['dpt_name'],
				'dpt_open'				=> $row['dpt_open'],
				'dpt_require_package'	=> $row['dpt_require_package'],
				'dpt_position'			=> $row['dpt_position'],
				'dpt_notify'			=> $row['dpt_notify'],
				'dpt_notify_reply'		=> $row['dpt_notify_reply'],
			);
			$this->lib->convertSupportDepartment ( $row['dpt_id'], $save, $packages );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_support_staff ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_support_staff',
		);
		
		$loop = $this->lib->load ( 'nexus_support_staff', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$departments	= explode ( ',', $row['staff_departments'] );
			$save			= array (
				'staff_type'	=> $row['staff_type'],
				'staff_id'		=> $row['staff_id']
			);
			$this->lib->convertSupportStaff ( $row['staff_id'], $save, $departments );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_support_requests ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_support_requests',
			'order'		=> 'r_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_support_requests', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'r_title'				=> $row['r_title'],
				'r_member'				=> $row['r_member'],
				'r_department'			=> $row['r_department'],
				'r_purchase'			=> $row['r_purchase'],
				'r_status'				=> $row['r_status'],
				'r_severity'			=> $row['r_severity'],
				'r_severity_lock'		=> $row['r_severity_lock'],
				'r_started'				=> $row['r_started'],
				'r_last_reply'			=> $row['r_last_reply'],
				'r_last_reply_by'		=> $row['r_last_reply_by'],
				'r_last_new_reply'		=> $row['r_last_new_reply'],
				'r_last_staff_reply'	=> $row['r_last_staff_reply'],
				'r_staff'				=> $row['r_staff'],
				'r_staff_lock'			=> $row['r_staff_lock'],
				'r_replies'				=> $row['r_replies'],
				'r_notify'				=> serialize ( array ( 'type' => 'm', 'value' => $row['r_member'] ) ), //TODO
				'r_email'				=> $row['r_email'],
				'r_email_key'			=> $row['r_email_key'],
			);
			$this->lib->convertSupportRequest ( $row['r_id'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_support_replies ( )
	{
		$main = array (
			'select'	=> '*',
			'from'		=> 'nexus_support_replies',
			'order'		=> 'reply_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_support_replies', $main );
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'reply_request'	=> $row['reply_request'],
				'reply_member'	=> $row['reply_member'],
				'reply_type'	=> $row['reply_type'],
				'reply_post'	=> $this->fixPostData ( $row['reply_post'] ),
				'reply_hidden'	=> $row['reply_hidden'],
				'reply_date'	=> $row['reply_date'],
				'reply_email'	=> $row['reply_email'],
			);
			$this->lib->convertSupportReply ( $row['reply_id'], $save );
		}
		
		$this->lib->next ( );
	}
	
	private function convert_nexus_attachments ( )
	{
		$this->lib->saveMoreInfo ( 'nexus_attachments', array ( 'attach_path' ) );
		
		$main = array (
			'select'	=> '*',
			'from'		=> 'attachments',
			'where'		=> 'attach_rel_module = \'support\'',
			'order'		=> 'attach_id ASC',
		);
		
		$loop = $this->lib->load ( 'nexus_attachments', $main );
		
		$this->lib->getMoreInfo ( 'nexus_attachments', $loop, array (
			'attach_path'	=> array (
				'type'			=> 'text',
				'label'			=> 'The path to the folder where attachments are saved (usually path_to_ipb/uploads):',
			)
		), 'path' );
		
		$get	= unserialize ( $this->settings['conv_extra'] );
		$us		= $get[$this->lib->app['name']];
		$path	= $us['attach_path'];
		
		if ( !is_writable ( $this->settings['upload_dir'] ) )
		{
			$this->lib->error ( 'Your IP.Board upload path is not writeable. '.$this->settings['upload_dir'] );
		}
		
		if ( !is_readable ( $path ) )
		{
			$this->lib->error ( 'Your remote upload path is not readable.' );
		}
		
		while ( $row = ipsRegistry::DB ( 'hb' )->fetch ( $this->lib->queryRes ) )
		{
			$save = array (
				'attach_ext'			=> $row['attach_ext'],
				'attach_file'			=> $row['attach_file'],
				'attach_location'		=> $row['attach_location'],
				'attach_thumb_location'	=> $row['attach_thumb_location'],
				'attach_thumb_width'	=> $row['attach_thumb_width'],
				'attach_thumb_height'	=> $row['attach_thumb_height'],
				'attach_is_image'		=> $row['attach_is_image'],
				'attach_hits'			=> $row['attach_hits'],
				'attach_date'			=> $row['attach_date'],
				'attach_post_key'		=> $row['attach_post_key'],
				'attach_member_id'		=> $row['attach_member_id'],
				'attach_filesize'		=> $row['attach_filesize'],
				'attach_rel_id'			=> $row['attach_rel_id'],
				'attach_rel_module'		=> 'support',
				'attach_img_width'		=> $row['attach_img_width'],
				'attach_img_height'		=> $row['attach_img_height'],
			);
			
			$done = $this->lib->convertAttachment ( $row['attach_id'], $save, $path, FALSE, TRUE );
			
			if ( $done === true )
			{
				$aid = $this->lib->getLink ( $row['attach_id'], 'attachments' );

				$pid = $this->lib->getLink ( $row['attach_rel_id'], 'nexus_support_replies' );
				
				$attachrow = $this->DB->buildAndFetch ( array (
					'select'	=> 'reply_post',
					'from'		=> 'nexus_support_replies',
					'where'		=> 'reply_id = ' . $pid
				) );
				
				$save = preg_replace ( "#(\[attachment=)({$row['attach_id']}+?)\:([^\]]+?)\]#ie", "'$1'. $aid .':$3]'", $attachrow['reply_post'] );
				
				$this->DB->update ( 'nexus_support_replies', array ( 'reply_post' => $save ), 'reply_id = ' . $pid );
			}
		}
		
		$this->lib->next ( );
	}
}

?>