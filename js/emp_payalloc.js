
/*=======================================================\
|                        FrontHrm                        |
|--------------------------------------------------------|
|   Creator: Phương <trananhphuong83@gmail.com>          |
|   Date :   09-Jul-2017                                 |
|   Description: Frontaccounting Payroll & Hrm Module    |
|   Free software under GNU GPL                          |
|                                                        |
\=======================================================*/

function blur_alloc(i) {
		var change = get_amount(i.name);
		payment_amt = get_amount('item_amount', true);
		
		if (i.name != 'amount' && i.name != 'charge' && i.name != 'discount')
			change = Math.min(change, get_amount('maxval'+i.name.substr(6), 1))

		price_format(i.name, change, user.pdec);
		if (i.name != 'amount' && i.name != 'charge') {
			if (change<0) change = 0;
			change = change-i.getAttribute('_last');
			if (i.name == 'discount') change = -change;

			var total = get_amount('amount')+change;
			price_format('amount', total, user.pdec, 0);
			price_format('amount', total, user.pdec, 'amount');
			price_format('item_amount', parseFloat(payment_amt) - change, user.pdec, 'item_amount');
			price_format('payment_total_amt', parseFloat(payment_amt) - change, user.pdec, 'payment_total_amt');
		}
}

function emp_allocate_all(doc) {
	var amount = get_amount('amount'+doc);
	var unallocated = get_amount('un_allocated'+doc);
	var total = get_amount('amount', true);
	var gl_payments = document.getElementById('item_amount');
	var payment_amt = gl_payments == null ? 0 : get_amount('item_amount', true);
	var left = 0;
	total -=  (amount-unallocated);
	left -= (amount-unallocated);
	amount = unallocated;

	if(left<0) {
		total  += left;
		amount += left;
		left = 0;
	}

	if(gl_payments != null) {
		price_format('amount'+doc, amount, user.pdec);
		price_format('amount', total, user.pdec);
		price_format('amount', total, user.pdec, 'amount');
		price_format('item_amount', payment_amt - amount, user.pdec, 'item_amount');
		price_format('payment_total_amt', parseFloat(payment_amt) - amount, user.pdec, 'payment_total_amt');
	}
}

function emp_allocate_none(doc) {
	amount = get_amount('amount'+doc);
	total = get_amount('amount', true);
	payment_amt = get_amount('item_amount', true);
	price_format('amount'+doc, 0, user.pdec);
	price_format('amount', total-amount, user.pdec);
	price_format('amount', total-amount, user.pdec, 'amount');
	price_format('item_amount', parseFloat(payment_amt) + amount, user.pdec, 'item_amount');
	price_format('payment_total_amt', parseFloat(payment_amt) + amount, user.pdec, 'payment_total_amt');
}
