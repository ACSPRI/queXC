<?
/**
 * Display how much work is left to do
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

/**
 * Work functions
 */
include("../functions/functions.work.php");


if (isset($_GET['del']))
{
	$work_id = intval($_GET['del']);

	$db->StartTrans();

	//Delete all work that is dependend on this, including this:
	delete_work($work_id);

	$db->CompleteTrans();
}



xhtml_head(T_("Work remaining"),true,array("../css/table.css"),array("../js/display.js"));

$data_id = 0;
if (isset($_GET['data_id'])) $data_id = intval($_GET['data_id']);

//Select a data file 
$sql = "SELECT data_id as value,description, CASE WHEN data_id = '$data_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM data";

print "<div>" . T_("Select data file: ");
display_chooser($db->GetAll($sql),'data_id','data_id');
print "</div>";

//List work to do for this data_id
$sql = "SELECT count(*) as count, d.description as datad, p.description as processd, c.name, o.description as odes, CONCAT('<a href=\'?del=',w.work_id,'\'>" . T_("Delete") . "</a>') as dele
	FROM `work` AS w
	LEFT JOIN work_parent AS wp ON ( wp.work_id = w.work_id )
	JOIN `process` AS p ON ( p.process_id = w.process_id )
	JOIN `column` AS c ON ( c.column_id = w.column_id )
	JOIN `data` AS d ON ( d.data_id = c.data_id)
	JOIN cell AS ce ON ( ce.column_id = w.column_id )
	LEFT JOIN work_unit AS wu2 ON ( wu2.cell_id = ce.cell_id AND wu2.work_id = wp.parent_work_id AND wu2.completed IS NOT NULL )
	LEFT JOIN work_unit AS wu ON ( wu.cell_id = ce.cell_id AND wu.process_id = w.process_id AND w.work_id = wu.work_id )
	LEFT JOIN code_group AS cg ON ( cg.code_group_id = p.code_group_id )
	LEFT JOIN operator AS o ON (w.operator_id = o.operator_id)
	WHERE wu.cell_id IS NULL
	AND (wp.work_id IS NULL OR wu2.cell_id IS NOT NULL)";

if ($data_id != 0)
	$sql .= " AND c.data_id = '$data_id' ";

$sql .= " GROUP BY c.data_id,p.process_id,w.work_id ";

$rs = $db->GetAll($sql);

print "<div>" . T_("Work remaining") .  "</div>";

if (empty($rs))
	print "<p>" . T_("No work remaining") . " <a href='markcolumns.php?data_id=$data_id'>" . T_("Create work") . "</a></p>";
else
	xhtml_table($rs,array('count','name','datad','processd','odes','dele'),array(T_("Rows to do"),T_("Column"),T_("Data file"),T_("Process to apply"),T_("For specific operator?"),T_("Delete work")));


xhtml_foot();
?>
