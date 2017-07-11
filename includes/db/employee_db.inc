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

function db_has_employee() {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."employee");
}

//--------------------------------------------------------------------------

function add_employee($first_name, $last_name, $adrs, $mobile, $mail, $birth, $notes)
{
	
	$sql = "INSERT INTO ".TB_PREF."employee(
	emp_first_name,
	emp_last_name,
	emp_address,
	emp_mobile,
	emp_email,
	emp_birthdate,
	emp_notes
	) VALUES ("
	.db_escape($first_name).", "
	.db_escape($last_name).", "
	.db_escape($adrs).", "
	.db_escape($mobile).", "
	.db_escape($mail).", '"
	.date2sql($birth)."', "
	.db_escape($notes)
	.")";
	
	db_query($sql, 'Employee details could not be added.');
}

//--------------------------------------------------------------------------

function update_employee($id, $first_name, $last_name, $adrs, $mobile, $mail, $birth, $notes)
{
	$sql = "UPDATE ".TB_PREF."employee SET 
		emp_first_name = ".db_escape($first_name).", 
		emp_last_name = ".db_escape($last_name).", 
		emp_address = ".db_escape($adrs).", 
		emp_mobile = ".db_escape($mobile).", 
		emp_email = ".db_escape($mail).", 
		emp_birthdate = '".date2sql($birth)."', 
		emp_notes = ".db_escape($notes)." 
		WHERE emp_id = ".db_escape($id);
	
	db_query($sql, 'Could not write employee data');
}

//--------------------------------------------------------------------------

function get_employees($id=false, $all = false) {
	
	$sql = "SELECT *, CONCAT(emp_first_name, ' ', emp_last_name) AS name FROM ".TB_PREF."employee";
	$where = array();
	
	if($id)
		$where[] = "emp_id=".db_escape($id);
		
	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);

	$result = db_query($sql, "could not get teacher");
	
	if($id)
		return db_fetch($result);
	else
		return $sql;
}

//--------------------------------------------------------------------------

function delete_employee($id) {
	$sql = "DELETE FROM ".TB_PREF."employee WHERE emp_id = ".db_escape($id);
	db_query($sql, 'Could not delete employee data.');
}