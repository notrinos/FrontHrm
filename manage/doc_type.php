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

page(_($help_context = 'Manage Document Categories'));
simple_page_mode(false);

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') {

	$input_error = 0;

	if (strlen($_POST['name']) == 0 || empty(trim($_POST['name']))) {
		$input_error = 1;
		display_error(_('The document type description cannot be empty.'));
		set_focus('name');
	}
    elseif (!is_numeric($_POST['days'])) {
		$input_error = 1;
		display_error(_('Days before expiry must be a number.'));
		set_focus('days');
	}
	if ($input_error != 1) {
    	write_doc_type($selected_id, $_POST['name'], $_POST['days'] );
		if($selected_id != '')
			display_notification(_('Selected document type has been updated'));
		else
			display_notification(_('New document type item has been added'));
		$Mode = 'RESET';
	}
}

//--------------------------------------------------------------------------

if ($Mode == 'Delete') {

	if (doc_type_used($selected_id))
		display_error(_('This document category cannot be deleted.'));
	else {
		delete_doc_type($selected_id);
		display_notification(_('Selected document category has been deleted'));
	}
	$Mode = 'RESET';
}

if ($Mode == 'RESET') {
	$selected_id = '';
	$_POST['selected_id'] = '';
	$_POST['name'] = '';
	$_POST['days'] = '';
}

//--------------------------------------------------------------------------

$result = db_query(get_doc_types(false, check_value('show_inactive')));

start_form();
start_table(TABLESTYLE);
$th = array(_('Id'), _('Description'), _('Alert Before Expiry'), "", "");
inactive_control_column($th);

table_header($th);
$k = 0;

while ($myrow = db_fetch($result)) {

	alt_table_row_color($k);
	label_cell($myrow['type_id']);
	label_cell($myrow['type_name']);
	label_cell($myrow['notify_before'].'&nbsp;'._('days'));
	inactive_control_cell($myrow['type_id'], $myrow['inactive'], 'document_types', 'type_id');
 	edit_button_cell('Edit'.$myrow['type_id'], _('Edit'));
 	delete_button_cell('Delete'.$myrow['type_id'], _('Delete'));
    
	end_row();
}

inactive_control_row($th);
end_table(1);

start_table(TABLESTYLE2);

if ($selected_id != '') {
	
 	if ($Mode == 'Edit') {
		
		$myrow = get_doc_types($selected_id);
		$_POST['days'] = $myrow['notify_before'];
		$_POST['name']  = $myrow['type_name'];
		hidden('selected_id', $myrow['type_id']);
	}
}

text_row(_('Description:'), 'name', null, 40, 50);

text_row(_('Alert before expiry:'), 'days', null, 10, 10, null, '', _('days'));

end_table(1);

submit_add_or_update_center($selected_id == '', '', 'both');

end_form();
end_page();