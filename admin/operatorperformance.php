<?php 
/**
 * Display performance of operators
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

xhtml_head(T_("Operator performance"),true,array("../css/table.css"),array("../js/display.js"));

$operator_id = 0;
if (isset($_GET['operator_id'])) $operator_id = intval($_GET['operator_id']);
$process_id = 0;
if (isset($_GET['process_id'])) $process_id = intval($_GET['process_id']);


//Select a operator 
$sql = "SELECT operator_id as value,description, CASE WHEN operator_id = '$operator_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM operator";

print "<div>" . T_("Select operator: ");
display_chooser($db->GetAll($sql),'operator_id','operator_id',true,"process_id=$process_id");
print "</div>";

//Select a process 
$sql = "SELECT process_id as value,description, CASE WHEN process_id = '$process_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM process";

print "<div>" . T_("Select process: ");
display_chooser($db->GetAll($sql),'process_id','process_id',true,"operator_id=$operator_id");
print "</div>";


//List performance by operator and process
$sql = "SELECT o.description AS odes, p.description AS pdes, count( * ) as count , AVG( TIMESTAMPDIFF(SECOND , wu.assigned, wu.completed ) ) as avgtime
	FROM work_unit AS wu
	JOIN operator AS o ON ( o.operator_id = wu.operator_id )
	JOIN process AS p ON ( p.process_id = wu.process_id )
	WHERE wu.completed IS NOT NULL
	AND wu.assigned IS NOT NULL
	AND TIMESTAMPDIFF(SECOND, wu.assigned, wu.completed) < '" . PERFORMANCE_IGNORE_LONGER_THAN . "' ";

if ($operator_id != 0) $sql .= " AND wu.operator_id = '$operator_id' ";
if ($process_id != 0) $sql .= " AND wu.process_id = '$process_id' ";

$sql .= "GROUP BY wu.process_id, wu.operator_id
	ORDER BY avgtime ASC";

$all = $db->GetAll($sql);

xhtml_table($all,array('odes','pdes','avgtime','count'),array(T_("Operator"),T_("Process"),T_("Average time"),T_("Number of records")));


xhtml_foot();
?>
