<?php 
/**
 * Functions relating to importing DDI and Fixed Width data files, and coding CSV
 *
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
 * @subpackage functions
 * @link http://www.deakin.edu.au/dcarf/ queXC was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/agpl-v3.html The GNU Affero General Public License (AGPL) Version 3
 * 
 */


/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

/**
 * Database file
 */
include_once(dirname(__FILE__).'/../db.inc.php');

/**
 * Return only numbers from a string
 *
 * @param string $str String containing any character
 * @return int A number
 * 
 */
function only_numbers($str)
{
	return ereg_replace('[^0-9]*','',$str);
}


/**
 * Create a new data record in the database
 *
 * @param string $description The description of the data file
 * @return int The data_id
 *
 */
function new_data($description)
{
	global $db;

	$description = $db->qstr($description);

	$sql = "INSERT INTO data (data_id,description)
		VALUES (NULL,$description)";

	$db->Execute($sql);

	return $db->Insert_ID();
}

/**
 * Import a fixed width data file
 *
 * @param string $filename The name of the fixed width data file
 * @param int $data_id The data id to import
 * @param int|bool $column_id The column used to identify a row so as not to import duplicate data
 * @return array|bool False if failed to import, otherwise an array containing all row_id's of imported data
 *
 */
function import_fixed_width($filename,$data_id,$column_id = false)
{
	global $db;

	$db->StartTrans();

	//Get all columns for this data file which are in_input
	$sql = "SELECT *
		FROM `column`
		WHERE data_id = '$data_id'
		AND in_input = '1'";

	$rs = $db->Execute($sql);
	$columns = $rs->GetAssoc();

	//Insert line by line, cell by cell using string concatenation
	//If column_id is set, check if this line should be inserted first

	$lines = file($filename);
	
	$row_id = -1;
	
	//If data exists already, get the last row_id in the database
	$sql = "SELECT MAX(row_id) as row_id
		FROM cell
		WHERE column_id = '" . key($columns) . "'";
	
	$max_row_id = $db->GetRow($sql);

	if (isset($max_row_id['row_id'])) $row_id = intval($max_row_id['row_id']);

	$rowsimported = array();

	foreach($lines as $line)
	{
		set_time_limit(240); //need to do this per line as it will take a while...

		//if column_id is set, see if this already exists
		if ($column_id)
		{
			if (isset($columns[$column_id]))
			{
				$compare = $columns[$column_id];

				//Get the id data from the data file
				$data = $db->qstr(substr($line, intval($compare['startpos']) - 1, $compare['width']));

				$sql = "SELECT cr.data
					FROM cell_revision as cr, cell as c
					WHERE c.column_id = '$column_id'
					AND cr.cell_id  = c.cell_id
					AND cr.data LIKE $data";

				$comp = $db->GetAll($sql);

				if (count($comp) >= 1)
					continue; //do not import this row as a key already exists
			}
			else
			{
				//error, the given column isn't set
				return false;
			}
		}

		$row_id++;

		//if we are clear to go, insert the data for this row
		foreach($columns as $column_id_loop => $column)
		{	
				$data = $db->qstr(substr($line, intval($column['startpos']) - 1, $column['width']));

				$sql = "INSERT INTO cell (cell_id,row_id,column_id)
					VALUES (NULL,'$row_id','$column_id_loop')";
				$db->Execute($sql);
				$cell_id = $db->Insert_ID();
				$sql = "INSERT INTO cell_revision (cell_revision_id,cell_id,data)
					VALUES (NULL, '$cell_id', $data)";
				$db->Execute($sql);
		}

		$rowsimported[$row_id] = $row_id;

	}

	if($db->CompleteTrans())
		return $rowsimported;
	
	return false;
}



/**
 * Import a csv data file
 *
 * @param string $filename The name of the csv data file
 * @param int $data_id The data id to import
 * @param int|bool $column_id The column used to identify a row so as not to import duplicate data
 * @return array|bool False if failed to import, otherwise an array containing all row_id's of imported data
 *
 */
