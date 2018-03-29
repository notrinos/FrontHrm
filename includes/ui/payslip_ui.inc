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

function get_work_days($start_date, $end_date) {

    $begin = strtotime($start_date);
    $end   = strtotime($end_date);
    
    if ($begin > $end)
        return 0;
    else {
        $no_days  = 0;
        $weekends = 0;
        while ($begin <= $end) {
            $no_days++;          // no of days in the given interval
            $what_day = date("N", $begin);
            if ($what_day > 6)   // 6 and 7 are weekend days
                $weekends++;
            
            $begin += 86400;     // +1 day
        }
        $working_days = $no_days - $weekends;

        return $working_days;
    }
}

//--------------------------------------------------------------------------

function display_payslip_header(&$Order) {
	global $Ajax, $Payable_act, $Work_days;

	$qes = has_quick_entries(QE_JOURNAL);
	$new = $Order->order_id==0;

	start_outer_table(TABLESTYLE2, "cellpadding='10'");

	table_section(1);
   		date_row(_('Date').':&nbsp;', 'date_', '', $new);
		ref_row(_('Reference').':', 'ref', '');

	table_section(3);
		date_row(_('From Date').':', 'from_date', '', $new, 0, 0, 0, null, true);
		date_row(_('To Date').':', 'to_date', '', $new, 0, 0, 0, null, true);

	table_section(2);
		hidden('PayType', PT_EMPLOYEE);
		employee_list_row(_('Pay To').':&nbsp;', 'person_id', null, _("Select employee"), true, false);
		
		if(!isset($_POST['PaySlipNo']))
			$_POST['PaySlipNo'] = get_next_payslip_no();
		
		hidden('PaySlipNo', $_POST['PaySlipNo']);
		if(isset($_POST['person_id']) && $_POST['person_id'] != 0)
		    label_row(_('Pay Basis').':', $Order->pay_basis == 0 ? _('Monthly salary') : _('Daily wages'));
		label_row(_('Payslip No').':', $_POST['PaySlipNo']);
	
	table_section(4);
    
    $from = $_POST['from_date'];
    $to = $_POST['to_date'];
    $ot = db_query(get_overtime());

    if($_POST['person_id'] != '') {
        $emp_id = $_POST['person_id'];
        $workdays = get_workdays_no($from, $to, $emp_id);
        $leave_hours = get_leave_hours($from, $to, $emp_id);
        label_row(_('Work days').':', $workdays.'&nbsp;'._('days'));
        label_row(_('Leave hours').':', $leave_hours.'&nbsp;'._('hours'));
        
        while($row = db_fetch($ot)) {
            $hours_no = 0;
            
            foreach(get_ot_from_date_range($from, $to, $emp_id, $row['overtime_id']) as $hours) {
                $hours_no += $hours['hours_no'];
                //$total_ot += $hours['hours_no'];
            }
            label_row($row['overtime_name'].':', $hours_no.' '._('hours'));
        }
        hidden('leaves', $leave_hours);
        hidden('deductableleaves', $Work_days - $workdays);
        hidden('workdays', $workdays);
    }
    else {
        $emp_id = '';
        label_row(_('Work days').':', '');
        label_row(_('Leave hours').':', '');
        while($row = db_fetch($ot)) {
            label_row($row['overtime_name'], '');
        }
    }

	if (list_updated('person_id'))
		$Order->clear_items();
	
	end_outer_table();
}

//--------------------------------------------------------------------------

