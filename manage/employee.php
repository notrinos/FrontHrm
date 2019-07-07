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

$page_security = 'SA_EMPL';
$path_to_root  = '../../..';

include_once($path_to_root . '/includes/db_pager.inc');
include_once($path_to_root . '/includes/session.inc');
add_access_extensions();

$js = "";
if($SysPrefs->use_popup_windows)
	$js .= get_js_open_window(900, 500);
if(user_use_date_picker())
	$js .= get_js_date_picker();

include_once($path_to_root . '/includes/ui.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');

//--------------------------------------------------------------------------

foreach(db_query(get_employees(false, true)) as $emp_row) {
	
	if(isset($_POST[$emp_row['emp_id']])) {
		
		$_SESSION['EmpId'] = $emp_row['emp_id'];
		$_POST['_tabs_sel'] = 'add';
		$Ajax -> activate('_page_body');
	}
}

$cur_id = isset($_SESSION['EmpId']) ? $_SESSION['EmpId'] : '';

$upload_file = "";
$avatar_path = company_path().'/FrontHrm/images/';
if(isset($_FILES['pic']) && $_FILES['pic']['name'] != '') {
	
	$result = $_FILES['pic']['error'];
 	$upload_file = 'Yes';
	$filename = $avatar_path;
    
    if(!file_exists(company_path().'/FrontHrm')) {
		mkdir(company_path().'/FrontHrm');
		copy(company_path().'/index.php', company_path().'/FrontHrm/index.php');
    }
	if(!file_exists($filename)) {
		mkdir($filename);
		copy(company_path().'/index.php', $filename.'index.php');
	}
	
	$filename .= emp_img_name($cur_id).'.jpg';
	
	if($_FILES['pic']['error'] == UPLOAD_ERR_INI_SIZE) {

		display_error(_('The file size is over the maximum allowed.'));
		$upload_file = 'No';
	}
	elseif($_FILES['pic']['error'] > 0) {

		display_error(_('Error uploading file.'));
		$upload_file = 'No';
	}
	if((list($width, $height, $type, $attr) = getimagesize($_FILES['pic']['tmp_name'])) !== false)
		$imagetype = $type;
	else
		$imagetype = false;

	if($imagetype != IMAGETYPE_GIF && $imagetype != IMAGETYPE_JPEG && $imagetype != IMAGETYPE_PNG) {

		display_warning( _('Only graphics files can be uploaded'));
		$upload_file = 'No';
	}
	elseif(!in_array(strtoupper(substr(trim($_FILES['pic']['name']), strlen($_FILES['pic']['name']) - 3)), array('JPG','PNG','GIF'))) {

		display_warning(_('Only graphics files are supported - a file extension of .jpg, .png or .gif is expected'));
		$upload_file ='No';
	}
	elseif( $_FILES['pic']['size'] > ($SysPrefs->max_image_size * 1024)) {

		display_warning(_('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . $SysPrefs->max_image_size);
		$upload_file ='No';
	} 
	elseif( $_FILES['pic']['type'] == "text/plain" ) {

		display_warning( _('Only graphics files can be uploaded'));
        $upload_file ='No';
	}
	elseif(file_exists($filename)) {

		$result = unlink($filename);
		if(!$result) {
			display_error(_('The existing image could not be removed'));
			$upload_file ='No';
		}
	}
	if($upload_file == 'Yes')
		$result  =  move_uploaded_file($_FILES['pic']['tmp_name'], $filename);
	
	$Ajax->activate('_page_body');
}

//--------------------------------------------------------------------------

function can_process() {
	
	if(strlen($_POST['emp_first_name']) == 0 || $_POST['emp_first_name'] == '') {
		display_error(_('The employee first name must be entered.'));
		set_focus('emp_first_name');
		return false;
	}
	if(strlen($_POST['emp_last_name']) == 0 || $_POST['emp_last_name'] == '') {
		display_error(_('Employee last name must be entered.'));
		set_focus('emp_last_name');
		return false;
	}
	if(strlen($_POST['emp_email']) > 0 && !filter_var($_POST['emp_email'], FILTER_VALIDATE_EMAIL)) {
		display_error(_('Invalid email.'));
		set_focus('emp_email');
		return false;
	}
	if(!is_date($_POST['emp_birthdate'])) {
		display_error(_('Invalid birth date.'));
		set_focus('emp_birthdate');
		return false;
	}
	if(!is_date($_POST['emp_hiredate']) && $_POST['emp_hiredate'] != null && $_POST['emp_hiredate'] != '00/00/0000') {
		display_error(_('Invalid hire date.'));
		set_focus('emp_hiredate');
		return false;
	}
	if(!empty($_POST['personal_salary']) && !check_num('basic_amt', FLOAT_COMP_DELTA)) {
		display_error(_('Basic salary amount must be a positive number'));
		set_focus('basic_amt');
		return false;
	}
	if(!empty($_POST['personal_salary']) && empty($_POST['position_id'])) {
		display_error(_("Staff's Job Position must be selected to use Personal Salary Structure"));
		set_focus('position_id');
		return false;
	}
	if(get_post('inactive') == 1) {
	    if(!is_date($_POST['emp_releasedate'])) {
	    	display_error( _('Invalid release date.'));
	    	set_focus('emp_releasedate');
	    	return false;
	    }
	}
	return true;
}

//--------------------------------------------------------------------------

function can_delete($cur_id) {

	$employee = get_employees($cur_id, true);

	if($employee['emp_hiredate'] && $employee['emp_hiredate'] != '0000-00-00') {
		display_error('Employed person cannot be deleted.');
		return false;
	}
	return true;
}

//--------------------------------------------------------------------------

function id_link($row) {
	return button($row['emp_id'], $row['emp_id']);
}
function get_name($row) {
	return "<b>".button($row['emp_id'], $row['emp_first_name'].' '.$row['emp_last_name'])."</b>";
}
function gender_name($row) {
	if($row['gender'] == 0)
		return  _('Female');
	elseif($row['gender'] == 1)
	    return _('Male');
	else
	    return _('Other');
}
function emp_hired($row) {
	return ($row['emp_hiredate'] == '0000-00-00') ? _('Not hired') : '<center>'.sql2date($row['emp_hiredate']).'</center>';
}
function emp_department($row) {
	
	if($row['emp_hiredate'] == '0000-00-00' || $row['department_id'] == 0)
		return _('Not selected');
	else
		return get_departments($row['department_id'])['dept_name'];
}

function employees_table() {
	
	$_SESSION['EmpId'] = '';
	if(db_has_employee()) {
		
		$sys_grades = get_company_pref('payroll_grades');
		$sql = get_employees(false, check_value('show_inactive'), get_post('DeptId'), false, get_post('position'), get_post('grade'), get_post('string'));
		
		start_table(TABLESTYLE_NOBORDER);
		start_row();
		ref_cells(_("Enter Search String:"), 'string', _('Enter fragment or leave empty'), null, null, true);
		department_list_cells(null, 'DeptId', null, _('All departments'), true);
		position_list_cells(null, 'position', null, _('All Positions'), true);
		number_list_cells(null, 'grade', null, 1, $sys_grades, _('All Grades'), true);
		check_cells(_('Show resigned:'), 'show_inactive', null, true);
		submit_cells('Search', _('Search'), '', '', 'default');
		end_row();
		end_table(1);
		
        $cols = array(
          _('ID'),
		  'first_name' => 'skip',
          _('Name') => array('fun'=>'get_name'),
		  _('Gender') => array('fun'=>'gender_name'),
		  'address' => 'skip',
		  _('Mobile') => array(),
		  _('Email'),
		  _('Birth') => array('type'=>'date'),
		  'notes' => 'skip',
		  _('Hired Date') => array('fun'=>'emp_hired'),
		  _('Department') => array('fun'=>'emp_department')
        );

        $table =& new_db_pager('emp_tbl', $sql, $cols);
        $table->width = "80%";
        $table->page_len = 50;
	
	    // display_note(_('Press name to edit employee details.'));
        display_db_pager($table);
	}
	else
		display_note(_('No employee defined.'), 1);
}

//--------------------------------------------------------------------------

function employee_settings($cur_id) {
	global $path_to_root, $avatar_path;
	
	if($cur_id) {
		$employee = get_employees($cur_id, true);
		$_POST['emp_first_name'] = $employee['emp_first_name'];
		$_POST['emp_last_name'] = $employee['emp_last_name'];
		$_POST['gender'] = $employee['gender'];
		$_POST['emp_address'] = $employee['emp_address'];
		$_POST['emp_mobile'] = $employee['emp_mobile'];
		$_POST['emp_email'] = $employee['emp_email'];
		$_POST['emp_birthdate'] = sql2date($employee['emp_birthdate']);
		$_POST['national_id'] = $employee['national_id'];
		$_POST['passport'] = $employee['passport'];
		$_POST['bank_account'] = $employee['bank_account'];
		$_POST['tax_number'] = $employee['tax_number'];
		$_POST['emp_notes'] = $employee['emp_notes'];
		$_POST['emp_hiredate'] = sql2date($employee['emp_hiredate']);
		$_POST['department_id'] = $employee['department_id'];
		$_POST['position_id'] = $employee['position_id'];
		$_POST['grade_id'] = $employee['grade_id'];
		$_POST['personal_salary'] = $employee['personal_salary'];
		$_POST['emp_releasedate'] = sql2date($employee['emp_releasedate']);
		$_POST['inactive'] = $employee['inactive'];

		if(!empty($employee['personal_salary'])) {
			$emp_salary = get_emp_salary_structure($cur_id);
			foreach($emp_salary as $pay_element) {

				$element_code = $pay_element['pay_rule_id'];

				if($pay_element['is_basic'] == 1)
					$_POST['basic_amt'] = price_format($pay_element['pay_amount']);
				else
					$_POST['amt_'.$element_code] = price_format($pay_element['pay_amount']);
			}
		}
	}
	start_outer_table(TABLESTYLE2);

	table_section(1);
	hidden('emp_id');

	file_row(_('Image File:'), 'pic', 'pic');
	$emp_img_link = '';
	$check_remove_image = false;
	if($cur_id && file_exists($avatar_path.emp_img_name($cur_id).'.jpg')) {
		$emp_img_link .= "<img id='emp_img' alt = '[".$cur_id.".jpg".
			"]' src='".$avatar_path.emp_img_name($cur_id).
			".jpg?nocache=".rand()."'"." height='100'>";
		$check_remove_image = true;
	} 
	else 
		$emp_img_link .= "<img id='emp_img' alt = '.jpg' src='".$path_to_root."/modules/FrontHrm/images/avatar/no_image.svg' height='100'>";

	label_row("&nbsp;", $emp_img_link);
	if($check_remove_image)
		check_row(_('Delete Image:'), 'del_image');
	
	table_section_title(_('Personal Information'));

	if($cur_id)
		label_row(_('Employee Id:'), $cur_id);

	text_row(_('First Name:'), 'emp_first_name', get_post('emp_first_name'), 37, 50);
	text_row(_('Last Name:'), 'emp_last_name', get_post('emp_last_name'), 37, 50);
	gender_radio_row(_('Gender:'), 'gender', get_post('gender'));
	textarea_row(_('Address:'), 'emp_address', get_post('emp_address'), 35, 5);
	text_row(_('Mobile:'), 'emp_mobile', get_post('emp_mobile'), 37, 30);
	email_row(_('e-Mail:'), 'emp_email', get_post('emp_email'), 37, 100);
	date_row(_('Birth Date:'), 'emp_birthdate', null, null, 0, 0, -18);
	
	table_section(2);
	
	table_section_title(_('Personal Information'));
	text_row(_('National ID:'), 'national_id', get_post('national_id'), 37, 50);
	text_row(_('Passport:'), 'passport', get_post('passport'), 37, 50);
	text_row(_('Bank Name/Account:'), 'bank_account', get_post('bank_account'), 37, 50);
	text_row(_('Tax ID Number:'), 'tax_number', get_post('tax_number'), 37, 50);

	table_section_title(_('Job Information'));
	
	textarea_row(_('Notes:'), 'emp_notes', null, 35, 5);
	date_row(_('Hire Date:'), 'emp_hiredate', null, null, 0, 0, 1001);
	
	if($cur_id) {
		if($employee['emp_hiredate'] != '0000-00-00')
			department_list_row(_('Department:'), 'department_id', null, _('Not selected'));
		else {
			label_row(_('Department:'), _('Set hire date first'));
			hidden('department_id');
		}
	}
	else
		department_list_row(_('Department:'), 'department_id', null, _('Not selected'));
		
	position_list_row(_('Job Position:'), 'position_id', null, _('Not selected'));
	$sys_grades = get_company_pref('payroll_grades');
	number_list_row(_('Salary Grade:'), 'grade_id', null, 1, $sys_grades, _('Basic'));
	if($cur_id) {
		check_row(_('Resigned:'), 'inactive');
		date_row(_('Release Date:'), 'emp_releasedate', null, null, 0, 0, 1001);
	}
	else{
		hidden('inactive');
		hidden('emp_releasedate');
	}

	table_section(3);
	table_section_title(_('Pay Elements'));

	start_row();
	label_cell('(?)', "title='"._('Enter negative amount for deduction, positive for earning')."' align='right' colspan='2'");
	end_row();

	yesno_list_row(_('Use Personal Salary Structure:'), 'personal_salary');

	amount_row(_('Basic Salary Amount:'), 'basic_amt', null, null, null, null, true);
	$elements = get_payroll_elements();
	while($row = db_fetch($elements)) {
		amount_row($row['element_name'].':', 'amt_'.$row['account_code'], null, null, null, null, true);
	}

	end_outer_table(1);
	
	div_start('controls');
	
	if($cur_id) {
		
		submit_center_first('addupdate', _('Update Employee'), _('Update employee details'), 'default');
		submit_return('select', get_post('emp_id'), _('Select this employee and return to document entry.'));
		submit_center_last('delete', _('Delete Employee'), _('Delete employee data if have been never used'), true);
	}
	else
		submit_center('addupdate', _('Add New Employee Details'), true, '', 'default');
	
	div_end();
}

//--------------------------------------------------------------------------

if(isset($_POST['addupdate'])) {
	
	if(!can_process())
		return;

	if(!empty($_POST['personal_salary']))
		begin_transaction();

	write_employee(
		$cur_id,
		$_POST['emp_first_name'],
		$_POST['emp_last_name'],
		$_POST['gender'],
		$_POST['emp_address'],
		$_POST['emp_mobile'],
		$_POST['emp_email'],
		$_POST['emp_birthdate'],
		$_POST['national_id'],
		$_POST['passport'],
		$_POST['bank_account'],
		$_POST['tax_number'],
		$_POST['emp_notes'],
		$_POST['emp_hiredate'],
		$_POST['department_id'],
		$_POST['position_id'],
		$_POST['grade_id'],
		$_POST['personal_salary'],
		$_POST['emp_releasedate'],
		$_POST['inactive']
	);

	if($cur_id) {
		$emp_id = $cur_id;
		$new = false;
	}
	else {
		$emp_id = db_insert_id();
		$new = true;
	}

	if(!empty($_POST['personal_salary'])) {
		
		$basic = get_position($_POST['position_id']);
		$basic_acc = $basic['pay_rule_id'];
		$pay_elements = array();
		$pay_elements[] = array(
			'emp_id' => $emp_id,
			'pay_rule_id' => $basic_acc,
			'pay_amount' => input_num('basic_amt'),
			'type' => DEBIT,
			'is_basic' => 1
		);

		foreach($_POST as $p=>$val) {
			if(substr($p, 0, 4) == 'amt_') {
				$pay_elements[] = array(
					'emp_id' => $emp_id,
					'pay_rule_id' => substr($p, 4),
					'pay_amount' => input_num($p),
					'type' => input_num($p) > 0 ? DEBIT : CREDIT,
					'is_basic' => 0
				);
			}
		}
		write_personal_salary_structure($pay_elements);
		commit_transaction();
	}

	if(check_value('del_image')) {
		$filename = $avatar_path.emp_img_name($cur_id).".jpg";
		if (file_exists($filename))
			unlink($filename);
	}
	if($cur_id) {
		$_SESSION['EmpId'] = $cur_id;
		display_notification(_('Employee details has been updated.'));
	}
	else {
		$_SESSION['EmpId'] = $emp_id;
		$cur_id = $_SESSION['EmpId'];
		display_notification(_('A new employee has been added.'));
	}

	$Ajax->activate('_page_body');
}
elseif(isset($_POST['delete'])) {

	if(!can_delete($cur_id))
		return;
	delete_employee($cur_id);
	display_notification(_('Employee details has been deleted.'));
	$Ajax -> activate('_page_body');
}

//--------------------------------------------------------------------------

page(_($help_context = 'Manage Employees'), false, false, '', $js);

start_form(true);

tabbed_content_start(
	'tabs',
	array(
		'list' => array(_('Employees &List'), 999),
		'add' => array(_('&Add/Edit Employee'), 999)
	)
);

if(get_post('_tabs_sel') == 'list')
	employees_table();
elseif(get_post('_tabs_sel') == 'add')
	employee_settings($cur_id);

br();

tabbed_content_end();

end_form();
end_page();