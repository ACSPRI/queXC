<?
/**
 * Assign operators to processes in a checkbox matrix
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
 * @author Adam Zammit <adam.zammit@deakin.edu.au>
 * @copyright Deakin University 2007,2008,2009
 * @package queXC
 * @subpackage admin
 * @link http://www.deakin.edu.au/dcarf/ queXC was writen for DCARF - Deakin Computer Assisted Research Facility
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

if (isset($_POST['submit']) && isset($_GET['operator_id']))
{
	$operator_id = intval($_GET['operator_id']);

	$db->StartTrans();

	$sql = "DELETE 
		FROM operator_process
		WHERE operator_id = $operator_id";

	$db->Execute($sql);

	foreach ($_POST as $g => $v)
	{
		$v = intval($v);
		if (substr($g,0,3) == "pid")
		{
			$auto_code = 0;
			$supervisor = 0;
			if (isset($_POST["auto" . $v])) $auto_code = 1;
			if (isset($_POST["super" . $v])) $supervisor = 1;
			$sql = "INSERT INTO operator_process (operator_id,process_id,auto_code,is_supervisor)
				VALUES ($operator_id,$v,$auto_code,$supervisor)";
			$db->Execute($sql);
		}
	}

	$db->CompleteTrans();
}


xhtml_head(T_("Assign operators to processes"),true,array("../css/table.css"),array("../js/display.js"));

$operator_id = 0;
if (isset($_GET['operator_id'])) $operator_id = intval($_GET['operator_id']);

//Select operator
$sql = "SELECT operator_id as value, description, CASE WHEN operator_id = '$operator_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM operator";

print "<div>" . T_("Select operator:") . "</div>";
display_chooser($db->GetAll($sql),'operator_id','operator_id');

if ($operator_id != 0)
{
	print "<div>" . T_("Select processes available to this operator") . "</div>"; 
			
	print "<form action='' method='post'>";
			
	//display a checkbox of all processes

	$sql = "SELECT p.process_id,p.description, CONCAT('<input type=\'checkbox\' name=\'pid', p.process_id, '\' value=\'', p.process_id, '\' ', CASE WHEN op.process_id IS NOT NULL THEN 'checked=\'checked\'' ELSE '' END  , '/>') as cbox, CONCAT('<input type=\'checkbox\' name=\'auto', p.process_id, '\' value=\'', p.process_id, '\' ', CASE WHEN (op.process_id IS NOT NULL AND op.auto_code = 1) THEN 'checked=\'checked\'' ELSE '' END  , '/>') as abox,  CONCAT('<input type=\'checkbox\' name=\'super', p.process_id, '\' value=\'', p.process_id, '\' ', CASE WHEN (op.process_id IS NOT NULL AND op.is_supervisor = 1) THEN 'checked=\'checked\'' ELSE '' END  , '/>') as sbox

		FROM process as p
		LEFT JOIN operator_process AS op ON (op.operator_id = $operator_id AND op.process_id = p.process_id)";

	$rs = $db->GetAll($sql);
	translate_array($rs,array("description"));
	xhtml_table($rs,array('description','cbox','abox','sbox'),array(T_("Process"),T_("Select"),T_("Allow queXC to auto guess code"),T_("Operator is supervisor for this process")));

	print "<div><input type='submit' name='submit' value='" . T_("Assign processes") . "'/></div></form>";
}



xhtml_foot();

?>
