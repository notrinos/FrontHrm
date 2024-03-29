<?php
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương <trananhphuong83@gmail.com>          |
|   Date :   09-Jul-2017                                 |
|   Description: NotrinosERP Payroll & Hrm Module        |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

function department_list($name, $id = null, $spec_option = false, $submit_on_change = false, $show_inactive = false) {
	$sql = "SELECT dept_id, dept_name, inactive FROM ".TB_PREF."department ";

	return combo_input($name, $id, $sql, 'dept_id', 'dept_name',
	array(
		'spec_option' => $spec_option,
		'select_submit' => $submit_on_change,
		'async' => false,
		'show_inactive' => $show_inactive
	));
}

//--------------------------------------------------------------------------

function department_list_cells($label, $name, $id = null, $all_option = false, $submit_on_change = false, $show_inactive = false) {
	if ($label != null)
		echo '<td>'.$label."</td>\n";
	echo '<td>';
	echo department_list($name, $id, $all_option, $submit_on_change, $show_inactive);
	echo "</td>\n";
}

//--------------------------------------------------------------------------

function department_list_row($label, $name, $id = null, $all_option = false, $submit_on_change = false, $show_inactive = false) {
	echo "<tr><td class='label'>".$label.'</td>';
	department_list_cells(null, $name, $id, $all_option, 
	$submit_on_change, $show_inactive);
	echo '</tr>';
}
