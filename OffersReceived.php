<?php

/*$Id: OffersReceived.php 4500 2011-02-27 09:18:42Z daintree $ */

include('includes/session.inc');
$Title = _('Supplier Offers');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

if (isset($_POST['supplierid'])) {
	$sql="SELECT suppname,
				email,
				currcode,
				paymentterms
			FROM suppliers
			WHERE supplierid='".$_POST['supplierid']."'";
	$result = DB_query($sql, $db);
	$myrow=DB_fetch_array($result);
	$SupplierName=$myrow['suppname'];
	$Email=$myrow['email'];
	$CurrCode=$myrow['currcode'];
	$PaymentTerms=$myrow['paymentterms'];
}

if (!isset($_POST['supplierid'])) {
	$sql="SELECT DISTINCT
			offers.supplierid,
			suppliers.suppname
		FROM offers
		LEFT JOIN purchorderauth
			ON offers.currcode=purchorderauth.currabrev
		LEFT JOIN suppliers
			ON suppliers.supplierid=offers.supplierid
		WHERE purchorderauth.userid='".$_SESSION['UserID']."'
			AND purchorderauth.cancreate=0";
	$result=DB_query($sql, $db);
	if (DB_num_rows($result)==0) {
		prnMsg(_('There are no offers outstanding that you are authorised to deal with'), 'information');
	} else {
		echo '<p class="page_title_text noPrint" ><img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" title="' . _('Select Supplier') . '" alt="" />
             ' . ' ' . _('Select Supplier') . '</p>';
		echo '<form method="post" class="noPrint" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'">';
        echo '<div>';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<table class="selection">
			<tr>
				<td>'._('Select Supplier').'</td>
				<td><select name=supplierid>';
		while ($myrow=DB_fetch_array($result)) {
			echo '<option value="'.$myrow['supplierid'].'">'.$myrow['suppname'].'</option>';
		}
		echo '</select></td>
			</tr>
			<tr><td colspan="12">
				<div class="centre">
					<input type="submit" name="select" value="' . _('Enter Information') . '" />
				</div>
				</td>
			</tr>
			</table>
            </div>
			</form>';
	}
}

