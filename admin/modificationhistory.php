<?
/**
 * Display the modifications done to a data file 
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

xhtml_head(T_("Display modification history"),true,array("../css/table.css"),array("../js/display.js"));

$data_id = 0;
if (isset($_GET['data_id'])) $data_id = intval($_GET['data_id']);

//Select a code group to export data from
$sql = "SELECT data_id as value,description, CASE WHEN data_id = '$data_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM data";

print "<div>" . T_("Select data file: ");
display_chooser($db->GetAll($sql),'data_id','data_id');
print "</div>";

if ($data_id != 0)
{
	$column_id = 0;
	if (isset($_GET['column_id'])) $column_id = intval($_GET['column_id']);
	$row_id = -1;
	if (isset($_GET['row_id'])) $row_id = intval($_GET['row_id']);


	$sql = "SELECT column_id as value,name as description, CASE WHEN column_id = '$column_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM `column` 
		WHERE data_id = '$data_id'";

	print "<div>" . T_("Select column: ");
	display_chooser($db->GetAll($sql),'column_id','column_id',true,"data_id=$data_id&amp;row_id=$row_id");
	print "</div>";

	$sql = "SELECT c.row_id as value,c.row_id as description, CASE WHEN c.row_id = '$row_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM `column` as co, cell as c
		WHERE co.data_id = '$data_id'
		AND c.column_id = co.column_id
		GROUP BY c.row_id";

	print "<div>" . T_("Select row: ");
	display_chooser($db->GetAll($sql),'row_id','row_id',true,"data_id=$data_id&amp;column_id=$column_id");
	print "</div>";


	$sql = "SELECT co.name,c.row_id,p.description as pdes, o.description as odes, assigned, completed, data
		FROM cell AS c
		JOIN `column` AS co ON ( co.data_id = '$data_id' ";

	if ($column_id != 0)
		$sql .= " AND co.column_id = '$column_id' ";

	$sql .= " AND co.column_id = c.column_id )
		JOIN work_unit AS wu ON ( wu.cell_id = c.cell_id )
		JOIN cell_revision AS cr ON ( cr.work_unit_id = wu.work_unit_id )
		LEFT JOIN process as p ON (p.process_id = wu.process_id)
		LEFT JOIN operator as o ON (o.operator_id = wu.operator_id)";

	if ($row_id != -1)
		$sql .= " WHERE c.row_id = '$row_id'";

	$sql .= " ORDER BY cr.cell_revision_id DESC LIMIT 500";

	$rs2 = $db->GetAll($sql);
	translate_array($rs2,array("pdes"));
	xhtml_table($rs2,array('name','row_id','pdes','odes','assigned','completed','data'),array(T_("Column"),T_("Row"),T_("Process"),T_("Operator"),T_("Assigned"),T_("Completed"),T_("Data")));

}

xhtml_foot();
?>
