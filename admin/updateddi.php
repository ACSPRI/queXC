<?php 
/**
 * Update the data description of a questionnaire
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
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2010
 * @package queXC
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXC was writen for ACSPRI
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
 * Remove all excessive white space
 * 
 * @param string $str a string
 * @return string the string with missing white space
 */
function trimall($str)
{
	return preg_replace('/[\r\n\s\t]+/xms', ' ', trim($str));  
}

if (isset($_GET['submit']) || isset($_GET['submitmove']))
{
	$data_id = intval($_GET['data_id']);
	$column_id = intval($_GET['column_id']);
	$code_level_id = "NULL";
	if (isset($_GET['code_level_id'])) $code_level_id = intval($_GET['code_level_id']);

	$db->StartTrans();
	
	if (isset($_GET['n'.$column_id]) && isset($_GET['d'.$column_id]))
	{
		$name = $db->qstr(trimall($_GET['n'.$column_id]));
		$desc = $db->qstr(trimall($_GET['d'.$column_id]));
		$sql = "UPDATE `column`
			SET name = $name, description = $desc, code_level_id = $code_level_id
			WHERE column_id = '$column_id'";
		$db->Execute($sql);
	}

	if ($code_level_id != "NULL")
	{
		$sql = "SELECT code_id
			FROM code
			WHERE code_level_id = '$code_level_id'";
				
		$codes = $db->GetAll($sql);

		foreach($codes as $c)
		{
			$code_id = $c['code_id'];
			if (isset($_GET['c'.$code_id]) && isset($_GET['l'.$code_id]))
			{
				$value = $db->qstr(trimall($_GET['c'.$code_id]));
				$label = $db->qstr(trimall($_GET['l'.$code_id]));
				$sql = "UPDATE `code`
					SET value = $value, label = $label
					WHERE code_id = '$code_id'";

				$db->Execute($sql);
			}
		}
	}

	$db->CompleteTrans();

	if (isset($_GET['submitmove']))
	{
		$sql = "SELECT column_id
			FROM `column`
			WHERE data_id = '$data_id'
			AND column_id > '$column_id'
			AND in_input = 1
			ORDER BY column_id ASC
			LIMIT 1";
	
		$next = $db->GetRow($sql);
		
		if (!empty($next))
			$_GET['column_id'] = $next['column_id'];
	}
}
	


xhtml_head(T_("Update data description"),true,array("../css/table.css"),array("../js/display.js"));

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
		WHERE in_input = 1
		AND data_id = '$data_id'";

	print "<div>" . T_("Select variable: ");
	$c = $db->GetAll($sql);

	display_chooser($c,'column_id','column_id',true,"data_id=$data_id");
	print "</div>";

	if ($column_id != 0)
	{
		//Select variable (column)
		$sql = "SELECT column_id,name,description,code_level_id
			FROM `column`
			WHERE column_id = '$column_id'";
	
		$rs = $db->GetAll($sql);

		print "<form action='?' method='get'>";
		print "<div><input type='hidden' name='column_id' value='$column_id'/><input type='hidden' name='data_id' value='$data_id'/></div>";
		foreach($rs as $r)
		{
			print "<div><input type='text' name='n{$r['column_id']}' id='n{$r['column_id']}' value=\"{$r['name']}\" size='5'/> 
	<input type='text' name='d{$r['column_id']}' id='d{$r['column_id']}' value=\"{$r['description']}\" size='100'/></div>";
	
			if (!empty($r['code_level_id']))
			{
				$old_code_level_id = 0;
				$cli = $r['code_level_id'];
				if (isset($_GET['old_code_level_id']))
				{
					$old_code_level_id = $_GET['old_code_level_id'];
					if ($old_code_level_id != 0)
						$cli = $old_code_level_id;
				}

				$sql = "SELECT co.code_level_id as value, co.name as description, CASE WHEN co.code_level_id ='$old_code_level_id' THEN 'selected=\'selected\'' ELSE '' END as selected
					FROM `column` as co
					WHERE co.data_id = '$data_id' 
					AND co.column_id < '$column_id'
					AND co.code_level_id IS NOT NULL
					ORDER BY co.column_id DESC";

				$oldcodes = $db->GetAll($sql);

				print "<div>" . T_("Choose an existing code level: ");
				display_chooser($oldcodes,'old_code_level_id','old_code_level_id',true,"data_id=$data_id&amp;column_id=$column_id");
				print "</div>";

				$sql = "SELECT code_id,value,label
					FROM code
					WHERE code_level_id = '$cli'";
				
				$codes = $db->GetAll($sql);
	
				foreach($codes as $c)
					print "<div><input type='text' name='c{$c['code_id']}' id='c{$c['code_id']}' size='2' value=\"{$c['value']}\"/> <input type='text' name='l{$c['code_id']}' id='l{$c['code_id']}' value=\"{$c['label']}\" size='50'/></div>"; 

				print "<div><input type='hidden' name='code_level_id' id='code_level_id' value='$cli'/></div>";

			}
		}

		print "<div><input type='submit' name='submit' id='submit' value='". T_("Update") . "'/><input type='submit' name='submitmove' id='submitmove' value='". T_("Update and move to next column") . "'/></div></form>";
	}
}

xhtml_foot();
?>
