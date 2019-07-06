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

include_once($path_to_root."/includes/db_pager.inc");

function display_FrontHrm_pager(&$pager) {
    global $path_to_root;

	$pager->select_records();

	div_start("_{$pager->name}_span");
    $headers = array();

	foreach($pager->columns as $num_col => $col) {

    	if (isset($col['head']) && ($col['type']!='inactive' || get_post('show_inactive'))) {
			if (!isset($col['ord']))
				$headers[] = $col['head'];
			else {
	  			$icon = (($col['ord'] == 'desc') ? 'sort_desc.gif' : ($col['ord'] == 'asc' ? 'sort_asc.gif' : 'sort_none.gif'));
				$headers[] = navi_button($pager->name.'_sort_'.$num_col, $col['head'], true, $icon);
			}
 		}
	}

    start_table(TABLESTYLE, "width='$pager->width'");
    table_header($headers);

	if($pager->header_fun) {

		start_row("class='{$pager->header_class}'");
		$fun = $pager->header_fun;

		if (method_exists($pager, $fun))
			$h = $pager->$fun($pager);
  		elseif (function_exists($fun))
			$h = $fun($pager);
		
		foreach($h as $c) {
			$pars = isset($c[1]) ? $c[1] : '';
			label_cell($c[0], $pars);
		}
		end_row();
	}

	$cc = 0;
   	foreach($pager->data as $line_no => $row) {	

        if ($pager->row_fun) {
            if (function_exists($pager->row_fun)) {
                $fn = $pager->row_fun;
                $row = $fn($row);
            }
        }

		$marker = $pager->marker;
		$marker_warn = $pager->marker_warn;
		
	    if ($marker && $marker($row) && $marker_warn && $marker_warn($row)) 
        	start_row("class='$pager->marker_class'");
        elseif($marker_warn && $marker_warn($row))
		    start_row("style='background-color:$pager->marker_warn_bg'");
	    else	
			alt_table_row_color($cc);

	    foreach ($pager->columns as $k=>$col) {
	        $coltype = $col['type'];
	        $cell = isset($col['name']) ? $row[$col['name']] : '';

	        if (isset($col['fun'])) {
	            $fun = $col['fun']; 
	            if (method_exists($pager, $fun))
	                $cell = $pager->$fun($row, $cell);
	            elseif (function_exists($fun))
				    $cell = $fun($row, $cell);
			    else
				    $cell = '';
			}
			switch($coltype) {
				case 'time':
				    label_cell($cell, "width='40'"); break;
				case 'date':
				    label_cell(sql2date($cell), "align='center' nowrap"); break;
				case 'dstamp':
				    label_cell(sql2date(substr($cell, 0, 10)), "align='center' nowrap"); break;
				case 'tstamp':
				    label_cell(sql2date(substr($cell, 0, 10)).'&nbsp;&nbsp;'. substr($cell, 10), "align='center'"); break;
				case 'percent':
				    percent_cell($cell); break;
				case 'amount':
				    if ($cell=='')
				    	label_cell('');
				    else
				    	amount_cell($cell, false); break;
				case 'qty':
				    if ($cell=='')
				    	label_cell('');
				    else
				    	qty_cell($cell, false, isset($col['dec']) ? $col['dec'] : null); break;
				case 'email':
				    email_cell($cell,isset( $col['align']) ? "align='" . $col['align'] . "'" : null); break;
				    case 'rate':
				    label_cell(number_format2($cell, user_exrate_dec()), "align=center"); break;
				case 'inactive':
				    if(get_post('show_inactive'))
				    	$pager->inactive_control_cell($row); break;
				default:
				    if (isset( $col['align']))
				    	label_cell($cell, "align='" . $col['align'] . "'");
				    else
				    	label_cell($cell);
				case 'skip':
			}
		}
	    end_row();
	}

	if($pager->footer_fun) {

		start_row("class='{$pager->footer_class}'");

		$fun = $pager->footer_fun;

		if (method_exists($pager, $fun))
			$h = $pager->$fun($pager);
  		elseif (function_exists($fun))
			$h = $fun($pager);
		
		foreach($h as $c) {
			$pars = isset($c[1]) ? $c[1] : '';
			label_cell($c[0], $pars);
		}
		end_row();
	}

	start_row("class='navibar'");

	$colspan = count($pager->columns);
	$inact = @$pager->inactive_ctrl == true ? '&nbsp;&nbsp;'.checkbox(null, 'show_inactive', null, true). _("Show also Inactive") : '';

	if($pager->rec_count) {
		echo "<td colspan = $colspan class='navibar' style='border:none;padding:3px;'>";
		echo "<div style='float:right;'>";
		$but_pref = $pager->name.'_page_';
	    start_table();
		start_row();
		if (@$pager->inactive_ctrl) 
			submit('Update', _('Update'), true, '', null);
		echo navi_button_cell($but_pref.'first', _('First'), $pager->first_page, 'right');
		echo navi_button_cell($but_pref.'prev', _('Prev'), $pager->prev_page,'right');
		echo navi_button_cell($but_pref.'next', _('Next'), $pager->next_page,'right');
		echo navi_button_cell($but_pref.'last', _('Last'), $pager->last_page, 'right');
		end_row(); 
		end_table();
		echo "</div>";
		$from = ($pager->curr_page-1) * $pager->page_len + 1;
		$to = $from + $pager->page_len - 1;
		if ($to > $pager->rec_count)
			$to = $pager->rec_count;
		$all = $pager->rec_count;
		echo sprintf(_('Records %d-%d of %d'), $from, $to, $all);
		echo $inact;
		echo "</td>";
	}
	else
	  	label_cell( _('No records') . $inact, "colspan=$colspan class='navibar'");

	end_row();
	end_table();

	if (isset($pager->marker_txt))
   		display_note($pager->marker_txt, 0, 0, "class='$pager->notice_class'");
   	if (isset($pager->marker_txt_warn))
   		display_note($pager->marker_txt_warn, 0, 0, "style='color:$pager->notice_warn_color'");

   	div_end();
   	return true;
}

//----------------------------------------------------------------------------------------

class FrontHrm_db_pager extends db_pager {
    
    var $marker_warn;
	var $marker_txt_warn;
    var $marker_warn_bg;
    var $notice_warn_color;
	
	function set_marker_warnings($func, $notice='', $markerbg = '#FFFFD0', $msgcolor = '#FFCC00' ) {
		$this->marker_warn = $func;
		$this->marker_txt_warn = $notice;
		$this->marker_warn_bg = $markerbg;
		$this->notice_warn_color = $msgcolor;
	}

};

//----------------------------------------------------------------------------------------

function &new_FrontHrm_pager($name, $sql, $coldef, $table = null, $key = null, $page_len = 0)  {

    if (isset($_SESSION[$name]) && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SESSION[$name]->sql != $sql))
		unset($_SESSION[$name]);
	
	if (!isset($_SESSION[$name])) {
	    $_SESSION[$name] = new FrontHrm_db_pager($sql, $name, $table, $page_len);
		$_SESSION[$name]->main_tbl = $table;
		$_SESSION[$name]->key = $key;
		$_SESSION[$name]->set_sql($sql);
		$_SESSION[$name]->set_columns($coldef);
	}
	
	return  $_SESSION[$name];
}