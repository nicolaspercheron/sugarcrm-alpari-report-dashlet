{if not $report_id}
    <div style="width:100%;padding:4px;text-align:center">{$noReportLbl}</div>
{elseif not $columns}
    <div id="alpari_report_dashlet_{$id}">
        <div style="width:100%;padding:4px;text-align:center">
            <script type="text/javascript">
                uri = "/index.php?module=Reports&action=CallMethodDashlet&method=ajaxResult&to_pdf=1&id={$id}";
                id = "alpari_report_dashlet_{$id}";
                retrieveErrorLabel = "{$retrieveErrorLbl}";

                {literal}
                YAHOO.util.Connect.asyncRequest("GET", uri, {
                    success: function (o) {
                        document.getElementById(id).innerHTML = o.responseText;
                    },
                    failure: function (o) {
                        document.getElementById(id).innerHTML = retrieveErrorLabel;
                    }
                });
                {/literal}
            </script>
            Loading...
        </div>
    </div>
{elseif not $rows}
    <div style="width:100%;padding:4px;text-align:center">1{$noDataLbl}</div>
{else}
    <table width="100%" cellspacing="0" cellpadding="0" border="0" class="list view">
        <tr>
            {foreach from=$columns item=column}
                <th scope="col">{$column->label}</th>
            {/foreach}
        </tr>
        {foreach from=$rows item=row name=rows}
            {assign var="irow" value=$row|@array_values}
            <tr class="{if $smarty.foreach.rows.index is even}oddListRowS1{else}evenListRowS1{/if}">
                {foreach from=$columns item=field name=rowField}
                    <td>
                        {$irow[$smarty.foreach.rowField.index]}
                    </td>
                {/foreach}
            </tr>
        {/foreach}
    </table>
{/if}