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

$path_to_root = '../../..';
include_once($path_to_root . '/reporting/includes/tcpdf.php');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_db.inc');
include_once($path_to_root . '/modules/FrontHrm/includes/frontHrm_ui.inc');

$pdf = new TCPDF("P", 'mm', 'A4', true, 'UTF-8');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(16,0,16);

$pdf->SetAutoPageBreak(TRUE, 2);

if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

//--------------------------------------------------------------------------

$x = $pdf->getPageWidth();
$y = $pdf->getPageHeight();
$img_width = 80;

$payslip = get_payslip(false, $_POST['PARAM_0']);
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

$myrow = get_company_prefs();
$comp_name = $myrow['coy_name'];
$comp_adrs = $myrow['postal_address'];
$comp_phone = $myrow['phone'];
$comp_logo = $myrow['coy_logo'];

$head = "<table border='0.5' cellpadding='5'>
			<tr>
				<td colspan=3>
					Employee: $emp_name <br />
					ID : $emp_id <br />
					Department: $emp_dept
				</td>
				<td>
					Payslip no: $payslip_no <br />
					From: ".sql2date($from)."<br />
					To : ".sql2date($to)."
				</td>
			</tr>
		</table>";

$title = "<table border='0.5' cellpadding='3'>
		    <tr>
		        <td>Description</td>
		 	    <td>Quantity</td>
			    <td>Leaves</td>
			    <td>Earning</td>
			</tr>
		</table>";

$contents = "<table border='0.5' cellpadding='3'>";

$overtimes = get_overtime();
$emp_payslip = get_emp_att($payslip_no, $emp_id);
$work_days = 0;
$leave_hours = 0;

$contents .= "<tr>
				<td>Regular time</td>";

while($row = db_fetch($emp_payslip)){
	
	if($row['overtime_id'] == 0) {
		$work_days += 1;
		if($row['hours_no'] != $Work_hours)
			$leave_hours += ($Work_hours - $row['hours_no']);
	}
}

$day_amount = get_day_amount($payslip_no);
$basic_amount = ($day_amount* $work_days) - (($day_amount/$Work_hours)*$leave_hours);
$total_earn = $basic_amount;

$contents .= "<td>$work_days".'&nbsp;'._('days')."</td>
			 <td>$leave_hours".'&nbsp;'._('hours')."</td>
			 <td style='text-align:right'>".price_format($basic_amount)."</td>
			 </tr>";

foreach(db_query($overtimes) as $overtime) {
	
	$emp_payslip = get_emp_att($payslip_no, $emp_id);
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
	$contents .= "<tr>
				 <td>$time_name</td>
				 <td>$overtime_hours hours</td>
				 <td></td>";
	if($overtime_hours > 0)
		$contents .= "<td style='text-align:right'>".price_format($overtime_amount)."</td>";
	else
		$contents .= "<td></td>";
	
	$contents .= "</tr>";
}
foreach (get_payslip_allowance($payslip_no) as $row) {
	$element = get_payroll_elements(false, $row['detail']);
	$element_name = $element['element_name'];
	$allowance_amount = $row['amount'];
	$total_earn += $allowance_amount;
	$contents .= "<tr><td colspan='3'>$element_name</td><td style='text-align:right'>".price_format($allowance_amount)."</td></tr>";
}

$allocated = get_payslip_allocated_advances($payslip_no);
$total_earn -= $allocated;
$contents .= "<tr><td colspan='3'>"._('Advance Deduction')."</td><td style='text-align:right'>".price_format(0-$allocated)."</td></tr>";

$contents .= "<tr>
				<td colspan='3' align='right'><b>Total salary</b></td><td style='text-align:right'>".price_format($total_earn)."</td>
			 </tr>
		 </table>";

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
function get_emp_att($payslip_no, $emp) {
	global $from, $to;
	
	$sql = "SELECT * FROM ".TB_PREF."attendance WHERE emp_id = ".db_escape($emp)." AND att_date BETWEEN '$from' AND '$to'";
	$result = db_query($sql, 'Could not get employee attendance data.');

	return $result;
}
function get_day_amount($payslip_no) {
	global $work_days;
	$sql = "SELECT salary_amount, deductable_leaves FROM ".TB_PREF."payslip WHERE payslip_no = ".db_escape($payslip_no);
	
	$result = db_query($sql, "could not get payslip details.");
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

//--------------------------------------------------------------------------

$pdf->AddPage('P', 'A4');

if($comp_logo)
	$logo_path = company_path().'/images/'.$comp_logo;
else
	$logo_path = $path_to_root . '/themes/default/images/logo_frontaccounting.jpg';

$pdf->Image($logo_path, $x/2 - $img_width/2, 20, $img_width);

$pdf->writeHTMLCell(2*$x/3-30, 0, 15, 40, _('Company:').'&nbsp;'.$comp_name, 0, 0, 0, true);
$pdf->writeHTMLCell($x/3-30, 0, 2*$x/3+20, 45, _('Date:').'&nbsp;'.Today(), 0, 0, 0, true);
$pdf->writeHTMLCell($x, 0, 15, 45, _('Address:').'&nbsp;'.$comp_adrs, 0, 0, 0, true);
$pdf->writeHTMLCell($x, 0, 15, 50, _('Phone:').'&nbsp;'.$comp_phone, 0, 0, 0, true);

$pdf->SetFont('', 'BI', 25);
$pdf->Write(0, _('Payslip'), '', 0, 'C', true, 0, false, false, 0);

$pdf->SetFont('', '', 10);
$pdf->writeHTMLCell($x-30, 0, 15, 85, $head, 0, 0, 0, true);
$pdf->writeHTML("");

$pdf->SetFont('', 'B', 10);
$pdf->writeHTMLCell($x-30, 0, 15, 123.5, $title, 0, 0, 0, true, 'C');
$pdf->setFont('', '', 10);
$pdf->writeHTMLCell($x-30, 0, 15, 130, $contents, 0, 0, 0, true);

//--------------------------------------------------------------------------

$pdf->Output($path_to_root . '/modules/FrontHrm/reports/payslip_'.$payslip_no.'.pdf', 'I');