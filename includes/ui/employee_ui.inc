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

function employee_list($name, $id=null, $spec_option=false, $submit_on_change=false, $show_inactive=false, $dept=false, $multi=false) {
	$sql = "SELECT emp_id, CONCAT(emp_first_name, ' ', emp_last_name) AS name, inactive FROM ".TB_PREF."employee ";

	$options = array(
		'spec_option' => $spec_option,
		'select_submit' => $submit_on_change,
		'where' => $dept == false ? array('1') : array("department_id = $dept"),
		'async' => false,
		'multi' => $multi,
		'show_inactive' => $show_inactive
	);
	if(is_array($id))
		$options['where'][] = "emp_id IN (".implode(',', $id).")";

	return combo_input($name, $id, $sql, 'emp_id', 'name', $options);
}

//--------------------------------------------------------------------------

function employee_list_cells($label, $name, $id=null, $all_option=false, $submit_on_change=false, $show_inactive=false, $dept=false, $multi=false) {
	if ($label != null)
		echo '<td>'.$label."</td>\n";
	echo "<td>";
	echo employee_list($name, $id, $all_option, $submit_on_change, $show_inactive, $dept, $multi);
	echo "</td>\n";
}

//--------------------------------------------------------------------------

function employee_list_row($label, $name, $id=null, $all_option=false, $submit_on_change=false, $show_inactive=false, $dept=false, $multi=false) {
	echo "<tr><td class='label'>".$label.'</td>';
	employee_list_cells(null, $name, $id, $all_option, $submit_on_change, $show_inactive, $dept, $multi);
	echo '</tr>';
}

//--------------------------------------------------------------------------

function gender_radio_row($label, $name, $gender=null) {

	echo "<tr><td class='label'>".$label.'</td><td>';
	
	if($gender==0){
		echo "<input type='radio' name=".$name." value='1'>"._('Male')."&nbsp;&nbsp;&nbsp;";
		echo "<input type='radio' name=".$name." value='0' checked>"._('Female')."&nbsp;&nbsp;&nbsp;";
		echo "<input type='radio' name=".$name." value='2'>"._('Other');
	}
	elseif($gender==1) {
		echo "<input type='radio' name=".$name." value='1' checked>"._('Male')."&nbsp;&nbsp;&nbsp;";
		echo "<input type='radio' name=".$name." value='0'>"._('Female')."&nbsp;&nbsp;&nbsp;";
		echo "<input type='radio' name=".$name." value='2'>"._('Other');
	}
	else {
		echo "<input type='radio' name=".$name." value='1'>"._('Male')."&nbsp;&nbsp;&nbsp;";
		echo "<input type='radio' name=".$name." value='0'>"._('Female')."&nbsp;&nbsp;&nbsp;";
		echo "<input type='radio' name=".$name." value='2' checked>"._('Other');
	}
	echo '</td></tr>';
}