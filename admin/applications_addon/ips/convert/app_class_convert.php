<?php
class app_class_convert
{
	function __construct( ipsRegistry $registry )
	{
		//require_once( IPS_ROOT_PATH . 'applications_addon/ips/convert/sources/interface_acp.php' );
		
		/* Where are we? http://community.invisionpower.com/tracker/issue-37156-adding-tags-and-conversions/ */
		define( 'IN_CONVERTER', true );

    if ( file_exists( DOC_IPS_ROOT_PATH . 'cache/converter_lock.php' ) )
    {
      ipsRegistry::getClass('output')->showError( 'The converters have been locked. To unlock, delete the cache/converter_lock.php file.' );
    }
	}
}