<?php defined('_JEXEC') or die('Restricted access'); ?>

<?php echo JText::_('COM_TIENDA_OFFLINE_PAYMENT_PREPARATION_MESSAGE'); ?>

<table class="adminlist">
<tbody>
<tr>
    <td class="key" style="width: 100px; text-align: right;">
        <?php echo JText::_('COM_TIENDA_OFFLINE_PAYMENT_METHOD'); ?>
    </td>
    <td>
        <?php echo @$vars->payment_method; ?> 
    </td>
</tr>
</tbody>
</table>