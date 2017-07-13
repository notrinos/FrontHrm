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

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_db.inc");
include_once($path_to_root . "/modules/FrontHrm/includes/frontHrm_ui.inc");

//--------------------------------------------------------------------------

page(_($help_context = "Manage Department"));
simple_page_mode(false);

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') {

	if(strlen($_POST['name']) == 0 || $_POST['name'] == '') {
		display_error( _("The Department name cannot be empty."));
		set_focus('name');
	}
	else {
		write_department($selected_id, $_POST['name']);
		
    	if ($selected_id != "")
			display_notification(_('Selected department has been updated'));
    	else
			display_notification(_('New department has been added'));
		
		$Mode = 'RESET';
	}
}

if ($Mode == 'Delete') {

	if(department_has_employees($selected_id))
		display_error( _("The Department cannot be deleted."));
	else {
		delete_department($selected_id);
		display_notification(_('Selected department has been deleted'));
	}
	$Mode = 'RESET';
}

if($Mode == 'RESET')
	$selected_id = $_POST['selected_id']  = $_POST['name'] = '';

//--------------------------------------------------------------------------

start_form();

start_table(TABLESTYLE);
$th = array(_("Deparment Id"), _("Department Name"), "", "");
inactive_control_column($th);
table_header($th);

$result = db_query(get_departments(false, check_value('show_inactive')));
$k = 0;
while ($myrow = db_fetch($result)) {
	alt_table_row_color($k);

	label_cell($myrow["dept_id"]);
	label_cell($myrow['dept_name']);
	inactive_control_cell($myrow["dept_id"], $myrow["inactive"], 'department', 'dept_id');
	edit_button_cell("Edit".$myrow["dept_id"], _("Edit"));
	delete_button_cell("Delete".$myrow["dept_id"], _("Delete"));
	end_row();
}
inactive_control_row($th);
end_table(1);

start_table(TABLESTYLE2);

if($selected_id != '') {
	
 	if ($Mode == 'Edit') {
		
		$myrow = get_departments($selected_id);
		$_POST['name']  = $myrow["dept_name"];
		hidden('selected_id', $myrow['dept_id']);
 	}
}

text_row_ex(_("Department Name:"), 'name', 50, 60);

end_table(1);

submit_add_or_update_center($selected_id == '', '', 'both');

end_form();
end_page();
