<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Regis TEDONE <regis.tedone@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Section menu' for the 'rt_sectionmenu' extension.
 *
 * @author	Regis TEDONE <regis.tedone@gmail.com>
 * @package	TYPO3
 * @subpackage	tx_rtsectionmenu
 */
class tx_rtsectionmenu_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_rtsectionmenu_pi1';
	var $scriptRelPath = 'pi1/class.tx_rtsectionmenu_pi1.php';
	var $extKey        = 'rt_sectionmenu';
	
	
	/*****************************************************
	 * Fonction that tests if a variable is empty or null
	 ****************************************************/	
	function testEmpty($var) {
		if (empty($var) || $var == '' || $var == NULL || $var == undefined) {
			return true;
		} else {
			return false;
		}
	}
	
	/********************************************************************
	 * Fonction that returns mutiple db records in an asscociative array
	 *******************************************************************/	
	function getMultiDbRecords($fields_array, $table, $where_clause, $group_by = '', $order_by = '', $limit = '', $debug = false) {
		$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = false;
		if($debug) {
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;			
		}
		
		$fields = implode(',', $fields_array);
		$res = $GLOBALS["TYPO3_DB"]->exec_SELECTquery($fields, $table, $where_clause, $group_by, $order_by);
		if($GLOBALS["TYPO3_DB"]->sql_num_rows($res) > 0) {
			$results = array();
			while($row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
				$tmp_ar = array();
				foreach($row as $key=>$value) {
					$tmp_ar[$key] = $value;
				}
				array_push($results,$tmp_ar);	
			}
		} else {
			$results = false;	
		}
		$GLOBALS["TYPO3_DB"]->sql_free_result($res);
		
		if($debug) {
			return $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		} else {
			return $results;
		}
	}
	
	/********************************************************
	 * Fonction that returns a single db record in a variable
	 ********************************************************/	
	function getSingleDbRecord($field, $table, $where_clause, $debug = false) {
		$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = false;
		if($debug) {
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;			
		}		
		$res = $GLOBALS["TYPO3_DB"]->exec_SELECTquery($field, $table, $where_clause);
		if($GLOBALS["TYPO3_DB"]->sql_num_rows($res) == 1) {
			$row = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res);
			$result = $row[$field];	
		} else {
			$result = false;	
		}
		$GLOBALS["TYPO3_DB"]->sql_free_result($res);
		if($debug) {
			return $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		} else {
			return $result;
		}		
	}					
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;
	
		//Databases tables
		$pages_table= 'pages';
		$contents_table= 'tt_content';
		
		//Variable that will hold the section index menu
		$menuwrap = '';

		//Get the current Language
		$current_language = $GLOBALS['TSFE']->sys_language_uid;

		//Get the page id
		$entrypoint = $GLOBALS['TSFE']->id;
		
		//Get this plugin id
		$current_objId = $this->cObj->data['uid'];
		
		//Get the contents of this page
		$contents_where_clause = "`uid`=".$entrypoint;
		$res_contents = $this->getSingleDbRecord('tx_templavoila_flex', $pages_table, $contents_where_clause); 

		//If there is at least one content
		if($res_contents) {
			
			//Get the TV field_name and contents uids list
			$contents_xml_array = t3lib_div::xml2array($res_contents);
			foreach($contents_xml_array['data']['sDEF']['lDEF'] as $field_key => $field_value) {
				$contents_array = t3lib_div::trimExplode(',', $field_value['vDEF']);
				if(in_array($current_objId, $contents_array)) {
					$current_TV_Field = $field_key;
					unset($contents_array[array_search($current_objId, $contents_array)]);
					break;
				}
			}
			
			//$menuwrap .= t3lib_utility_Debug::viewArray($contents_array) . "<br />";
			
			$contents = implode(',', $contents_array);
			
			//Get the contents information for the contents of this page
			$fields = array('uid','CType','tx_templavoila_flex');
			$contents_info_where_clause = "FIND_IN_SET(`uid`, '".$contents."')" . $this->cObj->enableFields($contents_table);
			$contents_info_orderby = "FIND_IN_SET(`uid`, '".$contents."')";
			$contents_info_array = $this->getMultiDbRecords($fields, $contents_table, $contents_info_where_clause, '', $contents_info_orderby); 
			
			//If there is any contents in the page
			if($contents_info_array) {
			
				//FCE container uid search and replace by inside content uid
				
				foreach($contents_info_array as $info) {
					if($info['CType'] == 'templavoila_pi1') {
						$fce_content_xml_array = t3lib_div::xml2array($info['tx_templavoila_flex']);
						foreach($fce_content_xml_array['data']['sDEF']['lDEF'] as $key=>$value) {
							if($key==$current_TV_Field) {
								$fce_content_uid_array = explode(',', $fce_content_xml_array['data']['sDEF']['lDEF'][$current_TV_Field]['vDEF']);
								$position = array_search($info['uid'], $contents_array);
								array_splice($contents_array, $position, 0, $fce_content_uid_array);
								unset($contents_array[array_search($info['uid'], $contents_array)]);
							}
						}
					}
				}
				
				$contents = implode(',', $contents_array);
				
				//Get the contents information for the real contents of this page
				$fields = array('uid','l18n_parent','header','sectionIndex');
				$real_contents_where_clause = "FIND_IN_SET(`uid`, '".$contents."')" . $this->cObj->enableFields($contents_table);
				$real_contents_orderby = "FIND_IN_SET(`uid`, '".$contents."')";
				$real_contents_array = $this->getMultiDbRecords($fields, $contents_table, $real_contents_where_clause, '', $real_contents_orderby); 
				
				//Section index menu display 
				$menuwrap .= '<div class="well well-small"><ul class="unstyled">';
				
				$i = 0;
				foreach($real_contents_array as $real_info) {
	
					//If current Language is not default
					if($real_info['l18n_parent'] != $current_language) {
						$fields = array('uid','header','sectionIndex');
						$real_contents_altlang_where_clause = "`l18n_parent`= " .$real_info['uid'] . " AND `sectionIndex`=1". $this->cObj->enableFields($contents_table);
						$real_contents_altlang_array = $this->getMultiDbRecords($fields, $contents_table, $real_contents_altlang_where_clause); 

						//Contents uid and header search and replace by alternative items
						$real_contents_array[$i]['uid'] = $real_contents_altlang_array[0]['uid'];
						$real_contents_array[$i]['header'] = $real_contents_altlang_array[0]['header'];
						$real_contents_array[$i]['sectionIndex'] = $real_contents_altlang_array[0]['sectionIndex'];
					}
					if($real_contents_array[$i]['sectionIndex'] == 1) {
						//Section menu link
						$section_lnk = t3lib_div::getIndpEnv('TYPO3_SITE_URL').$this->pi_getPageLink($entrypoint).'#c'.$real_contents_array[$i]['uid'];
						//Section index menu construction
						$menuwrap .= '<li>';
						$menuwrap .= '<i class="icon-circle-arrow-down" style="margin-top:0;"></i>&nbsp;&nbsp;';
						$menuwrap .= '<a href="'.$section_lnk.'" target="_top">'.$real_contents_array[$i]['header'].'</a>';
						$menuwrap .= '</li>';
					}
					$i++;
				}
				$menuwrap .= '</ul></div>';
			}
		}
		return $this->pi_wrapInBaseClass($menuwrap);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rt_sectionmenu/pi1/class.tx_rtsectionmenu_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rt_sectionmenu/pi1/class.tx_rtsectionmenu_pi1.php']);
}
?>