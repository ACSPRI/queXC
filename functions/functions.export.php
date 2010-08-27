<?
/**
 * Functions relating to exporting data from queXC
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
 * Export CSV file 
 *
 * @param int $data_id The data id to export
 * @param bool $header Whether to echo the header or not
 * @param bool $replacecode Whether to replace the code value with the code text
 * @param bool $headrow Whether to include the first row as the variable names
 * @param bool|array $specificcolumns if an array of column_id's only export these
 * @param bool $allowempty If we should export a record if it is empty 
 *
 */
function export_csv($data_id, $header = true, $replacecode = false, $headrow = true, $specificcolumns = false, $allowempty = true)
{
	global $db;

	$cols = "";

	if (is_array($specificcolumns))
		$cols = " AND (co.column_id = " . implode($specificcolumns, " OR co.column_id = ") . ") ";

	//Get all columns for this data file
	$sql = "SELECT *
		FROM `column` as co
		WHERE co.data_id = '$data_id'
		$cols
		ORDER BY co.in_input DESC , co.startpos ASC , co.sortorder ASC, co.column_id ASC";


	$columns = $db->GetAll($sql);

	//For each (row) export the latest revision from each column
	$sql = "SELECT row_id
		FROM `cell` as c, `column` as co
		WHERE co.data_id = '$data_id'
		AND c.column_id = co.column_id
		$cols
		GROUP BY row_id";

	$rows = $db->GetAll($sql);

	if ($header)
	{
		header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header ("Content-Type: text/csv");
		header ("Content-Disposition: attachment; filename=data_$data_id.csv");
	}

	if ($headrow)
	{
		$ccount = count($columns);
		$t = 1;
		foreach($columns as $c)
		{
			echo "\"" . str_replace("\"", "'", $c['description']) . "\"";
			if ($t < $ccount) 
				echo ",";
			$t++;
		}
		
		echo "\r\n";
	}

	foreach($rows as $row)
	{
		set_time_limit(240); //need to do this per line as it will take a while...
		
		$rowtext = "";

		//Get the latest revision of the data for this row...
		$sql = "SELECT co.startpos, co.width, co.code_level_id, (
				SELECT DATA FROM cell_revision
				WHERE cell_id = c.cell_id
				ORDER BY cell_revision_id DESC
				LIMIT 1) AS data
			FROM `column` AS co
			LEFT JOIN cell AS c ON ( c.column_id = co.column_id AND c.row_id = '{$row['row_id']}' )
			WHERE co.data_id = '$data_id'
			$cols
			ORDER BY co.in_input DESC , co.startpos ASC , co.sortorder ASC, co.column_id ASC";

		$cells = $db->GetAll($sql);
		
		$ccount = count($cells);
		$t = 1;
		$p = 1;
		foreach($cells as $cell)
		{
			$v = $cell['data']; //default is the listed data
	
			if (!$allowempty)
			{
				$tmp = trim($v);
				if (strlen($tmp) == 0)
				{
					$p = 0;
					break; //don't export where a cell will be empty
				}
			}

			if ($replacecode && !empty($cell['code_level_id']))
			{
				$sql = "SELECT label
					FROM `code`
					WHERE code_level_id = {$cell['code_level_id']}
					AND value LIKE '{$cell['data']}'";

				$label = $db->CacheGetRow($sql);
				$v = $label['label'];
			}
			
			$rowtext .= "\"" . str_replace("\"","'",$v) . "\"";
			
			if ($t < $ccount)
				$rowtext .= ",";
			$t++;
		}
		if ($p == 1)
		{
			$rowtext .= "\r\n";
			echo $rowtext;
		}
	}

}



/**
 * Export a fixed width data file
 *
 * @param int $data_id The data id to export
 * @param bool $header Whether to echo the header or not
 *
 */
