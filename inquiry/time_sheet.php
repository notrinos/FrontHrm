<?php
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương <trananhphuong83@gmail.com>          |
|   Date :   09-Jul-2017                                 |
|   Description: NotrinosERP Payroll & Hrm Module        |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

$page_security = 'SA_EMPL';
$path_to_root  = '../../..';

include_once($path_to_root . '/includes/db_pager.inc');
include_once($path_to_root . '/includes/session.inc');
add_access_extensions();

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

include_once($path_to_root . '/includes/ui.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');

//--------------------------------------------------------------------------

page(_($help_context = 'Timesheet Inquiry'), false, false, '', $js);

start_form();
start_table(TABLESTYLE_NOBORDER);
start_row();

department_list_cells(null, 'DeptId', null, _('All departments'), true);
employee_list_cells(null, 'EmpId', null, _('All employees'), true, false, get_post('DeptId'));

date_cells(_('From:'), 'FromDate', '', null, 0, -1, 0, null, true);
date_cells(_('To:'), 'ToDate', '', null, 0, 0, 0, null, true);
overtime_list_cells(null, 'OvertimeId', '', _('Regular time'), true);
submit_cells('Search', _('Search'), '', '', 'default');

end_row();
end_table(1);
	
//-------------------------------------------------------------------------- 
	
$cols = array(_('Id')=>array('align'=>'center'), _('Employee Name'));
	
$from = new DateTime(date2sql($_POST['FromDate']));
$to = new DateTime(date2sql($_POST['ToDate']).'+1 day');
$interval = new DateInterval('P1D');
$period = new DatePeriod($from, $interval, $to);
$weekend = get_company_pref('weekend_day');
if(empty($weekend))
	$weekend = 7;
	
foreach($period as $day) {
	
	if($day->format('N') != $weekend)
		$cols[$day->format('d').'<p hidden>'.$day->format('m').'</p>'] = array('align'=>'center');
	else
		$cols["<div style='background:#FFCCCC'>".$day->format('d').'</div><p hidden>'.$day->format('m').'</p>'] = array('align'=>'center');
}
$sql = get_attendance($_POST['FromDate'], $_POST['ToDate'], $_POST['EmpId'], $_POST['DeptId'], $_POST['OvertimeId']);
$table = & new_db_pager('emp_att_tbl', $sql, $cols);
$table->width = '95%';
$table->page_len = 50;
display_db_pager($table);

end_form();
end_page();
