<?php
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương                                      |
|   Date :   09-Jul-2017                                 |
|   Description: Frontaccounting Payroll & Hrm Module    |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

function db_has_salary_scale() {

	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."salaryscale");
}

//--------------------------------------------------------------------------

function set_basic_salary($account, $amount, $id, $new=false) {
    $date = date2sql(Today());

	if($new)
	    $sql = "INSERT INTO ".TB_PREF."salary_structure(date, salary_scale_id, pay_rule_id, pay_amount, type, is_basic) VALUES ('$date', ".db_escape($id).", ".db_escape($account).", ".db_escape($amount).", 1, 1)";
	else
		$sql = "UPDATE ".TB_PREF."salary_structure SET pay_rule_id = ".db_escape($account).", pay_amount = ".db_escape($amount)." WHERE salary_scale_id = ".db_escape($id);
	
	    db_query($sql, _('Could not write salary structure data'));
}

function write_scale($id = false, $name, $pay_basis) {
	
	if(!$id)
		$sql = "INSERT INTO ".TB_PREF."salaryscale(scale_name, pay_basis) VALUES (".db_escape($name).",".db_escape($pay_basis).")";
	else
		$sql = "UPDATE ".TB_PREF."salaryscale SET scale_name = ".db_escape($name).", pay_basis = ".db_escape($pay_basis)." WHERE scale_id = ".db_escape($id);
	
	db_query($sql, _('Could not write salary scale data'));
}

//--------------------------------------------------------------------------

function delete_salary_scale($id) {
	
	$sql = "DELETE FROM ".TB_PREF."salaryscale WHERE scale_id = ".db_escape($id);;
	db_query($sql, _('could not delete salary scale'));
}

//--------------------------------------------------------------------------

function get_salary_scale($id = false, $all = false) {
    
	$sql = "SELECT sc.*, st.pay_amount, st.pay_rule_id FROM ".TB_PREF."salaryscale sc, ".TB_PREF."salary_structure st";
	$where = array("sc.scale_id = st.salary_scale_id AND st.is_basic = 1");
	
	if(!$all)
		$where[] = "!inactive";
	if($id)
		$where[] = "scale_id=".db_escape($id);
		
	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);
	else
		$sql .= ' WHERE 1';

	$result = db_query($sql, _('Could not get salary scale data.'));
	
	if($id)
		return db_fetch($result);
	else
		return $sql;

}

//--------------------------------------------------------------------------

function salary_scale_used($id) {

	return  check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."employee WHERE salary_scale_id = ".db_escape($id));
}

//--------------------------------------------------------------------------

function get_emp_basic_salary($id) {
	$sql = "SELECT s.pay_amount, s.pay_rule_id FROM ".TB_PREF."salary_structure s, ".TB_PREF."employee e WHERE e.salary_scale_id = s.salary_scale_id AND s.is_basic = 1 AND e.emp_id = ".db_escape($id);

	$result = db_query($sql, _('could not get salary structure'));
	return db_fetch($result);
}