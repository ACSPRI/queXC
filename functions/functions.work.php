<?
/**
 *  Functions relating to the assigning and creating of work
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
 * Spelling file
 */
include_once(dirname(__FILE__).'/functions.spelling.php');

/**
 * Spacing file
 */
include_once(dirname(__FILE__).'/functions.spacing.php');

/**
 * Get the manual function for the given process
 *
 * @param int $process_id the process id
 * @return bool|string false if no manual process, else the function name
 */
function get_process_function($process_id)
{
	global $db;

	$sql = "SELECT manual_process_function
		FROM process
		WHERE process_id = $process_id";
	
	$row = $db->GetRow($sql);

	if (empty($row) || empty($row['manual_process_function']))
		return false;

	return $row['manual_process_function'];

}

/**
 * Delete all work and work_parent records for a work unit
 * Make sure to delete all children of this record as they depend on it
 * 
 * @param $work_id
 */
function delete_work($work_id)
{
	global $db;

	$db->StartTrans();

	$sql = "SELECT work_id
		FROM work_parent
		WHERE parent_work_id = '$work_id'";

	$children = $db->GetAll($sql);

	if (!empty($children))
	{
		foreach ($children as $c)
		{
			delete_work($c['work_id']);
		}
	}

	$sql = "DELETE FROM work
		WHERE work_id = '$work_id'";
	
	$db->Execute($sql);

	$sql = "DELETE FROM work_parent
		WHERE work_id = '$work_id'";
	
	$db->Execute($sql);

	$db->CompleteTrans();
}

/**
 * Remove all uncompleted work units from an operator
 *
 * But don't remove where records are for a supervisor
 *
 * @param int $operator_id The operator id
 */
function remove_work_units($operator_id)
{
	global $db;

	$db->StartTrans();

	//Remove all work units that could technically be assigned to someone else
	$sql = "SELECT wu.work_unit_id
		FROM work_unit AS wu
		LEFT JOIN operator_process AS op ON (op.process_id = wu.process_id AND op.operator_id = wu.operator_id AND op.is_supervisor = 1)
		WHERE wu.operator_id = '$operator_id'
		AND wu.completed IS NULL
		AND op.process_id IS NULL";

	$rs = $db->GetAll($sql);

	if (!empty($rs))
	{
		foreach($rs as $r)
		{
			$wuid = $r['work_unit_id'];
	
			$sql = "DELETE FROM work_unit
				WHERE operator_id = '$operator_id'
				AND work_unit_id = '$wuid'
				AND completed IS NULL";
		
			$db->Execute($sql);
		}
	}

	$db->CompleteTrans();
	
}


/**
 * Refer to supervisor
 *
 * @param int $work_unit_id The work unit to refer
 * @return bool True if could assign, false if cannot (i.e. no supervisor selected)
 */
function refer_to_supervisor($work_unit_id)
{
	global $db;

	$sql = "SELECT o.operator_id
		FROM operator_process as o
		JOIN work_unit AS wu ON (wu.work_unit_id = $work_unit_id AND wu.process_id = o.process_id)
		JOIN work AS w ON (w.work_id = wu.work_id)
		JOIN `column` AS c ON (c.column_id = w.column_id)
		JOIN operator_data AS od ON (od.data_id = c.data_id AND od.operator_id = o.operator_id)
		WHERE o.is_supervisor = 1";

	$rs = $db->GetRow($sql); //will currently just get the first supervisor

	if (!empty($rs))
	{
		$so = $rs['operator_id'];

		$sql = "UPDATE work_unit
			SET operator_id = '$so', assigned = NULL
			WHERE work_unit_id = $work_unit_id 
			AND completed IS NULL";

		$db->Execute($sql);

		return true;
	}

	return false;
}

/**
 * Redo a work unit 
 *
 * @param int $work_unit_id The work unit to redo
 * @param int|bool $operator_id A new operator ID to assign to, false if the same
 */
function redo($work_unit_id,$operator_id = false)
{
	global $db;

	/*
	//This SQL is a problem because when work units are cleared, this will be cleared and the 
	//existing work unit will be deemed to complete the job
	$sql = "INSERT INTO work_unit (work_unit_id,work_id,cell_id,process_id,operator_id,assigned,completed)
		SELECT NULL,work_id,cell_id,process_id,operator_id,NULL,NULL
		FROM work_unit
		WHERE work_unit_id = '$work_unit_id'";
	*/

	$sql = "UPDATE work_unit 
		SET assigned = NULL, completed = NULL
		WHERE work_unit_id = '$work_unit_id'";

	$db->Execute($sql);
}

/**
 * Complete the work for this work_unit_id
 *
 * @param int $work_unit_id
 *
 */
function complete_work($work_unit_id)
{
	global $db;

	$sql = "UPDATE work_unit
		SET completed = NOW()
		WHERE work_unit_id = '$work_unit_id'
		AND assigned IS NOT NULL";
	
	$db->Execute($sql);
}


/**
 * Save and mark as done the data given in the post variable
 *
 * @param array $post The data posted - should include at least work_unit_id
 */
