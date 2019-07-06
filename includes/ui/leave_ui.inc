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

function leave_types_list($name, $id = null, $spec_option = false, $submit_on_change = false, $show_inactive = false) {
	$sql = "SELECT leave_id, leave_name, inactive FROM ".TB_PREF."leave_type ";

	return combo_input($name, $id, $sql, 'leave_id', 'leave_name',
	array(       
		'spec_option' => $spec_option,
		'select_submit' => $submit_on_change,
		'async' => false,
		'show_inactive' => $show_inactive
	));
}

//--------------------------------------------------------------------------

function leave_types_list_cells($label, $name, $id = null, $all_option = false, $submit_on_change = false, $show_inactive = false) {
	if($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo leave_types_list($name, $id, $all_option, $submit_on_change, $show_inactive);
	echo "</td>\n";
}

//--------------------------------------------------------------------------

function leave_types_list_row($label, $name, $id = null, $all_option = false, $submit_on_change = false, $show_inactive = false) {
	echo "<tr><td class='label'>$label</td>";
	leave_types_list_cells(null, $name, $id, $all_option, $submit_on_change, $show_inactive);
	echo "</tr>";
}