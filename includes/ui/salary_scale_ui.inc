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

function salaryscale_list($name, $id = null, $spec_option = false, $submit_on_change = false, $show_inactive = false)
{
	$sql = "SELECT scale_id, scale_name, inactive FROM ".TB_PREF."salaryscale ";

	return combo_input($name, $id, $sql, 'scale_id', 'scale_name',
	array(       
		'spec_option' => $spec_option,
		'select_submit' => $submit_on_change,
		'async' => false,
		'show_inactive' => $show_inactive
	));
}

//--------------------------------------------------------------------------

function salaryscale_list_cells($label, $name, $id = null, $all_option = false, $submit_on_change = false, $show_inactive = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo salaryscale_list($name, $id, $all_option, $submit_on_change, $show_inactive);
	echo "</td>\n";
}

//--------------------------------------------------------------------------

function salaryscale_list_row($label, $name, $id = null, $all_option = false, $submit_on_change = false, $show_inactive = false)
{
	echo "<tr><td class='label'>$label</td>";
	salaryscale_list_cells(null, $name, $id, $all_option, 
	$submit_on_change, $show_inactive);
	echo "</tr>";
}