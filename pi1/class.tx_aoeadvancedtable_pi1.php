<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Daniel Pötzinger <nospam@spam.de>
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

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Advanced Table Rendering' for the 'aoe_advancedtable' extension.
 *
 * @author     Daniel Pötzinger <nospam@spam.de>
 * @package    TYPO3
 * @subpackage tx_aoeadvancedtable
 */
class tx_aoeadvancedtable_pi1 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    public $prefixId      = 'tx_aoeadvancedtable_pi1';        // Same as class name
    public $scriptRelPath = 'pi1/class.tx_aoeadvancedtable_pi1.php';    // Path to this script relative to the extension dir.
    public $extKey        = 'aoe_advancedtable';    // The extension key.
    public $pi_checkCHash = true;

    private function getTableAttributes($conf, $type)
    {

         // Initializing:
        $tableTagParams_conf = $conf['tableParams_'.$type.'.'];

        $conf['color.'][200] = '';
        $conf['color.'][240] = 'black';
        $conf['color.'][241] = 'white';
        $conf['color.'][242] = '#333333';
        $conf['color.'][243] = 'gray';
        $conf['color.'][244] = 'silver';

         // Create table attributes array:
        $tableTagParams = array();
        $tableTagParams['border'] =  $this->cObj->data['table_border'] ? intval($this->cObj->data['table_border']) : $tableTagParams_conf['border'];
        $tableTagParams['cellspacing'] =  $this->cObj->data['table_cellspacing'] ? intval($this->cObj->data['table_cellspacing']) : $tableTagParams_conf['cellspacing'];
        $tableTagParams['cellpadding'] =  $this->cObj->data['table_cellpadding'] ? intval($this->cObj->data['table_cellpadding']) : $tableTagParams_conf['cellpadding'];
        $tableTagParams['bgcolor'] =  isset($conf['color.'][$this->cObj->data['table_bgColor']]) ? $conf['color.'][$this->cObj->data['table_bgColor']] : $conf['color.']['default'];

         // Return result:
        return $tableTagParams;
    }

    private function getCellDataFromCellValue($c)
    {
        $res=array();

        $res['content']=$c;
        $res['colspan']=false;
        $res['rowspan']=false;
        $res['attributes']=array();
        if ($c[0]=='{') {
            //cell begin with proc instructions, read it
            for ($i=1; $i<strlen($c); $i++) {
                if ($c[$i]=='}') {
                    break;
                }
                $procInstr.=$c[$i];
            }
            //parse procInst
            $attributepairs=GeneralUtility::trimExplode(';', $procInstr);
            foreach ($attributepairs as $attributepair) {
                $attribute=GeneralUtility::trimExplode('=', $attributepair);
                $res['attributes'][$attribute[0]]=$attribute[1];
            }
            $res['content']=substr($c, $i+1);
        }
        if (trim($c)=='<') {
            $res['colspan']=true;
        }
        if (trim($c)=='^') {
            $res['rowspan']=true;
        }
        //unescape special chars
        $res['content']=str_replace('\<', '<', $res['content']);
        $res['content']=str_replace('\^', '^', $res['content']);
        $res['content']=str_replace('\{', '{', $res['content']);
        return $res;
    }


    /**
     * Rendering the "Table" type content element, called from TypoScript (tt_content.table.20)
     *
     * @param  string        Content input. Not used, ignore.
     * @param  array        TypoScript configuration
     * @return string        HTML output.
     * @access private
     */
    public function render_table($content, $conf)
    {
        $content = trim($this->cObj->data['bodytext']);

        // Init FlexForm configuration
         $this->pi_initPIflexForm();

          // Get bodytext field content
         $content = trim($this->cObj->data['bodytext']);
        if (!strcmp($content, '')) {
            return '';
        }

          // get flexform values
         $caption = trim(htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_caption')));
         $summary = trim(htmlspecialchars($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_summary')));
         $useTfoot = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_tfoot'));
         $headerPos = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_headerpos');
         $noStyles = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_nostyles');
         $tableClass = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'acctables_tableclass');

         $delimiter = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tableparsing_delimiter', 's_parsing'));
        if ($delimiter) {
            $delimiter = chr(intval($delimiter));
        } else {
            $delimiter = '|';
        }
         $quotedInput = trim($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'tableparsing_quote', 's_parsing'));
        if ($quotedInput) {
            $quotedInput = chr(intval($quotedInput));
        } else {
            $quotedInput = '';
        }



          // Split into single lines (will become table-rows):
         $rows = GeneralUtility::trimExplode(chr(10), $content);
         reset($rows);

          // Find number of columns to render:
            $cols = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->cObj->data['cols']?$this->cObj->data['cols']:count(explode($delimiter, current($rows))), 0, 100);
          // Traverse rows (rendering the table here)
         $rCount = count($rows);

         //***************************************
         //***********RENDERING*******************

         include_once __DIR__ . 'class.tx_thexttableservice.php';
         $table = GeneralUtility::makeInstance('tx_thexttableservice');
         $table->loadDefinitions(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('aoe_advancedtable') . 'pi1/exttabledefinitions.xml');

         $table->insertRows(0, $rCount-1);
         $table->insertCols(0, $cols-1);

         $cellcontent='';
         //***********Traverse each Row*******************
        for ($row = 0; $row < $table->getRowCount(); $row++) {
            $cells = explode($delimiter, $rows[$row]);
            //Set common row attributes:
            $_rowClass=($row%2)?'tr-odd ':'tr-even ';
            if ($row == ($table->getRowCount()-1)) {
                 //is last row:
                 $_rowClass.='tr-last ';
            }
            $_rowClass.='tr-'.$row;

            $lastCellInRowData=array();
            //***********Traverse each col in row*******************
            for ($col = 0; $col < $table->getColCount(); $col++) {
                $cellData=$this->getCellDataFromCellValue($cells[$col]);
                $cellcontent=$cellData['content'];
                //Set common cell attributes:
                $_cellClass='';
                if ($col == ($table->getColCount()-1)) {
                    //is last col:
                    $_cellClass='td-last ';
                }
                $_cellClass.='td-'.$col;

                //process attributes for the current cell:
                foreach ($cellData['attributes'] as $k => $v) {
                    switch ($k) {
                        case 'class':
                            $_cellClass.=' '.$v;
                            break;
                        case 'rowClass':
                            $_rowClass.=' '.$v;
                            break;
                        case 'cellType':
                            if ($v=='header') {
                                $table->setCellType($col, $row, 'th');
                            }
                            break;
                        default:
                            //$table->setCellAttribute($col, $row, $k,$v);
                            break;
                    }
                }
                $table->setCellAttribute($col, $row, 'class', $_cellClass);

                //***Check colspan and rowspan******
                if ($cellData['colspan']) {
                    $this->setColspanForTable($col, $row, $table);
                } elseif ($cellData['rowspan']) {
                    $this->setRowspanForTable($col, $row, $table);
                } //***********no cellspan and rowspan: add content to the cell.*******************
                else {
                    $cellcontent=$this->cObj->stdWrap($cellcontent, $conf['innerStdWrap.']);
                    $table->setCellContent($col, $row, $cellcontent); //.$col ."x" .$row
                }
            } // (for-loop cols)
            $table->setRowAttribute($row, 'class', $_rowClass);
        } // (for-loop rows)

        //last row is tfoot?
        if ($useTfoot) {
            $table->setRowGroup($table->getRowCount()-1, 'tfoot');
        }
        if ($caption) {
             $table->setTableCaption($caption);
        }
        if ($summary) {
             $table->setTableAttribute('summary', $summary);
        }

        // Set header type:
        $type = intval($this->cObj->data['layout']);
        // Table tag params.
        $tableTagParams = $this->getTableAttributes($conf, $type);
        foreach ($tableTagParams as $key => $value) {
            if ($value !='') {
                $table->setTableAttribute($key, $value);
            }
        }

        $tableClass = 'contenttable contenttable-'.$type;
        $table->setTableAttribute('class', $tableClass);


        // generate id prefix for accessible header
        $headerScope = ($headerPos=='top'?'col':'row');
        $headerIdPrefix = $headerScope.$this->cObj->data['uid'].'-';

        //*******set headercells and header attribute*********
        if ($headerPos == 'top') {
            $table->setRowGroup(0, 'thead');
            $table->setCellTypeInRange(0, 0, $table->getColCount(), 0, 'th');
            $table->setCellAttributesInRange(0, 0, $table->getColCount(), 0, array('scope'=>$headerScope));
            for ($col = 0; $col < $table->getColCount(); $col++) {
                $table->setCellAttribute($col, 0, 'id', $headerIdPrefix.$col);
                $table->setCellAttributesInRange($col, 1, $col, $table->getRowCount(), array('headers'=>$headerIdPrefix.$col));
            }
        } elseif ($headerPos == 'left') {
            $table->setCellTypeInRange(0, 0, 0, $table->getRowCount(), 'th');
            $table->setCellAttributesInRange(0, 0, $table->getColCount(), 0, array('scope'=>$headerScope));
            for ($row = 0; $row < $table->getRowCount(); $row++) {
                $table->setCellAttribute(0, $row, $table->getColCount(), 0, 'id', $headerIdPrefix.$row);
                $table->setCellAttributesInRange(1, $row, $table->getColCount(), $row, array('headers'=>$headerIdPrefix.$row));
            }
        }


        //*******OUTPUT*********
        //debug($table->errorMessage);

        return $table->getXHTML();
    }

    private function setColspanForTable($col, $row, &$table)
    {
        $table->setCellContent($col, $row, '[colspan]');    //dumy set attribute (used for detecting multiple rowspans)
        //search the first cell in col whith colspan and increase it
        $_colspanValue=1;
        for ($j=($col-1); $j>=0; $j--) {
            $foundcolspanValue=$table->getCellAttribute($j, $row, 'colspan');
            if ($foundcolspanValue=='' && $table->getCellContent($j, $row) !='[colspan]') {
                 break;
            }
            if ($foundcolspanValue) {
                $_colspanValue=$foundcolspanValue;
                break;
            }
        }
        $table->setCellAttribute($j, $row, 'colspan', $_colspanValue+1);
    }

    private function setRowspanForTable($col, $row, &$table)
    {
        $table->setCellContent($col, $row, '[rowspan]');    //dummy set attribute (used for detecting multiple rowspans)
        //reverse search for the first cell in row whith rowspan and increase it
        $_rowspanValue=1;
        for ($j=($row-1); $j>=0; $j--) {
            $foundrowspanValue=$table->getCellAttribute($col, $j, 'rowspan');
            if ($foundrowspanValue =='' && $table->getCellContent($col, $j) !='[rowspan]') {
                 break;
            }
            if ($foundrowspanValue) {
                $_rowspanValue=$foundrowspanValue;
                break;
            }
        }
        $table->setCellAttribute($col, $j, 'rowspan', $_rowspanValue+1);
    }
}
