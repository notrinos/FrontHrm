DROP TABLE IF EXISTS `0_employee`;
CREATE TABLE IF NOT EXISTS `0_employee` (
    `emp_id` int(11) NOT NULL AUTO_INCREMENT,
    `emp_first_name` varchar(100) DEFAULT NULL,
    `emp_last_name` varchar(100) DEFAULT NULL,
	`gender` tinyint(1) NOT NULL DEFAULT '0',
    `emp_address` tinytext,
    `emp_mobile` varchar(30) DEFAULT NULL,
    `emp_email` varchar(100) DEFAULT NULL,
    `emp_birthdate` date NOT NULL,
    `emp_notes` tinytext NOT NULL,
    `emp_hiredate` date DEFAULT NULL,
    `department_id` int(11) NOT NULL,
    `salary_scale_id` int(11) NOT NULL,
    `emp_releasedate` date DEFAULT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`emp_id`),
    KEY `salary_scale_id` (`salary_scale_id`),
    KEY `department_id` (`department_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_department`;
CREATE TABLE IF NOT EXISTS `0_department` (
    `dept_id` int(11) NOT NULL AUTO_INCREMENT,
    `dept_name` tinytext NOT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`dept_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_salaryscale`;
CREATE TABLE IF NOT EXISTS `0_salaryscale` (
    `scale_id` int(11) NOT NULL AUTO_INCREMENT,
    `scale_name` text NOT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`scale_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_overtime`;
CREATE TABLE IF NOT EXISTS `0_overtime` (
    `overtime_id` int(11) NOT NULL AUTO_INCREMENT,
	  `overtime_name` varchar(100) NOT NULL,
    `overtime_rate` float(5) NOT NULL,
    `inactive` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`overtime_id`)
) ENGINE=InnoDB;


DROP TABLE IF EXISTS `0_attendance`;
CREATE TABLE IF NOT EXISTS `0_attendance` (
    `emp_id` int(11) NOT NULL,
    `overtime_id` int(11) NOT NULL,
    `hours_no` int(11) NOT NULL DEFAULT '0',
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
  `pay_rule_id` int(11) NOT NULL,
  `pay_amount` double NOT NULL,
  `type` tinyint(1) NOT NULL COMMENT '0 for credit,1 for debit',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;


ALTER TABLE `0_gl_trans` ADD `payslip_no` INT(11) NOT NULL DEFAULT '0';
