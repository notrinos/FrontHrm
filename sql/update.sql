DROP TABLE IF EXISTS `0_employee`;
CREATE TABLE IF NOT EXISTS `0_employee` (
    `emp_id` int(11) NOT NULL AUTO_INCREMENT,
    `emp_first_name` varchar(100) DEFAULT NULL,
    `emp_last_name` varchar(100) DEFAULT NULL,
	  `gender` tinyint(1) NOT NULL DEFAULT 0,
    `emp_address` tinytext,
    `emp_mobile` varchar(30) DEFAULT NULL,
    `emp_email` varchar(100) DEFAULT NULL,
    `emp_birthdate` date NOT NULL,
    `emp_notes` tinytext NOT NULL,
    `emp_hiredate` date DEFAULT NULL,
    `department_id` int(11) NOT NULL,
    `salary_scale_id` int(11) NOT NULL DEFAULT 0,
    `emp_releasedate` date DEFAULT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`emp_id`),
    KEY `salary_scale_id` (`salary_scale_id`),
    KEY `department_id` (`department_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_department`;
CREATE TABLE IF NOT EXISTS `0_department` (
    `dept_id` int(11) NOT NULL AUTO_INCREMENT,
    `dept_name` tinytext NOT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`dept_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_salaryscale`;
CREATE TABLE IF NOT EXISTS `0_salaryscale` (
    `scale_id` int(11) NOT NULL AUTO_INCREMENT,
    `scale_name` text NOT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT 0,
    `pay_basis` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = monthly, 1 = daily',
    PRIMARY KEY (`scale_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_overtime`;
CREATE TABLE IF NOT EXISTS `0_overtime` (
    `overtime_id` int(11) NOT NULL AUTO_INCREMENT,
	  `overtime_name` varchar(100) NOT NULL,
    `overtime_rate` float(5) NOT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`overtime_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_attendance`;
CREATE TABLE IF NOT EXISTS `0_attendance` (
    `emp_id` int(11) NOT NULL,
    `overtime_id` int(11) NOT NULL,
    `hours_no` float(5) NOT NULL DEFAULT 0,
    `rate` float(5) NOT NULL DEFAULT 1,
    `att_date` date NOT NULL,
    PRIMARY KEY (`emp_id`,`overtime_id`,`att_date`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_payroll_account`;
CREATE TABLE IF NOT EXISTS `0_payroll_account` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` int(11) NOT NULL,
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_payroll_structure`;
CREATE TABLE IF NOT EXISTS `0_payroll_structure` (
  `salary_scale_id` int(11) NOT NULL,
  `payroll_rule` text NOT NULL,
  KEY `salary_scale_id` (`salary_scale_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_salary_structure`;
CREATE TABLE IF NOT EXISTS `0_salary_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `salary_scale_id` int(11) NOT NULL,
  `pay_rule_id` varchar(15) NOT NULL,
  `pay_amount` double NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '0 for credit, 1 for debit',
  `is_basic` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_payslip`;
CREATE TABLE IF NOT EXISTS `0_payslip` (
  `payslip_no` int(11) NOT NULL AUTO_INCREMENT,
  `trans_no` int(11) NOT NULL DEFAULT 0,
  `emp_id` int(11) NOT NULL,
  `generated_date` date NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `leaves` int(11) NOT NULL,
  `deductable_leaves` int(11) NOT NULL,
  `payable_amount` double NOT NULL DEFAULT 0,
  `salary_amount` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`payslip_no`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_payslip_details`;
CREATE TABLE IF NOT EXISTS `0_payslip_details` (
  `payslip_no` int(11) NOT NULL AUTO_INCREMENT,
  `detail` int(11) NOT NULL DEFAULT 0,
  `amount` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`payslip_no`, `detail`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `0_employee_trans`;
CREATE TABLE IF NOT EXISTS `0_employee_trans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trans_no` int(11) NOT NULL DEFAULT 0,
  `payslip_no` int(11) NOT NULL,
  `pay_date` date NOT NULL,
  `to_the_order_of` varchar(255) NOT NULL,
  `pay_amount` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_deductleave_act';
DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_month_work_days';
DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_overtime_act';
DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_payable_act';
DELETE FROM `0_sys_prefs` WHERE `0_sys_prefs`.`name` = 'payroll_work_hours';

INSERT INTO `0_sys_prefs` VALUES ('payroll_deductleave_act', NULL, 'int', NULL, 5410);
INSERT INTO `0_sys_prefs` VALUES ('payroll_month_work_days', NULL, 'float', NULL, 26);
INSERT INTO `0_sys_prefs` VALUES ('payroll_overtime_act', NULL, 'int', NULL, 5420);
INSERT INTO `0_sys_prefs` VALUES ('payroll_payable_act', NULL, 'int', NULL, 2100);
INSERT INTO `0_sys_prefs` VALUES ('payroll_work_hours', NULL, 'float', NULL, 8);
