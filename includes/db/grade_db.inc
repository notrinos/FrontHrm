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

function grade_exist($grade, $position) {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."grade_table WHERE grade_id = ".db_escape($grade)." AND position_id = ".db_escape($position));
}

function add_grade_table($grade, $position, $amount) {
    $sql= "INSERT INTO ".TB_PREF."grade_table (grade_id, position_id, amount) VALUES (".db_escape($grade).", ".db_escape($position).", ".db_escape($amount).")";
    
    db_query($sql, _('could not insert grade data'));
}

function update_grade_table($grade, $position, $amount) {
	$sql = "UPDATE ".TB_PREF."grade_table SET amount = ".db_escape($amount)." WHERE grade_id = ".db_escape($grade)." AND position_id = ".db_escape($position);
	db_query($sql, _('could not update grade data'));
}

function get_position_grades($position) {
    $sql = "SELECT * FROM ".TB_PREF."grade_table WHERE position_id = ".db_escape($position);
    
    return db_query($sql, _('Could not get grade data'));
}

function get_grade_amount($position, $grade) {
	$sql = "SELECT amount FROM ".TB_PREF."grade_table WHERE position_id = ".db_escape($position)." AND grade_id = ".db_escape($grade);
	$result = db_query($sql, _('could not get grade data'));
	$amount = db_fetch($result)[0];

	return $amount;
}

function get_all_grades() {
	$sql = "SELECT * FROM ".TB_PREF."grade_table";

	return db_query($sql, _('could not read grade table data'));
}

function delete_grade($position) {

    $sql = "DELETE FROM ".TB_PREF."grade_table WHERE position_id = ".db_escape($position);
    db_query($sql, _('Could not delete grade table'));
}

function max_grade_used() {
	$sql = "SELECT MAX(grade_id) FROM ".TB_PREF."employee WHERE !inactive";
	$result = db_query($sql, _('could not get employee data'));
	$max = db_fetch($result)[0];

	return $max;
}

function set_grade_salary($account, $amount, $position_id, $grade_id, $new=false) {
    $date = date2sql(Today());

	if($new)
	    $sql = "INSERT INTO ".TB_PREF."salary_structure(date, position_id, grade_id, pay_rule_id, pay_amount, type, is_basic) VALUES ('$date', ".db_escape($position_id).", ".db_escape($grade_id).", ".db_escape($account).", ".db_escape($amount).", 1, 1)";
	else
		$sql = "UPDATE ".TB_PREF."salary_structure SET pay_rule_id = ".db_escape($account).", pay_amount = ".db_escape($amount)." WHERE position_id = ".db_escape($position_id)." AND grade_id = ".db_escape($grade_id);
	
	    db_query($sql, _('Could not write salary structure data'));
}