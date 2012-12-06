 <?php
/**
 * @version	1.5
 * @package	Tienda
 * @author 	Dioscouri Design
 * @link 	http://www.dioscouri.com
 * @copyright Copyright (C) 2007 Dioscouri Design. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/
 
/** ensure this file is being included by a parent file */
defined( '_JEXEC' ) or die( 'Restricted access' );

Tienda::load( 'TiendaTable', 'tables._base' );

class TiendaTablePayment extends TiendaTable 
{
	public function __construct( $db=null, $tbl_name=null, $tbl_key=null ) 
	{
		if (version_compare(JVERSION,'1.6.0','ge')) {
	        // Joomla! 1.6+ code here
	        $tbl_key 	= 'extension_id';
	        $tbl_suffix = 'extensions';
	    } else {
	        // Joomla! 1.5 code here
	        $tbl_key 	= 'id';
	        $tbl_suffix = 'plugins';
	    }
	    
	    $this->set( '_suffix', 'payment' );
	    
	    if (empty($db)) {
	        $db = JFactory::getDBO();
	    }
	    
		parent::__construct( "#__{$tbl_suffix}", $tbl_key, $db );		
	}
	
	public function getName() 
	{
	    $params = new DSCParameter( $this->params );
	    if ($params->get('label')) {
	        return $params->get('label');
	    }
	    
	    return $this->name;
	}
}

