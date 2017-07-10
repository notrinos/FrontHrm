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

$js = "";
if ($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if (user_use_date_picker())
	$js .= get_js_date_picker();

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_db.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_ui.inc");

//--------------------------------------------------------------------------

function employees_list() {
	
	if(db_has_employee()) {
		
	}
	else
		display_note(_("No employee defined."), 1);
}

//--------------------------------------------------------------------------

function employee_settings($cur_id) {
	
	
}

//--------------------------------------------------------------------------

page(_($help_context = "Manage Employees"), false, false, "", $js);

start_form();

tabbed_content_start('tabs', array(
			 'list' => array(_('Employees &List'), 999),
             'add' => array(_('&Add/Edit Employee'), 999)));

  switch (get_post('_tabs_sel')) {
    default:
	case 'list':
		br();
        employees_list();
        break;
    case 'add':
		br();
        employee_settings($cur_id);
  }
br();
tabbed_content_end();

end_form();
end_page();
