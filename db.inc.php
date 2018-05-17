<?php 
/**
 * Database configuration file
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
 * @subpackage configuration
 * @link http://www.deakin.edu.au/dcarf/ queXC was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/agpl-v3.html The GNU Affero General Public License (AGPL) Version 3
 * 
 */



/**
 * Set locale
 */
include_once(dirname(__FILE__).'/lang.inc.php');

/**
 * Include ADODB
 */
if (!(include_once(ADODB_PATH . 'adodb.inc.php')))
{
	print "<p>ERROR: Please modify config.inc.php for ADODB_PATH to point to your ADODb installation</p>";
}

/**
 * Include ADODB session handling functions
 */
//if (!(include_once(ADODB_PATH . 'session/adodb-session2.php')))
//{
//	print "<p>ERROR: Please modify config.inc.php for ADODB_PATH to point to your ADODb installation</p>";
//}

//global database variable
$db = newADOConnection(DB_TYPE);
$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$db->SetFetchMode(ADODB_FETCH_ASSOC);

//store session in database (see sessions2 table)
//ADOdb_Session::config(DB_TYPE, DB_HOST, DB_USER, DB_PASS, DB_NAME,$options=false);

?>
