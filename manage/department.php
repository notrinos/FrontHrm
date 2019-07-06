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

include_once($path_to_root . '/includes/ui.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');

//--------------------------------------------------------------------------

page(_($help_context = 'Manage Department'));
simple_page_mode(false);

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') {

	if(strlen($_POST['name']) == 0 || $_POST['name'] == '') {
		display_error( _('The Department name cannot be empty.'));
		set_focus('name');
	}
	elseif(empty($_POST['basic_acc']) && !empty($USE_DEPT_ACC)) {
		display_error( _('Please select basic account'));
		set_focus('basic_acc');
	}
	elseif(!empty($USE_DEPT_ACC) && !is_expenses_account($_POST['basic_acc'])) {
		display_error(_('Salary Basic Account must be an expenses account.'));
		set_focus('basic_acc');
	}
	else {
		write_department($selected_id, $_POST['name'], $_POST['basic_acc']);

    	if ($selected_id != '')
			display_notification(_('Selected department has been updated'));
    	else
			display_notification(_('New department has been added'));
		
		$Mode = 'RESET';
	}
}

if ($Mode == 'Delete') {

	if(department_has_employees($selected_id))
		display_error( _('The Department cannot be deleted.'));
	else {
		delete_department($selected_id);
		display_notification(_('Selected department has been deleted'));
	}
	$Mode = 'RESET';
}

if($Mode == 'RESET')
	$selected_id = $_POST['selected_id'] = $_POST['name'] = $_POST['basic_acc'] = '';

//--------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE);
if(!empty($USE_DEPT_ACC))
    $th = array(_('Id'), _('Department Name'), _('Salary Basic Account'), '', '');
else
	$th = array(_('Id'), _('Department Name'), '', '');
inactive_control_column($th);
table_header($th);

$result = db_query(get_departments(false, check_value('show_inactive')));

$k = 0;
while ($myrow = db_fetch($result)) {
	alt_table_row_color($k);

	label_cell($myrow['dept_id']);
	label_cell($myrow['dept_name']);
	if(!empty($USE_DEPT_ACC))
		label_cell($myrow['basic_account']);
	inactive_control_cell($myrow['dept_id'], $myrow['inactive'], 'department', 'dept_id');
	edit_button_cell('Edit'.$myrow['dept_id'], _('Edit'));
	delete_button_cell('Delete'.$myrow['dept_id'], _('Delete'));
	end_row();
}
inactive_control_row($th);
end_table(1);

start_table(TABLESTYLE2);

if($selected_id != '') {
	
 	if ($Mode == 'Edit') {
		
		$myrow = get_departments($selected_id);
		$_POST['name']  = $myrow['dept_name'];
		$_POST['basic_acc'] = $myrow['basic_account'];
 	}
 	hidden('selected_id', $selected_id);
}

text_row_ex(_('Department Name').':', 'name', 50, 60);

if(!empty($USE_DEPT_ACC))
    gl_all_accounts_list_row(_('Salary Basic Account'), 'basic_acc', null, true, false, _('Select basic account'));
else
	hidden('basic_acc');

end_table(1);

submit_add_or_update_center($selected_id == '', '', 'both');

end_form();
end_page();