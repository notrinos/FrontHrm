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

$page_security = 'SA_HRSETUP';
$path_to_root  = '../../..';

include_once($path_to_root . "/includes/session.inc");
add_access_extensions();

$js = '';
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/company_db.inc");

//--------------------------------------------------------------------------

function can_process() {

	if (!check_num('payroll_month_work_days', 0, 31) || strlen($_POST['payroll_month_work_days']) == 0) {
		display_error(_("The number of month working days must be between 0 and 31."));
		set_focus('payroll_month_work_days');
		return false;
	}
	if (!check_num('payroll_work_hours', 0, 24) || strlen($_POST['payroll_work_hours']) == 0) {
		display_error(_("The number of working hours must be between 0 and 24."));
		set_focus('payroll_work_hours');
		return false;
	}

	return true;
}

//--------------------------------------------------------------------------

if (isset($_POST['submit']) && can_process()) {

	update_company_prefs(get_post(array('payroll_payable_act', 'payroll_deductleave_act', 'payroll_overtime_act', 'payroll_month_work_days', 'payroll_work_hours')));

	display_notification(_("The Payroll setup has been updated."));
}

//--------------------------------------------------------------------------

page(_($help_context = "Payroll default Settings"), false, false, "", $js);

start_form();

start_outer_table(TABLESTYLE2);

table_section(1);

$myrow = get_company_prefs();

$_POST['payroll_payable_act'] = $myrow['payroll_payable_act'];
$_POST['payroll_deductleave_act'] = $myrow['payroll_deductleave_act'];
$_POST['payroll_overtime_act'] = $myrow['payroll_overtime_act'];
$_POST['payroll_month_work_days'] = $myrow['payroll_month_work_days'];
$_POST['payroll_work_hours'] = $myrow['payroll_work_hours'];

table_section_title(_("General GL"));

gl_all_accounts_list_row(_("Payroll payable account:"), 'payroll_payable_act', $_POST['payroll_payable_act']);
gl_all_accounts_list_row(_("Deductible account:"), 'payroll_deductleave_act', $_POST['payroll_deductleave_act']);
gl_all_accounts_list_row(_("Overtime account:"), 'payroll_overtime_act', $_POST['payroll_overtime_act']);

table_section_title(_("Other Parameters"));

text_row(_("Work days per month:"), 'payroll_month_work_days', $_POST['payroll_month_work_days'], 6, 6, '', '', _("days"));
text_row(_("Work hours per day:"), 'payroll_work_hours', $_POST['payroll_work_hours'], 6, 6, '', '', _("hours"));

end_outer_table(1);

submit_center('submit', _("Update"), true, '', 'default');

end_form(2);

end_page();