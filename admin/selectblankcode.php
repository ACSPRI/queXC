<?
/**
 * Select the code to automatically code when a record is blank 
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
	//assign the code to the blank_code_id field of code_group
	$code_group_id = intval($_POST['code_group_id']);
	$code_id = "NULL";
	if (!empty($_POST['code_id']))
		$code_id = intval($_POST['code_id']);
	
	$sql = "UPDATE code_group
		SET blank_code_id = $code_id
		WHERE code_group_id = $code_group_id";
	
	$db->Execute($sql);
}


xhtml_head(T_("Select blank code"),true,array("../css/table.css"),array("../js/display.js"));

$code_group_id = 0;
if (isset($_GET['code_group_id'])) $code_group_id = intval($_GET['code_group_id']);

//Select code group
$sql = "SELECT code_group_id as value,description, CASE WHEN code_group_id = '$code_group_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM code_group";

print "<div>" . T_("Select code group: ");
$rs2 = $db->GetAll($sql);
translate_array($rs2,array("description"));
display_chooser($rs2,'code_group_id','code_group_id');
print "</div>";

if ($code_group_id != 0)
{
	//List codes for the first level of this code groups (include null)
	$sql = "SELECT c.code_id as value, c.label as description, CASE WHEN c.code_id = cg.blank_code_id THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM `code` as c, code_level as cl, code_group as cg
		WHERE cl.code_group_id = '$code_group_id'
		AND cg.code_group_id = '$code_group_id'
		AND cl.level = 0
		AND c.code_level_id = cl.code_level_id";

	print "<form action='' method='post'><div><input type='hidden' name='code_group_id' value='$code_group_id'/></div>";
	print "<div>" . T_("Select code: ");
	$c = $db->GetAll($sql);
	translate_array($c,array("description"));
	display_chooser($c,'code_id','code_id',true,false,false);
	print "</div>";
	print "<p><input type='submit' name='submit' value='" . T_("Assign as blank code") . "'/></p></form>";

}


xhtml_foot();
?>
