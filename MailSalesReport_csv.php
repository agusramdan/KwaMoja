<?php

/* Now this is not secure so a malicious user could send multiple emails of the report to the intended receipients
 *
 * The intention is that this script is called from cron at intervals defined with a command like:
 *
 * /usr/bin/wget http://localhost/web-erp/MailSalesReport.php
 *
 * The configuration of this script requires the id of the sales analysis report to send
 * and an array of the receipients and the company database to use
 */

/*The Sales report to send */
$ReportID = 4;

/* ----------------------------------------------------------------------------------------------*/

include('includes/session.php');
/*The company database to use */
$DatabaseName = $_SESSION['DatabaseName'];
/*The people to receive the emailed report, This mail list now can be maintained in Mailing List Maintenance of Set Up */
$Recipients = GetMailList('SalesAnalysisReportRecipients');
if (sizeOf($Recipients) == 0) {
	$Title = _('Inventory Valuation') . ' - ' . _('Problem Report');
	include('includes/header.php');
	prnMsg(_('There are no members of the Sales Analysis Report Recipients email group'), 'warn');
	include('includes/footer.php');
	exit;
}
include('includes/ConstructSQLForUserDefinedSalesReport.php');
include('includes/CSVSalesAnalysis.php');


include('includes/htmlMimeMail.php');

$Mail = new htmlMimeMail();
$attachment = $Mail->getFile($_SESSION['reports_dir'] . '/SalesAnalysis.csv');
$Mail->setText(_('Please find herewith the comma separated values sales report'));
$Mail->addAttachment($attachment, 'SalesAnalysis.csv', 'application/csv');
$Mail->setSubject(_('Sales Analysis') . ' - ' . _('CSV Format'));
if ($_SESSION['SmtpSetting'] == 0) {
	$Mail->setFrom($_SESSION['CompanyRecord']['coyname'] . '<' . $_SESSION['CompanyRecord']['email'] . '>');
	$Result = $Mail->send($Recipients);
} else {
	$Result = SendmailBySmtp($Mail, $Recipients);
}
?>