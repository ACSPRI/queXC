<?
/**
 * Import a code file from an un-headered CSV file of this format:
 * code,label,keywords,parent_code
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
 * Import functions
 */
include("../functions/functions.import.php");

xhtml_head(T_("Import code group"));

if (isset($_POST['import_file']))
{
	$allow = 0;
	if (isset($_POST['allow'])) 
		$allow = 1;

	//file has been submitted
	$datafname = tempnam("/tmp", "FOO");
	move_uploaded_file($_FILES['datafile']['tmp_name'],$datafname);

	if ($_FILES['datafile']['error'] == UPLOAD_ERR_OK)
	{
		$code_group_id = import_code($datafname,$_POST['description'],$allow);
		if ($code_group_id)
		{
			p(T_("Successful import"),"h1");
			print "<a href='selectblankcode.php?code_group_id=$code_group_id'>" . T_("Select a code to automatically assign when an entry is blank for this code group") . "</a>";
		}
		else
			p(T_("Failed to import code group") . " - " . T_("Error importing codes"),"h1");
	}
	else
		p(T_("Failed to import code group") . " - " . T_("Error uploading file"),"h1");
	
}

p(T_("The CSV file must have 4 fields: code,label,keywords,parent_code"),"p");
p(T_("Only keywords and parent_code may be left blank"),"p");
p(T_("Do not include a header line as this will be imported as a code"),"p");
p(T_("Remember to include codes at the top level (without parents) such as: 'Not codable' and 'Left blank' - these will not be added automatically"),"p");
?>
<form enctype="multipart/form-data" action="" method="post">
<p><input type="hidden" name="MAX_FILE_SIZE" value="1000000000" /></p>
<p><? echo T_("Choose the CSV code file to upload:"); ?><input name="datafile" type="file" /></p>
<p><? echo T_("Description for this code group:"); ?><input name="description" type="text" /></p>
<p><? echo T_("Allow additions to this code group?"); ?><input name="allow" type="checkbox" /></p>
<p><input type="submit" name="import_file" value="<? echo T_("Create new code group"); ?>"/></p>
</form>
<?

xhtml_foot();
?>