function import_csv_data($filename,$data_id,$column_id = false)
{
	global $db;

	$db->StartTrans();

	//Get all columns for this data file which are in_input
	$sql = "SELECT *
		FROM `column`
		WHERE data_id = '$data_id'
		AND in_input = '1'
		ORDER BY sortorder ASC";

	$rs = $db->Execute($sql);
	$columns = $rs->GetAssoc();

	//Insert line by line, cell by cell using csv function
	//If column_id is set, check if this line should be inserted first

	$handle = fopen($filename, "r");
	
	$row_id = -1;
	
	//If data exists already, get the last row_id in the database
	$sql = "SELECT MAX(row_id) as row_id
		FROM cell
		WHERE column_id = '" . key($columns) . "'";
	
	$max_row_id = $db->GetRow($sql);

	if (isset($max_row_id['row_id'])) 
		$row_id = intval($max_row_id['row_id']);

	$rc = -1;

	$rowsimported = array();

	while (($r = fgetcsv($handle)) !== FALSE) 
	{
		$rc++;
		if ($rc == 0) //skip the first row
			continue;

		$c = 0;
		set_time_limit(240); //need to do this per line as it will take a while...

		//if column_id is set, see if this already exists
		if ($column_id)
		{
			if (isset($columns[$column_id]))
			{
        $compare = $columns[$column_id];
        //check against CSV by sortorder
				$elementnumber = intval($compare['sortorder']);

				//Get the id data from the data file
				$data = $db->qstr($r[$elementnumber]);

				$sql = "SELECT cr.data
					FROM cell_revision as cr, cell as c
					WHERE c.column_id = '$column_id'
					AND cr.cell_id  = c.cell_id
					AND cr.data LIKE $data";

				$comp = $db->GetAll($sql);

				if (count($comp) >= 1)
					continue; //do not import this row as a key already exists
			}
			else
			{
				//error, the given column isn't set
				return false;
			}
		}

		$row_id++;

		//if we are clear to go, insert the data for this row
		foreach($columns as $column_id_loop => $column)
		{	
			$elementnumber = intval($column['sortorder']);
			$data = $db->qstr($r[$elementnumber]);

			$sql = "INSERT INTO cell (cell_id,row_id,column_id)
				VALUES (NULL,'$row_id','$column_id_loop')";
      	$db->Execute($sql);

         //print "ROW: $row_id COLUMN:" . $column['name'] . " INSERT: " . $data . "<br/>\n";

      	$cell_id = $db->Insert_ID();
			
			$sql = "INSERT INTO cell_revision (cell_revision_id,cell_id,data)
				VALUES (NULL, '$cell_id', $data)";
			
    	$db->Execute($sql);
		}

		$rowsimported[$row_id] = $row_id;

	}

	if ($db->CompleteTrans())
		return $rowsimported;
	
	return false;
}



/**
 * Create columns for a CSV file based on the header
 *
 * @param string $filename File name of the csv file
 * @param int $data_id The data ID to create the structure for
 *
 *
 */
