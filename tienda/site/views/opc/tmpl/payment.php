<?php defined('_JEXEC') or die('Restricted access'); ?>

<form id="opc-payment-form" name="opc-payment-form" action="" method="post">

<ul class="unstyled control-group">
<?php if (empty($this->showPayment)) { ?>
    <li class="control">
        <p class="text-info">
            <?php echo JText::_( "COM_TIENDA_NO_PAYMENT_NECESSARY" ); ?>
        </p>
    </li>
<?php } else { ?>
    <?php if (!empty($this->payment_plugins)) { ?>
    
        <?php foreach($this->payment_plugins as $payment_plugin) { ?>
            <li class="control">
                <label class="radio">
                    <input class="payment-plugin required" value="<?php echo $payment_plugin->element; ?>" onclick="Opc.payment.getPluginForm('<?php echo $payment_plugin->element; ?>', 'opc-payment-method-form-container', '<?php echo JText::_('COM_TIENDA_GETTING_PAYMENT_METHOD'); ?>');" name="payment_plugin" type="radio" <?php echo (!empty($payment_plugin->checked)) ? "checked" : ""; ?> />
                    <?php echo $payment_plugin->getName(); ?>
                </label>
            </li>
        <?php } ?>
        
        <div id='opc-payment-method-form-container'>
            <?php if (!empty($this->payment_form_div)) { ?>
                <?php echo $this->payment_form_div;?>
            <?php } ?>
        </div>
        
    <?php } ?>
<?php } ?>
</ul>

    <div id="opc-payment-validation"></div>
    
    <a id="opc-payment-button" class="btn btn-primary"><?php echo JText::_('COM_TIENDA_CONTINUE') ?></a>

</form>
