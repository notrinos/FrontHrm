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

page(_($help_context = "Manage Overtime"));
simple_page_mode(false);

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') {

	$input_error = 0;

	if (strlen($_POST['rate']) == 0 || $_POST['rate'] == '') {
		$input_error = 1;
		display_error(_("The overtime rate cannot be empty."));
		set_focus('rate');
	}
    if (!is_numeric($_POST['rate'])) {
		$input_error = 1;
		display_error(_("Overtime rate must be a number."));
		set_focus('rate');
	}
	if (strlen($_POST['name']) == 0 || $_POST['name'] == '') {
		$input_error = 1;
		display_error(_("The overtime name cannot be empty."));
		set_focus('name');
	}
	if ($input_error !=1) {
    	write_overtime($selected_id, $_POST['name'], $_POST['rate'] );
		if($selected_id != '')
			display_notification(_('Selected overtime has been updated'));
		else
			display_notification(_('New overtime item has been added'));
		$Mode = 'RESET';
	}
}

//--------------------------------------------------------------------------

if ($Mode == 'Delete') {

	if (overtime_used($selected_id))
		display_error(_("This overtime cannot be deleted."));
	else {
        
		delete_overtime($selected_id);
		display_notification(_('Selected overtime item has been deleted'));
	}
	$Mode = 'RESET';
}

if ($Mode == 'RESET') {
    
	$selected_id = $_POST['selected_id'] = $_POST['name'] = $_POST['rate'] = '';
}

//--------------------------------------------------------------------------

$result = db_query(get_overtime(false, check_value('show_inactive')));

start_form();
start_table(TABLESTYLE);
$th = array(_('Id'), _('Overtime Name'), _('Overtime Rate'), "", "");
inactive_control_column($th);

table_header($th);
$k = 0;

while ($myrow = db_fetch($result)) {

	alt_table_row_color($k);
	label_cell($myrow["overtime_id"]);
	label_cell($myrow["overtime_name"]);
	amount_cell($myrow["overtime_rate"]);
	inactive_control_cell($myrow["overtime_id"], $myrow["inactive"], 'overtime', 'overtime_id');
 	edit_button_cell("Edit".$myrow["overtime_id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["overtime_id"], _("Delete"));
    
	end_row();
}

inactive_control_row($th);
end_table(1);

start_table(TABLESTYLE2);

if ($selected_id != '') {
	
 	if ($Mode == 'Edit') {
		
		$myrow = get_overtime($selected_id);
		$_POST['rate'] = $myrow["overtime_rate"];
		$_POST['name']  = $myrow["overtime_name"];
		hidden('selected_id', $myrow["overtime_id"]);
	}
}
if ($selected_id != '' && overtime_used($selected_id)) {
    label_row(_("Overtime rate:"), $_POST['rate']);
    hidden('rate', $_POST['rate']);
} else
    text_row(_("Overtime rate:"), 'rate', null, 20, 20);

text_row(_("Overtime Name:"), 'name', null, 40, 50);

end_table(1);

submit_add_or_update_center($selected_id == '', '', 'both');

end_form();
end_page();