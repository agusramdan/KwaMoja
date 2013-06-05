<?php

include ('includes/session.inc');
$Title = _('General Ledger Journal Inquiry');
$ViewTopic= "GeneralLedger";
$BookMark = "GLJournalInquiry";
include('includes/header.inc');

echo '<p class="page_title_text noPrint" ><img src="'.$RootPath.'/css/'.$Theme.'/images/money_add.png" title="' . $Title.'" alt="' . $Title.'" />' . ' ' . $Title.'</p>';

if (!isset($_POST['Show'])) {
	echo '<form onSubmit="return VerifyForm(this);" onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<table class="selection" summary="' . _('Selection Criteria') . '">';
	echo '<tr><th colspan="3">' . _('Selection Criteria') . '</th></tr>';

	$sql = "SELECT typeno FROM systypes WHERE typeid=0";
	$result = DB_query($sql, $db);
	$myrow = DB_fetch_array($result);
	$MaxJournalNumberUsed = $myrow['typeno'];

	echo '<tr>
			<td>' . _('Journal Number Range') . ' (' . _('Between') . ' 1 ' . _('and') . ' ' . $MaxJournalNumberUsed . ')</td>
			<td>' . _('From') . ':'. '&nbsp;&nbsp;&nbsp;<input type="text" class="number" name="NumberFrom" size="10" minlength="0" maxlength="11" value="1" />'.'</td>
			<td>' . _('To') . ':'. '&nbsp;&nbsp;&nbsp;<input type="text" class="number" name="NumberTo" size="10" minlength="0" maxlength="11" value="' . $MaxJournalNumberUsed . '" />'.'</td>
		</tr>';

	$sql = "SELECT MIN(trandate) AS fromdate,
					MAX(trandate) AS todate FROM gltrans WHERE type=0";
	$result = DB_query($sql, $db);
	$myrow = DB_fetch_array($result);
	if (isset($myrow['fromdate']) and $myrow['fromdate'] != '') {
		$FromDate = $myrow['fromdate'];
		$ToDate = $myrow['todate'];
	} else {
		$FromDate=date('Y-m-d');
		$ToDate=date('Y-m-d');
	}

	echo '<tr>
			<td>' . _('Journals Dated Between') . ':</td>
			<td>' . _('From') . ':'. '&nbsp;&nbsp;&nbsp;<input type="text" name="FromTransDate" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" minlength="0" maxlength="10" size="11" value="' . ConvertSQLDate($FromDate) . '" /></td>
			<td>' . _('To') . ':'. '&nbsp;&nbsp;&nbsp;<input type="text" name="ToTransDate" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" minlength="0" maxlength="10" size="11" value="' . ConvertSQLDate($ToDate) . '" /></td>
		</tr>';

	echo '</table>';
	echo '<br /><div class="centre"><input type="submit" name="Show" value"' . _('Show transactions'). '" /></div>';
	echo '</form>';
} else {

	$sql="SELECT gltrans.typeno,
				gltrans.trandate,
				gltrans.account,
				chartmaster.accountname,
				gltrans.narrative,
				gltrans.amount,
				gltrans.tag,
				tags.tagdescription,
				gltrans.jobref
			FROM gltrans
			INNER JOIN chartmaster
				ON gltrans.account=chartmaster.accountcode
			LEFT JOIN tags
				ON gltrans.tag=tags.tagref
			WHERE gltrans.type='0'
				AND gltrans.trandate>='" . FormatDateForSQL($_POST['FromTransDate']) . "'
				AND gltrans.trandate<='" . FormatDateForSQL($_POST['ToTransDate']) . "'
				AND gltrans.typeno>='" . $_POST['NumberFrom'] . "'
				AND gltrans.typeno<='" . $_POST['NumberTo'] . "'
			ORDER BY gltrans.typeno";

	$result = DB_query($sql, $db);
	if (DB_num_rows($result)==0) {
		prnMsg(_('There are no transactions for this account in the date range selected'), 'info');
	} else {
		echo '<table class="selection" summary="' . _('General ledger journal listing') . '">
			<tr>
				<th colspan="9">
					<b>'. _('General Ledger Jornals') .'</b>
					<img src="'.$RootPath.'/css/'.$Theme.'/images/printer.png" class="PrintIcon noPrint" title="' . _('Print') . '" alt="' . _('Print') . '" onclick="window.print();" />
				</th>
			</tr>
			<tr>
				<th>' . ('Date') . '</th>
				<th>'._('Journal Number').'</th>
				<th>'._('Account Code').'</th>
				<th>'._('Account Description').'</th>
				<th>'._('Narrative').'</th>
				<th>'._('Amount').' '.$_SESSION['CompanyRecord']['currencydefault'].'</th>
				<th>'._('Tag').'</th>
			</tr>';

		$LastJournal = 0;

		while ($myrow = DB_fetch_array($result)){

			if ($myrow['tag']==0) {
				$myrow['tagdescription']='None';
			}

			if ($myrow['typeno']!=$LastJournal) {
				echo '<tr><td colspan="8"</td></tr><tr>
					<td>'. ConvertSQLDate($myrow['trandate']) . '</td>
					<td class="number">'.$myrow['typeno'].'</td>';

			} else {
				echo '<tr><td colspan="2"></td>';
			}

			echo '<td>'.$myrow['account'].'</td>
					<td>'.$myrow['accountname'].'</td>
					<td>'.$myrow['narrative'] .'</td>
					<td class="number">'.locale_number_format($myrow['amount'],$_SESSION['CompanyRecord']['decimalplaces']).'</td>
					<td class="number">'.$myrow['tag'] . ' - ' . $myrow['tagdescription'].'</td>';

			if ($myrow['typeno']!=$LastJournal) {
				echo '<td class="number"><a href="PDFGLJournal.php?JournalNo='.$myrow['typeno'].'">'._('Print') .'</a></td></tr>';

				$LastJournal = $myrow['typeno'];
			} else {
				echo '<td colspan="1"></td></tr>';
			}

		}
		echo '</table>';
	} //end if no bank trans in the range to show

	echo '<form onSubmit="return VerifyForm(this);" onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<br /><div class="centre"><input type="submit" name="Return" value="' . _('Select Another Date'). '" /></div>';
	echo '</form>';
}
include('includes/footer.inc');

?>