function export_fixed_width($data_id, $header = true)
{
	global $db;

	//For each (row) export the latest revision from each column
	$sql = "SELECT row_id
		FROM `cell` as c, `column` as co
		WHERE co.data_id = '$data_id'
		AND c.column_id = co.column_id
		GROUP BY row_id";

	$rows = $db->GetAll($sql);

	if ($header)
	{
		header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header ("Content-Type: text");
		header ("Content-Disposition: attachment; filename=data_$data_id.txt");
	}

	
	foreach($rows as $row)
	{
		set_time_limit(240); //need to do this per line as it will take a while...
		
		$rowtext = "";

		//Get the latest revision of the data for this row...
		$sql = "SELECT co.startpos, co.width, co.in_input, (
				SELECT DATA FROM cell_revision
				WHERE cell_id = c.cell_id
				ORDER BY cell_revision_id DESC
				LIMIT 1) AS data
			FROM `column` AS co
			LEFT JOIN cell AS c ON ( c.column_id = co.column_id AND c.row_id = '{$row['row_id']}' )
			WHERE co.data_id = '$data_id'
			ORDER BY co.in_input DESC , co.startpos ASC , co.sortorder ASC, co.column_id ASC";

		//Trim and pad the data to the correct width
		$cells = $db->GetAll($sql);
		
		$startpos = 1;
		foreach($cells as $cell)
		{
			if ($cell['in_input'] == 1)
			{
				if ($cell['startpos'] > $startpos) //if there is a gap, fill it with blank space
				{
					$rowtext .= str_pad(" ",($cell['startpos'] - $startpos)," ",STR_PAD_RIGHT);
					$startpos = $cell['startpos'];
				}
			}							
			$rowtext .= str_pad(substr($cell['data'],0,$cell['width']), $cell['width'], " ", STR_PAD_RIGHT);
			$startpos += $cell['width'];
		}
		
		$rowtext .= "\r\n";

		echo $rowtext;
	}

}


/**
 * Export a DDI file
 *
 * @param int $data_id The data ID to export
 * @param bool $freqs True if we want to export frequencies
 *
 */
