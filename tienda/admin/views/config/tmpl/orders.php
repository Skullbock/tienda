<?php defined('_JEXEC') or die('Restricted access'); ?>
<?php $form = @$this -> form; ?>
<?php $row = @$this -> row; ?>

<form action="<?php echo JRoute::_( @$form['action'] ) ?>" method="post" class="adminform" name="adminForm" id="adminForm" enctype="multipart/form-data">

    <div>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="group" value="<?php echo $this->getLayout(); ?>" /> 
    </div>
    
    <div class="tabbable">
    
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#tab1" data-toggle="tab"><?php echo JText::_('COM_TIENDA_GENERAL_SETTINGS'); ?></a>
            </li>
            <li>
                <a href="#tab2" data-toggle="tab"><?php echo JText::_('COM_TIENDA_SHIPPING_TAX_SETTINGS'); ?></a>
            </li>
            <li>
                <a href="#tab3" data-toggle="tab"><?php echo JText::_('COM_TIENDA_COUPON_SETTINGS'); ?></a>
            </li>
            <li>
                <a href="#tab4" data-toggle="tab"><?php echo JText::_('COM_TIENDA_ADDRESS_FIELDS_MANAGEMENT'); ?></a>
            </li>
            <li>
                <a href="#tab5" data-toggle="tab"><?php echo JText::_('COM_TIENDA_ADVANCED_SETTINGS'); ?></a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="tab1">
                <?php $this->setLayout( 'orders_general' ); echo $this->loadTemplate(); ?>
            </div>
            
            <div class="tab-pane" id="tab2">
                <?php $this->setLayout( 'orders_taxes' ); echo $this->loadTemplate(); ?>
            </div>
                        
            <div class="tab-pane" id="tab3">
                <?php $this->setLayout( 'orders_coupons' ); echo $this->loadTemplate(); ?>
            </div>
            
            <div class="tab-pane" id="tab4">
                <?php $this->setLayout( 'orders_addresses' ); echo $this->loadTemplate(); ?>
            </div>
            
            <div class="tab-pane" id="tab5">
                <?php $this->setLayout( 'orders_advanced' ); echo $this->loadTemplate(); ?>
            </div>
        </div>
    </div>
    
</form>