function save_work_process($post)
{
	global $db;

	$work_unit_id = $post['work_unit_id'];

	$db->StartTrans();

	//Get the work unit details

	//If the process is not a code, update the cell (ci) with the posted text

	$sql = "SELECT c.column_id,ce.row_id
		FROM work_unit as wu, `column` as c, work as w, cell as ce, process as p
		WHERE wu.work_unit_id = '$work_unit_id'
		AND wu.process_id = p.process_id
		AND p.code_group_id IS NULL
		AND wu.work_id = w.work_id
		AND c.column_id = w.column_id
		AND ce.cell_id = wu.cell_id";

	$rows = $db->GetAll($sql); //A listing of all columns to write data to.

	if ($db->HasFailedTrans()) print $sql;

	foreach($rows as $row)
	{
		$cell_id = get_cell_id($row['row_id'],$row['column_id']);
		if (isset($post['ci' . $cell_id])) //Only change if something given (don't write blank)
			new_cell_revision($cell_id,$post['ci' . $cell_id],$work_unit_id);
	}


	//If we are dealing with a multi-column, get each column group
	$sql = "SELECT c.column_group_id
		FROM work_unit as wu, `column` as c, work as w, process as p
		WHERE wu.work_unit_id = '$work_unit_id'
		AND wu.process_id = p.process_id
		AND p.code_group_id IS NOT NULL
		AND wu.work_id = w.work_id
		AND w.column_multi_group_id IS NOT NULL
		AND c.column_multi_group_id = w.column_multi_group_id
		GROUP BY c.column_group_id";

	$mg = $db->GetAll($sql);

	$groups = array();

	if (empty($mg))
	{
		$groups[] = " AND c.column_group_id = w.column_group_id ";
	}
	else
	{
		foreach($mg as $m)
		{
			$groups[] = " AND c.column_group_id = {$m['column_group_id']} ";

			//If text is submitted for this group, update via the reference_column_group
			if (isset($post['tt' . $m['column_group_id']]))
			{
				//get column and row id
				$sql = "SELECT c.column_id, ce.row_id
					FROM `column` as c, cell as ce, work_unit as wu
					WHERE reference_column_group_id = '{$m['column_group_id']}'
					AND ce.cell_id = wu.cell_id
					AND wu.work_unit_id = '$work_unit_id'";
				
				$tt = $db->GetRow($sql);

				if (empty($tt))
				{
					print "ERROR saving text data for work unit $work_unit_id for reference column " . $m['column_group_id'];
					exit();
				}	
	
				$cell_id = get_cell_id($tt['row_id'],$tt['column_id']);
				if ($cell_id == false)
					$cell_id = new_cell($tt['row_id'],$tt['column_id']);
				new_cell_revision($cell_id,$post['tt' . $m['column_group_id']],$work_unit_id);
			}
		}
	}

	
	foreach($groups as $g) //loop over either all columns groups for this question or the one
	{
		

		//If the process has a code_group - update all code levels (cli) with the posted codes 

		$sql = "SELECT c.code_level_id,c.column_id,ce.row_id,c.column_group_id
			FROM work_unit as wu, `column` as c, work as w, cell as ce, process as p
			WHERE wu.work_unit_id = '$work_unit_id'
			AND wu.process_id = p.process_id
			AND p.code_group_id IS NOT NULL
			AND wu.work_id = w.work_id
			AND ce.cell_id = wu.cell_id
			AND c.reference_column_group_id IS NULL "; //make sure to ignore reference columns so we don't override what we already did with blanks
	
		$sql .= $g;

		$rs = $db->Execute($sql); //A listing of all columns to write data to.
	
		if ($db->HasFailedTrans()) print $sql;
		
		if (!empty($rs))
		{
			$rows = $rs->GetAssoc(); //By code_level_id
		
			$t = current($rows);
			$row_id = $t['row_id'];
	
			//If a code level doesn't exist in a column, create the column
			foreach($post as $key => $val)
			{
				if (substr($key,0,2) == 'cc')
				{
					$cc = explode("X",substr($key,2));
					$code_level_id = intval($cc[0]);
					$column_group_id = intval($cc[1]);

					if ($column_group_id == $t['column_group_id'] && !isset($rows[$code_level_id]))
					{
						//create a column
	
						$sql = "INSERT INTO `column` (column_id,data_id,column_group_id,name,description,startpos,width,type,in_input,sortorder,code_level_id,column_multi_group_id)
							SELECT NULL,c.data_id,c.column_group_id,CONCAT('L$code_level_id','G',c.column_group_id),CONCAT('" . T_("Code for group:") . " ', cg.description, ' "  . T_("Level:") . " ',cl.level),0,cl.width,(SELECT c.value NOT REGEXP '[0-9]+' as ttype
								FROM code as c
								WHERE c.code_level_id = $code_level_id
								GROUP BY c.value NOT REGEXP '[0-9]+'
								ORDER BY ttype ASC
								LIMIT 1),'0','0',$code_level_id,c.column_multi_group_id
							FROM work_unit AS wu, `column` AS c, work AS w, cell AS ce, code_group as cg, code_level as cl, process as p
							WHERE wu.work_unit_id = $work_unit_id
							AND p.process_id = wu.process_id
							AND p.code_group_id IS NOT NULL
							AND wu.work_id = w.work_id
							AND c.column_group_id = w.column_group_id
							AND ce.cell_id = wu.cell_id
							AND cl.code_level_id = $code_level_id
							AND cg.code_group_id = cl.code_group_id
							LIMIT 1";
	
						$db->Execute($sql);
	
						$column_id = $db->Insert_ID();
	
						$rows[$code_level_id] = array('row_id' => $row_id, 'column_id' => $column_id);
					}
				}
			}
		
			foreach($rows as $key => $row)
			{
				$code_level_id = $key;
				
				$code = ""; //insert blank if nothing submitted - otherwise old codes will shine through
		
				if (isset($post['cc' . $code_level_id . "X" .  $t['column_group_id']])) //if something was submitted for this code level
					$code = $post['cc' . $code_level_id . "X" . $t['column_group_id']];
		
				$cell_id = get_cell_id($row['row_id'],$row['column_id']);
				if ($cell_id == false)
					$cell_id = new_cell($row['row_id'],$row['column_id']);
				new_cell_revision($cell_id,$code,$work_unit_id);
			}
		}
	}
	//Complete this row
	complete_work($work_unit_id);

	$db->CompleteTrans();
}

/**
 * Display the XHTML for processing based on this work_unit
 *
 */
function get_work_process($work_unit_id)
{
	global $db;

	$sql = "SELECT cell_id,process_id,work_id
		FROM work_unit
		WHERE work_unit_id = '$work_unit_id'";
	
	return $db->GetRow($sql);
}


/**
 * Get a listing for the column for this work unit
 *
 * @return array (name,data) of this column, cell
 */
function get_work_column($work_unit_id)
{
	global $db;

	$sql = "SELECT co1.name as name,wu.cell_id as cell_id,co1.description as description
		FROM work_unit as wu
		JOIN work as w ON (w.work_id = wu.work_id)
		JOIN `column` as co1 ON (co1.column_id = w.column_id)
		WHERE wu.work_unit_id = '$work_unit_id'";

	$row = $db->GetRow($sql);

	return array('name' => $row['name'], 'data' => get_cell_data($row['cell_id']), 'description' => $row['description']);
}

/**
 * Get a listing of variables (name,data) for each
 * that have been marked as relevant to this process and data
 *
 * @return array (name,data) of relevant variables
 */
function get_work_other_variables($work_unit_id)
{
	global $db;

	//The work unit describes what column and cell to work on

	//DPCc describes that given a DPC, the given c's are relevant

	$sql = "SELECT CONCAT(c.name, ': ', c.description) as name ,ce2.cell_id,c.code_level_id
		FROM work_unit as wu, column_process_column as cpc, `column` as c, cell as ce1, cell as ce2, work as w
		WHERE wu.work_id = w.work_id
		AND w.column_id = cpc.column_id
		AND wu.process_id = cpc.process_id
		AND wu.work_unit_id = '$work_unit_id'
		AND c.column_id = cpc.relevant_column_id
		AND ce1.cell_id = wu.cell_id
		AND ce2.row_id = ce1.row_id
		AND ce2.column_id = cpc.relevant_column_id";

	$rows = $db->GetAll($sql);

	$data = array();

	foreach($rows as $row)
	{
		list ($t1,$t2) = get_cell_data($row['cell_id']);
		if (!empty($row['code_level_id']))
		{
			$cli = $row['code_level_id'];
			//Replace with code label if a code supplied
			$sql = "SELECT `label`
				FROM `code`
				WHERE `code_level_id` = '$cli'
				AND `value` LIKE '$t1'";
			$rs = $db->GetRow($sql);
			if (!empty($rs))
				$t1 = $rs['label'];
		}
		$data[] = array('name' => $row['name'], 'data' => $t1);
	}

	return $data;
}


