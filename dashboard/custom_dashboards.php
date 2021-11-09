<?php
/**********************************************************************
	Copyright (C) NotrinosERP.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

add_access_extensions();
$dashboard->addDashboard(_('HRM'), DA_HRM);
$dashboard->addWidget(DA_HRM, 901, WIDGET_HALF);
$dashboard->addWidget(DA_HRM, 902, WIDGET_HALF);
$dashboard->addWidget(DA_HRM, 903, WIDGET_HALF);
$dashboard->addWidget(DA_HRM, 904, WIDGET_HALF);

function employee_by_dept() {
	$sql = "SELECT COUNT(e.emp_id) total, d.dept_name FROM ".TB_PREF."employee e, ".TB_PREF."department d WHERE e.department_id = d.dept_id GROUP BY dept_id";

	return db_query($sql, 'could not get employee data');
}

function employees_by_age() {
	
	$ages = array(20, 30, 40, 50, 60);

	$sql = "SELECT 
	(SELECT COUNT(*) FROM (SELECT TIMESTAMPDIFF(YEAR, emp_birthdate, CURDATE()) AS age FROM ".TB_PREF."employee WHERE !inactive) AS tbl WHERE tbl.age < ".$ages[0].") AS 'Under ".$ages[0]."', 
	(SELECT COUNT(*) FROM (SELECT TIMESTAMPDIFF(YEAR, emp_birthdate, CURDATE()) AS age FROM ".TB_PREF."employee WHERE !inactive) AS tbl WHERE tbl.age >= ".$ages[0]." AND tbl.age < ".$ages[1].") AS '".$ages[0]."-".($ages[1]-1)."',
	(SELECT COUNT(*) FROM (SELECT TIMESTAMPDIFF(YEAR, emp_birthdate, CURDATE()) AS age FROM ".TB_PREF."employee WHERE !inactive) AS tbl WHERE tbl.age >= ".$ages[1]." AND tbl.age < ".$ages[2].") AS '".$ages[1]."-".($ages[2]-1)."', 
	(SELECT COUNT(*) FROM (SELECT TIMESTAMPDIFF(YEAR, emp_birthdate, CURDATE()) AS age FROM ".TB_PREF."employee WHERE !inactive) AS tbl WHERE tbl.age >= ".$ages[2]." AND tbl.age < ".$ages[3].") AS '".$ages[2]."-".($ages[3]-1)."', 
	(SELECT COUNT(*) FROM (SELECT TIMESTAMPDIFF(YEAR, emp_birthdate, CURDATE()) AS age FROM ".TB_PREF."employee WHERE !inactive) AS tbl WHERE tbl.age >= ".$ages[3]." AND tbl.age < ".$ages[4].") AS '".$ages[3]."-".($ages[4])."', 
	(SELECT COUNT(*) FROM (SELECT TIMESTAMPDIFF(YEAR, emp_birthdate, CURDATE()) AS age FROM ".TB_PREF."employee WHERE !inactive) AS tbl WHERE tbl.age >= ".$ages[4].") AS 'Above ".$ages[4]."'" ;
	$result = db_query($sql, 'could not get employee data');

	return db_fetch_assoc($result);
}
