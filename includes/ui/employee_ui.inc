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

function gender_radio_row($label, $name, $gender=null) {
    echo "<tr><td class='label'>$label</td><td>";
	
    if($gender==0){
        echo "<input type='radio' name=$name value='1'>Male&nbsp;&nbsp;&nbsp;";
        echo "<input type='radio' name=$name value='0' checked>Female";
    }
    else {
        echo "<input type='radio' name=$name value='1' checked>Male&nbsp;&nbsp;&nbsp;";
        echo "<input type='radio' name=$name value='0'>Female";
    }
    echo "</td></tr>";
}