/**
 * Get the data description for this work unit
 *
 * @return string The data description or empty if none/false
 */
function get_work_data_description($work_unit_id)
{
	$d = "";

	global $db;

	$sql = "SELECT d.description
		FROM work_unit as wu, work as w, `column` as c, data as d
		WHERE  d.data_id = c.data_id
		AND c.column_id = w.column_id
		AND w.work_id = wu.work_id
		AND wu.work_unit_id = '$work_unit_id'";

	$rs = $db->GetRow($sql);

	if (isset($rs['description'])) $d = $rs['description'];

	return $d;
}

/**
 * Get the operator id
 *
 * @return int|bool The operator Id or false if unable to find
 */
function get_operator_id()
{
	global $db;

	$sql = "SELECT operator_id
		FROM operator
		WHERE username = '{$_SERVER['PHP_AUTH_USER']}'";

	$o = $db->GetRow($sql);

	if (empty($o))
		return false;

	return $o['operator_id'];

}

/**
 * 
 * Copy data from one column to another
 * 
 * @param int $column_id_from The column_id to copy data from
 * @param int $column_id_to The column_id to copy data to
 *
 */
function copy_data($column_id_from,$column_id_to)
{
	global $db;

	$db->StartTrans();
	
	$sql = "SELECT cell_id, row_id
		FROM `cell`
		WHERE column_id = '$column_id_from'";

	$rs = $db->GetAll($sql);

	//Get each latest cell revision and create a new one in the new column
	foreach($rs as $r)
	{
		$cell_id_from = $r['cell_id'];
		$row_id = $r['row_id'];

		list($data,$cell_revision_id) = get_cell_data($cell_id_from);

		$cell_id_to = get_cell_id($row_id,$column_id_to);
		if ($cell_id_to == false)
			$cell_id_to = new_cell($row_id,$column_id_to);

		new_cell_revision($cell_id_to,$data);
	}

	$db->CompleteTrans();
}


/** 
 *
 * Copy a column and return the new column_id of the copy
 *
 * @param int $column_id The column_id to copy
 * @param bool $copy_data Whether to copy the data that exists in the column or not
 * @param bool $copy_code_group Whether to copy the associated code group (if any) or not
 * @param bool|int $column_group_id The column group to assign to
 * @param bool|int $column_multi_group_id The column multi group to assign to
 * @return int The new column_id
 */
function copy_column($column_id,$copy_data = false, $copy_code_group = false, $column_group_id = 'NULL', $column_multi_group_id = 'NULL')
{
	global $db;

	$db->StartTrans();

	$code_level_id = 'code_level_id';

	if ($copy_code_group)
	{
		$sql = "SELECT c.code_level_id, cl.code_group_id, cl.level
			FROM `column` as c, code_level as cl
			WHERE c.column_id = '$column_id'
			AND cl.code_level_id = c.code_level_id";

		$rs = $db->GetRow($sql);

		if (!empty($rs))
		{
			$cgi = $rs['code_group_id'];
			$level = $rs['level'];

			$ncgi = copy_code_group($cgi);

			$sql = "SELECT code_level_id
				FROM code_level
				WHERE level = '$level'
				AND code_group_id = '$ncgi'";

			$cl = $db->GetRow($sql);

			if (!empty($cl))
				$code_level_id = $cl['code_level_id'];
		}
	}

	$sql = "INSERT INTO `column` (`column_id`, `data_id`, `column_group_id`, `column_multi_group_id`, `name`, `description`, `startpos`, `width`, `type`, `in_input`, `sortorder`, `code_level_id`, `reference_column_group_id`)
		SELECT column_id, data_id, $column_group_id, $column_multi_group_id, CONCAT('" . T_("COPY") . "', name), CONCAT('" . T_("Copy of: ") . "', description), 0, width, type, 0, 0, $code_level_id, reference_column_group_id
		FROM `column`
		WHERE column_id = '$column_id'";

	$db->Execute($sql);

	$ncolumn_id = $db->Insert_ID();
	
	if ($copy_data)
		copy_data($column_id,$ncolumn_id);

	$db->CompleteTrans();
	
	return $ncolumn_id;
}

/**
 * Copy a code group and return the code_group_id of the copy
 *
 * @param int code_group_id The code group to copy
 * @param bool allow_additions True if additions are to be allowed, false if not
 * @return int The copy of the code group
 */
function copy_code_group($code_group_id,$allow_additions = true)
{
	global $db;

	$a = 1;
	if (!$allow_additions) $a = 0;

	$db->StartTrans();

	$sql = "INSERT INTO code_group (code_group_id,description,blank_code_id,allow_additions,in_input)
		SELECT NULL,CONCAT('" . T_("Copy of:") ." ',description),blank_code_id,$a,0
		FROM code_group
		WHERE code_group_id = '$code_group_id'";
	
	$db->Execute($sql);

	$ncode_group_id = $db->Insert_ID();

	$sql = "SELECT code_level_id,code_group_id,level,width
		FROM code_level
		WHERE code_group_id = '$code_group_id'";
	
	$rs = $db->GetAll($sql);

	$cl = array();

	//Copy code_levels
	foreach($rs as $r)
	{
		$sql = "INSERT INTO code_level (code_level_id,code_group_id,level,width)
			VALUES (NULL,'$ncode_group_id','{$r['level']}','{$r['width']}')";

		$db->Execute($sql);

		$cl[$r['code_level_id']] = $db->Insert_ID();
	}

	$co = array(); //index of old codes with new codes

	//Copy codes
	foreach($cl as $key => $val)
	{
		$sql = "SELECT code_id,value,label,keywords,code_level_id
			FROM code
			WHERE code_level_id = '$key'";

		$rs = $db->GetAll($sql);

		foreach($rs as $r)
		{
			$sql = "INSERT INTO code (code_id,value,label,keywords,code_level_id)
				VALUES (NULL,'{$r['value']}','{$r['label']}','{$r['keywords']}','$val')";

			$db->Execute($sql);

			$co[$r['code_id']] = $db->Insert_ID();
		}
	}

	//Update blank code
	$sql = "SELECT blank_code_id
		FROM code_group
		WHERE code_group_id = '$ncode_group_id'";

	$rs = $db->GetRow($sql);

	if (isset($rs['blank_code_id']) && !empty($rs['blank_code_id']))
	{
		$nbcid = $co[$rs['blank_code_id']];
		$sql = "UPDATE code_group
			SET blank_code_id = '$nbcid'
			WHERE code_group_id = '$ncode_group_id'";

		$db->Execute($sql);

	}

	//Copy code_parent relationship

	$sql = "SELECT cp.code_id,cp.parent_code_id
		FROM code_parent as cp,code as c,code_level as cl
		WHERE cp.code_id = c.code_id
		AND c.code_level_id = cl.code_level_id
		AND cl.code_group_id = '$code_group_id'";


	$rs = $db->GetAll($sql);

	foreach($rs as $r)
	{
		$cid = $co[$r['code_id']];
		$cpid = $co[$r['parent_code_id']];

		$sql = "INSERT INTO code_parent (code_id,parent_code_id)
			VALUES ('$cid','$cpid')";

		$db->Execute();
	}

	if ($db->CompleteTrans())
		return $ncode_group_id;

	return false;
}


