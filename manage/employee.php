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

foreach(db_query(get_employees()) as $emp_row) {
	if(isset($_POST[$emp_row['emp_id']])) {
		$_SESSION['emp_id'] = $emp_row['emp_id'];
		$_POST['_tabs_sel'] = 'add';
		$Ajax -> activate('_page_body');
	}
}

$_POST['emp_id'] = isset($_SESSION['emp_id']) ? $_SESSION['emp_id'] : '';
$cur_id = $_POST['emp_id'];

//--------------------------------------------------------------------------

function can_process() {
	
	if(strlen($_POST['EmpFirstName']) == 0 || $_POST['EmpFirstName'] == "")
	{
		display_error(_("The employee first name must be entered."));
		set_focus('EmpFirstName');
		return false;
	}
	if(strlen($_POST['EmpLastName']) == 0 || $_POST['EmpLastName'] == "")
	{
		display_error(_("The employee last name must be entered."));
		set_focus('EmpLastName');
		return false;
	}
	if(!filter_var($_POST['EmpEmail'], FILTER_VALIDATE_EMAIL)) {
		display_error(_("Invalid email."));
		set_focus('EmpEmail');
		return false;
	}
	return true;
}

//--------------------------------------------------------------------------

function can_delete($cur_id) {
	$employee = get_employees($cur_id);
	if($employee['emp_hiredate'] != null) {
		display_error('Employed person cannot be deleted.');
		return false;
	}
	return true;
}

//--------------------------------------------------------------------------

function id_link($row) {
	return button($row['emp_id'], $row['emp_id']);
}
function get_name($row) {
	return $row['emp_first_name'].' '.$row['emp_last_name'];
}

function employees_list() {
	global $Ajax;
	
	$_SESSION['emp_id'] = '';
	if(db_has_employee()) {
		
		start_table(TABLESTYLE_NOBORDER);
		start_row();
		check_cells(_("Show resigned:"), 'show_inactive', null, true);
		end_row();
		end_table();
		
		$sql = get_employees(false, check_value('show_inactive'));

        $cols = array(
          _('ID') => array('fun'=>'id_link'),
          _('Name') => array('fun'=>'get_name')
        );

        $table =& new_db_pager('student_tbl', $sql, $cols);
        $table->width = "80%";
	
	    display_note(_('Press Id to edit employee details.'));
        display_db_pager($table);
	}
	else
		display_note(_("No employee defined."), 1);
}

//--------------------------------------------------------------------------

function employee_settings($cur_id) {
	
	if($cur_id) {
		$employee = get_employees($cur_id);
		$_POST['EmpFirstName'] = $employee['emp_first_name'];
		$_POST['EmpLastName'] = $employee['emp_last_name'];
		$_POST['EmpAddress'] = $employee['emp_address'];
		$_POST['EmpMobile'] = $employee['emp_mobile'];
		$_POST['EmpEmail'] = $employee['emp_email'];
		$_POST['EmpBirthDate'] = sql2date($employee['emp_birthdate']);
		$_POST['EmpNotes'] = $employee['emp_notes'];
	}
	start_outer_table(TABLESTYLE2);

	table_section(1);
	
	table_section_title(_("Basic Data"));
	
	hidden('emp_id');
	text_row(_("First Name:"), 'EmpFirstName', get_post('EmpFirstName'), 37, 50);
	text_row(_("Last Name:"), 'EmpLastName', get_post('EmpLastName'), 37, 50);
	textarea_row(_("Address:"), 'EmpAddress', get_post('EmpAddress'), 35, 5);
	text_row(_("Mobile:"), 'EmpMobile', get_post('EmpMobile'), 37, 30);
	email_row(_("e-Mail:"), 'EmpEmail', get_post('EmpEmail'), 37, 100);
	date_row(_("Birth Date:"), 'EmpBirthDate');
	textarea_row(_("Notes:"), 'EmpNotes', null, 35, 5);
	
	end_outer_table(1);
	
	div_start('controls');
	
	if ($cur_id) {
		
		submit_center_first('submit', _("Update Employee"), _('Update employee details'), 'default');
		submit_return('select', get_post('emp_id'), _("Select this employee and return to document entry."));
		submit_center_last('delete', _("Delete Employee"), _('Delete employee data if have been never used'), true);
	}
	else
		submit_center('submit', _("Add New Employee Details"), true, '', 'default');
	
	div_end();
}

//--------------------------------------------------------------------------

if (isset($_POST['submit'])) {
	
	if(!can_process())
		return;
	write_employee(
		$cur_id,
		$_POST['EmpFirstName'],
		$_POST['EmpLastName'],
		$_POST['EmpAddress'],
		$_POST['EmpMobile'],
		$_POST['EmpEmail'],
		$_POST['EmpBirthDate'],
		$_POST['EmpNotes']
	);
	if($cur_id)
		display_notification(_("Employee details has been updated."));
	else 
		display_notification(_("A new employee has been added."));
	
	$Ajax->activate('_page_body');
}
elseif(isset($_POST['delete'])) {
	if(!can_delete($cur_id))
		return;
	delete_employee($cur_id);
	display_notification(_("Employee details has been deleted."));
	$Ajax -> activate('_page_body');
}

//--------------------------------------------------------------------------

page(_($help_context = "Manage Employees"), false, false, "", $js);

start_form();

tabbed_content_start('tabs', array(
			 'list' => array(_('Employees &List'), 999),
             'add' => array(_('&Add/Edit Employee'), 999)));

if(get_post('_tabs_sel') == 'list')
	employees_list();
elseif(get_post('_tabs_sel') == 'add')
	employee_settings($cur_id);
br();
tabbed_content_end();

end_form();
end_page();
