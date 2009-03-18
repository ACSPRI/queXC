<?
/**
 * Mark columns to be coded 
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
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * DB functions
 */
include("../db.inc.php");


if (isset($_POST['submit']))
{
	//apply process
	$data_id = intval($_POST['data_id']);
	$process_id = intval($_POST['process_id']);
	$column_id = intval($_POST['column_id']);
	$anyoperators = intval($_POST['any']);
	$operators = array();
	
	for ($i = 0; $i < $anyoperators; $i++)
		$operators[] = "NULL";

	foreach($_POST as $key => $val)
	{
		if (substr($key,0,3) == 'oid')
			$operators[] = intval($_POST[$key]);
	}

	if (count($operators) > 0)
	{
		include ("../functions/functions.work.php");
		create_work($data_id,$process_id,$column_id,$operators);
	}
}


xhtml_head(T_("Mark columns to be coded"),true,array("../css/table.css"),array("../js/display.js"));

$data_id = 0;
if (isset($_GET['data_id'])) $data_id = intval($_GET['data_id']);

//Select a data file to mark
$sql = "SELECT data_id as value,description, CASE WHEN data_id = '$data_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM data";

print "<div>" . T_("Select data file: ");
display_chooser($db->GetAll($sql),'data_id','data_id');
print "</div>";

if ($data_id != 0)
{
	$column_id = 0;
	if (isset($_GET['column_id'])) $column_id = intval($_GET['column_id']);

	//Select variable (column)
	$sql = "SELECT column_id as value, name as description, CASE WHEN column_id = '$column_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM `column`
		WHERE type = 1
		AND data_id = '$data_id'";

	print "<div>" . T_("Select variable: ");
	$c = $db->GetAll($sql);

	display_chooser($c,'column_id','column_id',true,"data_id=$data_id");
	print "</div>";

	if ($column_id != 0)
	{
		$sql = "SELECT description
			FROM `column`
			WHERE column_id = '$column_id'";

		$d = $db->GetRow($sql);
		print "<div>" . T_("Variable description:") . "</div>";
		print "<div>" . $d['description'] . "</div>";
		
		$process_id = 0;
		if (isset($_GET['process_id'])) $process_id = intval($_GET['process_id']);

		//Select process
		$sql = "SELECT process_id as value, description, CASE WHEN process_id = '$process_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
			FROM process";

		print "<div>" . T_("Select process to apply to this variable:") . "</div>";
		$rs2 = $db->GetAll($sql);
		translate_array($rs2,array("description"));
		display_chooser($rs2,'process_id','process_id',true,"data_id=$data_id&amp;column_id=$column_id");

		if ($process_id != 0)
		{
			print "<div>" . T_("Select operator(s) to apply this process") . "</div>"; 
			
			print "<form action='' method='post'><div><input type='hidden' name='column_id' value='$column_id'/><input type='hidden' name='data_id' value='$data_id'/><input type='hidden' name='process_id' value='$process_id'/></div>";
			
			//display a checkbox of all operators

			$sql = "SELECT o.operator_id,o.description, CONCAT('<input type=\'checkbox\' name=\'oid', o.operator_id, '\' value=\'', o.operator_id, '\'/>') as cbox
				FROM operator as o, operator_process as op, operator_data as od
				WHERE o.operator_id = op.operator_id 
				AND op.process_id = '$process_id'
				AND od.operator_id = o.operator_id
				AND od.data_id = '$data_id'";

			$rs = $db->GetAll($sql);

			//Add an "any operator" field
			$rs[] = array('description' => T_("Any operator: enter how many"), 'cbox' => "<input type='text' name='any' value='0'/>");

			xhtml_table($rs,array('description','cbox'),array(T_("Operator"),T_("Select")));

			print "<div><input type='submit' name='submit' value='" . T_("Create work") . "'/></div></form>";
		}

	}

	//List work already created for this data_id
	$sql = "SELECT w.work_id,c.name,p.description, wp.parent_work_id, o.description as oname
		FROM work as w
		JOIN process as p ON (p.process_id = w.process_id)
		JOIN `column` as c ON (c.data_id = '$data_id' AND w.column_id = c.column_id)
		LEFT JOIN work_parent as wp on (wp.work_id = w.work_id)
		LEFT JOIN operator as o on (w.operator_id = o.operator_id)
		ORDER BY w.work_id ASC";

	$rs = $db->GetAll($sql);
	translate_array($rs,array("description"));
	p(T_("Current work for this data file"),"h2");
	xhtml_table($rs,array('work_id','name','description','parent_work_id','oname'),array(T_("Work ID"),T_("Variable name"),T_("Process description"),T_("Parent job"),T_("Assigned operator")));

}


xhtml_foot();
?>
