<?php 
defined('_JEXEC') or die('Restricted access');
JHTML::_('stylesheet', 'leftmenu_admin.css', 'media/com_tienda/css/');
?>

<div id="<?php echo $this->name; ?>" class="leftmenu-navigation">
    <ul class="nav nav-pills nav-stacked">
    <?php 
    foreach ($this->items as $item) {
        ?>
        <li <?php  if ($item[2] == 1) {echo 'class="active"'; } ?> >
        <?php 
		
        if ($this->hide) {
            
            if ($item[2] == 1) {
            ?>  <span class="nolink active"><?php echo $item[0]; ?></span> <?php
            } else {
            ?>  <span class="nolink"><?php echo $item[0]; ?></span> <?php    
            }
            
        } else {
            $u = JURI::getInstance();
            $u->parse($item[1]);
            $class = '';
            if ($u->getVar('view')) 
            {
                $class = 'view-'.$u->getVar('view');
            }
                
            if ($item[2] == 1) {
            ?> <a class="active <?php echo $class; ?>" href="<?php echo $item[1]; ?>"><?php echo $item[0]; ?></a> <?php
            } else {
            ?> <a class="<?php echo $class; ?>" href="<?php echo $item[1]; ?>"><?php echo $item[0]; ?></a> <?php   
            }        
        }
		?>
		</li>
		<?php
        
    }
    ?>
    </div>
</div>