function import_csv_columns($filename,$data_id)
{
	global $db;

	//open the CSV file

	$db->StartTrans();

	$row = 1;
	$handle = fopen($filename, "r");
	
	$cols = array();
	$numberofcols = 0;

	while (($r = fgetcsv($handle)) !== FALSE) 
	{
		$c = 0;

		if ($row == 1)
		{
			$numberofcols = count($r);

			//create columns by variable
			foreach($r as $h)
			{
				//create varname by column?
				$varname = "C$c";
				$description = $db->qstr($h);

				$cols[$c] = array('varname' => $varname, 'description' => $description, 'width' => 0, 'type' => 0);
	
				$c++;
			}
		}
		else
		{
			if (count($r) != $numberofcols) //If not the same on each row = error
			{
				print "NOT THE SAME COLS";
				$db->FailTrans();
				break;
			}
			//Update maximum width and type (type priority is 1 for string)
			foreach ($r as $h)
			{
				if (strlen($h) > $cols[$c]['width'])
					$cols[$c]['width'] = strlen($h);

				if ($cols[$c]['type'] == 0 && !is_numeric($h))
					$cols[$c]['type'] = 1;
				
				$c++;
			}

		}


		$row++;
	}

	
	//Create one column group for all
	$sql = "INSERT INTO column_group (column_group_id,description)
		VALUES (NULL,'" . T_("Column group for data_id:") . " $data_id')";

	$db->Execute($sql);

	if ($db->HasFailedTrans()) print "<p>Failed on: $sql</p>";

	$column_group_id = $db->Insert_ID();


	//Create columns
	
	$sort_order = 0;
	foreach($cols as $a)
	{
		$sql = "INSERT INTO `column` (column_id,data_id,column_group_id,name,description,startpos,width,type,in_input,sortorder,code_level_id)
			VALUES (NULL,'$data_id','$column_group_id','{$a['varname']}',{$a['description']},0,'{$a['width']}','{$a['type']}',1,'$sort_order',NULL)";

		$db->Execute($sql);

		if ($db->HasFailedTrans()) print "<p>Failed on: $sql</p>";

		$sort_order++;
	}

	return $db->CompleteTrans();
}

/**
 * Import a DDI file
 *
 * @param string $filename File name of the ddi file
 * @param int $data_id The data ID to create the structure for
 * @param string $description A description of the sample
 *
 *
 */
function import_ddi($filename,$data_id)
{
	global $db;

	if (version_compare(PHP_VERSION,'5','>='))
		require_once(dirname(__FILE__).'/domxml-php4-to-php5.php');

	//open the DDI file
	if (!$dom = domxml_open_file($filename)) 
		return false;

	$root = $dom->document_element();
	$vars = $dom->get_elements_by_tagname("var");
	$varGrps = $dom->get_elements_by_tagname("varGrp");

	$db->StartTrans();

	$sort_order = 0;

	//create columns by variable
	foreach ($vars as $var)
	{
		$varname = $db->qstr($var->get_attribute("name"));
		$locations = $var->get_elements_by_tagname("location");
		$catgrys = $var->get_elements_by_tagname("catgry");

		$descriptions = $var->get_elements_by_tagname("labl");
		$description = $db->qstr($descriptions[0]->get_content());

		//Get the location of the variable
		foreach ($locations as $location){
			$startpos = intval($location->get_attribute("StartPos"));
			$width = intval($location->get_attribute("width"));
		}

		$varFormats = $var->get_elements_by_tagname("varFormat");

		//Get the type of the variable
		foreach ($varFormats as $varFormat){
			//type is 'numeric' or 'character'
			$type = $varFormat->get_attribute("type");
		}

		$t = 0;
		if ($type != "numeric" && $type != "number") $t = 1;

		$code_level_id = "NULL";

		//Look for value labels
		if (!empty($catgrys))
		{
			//Create the code group
			$sql = "INSERT INTO code_group (code_group_id,description)
				VALUES (NULL,CONCAT('" . T_("Code imported from (data_id:variable name)") . " $data_id:',$varname))";

			$db->Execute($sql);

			//if ($db->HasFailedTrans()) print "<p>Failed on: $sql</p>";

			$code_group_id = $db->Insert_ID();

			//Assume DDI only has single level codes
			$sql = "INSERT INTO code_level (code_level_id,code_group_id,level,width)
				VALUES (NULL,'$code_group_id',0,'$width')";

			$db->Execute($sql);

			//if ($db->HasFailedTrans()) print "<p>Failed on: $sql</p>";
			
			$code_level_id = $db->Insert_ID();

			foreach ($catgrys as $catgry){
				$catValus = $catgry->get_elements_by_tagname("catValu");
				$catValu = $db->qstr($catValus[0]->get_content());	
				$labls = $catgry->get_elements_by_tagname("labl");
				$labl = $db->qstr($labls[0]->get_content());
	
				$sql = "INSERT INTO code (code_id,value,label,code_level_id)
					VALUES (NULL,$catValu,$labl,'$code_level_id')";

				$db->Execute($sql);

				if ($db->HasFailedTrans()) print "<p>Failed on: $sql</p>";
			}
		}


		$sql = "INSERT INTO `column` (column_id,data_id,column_group_id,name,description,startpos,width,type,in_input,sortorder,code_level_id)
			VALUES (NULL,'$data_id',0,$varname,$description,'$startpos','$width','$t',1,'$sort_order',$code_level_id)";

		$db->Execute($sql);

		//if ($db->HasFailedTrans()) print "<p>Failed on: $sql</p>";

		$sort_order++;
	}

	//create column groups (varGrp)
	foreach($varGrps as $varGrp)
	{
		$vars = explode(" ",$varGrp->get_attribute("var")); //vars are separated by spaces in var attribute
		$labls = $varGrp->get_elements_by_tagname("labl");
		$labl = $db->qstr($labls[0]->get_content());

		//create a new vargroup with the given label
		$sql = "INSERT INTO column_group (column_group_id,description)
			VALUES (NULL,$labl)";

		$db->Execute($sql);

		//if ($db->HasFailedTrans()) print "<p>Failed on: $sql</p>";

		$column_group_id = $db->Insert_ID();

		//Now assign all variables with the same var name to this column group
		foreach($vars as $var)
		{
			$var = $db->qstr($var);

			$sql = "UPDATE `column`
				SET column_group_id = '$column_group_id'
				WHERE name LIKE $var";

			$db->Execute($sql);

			//if ($db->HasFailedTrans()) print "<p>Failed on: $sql</p>";
		}
	}

	return $db->CompleteTrans();
}


