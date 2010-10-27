<?php defined('_JEXEC') or die('Restricted access'); ?>
<?php JHTML::_('script', 'tienda.js', 'media/com_tienda/js/'); ?>
<?php $state = @$vars->state; ?>
<?php $items = @$vars->items; ?>

    <table class="adminlist" style="clear: both;">
        <thead>
            <tr>
                <th style="width: 5px;">
                    <?php echo JText::_("Num"); ?>
                </th>
                <th style="width: 50px;">
                    <?php echo JText::_("ID"); ?>
                </th>
                <th style="width: 100px;">
                    <?php echo JText::_("Created"); ?>
                </th>
                <th style="text-align: left;">
                    <?php echo JText::_("Product Name"); ?>
                </th>
                <th style="text-align: left;">
                    <?php echo JText::_("User"); ?>
                </th>
                <th style="width: 100px;">
                    <?php echo JText::_("Expires"); ?>
                </th>
                <th style="width: 100px;">
                    <?php echo JText::_("Price"); ?>
                </th>
                <th style="width: 100px;">
                    <?php echo JText::_("Order ID"); ?>
                </th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <td colspan="20">

                </td>
            </tr>
        </tfoot>
        <tbody>
        <?php $i=0; $k=0; ?>
        <?php foreach (@$items as $item) : ?>
            <tr class='row<?php echo $k; ?>'>
                <td align="center">
                    <?php echo $i + 1; ?>
                </td>
                <td style="text-align: center;">
                    <?php echo $item->subscription_id; ?>
                </td>
                <td style="text-align: center;">
                    <?php // TODO JHTML created date ?>
                </td>
                <td style="text-align: left;">
                    <?php echo JText::_($item->product_name); ?>
                    <?php // TODO Also product ID, [in brackets] ?>
                </td>
                <td style="text-align: left;">
                    <?php echo $item->user_username; ?>
                    <?php // TODO Also more details on user, such as email and full name ?>
                </td>
                <td style="text-align: center;">
                    <?php // TODO JHTML expires date ?>
                </td>
                <td style="text-align: center;">
                    <?php // TODO Price of subscription ?>
                </td>
                <td style="text-align: center;">
                    <?php // TODO Order number of subscription ?>
                </td>
            </tr>
            <?php ++$i; $k = (1 - $k); ?>
            <?php endforeach; ?>

            <?php if (!count(@$items)) : ?>
            <tr>
                <td colspan="10" align="center">
                    <?php echo JText::_('No items found'); ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
