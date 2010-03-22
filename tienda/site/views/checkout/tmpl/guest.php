<?php 
    defined('_JEXEC') or die('Restricted access'); 
	JHTML::_('stylesheet', 'tienda.css', 'media/com_tienda/css/'); 
	JHTML::_('script', 'tienda.js', 'media/com_tienda/js/'); 
	JHTML::_('script', 'tienda_checkout.js', 'media/com_tienda/js/');
	$form = @$this->form; 
	$row = @$this->row;
	$baseurl = "index.php?option=com_tienda&format=raw&controller=addresses&task=getAddress&address_id="; 
?>
<div class='componentheading'>
    <span><?php echo JText::_( "Insert the Addresses and Select the Shipping Method" ); ?></span>
</div>

    <?php // echo TiendaMenu::display(); ?>
    
<div id='onCheckout_wrapper'>

	<!-- Progress Bar -->
	<?php echo $this->progress; ?>

    <form action="<?php echo JRoute::_( @$form['action'] ); ?>" method="post" name="adminForm" enctype="multipart/form-data">
        
        <!--    ORDER SUMMARY   -->
        <h3><?php echo JText::_("Order Summary") ?></h3>
        <div id='onCheckoutCart_wrapper'> 
			<?php
                echo @$this->orderSummary;
 		    ?>
        </div>
        
        <h3>
            <?php echo JText::_("Insert Shipping and Billing Addresses") ?>
        </h3>
        
        <h4>
            <?php echo JText::_("Insert your Email Address") ?>
        </h4>
       	
       	<table style="clear: both;">
        	<tr>
            <td style="text-align: left;">
                <!--    Email Address   -->             
                <input name="email_address" id="email_address" type="text" size="48" maxlength="250" />
            </td>
        </tr>
		</table>
			

        <table style="clear: both;">
        <tr>
            <td style="text-align: left;">
                <!--    BILLING ADDRESS   -->             
                <h4 id='billing_address_header' class="address_header">
                    <?php echo JText::_("Billing Address") ?>
                </h4>                
                <!--    BILLING ADDRESS FORM  -->
                <div id="billingDefaultAddress">
                   <?php echo @$this->billing_address_form; ?>
                </div>
            </td>
        </tr>
        <tr>
            <td style="text-align: left;">
                <!--    SHIPPING ADDRESS   -->
	            <h4 id='shipping_address_header' class="address_header">
	               <?php echo JText::_("Shipping Address") ?>
	            </h4>
	           
                    <div>
                        <input id="sameasbilling" name="sameasbilling" type="checkbox" onclick="tiendaDisableShippingAddressControls(this);" />&nbsp;
                        <?php echo JText::_( 'Same As Billing Address' ); ?>:
                    </div>
				
				<!--    SHIPPING ADDRESS FORM  -->
	            <div id="shippingDefaultAddress">
	                   <?php echo @$this->shipping_address_form; ?>
	            </div>
	            
            </td>
        </tr>
        </table>

        <!-- SHIPPING METHODS -->
        <h3><?php echo JText::_("Shipping Method") ?></h3>
        <div id="shippingmethods">
	    	<?php 
	    		$attribs = array( 'class' => 'inputbox', 'size' => '1', 'onclick' => 'tiendaGetCheckoutTotals();');
		    	echo TiendaSelect::shippingmethod( $this->order->shipping_method_id, 'shipping_method_id', $attribs, 'shipping_method_id', true ); 
		    ?>	
		    <div id="validationmessage" style="padding-top: 10px;"></div>
        </div>      

        <!--    COMMENTS   -->        
        <h3><?php echo JText::_("Shipping Notes") ?></h3>
        <?php echo JText::_( "Add optional notes for shipment here" ); ?>:
        <br/>
        <textarea id="customer_note" name="customer_note" rows="5" cols="70"></textarea>
        
        <p>            
        <!--    SUBMIT   -->
        <input type="button" class="button" onclick="window.location = '<?php echo JRoute::_('index.php?option=com_tienda&view=carts'); ?>'" value="<?php echo JText::_('Return to Shopping Cart'); ?>" />
        <input type="button" class="button" onclick="tiendaFormValidation( '<?php echo @$form['validation']; ?>', 'validationmessage', 'selectpayment', document.adminForm )" value="<?php echo JText::_('Select Payment Method'); ?>" />	
		<input type="hidden" id="currency_id" name="currency_id" value="<?php echo $this->order->currency_id; ?>" />
		<input type="hidden" id="step" name="step" value="selectshipping" />
		<input type="hidden" id="task" name="task" value="" />
		<input type="hidden" id="guest" name="guest" value="1" />
        </p>
        
        <?php echo $this->form['validate']; ?>
    </form>
</div>
