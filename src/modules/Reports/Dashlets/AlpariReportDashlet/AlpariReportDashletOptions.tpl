<div style="width:600px">
    <form name="configure_{$id}" action="index.php" method="post"
          onSubmit="return SUGAR.dashlets.postForm('configure_{$id}', SUGAR.mySugar.uncoverPage);"><br/>
        <input type="hidden" name="id" value="{$id}"/>
        <input type="hidden" name="module" value="Home"/>
        <input type="hidden" name="action" value="ConfigureDashlet"/>
        <input type="hidden" name="to_pdf" value="true"/>
        <input type="hidden" name="configure" value="true"/>

        <table width="100%" cellpadding="0" cellspacing="0" border="0" class="edit view" align="center">
            <td align="left" colspan="4" scope="row">
                <h2>{$optionsLbl}</h2>
            </td>
            <tr>
                <td scope="row">{$reportLbl}</td>
                <td>
                    <select name="report_id">
                        {foreach from=$summary_reports item=report}
                        <option {if $report.id eq $report_id}selected="true" {/if}value="{$report.id}">{$report.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td scope="row">{$limitLbl}</td>
                <td>
                    <input class="text" name="limit" size="3" value="{$limit}"/>
                </td>
            </tr>
            <tr>
                <td align="right" colspan="2">
                    <input type="submit" class="button" value="{$saveLbl}">
                </td>
            </tr>
        </table>
    </form>
</div>