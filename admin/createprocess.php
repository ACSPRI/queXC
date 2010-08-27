<?
/**
 * Create a process from a code group 
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


xhtml_head(T_("Create process"),true,array("../css/table.css"),array("../js/display.js"));

if (isset($_POST['submit']))
{
	//Create a new process given the code and parent process (if any)
	$code_group_id = intval($_POST['code_group_id']);
	$description = $db->qstr($_POST['description']);
	$autolabel = 0;
	$autovalue = 0;
	$autokeyword = 0;
	$template = 0;
	$exclusive = 0;
	if (isset($_POST['autolabel'])) $autolabel = 1;
	if (isset($_POST['autokeyword'])) $autokeyword = 1;
	if (isset($_POST['autovalue'])) $autovalue = 1;
	if (isset($_POST['template'])) $template = 1;
	if (isset($_POST['exclusive'])) $exclusive = 1;


	$db->StartTrans();

	$sql = "INSERT INTO process (process_id,description,code_group_id,auto_code_value,auto_code_label,template,exclusive,auto_code_keyword)
		VALUES (NULL,$description,'$code_group_id','$autovalue','$autolabel','$template', '$exclusive','$autokeyword')";
		
	$db->Execute($sql);

	$process_id = $db->Insert_ID();

	if (!empty($_POST['process_id']))
	{
		$parent_process_id = intval($_POST['process_id']);
		$sql = "INSERT INTO process_parent (process_id, parent_process_id)
			VALUES ($process_id,$parent_process_id)";
		$db->Execute($sql);
	}
		
	if ($db->CompleteTrans())
		p(T_("Successfully created process"),"h1");
	else
		p(T_("Failed to create process"),"h1");
}



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
	print "<form action='' method='post'><div><input type='hidden' name='code_group_id' value='$code_group_id'/></div>";
	
	//Parent process (if any)
	$sql = "SELECT process_id as value, description, '' AS selected
		FROM process";
	
	print "<div>" . T_("Select parent process (if any): ");
	$rs2 = $db->GetAll($sql);
	translate_array($rs2,array("description"));
	display_chooser($rs2,'process_id','process_id',true,false,false);
	print "</div>";

	print "<div><input type='checkbox' name='autolabel' id='autolabel'/><label for='autolabel'>" . T_("Automatically assign a code if the code label exactly matches the data?") . "</label></div>";
	print "<div><input type='checkbox' name='autovalue' id='autovalue'/><label for='autovalue'>" . T_("Automatically assign a code if the code value exactly matches the data?") . "</label></div>";
	print "<div><input type='checkbox' name='autokeyword' id='autokeyword'/><label for='autovalue'>" . T_("Automatically assign a code if there is a matching code keyword in the database?") . "</label></div>";
	print "<div><input type='checkbox' name='template' id='template'/><label for='template'>" . T_("Use the code group as a template? (Create a new, editable code group for each work unit that this is assigned to)") . "</label></div>";
	print "<div><input type='checkbox' name='exclusive' id='exclusive'/><label for='exclusive'>" . T_("Should this process only be run by operator(s) that have not worked on the prior process (exclusive)?") . "</label></div>";

	print "<div>" . T_("Name for process using this code: ");
	print "<input type='text' name='description'/>";
	print "</div>";
	print "<p><input type='submit' name='submit' value='" . T_("Create new process") . "'/></p></form>";

}

//List existing processes

$sql = "SELECT p.process_id,p.description as pdes,c.description as cdes
	FROM process as p
	LEFT JOIN code_group AS c ON (c.code_group_id = p.code_group_id)";

p(T_("Existing processes"),'h2');
$rs2 = $db->GetAll($sql);
translate_array($rs2,array("pdes","cdes"));
xhtml_table($rs2,array('process_id','pdes','cdes'),array(T_("Process ID"),T_("Process"),T_("Code Group")));

xhtml_foot();
?>
