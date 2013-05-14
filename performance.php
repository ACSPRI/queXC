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
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright Australian Consortium for Social and Political Research (ACSPRI) 2007,2008,2009,2010
 * @package queXC
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXC was writen for ACSPRI
 * @license http://opensource.org/licenses/agpl-v3.html The GNU Affero General Public License (AGPL) Version 3
 * 
 */

/**
 * Configuration file
 */
include ("config.inc.php");

/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * DB functions
 */
include("db.inc.php");

/**
 * Work functions
 */
include("functions/functions.work.php");

$operator_id = get_operator_id();

$work_unit_id = get_work($operator_id);

xhtml_head(T_("Operator performance"),true,array("css/table.css"),array("js/display.js"));

//List performance by operator and process
$sql = "SELECT o.description AS odes, p.description AS pdes, count( * ) as count , AVG( TIMESTAMPDIFF(SECOND , wu.assigned, wu.completed ) ) as avgtime, o.operator_id
	FROM work_unit AS wu
	JOIN operator AS o ON ( o.operator_id = wu.operator_id )
	JOIN process AS p ON ( p.process_id = wu.process_id )
	JOIN work_unit AS wu2 ON (wu2.work_unit_id = '$work_unit_id' AND p.process_id = wu2.process_id)
	WHERE wu.completed IS NOT NULL
	AND wu.assigned IS NOT NULL
	AND TIMESTAMPDIFF(SECOND, wu.assigned, wu.completed) < '" . PERFORMANCE_IGNORE_LONGER_THAN . "' 
        GROUP BY wu.process_id, wu.operator_id
	ORDER BY avgtime ASC";

$all = $db->GetAll($sql);

xhtml_table($all,array('odes','avgtime','count'),array(T_("Operator"),T_("Average time"),T_("Number done")),"tclass",array("operator_id" => $operator_id));

xhtml_foot();
?>
