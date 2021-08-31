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

include_once($path_to_root.'/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root.'/modules/FrontHrm/includes/frontHrm_ui.inc');

//----------------------------------------------------------------------------------------------------

print_employee_payslip();

//----------------------------------------------------------------------------------------------------

function get_payslip_allowance($payslip_no) {
	$sql = "SELECT * FROM ".TB_PREF."payslip_details WHERE payslip_no = ".db_escape($payslip_no);
	return db_query($sql, _('Could not get payslip details'));
}
function get_pay_period($payslip_no) {
	
	$sql = "SELECT from_date, to_date FROM ".TB_PREF."payslip WHERE payslip_no = $payslip_no";
	$result = db_fetch(db_query($sql, 'Could not get payslip details.'));
	$from = $result['from_date'];
	$to = $result['to_date'];
	
	return array($from, $to);
}
function get_emp_att($emp, $from, $to) {
	
	$sql = "SELECT * FROM ".TB_PREF."attendance WHERE emp_id = ".db_escape($emp)." AND (att_date BETWEEN '$from' AND '$to')";
	$result = db_query($sql, 'Could not get employee attendance data.');

	return $result;
}
function get_day_amount($payslip_no, $work_days) {
	
	$sql = "SELECT salary_amount, deductable_leaves FROM ".TB_PREF."payslip WHERE payslip_no = ".db_escape($payslip_no);
	
	$result = db_query($sql, 'could not get payslip details.');
	$row = db_fetch($result);

	$amount = ($row['salary_amount'] / ($work_days + $row['deductable_leaves']));

	return $amount;
}
function get_payslip_allocated_advances($payslip_no) {
	$sql = "SELECT SUM(a.amount) FROM ".TB_PREF."employee_advance_allocation a, ".TB_PREF."employee_trans t WHERE t.id = a.trans_no_from AND t.payslip_no = ".db_escape($payslip_no);
	$result = db_query($sql, _('could not get employee allocations amount'));
	$row = db_fetch($result);

	return $row[0];
}

//----------------------------------------------------------------------------------------------------

function print_employee_payslip() {
	global $path_to_root, $Work_hours;

	include_once($path_to_root.'/includes/ui/ui_globals.inc');
	include_once($path_to_root.'/reporting/includes/pdf_report.inc');

	$payslip_from = $_POST['PARAM_0'];
	$payslip_to = $_POST['PARAM_1'];
	$comments = $_POST['PARAM_2'];
	$orientation = $_POST['PARAM_3'] ? 'L' : 'P';
	$dec = user_price_dec();

	$rep = new FrontReport(_('Payslip'), 'payslip', 'A4', 9, $orientation);

	$x = $rep->getPageWidth();
	$y = $rep->getPageHeight();

	$cols = array(4, 70, 200, 300, 350, 400, 450, 515);
	$headers = array(_('Pay Element'), '', _('Quantity'), _('Leaves'), '', '', _('Earnings'));
	$aligns = array('left',	'left',	'left', 'left', 'left', 'left', 'left');
	$params = array('comments' => '');

	for($i = $payslip_from; $i <= $payslip_to; $i++) {
		$payslip = get_payslip(false, $i);
		$emp = get_employees($payslip['emp_id']);
		$emp_name = $emp['emp_first_name'].' '.$emp['emp_last_name'];
		$emp_id = $emp['emp_id'];
		if($emp['department_id'] != 0)
			$emp_dept = get_departments($emp['department_id'])['dept_name'];
		else
			$emp_dept = _('Not set');
		$payslip_no = $payslip['payslip_no'];
		$from = get_pay_period($payslip_no)[0];
		$to = get_pay_period($payslip_no)[1];

		$overtimes = get_overtime();
		$emp_payslip = get_emp_att($emp_id, $from, $to);
		$work_days = 0;
		$leave_hours = 0;
		foreach($emp_payslip as $row) {
	
			if($row['overtime_id'] == 0) {
				$work_days++;
				if($row['hours_no'] != $Work_hours)
					$leave_hours += ($Work_hours - $row['hours_no']);
			}
		}
		$day_amount = get_day_amount($payslip_no, $work_days);
		$basic_amount = ($day_amount * $work_days) - (($day_amount/$Work_hours)*$leave_hours);
		$total_earn = $basic_amount;

		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->SetHeaderType('payslip_layout');
		$rep->NewPage();

		$rep->row = $rep->row - $rep->topMargin - (7*$rep->lineHeight);

		$rep->TextCol(0, 1,	_('Employee:'));
		$rep->TextCol(1, 4,	$emp_name);
		$rep->TextCol(4, 5,	_('Payslip #:'));
		$rep->TextCol(5, 6,	$payslip_no);
		$rep->NewLine();
		$rep->TextCol(0, 1,	_('ID:'));
		$rep->TextCol(1, 3,	'&nbsp;'.$emp_id);
		$rep->TextCol(4, 5,	_('From:'));
		$rep->TextCol(5, 7,	sql2date($from));
		$rep->NewLine();
		$rep->TextCol(0, 1,	_('Department:'));
		$rep->TextCol(1, 4,	'&nbsp;'.$emp_dept);
		$rep->TextCol(4, 5,	_('To:'));
		$rep->TextCol(5, 7,	sql2date($to));

		$rep->NewLine(9);
		$rep->lineHeight *= 1.5;
		$rep->TextCol(0, 2,	_('Regular time'));
		$rep->TextCol(2, 3,	$work_days.'&nbsp;'._('days'));
		$rep->TextCol(3, 4,	$leave_hours.'&nbsp;'._('hours'));
		$rep->TextCol(6, 7,	price_format($basic_amount, $dec));

		foreach(db_query($overtimes) as $overtime) {
	
			$emp_payslip = get_emp_att($emp_id, $from, $to);
			$overtime_hours = 0;
			$overtime_amount = 0;
			$time_name = $overtime['overtime_name'];
	
			while($row = db_fetch($emp_payslip)) {
				if($row['overtime_id'] == $overtime['overtime_id']) {
					$overtime_hours += $row['hours_no'];
					$overtime_amount += (($day_amount/$Work_hours)*$row['rate'])*$row['hours_no'];
				}
			}
	
			$total_earn += $overtime_amount;
			$rep->NewLine();
			$rep->TextCol(0, 2,	$time_name);
			$rep->TextCol(2, 3,	$overtime_hours.'&nbsp;'._('hours'));
			$rep->TextCol(6, 7,	price_format($overtime_amount, $dec));
		}
		foreach (get_payslip_allowance($payslip_no) as $row) {
			$element = get_payroll_elements(false, $row['detail']);
			$element_name = $element['element_name'];
			$allowance_amount = $row['amount'];
			$total_earn += $allowance_amount;
			$rep->NewLine();
			$rep->TextCol(0, 2,	$element_name);
			$rep->TextCol(6, 7,	price_format($allowance_amount, $dec));
		}
		$allocated = get_payslip_allocated_advances($payslip_no);
		$total_earn -= $allocated;
		$rep->NewLine();
		$rep->TextCol(0, 2,	_('Advance Deduction'));
		$rep->TextCol(6, 7,	price_format(0-$allocated, $dec));
		$rep->NewLine(2);
		$rep->Font('bold');
		$rep->TextCol(4, 6,	_('Total Salary'));
		$rep->TextCol(6, 7,	price_format($total_earn, $dec));
		$rep->Font();
	}

	$rep->End();
}