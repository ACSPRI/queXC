<?php 
/**
 * Import a code file from an un-headered CSV file of this format:
 * code,keywords
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
 * @link http://www.acspri.org.au/software/ queXC was writen for ACSPRI
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
 * Import functions
 */
include("../functions/functions.import.php");

xhtml_head(T_("Import code keywords"));

if (isset($_POST['import_file']))
{
	$code_group_id = intval($_POST['code_group_id']);

	//file has been submitted
	$datafname = tempnam("/tmp", "FOO");
	move_uploaded_file($_FILES['datafile']['tmp_name'],$datafname);

	if ($_FILES['datafile']['error'] == UPLOAD_ERR_OK)
	{
		$outcome = import_keyword_code($datafname,$_POST['description'],$code_group_id);
		if ($outcome === true)
		{
			p(T_("Successful import"),"h1");
		}
		else
		{
			p(T_("Failed to import. Could not determine corresponding code for:"),"h1");
			print "<pre>";
			print_r($outcome);
			print "</pre>";
		}
	}
	else
		p(T_("Failed to import codes") . " - " . T_("Error uploading file"),"h1");
	
}

p(T_("The CSV file must have 2 fields: code,keywords"),"p");
p(T_("No fields may be blank"),"p");
p(T_("Do not include a header line as this will be imported as a code/keyword pair"),"p");

//Select a code group to export data from
$sql = "SELECT code_group_id as value,description, ''  AS selected
	FROM code_group";

$rs2 = $db->GetAll($sql);
translate_array($rs2,array("description"));
print "</div>";


?>
<form enctype="multipart/form-data" action="" method="post">
<p><input type="hidden" name="MAX_FILE_SIZE" value="1000000000" /></p>
<p><?php  echo T_("Choose the CSV code keywords file to upload:"); ?><input name="datafile" type="file" /></p>
<p><?php  echo T_("Description for this code keywords file:"); ?><input name="description" type="text" /></p>
<p><?php  echo T_("What coding scheme does this list apply to?"); display_chooser($rs2,'code_group_id','code_group_id',false,false,false,false); ?>
<p><input type="submit" name="import_file" value="<?php  echo T_("Create new keyword code"); ?>"/></p>
</form>
<?php 

xhtml_foot();
?>