function export_ddi($data_id,$freqs = true)
{
	global $db;

	if (version_compare(PHP_VERSION,'5','>='))
		require_once(dirname(__FILE__).'/domxml-php4-to-php5.php');

	$dom = domxml_new_doc("1.0");  //create new file

	$c = $dom->create_element("codeBook");
	$dom->append_child($c);

	$rows = 1;
	$vars = 1;

	$sql = "SELECT row_id
		FROM `cell` as c, `column` as co
		WHERE co.data_id = '$data_id'
		AND c.column_id = co.column_id
		GROUP BY row_id";

	$rs = $db->Execute($sql);
	$rows = $rs->RecordCount();

	$sql = "SELECT column_id
		FROM `column` as co
		WHERE co.data_id = '$data_id'";

	$rs = $db->Execute($sql);
	$vars = $rs->RecordCount();

	$fileDscr = $dom->create_element("fileDscr");
	$fileTxt = $dom->create_element("fileTxt");
	
	$fileName = $dom->create_element("fileName");
	$fileName->set_content(T_("Fixed width Data File"));
	$fileTxt->append_child($fileName);
	
	$dimensns = $dom->create_element("dimensns");
	
	$caseQnty = $dom->create_element("caseQnty");
	$caseQnty->set_content($rows);
	$dimensns->append_child($caseQnty);

	$varQnty = $dom->create_element("varQnty");
	$varQnty->set_content($vars);
	$dimensns->append_child($varQnty);

	$recPrCas = $dom->create_element("recPrCas");
	$recPrCas->set_content("1");
	$dimensns->append_child($recPrCas);

	$recNumTot = $dom->create_element("recNumTot");
	$recNumTot->set_content($rows);
	$dimensns->append_child($recNumTot);

	$fileTxt->append_child($dimensns);

	$fileType = $dom->create_element("fileType");
	$fileType->set_content(T_("Raw data (ACSII)"));
	$fileTxt->append_child($fileType);
	
	$format = $dom->create_element("format");
	$format->set_content(T_("Fixed width"));
	$fileTxt->append_child($format);

	$software = $dom->create_element("software");
	$software->set_content(T_("queXC - http://quexc.sourceforge.net/"));
	$software->set_attribute("version","0.9.2");
	$fileTxt->append_child($software);
	
	$fileDscr->append_child($fileTxt);
	$c->append_child($fileDscr);

	$d = $dom->create_element("dataDscr");
	$c->append_child($d);		//create dataDscr element

	//Create the varGrp elements
	$sql = "SELECT c.column_group_id, cg.description, GROUP_CONCAT( c.name SEPARATOR ' ' ) AS name
		FROM `column` AS c
		LEFT JOIN column_group AS cg ON ( cg.column_group_id = c.column_group_id )
		WHERE c.data_id = '$data_id'
		GROUP BY c.column_group_id";

	$vargroups = $db->GetAll($sql);

	foreach($vargroups as $vg)
	{
		$t = $dom->create_element("varGrp");
		$t->set_attribute("var",$vg['name']);
		$l = $dom->create_element("labl");
		$l->set_attribute("level","VAR GROUP");
		$l->set_content($vg['description']);
		$t->append_child($l);
		//Add to the dataDscr node
		$d->append_child($t);
	}

	
	//Now create the var elements

	$sql = "SELECT c.*
		FROM `column` as c
		WHERE data_id = '$data_id'
		ORDER BY c.in_input DESC , c.startpos ASC , c.sortorder ASC, c.column_id ASC";

	$cols = $db->GetAll($sql);

	$startpos = 1;
	$width = 0;

	foreach ($cols as $col)
	{
		$varname = $col['name'];
		$vardescription = $col['description'];
	
		if ($col['type'] == 0)
			$vartype = 'numeric';
		else
			$vartype = 'character';
		
		if(!empty($col['startpos']) && $col['startpos'] > 0)
			$startpos = $col['startpos'];
		else 
			$startpos = $startpos + $width;

		$width = $col['width'];
		
		$var = $dom->create_element("var");
			$var->set_attribute("ID", "$varname");
			$var->set_attribute("name", "$varname");
			$var->set_attribute("dcml", "0");

		$location = $dom->create_element("location");
			$location->set_attribute("StartPos", "$startpos");
			$location->set_attribute("width", "$width");
	
		$var->append_child($location);
		
		$labl = $dom->create_element("labl");
			$labl->set_attribute("level", "variable");
			$labl->set_content("$vardescription");
	
		$var->append_child($labl);
	
		//If there are categories, insert them here
		if (!empty($col['code_level_id']))
		{
			$code_level_id = $col['code_level_id'];

			$sql = "SELECT value,label
				FROM code
				WHERE code_level_id = '$code_level_id'
				ORDER BY code_id ASC";

			$codes = $db->GetAll($sql);

			foreach($codes as $code)
			{
				$cgr = $dom->create_element("catgry");
				$cgr->set_attribute("missing","N");
				$cgrc = $dom->create_element("catValu");
				$cgrc->set_content($code['value']);
				$cgr->append_child($cgrc);
				$cgrl = $dom->create_element("labl");
				$cgrl->set_attribute("level","category");
				$cgrl->set_content($code['label']);
				$cgr->append_child($cgrl);

				if ($freqs)
				{
					$sql = "SELECT cr1.data
						FROM `column` as co
						LEFT JOIN cell AS c on (c.column_id = co.column_id)
						INNER JOIN cell_revision AS cr1 ON (cr1.cell_id = c.cell_id)
						INNER JOIN cell_revision AS cr2 ON (cr2.cell_id = c.cell_id)
						WHERE co.column_id = '{$col['column_id']}'
						AND TRIM(cr1.data) LIKE '{$code['value']}'
						GROUP BY c.cell_id,cr1.cell_revision_id
						HAVING cr1.cell_revision_id = MAX(cr2.cell_revision_id)";

					$rs = $db->Execute($sql);
					
					$fcount = $rs->RecordCount();
	
					$cstat = $dom->create_element("catStat");
					$cstat->set_attribute("type","freq");
					$cstat->set_content($fcount);
					$cgr->append_child($cstat);
	
					$cstat = $dom->create_element("catStat");
					$cstat->set_attribute("type","percent");
					$cstat->set_content(round(($fcount / $rows) * 100,2));
					$cgr->append_child($cstat);
				}

				$var->append_child($cgr);
			}

		}

		$varformat =  $dom->create_element("varFormat");
			$varformat->set_attribute("type",$vartype);
			$varformat->set_content("ASCII");
	
		$var->append_child($varformat);	
		
		$d->append_child($var);
	}


	//echo a formatted version of the DDI file

	$ret = $dom->dump_mem(true);	
	
	header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header ("Content-Type: text/xml");
	header ("Content-Length: " . strlen($ret));
	header ("Content-Disposition: attachment; filename=ddi_$data_id.xml");

	echo $ret;

}