/**
 * Create work and associated columns in the database
 *
 * @param int data_id The id of the data group
 * @param int process_id The process id to apply
 * @param int column_id The column id where the data originates
 * @param array operators An array of operators to assign the work to (if any)
 * @param int|bool $mcgi Multi code_group_id - to create multiple columns if exists
 * @param array|bool $compare Whether we are creating a comparison work item or not (if true, should be an array of parent work_ids)
 * @return bool True on success, false on fail
 */
function create_work($data_id,$process_id,$column_id,$operators = array('NULL'),$mcgi = false,$compare = false)
{
	global $db;

	$db->StartTrans();

	//Determine if any code applies
	$sql = "SELECT code_group_id,template
		FROM process
		WHERE process_id = '$process_id'";

	$c = $db->GetRow($sql);

	$column_group_id = "NULL";
	$cmgi = "NULL";
	$work_id = array();

	if (!empty($c['code_group_id']))
	{
		$mc = array(array('cmgi' => "NULL", 'label' => ""));


		if ($mcgi)
		{
			$sql = "INSERT INTO column_multi_group(description,code_group_id)
				VALUES ('','$mcgi')";

			$db->Execute($sql);
			
			$cmgi = $db->Insert_ID();

			$sql = "SELECT $cmgi as cmgi,c.label as label,cg.blank_code_id,c.code_id
				FROM code as c,code_level as cl,code_group as cg
				WHERE cg.code_group_id = '$mcgi'
				AND cl.code_group_id = cg.code_group_id
				AND c.code_level_id = cl.code_level_id
				AND cl.level = 0"; //select the top level only

			$mc = $db->GetAll($sql);
		}


		//Columns need to be created as we are creating a new code
	
		//need to create a new column for each assigned operator (if there is one)
		foreach($operators as $operator_id)
		{
			foreach($mc as $m) //loop over all multi columns 
			{
				$template = $c['template'];
				$cmgi = $m['cmgi'];
	
				if ($template == 1) //create a new code group based on this one
					$code_group_id = copy_code_group($c['code_group_id'],!$compare); //if comparing don't allow manual additions
				else
					$code_group_id = $c['code_group_id'];
	

				if ($compare !== false)
				{
					//if comparing, create codes in this code group for each column in this work
					$sql = "SELECT column_group_id,column_multi_group_id
						FROM work
						WHERE ( ";
				
					foreach($compare as $c)
						$sql .= " work_id = $c OR";

					$sql = substr($sql,0,-2) . ")";
						
					$ncodes = $db->GetAll($sql);
				
					$width = 0;
	
					foreach($ncodes as $n)
					{
						if (!empty($n['column_group_id'])) $c = "cgi" . $n['column_group_id'];
						if (!empty($n['column_multi_group_id'])) $c = "cmgi" . $n['column_multi_group_id'];

						if (strlen($c) > $width) $width = strlen($c);

						$sql = "INSERT INTO `code` (code_id,value,label,code_level_id)
							SELECT NULL,'$c','$c',code_level_id
							FROM code_level
							WHERE code_group_id = '$code_group_id'";

						$db->Execute($sql);
					}

					$sql = "UPDATE code_level
						SET width = '$width'
						WHERE code_group_id = '$code_group_id'";

					$db->Execute($sql);
				}
	

				//First create a new column_group
				$sql = "INSERT INTO column_group (column_group_id,description,code_group_id)
					SELECT NULL, CONCAT(p.description, ' " . T_("code for variable:") . " ', c.name, CASE WHEN o.operator_id IS NULL THEN '' ELSE CONCAT(' " . T_("by operator:") . " ', o.description) END ), '$code_group_id'
					FROM process as p
					JOIN `column` as c on (c.column_id = '$column_id')
					LEFT JOIN operator as o on (o.operator_id = $operator_id)
					WHERE p.process_id = '$process_id'";
			
				$db->Execute($sql);
				
				$column_group_id = $db->Insert_ID();
	
				//Now create all necessary columns
	
				$sql = "INSERT INTO `column` (column_id,data_id,column_group_id,name,description,startpos,width,type,in_input,sortorder,code_level_id,column_multi_group_id)
					SELECT NULL,'$data_id','$column_group_id',CONCAT('L',ocl.code_level_id,'G$column_group_id'),";

				if ($cmgi == "NULL")
					$sql .= "CONCAT('". T_("Code for group:") ." ',ocg.description, ' " . T_("Level:") . " ',ocl.level)";
				else
					$sql .= "'{$m['label']}'";

				$sql .= ",0,width,(SELECT c.value NOT REGEXP '[0-9]+' as ttype
						FROM code as c
						WHERE c.code_level_id = ocl.code_level_id
						GROUP BY c.value NOT REGEXP '[0-9]+'
						ORDER BY ttype ASC
						LIMIT 1),'0','0',ocl.code_level_id,$cmgi
					FROM code_level as ocl, code_group as ocg
					WHERE ocl.code_group_id = '$code_group_id'
					AND ocg.code_group_id = '$code_group_id'";
	
				$db->Execute($sql);
				
				if ($m['code_id'] == $m['blank_code_id']) //blank code so make this column the blank one
				{
					$temp_column_id = $db->Insert_ID();

					$sql = "UPDATE column_multi_group
						SET blank_column_id = '$temp_column_id'
						WHERE column_multi_group_id = '$cmgi'";

					$db->Execute($sql);
				}

				if ($mcgi) //if multi columns, insert a text column for each code column
				{
					$sql = "INSERT INTO `column` (column_id,data_id,column_group_id,name,description,startpos,width,type,in_input,sortorder,reference_column_group_id) VALUES (NULL,'$data_id','$column_group_id','TG$column_group_id','" . T_("Text for") . " $column_group_id','0','2048','1','0','0','$column_group_id')";
					$db->Execute($sql);
				}
			}

			if ($cmgi != "NULL")
				$column_group_id = "NULL";

			//Create the initial work item
			$sql = "INSERT INTO work (work_id,column_id,column_group_id,process_id,operator_id,column_multi_group_id)
				VALUES (NULL,'$column_id',$column_group_id,'$process_id',$operator_id,$cmgi)";
		
			$db->Execute($sql);
	
			$twid = $db->Insert_ID();

			$work_id[] = $twid;

		}
	}
	else
	{
		//No code group to worry about
		foreach($operators as $operator_id)
		{
			//Create the initial work item
			$sql = "INSERT INTO work (work_id,column_id,column_group_id,process_id,operator_id)
				VALUES (NULL,'$column_id',NULL,'$process_id',$operator_id)";
			
			$db->Execute($sql);
	
			$work_id[] = $db->Insert_ID();
		}
	}


	$nwork_id = array();
	foreach ($work_id as $w) //code for each
		$nwork_id[] = array($w,$process_id);
	

	//Create all dependent work items
	//For multiple operators, each work item will have the same parent process
	do
	{
		$sql = "SELECT parent_process_id
			FROM process_parent
			WHERE process_id = '$process_id'";
	
		$pprocess = $db->GetRow($sql);
	
		if (!empty($pprocess))
		{
			$parent_process_id = $pprocess['parent_process_id'];
		
			$sql = "INSERT INTO work (work_id,column_id,column_group_id,process_id)
				VALUES (NULL, '$column_id',NULL,'$parent_process_id')";
			
			$db->Execute($sql);
		
			$parent_work_id = $db->Insert_ID();

			$nwork_id[] = array($parent_work_id,$parent_process_id);

			foreach($work_id as $wid)
			{
				$sql = "INSERT INTO work_parent (work_id,parent_work_id)
					VALUES ('$wid','$parent_work_id')";
		
				$db->Execute($sql);
			}

			$process_id = $parent_process_id;
			$work_id = array($parent_work_id);
		}
	} while (!empty($pprocess));


	//Insert if comparing
	if ($compare !== false)
	{
		foreach($compare as $c)
		{
			$sql = "INSERT INTO work_parent (work_id,parent_work_id)
				VALUES ('$twid','$c')";
		
			$db->Execute($sql);	
		}

	}

	run_auto_processes(array_reverse($nwork_id));

	return $db->CompleteTrans();
}

