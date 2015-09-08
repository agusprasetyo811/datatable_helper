<?php
/***********************************************************************
 * Datatable Helper Codeigniter
 * source : http://datatables.net/release-datatables/examples/data_sources/server_side.html
 * Edited in use
 * 
 * By Agus Prasetyo
 * email : agusprasetyo811@gmail.com
 ***********************************************************************/

/**
 * Datatable excute function and return json data
 * 
 * @param $aCol
 * @param $sTable
 * @param $sGroupBy
 * @param $sIndexTable
 * @param $anyWhere
 * @return json $output
 */
function datatable_excute($aCol, $sTable, $sGroupBy = NULL, $sIndexTable = NULL, $anyWhere = NULL) {
	$CI =& get_instance();
	 
	$sGroupBy = ($sGroupBy != NULL) ? $sGroupBy : '';
	
	$aColumns = array_keys($aCol);
	$aColVal = array_values($aCol);
	
	$sIndexTable = ($sIndexTable != NULL) ? (int) $sIndexTable : 0;
	
	# Paging
	$sLimit = "";
	
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' ) {
		$sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
				intval( $_GET['iDisplayLength'] );
	}
	
	# Ordering
	$sOrder = "";	
	if ( isset( $_GET['iSortCol_0'] ) ) {
		$sOrder = "ORDER BY  ";
		for ($i=0 ; $i < intval( $_GET['iSortingCols'] ) ; $i++) {
			if ($_GET['bSortable_'.intval($_GET['iSortCol_'.$i])] == "true") {
				//echo intval($_GET['iSortCol_'.$i]).$i;
				$get_col = (intval($_GET['iSortCol_'.$i ]) < 0) ? 0 : intval($_GET['iSortCol_'.$i ]) - $sIndexTable;
				$cek_col[] = $aColVal[intval($_GET['iSortCol_'.$i ]) - $sIndexTable];
				$sOrder .= "". $aColVal[$get_col]." ". ($_GET['sSortDir_'.$i] === 'asc' ? 'asc' : 'desc') .", ";
			}
		}
		
		$sOrder = substr_replace( $sOrder, "", -2 );
		
		if ($sOrder == "ORDER BY") {
			$sOrder = "";
		}
		
		if (!isset($cek_col)) {
			$sOrder = "";
		}
	}
	
	
	# Filtering
	$sWhere = "";
	if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
		$sWhere = "WHERE ";
		
		if (count($anyWhere) != 0) {
			foreach ($anyWhere as $cond) {
				$getAnyCon[] = $cond;
			}
				
			$sWhere .= implode('AND ', $getAnyCon) ." AND ";
		}
		
		for ( $i=0 ; $i<count($aColumns) ; $i++ ) {
			if (strpos($aColumns[$i],'@') !== false) {
			} else {
				$sWhere .= " ".$aColumns[$i]." LIKE '%".mysql_real_escape_string( $_GET['sSearch'] )."%' OR ";
			}
		}
		
		$sWhere = substr_replace( $sWhere, "", -3 );
		
	} else {
		if (count($anyWhere) != 0) {
			foreach ($anyWhere as $cond) {
				$getAnyCon[] = $cond;		
			}
			
			$sWhere .= "WHERE ". implode('AND ', $getAnyCon);
		}	
	}
	
	if (trim($sWhere) == "WHERE") {
		$sWhere = "";
	}
	
	#Individual column filtering
	for ( $i=0 ; $i<count($aColumns) ; $i++ ) {
		if (strpos($aColumns[$i],'@') !== false) {} else {
			if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' ) {
				if ( $sWhere == "" ) {
					$sWhere = "WHERE ";
				} else {
					$sWhere .= " AND ";
				}
				$sWhere .= "".$aColumns[$i]." LIKE '%".mysql_real_escape_string($_GET['sSearch_'.$i])."%' ";
			}	
		}
	}
	
	if ($sGroupBy != '') {
		$sGroupBy = "GROUP BY ". $sGroupBy." ";
	} else {
		$sGroupBy = '';
	}
	
	# SQL queries Get data to display
	for($col = 0; $col < count($aCol); $col++) {
		if (strpos($aColumns[$col],'@') !== false) {
			$aColumns[$col] = substr($aColumns[$col], 1);
		} 
		$getCol[] = $aColumns[$col]." AS ". $aColVal[$col];
	}
	
	$sQuery = "SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $getCol))." FROM   $sTable $sWhere $sGroupBy $sOrder $sLimit ";
	$rResult = $CI->db->query($sQuery);
	
	# Data set length after filtering
	$sQuery = "SELECT FOUND_ROWS() as found_rows";
	$rResultFilterTotal = $CI->db->query($sQuery);
	$aResultFilterTotal = $rResultFilterTotal->row()->found_rows;
	$iFilteredTotal = (string) $aResultFilterTotal;
	
	# Total data set length
	//$sQuery = "SELECT COUNT(".$sIndexColumn.") FROM $sTable $sGroupBy";
	$sQuery = "SELECT * FROM $sTable $sGroupBy";
	$rResultTotal = $CI->db->query($sQuery);
	$aResultTotal = $rResultTotal->num_rows();
	$iTotal = (string) $aResultTotal;
	
	# Output
	$output = array(
			"sEcho" => intval(@$_GET['sEcho']),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iFilteredTotal,
	"aaData" => array()
	);
	
	$no = (@$_GET['iDisplayStart'] != NULL) ? $_GET['iDisplayStart'] + 1 : 1;
	foreach ($rResult->result() as $aRow) {
		$get_no = $no++;
		$row = array();
		for ( $i=0 ; $i<count($aColVal) ; $i++ ) {
			/* General output */
			$row['no'] = @$get_no;
			$row[$aColVal[$i]] = @$aRow->$aColVal[$i];
		}	
		$output['aaData'][] = $row;
	}
	
	return json_encode($output);
}
