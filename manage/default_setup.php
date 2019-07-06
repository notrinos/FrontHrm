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

$page_security = 'SA_HRSETUP';
$path_to_root  = '../../..';

include_once($path_to_root . '/includes/session.inc');
add_access_extensions();

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);

include_once($path_to_root . '/includes/ui.inc');
include_once($path_to_root . '/admin/db/company_db.inc');

include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');

//--------------------------------------------------------------------------

function can_process() {

	if (!check_num('payroll_month_work_days', 0, 31) || strlen($_POST['payroll_month_work_days']) == 0) {
		display_error(_('The number of month working days must be between 0 and 31.'));
		set_focus('payroll_month_work_days');
		return false;
	}
	if (!check_num('payroll_work_hours', 0, 24) || strlen($_POST['payroll_work_hours']) == 0) {
		display_error(_('The number of working hours must be between 0 and 24.'));
		set_focus('payroll_work_hours');
		return false;
	}
	if(!check_num('payroll_grades', max_grade_used())) {
		display_error(sprintf(_("Grade %s is being used by employees, cannot select a lower grade"), max_grade_used()));
		set_focus('payroll_grades');
		return false;
	}

	return true;
}

//--------------------------------------------------------------------------

if (isset($_POST['submit']) && can_process()) {

	update_company_prefs(get_post(array('payroll_payable_act', 'payroll_deductleave_act', 'payroll_overtime_act', 'payroll_month_work_days', 'payroll_work_hours', 'payroll_dept_based', 'payroll_grades')));

	display_notification(_('The Payroll setup has been updated.'));
}

//--------------------------------------------------------------------------

page(_($help_context = 'Payroll default Settings'), false, false, '', $js);

start_form();

start_outer_table(TABLESTYLE2);

table_section(1);

$myrow = get_company_prefs();

$_POST['payroll_payable_act'] = $myrow['payroll_payable_act'];
$_POST['payroll_deductleave_act'] = $myrow['payroll_deductleave_act'];
$_POST['payroll_overtime_act'] = $myrow['payroll_overtime_act'];
$_POST['payroll_month_work_days'] = $myrow['payroll_month_work_days'];
$_POST['payroll_work_hours'] = $myrow['payroll_work_hours'];
$_POST['payroll_dept_based'] = $myrow['payroll_dept_based'];
$_POST['payroll_grades'] = $myrow['payroll_grades'];

table_section_title(_('General GL'));

gl_all_accounts_list_row(_('Payroll payable account').':', 'payroll_payable_act', $_POST['payroll_payable_act'], true);
gl_all_accounts_list_row(_('Deductible account').':', 'payroll_deductleave_act', $_POST['payroll_deductleave_act'], true, false, _('Use Salary Basic Account'));
gl_all_accounts_list_row(_('Overtime account').':', 'payroll_overtime_act', $_POST['payroll_overtime_act'], true, false, _('Use Salary Basic Account'));

table_section_title(_('Working time parameters'));

text_row(_('Work days per month').':', 'payroll_month_work_days', $_POST['payroll_month_work_days'], 6, 6, '', '', _('days'));
text_row(_('Work hours per day').':', 'payroll_work_hours', $_POST['payroll_work_hours'], 6, 6, '', '', _('hours'));

table_section_title(_('Others'));
check_row(_('Salary based on department').':', 'payroll_dept_based', $_POST['payroll_dept_based']);
number_list_row(_('Number of Grades').':', 'payroll_grades', null, 1, $max_grade_number);

end_outer_table(1);

submit_center('submit', _('Update'), true, '', 'default');

end_form(2);
end_page();