/**
 * Get the next work_unit to do
 *
 * @param int $operator_id The ID of the operator
 * @return bool|int The work_unit_id or false if none available to assign
 */
function get_work($operator_id)
{
	global $db;

	$db->StartTrans();

	$work_unit_id = false;

	//Select the next assigned, not completed work unit
	$sql = "SELECT work_unit_id
		FROM work_unit
		WHERE operator_id = '$operator_id'
		AND completed IS NULL
		AND assigned IS NOT NULL";

	$rs = $db->GetAll($sql);

	if (count($rs) == 0)
	{
		//If nothing currently assigned, assign the next work unit to the operator
		$sql = "SELECT work_unit_id
			FROM work_unit
			WHERE operator_id = '$operator_id'
			AND completed IS NULL
			AND assigned IS NULL
			ORDER BY work_unit_id ASC
			LIMIT 1";

		$rs2 = $db->GetRow($sql);

		if (!empty($rs2))
			$work_unit_id = $rs2['work_unit_id'];
		else
			$work_unit_id = assign_work($operator_id); //If nothing assigned or completed available, assign work to this operator

		
		if ($work_unit_id != false)
		{
			$sql = "UPDATE work_unit
				SET assigned = NOW()
				WHERE work_unit_id = $work_unit_id";
	
			$db->Execute($sql);
		}
	}
	else if (count($rs) > 1)
	{
		//Error - there should never be more than one work unit assigned to an operator that is not complete
		print "<p>" . T_("ERROR: There is more than one work_unit assigned to you that is not complete. This may be due to logging on with the same username on multiple machines at the same time. Please avoid doing this - and ask your technician to delete from the work_unit table the rows that are assigned to your operator id") . "</p>";

		exit(); //should we do this?
	}
	else
		$work_unit_id = $rs[0]['work_unit_id'];

	$db->CompleteTrans();

	return $work_unit_id;

}

/** 
 * Assign work to the operator
 *
 * Assigning by column will give you the same type of work
 * by row will give you jobs for the same 'case' in the data
 *
 *
 * @param int $operator_id The id of the operator
 * @param bool $by_row Assign by row - true, false for assign by column
 * @return bool|int False if nothing to assign, otherwise the first work_unit_id assigned
 */
