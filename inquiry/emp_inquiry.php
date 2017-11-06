<?php
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương                                      |
|   Date :   09-07-2017                                  |
|   Description: Frontaccounting Payroll & Hrm Module    |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

$page_security = 'SA_EMPL';
$path_to_root  = '../../..';

include_once($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_db.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

//--------------------------------------------------------------------------

page(_($help_context = "Employee Transaction"), isset($_GET['EmpId']), false, '', $js);

if (isset($_GET['EmpId']))
	$_POST['EmpId'] = $_GET['EmpId'];

start_form();
start_table(TABLESTYLE_NOBORDER);
start_row();

department_list_cells(_("Department:"), 'DeptId', null, _("All departments"), true);
employee_list_cells(_("Employee:"), "EmpId", null, _("All employees"), true, false, get_post('DeptId'));

date_cells(_("From:"), 'FromDate', '', null, 0, -1, 0, null, true);
date_cells(_("To:"), 'ToDate', '', null, 0, 0, 0, null, true);
check_cells(_("Only unpaid:"), 'OnlyUnpaid', null, true);

submit_cells('Search', _("Search"), '', '', 'default');

end_row();
end_table(1);
    
//--------------------------------------------------------------------------

function check_overdue($row) {

}
function payslip_status($row) {
	return $row['PaySlip'] == 1 ? 'unpaid' : 'paid';
}
function view_link($row) {
	return get_trans_view_str($row["trans_type"], $row["trans_no"]);
}
function prt_link($row) {
	return print_document_link($row['trans_no'], _('Print this Payslip'), true, ST_PAYSLIP, ICON_PRINT, '', '', 0);
}

$sql = get_sql_for_payslips(get_post('EmpId'), get_post('FromDate'), get_post('ToDate'), get_post('DeptId'), '', check_value('OnlyUnpaid'));

$cols = array (
	_('Trans #') => array('fun'=>'view_link'), 
	'type' => 'skip',
	_('Employee ID'),
	_('Employee Name'),
	_('Payslip No') => '',
	_('Pay from') => array('type'=>'date'),
	_('Pay to') => array('type'=>'date'),
	_('Amount') => array('type'=>'amount'),
	_('Status') => array('fun'=>'payslip_status', 'align'=>'center'),
	'' => array('align'=>'center', 'fun'=>'prt_link')
);

$table =& new_db_pager('trans_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Marked items are overdue."));

$table->width = "85%";

display_db_pager($table);

end_form();
end_page();
