<?php 
/**
 * Assign operators to be supervisors for processes in a checkbox matrix
 *
 *
 *      This file is part of queXC
 *      
 *      queXC is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU Affero General Public License as published by
 *      the Free Software Foundation; either version 3 of the License, or
 *      (at your option) any later version.
 *      
 *      queXC is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU Affero General Public License for more details.
 *      
 *      You should have received a copy of the GNU Affero General Public License
 *      along with queXC; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright Australian Consortium for Social and Political Research Inc. (ACSPRI) 2010
 * @package queXC
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXC was writen for ACSPRI
 * @license http://opensource.org/licenses/agpl-v3.html The GNU Affero General Public License (AGPL) Version 3
 * 
 */

/**
 * Configuration file
 */
include ("../config.inc.php");

/**
 * Database file
 */
include ("../db.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Return if an operator has already been assigned to this process
 *
 * @param int $operator_id Operator id
 * @param int $process_id Data id
 * @return int 1 if assigned otherwise 0
 *
 */
function vq($operator_id,$process_id)
{
	global $db;

	$sql = "SELECT operator_id,process_id
		FROM operator_process_supervisor
		WHERE operator_id = '$operator_id' and process_id = '$process_id'";

	$vq = $db->Execute($sql);

	if ($vq)
		return $vq->RecordCount();
	else
		return 0;
}

/**
 * Assign an operator to a process
 *
 * @param int $operator_id Operator id
 * @param int $process_id Data id
 *
 */
function vqi($operator_id,$process_id)
{
	global $db;

	$sql = "INSERT INTO
		operator_process_supervisor (operator_id,process_id)
		VALUES('$operator_id','$process_id')";

	$db->Execute($sql);
}


/**
 * Unassign an operator from a process
 *
 * @param int $operator_id Operator id
 * @param int $process_id Data id
 *
 */
function vqd($operator_id,$process_id)
{
	global $db;

	$sql = "DELETE FROM
		operator_process_supervisor	
		WHERE operator_id = '$operator_id' and process_id = '$process_id'";

	$db->Execute($sql);
}




if (isset($_POST['submit']))
{
	$db->StartTrans();

	$sql = "DELETE 
		FROM operator_process_supervisor
		WHERE 1";

	$db->Execute($sql);

	foreach ($_POST as $g => $v)
	{
		$a = explode("_",$g);
		if ($a[0] == "cb")
			vqi($a[2],$a[1]);
	}

	$db->CompleteTrans();
}



$sql = "SELECT process_id,description
	FROM process
	ORDER by process_id ASC";

$processs = $db->GetAll($sql);

$sql = "SELECT operator_id,description
	FROM operator
	ORDER by operator_id ASC";

$operators = $db->GetAll($sql);


xhtml_head(T_("Assign operators to be supervisors of processes"),false,array("../css/table.css"));

?>

<script type="text/javascript">

<?php 
print "process_id = new Array(";

$s = "";

foreach($processs as $q)
{
	$s .= "'{$q['process_id']}',";
}

$s = substr($s,0,strlen($s) - 1);
print "$s);\n";

print "operator_id = new Array(";

$s = "";

foreach($operators as $q)
{
	$s .= "'{$q['operator_id']}',";
}

$s = substr($s,0,strlen($s) - 1);
print "$s);\n";

?>

var QidOn = 0;
var VidOn = 0;

function checkQid(q)
{
	
	for (y in operator_id)
	{
		v = operator_id[y];

		cb = document.getElementById('cb_' + q + "_" + v);

		if (QidOn == 0)
			cb.checked = 'checked';
		else
			cb.checked = '';
			
	}

	if (QidOn == 0)
		QidOn = 1;
	else
		QidOn = 0;
}



function checkVid(v)
{
	
	for (y in process_id)
	{
		q = process_id[y];

		cb = document.getElementById('cb_' + q + "_" + v);

		if (VidOn == 0)
			cb.checked = 'checked';
		else
			cb.checked = '';
			
	}

	if (VidOn == 0)
		VidOn = 1;
	else
		VidOn = 0;
}



</script>
</head>
<body>


<?php 



print "<form action=\"\" method=\"post\"><table>";

print "<tr><th></th>";
foreach($processs as $q)
{
	print "<th><a href=\"javascript:checkQid({$q['process_id']})\">{$q['description']}</a></th>";
}
print "</tr>";

$class = 0;

foreach($operators as $v)
{
	print "<tr class='";
	if ($class == 0) {$class = 1; print "even";} else {$class = 0; print "odd";}
	print "'>";
	print "<th><a href=\"javascript:checkVid({$v['operator_id']})\">{$v['description']}</a></th>";
	foreach($processs as $q)
	{
		$checked = "";
		if (vq($v['operator_id'],$q['process_id'])) $checked="checked=\"checked\"";
		print "<td><input type=\"checkbox\" name=\"cb_{$q['process_id']}_{$v['operator_id']}\" id=\"cb_{$q['process_id']}_{$v['operator_id']}\" $checked></input></td>";
	}

	print "</tr>";
}


print "</table><p><input type=\"submit\" name=\"submit\" value=\"" . T_("Assign operators to be supervisor for these processes") . "\"/></p></form>";


xhtml_foot();

?>
