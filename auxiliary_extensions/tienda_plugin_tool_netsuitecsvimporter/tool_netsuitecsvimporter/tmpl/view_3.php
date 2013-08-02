<?php defined('_JEXEC') or die('Restricted access'); ?>

<p><?php echo JText::_('COM_TIENDA_THIS_TOOL_IMPORTS_DATA_FROM_A_CSV_FILE_TO_TIENDA'); ?></p>

   <div class="note" id="netsuite-import-status">
    Importing <span id="netsuite-import-progress">0</span> of <?php echo @$vars->total_records; ?>
    <div class="progress">
      <div class="bar" id="netsuite-progress-bar" style="width: 0%;"></div>
    </div>
   </div>

   <div id="netsuite-import-log" style="height: 200px; display:none; overflow: scroll;">
        <h3>Log</h3>
        <div class="logs note"></div>
        <a href="<?php echo JURI::root(); ?>/tmp/<?php echo JFactory::getSession()->getId(); ?>.log" target="_blank">Download Log</a>
   </div>

<script type="text/javascript">
tiendaJQ(document).ready(function($){
    var form = $('form.adminform');
    $('[name="task"]', form).val('view');
    var url = 'index.php?option=com_tienda&controller=tools&task=doTaskAjax&element=tool_netsuitecsvimporter.tienda&elementTask=ajaxImport&format=raw'
    var logs = 'index.php?option=com_tienda&controller=tools&task=doTaskAjax&element=tool_netsuitecsvimporter.tienda&elementTask=getLogs&format=raw'

    var done = <?php echo (int) @$vars->state->start; ?>;
    function migrate(start) {
        if (!start) {
            start = 0;
        }
        var total = <?php echo @$vars->total_records; ?>;
        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: {
                uploaded_file: '<?php echo @$vars->state->uploaded_file; ?>',
                start: start,
                limit: 25,
                skip_first: <?php echo @$vars->state->skip_first; ?>,
                total: total
            },
            success: function(data) {
                var processed = parseInt(data.msg);
                done = done + processed;
                $('#netsuite-import-progress').html(done);
                $('#netsuite-progress-bar').css({width: (done * 100 / total)+'%'})
                if (done >= total) {
                    $('#netsuite-import-status').html('Import finished');
                    $.ajax({
                        url: logs, 
                        type: 'GET',
                        dataType: 'json', 
                        success: function(l){
                            $('#netsuite-import-log').show();
                            $('#netsuite-import-log .note').html(l.msg);
                        }
                    });
                } else {
                   migrate(done);
                }
            }
        });
    }

    migrate(<?php echo (int) @$vars->state->start; ?>);
});
</script>