function generate_gl_items($Order) {

	global $Ajax, $Payable_act, $Deduct_act, $Overtime_act, $Work_hours, $Work_days;

	$emp_id 	= $_POST['person_id'];
	$salary_scale = get_employees($emp_id)['salary_scale_id'];
	$from_date 	= $_POST['from_date'];
	$to_date 	= $_POST['to_date'];
    $workdays = get_workdays_no($from_date, $to_date, $emp_id);
    $total_ot = 0;

	$Order->clear_items();
	$totalCredit = $totalDebit = 0;
	$payable_amount = 0;
	$Order->empty_payment = false;
	$Order->pay_basis = get_salary_scale($salary_scale)['pay_basis'];
	$allowance = array();
	$salary_rules = get_emp_salary_structure($emp_id);
	$salary_amount = get_emp_basic_salary($emp_id)['pay_amount'];

	if($Order->pay_basis == DAILY_WAGE)
        $Work_days = 1;

    if(db_num_rows($salary_rules) > 0) {

		while($myrow = db_fetch($salary_rules) ) {

			if($Order->pay_basis == MONTHLY_SALARY)
			    $Order->add_gl_item($myrow['pay_rule_id'], 0, 0, ($myrow['type'] == CREDIT) ? -$myrow['pay_amount']:$myrow['pay_amount'], '');
			else {
			    if($workdays != 0)
			        $Order->add_gl_item($myrow['pay_rule_id'], 0, 0, ($myrow['type'] == CREDIT) ? -$myrow['pay_amount']*$workdays:$myrow['pay_amount']*$workdays, '');
			}
                
            if($workdays != 0) {
                if($myrow['type'] == CREDIT) 
				    $totalCredit += $myrow['pay_amount'];
                else 
				    $totalDebit += $myrow['pay_amount'];
            }
			    
			if($myrow['is_basic'] != 1)
			    $allowance[$myrow['pay_rule_id']] = $myrow['pay_amount'];

		}
		$Order->allowance = $allowance;
	}
	
    //overtime calculation
    $overtime_rows = db_query(get_overtime());
    $overtime_amount = 0;
    
    while($overtime_row = db_fetch($overtime_rows)) {
        $ot_from_date_range = get_ot_from_date_range($from_date, $to_date, $emp_id, $overtime_row['overtime_id']);

        while($ot_row = db_fetch($ot_from_date_range)) {
            $ot_rate = $ot_row['rate'];
            $ot_hours = $ot_row['hours_no'];
            $overtime_amount += ($salary_amount/$Work_days)/$Work_hours * $ot_rate * $ot_hours;
            $total_ot += $ot_row['hours_no'];
        }
            //$ot_rate = get_ot_from_date_range($from_date, $to_date, $emp_id, $overtime_row['overtime_id'])['rate'];
            //$ot_hours = get_ot_from_date_range($from_date, $to_date, $emp_id, $overtime_row['overtime_id'])['hours_no'];
            //$overtime_amount += ($salary_amount/$Work_days)/$Work_hours * $ot_rate * $ot_hours;
    }
    if($overtime_amount != 0) 
        $Order->add_gl_item($Overtime_act, 0, 0, $overtime_amount, '');

    $totalDebit += $overtime_amount;
	
    //leave calculation
    $deductableleaves = ($Order->pay_basis == MONTHLY_SALARY) ? ($Work_days - $workdays) : 0;
    $leave_hours = get_leave_hours($from_date, $to_date, $emp_id);
    $leave_hours_amount = ($salary_amount/$Work_days)/$Work_hours * $leave_hours;
	$leave_deduct_amount = ($salary_amount/$Work_days*$deductableleaves) + $leave_hours_amount;
    
	if($leave_deduct_amount != 0){
		$Order->add_gl_item($Deduct_act, 0, 0, -$leave_deduct_amount, '');
		$totalCredit += $leave_deduct_amount;
	}
    
	//expenses and deposits
	$expences = get_expences_n_deposits($emp_id, $from_date, $to_date);
	if(db_num_rows($expences) > 0){
	
		while($myrow1 = db_fetch($expences)){
			
			$Order->add_gl_item($myrow1['account'], 0,0,-$myrow1['amount'], '');
			$amount=abs($myrow1['amount']);
			if($myrow1['type'] == ST_BANKDEPOSIT)
				$totalDebit += $amount;
			else
				$totalCredit +=$amount;
		}
	}

	$payable_amount = $totalCredit - $totalDebit;
    
	if($payable_amount != 0)
		$Order->add_gl_item($Payable_act, 0, 0, $payable_amount, '');
	else
		$Order->empty_payment = true;

	$Order->payable_amt = abs($payable_amount);
	$Order->overtime_amt = abs($overtime_amount);

	if($workdays == 0 && $total_ot == 0) {
        display_warning(_('non-working period'));
        $Order->clear_items();
        set_focus('from_date');
    }
        
	//------------------------------------------------==================
	unset($_POST['totamount']); // enable default
	$Ajax->activate('totamount');
}

