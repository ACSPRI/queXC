<?php 
/**
 * Create an operator and link to a webserver username for authentication
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
 * Database file
 */
include ("../db.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");


global $db;

$a = false;

if (isset($_POST['operator']))
{
	$operator = $db->qstr($_POST['operator'],get_magic_quotes_gpc());
	$description = $db->qstr($_POST['description'],get_magic_quotes_gpc());
	if (!empty($_POST['operator']))
	{
		$sql = "INSERT INTO operator
			(`operator_id` ,`username` ,`description`)
			VALUES (NULL , $operator, $description);";
	
		if ($db->Execute($sql))
		{
			$a = T_("Added: ") . $operator;	

		}else
		{
			$a = T_("Could not add") . " $operator. " . T_("There may already be an operator of this name");
		}


	}
}


xhtml_head(T_("Add an operator"));

if ($a)
{
?>
	<h3><?php  echo $a; ?></h3>
<?php 
}
?>
<h1><?php  echo T_("Add an operator"); ?></h1>
<p><?php  echo T_("Add an operator to allow them to use queXC"); ?>.</p>
<p><?php  echo T_("Use this form to enter the username of a user based on your directory security system. For example, if you have secured the base directory of queXC using Apache file based security, enter the usernames of the users here."); ?></p>
<form enctype="multipart/form-data" action="" method="post">
	<p><?php  echo T_("Enter the username of an operator to add:"); ?> <input name="operator" type="text"/></p>
	<p><?php  echo T_("Enter a description of the operator to add:"); ?> <input name="description" type="text"/></p>
	<p><input type="submit" value="<?php  echo T_("Add user"); ?>" /></p>
</form>

<?php 

xhtml_foot();

?>