/**
 * Import a CSV file containing a code and keyword
 * 
 * Format of the CSV file:
 * code,keyword
 *
 * @param string $filename The filename of the CSV file to open
 * @param string $description The description of the code keyword group
 * @param int $code_group_id The code group these keywords belong to
 *
 */
function import_keyword_code($filename,$description,$code_group_id)
{
	global $db;

	$db->StartTrans();

	//Create a new code_keyword_group with a description of this code
	$sql = "INSERT INTO code_keyword_group (code_keyword_group_id,code_group_id,description)
		VALUES (NULL,$code_group_id," . $db->qstr($description) . ")";

	$db->Execute($sql);

	$ckgi = $db->Insert_ID();

	//Insert rows in to code_keyword table. 
	$failed = array();
	$succeed  = array();

	$row = 1;
	$handle = fopen($filename, "r");
	while (($r = fgetcsv($handle)) !== FALSE) 
	{
		//find the code_id based on the code value in the CSV file
		do {
			$sql = "SELECT c.*
				FROM `code` as c, code_level as cl
				WHERE c.code_level_id = cl.code_level_id
				AND cl.code_group_id = '$code_group_id'
				AND (c.value LIKE " . $db->qstr($r[0]) . ")";

			$code = $db->GetRow($sql);
			
			if (strlen($r[0]) > 0 && substr($r[0], -1) == "0")
				$r[0] = substr($r[0], 0, -1);
			else
				break;
			

		} while (empty($code));

		if (!empty($code))	
		{
			$code_id = $code['code_id'];
			
			$sql = "INSERT INTO code_keyword (code_keyword_id,code_keyword_group_id,code_id,keyword)
				VALUES (NULL,'$ckgi','$code_id', " . $db->qstr($r[1]) . ")";

			$db->Execute($sql);
		}
		else	
		{
			$db->FailTrans();
			$error = array("Row:" => $row,$r);
			break;
		}
		$row++;
	}
	fclose($handle);

	if($db->CompleteTrans())
		return true;
	
	return $error;
}

