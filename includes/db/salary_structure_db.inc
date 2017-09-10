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

function add_salary_structure($pay_rules=array()) {

	if(empty($pay_rules))
		return false;

	begin_transaction();
    
		foreach($pay_rules as $rule) {
        
			insert_structure($rule['salary_scale_id'],
			$rule['pay_rule_id'],$rule['pay_amount'],$rule['type']);
		}
	commit_transaction();
}

//--------------------------------------------------------------------------

function insert_structure($salary_scale_id, $pay_rule_id,$pay_amount, $type) {

	$date_ = date2sql(Today());
	$sql = "INSERT INTO ".TB_PREF."salary_structure(date,salary_scale_id, pay_rule_id,pay_amount,type) VALUES ('"
	.$date_."',".db_escape($salary_scale_id).",".db_escape($pay_rule_id).","
	.db_escape($pay_amount).",".db_escape($type).")";

	db_query($sql, "could not add salary structure");
}

//--------------------------------------------------------------------------

function exists_salary_structure($id) {

	return check_empty_result("SELECT id FROM ".TB_PREF."salary_structure WHERE salary_scale_id = ".db_escape($id));
}

//--------------------------------------------------------------------------

function delete_salary_structure($salary_scale_id) {

	$sql = "DELETE FROM ".TB_PREF."salary_structure WHERE salary_scale_id =".db_escape($salary_scale_id);
    db_query($sql, "could not reset salary structure role");
}

//--------------------------------------------------------------------------

function get_salary_structure($salary_scale_id) {

	$sql = "SELECT st.*, ac.account_name FROM ".TB_PREF."salary_structure st, ".TB_PREF."chart_master ac WHERE ac.account_code = st.pay_rule_id AND st.salary_scale_id =".db_escape($salary_scale_id);

  	return db_query($sql, "Could not get salary structure");
}

//--------------------------------------------------------------------------

function get_emp_salary_structure($emp_id) {

	$sql = "SELECT s.* FROM ".TB_PREF."salary_structure s, ".TB_PREF."employee emp WHERE s.salary_scale_id = emp.salary_scale_id AND emp.emp_id =".db_escape($emp_id);

	return db_query($sql, "Could not get employee salary structure");
}