//--------------------------------------------------------------------------

function display_advice_header(&$Order) {
	global $Ajax, $Payable_act;

	$qes = has_quick_entries(QE_JOURNAL);
	$new = $Order->order_id==0;

	hidden('ref_original');
	start_outer_table(TABLESTYLE2, "width='90%'");

	table_section(1);

   		date_row(_("Date:"), 'date_', '', $new);
		ref_row(_("Reference:"), 'ref', '');

	table_section(2, "35%");
		
		hidden('PayType', PT_EMPLOYEE);

		$employee = get_employees($Order->person_id);

		$_POST['to_the_order_of'] = $Order->to_the_order_of;

		if(isset($_GET['PaymentAdvice']) || isset($_POST['PaymentAdvice'])) {
			label_row(_("Pay To: "), $employee['emp_first_name']." ".$employee['emp_last_name']);
			hidden('emp_id', $Order->person_id);
			label_row(_("To the Order of:"),$Order->to_the_order_of);

			hidden('to_the_order_of');

			if(!isset($_POST['PaymentAdvice']))
				$_POST['PaymentAdvice'] = $_GET['PaymentAdvice'];
			hidden('PaymentAdvice');
		}
        else {
			employee_list_row(_("Pay To: "), 'emp_id', null, _("Select Employee"), true, false, false);
			text_row(_("To the Order of:"), 'to_the_order_of', $_POST['to_the_order_of'], 20, 50);
		}
		table_section(3, "35%");

		if(!isset($_POST['PaySlipNo']))
			$_POST['PaySlipNo'] = $Order->payslip_no = 0;

		bank_accounts_list_row(_("From:"), 'bank_account', null, true);
		bank_balance_row(get_post('bank_account'));
	
        if(!isset($_GET['PaymentAdvice']) && !isset($_POST['PaymentAdvice']))
            employee_payslip_list_row(_("Pay Slip:"), 'for_payslip', null, get_post('emp_id'), _("Select payslip"), true);
		
		if (list_updated('emp_id')) {

			$Order->clear_items();
			unset($_POST['totamount']);
			$Ajax->activate('totamount');
			$Ajax->activate('for_payslip');
			$Order->payslip_no = 0;	
		}
		
		if (list_updated('for_payslip') || list_updated('bank_account')) {

			$Order->clear_items();
			$_POST['memo_'] = "";

			if(list_updated('for_payslip')) {
				unset($_POST['PaySlipNo']);
				$Order->payslip_no = 0;	
				$Order->payslip_trans_no = get_post('for_payslip');
			}
            
            if(!isset($Order->payslip_trans_no))
                $payslip = null;
            else    
                $payslip = get_payslip(false, $Order->payslip_trans_no);
                
		
			if($payslip) {
				$_POST['memo_'] = "Payment advice gl entry For Payslip #".$payslip['payslip_no'];

				if(list_updated('for_payslip')) {

					$Order->payslip_trans_no = $payslip['trans_no'];
					$Order->person_id = $payslip['emp_id'];
					//$Order->to_the_order_of = $payslip['to_the_order_of'];
					$Order->payslip_no = $_POST['PaySlipNo'] = $payslip['payslip_no'];
				}
                
				$pay_amt = $payslip['payable_amount'];

				$bank_ac_code = get_bank_gl_account(get_post('bank_account'));
	
				$Order->add_gl_item($Payable_act, 0, 0, $pay_amt, '');
				$Order->add_gl_item($bank_ac_code, 0, 0, -$pay_amt, '');
			}
			$Ajax->activate('items_table');
		}

		hidden('PaySlipNo', $Order->payslip_no);
	
	end_outer_table(1);
}

