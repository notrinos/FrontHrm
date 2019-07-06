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

function add_salary_structure($pay_rules=array()) {

	if(empty($pay_rules))
		return false;

	begin_transaction();
    
		foreach($pay_rules as $rule)
			insert_structure($rule['position_id'], $rule['grade_id'], $rule['pay_rule_id'], $rule['pay_amount'], $rule['type']);

	commit_transaction();
}

function write_personal_salary_structure($pay_rules=array()) {

	if(empty($pay_rules))
		return false;
    
	foreach($pay_rules as $rule)
		write_personal_structure($rule['emp_id'], $rule['pay_rule_id'], $rule['pay_amount'], $rule['type'], $rule['is_basic']);
}

//--------------------------------------------------------------------------

function insert_structure($position_id, $grade_id, $pay_rule_id, $pay_amount, $type) {

	$date = date2sql(Today());
	$sql = "INSERT INTO ".TB_PREF."salary_structure(date, position_id, grade_id, pay_rule_id, pay_amount, type) VALUES ('"
	    .$date."',"
	    .db_escape($position_id).","
	    .db_escape($grade_id).","
	    .db_escape($pay_rule_id).","
	    .db_escape($pay_amount).","
	    .db_escape($type)
	.")";

	db_query($sql, _('could not add salary structure'));
}

function write_personal_structure($emp_id, $pay_rule_id, $pay_amount, $type, $is_basic = 0) {

	$date = date2sql(Today());

	if($pay_amount == 0)
		$sql = "DELETE FROM ".TB_PREF."personal_salary_structure WHERE emp_id = ".db_escape($emp_id)." AND pay_rule_id = ".db_escape($pay_rule_id)." AND is_basic = ".db_escape($is_basic);
	elseif(!check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."personal_salary_structure WHERE emp_id = ".db_escape($emp_id)." AND pay_rule_id = ".db_escape($pay_rule_id)." AND is_basic = ".db_escape($is_basic))) {
		$sql = "INSERT INTO ".TB_PREF."personal_salary_structure(date, emp_id, pay_rule_id, pay_amount, type, is_basic) VALUES ('"
		.$date."',"
	    .db_escape($emp_id).","
	    .db_escape($pay_rule_id).","
	    .db_escape($pay_amount).","
	    .db_escape($type).","
	    .db_escape($is_basic)
	    .")";
	}
	else {
		$sql = "UPDATE ".TB_PREF."personal_salary_structure SET date = '".$date."', pay_amount = ".db_escape($pay_amount).", type = ".db_escape($type).", is_basic = ".db_escape($is_basic)." WHERE emp_id = ".db_escape($emp_id)." AND pay_rule_id = ".db_escape($pay_rule_id);
	}

	db_query($sql, _('could not write salary structure'));
}

function update_personal_basic_account($emp_id, $acc) {
	$sql = "UPDATE ".TB_PREF."personal_salary_structure SET pay_rule_id = ".db_escape($acc)." WHERE emp_id = ".db_escape($emp_id)." AND is_basic = 1";
	db_query($sql, _('could not write salary structure'));
}

//--------------------------------------------------------------------------

function exists_salary_structure($position_id) {

	return check_empty_result("SELECT id FROM ".TB_PREF."salary_structure WHERE position_id = ".db_escape($position_id)." AND !is_basic");
}

//--------------------------------------------------------------------------

function delete_salary_structure($position_id, $grade_id) {

	$sql = "DELETE FROM ".TB_PREF."salary_structure WHERE position_id =".db_escape($position_id)." AND grade_id = ".db_escape($grade_id)." AND !is_basic";
    db_query($sql, _('could not delete salary structure'));
}

//--------------------------------------------------------------------------

function get_salary_structure($position_id, $grade_id = 0) {

	$sql = "SELECT st.*, ac.account_name FROM ".TB_PREF."salary_structure st, ".TB_PREF."chart_master ac WHERE ac.account_code = st.pay_rule_id AND st.position_id = ".db_escape($position_id)." AND st.grade_id = ".db_escape($grade_id);

  	return db_query($sql, _('Could not get salary structure'));
}

//--------------------------------------------------------------------------

function get_emp_salary_structure($emp_id) {

	$person = get_employees($emp_id);

	if(empty($person['personal_salary']))
		$sql = "SELECT s.* FROM ".TB_PREF."salary_structure s, ".TB_PREF."employee e WHERE s.position_id = e.position_id AND s.grade_id = e.grade_id AND e.emp_id = ".db_escape($emp_id);
	else
		$sql = "SELECT * FROM ".TB_PREF."personal_salary_structure WHERE emp_id = ".db_escape($emp_id);

	return db_query($sql, _('Could not get employee salary structure'));
}