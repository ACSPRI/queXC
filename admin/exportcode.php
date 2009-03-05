<?
/**
 * Export Code group 
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

/**
 * Export functions
 */
include("../functions/functions.export.php");

if (isset($_GET['codegroup']))
{
	export_code(intval($_GET['codegroup']));
	exit();
}

xhtml_head(T_("Export code groups"),true,array("../css/table.css"),array("../js/display.js"));

$code_group_id = 0;
if (isset($_GET['code_group_id'])) $code_group_id = intval($_GET['code_group_id']);

//Select a code group to export data from
$sql = "SELECT code_group_id as value,description, CASE WHEN code_group_id = '$code_group_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM code_group";

print "<div>" . T_("Select code group: ");
display_chooser($db->GetAll($sql),'code_group_id','code_group_id');
print "</div>";

if ($code_group_id != 0)
	print "<p><a href='?codegroup=$code_group_id>" . T_("Export code group") . "</a></p>";


xhtml_foot();
?>