//--------------------------------------------------------------------------

function display_gl_items($title, &$order) {
	global $path_to_root;

	display_heading($title);

	$dim = get_company_pref('use_dimension');

    div_start('items_table');
	start_table(TABLESTYLE, "width='80%'");
	if ($dim == 2)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension")." 1",
			_("Dimension")." 2", _("Debit"), _("Credit"), _("Memo"), "");
	else if ($dim == 1)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension"),
			_("Debit"), _("Credit"), _("Memo"), "");
	else
		$th = array(_("Account Code"), _("Account Description"),
			_("Debit"), _("Credit"), _("Memo"), "");

	if (count($order->gl_items)) $th[] = '';

	table_header($th);	

	$k = 0;

	$id = find_submit('Edit');
	foreach ($order->gl_items as $line => $item) {
    
        if ($id != $line) {
            alt_table_row_color($k);

            label_cells($item->code_id, $item->description);
	        if ($dim >= 1)
	            label_cell(get_dimension_string($item->dimension_id, true));
	        if ($dim > 1)
	            label_cell(get_dimension_string($item->dimension2_id, true));
	        if ($item->amount > 0) {
	            amount_cell(abs($item->amount));
	            label_cell("");
	        }	
	        else {
	            label_cell("");
	            amount_cell(abs($item->amount));
	        }	
            label_cell($item->reference);

            edit_button_cell("Edit$line", _("Edit"), _('Edit journal line'));
            delete_button_cell("Delete$line", _("Delete"), _('Remove line from journal'));
	        end_row();
		} 
		else {
			gl_edit_item_controls($order, $dim, $line);
		}
	}
	//if ($id == -1)
		//gl_edit_item_controls($order, $dim);

	if ($order->count_gl_items()!=null) {
		$colspan = ($dim == 2 ? "4" : ($dim == 1 ? "3" : "2"));
		start_row();
		label_cell(_("Total Salary"), "align=right colspan=" . $colspan);
		amount_cell($order->gl_items_total_debit());
		amount_cell(abs($order->gl_items_total_credit()));
		label_cell('', "colspan=3");
		end_row();
	}
    end_table();
	div_end();
}

//--------------------------------------------------------------------------

function gl_edit_item_controls(&$order, $dim, $Index=null) {
	global $Ajax;
	start_row();

	$id = find_submit('Edit');
	if ($Index != -1 && $Index == $id) {
	    // Modifying an existing row
		$item = $order->gl_items[$Index];
		$_POST['code_id'] = $item->code_id;
		$_POST['dimension_id'] = $item->dimension_id;
		$_POST['dimension2_id'] = $item->dimension2_id;
		if ($item->amount > 0) {
			$_POST['AmountDebit'] = price_format($item->amount);
			$_POST['AmountCredit'] = "";
		}
		else {
			$_POST['AmountDebit'] = "";
			$_POST['AmountCredit'] = price_format(abs($item->amount));
		}	
		$_POST['description'] = $item->description;
		$_POST['LineMemo'] = $item->reference;

		hidden('Index', $id);
		$skip_bank = !$_SESSION["wa_current_user"]->can_access('SA_BANKJOURNAL');
		echo gl_all_accounts_list('code_id', null, $skip_bank, true);
		if ($dim >= 1) 
			dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1) 
			dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	    $Ajax->activate('items_table');
	}
	else {
	    // Adding a new row
		$_POST['AmountDebit'] = '';  //price_format(0);
		$_POST['AmountCredit'] = ''; //price_format(0);
		$_POST['dimension_id'] = 0;
		$_POST['dimension2_id'] = 0;
		//$_POST['LineMemo'] = ""; // let memo go to next line Joe Hunt 2010-05-30
		$_POST['_code_id_edit'] = "";
		$_POST['code_id'] = "";
		if(isset($_POST['_code_id_update'])) {
			    $Ajax->activate('code_id');
		}
		
		$skip_bank = !$_SESSION["wa_current_user"]->can_access('SA_BANKJOURNAL');
		echo gl_all_accounts_list('code_id', null, $skip_bank, true);
		if ($dim >= 1)
			dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1)
			dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	}

	if ($dim < 1)
		hidden('dimension_id', 0);
	if ($dim < 2)
		hidden('dimension2_id', 0);

	small_amount_cells(null, 'AmountDebit');
	small_amount_cells(null, 'AmountCredit');
	text_cells_ex(null, 'LineMemo', 35, 255);

	if ($id != -1) {
		button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
 		set_focus('amount');
	} 
	else 
		submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new line to journal'), true);

	end_row();
}

