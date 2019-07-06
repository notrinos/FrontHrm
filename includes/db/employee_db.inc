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

function db_has_employee() {
	return check_empty_result("SELECT COUNT(*) FROM ".TB_PREF."employee");
}

//--------------------------------------------------------------------------

function write_employee($id=false, $first_name, $last_name, $gender, $adrs, $mobile, $mail, $birth, $national_id, $passport, $bank_account, $tax_number, $notes, $hire_date, $department, $position, $grade_id, $personal_salary, $release, $active) {

	db_query("SET sql_mode = ''");

	if(!$id) {
		$sql = "INSERT INTO ".TB_PREF."employee VALUES ("
		.db_escape($id).", "
		.db_escape($first_name).", "
		.db_escape($last_name).", "
		.db_escape($gender).", "
		.db_escape($adrs).", "
		.db_escape($mobile).", "
		.db_escape($mail).", '"
		.date2sql($birth)."', "
		.db_escape($national_id).", "
		.db_escape($passport).", "
		.db_escape($bank_account).", "
		.db_escape($tax_number).", "
		.db_escape($notes).", '"
		.date2sql($hire_date)."', "
		.db_escape($department).", "
		.db_escape($position).", "
		.db_escape($grade_id).", "
		.db_escape($personal_salary).", '"
		.date2sql($release)."', "
		.db_escape($active)
		.")";
	}
	else {
		$sql = "UPDATE ".TB_PREF."employee SET 
		emp_first_name = ".db_escape($first_name).", 
		emp_last_name = ".db_escape($last_name).", 
		gender = ".db_escape($gender).", 
		emp_address = ".db_escape($adrs).", 
		emp_mobile = ".db_escape($mobile).", 
		emp_email = ".db_escape($mail).", 
		emp_birthdate = '".date2sql($birth)."', 
		national_id = ".db_escape($national_id).", 
		passport = ".db_escape($passport).", 
		bank_account = ".db_escape($bank_account).", 
		tax_number = ".db_escape($tax_number).", 
		emp_notes = ".db_escape($notes).", 
		emp_hiredate = '".date2sql($hire_date)."', 
		department_id = ".db_escape($department).", 
		position_id = ".db_escape($position).", 
		grade_id = ".db_escape($grade_id).", 
		personal_salary = ".db_escape($personal_salary).", 
		emp_releasedate = '".date2sql($release)."', 
		inactive = ".db_escape($active)." 
		WHERE emp_id = ".db_escape($id);
	}
	
	db_query($sql, _('Could not write employee data'));
}

//--------------------------------------------------------------------------

function get_employees($id = false, $all=false, $department=false, $gender=false, $position_id=false, $grade_id = -1, $str=false) {
	
	$sql = "SELECT *, CONCAT(emp_first_name, ' ', emp_last_name) AS name FROM ".TB_PREF."employee";
	$where = array();
	
	if(!$all)
		$where[] = "!inactive";
	if($id)
		$where[] = "emp_id=".db_escape($id);
	if(!empty($department))
		$where[] = "department_id = ".db_escape($department);
	if($gender)
		$where[] = "gender = ".db_escape($gender);
	if(!empty($position_id))
		$where[] = "position_id = ".db_escape($position_id);
	if($grade_id >= 0)
		$where[] = "grade_id = ".db_escape($grade_id);
	if(!empty($str))
		$where[] = "(emp_first_name LIKE ".db_escape("%$str%")." OR emp_last_name LIKE ".db_escape("%$str%")." OR emp_email LIKE ".db_escape("%$str%")." OR emp_address LIKE ".db_escape("%$str%")."  OR emp_mobile LIKE ".db_escape("%$str%")."  OR national_id LIKE ".db_escape("%$str%")."  OR passport LIKE ".db_escape("%$str%")."  OR tax_number LIKE ".db_escape("%$str%").")";

		
	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);
	else
		$sql .= ' WHERE 1';

	$result = db_query($sql, _('Could not get employee data.'));
	
	if($id)
		return db_fetch($result);
	else
		return $sql;
}

//--------------------------------------------------------------------------

function delete_employee($id) {
	$sql = "DELETE FROM ".TB_PREF."employee WHERE emp_id = ".db_escape($id);
	db_query($sql, _('Could not delete employee data.'));
}

//--------------------------------------------------------------------------

function emp_img_name($emp_id) {

	$emp_id = strtr($emp_id, "><\\/:|*?", '________');
	return clean_file_name($emp_id);
}

//--------------------------------------------------------------------------

function check_employee_hired($id, $date) {

    $sql = "SELECT emp_hiredate FROM ".TB_PREF."employee WHERE emp_id = ".db_escape($id);
    $result = db_query($sql, _('could not get employee data'));
    $check = db_fetch($result);
    
    if(sql_date_comp(date2sql($date), $check[0]) < 0)
        return false;
    else
        return true;
}

//--------------------------------------------------------------------------

function employee_has_position($id) {
	return check_empty_result("SELECT position_id FROM ".TB_PREF."employee WHERE position_id != 0 AND emp_id = ".db_escape($id));
}

//--------------------------------------------------------------------------

function emp_position_has_structure($id) {
	return check_empty_result("SELECT e.position_id, s.* FROM ".TB_PREF."employee e, ".TB_PREF."salary_structure s WHERE e.position_id = s.position_id AND e.emp_id = ".db_escape($id));
}
