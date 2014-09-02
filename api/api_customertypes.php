<?php

/* This function returns a list of the customer types
 * currently setup on KwaMoja
 */

function GetCustomerTypeList($user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db) == 'integer') {
		$Errors[0] = NoAuthorisation;
		return $Errors;
	}
	$SQL = 'SELECT typeid FROM debtortype';
	$result = api_DB_query($SQL);
	$i = 0;
	while ($MyRow = DB_fetch_array($result)) {
		$TaxgroupList[$i] = $MyRow[0];
		$i++;
	}
	return $TaxgroupList;
}

/* This function takes as a parameter a customer type id
 * and returns an array containing the details of the selected
 * customer type.
 */

function GetCustomerTypeDetails($typeid, $user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db) == 'integer') {
		$Errors[0] = NoAuthorisation;
		return $Errors;
	}
	$SQL = "SELECT * FROM debtortype WHERE typeid='" . $typeid . "'";
	$result = api_DB_query($SQL);
	return DB_fetch_array($result);
}
?>