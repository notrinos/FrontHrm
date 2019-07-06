<?php
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương <trananhphuong83@gmail.com>          |
|   Date :   09-Jul-2017                                 |
|   Description: Frontaccounting Payroll & Hrm Module    |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

$page_security = 'SA_EMPL';
$path_to_root  = '../../..';

include_once($path_to_root . '/includes/db_pager.inc');
include_once($path_to_root . '/includes/session.inc');
add_access_extensions();

include_once($path_to_root . '/includes/ui.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');
include_once($path_to_root . '/reporting/includes/reporting.inc');

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

//--------------------------------------------------------------------------

page(_($help_context = 'Employee Transaction'), isset($_GET['EmpId']), false, '', $js);

if (isset($_GET['EmpId']))
	$_POST['EmpId'] = $_GET['EmpId'];

$days_no = date_diff2(begin_fiscalyear(), Today(), 'd');

start_form();

start_table(TABLESTYLE_NOBORDER);
start_row();

ref_cells(_('Reference').':', 'Ref', _('Enter reference fragment or leave empty'), null, null, true);
ref_cells(_('Memo').':', 'Memo', _('Enter memo fragment or leave empty'), null, null, true);
date_cells(_('From').':', 'FromDate', '', null, $days_no, 0, 0, null, true);
date_cells(_('To').':', 'ToDate', '', null, 0, 0, 0, null, true);

end_row();
// end_table();

// start_table(TABLESTYLE_NOBORDER);
start_row();

department_list_cells(null, 'DeptId', null, _('All departments'), true);
employee_list_cells(null, "EmpId", null, _('All employees'), true, false, get_post('DeptId'));
check_cells(_('Only unpaid:'), 'OnlyUnpaid', null, true);
submit_cells('Search', _('Search'), '', '', 'default');

end_row();
end_table(1);
    
//--------------------------------------------------------------------------

function check_overdue($row) {

}
function trans_type($row) {

	if($row['Type'] == 0)
		return _('Payslip');
	elseif ($row['payslip_no'] == 0)
		return _('Employee advance');
	else
		return _('Payment advice');
}
function view_link($row) {
	if($row['trans_no'] != 0)
	    return get_trans_view_str($row['Type'], $row['trans_no']);
}
function prt_link($row) {
	if($row['Type'] == 1 && $row['payslip_no'] != 0)
	    return hrm_print_link($row['payslip_no'], _('Print this Payslip'), true, ST_PAYSLIP, ICON_PRINT, '', '', 0);
}
function payslip_no($row) {
	return $row['payslip_no'] == 0 ? null : $row['payslip_no'];
}

$sql = get_sql_for_payslips(get_post('Ref'), get_post('Memo'), get_post('FromDate'), get_post('ToDate'), get_post('DeptId'), get_post('EmpId'), check_value('OnlyUnpaid'));

$cols = array (
	_('Date') => array('type'=>'date'),
	_('Trans #') => array('fun'=>'view_link'),
	_('Type') => array('fun'=>'trans_type'),
	_('Employee ID'),
	_('Employee Name'),
	_('Payslip No') => array('fun'=>'payslip_no'),
	_('Pay from') => array('type'=>'date'),
	_('Pay to') => array('type'=>'date'),
	_('Amount') => array('type'=>'amount'),
	'' => array('align'=>'center', 'fun'=>'prt_link')
);

$table =& new_db_pager('trans_tbl', $sql, $cols, null, null, 15);
$table->set_marker('check_overdue', _('Marked items are overdue.'));

$table->width = '80%';

display_db_pager($table);

end_form();
end_page();