//--------------------------------------------------------------------------

function gl_options_controls() {
	  echo "<br><table align='center'>";

	  textarea_row(_("Memo"), 'memo_', null, 50, 3);

	  echo "</table>";
}


//--------------------------------------------------------------------------


function _format_emp_payslip($row) {

	return "Trans# ".$row['type_no'] ." - ". $row['scale_name'];
}

//--------------------------------------------------------------------------

function employee_payslip_list($name, $selected_id = null, $emp_id, $spec_option = false, $submit_on_change = false, $show_inactive = false) {
	global $all_items;

	if($emp_id) {

	    $sql = "SELECT p.payslip_no, p.generated_date FROM ".TB_PREF."payslip p LEFT JOIN ".TB_PREF."employee_trans t ON p.payslip_no = t.payslip_no WHERE t.payslip_no IS NULL AND p.emp_id = ".db_escape($emp_id);

	    return combo_input($name, $selected_id, $sql, 'payslip_no', 'payslip_no',
	    array(
		    'spec_option' => $spec_option === true ? _("All Payslip") : $spec_option,
		    'format' => '_format_payslip_date',
		    'select_submit'=> $submit_on_change,
		    'async' => false,
		    'show_inactive' => $show_inactive,
	    ));
    }
}

function _format_payslip_date($row) {
	return 'Payslip #'.$row[0].' ('.sql2date($row[1]).')';
}

//--------------------------------------------------------------------------

function employee_payslip_list_cells($label, $name, $selected_id = null, $employee_id, $all_option = false, $submit_on_change = false, $show_inactive = false) {

	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td nowrap>";
	echo employee_payslip_list($name, $selected_id, $employee_id, $all_option, $submit_on_change,
		$show_inactive);
	echo "</td>\n";
}

//--------------------------------------------------------------------------

function employee_payslip_list_row($label, $name, $selected_id = null, $employee_id, $all_option = false, $submit_on_change = false, $show_inactive = false) {

	echo "<tr><td class='label'>$label</td><td>";
	echo employee_payslip_list($name, $selected_id, $employee_id, $all_option, $submit_on_change, $show_inactive);
	echo "</td></tr>\n";
}

//--------------------------------------------------------------------------

function hrm_print_link($doc_no, $link_text, $link=true, $type_no, $icon=false, $class='', $id='', $email=0, $extra=0) {
    global $path_to_root;

	$def_orientation = (user_def_print_orientation() == 1 ? 1 : 0);
	$dir = $path_to_root . "/modules/FrontHrm/reporting/redirect.php?";

	switch ($type_no) {

		case ST_PAYSLIP :
			$rep = ST_PAYSLIP;
			$ar = array(
				'PARAM_0' => $doc_no,  
				'PARAM_1' => $def_orientation);
			break;
		default:
			return null;
	}

	return print_link($link_text, $rep, $ar, $dir, $icon, $class, $id);
}