function assign_work($operator_id, $by_row = false)
{
	global $db;

	$db->StartTrans();

	$order = "c.column_id ASC";
	if ($by_row)
		$order = "c.data_id ASC, ce.row_id ASC";

	$sql = "SELECT w.work_id,c.data_id,ce.row_id,w.column_id,ce.cell_id,w.process_id,cg.blank_code_id,w.column_group_id,cg.code_group_id,p.auto_code_value,p.auto_code_label,p.exclusive, cmg.blank_column_id
		FROM `work` AS w
		LEFT JOIN work_parent AS wp ON ( wp.work_id = w.work_id )
		JOIN `process` AS p ON ( p.process_id = w.process_id )
		JOIN `column` AS c ON ( c.column_id = w.column_id )
		JOIN operator_data AS od ON ( od.operator_id = '$operator_id' AND od.data_id = c.data_id )
		JOIN operator_process AS op ON ( op.operator_id = '$operator_id' AND op.process_id = p.process_id )
		JOIN cell AS ce ON ( ce.column_id = w.column_id )
		LEFT JOIN work_unit AS wu2 ON ( wu2.cell_id = ce.cell_id AND wu2.work_id = wp.parent_work_id AND wu2.completed IS NOT NULL )
		LEFT JOIN work_unit AS wu ON ( wu.cell_id = ce.cell_id AND wu.process_id = w.process_id AND w.work_id = wu.work_id)
		LEFT JOIN work_unit AS wu3 ON (wu3.cell_id = ce.cell_id AND wu3.process_id = w.process_id AND wu3.operator_id = '$operator_id')
                LEFT JOIN work_unit AS wu4 ON (wu4.operator_id = '$operator_id' AND wu4.cell_id = ce.cell_id AND wu4.work_id IN (SELECT parent_work_id FROM work_parent WHERE work_parent.work_id = w.work_id))		
		LEFT JOIN column_group as colg ON (colg.column_group_id = w.column_group_id)
		LEFT JOIN code_group as cg ON (cg.code_group_id = colg.code_group_id)
		LEFT JOIN column_multi_group as cmg ON (cmg.column_multi_group_id = w.column_multi_group_id)
		WHERE (w.operator_id IS NULL OR w.operator_id = '$operator_id')
		AND wu.cell_id IS NULL
		AND (wp.work_id IS NULL OR wu2.cell_id IS NOT NULL)
		AND wu3.work_unit_id IS NULL
		AND !(p.exclusive = 1 AND wu4.cell_id IS NOT NULL)
		GROUP BY ce.cell_id, c.column_id, p.process_id
		ORDER BY $order
		LIMIT " . ASSIGN_MAX_LIMIT;

	$rs = $db->GetAll($sql);
	$work_unit_id = false;

	if (!empty($rs))
	{
		$data_id = $rs[0]['data_id'];
		$row_id = $rs[0]['row_id'];
		$column_id = $rs[0]['column_id'];

		$test = 0;

		foreach($rs as $r)
		{
			//If there is a blank_code_id set, check if the cell is blank, and if so, auto assign the blank code id
			if (!empty($r['blank_code_id']) || !empty($r['blank_column_id']))
			{
				list($tdata,$tcell_revision_id) = get_cell_data($r['cell_id']);
				$tdata = trim($tdata);

				if (strlen($tdata) == 0) //The cell is empty
				{
					if (!empty($r['blank_code_id']))
					{
						$blank_code_id = $r['blank_code_id'];
					
						//Code to the blank_code_id in this cell
						$sql = "SELECT co.column_id, c.value
							FROM code AS c
							JOIN `column` AS co ON ( co.code_level_id = c.code_level_id AND co.column_group_id = '{$r['column_group_id']}' )
							WHERE c.code_id = '$blank_code_id'";
					}
					else if (!empty($r['blank_column_id'])) //handle blank column
					{
						$blank_column_id = $r['blank_column_id'];

						//Code this column to the blank code id
						$sql = "SELECT co.column_id, c.value
							FROM `column` as co, code as c
							WHERE co.column_id = '$blank_column_id'
							AND c.code_level_id = co.code_level_id";
					}

					$brs = $db->GetRow($sql);


					if (!empty($brs))
					{
						//code blank to this cell
						$tcell_id = get_cell_id($r['row_id'],$brs['column_id']);
						if ($tcell_id == false)
							$tcell_id = new_cell($r['row_id'],$brs['column_id']);

						//store as done (Create completed work_unit)
						$twork_unit_id = create_work_unit($r['work_id'],$r['cell_id'],$r['process_id'],0);

						new_cell_revision($tcell_id,$brs['value'],$twork_unit_id);

						continue; //Don't insert a work record too
					}
				
				}
				
				
			}

			//Auto code if available
			if (!empty($r['code_group_id']) && ($r['auto_code_label'] == 1 || $r['auto_code_value'] == 1))
			{
				list($tdata,$tcell_revision_id) = get_cell_data($r['cell_id']);
				$tdata = trim($tdata);
				$tdata = $db->qstr($tdata);
				$tcode_group_id = $r['code_group_id'];

				//See if there is EXACTLY ONE label or value which matches this tdata

				$sql = "SELECT c.code_id,c.value,c.code_level_id
					FROM code as c, code_level as cl
					WHERE c.code_level_id = cl.code_level_id
					AND cl.code_group_id = '$tcode_group_id'";

				if ($r['auto_code_value'] == 1 && $r['auto_code_label'] == 1)
					$sql .= " (AND c.value LIKE $tdata OR c.label LIKE $tdata)";
				else if ($r['auto_code_value'] == 1)
					$sql .= " AND c.value LIKE $tdata ";
				else if ($r['auto_code_label'] == 1)
					$sql .= " AND c.label LIKE $tdata ";

				$aall = $db->GetAll($sql);


				if (count($aall) == 1) //one result - so auto code
				{
					$tcode_id = $aall[0]['code_id'];
					$tvalue = $aall[0]['value'];
					$tcode_level_id = $aall[0]['code_level_id'];

					
					//Select all columns for this column group
					$sql = "SELECT c.code_level_id,c.column_id, '' as value, '' as code_id
						FROM `column` as c, code_level as cl
						WHERE c.column_group_id = '{$r['column_group_id']}'
						AND cl.code_level_id = c.code_level_id
						ORDER BY cl.level ASC";


					$ttrs = $db->Execute($sql);
					$tcols = $ttrs->GetAssoc();

					$tcols[$tcode_level_id]['value'] = $tvalue;
					$tcols[$tcode_level_id]['code_id'] = $tcode_id;

					//update tcols for all parents (if any)
					do {
						$sql = "SELECT c.code_id, c.code_level_id, c.value
							FROM code_parent as cp, code as c
							WHERE c.code_id = cp.parent_code_id
							AND cp.code_id = '$tcode_id'
							LIMIT 1";
						
						$tcp = $db->GetRow($sql);

						if (empty($tcp))
							$tcode_id = false;
						else
						{
							$tcode_level_id = $tcp['code_level_id'];
							$tcode_id = $tcp['code_id'];
							$tvalue = $tcp['value'];
									
							$tcols[$tcode_level_id]['value'] = $tvalue;
									
							$tcols[$tcode_level_id]['value'] = $tvalue;
							$tcols[$tcode_level_id]['code_id'] = $tcode_id;
						}


					} while ($tcode_id != false);
					

					//store as done (Create completed work_unit)
					$twork_unit_id = create_work_unit($r['work_id'],$r['cell_id'],$r['process_id'],0);
	
					//Code to all cells using selected code
					foreach ($tcols as $tkey => $tval)
					{
						$tcell_id = get_cell_id($r['row_id'],$tval['column_id']);
						if ($tcell_id == false)
							$tcell_id = new_cell($r['row_id'],$tval['column_id']);

						new_cell_revision($tcell_id,$tval['value'],$twork_unit_id);
					}

					continue; //Don't insert a work record too
				}
			}

			//Add until the row or column changes
			if ($by_row)
			{
				if ($data_id != $r['data_id'] || $row_id != $r['row_id'])
					break;
				$data_id = $r['data_id'];
				$row_id = $r['row_id'];
			}
			else
			{
				if ($column_id != $r['column_id'])
					break;
				$column_id = $r['column_id'];
			}

			$sql = "INSERT INTO work_unit (work_unit_id,work_id,cell_id,process_id,operator_id,assigned,completed)
				VALUES (NULL,{$r['work_id']},{$r['cell_id']},{$r['process_id']},$operator_id,NULL,NULL)";
	
			$db->Execute($sql);


			if ($test == 0)
			{
				$work_unit_id = $db->Insert_ID();
				$test = 1;
			}
		}
	}

	$db->CompleteTrans();

	return $work_unit_id;
}


/**
 * Get auto processes for this data_id
 *
 * @param int $data_id The data_id
 * @return bool|array False if error otherwise an arary containing work and process ids
 */
