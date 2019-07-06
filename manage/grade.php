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

page(_($help_context = 'Manage Grades'));
simple_page_mode(true);

$grades_no = get_company_pref('payroll_grades');

if($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') {

	$basic_acc = db_fetch(get_salary_structure($_POST['position_id']))['pay_rule_id'];

	begin_transaction();
	for($i=1; $i<=$max_grade_number; $i++) {

		if(isset($_POST['amt_'.$i]) && $_POST['amt_'.$i] < 0) {
			display_error(_('Pay amount cannot be a negative number'));
			set_focus('amt_'.$i);
			exit();
		}
		$position = get_position($_POST['position_id']);
		$amt = input_num('amt_'.$i) > 0 ? input_num('amt_'.$i) : $position['pay_amount'];

		if(!grade_exist($i, $_POST['position_id'])) {
			add_grade_table($i, $_POST['position_id'], $amt);
			$new = true;
		}
		else {
			update_grade_table($i, $_POST['position_id'], $amt);
			$new = false;
		}

		set_grade_salary($basic_acc, $amt, $_POST['position_id'], $i, $new);
	}
	commit_transaction();
	display_notification(_('Grade table has been updated'));

	$Mode = 'RESET';
}

if ($Mode == 'Delete') {

	if(grade_used($selected_id))
		display_error( _('Grade table for selected job position cannot be deleted.'));
	else {
		delete_grade($selected_id);
		display_notification(_('Grade table of selected job position has been deleted'));
	}
	$Mode = 'RESET';
}

if($Mode == 'RESET') {
	$selected_id = -1;
	unset($_POST);
}

//--------------------------------------------------------------------------

start_form();

if(!db_has_position()) {
	display_note(_('Please define Job Positions First'));
	display_footer_exit();
}

start_table(TABLESTYLE);

$th = array(_('Job Position'), _('Basic Amount'));
for($i=1; $i<=$grades_no; $i++)
	$th[] = _('Grade').'&nbsp;'.$i;

array_push($th, '', '');
table_header($th);

$all_positions = db_query(get_position());

$k = 0;
while ($position = db_fetch($all_positions)) {

	alt_table_row_color($k);
	label_cell($position['position_name']);
	amount_cell($position['pay_amount']);
	$grades_row = get_position_grades($position['position_id']);

	for($i=1;$i<=$grades_no;$i++) {
		$pay = get_grade_amount($position['position_id'], $i);
		if(empty($pay))
			label_cell('');
		else
			amount_cell($pay);
	}

	edit_button_cell('Edit'.$position['position_id'], _('Edit'));
	delete_button_cell('Delete'.$position['position_id'], _('Delete'));
	end_row();
}
end_table(1);

start_table(TABLESTYLE2);

if($selected_id != -1) {
	
 	if($Mode == 'Edit') {
 		unset($_POST);
		$position = get_position($selected_id);
		$myrow = get_position_grades($selected_id);
		$_POST['position_id'] = $selected_id;
		
		foreach($myrow as $val) {
			$grade = $val['grade_id'];
			$_POST['amt_'.$grade] = empty($val['amount']) ? price_format($position['pay_amount']) : price_format($val['amount']);
		}
 	}
 	hidden('selected_id', $selected_id);
}

position_list_row(_('Job Position').':', 'position_id');

if($selected_id != -1) {
	$position = get_position($selected_id);
	label_row(_('Basic Amount:'), '&nbsp;'.price_format($position['pay_amount']));
}

for($i=1; $i<=$grades_no; $i++)
	amount_row(_('Grade').'&nbsp'.$i, 'amt_'.$i, null, null, null, null, true);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();
end_page();