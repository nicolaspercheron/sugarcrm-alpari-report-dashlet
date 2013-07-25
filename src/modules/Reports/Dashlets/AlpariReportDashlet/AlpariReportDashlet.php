<?php

/**
 * Copyright (c) 2013, Alpari
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *     - Redistributions of source code must retain the above copyright notice, this list of conditions and the
 *       following disclaimer.
 *     - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
 *       following disclaimer in the documentation and/or other materials provided with the distribution.
 *     - Neither the name of the Alpari nor the names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

include_once 'modules/Reports/Report.php';
include_once 'modules/Reports/templates/templates_reports.php';
include_once 'include/Dashlets/Dashlet.php';
include_once 'include/Sugar_Smarty.php';

/**
 * Class AlpariReportDashlet
 *
 * Class implements dashlet that shows reports result
 */
class AlpariReportDashlet extends Dashlet {
    /**
     * Storage for SavedReport
     *
     * @var string|null
     */
    private $_savedReport = null;

    /**
     * Source report ID
     *
     * @var string|NULL
     */
    public $reportId = null;

    /**
     * Limit of report result records
     *
     * @var int
     */
    public $limit = 5;

    /**
     * Constrcutor
     *
     * @param string $id Dashlet ID
     * @param array $def Dashlet definition
     */
    public function __construct($id, $def) {

        // Get settings
        if (isset($def['reportId']) && strlen($def['reportId']) == 36) {
            $this->reportId = $def['reportId'];
        }

        if (isset($def['limit']) && (int) $def['limit'] > 0) {

            $this->limit = $def['limit'];
        }

        // load the language strings here
        $this->loadLanguage('AlpariReportDashlet', 'custom/modules/Reports/Dashlets/');

        // dashlet is configurable?
        $this->isConfigurable = true;

        // dashlet has javascript attached to it?
        $this->hasScript = false;

        // if no custom title, use default
        if (!is_null($this->reportId) && strlen($this->_getSavedReport()->name) > 0) {
            $this->title = 'Report "' . $this->_getSavedReport()->name . '"';
        } else {
            $this->title = 'Alpari Report Dashlet';
        }

        // call parent constructor
        return parent::__construct($id);
    }


    /**
     * Displays the configuration form for the dashlet
     *
     * @return string
     */
    function displayOptions() {

        // Get db connection
        $conn = DBManagerFactory::getInstance();

        // Get list of summary reports
        $summaryReports = array();
        $res = $conn->query('SELECT `id`, `name`
                             FROM saved_reports
                             WHERE deleted = 0 AND report_type in ("summary", "tabular")
                             ORDER BY `name`');
        while ($row = $conn->fetchbyassoc($res)) {
            $summaryReports[] = $row;
        }

        $ss = new Sugar_Smarty();

        // Labels
        $ss->assign('optionsLbl', $this->dashletStrings['LBL_OPT_LABEL']);
        $ss->assign('reportLbl', $this->dashletStrings['LBL_OPT_REPORT_LABEL']);
        $ss->assign('limitLbl', $this->dashletStrings['LBL_OPT_LIMIT_LABEL']);
        $ss->assign('saveLbl', $this->dashletStrings['LBL_SAVE_BUTTON_LABEL']);

        // Values
        $ss->assign('id', $this->id);
        $ss->assign('report_id', $this->reportId);
        $ss->assign('limit', $this->limit);
        $ss->assign('summary_reports', $summaryReports);

        return parent::displayOptions() .
               $ss->fetch('custom/modules/Reports/Dashlets/AlpariReportDashlet/AlpariReportDashletOptions.tpl');
    }


    /**
     * Save options
     *
     * @param array $req Prepared request data frop options form
     *
     * @return array
     */
    function saveOptions($req) {
        return array('reportId' => $req['report_id'], 'limit' => (int) $req['limit']);
    }


    /**
     * Displays the dashlet
     *
     * @return string
     */
    public function display() {

        $ss = new Sugar_Smarty();
        $ss->assign('id', $this->id);
        $ss->assign('report_id', $this->reportId);
        $ss->assign('noReportLbl', $this->dashletStrings['LBL_NO_REPORT']);
        $ss->assign('retrieveErrorLbl', $this->dashletStrings['LBL_RETRIEVE_ERROR']);

        $render = $ss->fetch('custom/modules/Reports/Dashlets/AlpariReportDashlet/AlpariReportDashlet.tpl');

        return parent::display() . $render;
    }


    /**
     * Get and return SavedReport
     *
     * @return string
     */
    private function _getSavedReport() {

        // Find saved records in cache
        if (is_null($this->_savedReport)) {
            $this->_savedReport = new SavedReport();
            $this->_savedReport->disable_row_level_security = true;
            $this->_savedReport->retrieve($this->reportId, false);
        }

        return $this->_savedReport;
    }


    /**
     * Return report result
     *
     * @return array
     */
    private function _getReportResult() {

        $_reportSavedContent = $this->_getSavedReport()->content;
        $_reportSavedContentDecoded = json_decode($_reportSavedContent);

        // Run Report
        $reportObj = new Report($_reportSavedContent);

        // Run some query, depending on report-type
        if ($_reportSavedContentDecoded->report_type == 'tabular') {
            $reportObj->run_query();
        } else {
            $reportObj->run_summary_query();
        }

        $rows = array();
        for ($i=0; $i<$this->limit; $i++) {

            // Get next row, depending on report-type
            if ($_reportSavedContentDecoded->report_type == 'tabular') {
                $row =  $reportObj->get_next_row();
            } else {
                $row =  $reportObj->get_summary_next_row();
            }

            if (!$row) {
                break;
            }

            $rows[] = $row['cells'];
        }

        return $rows;
    }

    /**
     * Method execute report and return result data
     * Called from Dashlet on Home page
     *
     * @return void
     */
    function ajaxResult() {

        if (!is_null($this->reportId) && strlen($this->reportId) == 36) {
            $reportInfo = json_decode($this->_getSavedReport()->content);

            // List of report columns stored in different fields, that depending on report type
            if ($reportInfo->report_type == 'tabular') {
                $columns = $reportInfo->display_columns;
            } else {
                $columns = $reportInfo->summary_columns;
            }
            $rows = $this->_getReportResult();
        } else {
            $columns = array();
            $rows = array();
        }

        //echo '<pre>'; print_r($report_info); echo '</pre>';
        //echo '<pre>'; print_r($rows); echo '</pre>';

        $ss = new Sugar_Smarty();

        $ss->assign('report_id', $this->reportId);
        $ss->assign('columns', $columns);
        $ss->assign('rows', $rows);
        $ss->assign('noDataLbl', $this->dashletStrings['LBL_NO_DATA']);

        $render = $ss->fetch('custom/modules/Reports/Dashlets/AlpariReportDashlet/AlpariReportDashlet.tpl');

        echo $render;
        exit;
    }
}