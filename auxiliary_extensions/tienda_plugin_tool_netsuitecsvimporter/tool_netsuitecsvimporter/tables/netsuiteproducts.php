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

Tienda::load( 'TiendaTableXref', 'tables._basexref' );

class TiendaTableNetsuiteproducts extends TiendaTableXref 
{
	/** 
	 * @param $db
	 * @return unknown_type
	 */
	function TiendaTableNetsuiteProducts ( &$db ) 
	{                
		$tbl_key 	= 'netsuite_id';
		$tbl_suffix = 'netsuiteproductsxref';
		$name 		= 'tienda';
		
		$this->set( '_tbl_key', $tbl_key );
		$this->set( '_suffix', $tbl_suffix );
		
		parent::__construct( "#__{$name}_{$tbl_suffix}", $tbl_key, $db );	
	}
	
	function check()
	{
		if (empty($this->netsuite_id))
		{
			$this->setError( JText::_('COM_TIENDA_NETSUITEID_REQUIRED') );
			return false;
		}
		if (empty($this->product_id))
		{
			$this->setError( JText::_('COM_TIENDA_PRODUCT_REQUIRED') );
			return false;
		}

		if (!$this->option_id)
		{
			$this->option_id = null;
		}
		
		return true;
	}
}
