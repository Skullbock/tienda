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
defined('_JEXEC') or die('Restricted access');

Tienda::load( 'TiendaModelBase', 'models._base' );

class TiendaModelOrders extends TiendaModelBase
{
    var $_order                = null;
    var $initial_order_state   = 15; // pre-payment/orphan, set in save()
    var $billing_input_prefix  = 'billing_input_';
    var $shipping_input_prefix = 'shipping_input_';
    var $defaultShippingMethod = null;
    
    public function __construct($config = array()) 
    {
        parent::__construct($config);
        
        $this->defaultShippingMethod = $this->defines->get('defaultShippingMethod', '2');
        $this->initial_order_state = $this->defines->get('initial_order_state', '15');
    }
    
    protected function _buildQueryWhere(&$query)
    {
        $filter     = $this->getState('filter');
       	$filter_orderstate	= $this->getState('filter_orderstate');
       	$filter_userid	= $this->getState('filter_userid');
        $filter_id_from	= $this->getState('filter_id_from');
        $filter_id_to	= $this->getState('filter_id_to');
       	$filter_user	= $this->getState('filter_user');
        $filter_date_from	= $this->getState('filter_date_from');
        $filter_date_to		= $this->getState('filter_date_to');
       	$filter_datetype	= $this->getState('filter_datetype');
        $filter_total_from = $this->getState('filter_total_from');
        $filter_total_to   = $this->getState('filter_total_to');
        $filter_ordernumber    = $this->getState('filter_ordernumber');
        $filter_orderstates = $this->getState('filter_orderstates');
		

		//TODO handle solar and legal time where is present.	
		$filter_date_from= $this->local_to_GMT_data( $filter_date_from );		
		$filter_date_to=$this->local_to_GMT_data( $filter_date_to );

		 if (empty($filter_date_to))
		 {
			$date = date_create($filter_date_from);
			date_modify($date, '24 hour');	
			$filter_date_to= date_format($date, 'Y-m-d H:i:s');
		 }	
		
       	if ($filter)
       	{
			$key	= $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter ) ) ).'%');

			$where = array();

			$where[] = 'LOWER(tbl.order_id) LIKE '.$key;
			$where[] = 'LOWER(tbl.order_number) LIKE '.$key;
			$where[] = 'LOWER(ui.first_name) LIKE '.$key;
			$where[] = 'LOWER(ui.last_name) LIKE '.$key;
			$where[] = 'LOWER(u.email) LIKE '.$key;
			$where[] = 'LOWER(u.username) LIKE '.$key;
			$where[] = 'LOWER(u.name) LIKE '.$key;

			$query->where('('.implode(' OR ', $where).')');
       	}
        if (strlen($filter_id_from))
        {
			if (strlen($filter_id_to))
        	{
        		$query->where('tbl.order_id >= '.(int) $filter_id_from);
        	}
        		else
        	{
        		$query->where('tbl.order_id = '.(int) $filter_id_from);
        	}
       	}
		if (strlen($filter_id_to))
        {
        	$query->where('tbl.order_id <= '.(int) $filter_id_to);
       	}
    	if (strlen($filter_user))
        {
			$key	= $this->_db->Quote('%'.$this->_db->getEscaped( trim( strtolower( $filter_user ) ) ).'%');

			$where = array();
			$where[] = 'LOWER(ui.first_name) LIKE '.$key;
			$where[] = 'LOWER(ui.last_name) LIKE '.$key;
			$where[] = 'LOWER(u.email) LIKE '.$key;
			$where[] = 'LOWER(u.username) LIKE '.$key;
			$where[] = 'LOWER(u.name) LIKE '.$key;
			$where[] = 'LOWER(u.id) LIKE '.$key;
			$query->where('('.implode(' OR ', $where).')');
       	}

        if (strlen($filter_orderstate))
        {
            $query->where('tbl.order_state_id = '.$this->_db->Quote($filter_orderstate));
        }
       	
        if (is_array($filter_orderstates) && !empty($filter_orderstates))
        {
            $query->where('tbl.order_state_id IN('.implode(",", $filter_orderstates).')' );
        }
        
        if (strlen($filter_userid))
        {
            $query->where('tbl.user_id = '.$this->_db->Quote($filter_userid));
        }

        if (strlen($filter_date_from))
        {
        	switch ($filter_datetype)
        	{
        		case "shipped":
        			$query->where("tbl.shipped_date >= '".$filter_date_from."'");
        		  break;
        		case "modified":
        			$query->where("tbl.modified_date >= '".$filter_date_from."'");
        		  break;
        		case "created":
        		default:
        			$query->where("tbl.created_date >= '".$filter_date_from."'");
        		  break;
        	}
       	}
		if (strlen($filter_date_to))
        {
			switch ($filter_datetype)
        	{
        		case "shipped":
        			$query->where("tbl.shipped_date <= '".$filter_date_to."'");
        		  break;
        		case "modified":
        			$query->where("tbl.modified_date <= '".$filter_date_to."'");
        		  break;
        		case "created":
        		default:
        			$query->where("tbl.created_date <= '".$filter_date_to."'");
        		  break;
        	}
       	}
       	
        if (strlen($filter_total_from))
        {
            if (strlen($filter_total_to))
            {
                $query->where('tbl.order_total >= '.(int) $filter_total_from);
            }
                else
            {
                $query->where('tbl.order_total = '.(int) $filter_total_from);
            }
        }
        if (strlen($filter_total_to))
        {
            $query->where('tbl.order_total <= '.(int) $filter_total_to);
        }
    }
    
	protected function _buildQueryFields(&$query)
	{
		$field = array();

		$field[] = " tbl.* ";
		$field[] = " u.name AS user_name ";
		$field[] = " u.username AS user_username ";	
		$field[] = " u.email ";
		$field[] = " ui.phone_1 ";
		$field[] = " ui.fax ";
		$field[] = " ui.first_name as first_name";
		$field[] = " ui.last_name as last_name";
		$field[] = " ui.email as userinfo_email";
		$field[] = " s.order_state_code ";
		$field[] = " s.order_state_name ";
		$field[] = " s.order_state_description ";
		$field[] = " shipping.ordershipping_name ";
        $field[] = " oi.billing_company ";
        $field[] = " oi.billing_last_name ";
        $field[] = " oi.billing_first_name ";
        $field[] = " oi.billing_middle_name ";
        $field[] = " oi.billing_phone_1 ";
        $field[] = " oi.billing_phone_2 ";
        $field[] = " oi.billing_fax ";
        $field[] = " oi.billing_address_1 ";
        $field[] = " oi.billing_address_2 ";
        $field[] = " oi.billing_city ";
        $field[] = " oi.billing_zone_name ";
        $field[] = " oi.billing_country_name ";
        $field[] = " oi.billing_country_id ";
        $field[] = " oi.billing_postal_code ";
        $field[] = " oi.billing_tax_number ";
        $field[] = " oi.shipping_company ";
        $field[] = " oi.shipping_last_name ";
        $field[] = " oi.shipping_first_name ";
        $field[] = " oi.shipping_middle_name ";
        $field[] = " oi.shipping_phone_1 ";
        $field[] = " oi.shipping_phone_2 ";
        $field[] = " oi.shipping_fax ";        
        $field[] = " oi.shipping_address_1 ";
        $field[] = " oi.shipping_address_2 ";
        $field[] = " oi.shipping_city ";
        $field[] = " oi.shipping_zone_name ";
        $field[] = " oi.shipping_country_name ";
        $field[] = " oi.shipping_country_id ";
        $field[] = " oi.shipping_postal_code ";
        $field[] = " oi.shipping_tax_number ";
        $field[] = " oi.user_email ";

        $field[] = "
            (
            SELECT 
                COUNT(items.orderitem_id)
            FROM
                #__tienda_orderitems AS items 
            WHERE 
                items.order_id = tbl.order_id 
            ) 
            AS items_count 
        ";

		$query->select( $field );
	}
	
	protected function _buildQueryJoins(&$query)
	{
		$query->join('LEFT', '#__tienda_userinfo AS ui ON ui.user_id = tbl.user_id');
		$query->join('LEFT', '#__users AS u ON u.id = tbl.user_id');
		$query->join('LEFT', '#__tienda_orderstates AS s ON s.order_state_id = tbl.order_state_id');
        $query->join('LEFT', '#__tienda_orderinfo AS oi ON tbl.order_id = oi.order_id');
        $query->join('LEFT', '#__tienda_ordershippings AS shipping ON shipping.order_id = tbl.order_id');   
	}

    protected function _buildQueryOrder(&$query)
    {
		$order      = $this->_db->getEscaped( $this->getState('order') );
       	$direction  = $this->_db->getEscaped( strtoupper($this->getState('direction') ) );
		if ($order)
		{
       		$query->order("$order $direction");
       	}
       	else
       	{
            $query->order("tbl.order_id ASC");
       	}
    }	
	
	
	
	public function getList($refresh = false)
	{
	    if (empty( $this->_list ))
	    {
	        Tienda::load( 'TiendaHelperBase', 'helpers._base' );
            $list = parent::getList($refresh);
            
            // If no item in the list, return an array()
            if( empty( $list ) ){
                return array();
            }
            
            $amigos = TiendaHelperBase::getInstance( 'Amigos' );
            $currency_helper = TiendaHelperBase::getInstance( 'Currency' );
            
            foreach(@$list as $item)
            {
            		if( $item->user_id < Tienda::getGuestIdStart() ) // guest user
            		{
            			if( strlen( $item->billing_first_name ) || strlen( $item->billing_last_name ) )
            			{
	            			$item->user_name = JText::_( 'COM_TIENDA_GUEST' ).' - '.$item->billing_first_name.' '.$item->billing_last_name;
            			}
	            		else
            			{
	            			$item->user_name = JText::_( 'COM_TIENDA_GUEST' ).' - '.$item->userinfo_email;
            			}
            		}
            	
                $item->link = 'index.php?option=com_tienda&controller=orders&view=orders&task=edit&id='.$item->order_id;
                $item->link_view = 'index.php?option=com_tienda&view=orders&task=view&id='.$item->order_id;
                
                // retrieve the order's currency
                // this loads the currency, using the FK is it is the same of the
                // currency used in the order, or the DSCParameter currency of the order otherwise
                $order_currency = new DSCParameter($item->order_currency);
                $order_currency = $order_currency->toArray();
                
                //JModel::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/models' );
                //$cmodel = JModel::getInstance( 'Currencies', 'TiendaModel' );
                //$cmodel->setId($item->currency_id);
                $item->currency = $currency_helper->load( $item->currency_id );
                
                // if the order currency is not the same as it was during the order
                if (!empty($item->currency) && !empty($order_currency['currency_code']) && $item->currency->currency_code != $order_currency['currency_code'])
                {
                    // overwrite it with the original one
                    foreach(@$order_currency as $k => $v)
                    {
                        $item->currency->$k = $v;
                    }
                }
                
                // has a commission?
                if ($amigos->isInstalled())
                {
                    $item->commissions = $amigos->getCommissions( $item->order_id );
                }
            }

            $this->_list = $list;
	    }

		return $this->_list;
	}
	
	public function getItem($pk=null, $refresh=false, $emptyState=true)
	{
	    if (empty( $this->_item ))
	    {
	        Tienda::load( 'TiendaHelperBase', 'helpers._base' );
            JModel::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/models' );
            $amigos = TiendaHelperBase::getInstance( 'Amigos' );
            $currency_helper = TiendaHelperBase::getInstance( 'Currency' );
            
            if ($item = parent::getItem( $pk, $refresh, $emptyState))
            {
                // get the orderinfo
                $item->orderinfo = JTable::getInstance('OrderInfo', 'TiendaTable');
                $item->orderinfo->load(array('order_id'=>$item->order_id));
                
                //retrieve the order's items
                $model = JModel::getInstance( 'OrderItems', 'TiendaModel' );
                $model->setState( 'filter_orderid', $item->order_id);
                $model->setState( 'order', 'tbl.orderitem_name' );
                $model->setState( 'direction', 'ASC' );
                $item->orderitems = $model->getList($refresh);
                foreach ($item->orderitems as $orderitem)
                {
                    $model = JModel::getInstance( 'OrderItemAttributes', 'TiendaModel' );
                    $model->setState( 'filter_orderitemid', $orderitem->orderitem_id);
                    $attributes = $model->getList();
                    $attributes_names = array();
                    $attributes_codes = array();
                    foreach ($attributes as $attribute)
                    {
                        // store a csv of the attrib names
                        $attributes_names[] = JText::_( $attribute->orderitemattribute_name );
                        if($attribute->orderitemattribute_code) 
                            $attributes_codes[] = JText::_( $attribute->orderitemattribute_code );
                    }
                    $orderitem->attributes_names = implode(', ', $attributes_names);
                    $orderitem->attributes_codes = implode(', ', $attributes_codes);
                    
                    // adjust the price
                    $orderitem->orderitem_price = $orderitem->orderitem_price + floatval($orderitem->orderitem_attributes_price);
                }
                
                
                //retrieve the order's history
                $model = JModel::getInstance( 'OrderHistory', 'TiendaModel' );
                $model->setState( 'filter_orderid', $item->order_id);
                $model->setState( 'order', 'tbl.date_added' );
                $model->setState( 'direction', 'ASC' );
                $item->orderhistory = $model->getList($refresh);
                $item->link_view = 'index.php?option=com_tienda&view=orders&task=view&id='.$item->order_id;
                
                //retrieve the order's payments
                $model = JModel::getInstance( 'OrderPayments', 'TiendaModel' );
                $model->setState( 'filter_orderid', $item->order_id);
                $model->setState( 'order', 'tbl.created_date' );
                $model->setState( 'direction', 'ASC' );
                $item->orderpayments = $model->getList($refresh);
                
                //retrieve the order's shippings
                $model = JModel::getInstance( 'OrderShippings', 'TiendaModel' );
                $model->setState( 'filter_orderid', $item->order_id);
                $model->setState( 'order', 'tbl.created_date' );
                $model->setState( 'direction', 'ASC' );
                $item->ordershippings = $model->getList($refresh);
                
                //retrieve the order's taxclasses
                $model = JModel::getInstance( 'OrderTaxClasses', 'TiendaModel' );
                $model->setState( 'filter_orderid', $item->order_id);
                $model->setState( 'order', 'tbl.ordertaxclass_description' );
                $model->setState( 'direction', 'ASC' );
                $item->ordertaxclasses = $model->getList($refresh);

                // retrieve the order's taxrates
                $model = JModel::getInstance( 'OrderTaxRates', 'TiendaModel' );
                $model->setState( 'filter_orderid', $item->order_id);
                $model->setState( 'order', 'tbl.ordertaxclass_id, tbl.ordertaxrate_level' );
                $item->ordertaxrates = $model->getList($refresh);

                // retrieve the order's currency
                // this loads the currency, using the FK is it is the same of the
                // currency used in the order, or the DSCParameter currency of the order otherwise
                $order_currency = new DSCParameter($item->order_currency);
                $order_currency = $order_currency->toArray();
                
                //$model = JModel::getInstance( 'Currencies', 'TiendaModel' );
                //$model->setId($item->currency_id);
                $item->currency = $currency_helper->load( $item->currency_id );
                
                // if the order currency is not the same as it was during the order
                if (!empty($item->currency) && !empty($order_currency['currency_code']) && $item->currency->currency_code != $order_currency['currency_code'])
                {
                    // overwrite it with the original one
                    foreach(@$order_currency as $k => $v){
                        $item->currency->$k = $v;
                    }
                }
                
                // has a commission?
                if ($amigos->isInstalled())
                {
                    $item->commissions = $amigos->getCommissions( $item->order_id );
                }
            }
            
            $this->_item = $item;
	    }
		
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger( 'onPrepare'.$this->getTable()->get('_suffix'), array( &$this->_item ) );
		
        return $this->_item;
	}

	/**
	 * Clean the cache
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function clearCache()
	{
	    parent::clearCache();
	    self::clearCacheAuxiliary();
	}
	
	/**
	 * Clean the cache
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function clearCacheAuxiliary()
	{
	    DSCModel::addIncludePath( JPATH_ADMINISTRATOR . '/components/com_tienda/models' );
	
	    $model = DSCModel::getInstance('OrderCoupons', 'TiendaModel');
	    $model->clearCache();
	     
	    $model = DSCModel::getInstance('OrderHistory', 'TiendaModel');
	    $model->clearCache();
	     
	    $model = DSCModel::getInstance('OrderInfo', 'TiendaModel');
	    $model->clearCache();
	     
	    $model = DSCModel::getInstance('OrderItemAttributes', 'TiendaModel');
	    $model->clearCache();
	     
	    $model = DSCModel::getInstance('OrderItems', 'TiendaModel');
	    $model->clearCache();
	     
	    $model = DSCModel::getInstance('OrderPayments', 'TiendaModel');
	    $model->clearCache();
	     
	    $model = DSCModel::getInstance('OrderShippings', 'TiendaModel');
	    $model->clearCache();
	     
	    $model = DSCModel::getInstance('OrderTaxClasses', 'TiendaModel');
	    $model->clearCache();
	     
	    $model = DSCModel::getInstance('OrderTaxRates', 'TiendaModel');
	    $model->clearCache();
	}
	
	public function prepare( $values, $options=array(), &$order=null )
	{
	    if (empty($order)) {
	        $order = $this->getTable();
	    }
	    $this->_order = &$order;
	    $this->_values = $values;
	    $this->_options = $options;
	    
	    if (empty($options['skip_adjust_credits']))
	    {
	        $order->_adjustCredits = true; // this is not a POS order, so adjust the user's credits (if any used)
	    }
	    
	    $order->bind( $values );
	    $order->user_id = $values['user_id'];
	    $order->ip_address = $values['ip_address']; //$_SERVER['REMOTE_ADDR'];
	    
	    // set the currency
	    if (empty($values['currency_id'])) 
	    {
    	    Tienda::load( 'TiendaHelperCurrency', 'helpers.currency' );
    	    $order->currency_id = TiendaHelperCurrency::getCurrentCurrency();
	    }
	    
	    // Store the text verion of the currency for order integrity
	    Tienda::load( 'TiendaHelperOrder', 'helpers.order' );
	    $order->order_currency = TiendaHelperOrder::currencyToParameters($order->currency_id);
	    	    
	    $saveAddressesToDB = empty($options["save_addresses"]) ? false : true;
	    $this->setAddresses( $values, $saveAddressesToDB );
	     
	    // set the shipping method
	    if(@$values['shippingrequired'] || !empty($values['shipping_plugin']))
	    {
	        $order->shipping = new JObject();
	        $order->shipping->shipping_price      = $values['shipping_price'];
	        $order->shipping->shipping_extra   = $values['shipping_extra'];
	        $order->shipping->shipping_name        = $values['shipping_name'];
	        $order->shipping->shipping_tax      = $values['shipping_tax'];
	    }
	     
	    if (empty($options['skip_add_items']))
	    {
	        //get the items from the current user's cart and add them to the order
	        Tienda::load( 'TiendaHelperCarts', 'helpers.carts' );
	        $reviewitems = TiendaHelperCarts::getProductsInfo();
	        foreach ($reviewitems as $reviewitem)
	        {
	            $order->addItem( $reviewitem );
	        }
	    }
	     
	    if (empty($options['skip_add_coupons']))
	    {
	        $this->addCoupons($values);
	    }
	    
	    if (empty($options['skip_add_credit']) && !empty($values['order_credit']))
	    {
	        $order->addCredit($values['order_credit']);
	    }
	     
	    $order->order_state_id = empty($values['orderstate_id']) ? $this->initial_order_state : $values['orderstate_id'];
	    $order->calculateTotals();
	    $order->getShippingTotal();
	    $order->getInvoiceNumber();
	    
	    return $order;
	}
	
	public function validate( $values, $options=array(), &$order=null )
	{
	    // load checkout model and do checkout validation
	    if (empty($options['skip_checkout_validation'])) 
	    {
	        DSCModel::addIncludePath( JPATH_SITE . '/components/com_tienda/models' );
	        $model = DSCModel::getInstance('Checkout', 'TiendaModel' );
	        if (!$model->validate($values, $options)) 
	        {
	            $errors = $model->getErrors();
	            if (!empty($errors))
	            {
	                foreach ($errors as $error)
	                {
	                    $error = trim( $error );
	                    if (!empty($error))
	                    {
	                        $this->setError( $error );
	                    }
	                }
	            }
	        }
	    }
	    
	    // order validation

	    // fail if no email address
	    jimport('joomla.mail.helper');
	    if( !JMailHelper::isEmailAddress($values['email_address'])) {
	        $this->setError( JText::_('COM_TIENDA_PLEASE_ENTER_CORRECT_EMAIL') );
	    }
	    
	    $order = $this->prepare( $values, $options, $order );

	    // fail if no items
	    $items = $order->getItems();
	    if (empty($items)) {
	        $this->setError( JText::_('COM_TIENDA_ORDERS_MUST_CONTAIN_AN_ITEM') );
	    }
	    
	    // fail if negative order_total
	    if ((int) $order->order_total < 0) {
	        $this->setError( JText::_('COM_TIENDA_ORDERS_CANNOT_HAVE_NEGATIVE_TOTALS') );
	    }
	    
	    if ($paymentRequired = $order->isPaymentRequired()) 
	    {
	        // fail if payment required and no billing address
	        $billingAddress = $order->getBillingAddress();
	        if (!$billingAddress || !is_a($billingAddress, 'TiendaTableAddresses')) {
	            $this->setError( JText::_('COM_TIENDA_BILLING_ADDRESS_REQUIRED') );
	        } else {
	            // fail if payment required and billing address fails validation
                if (!$billingAddress->check()) {
                    $this->setError( JText::_('COM_TIENDA_BILLING_ADDRESS_ERROR') );
                }
	        }

	        // fail if payment required and no payment method selected
	        if (empty($values["payment_plugin"])) {
                $this->setError( JText::_('COM_TIENDA_PLEASE_SELECT_PAYMENT_METHOD') );
	        }
	    }

	    if ($shippingRequired = $order->isShippingRequired()) 
	    {
    	    // fail if shipping required and no shipping address
	        $shippingAddress = $order->getShippingAddress();
	        if (!$shippingAddress || !is_a($shippingAddress, 'TiendaTableAddresses')) {
	            $this->setError( JText::_('COM_TIENDA_SHIPPING_ADDRESS_REQUIRED') );
	        } else {
	            // fail if shipping required and shipping address fails validation
	            if (!$shippingAddress->check()) {
	                $this->setError( JText::_('COM_TIENDA_SHIPPING_ADDRESS_ERROR') );
	            }
	        }

	        // fail if shipping required and no shipping method selected
	        if (empty($values["shipping_plugin"])) {
	            $this->setError( JText::_('COM_TIENDA_PLEASE_SELECT_SHIPPING_METHOD') );
	        }
	    }
	    
	    return $this->check();
	}
	
	/**
	 * 
	 * @param array $values
	 * @param TiendaTableOrders $order
	 * @param array $options
	 */
	public function save( $values, $options=array(), &$order=null )
	{
	    $error = false;
	    	    
	    // load checkout model and do checkout save
	    if (empty($options['skip_checkout_save']))
	    {
	        DSCModel::addIncludePath( JPATH_SITE . '/components/com_tienda/models' );
	        $model = DSCModel::getInstance('Checkout', 'TiendaModel' );
	        if (!$checkoutSave = $model->save($values, $options))
	        {
	            $errors = $model->getErrors();
	            if (!empty($errors))
	            {
	                foreach ($errors as $error)
	                {
	                    $error = trim( $error );
	                    if (!empty($error))
	                    {
	                        $this->setError( $error );
	                    }
	                }
	            }
	        } 
	        else 
	        {
	            $values['user_id'] = $checkoutSave->user_id;
	        }
	    }

	    $order = $this->prepare( $values, $options, $order );
	     
	    $this->_order = &$order;
	    $this->_values = $values;
	    $this->_options = $options;
	    
	    //TODO: Do Something with Payment Infomation
	    if ( $order->save() )
	    {
	        $this->setId( $order->order_id );

	        // save the order items
	        if (!$this->saveOrderItems())
	        {
	            // TODO What to do if saving order items fails?
	            $error = true;
	        }
	    
	        // save the order vendors
	        if (!$this->saveOrderVendors())
	        {
	            // TODO What to do if saving order vendors fails?
	            $error = true;
	        }
	    
	        // save the order info
	        if (!$this->saveOrderInfo())
	        {
	            // TODO What to do if saving order info fails?
	            $error = true;
	        }
	    
	        // save the order history
	        if (!$this->saveOrderHistory())
	        {
	            // TODO What to do if saving order history fails?
	            $error = true;
	        }
	    
	        // save the order taxes
	        if (!$this->saveOrderTaxes())
	        {
	            // TODO What to do if saving order taxes fails?
	            $error = true;
	        }
	    
	        // save the order shipping info
	        if ( isset( $order->shipping ) && !$this->saveOrderShippings( $values ))
	        {
	            // TODO What to do if saving order shippings fails?
	            $error = true;
	        }
	    
	        // save the order coupons
	        if (!$this->saveOrderCoupons())
	        {
	            // TODO What to do if saving order coupons fails?
	            $error = true;
	        }
	    
	        $this->clearCache();
	    }
	    
	    if ($error)
	    {
	        return false;
	    }

	    return $order;        
	}
	
	/**
	 * Saves each individual item in the order to the DB
	 *
	 * @return unknown_type
	 */
	protected function saveOrderItems()
	{
	    JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
	    $order = $this->_order;
	    $items = $order->getItems();
	
	    if (empty($items) || !is_array($items))
	    {
	        $this->setError( "saveOrderItems:: ".JText::_('COM_TIENDA_ITEMS_ARRAY_INVALID') );
	        return false;
	    }
	
	    $error = false;
	    $errorMsg = "";
	    Tienda::load( 'TiendaHelperUser', 'helpers.user' );
	    foreach ($items as $item)
	    {
	        $item->order_id = $order->order_id;
	
	        if (!$item->save())
	        {
	            // track error
	            $error = true;
	            $errorMsg .= $item->getError();
	        }
	        else
	        {
	            //fire onAfterSaveOrderItem
	            $dispatcher = JDispatcher::getInstance();
	            $dispatcher->trigger( 'onAfterSaveOrderItem', array( $item ) );
	
	            // does the orderitem create a subscription?
	            if (!empty($item->orderitem_subscription))
	            {
	                $date = JFactory::getDate();
	                // these are only for one-time payments that create subscriptions
	                // recurring payment subscriptions are handled differently - by the payment plugins
	                $subscription = JTable::getInstance('Subscriptions', 'TiendaTable');
	                $subscription->user_id = $order->user_id;
	                $subscription->order_id = $order->order_id;
	                $subscription->product_id = $item->product_id;
	                $subscription->orderitem_id = $item->orderitem_id;
	                $subscription->transaction_id = ''; // in recurring payments, this is the subscr_id
	                $subscription->created_datetime = $date->toMySQL();
	                $subscription->subscription_enabled = '0'; // disabled at first, enabled after payment clears
	
	                switch($item->subscription_period_unit)
	                {
	                    case "Y":
	                        $period_unit = "YEAR";
	                        break;
	                    case "M":
	                        $period_unit = "MONTH";
	                        break;
	                    case "W":
	                        $period_unit = "WEEK";
	                        break;
	                    case "I":
	                        // expiration date is not important (it's calculated on-the-fly) => create a seemingly lifetime subscription
	                        $period_unit = 'YEAR';
	                        $item->subscription_period_interval = '100'; // we dont need to know the interval (we will know the last ID)
	                        break;
	                    case "D":
	                    default:
	                        $period_unit = "DAY";
	                        break;
	                }
	
	                if (!empty($item->subscription_lifetime))
	                {
	                    // set expiration 100 years in future
	                    $period_unit = "YEAR";
	                    $item->subscription_period_interval = '100';
	                    $subscription->lifetime_enabled = '1';
	                }
	                $database = JFactory::getDBO();
	                $query = " SELECT DATE_ADD('{$subscription->created_datetime}', INTERVAL {$item->subscription_period_interval} $period_unit ) ";
	                $database->setQuery( $query );
	                $subscription->expires_datetime = $database->loadResult();
	
	                if( $this->defines->get( 'display_subnum', 0 ) )
	                {
	                    $subscription->sub_number = TiendaHelperUser::getSubNumber( $order->user_id );
	                }
	
	                if (!$subscription->save())
	                {
	                    $error = true;
	                    $errorMsg .= $subscription->getError();
	                }
	
	                // add a sub history entry, email the user?
	                $subscriptionhistory = JTable::getInstance('SubscriptionHistory', 'TiendaTable');
	                $subscriptionhistory->subscription_id = $subscription->subscription_id;
	                $subscriptionhistory->subscriptionhistory_type = 'creation';
	                $subscriptionhistory->created_datetime = $date->toMySQL();
	                $subscriptionhistory->notify_customer = '0'; // notify customer of new trial subscription?
	                $subscriptionhistory->comments = JText::_('COM_TIENDA_NEW_SUBSCRIPTION_CREATED');
	                $subscriptionhistory->save();
	            }
	
	            // Save the attributes also
	            if (!empty($item->orderitem_attributes))
	            {
	                $attributes = explode(',', $item->orderitem_attributes);
	                foreach (@$attributes as $attribute)
	                {
	                    unset($productattribute);
	                    unset($orderitemattribute);
	                    $productattribute = JTable::getInstance('ProductAttributeOptions', 'TiendaTable');
	                    $productattribute->load( $attribute );
	                    $orderitemattribute = JTable::getInstance('OrderItemAttributes', 'TiendaTable');
	                    $orderitemattribute->orderitem_id = $item->orderitem_id;
	                    $orderitemattribute->productattributeoption_id = $productattribute->productattributeoption_id;
	                    $orderitemattribute->orderitemattribute_name = $productattribute->productattributeoption_name;
	                    $orderitemattribute->orderitemattribute_price = $productattribute->productattributeoption_price;
	                    $orderitemattribute->orderitemattribute_code = $productattribute->productattributeoption_code;
	                    $orderitemattribute->orderitemattribute_prefix = $productattribute->productattributeoption_prefix;
	                    $orderitemattribute->orderitemattribute_weight = $productattribute->productattributeoption_weight;
	                    $orderitemattribute->orderitemattribute_prefix_weight = $productattribute->productattributeoption_prefix_weight;
	                    if (!$orderitemattribute->save())
	                    {
	                        // track error
	                        $error = true;
	                        $errorMsg .= $orderitemattribute->getError();
	                    }
	                }
	            }
	        }
	    }
	
	    if ($error)
	    {
	        $this->setError( $errorMsg );
	        return false;
	    }
	    return true;
	}
	
	/**
	 * Saves the order info to the DB
	 * @return unknown_type
	 */
	protected function saveOrderInfo()
	{
	    $order = $this->_order;
	
	    JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
	    $row = JTable::getInstance('OrderInfo', 'TiendaTable');
	    $row->order_id = $order->order_id;
	    $row->user_email = @$this->_values['email_address'];
	    $row->bind( $this->_orderinfoBillingAddressArray );
	    $row->bind( $this->_orderinfoShippingAddressArray );
	    $row->user_id = $order->user_id;
	
	    // Get Addresses
	    $shipping_address = $order->getShippingAddress();
	    $billing_address = $order->getBillingAddress();
	
	    // set zones and countries
	    $row->billing_zone_id       = $billing_address->zone_id;
	    $row->billing_country_id    = $billing_address->country_id;
	    $row->shipping_zone_id      = $shipping_address->zone_id;
	    $row->shipping_country_id   = $shipping_address->country_id;
	
	    if (!$row->save())
	    {
	        $this->setError( $row->getError() );
	        return false;
	    }
	
	    $order->orderinfo = $row;
	    return true;
	}
	
	/**
	 * Adds an order history record to the DB for this order
	 * @return unknown_type
	 */
	protected function saveOrderHistory()
	{
	    $order = $this->_order;
	    $values = $this->_values;
	    
	    JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
	    $row = JTable::getInstance('OrderHistory', 'TiendaTable');
	    $row->order_id = $order->order_id;
	    $row->order_state_id = $order->order_state_id;
	
	    $row->notify_customer = '0'; // don't notify the customer on prepayment
	    $row->comments = @$values['order_history_comments'];
	
	    if (!$row->save())
	    {
	        $this->setError( $row->getError() );
	        return false;
	    }
	    return true;
	}
	
	/**
	 * Saves each vendor related to this order to the DB
	 * @return unknown_type
	 */
	protected function saveOrderVendors()
	{
	    $order = $this->_order;
	    $values = $this->_values;
	    $items = $order->getVendors();	    
	    
	    if (empty($items) || !is_array($items))
	    {
	        // No vendors other than store owner, so just skip this
	        //$this->setError( "saveOrderVendors:: ".JText::_('COM_TIENDA_VENDORS_ARRAY_INVALID') );
	        //return false;
	        return true;
	    }
	
	    $error = false;
	    $errorMsg = "";
	    foreach ($items as $item)
	    {
	        if (empty($item->vendor_id))
	        {
	            continue;
	        }
	        $item->order_id = $order->order_id;
	        if (!$item->save())
	        {
	            // track error
	            $error = true;
	            $errorMsg .= $item->getError();
	        }
	    }
	
	    if ($error)
	    {
	        $this->setError( $errorMsg );
	        return false;
	    }
	    return true;
	}
	
	/**
	 * Adds an order tax class/rate record to the DB for this order
	 * for each relevant tax class & rate
	 *
	 * @return unknown_type
	 */
	protected function saveOrderTaxes()
	{
	    $order = $this->_order;
	    $values = $this->_values;
	    JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
	
	    $taxclasses = $order->getTaxClasses();
	    foreach ($taxclasses as $taxclass)
	    {
	        unset($row);
	        $row = JTable::getInstance('OrderTaxClasses', 'TiendaTable');
	        $row->order_id = $order->order_id;
	        $row->tax_class_id = $taxclass->tax_class_id;
	        $row->ordertaxclass_amount = $order->getTaxClassAmount( $taxclass->tax_class_id );
	        $row->ordertaxclass_description = $taxclass->tax_class_description;
	        $row->save();
	    }
	
	    $taxrates = $order->getTaxRates();
	    foreach ($taxrates as $taxrate)
	    {
	        unset($row);
	        $row = JTable::getInstance('OrderTaxRates', 'TiendaTable');
	        $row->order_id = $order->order_id;
	        $row->tax_rate_id = $taxrate->tax_rate_id;
	        $row->ordertaxrate_rate = $taxrate->tax_rate;
	        $row->ordertaxrate_amount = $order->getTaxRateAmount( $taxrate->tax_rate_id );
	        $row->ordertaxrate_description = $taxrate->tax_rate_description;
	        $row->ordertaxrate_level = $taxrate->level;
	        $row->ordertaxclass_id = $taxrate->tax_class_id;
	        $row->save();
	    }
	
	    // TODO Better error tracking necessary here
	    return true;
	}
	
	/**
	 * Saves the order shipping info to the DB
	 * @return unknown_type
	 */
	protected function saveOrderShippings( $values=null )
	{
	    $order = $this->_order;
	    if (empty($values)) {
	        $values = $this->_values;
	    }
	    
	    JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
	    $row = JTable::getInstance('OrderShippings', 'TiendaTable');
	    $row->order_id = $order->order_id;
	    $row->ordershipping_type = $values['shipping_plugin'];
	    $row->ordershipping_price = $values['shipping_price'];
	    $row->ordershipping_name = $values['shipping_name'];
	    $row->ordershipping_code = $values['shipping_code'];
	    $row->ordershipping_tax = $values['shipping_tax'];
	    $row->ordershipping_extra = $values['shipping_extra'];
	
	    if (!$row->save())
	    {
	        $this->setError( $row->getError() );
	        return false;
	    }
	
	    // Let the plugin store the information about the shipping
	    $dispatcher = JDispatcher::getInstance();
	    $dispatcher->trigger( "onPostSaveShipping", array( $values['shipping_plugin'], $row ) );
	
	    return true;
	}
	
	/**
	 * Saves the order coupons to the DB
	 * @return unknown_type
	 */
	protected function saveOrderCoupons()
	{
	    $order = $this->_order;
	    $values = $this->_values;
	    JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
	
	    $error = false;
	    $errorMsg = "";
	    $ordercoupons = $order->getOrderCoupons();
	    foreach ($ordercoupons as $ordercoupon)
	    {
	        $ordercoupon->_increase_coupon_uses = false;
	        $ordercoupon->order_id = $order->order_id;
	        if (!$ordercoupon->save())
	        {
	            // track error
	            $error = true;
	            $errorMsg .= $ordercoupon->getError();
	        }
	    }
	
	    if ($error)
	    {
	        $this->setError( $errorMsg );
	        return false;
	    }
	
	    return true;
	}
	
	/**
	 *
	 * @param array $values
	 * @param boolean $saveAddressesToDB Save the addresses to the database
	 * @param boolean $ajax
	 * @return unknown_type
	 */
	public function setAddresses( &$values, $saveAddressesToDB = false, $ajax = false )
	{
	    $clearAddressCache = false;
	    $order = $this->_order;
	
	    Tienda::load( 'TiendaHelperCurrency', 'helpers.currency' );
	    $currency_id = TiendaHelperCurrency::getCurrentCurrency();
	    
	    $billing_address_id     = (!empty($values['billing_address_id'])) ? $values['billing_address_id'] : 0;
	    $shipping_address_id    = (!empty($values['shipping_address_id'])) ? $values['shipping_address_id'] : 0;
	    $same_as_billing        = (!empty($values['sameasbilling'])) ? true : false;
	
	    $user_id                = $values['user_id'];
	    $billing_input_prefix   = $this->billing_input_prefix;
	    $shipping_input_prefix  = $this->shipping_input_prefix;
	
	    if (empty($user_id) && $this->defines->get('guest_checkout_enabled', '1'))
	    {
	        $user_id = -1;
	    }
	
	    $billing_zone_id = 0;
	    $billingAddressArray = $this->getAddressArray( $billing_address_id, $billing_input_prefix, $values );
	    if (array_key_exists('zone_id', $billingAddressArray))
	    {
	        $billing_zone_id = $billingAddressArray['zone_id'];
	    }
	
	    //SHIPPING ADDRESS: get shipping address from dropdown or form (depending on selection)
	    $shipping_zone_id = 0;
	    if ($same_as_billing)
	    {
	        $shippingAddressArray = $billingAddressArray;
	    }
	    else
	    {
	        $shippingAddressArray = $this->getAddressArray($shipping_address_id, $shipping_input_prefix, $values);
	    }
	
	    if (array_key_exists('zone_id', $shippingAddressArray))
	    {
	        $shipping_zone_id = $shippingAddressArray['zone_id'];
	    }
	
	    // keep the array for binding during the saveOrderInfo process
	    $this->_orderinfoBillingAddressArray = $this->filterArrayUsingPrefix($billingAddressArray, '', 'billing_', true);
	    $this->_orderinfoShippingAddressArray = $this->filterArrayUsingPrefix($shippingAddressArray, '', 'shipping_', true);
	    $this->_billingAddressArray = $billingAddressArray;
	    $this->_shippingAddressArray = $shippingAddressArray;
	
	    JTable::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_tienda/tables' );
	    $billingAddress = JTable::getInstance('Addresses', 'TiendaTable');
	    $shippingAddress = JTable::getInstance('Addresses', 'TiendaTable');
	
	    // set the order billing address
	    $billingAddress->bind( $billingAddressArray );
	    $billingAddress->user_id = $user_id;
	    $billingAddress->addresstype_id = 1;
	    if ($saveAddressesToDB && !$billing_address_id)
	    {
	        $clearAddressCache = true;
	        $billingAddress->save();
	        if( !$billing_address_id ) {
	            $values['billing_address_id'] = $billingAddress->address_id;
	        }
	    }
	
	    $order->setAddress( $billingAddress );
	
	    // set the order shipping address
	    $shippingAddress->bind( $shippingAddressArray );
	    $shippingAddress->user_id = $user_id;
	    $shippingAddress->addresstype_id = 2;
	    if ($saveAddressesToDB && !$same_as_billing && !$shipping_address_id)
	    {
	        $clearAddressCache = true;
	        $shippingAddress->save();
	        if( !$shipping_address_id ) {
	            $values['shipping_address_id'] = $shippingAddress->address_id;
	        }
	    }

	    if ($clearAddressCache) 
	    {
    	    DSCModel::addIncludePath( JPATH_SITE . '/components/com_tienda/models' );
    	    $model = DSCModel::getInstance('Addresses', 'TiendaModel' );
    	    $model->clearCache();
	    }
	    
	    $order->setAddress( $shippingAddress, 'shipping' );
	
	    return $this;
	}
	
	/**
	 * Converts either a TiendaTableAddresses object or form input into an array that can be binded to an order
	 * 
	 * @param unknown_type $address_id
	 * @param unknown_type $input_prefix
	 * @param unknown_type $form_input_array
	 * @return array
	 */
	public function getAddressArray( $address_id, $input_prefix, $form_input_array )
	{
	    $addressArray = array();
	    if (!empty($address_id))
	    {
	        $addressArray = $this->retrieveAddressIntoArray($address_id);
	    }
	    else
	    {
	        $addressArray = $this->filterArrayUsingPrefix($form_input_array, $input_prefix, '', false );
	        // set the zone name
	        $zone = JTable::getInstance('Zones', 'TiendaTable');
	        $zone->load( @$addressArray['zone_id'] );
	        $addressArray['zone_name'] = $zone->zone_name;
	        // set the country name
	        $country = JTable::getInstance('Countries', 'TiendaTable');
	        $country->load( @$addressArray['country_id'] );
	        $addressArray['country_name'] = $country->country_name;
	    }
	    return $addressArray;
	}
	
	/**
	 * Converts an address from the DB into an array
	 * 
	 * @param $address_id
	 * @return unknown_type
	 */
	public function retrieveAddressIntoArray( $address_id )
	{
	    $model = JModel::getInstance( 'Addresses', 'TiendaModel' );
	    $model->setId($address_id);
	    $item = $model->getItem();
	    if (is_object($item))
	    {
	        return get_object_vars( $item );
	    }
	    return array();
	}
	
	/**
	 * Takes an array of inputs from a form and strips the input prefix 
	 * (normally billing_input_ or shipping_input_) in order to return an array that can be binded to an order 
	 * 
	 * @param unknown_type $oldArray
	 * @param unknown_type $old_prefix
	 * @param unknown_type $new_prefix
	 * @param unknown_type $append
	 * @return unknown_type
	 */
	public function filterArrayUsingPrefix( $oldArray, $old_prefix, $new_prefix, $append )
	{
	    // create array with input form keys and values
	    $address_input = array();
	
	    foreach ($oldArray as $key => $value)
	    {
	        if (($append) || (strpos($key, $old_prefix) !== false))
	        {
	            $new_key = '';
	            if ($append){
	                $new_key = $new_prefix.$key;
	            }
	            else{
	                $new_key = str_replace($old_prefix, $new_prefix, $key);
	            }
	            if (strlen($new_key)>0){
	                $address_input[$new_key] = $value;
	            }
	        }
	    }
	    return $address_input;
	}
	
	public function addCoupons( $values )
	{
	    $this->addCouponCodes( $values );
	    $this->addAutomaticCoupons();
	}
	
	public function addCouponCodes($values)
	{
	    $order = &$this->_order;
	
	    // get all coupons and add them to the order
	    $coupons_enabled = $this->defines->get('coupons_enabled');
	    $mult_enabled = $this->defines->get('multiple_usercoupons_enabled');
	    if (!empty($values['coupons']) && $coupons_enabled)
	    {
	        foreach ($values['coupons'] as $coupon_id)
	        {
	            $coupon = JTable::getInstance('Coupons', 'TiendaTable');
	            $coupon->load(array('coupon_id'=>$coupon_id));
	            $order->addCoupon( $coupon );
	            if (empty($mult_enabled))
	            {
	                // this prevents Firebug users from adding multiple coupons to orders
	                break;
	            }
	        }
	    }
	    
	    return $this;
	}
	
	public function addAutomaticCoupons()
	{
	    $order = &$this->_order;
	    $date = JFactory::getDate();
	    $date = $date->toMysql();
	
	    // Per Order Automatic Coupons
	    $model = JModel::getInstance('Coupons', 'TiendaModel');
	    $model->setState('filter_automatic', '1');
	    $model->setState('filter_date_from', $date);
	    $model->setState('filter_date_to', $date);
	    $model->setState('filter_datetype', 'validity');
	    $model->setState('filter_type', '0');
	    $model->setState('filter_enabled', '1');
	
	    $coupons = $model->getList();
	
	    // Per Product Automatic Coupons
	    $model->setState('filter_type', '1');
	    $coupons_2 = $model->getList(true);
	
	    $coupons = array_merge( $coupons, $coupons_2 );
	
	    if($coupons)
	    {
	        foreach($coupons as $coupon)
	        {
	            $order->addCoupon($coupon);
	        }
	    }
	    
	    return $this;
	}
}