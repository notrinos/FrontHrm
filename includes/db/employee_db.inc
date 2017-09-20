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

function write_employee($id=false, $first_name, $last_name, $gender, $adrs, $mobile, $mail, $birth, $notes, $hire_date, $department, $salary, $release, $active)
{
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
		.db_escape($notes).", '"
		.date2sql($hire_date)."', "
		.db_escape($department).", "
		.db_escape($salary).", '"
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
		emp_notes = ".db_escape($notes).", 
		emp_hiredate = '".date2sql($hire_date)."', 
		department_id = ".db_escape($department).", 
		salary_scale_id = ".db_escape($salary).", 
		emp_releasedate = '".date2sql($release)."', 
		inactive = ".db_escape($active)." 
		WHERE emp_id = ".db_escape($id);
	}
	
	db_query($sql, 'Could not write employee data');
}

//--------------------------------------------------------------------------

function get_employees($id = false, $all = false, $department = false) {
	
	$sql = "SELECT *, CONCAT(emp_first_name, ' ', emp_last_name) AS name FROM ".TB_PREF."employee";
	$where = array();
	
	if(!$all)
		$where[] = "!inactive";
	if($id)
		$where[] = "emp_id=".db_escape($id);
	if($department)
		$where[] = "department_id = ".db_escape($department);
		
	if(count($where))
		$sql .= ' WHERE '.implode(' AND ', $where);
	else
		$sql .= ' WHERE 1';

	$result = db_query($sql, "Could not get employee data.");
	
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

//--------------------------------------------------------------------------

function emp_img_name($emp_id) {

	$emp_id = strtr($emp_id, "><\\/:|*?", '________');
	return clean_file_name($emp_id);
}

//--------------------------------------------------------------------------

function check_employee_hired($id, $date) {

    $sql = "SELECT emp_hiredate FROM ".TB_PREF."employee WHERE emp_id = ".db_escape($id);
    $result = db_query($sql, "could not get employee data");
    $check = db_fetch($result);
    
    if(sql_date_comp(date2sql($date), $check[0]) < 0)
        return false;
    else
        return true;
}

//--------------------------------------------------------------------------

function employee_has_salary_scale($id) {
	return check_empty_result("SELECT salary_scale_id FROM ".TB_PREF."employee WHERE salary_scale_id != 0 AND emp_id = ".db_escape($id));
}

//--------------------------------------------------------------------------

function emp_salaryscale_has_structure($id) {
	return check_empty_result("SELECT e.salary_scale_id, s.* FROM ".TB_PREF."employee e, ".TB_PREF."salary_structure s WHERE e.salary_scale_id = s.salary_scale_id AND e.emp_id = ".db_escape($id));
}