if (!isset($_POST['submit']) and isset($_POST['supplierid'])) {
	$sql = "SELECT offers.offerid,
				offers.tenderid,
				offers.supplierid,
				suppliers.suppname,
				offers.stockid,
				stockmaster.description,
				offers.quantity,
				offers.uom,
				offers.price,
				offers.expirydate,
				offers.currcode,
				stockmaster.decimalplaces,
				currencies.decimalplaces AS currdecimalplaces
			FROM offers INNER JOIN purchorderauth
				ON offers.currcode=purchorderauth.currabrev
			INNER JOIN suppliers
				ON suppliers.supplierid=offers.supplierid
			INNER JOIN currencies
				ON suppliers.currcode=currencies.currabrev
			LEFT JOIN stockmaster
				ON stockmaster.stockid=offers.stockid
			WHERE purchorderauth.userid='" . $_SESSION['UserID'] . "'
				AND offers.supplierid='" . $_POST['supplierid'] . "'
				AND  offers.expirydate>=CURRENT_DATE
			ORDER BY offerid";
	$result=DB_query($sql, $db);

	echo '<form method="post" class="noPrint" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
    echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<p class="page_title_text noPrint" >
			<img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" title="' . _('Supplier Offers') . '" alt="" />' . ' ' . _('Supplier Offers') . '
		</p>';

	echo '<table class="selection">
			<tr>
				<th>'._('Offer ID').'</th>
				<th>'._('Supplier').'</th>
				<th>'._('Stock Item').'</th>
				<th>'._('Quantity').'</th>
				<th>'._('Units').'</th>
				<th>'._('Price').'</th>
				<th>'._('Total').'</th>
				<th>'._('Currency').'</th>
				<th>'._('Offer Expires').'</th>
				<th>'._('Accept').'</th>
				<th>'._('Reject').'</th>
				<th>'._('Defer').'</th>
			</tr>';
	$k=0;

	while ($myrow=DB_fetch_array($result)) {
		if ($k==1){
			echo '<tr class="EvenTableRows">';
			$k=0;
		} else {
			echo '<tr class="OddTableRows">';
			$k++;
		}
		echo '<td>'.$myrow['offerid'].'</td>
			<td>'.$myrow['suppname'].'</td>
			<td>'.$myrow['description'].'</td>
			<td class="number">'.locale_number_format($myrow['quantity'],$myrow['decimalplaces']).'</td>
			<td>'.$myrow['uom'].'</td>
			<td class="number">'.locale_number_format($myrow['price'],$myrow['currdecimalplaces']).'</td>
			<td class="number">'.locale_number_format($myrow['price']*$myrow['quantity'],$myrow['currdecimalplaces']).'</td>
			<td>'.$myrow['currcode'].'</td>
			<td>'.$myrow['expirydate'].'</td>
			<td><input type="radio" name="action'.$myrow['offerid'].'" value="1" /></td>
			<td><input type="radio" name="action'.$myrow['offerid'].'" value="2" /></td>
			<td><input type="radio" checked name="action'.$myrow['offerid'].'" value="3" /></td>
			<td><input type="hidden" name="supplierid" value="'.$myrow['supplierid'].'" /></td>
		</tr>';
	}
	echo '<tr>
			<td colspan="12">
				<div class="centre">
					<input type="submit" name="submit" value="' . _('Enter Information') . '" />
				</div>
			</td>
		</tr>
		</table>
        </div>
        </form>';
} else if(isset($_POST['submit']) and isset($_POST['supplierid'])) {
	include ('includes/htmlMimeMail.php');
	$accepts=array();
	$rejects=array();
	$defers=array();
	foreach ($_POST as $key => $value) {
		if(mb_substr($key,0,6)=='action') {
			$OfferID=mb_substr($key,6);
			switch ($value) {
				case 1:
					$accepts[]=$OfferID;
					break;
				case 2:
					$rejects[]=$OfferID;
					break;
				case 3:
					$defers[]=$OfferID;
					break;
			}
		}
	}
	if (sizeOf($accepts)>0){
		$MailText=_('This email has been automatically generated by the KwaMoja installation at').' '.
			$_SESSION['CompanyRecord']['coyname']."\n";
		$MailText.=_('The following offers you made have been accepted')."\n";
		$MailText.=_('An official order will be sent to you in due course')."\n\n";
		$sql="SELECT rate FROM currencies where currabrev='" . $CurrCode ."'";
		$result=DB_query($sql, $db);
		$myrow=DB_fetch_array($result);
		$Rate=$myrow['rate'];
		$OrderNo =  GetNextTransNo(18, $db);
		$sql="INSERT INTO purchorders (
					orderno,
					supplierno,
					orddate,
					rate,
					initiator,
					intostocklocation,
					deliverydate,
					status,
					stat_comment,
					paymentterms)
				VALUES (
					'".$OrderNo."',
					'".$_POST['supplierid']."',
					'".date('Y-m-d')."',
					'".$Rate."',
					'".$_SESSION['UserID']."',
					'".$_SESSION['DefaultFactoryLocation']."',
					'".date('Y-m-d')."',
					'"._('Pending')."',
					'"._('Automatically generated from tendering system')."',
					'".$PaymentTerms."')";
		DB_query($sql, $db);
		foreach ($accepts as $AcceptID) {
			$sql="SELECT offers.quantity,
							offers.price,
							offers.uom,
							stockmaster.description,
							stockmaster.stockid
						FROM offers
						LEFT JOIN stockmaster
							ON offers.stockid=stockmaster.stockid
						WHERE offerid='".$AcceptID."'";
			$result= DB_query($sql, $db);
			$myrow=DB_fetch_array($result);
			$MailText.=$myrow['description']."\t"._('Quantity').' '.$myrow['quantity']."\t"._('Price').' '.
					locale_number_format($myrow['price'])."\n";
			$sql="INSERT INTO purchorderdetails (orderno,
												itemcode,
												deliverydate,
												itemdescription,
												unitprice,
												actprice,
												quantityord,
												suppliersunit)
									VALUES ('".$OrderNo."',
											'".$myrow['stockid']."',
											'".date('Y-m-d')."',
											'".$myrow['description']."',
											'".$myrow['price']."',
											'".$myrow['price']."',
											'".$myrow['quantity']."',
											'".$myrow['uom']."')";
			$result=DB_query($sql, $db);
			$sql="DELETE FROM offers WHERE offerid='".$AcceptID."'";
			$result=DB_query($sql, $db);
		}
		$mail = new htmlMimeMail();
		$mail->setSubject(_('Your offer to').' '.$_SESSION['CompanyRecord']['coyname'].' '._('has been accepted'));
		$mail->setText($MailText);
		$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>');
		$result = $mail->send(array($Email), 'smtp');
		prnMsg(_('The accepted offers from').' '.$SupplierName.' '._('have been converted to purchase orders and an email sent to')
			.' '.$Email."\n"._('Please review the order contents').' '.'<a href="'.$RootPath .
				'/PO_Header.php?ModifyOrderNumber=' . $OrderNo.'">'._('here').'</a>', 'success');
	}
	if (sizeOf($rejects)>0){
		$MailText=_('This email has been automatically generated by the KwaMoja installation at').' '.
		$_SESSION['CompanyRecord']['coyname']."\n";
		$MailText.=_('The following offers you made have been rejected')."\n\n";
		foreach ($rejects as $RejectID) {
			$sql="SELECT offers.quantity,
							offers.price,
							stockmaster.description
						FROM offers
						LEFT JOIN stockmaster
							ON offers.stockid=stockmaster.stockid
						WHERE offerid='".$RejectID."'";
			$result= DB_query($sql, $db);
			$myrow=DB_fetch_array($result);
			$MailText.=$myrow['description']."\t"._('Quantity').' '.$myrow['quantity']."\t"._('Price').' '.
					locale_number_format($myrow['price'])."\n";
			$sql="DELETE FROM offers WHERE offerid='".$RejectID."'";
			$result=DB_query($sql, $db);
		}
		$mail = new htmlMimeMail();
		$mail->setSubject(_('Your offer to').' '.$_SESSION['CompanyRecord']['coyname'].' '._('has been rejected'));
		$mail->setText($MailText);
		$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>');
		$result = $mail->send(array($Email), 'smtp');
		prnMsg(_('The rejected offers from').' '.$SupplierName.' '._('have been removed from the system and an email sent to')
			.' '.$Email, 'success');
	}
	prnMsg(_('All offers have been processed, and emails sent where appropriate'), 'success');
}
include('includes/footer.inc');

?>