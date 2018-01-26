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

function employee_list($name, $id = null, $spec_option = false, $submit_on_change = false, $show_inactive = false, $dept = false)
{
	$sql = "SELECT emp_id, CONCAT(emp_first_name, ' ', emp_last_name) AS name, inactive FROM ".TB_PREF."employee ";

	return combo_input($name, $id, $sql, 'emp_id', 'name',
	array(       
		'spec_option' => $spec_option,
		'select_submit' => $submit_on_change,
		'where' => $dept == false ? 1 : array("department_id = $dept"),
		'async' => false,
		'show_inactive' => $show_inactive
	));
}

//--------------------------------------------------------------------------

function employee_list_cells($label, $name, $id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $dept = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo employee_list($name, $id, $all_option, $submit_on_change, $show_inactive, $dept);
	echo "</td>\n";
}

//--------------------------------------------------------------------------

function employee_list_row($label, $name, $id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $dept = false)
{
	echo "<tr><td class='label'>$label</td>";
	employee_list_cells(null, $name, $id, $all_option, 
	$submit_on_change, $show_inactive, $dept);
	echo "</tr>";
}

//--------------------------------------------------------------------------

function gender_radio_row($label, $name, $gender=null) {
    echo "<tr><td class='label'>$label</td><td>";
	
    if($gender==0){
        echo "<input type='radio' name=$name value='1'>Male&nbsp;&nbsp;&nbsp;";
        echo "<input type='radio' name=$name value='0' checked>Female&nbsp;&nbsp;&nbsp;";
        echo "<input type='radio' name=$name value='2'>Other";
    }
    elseif($gender==1) {
        echo "<input type='radio' name=$name value='1' checked>Male&nbsp;&nbsp;&nbsp;";
        echo "<input type='radio' name=$name value='0'>Female&nbsp;&nbsp;&nbsp;";
        echo "<input type='radio' name=$name value='2'>Other";
    }
    else {
        echo "<input type='radio' name=$name value='1'>Male&nbsp;&nbsp;&nbsp;";
        echo "<input type='radio' name=$name value='0'>Female&nbsp;&nbsp;&nbsp;";
        echo "<input type='radio' name=$name value='2' checked>Other";
    }
    echo "</td></tr>";
}