/**
 * Import a CSV file containing a coding scheme
 * The scheme may be hierarchical (parent codes)
 * 
 * Format of the CSV file:
 * code,label,keywords,parent_code
 *
 * @param string $filename The filename of the CSV file to open
 * @param string $description The description of the code group
 * @param int $allow Allow additions to this code group? 0 for no, 1 for yes
 *
 */
function import_code($filename,$description,$allow = 0)
{
	global $db;

	$db->StartTrans();

	//Create a new code_group with a description of this code
	$sql = "INSERT INTO code_group (code_group_id,description,allow_additions,in_input)
		VALUES (NULL," . $db->qstr($description) . ",'$allow',0)";

	$db->Execute($sql);

	$code_group_id = $db->Insert_ID();

	$sql = "INSERT INTO code_level (code_level_id,code_group_id,level,width)
		VALUES (NULL,'$code_group_id',0,0)";

	$db->Execute($sql);

	$code_level_id = $db->Insert_ID();

	$codeindex = array();
	$codeparentindex = array();

	//Insert rows into code table

	$row = 1;
	$handle = fopen($filename, "r");
	while (($r = fgetcsv($handle)) !== FALSE) 
	{
		$sql = "INSERT INTO code (code_id,value,label,keywords,code_level_id)
			VALUES (NULL,'{$r[0]}', " . $db->qstr($r[1]) . "," . $db->qstr($r[2]) . ",'$code_level_id')";

		$db->Execute($sql);

		$code_id = $db->Insert_ID();
	
		$codeindex[$r[0]] = $code_id;
		if (strlen(trim($r[3])) > 0)
			$codeparentindex[] = array($r[3],$code_id);
	}
	fclose($handle);

	//Insert code_parent relationship

	foreach($codeparentindex as $r)
	{
		if (!isset($codeindex[$r[0]]))
		{
			$db->FailTrans();
			break;
		}
		else
		{
			$sql = "INSERT INTO code_parent (parent_code_id,code_id)
				VALUES ('{$codeindex[$r[0]]}','{$r[1]}')";
	
			$db->Execute($sql);
		}
	}

	//Calculate levels
	$levels = array();
	$levels[0] = $code_level_id;

	$sql = "SELECT c.code_id
		FROM code as c
		WHERE c.code_level_id = '$code_level_id'";

	$codes = $db->GetAll($sql);

	foreach($codes as $r)
	{
		$lev = 0;
		$ci = $r['code_id'];

		do {
			$sql = "SELECT parent_code_id
				FROM code_parent
				WHERE code_id = '$ci'";
			
			$rs = $db->GetRow($sql);

			if (!empty($rs))
			{
				$ci = $rs['parent_code_id'];
				$lev++;
			}
			else
				break;

		} while(1);

		if ($lev > 0)
		{
			if (!isset($levels[$lev]))
			{
				$sql = "INSERT INTO code_level (code_level_id,code_group_id,level,width)
				VALUES (NULL,'$code_group_id','$lev',0)";

				$db->Execute($sql);

				$levels[$lev] = $db->Insert_ID();
			}

			$sql = "UPDATE code
				SET code_level_id = '{$levels[$lev]}'
				WHERE code_id = '{$r['code_id']}'";

			$db->Execute($sql);
			
		}
	}


	//Now update widths

	$sql = "SELECT code_level_id
		FROM code_level
		WHERE code_group_id = '$code_group_id'";
	
	$rows = $db->GetAll($sql);

	foreach($rows as $r)
	{
		$sql = "SELECT MAX( CHAR_LENGTH( TRIM( value ) ) ) as leng
			FROM code
			WHERE code_level_id = {$r['code_level_id']}";

		$leng = 0;
		$rs = $db->GetRow($sql);
		if (!empty($rs))
		{		
			$leng = $rs['leng'];
		}

		$sql = "UPDATE code_level
			SET width = '$leng'
			WHERE code_level_id = {$r['code_level_id']}";

		$db->Execute($sql);
	}

	if($db->CompleteTrans())
		return $code_group_id;
	
	return false;
}

?>
