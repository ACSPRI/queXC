<?
/**
 * Display work assigned to operators and delete if not complete
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


if (isset($_GET['work_id']) && isset($_GET['process_id']) && isset($_GET['operator_id']) )
{
	$work_id = intval($_GET['work_id']);
	$process_id = intval($_GET['process_id']);
	$operator_id = intval($_GET['operator_id']);

	$sql = "DELETE FROM work_unit
		WHERE completed IS NULL
		AND assigned IS NULL
		AND work_id = '$work_id'
		AND process_id = '$process_id'
		AND operator_id  = '$operator_id'";

	$db->Execute($sql);		
}



xhtml_head(T_("Assigned work"),true,array("../css/table.css"),array("../js/display.js"));

//List work to do for this data_id
$sql = "SELECT count(*) as count, wu.supervisor, p.description as processd, o.description as odes, CONCAT('<a href=\'?work_id=',wu.work_id,'&amp;process_id=',wu.process_id,'&amp;operator_id=',wu.operator_id,'\'>" . T_("Delete") . "</a>') as dele
	FROM `work_unit` AS wu
	JOIN `process` AS p ON ( p.process_id = wu.process_id )
	LEFT JOIN operator AS o ON (wu.operator_id = o.operator_id)
	WHERE wu.assigned IS NULL and wu.completed IS NULL 
	GROUP BY wu.operator_id,wu.process_id,wu.work_id,wu.supervisor ";

$rs = $db->GetAll($sql);

print "<div>" . T_("Assigned work") .  "</div>";

if (empty($rs))
	print "<p>" . T_("No assigned work") .  "</p>";
else
{
	translate_array($rs,array("processd"));
	xhtml_table($rs,array('count','processd','odes','supervisor','dele'),array(T_("Rows to do"),T_("Process to apply"),T_("Operator"),T_("Supervisor?"),T_("Delete assigned work")));
}

xhtml_foot();
?>