function get_auto_processes($data_id)
{
	global $db;
	
	$sql = 	"SELECT w.work_id, w.process_id
		FROM `work` as w
		JOIN `column` AS c ON (w.column_id = c.column_id AND c.data_id = '$data_id')
		ORDER BY w.work_id DESC";

	$rs = $db->GetAll($sql);

	if (empty($rs))
		return false;
	
	$r = array();
	foreach($rs as $a)
		$r[] = array($a['work_id'],$a['process_id']);

	return $r;
}


/**
 * Given a list of newly created work_id's
 * Find all parent processes, and execute the automatic ones
 * Must be given in the correct order
 *
 * @param array $work_ids A list of work id's/process_ids
 * @param array|bool $row_ids False to process all rows, otherwise a list of row_ids to process
 */
function run_auto_processes($work_ids, $row_ids = false)
{
	global $db;

	$db->StartTrans();

	foreach($work_ids as $w)
	{
		list($work_id,$process_id) = $w;

		$sql = "SELECT auto_process_function
			FROM process
			WHERE process_id = '$process_id'";

		$process = $db->GetRow($sql);

		if (!empty($process) && !empty($process['auto_process_function']))
		{
			$ap = $process['auto_process_function'];
			
			if (is_callable($ap))
			{
			
				//Get a list of all cell_id's to process (that haven't been already)
				$sql = "SELECT ce.cell_id, ce.row_id
					FROM `work` AS w
					JOIN `column` AS c ON ( c.column_id = w.column_id )
					JOIN cell AS ce ON ( ce.column_id = w.column_id )
					WHERE w.work_id = '$work_id'
					AND w.process_id = '$process_id'";

				$cells = $db->GetAll($sql);

				//for each cell...
				foreach($cells as $cell)
				{
					$cell_id = $cell['cell_id'];
					$row_id = intval($cell['row_id']);
					
					if ($row_ids != false && !isset($row_ids[$row_id]))
						continue; //skip if not on the list of rows to process

					list($data,$cell_revision_id) = get_cell_data($cell_id);
					if (call_user_func($ap,$data,$cell_id,$work_id,$process_id,$row_id) == 0) //return 0, create work unit
						create_work_unit($work_id,$cell_id,$process_id,0);
				}
			}
		}
	}

	return $db->CompleteTrans();
}


/**
 * Display XHTML for comparing
 *
 * @param int $cell_id The cell to display data for
 * @param string $cell_data The data in the cell
 * @param int $work_unit_id
 */
function compare_display($cell_id,$cell_data,$work_unit_id)
{
	global $db;

	//Select column_multi_group or column_group from work parents of this work_id
	
	$sql = "SELECT wp.parent_work_id, w.column_group_id, w.column_multi_group_id
		FROM work_parent as wp, work_unit as wu, work as w
		WHERE wp.work_id = wu.work_id
		AND wu.work_unit_id = '$work_unit_id'
		AND w.work_id = wp.parent_work_id";

	//Loop over all columns for this column_group and display cell contents (break on any difference)

	$rs = $db->GetAll($sql);

	//create an XHTML table so we can display columnwise


	$tablecontents = array();
	$tablecontents_match = array();

	print "<table class='tclass'><tr>";

	$j = 0;
	$maxi = 0;

	foreach($rs as $r)
	{
		$wi = $r['parent_work_id'];
		
		$title = "";
		if (!empty($r['column_group_id'])) $title = "cgi" . $r['column_group_id'];
		if (!empty($r['column_multi_group_id'])) $title = "cmgi" . $r['column_multi_group_id'];

		print "<th>$title</th>";

		$sql = "SELECT c.column_id, ce.cell_id, c.description, c.name, c.code_level_id
			FROM work AS w 
			JOIN `column` AS c ON (w.column_multi_group_id = c.column_multi_group_id OR c.column_group_id = w.column_group_id)
			JOIN `work_unit` as wu ON (wu.work_unit_id = '$work_unit_id')
			JOIN `cell` AS ce2 ON (ce2.cell_id = wu.cell_id)
			LEFT JOIN `cell` AS ce ON (ce.column_id = c.column_id AND ce.row_id = ce2.row_id)
			WHERE w.work_id = '$wi'
			ORDER BY c.column_id ASC";

		$compare = $db->GetAll($sql);
		
		$i = 0;
		$k = 0;

		foreach($compare as $c)
		{
			$ceid = $c['cell_id'];
			$colid = $c['column_id'];
			$desc = $c['description'];

			list($data,$rev) = get_cell_data($ceid);
			
			$contents = "";

			if (!empty($data))
			{
				if (!empty($c['code_level_id']))
				{
					$cli = $c['code_level_id'];

					//Replace with code label if a code supplied
					$sql = "SELECT `label`
						FROM `code`
						WHERE `code_level_id` = '$cli'
						AND `value` LIKE '$data'";
		
					$crs = $db->GetRow($sql);
		
					if (!empty($crs))
						$data = $crs['label'];
				}

				$contents = $desc . " : " . $data;	

				$tablecontents[$i][$j] = $contents;

				$i++;
				if ($i > $maxi) $maxi = $i;
			}
		
			$tablecontents_match[$k][$j] = $contents;
				
			$k++;
		}

		$j++;
	}
	print "</tr>";

	//New table display which matches columns
	for ($x = 0; $x < $k; $x++)
	{
		$blank = true;
		foreach($tablecontents_match[$x] as $c)
		{
			if (!empty($c))
			{
				$blank = false;
				break;
			}
		}
		if (!$blank)
		{
			print "<tr>";
			for ($y = 0; $y < $j; $y++)
			{
				print "<td>";
				if (isset($tablecontents_match[$x][$y])) print $tablecontents_match[$x][$y];
				print "</td>";
			}
			print "</tr>";
		}
	}
	print "</table>";

	/*
	//Original table display
		
	print "<table>";
	for ($x = 0; $x < $maxi; $x++)
	{
		print "<tr>";
		for ($y = 0; $y < $j; $y++)
		{
			print "<td>";
			if (isset($tablecontents[$x][$y])) print $tablecontents[$x][$y];
			print "</td>";
		}
		print "</tr>";
	}
	print "</table>";
	*/

	//coding
        print "<div class='header' id='header'>";
        display_codes($work_unit_id);
	print "</div>";
}

/**
 * Compare columns, and create an auto work unit if they are identical
 * Also create a cell containing a code identifying this as "identical"
 *
 * This function should only be run once for each work_id
 *
 * @param string $data The string in the original column
 * @param int $cell_id The cell id
 * @param int $work_id The work id
 * @param int $process_id The process id
 * @param int $row_id The row id
 * @return int Return 1 if the columns to compare are different, otherwise return 0
 */