/**
 * Export a CSV file containing a coding scheme
 * The scheme may be hierarchical (parent codes)
 * 
 * Format of the CSV file:
 * code,label,keywords,parent_code
 *
 * NOTE: Assumes there is only one or 0 parents for each code
 *
 * @param int $code_group_id The code group to export
 *
 */
function export_code($code_group_id)
{
	global $db;

	$sql = "SELECT c.value,c.label,c.keywords,cpc.value as pvalue
		FROM code as c
		JOIN code_level as cl ON (cl.code_group_id = '$code_group_id' AND c.code_level_id = cl.code_level_id)
		LEFT JOIN code_parent AS cp ON (cp.code_id = c.code_id)
		LEFT JOIN code AS cpc ON (cpc.code_id = cp.parent_code_id)
		ORDER BY cl.level ASC,c.value ASC";

	$codes = $db->GetAll($sql);

	header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header ("Content-Type: text");
	header ("Content-Disposition: attachment; filename=code_$code_group_id.csv");


	foreach($codes as $c)
		print "\"{$c['value']}\",\"{$c['label']}\",\"{$c['keywords']}\",\"{$c['pvalue']}\"\r\n";

}


/**
 * Escape a string to work properly with PSPP
 *
 * @param string $string The string to escape
 * @param int $length The maximum length of the string
 * @return string The escaped and cut string
 */
function pspp_escape($string,$length = 250)
{
	$from = array("'", "\r\n", "\n");
	$to   = array("", "", "");
	return substr(str_replace($from, $to, $string),0,$length);
}


/**
 * Export the data in PSPP form (may also work with SPSS)
 *
 * @param int data_id The data id to export
 * @param bool include_data Whether or not to include the data
 *
 */
function export_pspp($data_id, $include_data = true)
{
	global $db;

	header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header ("Content-Type: text");
	header ("Content-Disposition: attachment; filename=data_$data_id.sps");

	if ($include_data)
		echo "DATA LIST FIXED /";
	else 
		echo "DATA LIST FILE=\"data_$data_id.txt\" /";

	//export variables in the format: varname start-end (type)
	//Make sure not to include variables with no name as there is no way to identify them
	$sql = "SELECT c.*
		FROM `column` as c
		WHERE data_id = '$data_id'
		AND c.name IS NOT NULL
		AND c.name != ''
		ORDER BY c.in_input DESC , c.startpos ASC , c.sortorder ASC, c.column_id ASC";

	$cols = $db->GetAll($sql);

	$startpos = 1;
	$width = 0;

	foreach ($cols as $col)
	{
		$varname = $col['name'];
	
		if ($col['type'] == 0)
			$vartype = ' ';
		else
			$vartype = '(A) ';
		
		if(!empty($col['startpos']) && $col['startpos'] > 0)
			$startpos = $col['startpos'];
		else 
			$startpos = $startpos + $width;

		$width = $col['width'];
		
		$endpos = ($startpos + $width) - 1;

		echo "$varname $startpos-$endpos $vartype";
	}

	echo " .\nVARIABLE LABELS ";

	$first = true;
	foreach ($cols as $col)
	{
		$vardescription = pspp_escape($col['description']);
		$varname = $col['name'];
		
		if ($first)			
			$first = false;
		else
			echo "/";

		echo "$varname '$vardescription' ";
	}

	echo " .\nVALUE LABELS";

	//If there are categories, insert them here
	foreach ($cols as $col)
	{
		if (!empty($col['code_level_id']))
		{
			$varname = $col['name'];
			$code_level_id = $col['code_level_id'];


			$sql = "SELECT value,label
				FROM code
				WHERE code_level_id = '$code_level_id'
				ORDER BY code_id ASC";

			$codes = $db->GetAll($sql);

			if (!empty($codes))
			{
				echo " /$varname ";
			
				if ($col['type'] == 0)
					$surround = "";
				else
					$surround = "'";

				foreach($codes as $code)
				{
					echo $surround . $code['value'] . "$surround '" . pspp_escape($code['label'],60) . "' ";
				}
	
			}
		}
	}

	echo " .\n";

	if ($include_data)
	{
		echo "BEGIN DATA.\n";
		export_fixed_width($data_id,false);
		echo "END DATA.\n";
	}
}

?>
