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

function db_has_position() {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."position");
}

function set_basic_salary($account, $amount, $position_id, $new=false) {
    $date = date2sql(Today());

	if($new)
	    $sql = "INSERT INTO ".TB_PREF."salary_structure(date, position_id, pay_rule_id, pay_amount, type, is_basic) VALUES ('$date', ".db_escape($position_id).", ".db_escape($account).", ".db_escape($amount).", 1, 1)";
	else
		$sql = "UPDATE ".TB_PREF."salary_structure SET pay_rule_id = ".db_escape($account).", pay_amount = ".db_escape($amount)." WHERE position_id = ".db_escape($position_id);
	
	    db_query($sql, _('Could not write salary structure data'));
}

function write_position($id = false, $name, $pay_basis) {
	
	if(!$id)
		$sql = "INSERT INTO ".TB_PREF."position(position_name, pay_basis) VALUES (".db_escape($name).",".db_escape($pay_basis).")";
	else
		$sql = "UPDATE ".TB_PREF."position SET position_name = ".db_escape($name).", pay_basis = ".db_escape($pay_basis)." WHERE position_id = ".db_escape($id);
	
	db_query($sql, _('Could not write job position data'));
}

function delete_position($id) {
	$sql = "DELETE FROM ".TB_PREF."position WHERE position_id = ".db_escape($id);;
	db_query($sql, _('could not delete job position'));
}

function get_position($id = false, $all = false) {
    
	$sql = "SELECT p.*, s.pay_amount, s.pay_rule_id FROM ".TB_PREF."position p, ".TB_PREF."salary_structure s";
	$where = array("p.position_id = s.position_id AND s.is_basic = 1 AND s.grade_id = 0");
	
	if(!$all)
		$where[] = "!inactive";
	if($id)
		$where[] = "p.position_id = ".db_escape($id);
		
	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);
	else
		$sql .= ' WHERE 1';

	$result = db_query($sql, _('Could not get job position data.'));
	
	if($id)
		return db_fetch($result);
	else
		return $sql;
}

function position_used($id) {
	return  check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."employee WHERE position_id = ".db_escape($id));
}

function get_emp_basic_salary($emp_id, $new_staff = false) {

	$person = get_employees($emp_id);

	if(empty($person['personal_salary']) || $new_staff)
		$sql = "SELECT s.pay_amount, s.pay_rule_id FROM ".TB_PREF."salary_structure s, ".TB_PREF."employee e WHERE e.position_id = s.position_id AND s.grade_id = e.grade_id AND s.is_basic = 1 AND e.emp_id = ".db_escape($emp_id);
	else
		$sql = "SELECT pay_amount, pay_rule_id FROM ".TB_PREF."personal_salary_structure WHERE is_basic = 1 AND emp_id = ".db_escape($emp_id);

	$result = db_query($sql, _('could not get salary structure'));
	return db_fetch($result);
}

function is_expenses_account($acc) {
	$sql = "SELECT act_class.ctype FROM ".TB_PREF."chart_class act_class, ".TB_PREF."chart_types act_type, ".TB_PREF."chart_master coa WHERE coa.account_type=act_type.id AND act_type.class_id=act_class.cid AND coa.account_code=".db_escape($acc);

	$result = db_query($sql, _('could not retreive the account class for ').$acc);
	$row = db_fetch_row($result);
	return $row[0] == CL_EXPENSE;
}