function compare($data,$cell_id,$work_id,$process_id,$row_id)
{
	global $db;

	//Select column_multi_group or column_group from work parents of this work_id
	
	$sql = "SELECT parent_work_id
		FROM work_parent
		WHERE work_id = '$work_id'";

	//Loop over all columns for this column_group and compare cell contents (break on any difference)
	
	$rs = $db->GetAll($sql);

	$compare = "";

	foreach($rs as $r)
	{
		$wi = $r['parent_work_id'];

		$sql = "SELECT c.column_id, ce.cell_id
			FROM work AS w 
			JOIN `column` AS c ON (w.column_multi_group_id = c.column_multi_group_id OR c.column_group_id = w.column_group_id)
			LEFT JOIN `cell` AS ce ON (ce.column_id = c.column_id AND ce.row_id = '$row_id')
			WHERE w.work_id = '$wi'
			ORDER BY c.column_id ASC";

		if (empty($compare))
			$compare = $db->GetAll($sql);
		else
		{
			$test = $db->GetAll($sql);

			//print_r($compare);
			//print_r($test);

			for ($i = 0; $i < count($compare); $i++)
			{
				$compareval = $compare[$i]['cell_id'];
				$testval = $test[$i]['cell_id'];
				list($comparedata,$rev) = get_cell_data($compareval);
				list($thisdata,$rev) = get_cell_data($testval); //data from this SQL from the same column

				if ($comparedata != $thisdata)
				{
					//different, return 1
					return 1;
				}
			}
		}
	}

	//If identical - create a code in the new column as "identical" and return 0 to create work unit
	$work_unit_id = create_work_unit($work_id,$cell_id,$process_id,0);
		
	//Get the column_id of the new column to send to
	$sql = "SELECT c.column_id
		FROM `column` as c, work as w
		WHERE w.work_id = '$work_id'
		AND c.column_group_id = w.column_group_id";
	
	$c = $db->GetRow($sql);

	$column_id = $c['column_id'];

	$ncell_id = get_cell_id($row_id,$column_id);
	if ($ncell_id == false)
		$ncell_id = new_cell($row_id,$column_id);
	new_cell_revision($ncell_id,'1',$work_unit_id); //code as '1' "Identical"

	//return 1 as we have created the work_unit here
	return 1;
}


/**
 * Normalise the space for all available records
 *
 * This function should only be run once for each work_id
 *
 * @param string $data The string to space
 * @param int $cell_id The cell id
 * @param int $work_id The work id
 * @param int $process_id The process id
 * @return int Return 1 as we are creating the work unit, so we don't want to do it automatically
 */
function spacing($data,$cell_id,$work_id,$process_id,$row_id)
{
	$ndata = trim(normalise_space($data));

	$work_unit_id = create_work_unit($work_id,$cell_id,$process_id,0);
		
	if (strcmp($data,$ndata) != 0) //if different, create a new revision
		$cell_revision_id = new_cell_revision($cell_id,$ndata,$work_unit_id);

	return 1;
}


/**
 * Spell check all available records
 * If there is a spelling error, DO NOT add an item to the work_unit table
 * so it can be manually assigned to operators
 *
 * @param string $data The string to space
 * @param int $cell_id The cell id
 * @param int $work_id The work id
 * @param int $process_id The process id
 * @return int Return 1 if there is a spelling error, otherwise return 0
 *
 */
function spelling($data,$cell_id,$work_id,$process_id,$row_id)
{

	return 0;
}

/**
 * Given a string, return 1 if it is a valid email address or blank
 * return 0 if it is invalid
 *
 * @param string $string The string to check for a valid email address
 * @return int 0 if invalid, 1 if valid or blank
 */
function email_check($string)
{
	$t = trim($string);
	if (strlen($t) == 0) //blank
		return 1;
	$pattern = '/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+' . '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i';
	return preg_match($pattern, $t);
}

/**
 * Check records for a valid email address
 *
 * @param string $data The string to space
 * @param int $cell_id The cell id
 * @param int $work_id The work id
 * @param int $process_id The process id
 * @return int Return 1 if there is an error, otherwise return 0
 *
 */
function email($data,$cell_id,$work_id,$process_id,$row_id)
{
	if (email_check($data) != 1) //If there is a error, do not create a work_unit record
		return 1;

	return 0;	
}



/**
 * Create a completed work unit (for automatic processes)
 *
 */
function create_work_unit($work_id,$cell_id,$process_id,$operator_id)
{
	global $db;
	
	$sql = "INSERT INTO work_unit (work_unit_id,work_id,cell_id,process_id,operator_id,assigned,completed)
		VALUES (NULL,$work_id,$cell_id,$process_id,$operator_id,NOW(),NOW())";
	
	$db->Execute($sql);

	return $db->Insert_ID();
}


/**
 * Create a new cell for a column and a row
 *
 * @param int $row The row
 * @param int $column_id The column id
 * @return int The new cell_id
 */
function new_cell($row,$column_id)
{
	global $db;

	$sql = "INSERT INTO cell (cell_id,row_id,column_id)
		VALUES (NULL,'$row','$column_id')";

	$db->Execute($sql);

	return $db->Insert_ID();
}


/**
 * Get the cell_id given the row and the column
 *
 * @param int $row The row
 * @param int $column_id The column id
 * @return bool|int Return false if not found, otherwise return the cell_id
 *
 */
function get_cell_id($row,$column_id)
{
	global $db;

	$sql = "SELECT cell_id
		FROM cell
		WHERE row_id = '$row'
		AND column_id = '$column_id'";

	$row = $db->GetRow($sql);

	if (empty($row)) return false;

	return $row['cell_id'];
}


/**
 * Get the latest data and revision from a particular cell
 *
 * @param int cell_id The cell to get data for
 * @param int|bool revision The revision to retrieve, false for the latest
 * @return array An array containing the data, and the cell_revision_id
 */
function get_cell_data($cell_id,$revision=false)
{
	global $db;
	
	$sql = "SELECT data,cell_revision_id
		FROM cell_revision
		WHERE cell_id = '$cell_id'
		ORDER BY cell_revision_id DESC
		LIMIT 1";

	$row = $db->GetRow($sql);

	return array($row['data'],$row['cell_revision_id']);
}

/** 
 * Create a new revision for a cell
 * 
 * @param int $cell_id The cell id to create the revision for
 * @param string $data The data to insert
 * @param int $work_unit_id The work unit id
 * @return int $cell_revision_id The new cell_revision_id created
 */
function new_cell_revision($cell_id,$data,$work_unit_id = "NULL")
{
	global $db;

	$sql = "INSERT INTO cell_revision (cell_revision_id,cell_id,data,work_unit_id)
		VALUES (NULL,$cell_id," . $db->qstr($data) . ",$work_unit_id)";
	
	$db->Execute($sql);

	return $db->Insert_ID();
}


?>
