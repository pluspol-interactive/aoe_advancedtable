<?php
/*! @mainpage Introduction
 *
 * @section help Help me
 *
 * This version is very initial. So if you use it be prepared to some strange behavior.
 * If you find any bugs feel free to fix it and send it to me, or only give a report
 * to me. Every comment helps me to improve this piece of code.
 *
 * IMPORTANT
 * Please report all bugs on bugs.typo3.org. Choose "tx_thexttables" as project from the
 * dropdown list on the right side of the page and choose "Service" as category whren you
 * submit a bugreport. Thanks!
 *
 * @section hints Some hints
 *
 * This class was written to use it in the TYPO3 content management system (typo3.org).
 * But feel free to use in any other environment or standalone.
 *
 * The class consists of two files. The php file wich containss the class itself. and a
 * xml file wich contains the definitions for all types of tags used in the class.
 * You can edit the xml definitions to add or remove some attributes, tags or values (in sets)
 * An independent documentation of the xml-structure follows.
 *
 * So now, browse this documentation and use the class. Find bugs or give me suggestions.
 * HAVE FUN :)
 *
 * @section changelog Changelog
 *
 * 0.2.0 - REVISION 6
 *                      Fixed a buggy usage of array_merge in combination with PHP5
 * 0.1.3 - REVISION 5
 *			Fixed a bug in group allocation in "importXHTML" (Line 2517)
 * 0.1.2 - REVISION 4
 *			Bugfixes
 *			Changed the dataformat of the tagGroups array. This was neccessary because of it's
 *			bad structure. This is meaningless for this class but for the backend typo3 extension
 * 0.1.1 - Bugfixes
 *			Fixed a bug that occurs when a '&' where in the tablesource by importing from XHTML
 *			The XML-parser could not handle this, so it have to be masked before parsing
 * 0.1.0 - First public release
 * 0.0.3 - Added support for...
 *			named colors
 * 0.0.2 - Added support for...
 *			tag caption
 *			table attribute summary
 *
 * 0.0.1 - Initial release
 *
 * @section copy Copyright notice
 *
 * (c) 2004-2005 Thomas Hempel (thomas@work.de)
 * All rights reserved
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * TODOS
 *
 * @todo testing and bugfixing
 */

/**
 * KNOWN BUGS
 *
 * @bug	not really a bug, but a conflict. If you have a cell wich is overspanned by col- AND rowspan
 *		it is not handled as failure by this class. It's up to the browser... Firefox for example
 *		overlays the overspanned cell by the overspanning cells.
 */

/**
 * @author	Thomas Hempel <thomas@work.de>
 * @warning	If you find any bugs feel free to fix it and send it to me,
 *			or only give a report.
 *			Every comment helps me to improve this piece of code.
 */

class tx_thexttableservice {
	/// The revision of this class
	var $revision = 6;

	/// An errormessage wich contains the message if an error occured
	var $errorMessage = "";

	/// This is the array wich contains the hole table
	var $tableArray = array();
	/// The array wich contains the tag definitions
	var $tagDefinitions = array();

	/// The number of columns the table has
	var $colCount = 1;
	/// The number of columns the table has
	var $rowCount = 1;

	/// the properties for the table
	var $tableAttributes = array (
		"header_repeat" => 0,
	);

	/// the table caption
	var $tableCaption = array (
		"caption" => "",
		"attributes" => array(),
	);

	/// This defines a default cell with properties
	/// @param	type			the type (needed to get attributes from definitions)
	/// @param	tag				the tag that should be used for this cell (td or th)
	/// @param	skip			controls if the the cell should displayed (0) or not (1)
	/// @param	attributes		the attributes the cell has
	/// @param	content_type	the type of content (This is not used yet, but propably useful for the future)
	/// @param	content			The content of the cell
	var $defaultCell = array (
		"type" => "cell",
		"tag" => "td",
		"skip" => 0,
		"attributes" => array(),
		"content_type" => "text",
		"content" => "&#160;",
	);

	/// defines a default row with properties
	/// @param	type		the type (needed to get attributes from definitions)
	/// @param	group		the area the row is placed in (thead, tbody or tfoot)
	/// @param	tag			the tag for a row (tr, I don't think this will change in future, but who knows?)
	/// @param	attributes	the attributes for a row (e.g. bgcolor etc.) (this defined by the definitions.xml)
	var $defaultRow = array (
		"type" => "row",
		"group" => "tbody",
		"tag" => "tr",
		"attributes" => array("valign" => "top")
	);

	/// array that contains attributes for groups
	var $groupAttributes = array();

	/// definitions of content types
	var $contentTypes = array("text", "float");

	/// array to hold the connections from content-group to tag
	var $contentGroups = array();
	var $tagGroups = array();

	/// Colornames
	var $colorNames = array(
		"black",
		"gray",
		"maroon",
		"red",
		"green",
		"lime",
		"olive",
		"yellow",
		"navy",
		"blue",
		"purple",
		"fuchsia",
		"teal",
		"aqua",
		"silver",
		"white",
	);






	/**
	 * @defgroup constructor The constructor functions
	 * @brief	This are the routines that initializes the class.
	 */

	/**
	 * The constructor.
	 * Here we parse the tag definitions and reset the table. That means
	 * that we create a table with 2 defaultrows and two defaultcolumns.
	 * After that we extract all valid groups from the tag definitions and
	 * safe them in a classvariable.
	 * @ingroup constructor
	 *
	 * @param	definitions		The definition of the tablefields as XML
	 */
	public function __construct($definitions = "") {
		if ($definitions != "") {
			$this->loadDefinitions($definitions);
		}
	}


	/**
	 * This function initializes the table. It clears the tableArray and creates
	 * as much rows and cols as in defined in the colCount and rowCount vars.
	 * @ingroup constructor
	 */
	function resetTable() {
		// clear the table array
		$this->tableArray = array();

		// create a new row
		$row = $this->createEmptyRow();

		for ($i = 0; $i < $this->rowCount; $i++) {
			$this->tableArray[] = $row;
		}
	}


	/**
	 * This method loads the definitions from a string or a file.
	 * @ingroup constructor
	 *
	 * @param	xml		A string wich contains the definitions or a filename
	 * @return			true if success, otherwise false
	 */
	function loadDefinitions($xml) {
		// is it possible that the xml is a file?
		if (strlen($xml) <= 255) {
			// check if filexists
			if (file_exists($xml)) {
				$xml = implode("", file($xml));
			}
		}

		$tree = $this->xml2tree($xml);
		if (!$tree) return false;

		$this->tagDefinitions = $this->parseDefinitions($tree);
		$this->resetTable();

		// get all valid groups for rows
		$groups = array_keys($this->tagDefinitions["group"]);
		for ($i = 0; $i < count($this->tagDefinitions["group"]); $i++) {
			$this->groupAttributes[$groups[$i]] = array();
		}
	}









	/**
	 * @defgroup general The general functions
	 * @brief	Here we have some little get and set functions to handle the internal vars.
	 */

	/**
	 * This function returns the actual colcount
	 * @ingroup general
	 *
	 * @return	The actual number of columns in the table
	 */
	function getColCount() {
		return $this->colCount;
	}


	/**
	 * This function returns the actual colcount
	 * @ingroup general
	 *
	 * @return	The actual number of rows in the table
	 */
	function getRowCount() {
		return $this->rowCount;
	}


	/**
	 * This function returns the actual colcount
	 * @ingroup general
	 *
	 * @return	The revision of this class
	 */
	function getRevision() {
		return $this->revision;
	}


	/**
	 * This function returns the actual colcount
	 * @ingroup general
	 *
	 * @return	The actual errormessage
	 */
	function getErrorMsg() {
		return $this->errorMessage;
	}


	/**
	 * This function returns the actual colcount
	 * @ingroup general
	 *
	 * @return	The tagdefinition array
	 */
	function getTagDefinitions() {
		return $this->tagDefinitions;
	}


	/**
	 * This function exports all the data stored in this class as an
	 * serialized array.
	 * @ingroup general
	 *
	 * @return	A string containing the class-data as serialized array
	 */
	function exportData() {
		$export = array();
		$export["tableArray"] = $this->tableArray;
		$export["tagDefinitions"] = $this->tagDefinitions;
		$export["colCount"] = $this->colCount;
		$export["rowCount"] = $this->rowCount;
		$export["tableAttributes"] = $this->tableAttributes;
		$export["tableCaption"] = $this->tableCaption;
		$export["defaultCell"] = $this->defaultCell;
		$export["defaultRow"] = $this->defaultRow;
		$export["groupAttributes"] = $this->groupAttributes;
		$export["contentTypes"] = $this->contentTypes;
		$export["contentGroups"] = $this->contentGroups;
		$export["tagGroups"] = $this->tagGroups;
		return serialize($export);
	}


	/**
	 * This method imports a serialized array with class data into
	 * the class.
	 * @ingroup general
	 *
	 * @param	dataArray	the serialized data-array
	 */
	function importData($dataArray) {
		$dataArray = unserialize($dataArray);
		$this->tableArray = $dataArray["tableArray"];
		$this->tagDefinitions = $dataArray["tagDefinitions"];
		$this->colCount = $dataArray["colCount"];
		$this->rowCount = $dataArray["rowCount"];
		$this->tableAttributes = $dataArray["tableAttributes"];
		$this->tableCaption = $dataArray["tableCaption"];
		$this->defaultCell = $dataArray["defaultCell"];
		$this->defaultRow = $dataArray["defaultRow"];
		$this->groupAttributes = $dataArray["groupAttributes"];
		$this->contentTypes = $dataArray["contentTypes"];
		$this->contentGroups = $dataArray["contentGroups"];
		$this->tagGroups = $dataArray["tagGroups"];
	}












	/**
	 * @defgroup helper The helper functions
	 * @brief	The functions in this section are very important for the class.
	 *			Here we have all the validation stuff (e.g. attributevalues,
	 *			rowspans etc.) and some things for error-handling.
	 */

	/**
	 * Checks for a specific cell if the cell is overspanned by a col-
	 * or rowspan from a cell above or beside itself.
	 * This is neccessary because we have to remove the skip flag if
	 * another cell was deleted.
	 * It works very easy. At first we look for all cells left beside
	 * ourself and check if there is a colspan that will overspan us. If
	 * that is true, we set our own skip flag to true and exit.
	 * After that (no colspan found) we check all cells above ourself for
	 * rowspans. But here we also have to check if the cell with the
	 * rowpsan (if there is any) is in the same group than we are. Only if
	 * that is true, and only if the rowspan will overspan us, we have to
	 * set our skip-flag to true.
	 * @ingroup helper
	 * @private
	 *
	 * @param	col		the column
	 * @param	row		the row
	 */
	function checkSkipFlag($col, $row) {
		$this->tableArray[$row][$col]["skip"] = 0;

		// check for a colspan left beside the cell
		$offset = 2;
		for ($i = ($col -1); $i >= 0; $i--) {
			if ($this->tableArray[$row][$i]["attributes"]["colspan"] >= $offset) {
				// if a rowspan was found wich overspans this cell
				// set the flag and exit function
				$this->tableArray[$row][$col]["skip"] = 1;
				return;
			}
			$offset++;
		}
		$offset = 2;
		$group = $this->tableArray[$row]["properties"]["group"];
		for ($i = ($row -1); $i >= 0; $i--) {
			if ($this->tableArray[$i]["properties"]["group"] == $group) {
				if ($this->tableArray[$i][$col]["attributes"]["rowspan"] >= $offset) {
					// if a rowspan was found wich overspans this cell
					// set the flag and exit function
					$this->tableArray[$row][$col]["skip"] = 1;
					return;
				}
				$offset++;
			}
		}
	}


	/**
	 * This checks all cells if they need the skip-flag to be set.
	 * This not as trivial as it seems. OK, simply checking the skip-flag
	 * as described in function checkSkipFlag is not so hard. But we also
	 * have to set the skip-flag also for cells wich are not directly
	 * overspanned but wich are in a matrix of a cell wich has both spantypes
	 * set.
	 * For example:
	 * If we have the following table
	 * <pre>
	 * #########
	 * # A # B #
	 * #########
	 * # C # D #
	 * #########
	 * </pre>
	 * where cell A has colspan AND rowspan of 2. We have to set the skipflag for
	 * cell B, C AND D. That it is what the second loop do.
	 * @ingroup helper
	 */
	function checkAllCells() {
		// at first check the skip-flags
		for ($row = 0; $row < count($this->tableArray); $row++) {
			for ($col = 0; $col < (count($this->tableArray[$row]) -1); $col++) {
				$this->checkSkipFlag($col, $row);
			}
		}

		// lets check cells in a matrix of a cell wich has both types of span set
		for ($row = 0; $row < count($this->tableArray); $row++) {
			for ($col = 0; $col < (count($this->tableArray[$row]) -1); $col++) {
				$attributes = $this->tableArray[$row][$col]["attributes"];

				// also mark all cell in the col- and rowspan matrix with the skip-flag
				// if both attributes are set
				if ($attributes["colspan"] && $attributes["rowspan"]) {
					for ($x = ($col +1); $x < ($col +$attributes["colspan"]); $x++) {
						for ($y = ($row +1); $y < ($row +$attributes["rowspan"]); $y++) {
							if (is_array($this->tableArray[$y][$x])) {
								$this->tableArray[$y][$x]["skip"] = 1;
							}
						}
					}
				}
			}
		}
	}


	/**
	 * This checks all cells if it has a rowspan and if the cell is in a
	 * group with lesser rows than the colspan want's to overspan, or if
	 * the rowspan is bigger than bottom border of the group.
	 * The function corrects the values for rowspan by itself.
	 * @ingroup helper
	 *
	 */
	function checkRowspans() {
		for ($row = 0; $row < $this->rowCount; $row++) {
			for ($col = 0; $col < $this->colCount; $col++) {
				if ($this->tableArray[$row][$col]["attributes"]["rowspan"]) {
					$group = $this->tableArray[$row]["properties"]["group"];
					$value = $this->tableArray[$row][$col]["attributes"]["rowspan"];

					// count the rows in this group
					$count = 0;
					$group_index = 0;
					$rowspan = $value;

					for ($i = 0; $i < $this->rowCount; $i++) {
						$this_group = $this->tableArray[$i]["properties"]["group"];
						if ($row == $i) $group_index = $count;
						if ($this_group == $group) $count++;
					}

					if ($value > $count) $rowspan = $count;
					if (($group_index +$value) > $count) $rowspan = $count -$group_index;

					if ($rowspan > 1) {
						$this->tableArray[$row][$col]["attributes"]["rowspan"] = $rowspan;
					} else {
						$this->unsetCellAttributes($col, $row, "rowspan");
					}
				}
			}
		}
	}


	/**
	 * Checks the content of a cell if it's valid for the content type of the cell
	 * @param	col				The column of the cell
	 * @param	row				The row of the cell
	 * @param	force_ctype		if this is set, the content will be changed to ctype
	 * @return					true if content is valid for ctype or typecast was successfull, otherwise
	 */
	function checkContentType($col, $row, $force_ctype = false) {
		// $this->debug($this->tableArray);
		$coords = $this->checkCoordinates($col, $row, true);
		if (!$coords) {
			$this->errorMessage = "The cell (" .$col ."x" .$row .") does not exist";
			return false;
		}

		// get cell
		$cell = $this->tableArray[$row][$col];

		// $this->debug($cell);

		// check if the content type is allowed
		if (!in_array($cell["content_type"], $this->contentTypes)) {
			$this->errorMessage = "The content-type '" .$cell["content_type"] ."' is not allowed";
			return false;
		}

		if ($force_ctype) {
			// try to change the content to the ctype
			switch ($cell["content_type"]) {
				case "float":
					$this->tableArray[$row][$col]["content"] = floatval($this->tableArray[$row][$col]["content"]);
					break;
			}
		}

		// check if the content type is valid (only if float)
		switch ($cell["content_type"]) {
			case "float":
				$type = gettype($this->tableArray[$row][$col]["content"]);
				if (!($type == "integer") && !($type == "double")) {
					$this->errorMessage = "The value " .$cell["content"] ." is not of type " .$cell["content_type"];
					return false;
				}
				break;
		}

		return true;
	}


	/**
	 * This returns a string wich contains the last error message. You can set
	 * the attribute "print" to true if you want that the function prints out the
	 * error by itself. (The string is allread returned)
	 * @ingroup helper
	 *
	 * @param	print	set if the message should be printed or only returned
	 * @return			a string with the error message
	 */
	function getError($print = false) {
		if ($print) { $this->debug(array("Error:" => $this->getErrorMsg())); }
		return $this->errorMessage;
	}


	/**
	 * This checks the given coordinates if they are in the range of the table.
	 * If not it sets the coordinates to the maximum or minimum valid value and
	 * returns them as an array with the keys "col" and "row" wich have the
	 * corresponding values.
	 * If the attribute "return_bool" is set the function returns only a boolean
	 * value wich indicates if the submitted values are valid or not.
	 * @ingroup helper
	 *
	 * @param	col			the column that should be checked
	 * @param	row			the row that shopuld be checked
	 * @param	return_bool	if this is set, the function returns a boolean value if the coordinates are not valid
	 * @return				an array that contains the check coordinates as col and row or a boolean false
	 */
	function checkCoordinates($col, $row, $return_bool = false) {
		// define the result
		$result = array("col" => $col, "row" => $row);

		// set the valid flag
		$valid = true;

		// check the target
		if ($col > ($this->colCount -1)) { $result["col"] = ($this->colCount -1); $valid = false; }
		if ($col < 0) { $result["col"] = 0; $valid = false; }
		if ($row > ($this->rowCount -1)) { $result["row"] = ($this->rowCount -1); $valid = false; }
		if ($row < 0) { $result["row"] = 0; $valid = false; }

		// and return the new coordinates or false if option is set and coordinates are not valid
		if (!$return_bool) {
			return $result;
		} else {
			if ($valid) {
				return $result;
			} else {
				return $valid;
			}
		}
	}


	/**
	 * This is a very needful function. It checks if a value is valid for and specific attribute.
	 * It can handle different kinds of attribute-types.
	 *
	 * @ingroup helper
	 *
	 * @param	attribute_def	could be an array if set or a string wich defines the type (e.g. int)
	 * @param	value			the value the attribute should have
	 * @param	typecast		tries to change the type of the value explicite to the type it should have
	 * @return					true if the value is valid otherwise false
	 */
	function checkAttributeValue($attribute_def, $value, $typecast = false) {
		if (is_array($attribute_def)) {
			// if the attribute is an array, the type is a set
			// so the value must be in the range of the array
			if (!in_array($value, $attribute_def)) {
				$this->errorMessage = "The value " .$value ." is not valid for the set (" .implode(", ", $attribute_def) .")";
				return false;
			} else {
				return true;
			}
		}

		// the attribute should be something else
		$result = true;

		// check that there are no " in the value
		if (strpos($value, "\"")) {
			$this->errorMessage = "The value " .$value ." is not valid";
			return false;
		}

		$type= strtolower($attribute_def);

		if ($typecast) {
			switch ($type) {
				case "int":
					$value = intval($value);
					break;
				case "size":
					$rest = substr($value, -1);
					if ($rest != "%") $value = intval($value);
					break;
			}
		}


		switch ($type) {
			case "int":
				if (!is_int($value)) {
					$result = false;
					$this->errorMessage = "The value " .$value ." is not a valid integer";
				}
				break;
			case "string":
				if (!is_string($value)) {
					$result = false;
					$this->errorMessage = "The value " .$value ." is not a valid string";
				}
				break;
			case "color":
				if (in_array(strtolower($value), $this->colorNames)) break;
				if (!preg_match("/^#([A-F|0-9]{6})$/is", $value)) {
					$result = false;
					$this->errorMessage = "The value " .$value ." is not a valid color";
				}
				break;
			case "size":
				if (!is_int($value)) {
					$rest = substr($value, -1);
					$value = substr($value, 0, (strlen($value) -1));
					if (!is_int($value) && $rest != "%") {
						$result = false;
						$this->errorMessage = "The value " .$value .$rest ." is not a valid size";
					}
				}
				break;
			case "empty":
				if (strlen($value) > 0) {
					$result = false;
					$this->errorMessage = "The value " .$value ." is not empty";
				}
				break;
			default:
				$result = false;
				$this->errorMessage = "The type " .$type ." is not valid";
				break;
		}

		return $result;
	}













	/**
	 * @defgroup debug The debug functions
	 */

	/**
	 * function just for debugging. It prints an array as a table
	 * @ingroup debug
	 *
	 * @param	array_in	the array that should be displayed
	 * @return				a html-table that represents the submitted array
	 */
	function viewArray($array_in) {
		if (is_array($array_in)) {
			$result = "<table border=\"1\" cellpadding=\"1\" cellspacing=\"0\" bgcolor=\"white\">";
			if (!count($array_in)) {
				$result .= "<tr><td><font face=\"Verdana, Arial\" size=\"1\"><b>" .HTMLSpecialChars("EMPTY!") ."</b></font></td></tr>";
			}
			while (list($key, $val) = each($array_in)) {
				$result .= "<tr><td><font face=\"Verdana,Arial\" size=\"1\">" .HTMLSpecialChars((string)$key) ."</font></td><td>";
				if (is_array($array_in[$key])) {
					$result .= $this->viewArray($array_in[$key]);
				} else
					$result .= "<font face=\"Verdana,Arial\" size=\"1\" color=\"red\">" .nl2br(HTMLSpecialChars((string)$val)) ."<br /></font>";
				$result .= "</td></tr>";
			}
			$result .= "</table>";
		} else {
			return false;
		}
		return $result;
	}


	/**
	 * Just for debugging. Calls viewArray and prints out the result
	 * @ingroup debug
	 *
	 * @param	input	the array that shuld be displayed
	 */
	function debug($input) {
		echo $this->viewArray($input);
	}











	/**
	 * @defgroup default The defaulthandling
	 */

	/**
	 * This function sets the deafult cell
	 * @ingroup default
	 *
	 * @param	attributes	an array with the attributes for the default cell
	 * @param	tag			the tag for the default cell (can be empty)
	 * @param	ctype		the contenttype for the defaultcell (can be empty)
	 */
	function setDefaultCell($attributes, $tag = NULL, $ctype = NULL) {
		if ($tag == NULL) $tag = $this->defaultCell["tag"];
		if ($ctype == NULL) $ctype = $this->defaultCell["content_type"];

		$definitions = $this->tagDefinitions["cell"][$tag];
		$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

		$this->defaultCell["tag"] = $tag;
		$this->defaultCell["content_type"] = $ctype;

		// insert the attributes
		foreach ($attributes as $name => $value) {
			if ($this->checkAttributeValue($definitions[$name], $value)) {
				$this->defaultCell["attributes"][$name] = $value;
			}
		}
	}


	/**
	 * This function returns the default cell.
	 * @ingroup default
	 *
	 * @return	An array wich contains the default cell
	 */
	function getDefaultCell() {
		return $this->defaultCell;
	}


	/**
	 * This function sets the default row
	 * @ingroup default
	 *
	 * @param	attributes	an array with the attributes for the default row
	 * @param	group		the group of the default row (default = tbody)
	 * @param	tag			the tag for a row (default = tr)
	 */
	function setDefaultRow($attributes, $tag = NULL, $group = NULL) {
		if ($tag == NULL) $tag = $this->defaultRow["tag"];
		if ($group == NULL) $group = $this->defaultRow["group"];

		$definitions = $this->tagDefinitions["row"][$tag];
		$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

		$this->defaultRow["tag"] = $tag;
		$this->defaultRow["group"] = $group;

		// insert the attributes
		foreach ($attributes as $name => $value) {
			if ($this->checkAttributeValue($definitions[$name], $value)) {
				$this->defaultRow["attributes"][$name] = $value;
			}
		}
	}


	/**
	 * This function returns the default row.
	 * @ingroup default
	 *
	 * @return	An array wich contains the default row.
	 */
	function getDefaultRow() {
		// $this->debug($this->defaultRow);
		return $this->defaultRow;
	}










	/**
	 * @defgroup table The table functions
	 * @brief	All functions in this section are for editing the table itself.
	 *			At this time it's only editing the attributes
	 */

	 /**
	 * Sets a attributes to the table and checks if it is valid
	 * @ingroup table
	 *
	 * @param	attribute	the name of the attribute
	 * @param	value		the value of the attribute
	 * @param	force_type	If true the value will be changed to the type it should have
	 *
	 * @return				True if success, otherwise false
	 */
	function setTableAttribute($attribute, $value, $force_type = false) {
		$definitions = $this->tagDefinitions["parent"]["table"];
		$definitions["header_repeat"] = "int";
		$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

		// check if the attribute is in the definition array
		if (!isset($definitions[$attribute])) {
			$this->errorMessage = "The attribute " .$attribute ." is not allowed for a table";
			return false;
		}

		// check if the value is valid for this type of attribute
		if (!$this->checkAttributeValue($definitions[$attribute], $value, $force_type)) return false;

		// insert / update the attribute
		$this->tableAttributes[$attribute] = $value;

		return true;
	}


	/**
	 * This function returns the value of a table attribute. It checks if the attribute
	 * is defined and valid. If not, it returns false or NULL.
	 * @ingroup table
	 *
	 * @param	attribute	The attribute you want to get
	 * @return				The value of the attribute or false if it is not valid
	 */
	function getTableAttribute($attribute) {
		$definitions = $this->tagDefinitions["parent"]["table"];
		$definitions["header_repeat"] = "int";
		$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

		// check that the attribute is valid for tables
		if (!isset($definitions[$attribute])) {
			$this->errorMessage = "The attribute " .$attribute ." is not known for tables";
			return false;
		}

		// return null if the attribute is not set
		if (!isset($this->tableAttributes[$attribute])) return NULL;

		// else return the value
		return $this->tableAttributes[$attribute];
	}


	/**
	 * This method set's the caption for the table.
	 * @ingroup table
	 *
	 * @param	caption		The caption value (string)
	 * @return				True if success, otherwise false
	 */
	function setTableCaption($caption) {
		$this->tableCaption["caption"] = $caption;
	}


	/**
	 * This returns the caption for this table.
	 * @ingroup table
	 *
	 * @return	The caption
	 */
	function getTableCaption() {
		return $this->tableCaption["caption"];
	}


	/**
	 * This method set's an attribute for the caption of the table.
	 * @ingroup table
	 *
	 * @param	attribute	The caption value (string)
	 * @param	value		The value for the caption
	 * @param	force_type	If true the value will be changed to the type it should have
	 * @return				True if success, otherwise false
	 */
	function setTableCaptionAttribute($attribute, $value, $force_type = false) {
		$definitions = $this->tagDefinitions["table"]["caption"];

		// check if the attribute is in the definition array
		if (!isset($definitions[$attribute])) {
			$this->errorMessage = "The attribute " .$attribute ." is not allowed for a caption";
			return false;
		}

		// check if the value is valid for this type of attribute
		if (!$this->checkAttributeValue($definitions[$attribute], $value, $force_type)) return false;

		// insert / update the attribute
		$this->tableCaption["attributes"][$attribute] = $value;

		return true;
	}


	/**
	 * deletes a table attribute
	 * @ingroup table
	 *
	 * @param	attribute	the attribute that should be deleted
	 */
	function unsetTableAttribute($attribute) {
		unset($this->tableAttributes[$attribute]);
	}












	/**
	 * @defgroup groups The group functions
	 */

	/**
	 * check if a group is valid
	 * @ingroup groups
	 *
	 * @param	group	the groupname to check
	 * @return			true if the group is valid, otherwise false
	 */
	function checkGroup($group) {
		// check if the group is valid
		if (is_array($this->tagDefinitions["group"][$group])) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * sets a attributes to a group
	 * @ingroup groups
	 *
	 * @param	group		the group where we should set the attribute
	 * @param	attribute	the attribute that should be setted
	 * @param	value		the value the attribute should have
	 * @return				true if success, otherwise false
	 */
	function setGroupAttribute($group, $attribute, $value) {
		if (!$this->checkGroup($group)) {
			$this->errorMessage = "The group " .$group ." is not valid";
			return false;
		}

		$definitions = $this->tagDefinitions["group"][$group];
		$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

		// check if the attribute is in the definition array
		if (!isset($definitions[$attribute])) {
			$this->errorMessage = "The attribute " .$attribute ." is not allowed for group " .$group;
			return false;
		}

		// check if the value is valid for this type of attribute
		if (!$this->checkAttributeValue($definitions[$attribute], $value)) return false;

		$this->groupAttributes[$group][$attribute] = $value;

		$this->checkRowspans();

		return true;
	}


	/**
	 * This function returns the value of an attribute for a group. It checks if the
	 * group and attribute is valid for this group and returns false if not. If the
	 * attribute is not set it returns NULL otherwise the value.
	 * @ingroup groups
	 *
	 * @param	group		the group from wich we want to get the attributevalue
	 * @param	attribute	the attribute we want to get the value from
	 * @return				false if attribute or group is not valid, null if not set or value
	 */
	function getGroupAttribute($group, $attribute) {
		// check group
		if (!$this->checkGroup($group)) {
			$this->errorMessage = "The group " .$group ." is not valid";
			return false;
		}

		// get definitions
		$definitions = $this->tagDefinitions["group"][$group];
		$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

		// check if the attribute is in the definition array
		if (!isset($definitions[$attribute])) {
			$this->errorMessage = "The attribute " .$attribute ." is not known for group " .$group;
			return false;
		}

		// return null if the attribute is not set
		if (!isset($this->groupAttributes[$group][$attribute])) return NULL;

		// else return the value
		return $this->groupAttributes[$group][$attribute];
	}


	/**
	 * deletes a attributes from a group
	 * @ingroup groups
	 *
	 * @param	group		the group where we should set the attribute
	 * @param	attribute	the attribute that should be setted
	 * @param	value		the value of the attribute
	 * @return				true if success, otherwise false
	 */
	function unsetGroupAttribute($group, $attribute, $value) {
		if (!$this->checkGroup($group)) {
			$this->errorMessage = "The group " .$group ." is not valid";
			return false;
		}

		unset($this->groupAttributes[$group][$attribute]);

		return true;
	}












	/**
	 * @defgroup rows The row functions
	 */

	/**
	 * adds an attribute to a specific row
	 * this function checks if the attribute and the value is valid, through
	 * the tag definitions
	 * @ingroup rows
	 *
	 * @param	row			The row
	 * @param	attribute	The name of the attribute
	 * @param	value		The value of the attribute
	 * @return				true if success and false if not
	 */
	function setRowAttribute($row, $attribute, $value) {
		$definitions = $this->tagDefinitions["row"]["tr"];
		$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

		// Check if the attribute is in the definition
		// and cell should not be skipped
		if (!isset($definitions[$attribute])) {
			$this->errorMessage = "The attribute " .$attribute ." is not allowed for a row";
			return false;
		}

		// check if the value is valid for this type of attribute
		if (!$this->checkAttributeValue($definitions[$attribute], $value)) return false;

		$this->tableArray[$row]["properties"]["attributes"][$attribute] = $value;

		return true;
	}


	/**
	 * This returns the attributes from a cell. It check if the cell and the attribute is
	 * valid and retruns false if not. If trie it returns null if the attribute is not set
	 * otherwise it returns the value.
	 * @ingroup rows
	 *
	 * @param	row			The row of the cell
	 * @param	attribute	The attribute we want the value from
	 * @return				false if cell or attribute not valid, null if not set or value if all checks passed
	 */
	function getRowAttribute($row, $attribute) {
		// check coords
		$coords = $this->checkCoordinates(0, $row, true);

		if (!$coords) {
			$this->errorMessage = "The row (" .$row .") does not exist";
			return false;
		}

		$definitions = $this->tagDefinitions["row"]["tr"];
		$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

		// check if the attribute is in the definition array
		if (!isset($definitions[$attribute])) {
			$this->errorMessage = "The attribute " .$attribute ." is not known for rows";
			return false;
		}

		// return null if the attribute is not set
		if (!isset($this->tableArray[$row]["properties"]["attributes"][$attribute])) return NULL;

		// else return the value
		return $this->tableArray[$row]["properties"]["attributes"][$attribute];
	}


	/**
	 * adds an attribute to a specific row
	 * this function checks if the attribute and the value is valid, through
	 * the tag definitions
	 * @ingroup rows
	 *
	 * @param	row			The row
	 * @param	attributes	an array with attributes (keys = names)
	 * @return				true if success and false if not
	 */
	function setRowAttributes($row, $attributes) {
		if (is_array($attributes)) {
			foreach ($attributes as $name => $value) {
				if (!$this->setRowAttribute($row, $name, $value)) return false;
			}
		} else {
			$this->errorMessage = "The submitted attributes is not an array";
			return false;
		}

		return true;
	}


	/**
	 * This returns all attributes from a row. It check if the cell exists and returns false if not.
	 * @ingroup rows
	 *
	 * @param	row			The index of the row
	 * @return				false if row does not exist, otherwise array with attributes
	 */
	function getRowAttributes($row) {
		// check coords
		$coords = $this->checkCoordinates(0, $row, true);

		if (!$coords) {
			$this->errorMessage = "The row (" .$row .") does not exist";
			return false;
		}

		// else return the value
		return $this->tableArray[$row]["properties"]["attributes"];
	}


	/**
	 * deletes attributes from a row
	 * @ingroup rows
	 *
	 * @param	row			The row
	 * @param	attributes	the name of the attribute (or array with attributes)
	 */
	function unsetRowAttributes($row, $attributes) {
		$coords = $this->checkCoordinates(0, $row);
		if (!$coords) {
			$this->errorMessage = "The row " .$row ." does not exist";
			return false;
		}

		if (is_array($attributes)) {
			foreach ($attributes as $attribute) {
				$this->unsetRowAttribute($row, $attribute);
			}
		}
	}


	/**
	 * deletes a row attribute
	 * @ingroup rows
	 *
	 * @param	row			the row where we delete the attribute
	 * @param	attribute	the attribute that should be deleted
	 */
	function unsetRowAttribute($row, $attribute) {
		$coords = $this->checkCoordinates(0, $row, true);

		if (!$coords) {
			$this->errorMessage = "The row " .$row ." does not exist";
			return false;
		}

		unset($this->tableArray[$row]["properties"]["attributes"][$attribute]);
	}


	/**
	 * Edits the group-flag of a row
	 * @ingroup rows
	 *
	 * @param	row		the row that should be edited
	 * @param	group	the area the row should get
	 * @return			true if success, otherwise false
	 */
	function setRowGroup($row, $group) {
		$coords = $this->checkCoordinates(0, $row, true);

		if (!$coords) {
			$this->errorMessage = "The row " .$row ." does not exist";
			return false;
		}

		if (!$this->checkGroup($group)) {
			$this->errorMessage = "The group " .$group ." is not valid";
			return false;
		}

		$this->tableArray[$coords["row"]]["properties"]["group"] = $group;
		$this->checkRowspans();

		return true;
	}


	/**
	 * Returns the group of a row. Checks if the row exist.
	 * @ingroup rows
	 *
	 * @param	row		the row that should be edited
	 * @return			false if row does not exist, otherwise the group
	 */
	function getRowGroup($row) {
		$coords = $this->checkCoordinates(0, $row, true);

		if (!$coords) {
			$this->errorMessage = "The row " .$row ." does not exist";
			return false;
		}

		return $this->tableArray[$coords["row"]]["properties"]["group"];
	}


	/**
	 * adding a row to the table
	 * @ingroup rows
	 *
	 * @param	index	The row after wich the new row should be inserted
	 *					-1 (default) means the new row will be inserted at the end
	 * @param	count	number of rows that should be inserted
	 */
	function insertRows($index = -1, $count = 1) {
		// increase the rowcount
		$this->rowCount += $count;

		// get an empty new row
		$new_row = $this->createEmptyRow();

		// $this->debug($this->tableArray);

		for ($i = 0; $i < $count; $i++) {
			if ($index == 0) {
				// insert at the beginning
				array_unshift($this->tableArray, $new_row);
			} elseif ($index == -1) {
				// insert add the end
				$this->tableArray[] = $new_row;
			} else {
				// insert anywhere between
				// get the part before and after insertion
				$before = array_slice($this->tableArray, 0, $index);
				$after = array_slice($this->tableArray, $index);

				// build the new array
				$new = array();
				foreach ($before as $row) {
					$new[] = $row;
				}
				$new[] = $new_row;
				foreach ($after as $row) {
					$new[] = $row;
				}

				// set it
				$this->tableArray = $new;
			}
		}

		// check all cells for skip-flag
		$this->checkAllCells();
	}


	/**
	 * moves a complete row up or down
	 * if the target is outside of the table, the row is moved until the border
	 * @ingroup rows
	 *
	 * @param	index	the row that should be moved
	 * @param	offset	the offset where the row should be moved to
	 *					negative values moves the row up, positive values moves down
	 */
	function moveRow($index = 0, $offset = 0) {
		// get the target
		$target = $this->checkCoordinates(0, ($index +$offset));

		$target_row = $this->tableArray[$target["row"]];
		$this->tableArray[$target["row"]] = $this->tableArray[$index];
		$this->tableArray[$index] = $target_row;

		// check all cells for skip-flag
		$this->checkAllCells();
	}


	/**
	 * delete rows identified by index and count
	 * @ingroup rows
	 *
	 * @param	index	the index of the row that should be deleted
	 * @param	count	the number of rows that should be deleted
	 * @return			true if success, otherwise false
	 */
	function deleteRows($index, $count = 1) {
		if ($index > ($this->rowCount -1)) {
			$this->errorMessage = "The row does not exist";
			return false;
		}

		// decrease the row count
		$this->rowCount -= $count;

		// build an array with all indexes that should be deleted
		$index_array = array();
		for ($i = 0; $i < $count; $i++) {
			$index_array[] = $index +$i;
		}

		// delete the rows
		$new = array();
		for ($i = 0; $i < count($this->tableArray); $i++) {
			if (!in_array($i, $index_array)) {
				$new[] = $this->tableArray[$i];
			}
		}

		// set and return
		$this->tableArray = $new;

		// check all cells for skip-flag
		$this->checkAllCells();

		return true;
	}


	/**
	 * This function creates an empty row. With so much columns as the
	 * the value of colCount actually is.
	 * @ingroup rows
	 */
	function createEmptyRow() {
		// create the new row
		$new_row = array("properties" => $this->getDefaultRow());
		for ($i = 0; $i < $this->colCount; $i++) {
			$new_row[] = $this->createEmptyCell();
		}

		return $new_row;
	}













	 /**
	  * @defgroup cols The column functions
	  */

	/**
	 * adding a column to the table
	 * @ingroup cols
	 *
	 * @param	index		The col after wich the new col should be inserted
	 *						-1 (default) means the new col will be inserted at the end
	 * @param	count		The number of cols that should be inserted
	 */
	function insertCols($index = -1, $count = 1) {
		$this->colCount += $count;

		for ($i = 0; $i < count($this->tableArray); $i++) {
			$this->tableArray[$i] = $this->insertCells($this->tableArray[$i], $index, $count);
		}

		// check all cells for skip-flag
		$this->checkAllCells();
	}


	/**
	 * moves a column left or right
	 * if the column is going to leave the table, it will moved until
	 * the tableborder
	 * @ingroup cols
	 *
	 * @param	index		the col that should be moved
	 * @param	offset		the offset where the column is moved to
	 *						negative values move the column left
	 *						positive values move the column right
	 */
	function moveCol($index = 0, $offset = 0) {
		// get the target
		$target = $this->checkCoordinates(($index +$offset), 0);

		// cycle trough the rows
		for ($row = 0; $row < $this->rowCount; $row++) {
			// move the col (simply moving the cell at the index in every row)
			$target_cell = $this->tableArray[$row][$target["col"]];
			$this->tableArray[$row][$target["col"]] = $this->tableArray[$row][$index];
			$this->tableArray[$row][$index] = $target_cell;
		}

		// check all cells for skip-flag
		$this->checkAllCells();
	}


	/**
	 * deletes cols identified by index and count
	 * @ingroup cols
	 *
	 * @param	index	the index of the col that should be deleted
	 * @param	count	the count of cols that should be deleted
	 * @return			true if success, otherwise false
	 */
	function deleteCols($index, $count = 1) {
		if ($index > ($this->colCount -1)) {
			$this->errorMessage = "The column does not exist";
			return false;
		}

		// decrease the col count
		$this->colCount -= $count;

		for ($i = 0; $i < count($this->tableArray); $i++) {
			$this->tableArray[$i] = $this->deleteCells($this->tableArray[$i], $index, $count);
		}

		// check all cells for skip-flag
		$this->checkAllCells();
	}













	 /**
	  * @defgroup cells The cell functions
	  */

	 /**
	 * adds an attribute to a specific cell located by col and row index
	 * this function checks if the attribute and the value is valid, through
	 * the tag definitions
	 * @ingroup cells
	 *
	 * @param	col			The column of the cell
	 * @param	row			The row of the cell
	 * @param	attribute	The name of the attribute
	 * @param	value		The value of the attribute
	 * @param	check		If setted, the check routine is called after setting the attribute
	 * @param	force_type	If true the value will be changed to the type it should have
	 * @return				true if success and false if not
	 */
	function setCellAttribute($col, $row, $attribute, $value, $check = true, $force_type = false) {
		// check coords with bool return values if not valid
		$coords = $this->checkCoordinates($col, $row, true);

		if (!$coords) {
			$this->errorMessage = "The cell (" .$col ."x" .$row .") does not exist";
			return false;
		}

		$cell = $this->tableArray[$row][$col];
		$definitions = $this->tagDefinitions[$cell["type"]][$cell["tag"]];
		$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

		// Check if the attribute is in the definition
		// and cell should not be skipped
		if (!isset($definitions[$attribute])) {
			$this->errorMessage = "The attribute " .$attribute ." is not allowed for " .$cell["tag"];
			return false;
		}

		// check if the value is valid for this type of attribute
		if (!$this->checkAttributeValue($definitions[$attribute], $value, $force_type)) return false;


		// insert the attribute to the cell
		$cell["attributes"][$attribute] = $value;

		// write it back to the table array
		$this->tableArray[$row][$col] = $cell;

		if ($attribute == "rowspan") $this->checkRowspans();
		// $this->debug($this->tableArray);

		// check all cells for skip-flag
		if ($check) $this->checkAllCells();

		return true;
	}


	/**
	 * This returns the attributes from a cell. It check if the cell and the attribute is
	 * valid and retruns false if not. If trie it returns null if the attribute is not set
	 * otherwise it returns the value.
	 * @ingroup cells
	 *
	 * @param	col			The column of teh cell
	 * @param	row			The row of the cell
	 * @param	attribute	The attribute we want the value from
	 * @return				false if cell or attribute not valid, null if not set or value if all checks passed
	 */
	function getCellAttribute($col, $row, $attribute) {
		// check coords
		$coords = $this->checkCoordinates($col, $row, true);

		if (!$coords) {
			$this->errorMessage = "The cell (" .$col ."x" .$row .") does not exist";
			return false;
		}

		$cell = $this->tableArray[$row][$col];
		$definitions = $this->tagDefinitions[$cell["type"]][$cell["tag"]];
		$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

		// check if the attribute is in the definition array
		if (!isset($definitions[$attribute])) {
			$this->errorMessage = "The attribute " .$attribute ." is not known for cells";
			return false;
		}

		// return null if the attribute is not set
		if (!isset($cell["attributes"][$attribute])) return NULL;

		// else return the value
		return $cell["attributes"][$attribute];
	}


	/**
	 * adds a set of attributes to a cell
	 * @ingroup cells
	 *
	 * @param	col			The column of the cell
	 * @param	row			The row of the cell
	 * @param	attributes	an array with the attributes (keys are the name)
	 * @return				true if success and false if not
	 */
	function setCellAttributes($col, $row, $attributes) {
		// get the names
		$attribute_names = array_keys($attributes);

		for ($i = 0; $i < count($attributes); $i++) {
			// exit if a attribute can't be setted
			if (!$this->setCellAttribute($col, $row, $attribute_names[$i], $attributes[$attribute_names[$i]], false)) {
				return false;
			}
		}

		// check all cells for skip-flag
		$this->checkAllCells();

		return true;
	}


	/**
	 * This returns all attributes from a cell. It check if the cell exists and returns false if not.
	 * @ingroup cells
	 *
	 * @param	col			The column of the cell
	 * @param	row			The row of the cell
	 * @return				false if cell does not exist, otherwise array with attributes
	 */
	function getCellAttributes($col, $row) {
		// check coords
		$coords = $this->checkCoordinates($col, $row, true);

		if (!$coords) {
			$this->errorMessage = "The cell (" .$col ."x" .$row .") does not exist";
			return false;
		}

		// else return the value
		return $this->tableArray[$row][$col]["attributes"];
	}


	/**
	 * adds a set of attributes to a range of cells
	 * @ingroup cells
	 *
	 * @param	tl_col		the column of the top left cell
	 * @param	tl_row		the row of the top left cell
	 * @param	br_col		the column of the bottom right cell
	 * @param	br_row		the row of the bottom right cell
	 * @param	attributes	an array with the attributes (keys are the name)
	 * @return				true if success and false if not
	 */
	function setCellAttributesInRange($tl_col, $tl_row, $br_col, $br_row, $attributes) {
		$tl_coords = $this->checkCoordinates($tl_col, $tl_row);
		$br_coords = $this->checkCoordinates($br_col, $br_row);

		for ($col = $tl_coords["col"]; $col <= $br_coords["col"]; $col++) {
			for ($row = $tl_coords["row"]; $row <= $br_coords["row"]; $row++) {
				if (!$this->setCellAttributes($col, $row, $attributes, false)) return false;
			}
		}

		return true;
	}


	/**
	 * deletes an attribute from a cell located by col and row
	 * @ingroup cells
	 *
	 * @param	col			The column of the cell
	 * @param	row			The row of the cell
	 * @param	attributes	the name of the attribute (or array with attributes)
	 * @param	check		if this is checked the checkAllCells routine is called after unsetting
	 */
	function unsetCellAttributes($col, $row, $attributes, $check = false) {
		$coords = $this->checkCoordinates($col, $row);
		if (!$coords) {
			$this->errorMessage = "The cell at " .$col ."x" .$row ." does not exist";
			return false;
		}

		if (is_array($attributes)) {
			foreach ($attributes as $attribute) {
				unset($this->tableArray[$coords["row"]][$coords["col"]]["attributes"][$attribute]);
			}
		} else {
			unset($this->tableArray[$coords["row"]][$coords["col"]]["attributes"][$attributes]);
		}

		// check all cells for skip-flag
		if ($check) $this->checkAllCells();

		return true;
	}


	/**
	 * deletes some attributes given in an array from all cells in the given range
	 * @ingroup cells
	 *
	 * @param	tl_col		the column of the top left cell
	 * @param	tl_row		the row of the top left cell
	 * @param	br_col		the column of the bottom right cell
	 * @param	br_row		the row of the bottom right cell
	 * @param	attributes	an array or a string (if only 1 attribute should be unsetted) with the attribute names that should be removed
	 */
	function unsetCellAttributesInRange($tl_col, $tl_row, $br_col, $br_row, $attributes) {
		$tl_coords = $this->checkCoordinates($tl_col, $tl_row);
		$br_coords = $this->checkCoordinates($br_col, $br_row);

		for ($col = $tl_coords["col"]; $col <= $br_coords["col"]; $col++) {
			for ($row = $tl_coords["row"]; $row <= $br_coords["row"]; $row++) {
				$this->unsetCellAttributes($col, $row, $attributes, false);
			}
		}

		// check all cells for skip-flag
		$this->checkAllCells();
	}


	/**
	 * sets the content of a cell
	 * @ingroup cells
	 *
	 * @param	col				the column of the cell
	 * @param	row				the row of the cell
	 * @param	content			the content the cell should have
	 * @param	change_ctype	If this is set, the content_type will be changed to the content the cell should get
	 * @return					true if success, otherwise false
	 */
	function setCellContent($col, $row, $content, $change_ctype = false) {
		$coords = $this->checkCoordinates($col, $row, true);
		if (!$coords) {
			$this->errorMessage = "The cell at " .$col ."x" .$row ." does not exist";
			return false;
		}

		// change the content type if needed and forced
		$type = gettype($content);
		if ($change_ctype && ($type == "integer" || $type == "double")) {
			$this->setContentType($col, $row, "float");
		}

		// set and check the content
		$old_content = $this->tableArray[$row][$col]["content"];
		$this->tableArray[$row][$col]["content"] = $content;

		if (!$this->checkContentType($col, $row)) {
			$this->tableArray[$row][$col]["content"] = $old_content;
			return false;
		}

		return true;
	}


	/**
	 * Returns the content of a cell. If the cell does not exist it returns false.
	 * @ingroup cells
	 *
	 * @param	col		the column of the cell
	 * @param	row		the row of the cell
	 * @return			false if cell does not exist, else the content
	 */
	function getCellContent($col, $row) {
		$coords = $this->checkCoordinates($col, $row, true);
		if (!$coords) {
			$this->errorMessage = "The cell at " .$col ."x" .$row ." does not exist";
			return false;
		}

		return $this->tableArray[$row][$col]["content"];
	}


	/**
	 * This method defines the content type of a cell
	 * @ingroup cells
	 *
	 * @param	col		the column of the cell
	 * @param	row		the row of the cell
	 * @param	ctype	The content type the cell should get
	 * @return			true if success, otherwise false
	 */
	function setContentType($col, $row, $ctype) {
		$coords = $this->checkCoordinates($col, $row, true);
		if (!$coords) {
			$this->errorMessage = "The cell at " .$col ."x" .$row ." does not exist";
			return false;
		}

		if (!in_array($ctype, $this->contentTypes)) {
			$this->errorMessage = "The content type " .$ctype ." is not allowed";
			return false;
		}

		$old_type = $this->tableArray[$row][$col]["content_type"];
		$this->tableArray[$row][$col]["content_type"] = $ctype;

		if (!$this->checkContentType($col, $row, true)) {
			$this->tableArray[$row][$col]["content_type"] = $old_type;
			return false;
		}
		return true;
	}


	/**
	 * This method sets the content-type of cells in a range.
	 * @ingroup cells
	 *
	 * @param	tl_col		the column of the top left cell
	 * @param	tl_row		the row of the top left cell
	 * @param	br_col		the column of the bottom right cell
	 * @param	br_row		the row of the bottom right cell
	 * @param	ctype		The content-type the cells should get
	 * @return			true if success, otherwise false
	 */
	function setContentTypeInRange($tl_col, $tl_row, $br_col, $br_row, $ctype) {
		$tl_coords = $this->checkCoordinates($tl_col, $tl_row);
		$br_coords = $this->checkCoordinates($br_col, $br_row);


		for ($col = $tl_coords["col"]; $col <= $br_coords["col"]; $col++) {
			for ($row = $tl_coords["row"]; $row <= $br_coords["row"]; $row++) {
				if (!$this->setContentType($col, $row, $ctype)) return false;
			}
		}

		return true;
	}


	/**
	 * This method returns the content-type of a cell
	 * @ingroup cells
	 *
	 * @param	col		the column of the cell
	 * @param	row		the row of the cell
	 * @return			The contenttype or false if the cell coes not exist
	 */
	function getContentType($col, $row) {
		$coords = $this->checkCoordinates($col, $row, true);
		if (!$coords) {
			$this->errorMessage = "The cell at " .$col ."x" .$row ." does not exist";
			return false;
		}

		return $this->tableArray[$row][$col]["content_type"];
	}


	/**
	 * moves a cell identified by col and row by x-offset and y-offset
	 * if the target is out of the tablebound, the offsets will be setted
	 * to the outer border
	 * @ingroup cells
	 *
	 * @param	col			the col of the cell that should be moved
	 * @param	row			the row of the cell that should be moved
	 * @param	x_offset	the offset where the cell should be moved in horizontal direction
	 * @param	y_offset	the offset where the cell should be moved in vertical direction
	 */
	function moveCell($col = 0, $row = 0, $x_offset = 0, $y_offset = 0) {
		// get the target
		$target_x = $col +$x_offset;
		$target_y = $row +$y_offset;

		$target = $this->checkCoordinates($target_x, $target_y);

		// get the target cell
		$target_cell = $this->tableArray[$target["row"]][$target["col"]];

		// flip cell and target
		$this->tableArray[$target["row"]][$target["col"]] = $this->tableArray[$row][$col];
		$this->tableArray[$row][$col] = $target_cell;

		// check all cells for skip-flag
		$this->checkAllCells();
	}


	/**
	 * adds cell to a row
	 * @ingroup cells
	 *
	 * @param	row		The row array
	 * @param	index	The index where the cells should be inserted
	 * @param	count	The number of cells that should be inserted
	 *
	 * @return			and array with the new row
	 */
	function insertCells($row, $index = -1, $count = 1) {
		if ($index == -1) {
			// add at the end
			for ($i = 0; $i < $count; $i++) {
				$row[] = $this->createEmptyCell();
			}
			return $row;
		} else {
			$properties = $row["properties"];
			unset($row["properties"]);
			$before = array_slice($row, 0, $index);
			$after = array_slice($row, $index);

			// build the new array
			$new_row = array("properties" => $properties);
			foreach ($before as $cell) {
				$new_row[] = $cell;
			}
			for ($i = 0; $i < $count; $i++) {
				$new_row[] = $this->createEmptyCell();
			}
			foreach ($after as $cell) {
				$new_row[] = $cell;
			}

			return $new_row;
		}
	}


	/**
	 * delete a number of cells from a row string at index
	 * @ingroup cells
	 *
	 * @param	row		the row from wich the cells should be deleted
	 * @param	index	the index where the deletion starts
	 * @param	count	the number of cells that should be deleted
	 * @return			an array with the new row without some cells
	 */
	function deleteCells($row, $index, $count) {
		// build an array with all indexes that should be deleted
		$index_array = array();
		for ($i = 0; $i < $count; $i++) {
			$index_array[] = $index +$i;
		}

		$new_row = array("properties" => $row["properties"]);
		unset($row["properties"]);
		for ($i = 0; $i < count($row); $i++) {
			if (!in_array($i, $index_array)) {
				$new_row[] = $row[$i];
			}
		}

		return $new_row;
	}


	/**
	 * sets the celltype after checking that the type is valid.
	 * @ingroup cells
	 *
	 * @param	col		The column of the cell
	 * @param	row		The row of the cell
	 * @param	type	the type the cell should have
	 * @return			true if success otherwise false
	 */
	function setCellType($col, $row, $type) {
		$coords = $this->checkCoordinates($col, $row);
		if (!$coords) {
			$this->errorMessage = "The cell at " .$col ."x" .$row ." does not exist";
			return false;
		}

		$types = $this->tagDefinitions["cell"][$type];
		if (is_array($types)) {
			$this->tableArray[$coords["row"]][$coords["col"]]["tag"] = $type;
			return true;
		} else {
			$this->errorMessage = 'The type "' .$type .'" is not allowed';
			return false;
		}
	}


	/**
	 * Returns the celltype of a cell.
	 * @ingroup cells
	 *
	 * @param	col		The column of the cell
	 * @param	row		The row of the cell
	 * @return			false if cell does not exist, else the content
	 */
	function getCellType($col, $row) {
		$coords = $this->checkCoordinates($col, $row, true);
		if (!$coords) {
			$this->errorMessage = "The cell at " .$col ."x" .$row ." does not exist";
			return false;
		}

		return $this->tableArray[$row][$col]["tag"];
	}


	/**
	 * sets the celltype for a range of cells
	 * @ingroup cells
	 *
	 * @param	tl_col		the column of the top left cell
	 * @param	tl_row		the row of the top left cell
	 * @param	br_col		the column of the bottom right cell
	 * @param	br_row		the row of the bottom right cell
	 * @param	type		the type teh cells should get
	 */
	function setCellTypeInRange($tl_col, $tl_row, $br_col, $br_row, $type) {
		$tl_coords = $this->checkCoordinates($tl_col, $tl_row);
		$br_coords = $this->checkCoordinates($br_col, $br_row);

		for ($col = $tl_coords["col"]; $col <= $br_coords["col"]; $col++) {
			for ($row = $tl_coords["row"]; $row <= $br_coords["row"]; $row++) {
				if (!$this->setCellType($col, $row, $type)) {
					return false;
				}
			}
		}

		return true;
	}


	/**
	 * returns an empty cell as defined
	 * @ingroup cells
	 *
	 * @param	cell	an array with a celldefition if empty the defaultEmptyCell is used
	 * @return			the submitted cell or the default cell if cell is not set
	 */
	function createEmptyCell($cell = NULL) {
		if ($cell == NULL) $cell = $this->getDefaultCell();
		// $this->debug($cell);
		return $cell;
	}













	/**
	 * @defgroup output The output routines
	 */

	/**
	 * return the HTML from the table array
	 * @ingroup output
	 *
	 * @return	a string with the XHTML-code
	 */
	function getXHTML() {
		$result = '';
		$index = 1;
		$row_number = 0;
		// add the table tag
		$tableAttributes = $this->tableAttributes;
		unset($tableAttributes["header_repeat"]);
		$result .= "\n<table" .$this->getAttributes($tableAttributes) .">\n";

		// output the tablecaption
		if ($this->tableCaption["caption"] != "") {
			$result .= "  <caption" .$this->getAttributes($this->tableCaption["attributes"]) .">";
			$result .= $this->tableCaption["caption"] ."</caption>\n";
		}

		// $this->debug($this->tableArray);

		if (is_array($this->groupAttributes)) {
			// create an array with all rows from the tableArray sorted by their group
			// we use the groupAttributes array for this (we unset the things we added here afterwards)
			$groups = array_keys($this->groupAttributes);
			for ($i = 0; $i < count($this->groupAttributes); $i++) {
				$this->groupAttributes[$groups[$i]]["rows"] = array();
			}

			for ($row = 0; $row < $this->rowCount; $row++) {
				$row_group = $this->tableArray[$row]["properties"]["group"];
				$this->groupAttributes[$row_group]["rows"][] = $row;
			}

			// $this->debug($this->groupAttributes);
			$head_rowCount = count($this->groupAttributes["thead"]["rows"]);

			// output for every group
			for ($i = 0; $i < count($this->groupAttributes); $i++) {
				if (count($this->groupAttributes[$groups[$i]]["rows"]) > 0) {
					// output the group tag
					$result .= "  <" .$groups[$i];
					$attributes = $this->groupAttributes[$groups[$i]];
					// $this->debug(array($attributes, $groups[$i]));
					unset($attributes["rows"]);
					$result .= $this->getAttributes($attributes);
					$result .= ">\n";

					// cycle through the rows
					foreach ($this->groupAttributes[$groups[$i]]["rows"] as $row_index) {
						// insert a "normal" row
						$row = $this->tableArray[$row_index];
						$result .= $this->getRowXHTML($row);

						// here we handle the header repeat
						// insert the header once again
						if (($this->tableAttributes["header_repeat"] > 0) &&
							($index == $this->tableAttributes["header_repeat"]) &&
							($groups[$i] != "thead") &&
							($row_number < ($this->getRowCount() -1)) &&
							($head_rowCount > 0)) {

							$result .= "  </" .$groups[$i] .">\n";
							$result .= "  <" .$groups[$i];
							$sub_head_attributes = $this->groupAttributes["thead"];
							unset($sub_head_attributes["rows"]);
							$result .= $this->getAttributes($sub_head_attributes);
							$result .= ">\n";

							foreach ($this->groupAttributes["thead"]["rows"] as $sub_head_index) {
								$result .= $this->getRowXHTML($this->tableArray[$sub_head_index]);
							}

							$result .= "  </" .$groups[$i] .">\n";
							$result .= "  <" .$groups[$i];
							$attributes = $this->groupAttributes[$groups[$i]];
							unset($attributes["rows"]);
							$result .= $this->getAttributes($attributes);
							$result .= ">\n";

							$index = 0;
						}
						$index++;
						$row_number++;
					}

					if ($groups[$i] == "thead") $index = 1;

					// close the group
					$result .= "  </" .$groups[$i] .">\n";
				}
			}

			// unset all data we inserted in the groupAttributes array
			for ($i = 0; $i < count($this->groupAttributes); $i++) {
				unset($this->groupAttributes[$groups[$i]]["rows"]);
			}
		} else {
			// cycle through the rows (without any grouping)
			foreach ($this->tableArray as $row) {
				$result .= $this->getRowXHTML($row);
			}
		}

		// close the table
		$result .= "</table>\n";

		return $result;
	}


	/**
	 * returns the XHTML code for a single row
	 * @ingroup output
	 *
	 * @param	row		an array with the row
	 * @return			a string with the XHTML-code
	 */
	function getRowXHTML($row) {
		$result = '';

		// get possible row attributes
		$row_properties = $row["properties"];
		unset($row["properties"]);
		$result .= "    <" .$row_properties["tag"];

		// get the attributes for this row
		$result .= $this->getAttributes($row_properties["attributes"]);

		// close the opening row tag
		$result .= ">\n";

		foreach ($row as $cell) {
			if (!$cell["skip"]) {
				$result .= "      <" .$cell["tag"];
				$result .= $this->getAttributes($cell["attributes"]);
				$result .= ">" .trim($cell["content"]);
				$result .= "</" .$cell["tag"] .">\n";
			}
		}

		// close the row
		$result .=  "    </" .$row_properties["tag"] .">\n";

		return $result;
	}


	/**
	 * returns a string with all properties given in the submitted array
	 * @ingroup output
	 *
	 * @param	properties	array with tag properties
	 * @return				as string with the attributes as XHTML
	 */
	function getAttributes($properties) {
		$result = "";
		$attributes = array();

		if (count($properties) > 0) {
			$attribute_names = array_keys($properties);
			foreach ($properties as $name => $value) {
				$result .= " " .$name ."=\"";
				// if the attribute is empty
				if ($value == "") {
					$result .= $name;
				} else {
					$result .= $value;
				}
				$result .= "\"";
			}
		}

		return $result;
	}












	/**
	 * @defgroup input The input functions
	 */

	/**
	 * this function parses an xml document and returns a multidimensional
	 * array containing the date from the xml-source
	 * @ingroup input
	 *
	 * @param	xml			the xml-input
	 * @param	from_html	if this is set, the input structure is html and has to be parsed before processing
	 * @return				the array extracted from the xml
	 */
	function xml2tree($xml, $from_html = false) {
		if ($from_html) {
			// This is for replacing all < and > in cell value
			// to prevent inside tags from parsing by the parser
			// get all valid tags
			$tags = array();
			foreach ($this->tagDefinitions as $group => $definitions) {
				foreach ($definitions as $tag => $stuff) {
					if ($tag != "universal") $tags[] = $tag;
				}
			}

			// extract all tags
			$matches = array();
			$pattern = '&<[^>]+>&is';
			preg_match_all($pattern, $xml, $matches);

			if (is_array($matches[0])) {
				// every match
				foreach ($matches[0] as $match) {
					// $this->debug(array($match));
					$tag_match = array();
					// get the tagname (e.g. <table or <tbody>)
					preg_match("/<([^ |>])*/i", $match, $tag_match);
					// remove leading "<"
					$tag_match = substr($tag_match[0], 1);
					// if closingtag remove "/"
					if ($tag_match[0] == "/") $tag_match = substr($tag_match, 1);
					// check if the tag is a table tag
					if (!in_array($tag_match, $tags)) {
						// if not replace the brackets with placeholders
						$replacement = str_replace('<', '###[###', $match);
						$replacement = str_replace('>', '###]###', $replacement);
						// replace the original tag with the changed one
						$xml = str_replace($match, $replacement, $xml);
					}
				}
			}

			// this was added at Jan. 13. 2005 Bugfix relating & in cellcontent
			$xml = str_replace('&', '###AMP###', $xml);

			// debug($xml);
		}

		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		$result = xml_parse_into_struct($parser, $xml, $vals, $index);

		if (!$result) {
			$byte = xml_get_current_byte_index($parser);
			$line = xml_get_current_line_number($parser);
			$code = xml_get_error_code($parser);
			$this->errorMessage = "Error in XML\n";
			$this->errorMessage .= "code " .$code ."\n";
			$this->errorMessage .= "message " .xml_error_string($code) ."\n";
			$this->errorMessage .= "line " .$line ."\n";
			$this->errorMessage .= "content " .substr($xml, ($byte -10), 20);
			return false;
		}

		xml_parser_free($parser);

		$stack = array(array());
		$stacktop = 0;
		$parent = array();

		if ($from_html) $cellTags = array_keys($this->tagDefinitions['cell']);

		foreach ($vals as $val) {
			// if ($val["tag"] == 'td') debug(htmlspecialchars(nl2br($val['value'])));
			$type = $val["type"];
			if ($type == "open" || $type == "complete") {
				// open tag
				$stack[$stacktop++] = $tagi;
				$tagi = array("tag" => $val["tag"]);

				if (isset($val["attributes"])) $tagi["attrs"] = $val["attributes"];
				if (isset($val["value"])) $tagi["values"][] = $val["value"];
			}
			if ($type == "complete" || $type == "close") {
				// finish tag
				$tags[] = $oldtagi = $tagi;
				$tagi = $stack[--$stacktop];
				$oldtag = $oldtagi["tag"];

				// handle celltags special
				if (!$from_html || ($from_html && !in_array($oldtagi["tag"], $cellTags))) {
					unset($oldtagi["tag"]);
					$tagi["children"][$oldtag][] = $oldtagi;
				} else {
					// we save cells in a special field of the row array
					// and hold the tag, this will be parsed later on
					$tagi["children"]['cells'][] = $oldtagi;
				}

				$parent = $tagi;
			}
			if ($type == "cdata") {
				$tagi["values"][] = $val["value"];
			}
		}
		// if ($from_html) debug($parent);
		return $parent["children"];
	}


	/**
	 * here we build an array wich contains the definitions for every single field
	 * available for editing.
	 * @ingroup input
	 *
	 * @param	definitions		an array that contains the definitions
	 * @return					an array that contains the parsed definitions
	 */
	function parseDefinitions($definitions) {
		$definitions = $definitions["definitions"][0]["children"];
		$result = array();
		$tag_count = count($definitions["tag"]);

		foreach ($definitions["tag"] as $tag) {
			$tag = $tag["children"];
			$tag_type = $tag["type"][0]["values"][0];
			$name = $tag["name"][0]["values"][0];

			$attributes = array();
			// debug($tag);

			foreach ($tag["attributes"][0]["children"]["attribute"] as $attribute) {
				$group = $attribute["attrs"]["group"];
				$type = $attribute["attrs"]["type"];
				if ($type == "set") {
					$type = array();
					$values = $attribute["children"]["value"];
					foreach ($values as $value) {
						$type[] = $value["values"][0];
					}
				}

				$attributes[$attribute["attrs"]["name"]] = $type;

				$this->tagGroups[$tag_type][$name][$group][] = $attribute["attrs"]["name"];
				$this->contentGroups[$tag_type][$group] = $group;
			}

			$result[$tag_type][$name] = $attributes;
		}
		// debug($this->tagGroups);
		return $result;
	}



	/**
	 * imports a structure from a html-table
	 * @ingroup input
	 *
	 * @param	html	string input wich contains the html-source
	 * @return			true if import was successful, otherwise false (message in errorMessage)
	 */
	function importXHTML($html) {
		// echo 'importing: ' .htmlspecialchars($html);
		$html_tree = $this->xml2tree($html, true);

		if (!$html_tree) { /* debug($this->errorMessage); */ return false; }

		$import_table = array();
		$import_tableAttr = array();

		// get information about table
		if (is_array($html_tree["table"][0]["attrs"])) {
			// get valid definitions for tables
			$definitions = $this->tagDefinitions["parent"]["table"];
			$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

			// some attributes for the table are set
			foreach ($html_tree["table"][0]["attrs"] as $name => $value) {
				$this->setTableAttribute($name, $value, true);
			}
		}

		// let's go on with rows
		// strip some stuff we don't need anymore
		$html_tree = $html_tree["table"][0]["children"];
		// $this->debug($this->tagDefinitions);

		// get the group tags from definitions
		if (is_array($this->tagDefinitions["group"])) {
			$group_tags = array_keys($this->tagDefinitions["group"]);
		} else {
			$group_tags = array();
		}

		// check every part we got here
		foreach ($html_tree as $key => $sub_array) {
			// check if the sub_array is in a group
			// get group tags
			foreach ($sub_array as $sub_array_element) {
				// $this->debug(array($key, $sub_array_element));
				if (in_array($key, $group_tags)) {
					$import_table = array_merge((array)$import_table, (array)$this->importRowsFromXMLTree($sub_array_element["children"], $key));
				} else {
					$import_table = array_merge((array)$import_table, (array)$this->importRowsFromXMLTree(array("tr" => array(0 => $sub_array_element))));
				}
			}
		}

		// write the import to the tableArray
		$this->tableArray = $import_table;
		// and set the row and col count
		$this->rowCount = count($this->tableArray);
		$this->colCount = count($this->tableArray[0]) -1;

		// now check for all rowspans and insert the cells that are missed until now.
		// edit the default cell
		$old_skip = $this->defaultCell["skip"];
		$this->defaultCell["skip"] = 1;
		for ($row = 0; $row < $this->rowCount; $row++) {
			for ($col = 0; $col < $this->colCount; $col++) {
				$cell = $this->tableArray[$row][$col];
				if ($cell["attributes"]["rowspan"]) {
					$rowspan = $cell["attributes"]["rowspan"];
					$colspan = $cell["attributes"]["colspan"];
					if ($colspan == '') $colspan = 1;
					// add cells for all rows under this row as much as in colspan is defined
					for ($i = 1; $i < $rowspan; $i++) {
						$this->tableArray[($row +$i)] = $this->insertCells($this->tableArray[($row +$i)], $col, $colspan);

					}
				}
			}
		}
		$this->defaultCell["skip"] = $old_skip;

		return true;
	}


	/**
	 * parses a xml tree and returns an array with all rows found in it
	 * @ingroup input
	 *
	 * @param	xml_tree	an array with the xml tree
	 * @param	group		the group the rows are located in
	 * @return				an array with the rows in the internal format
	 */
	function importRowsFromXMLTree($xml_tree, $group = "tbody") {
		// $this->debug($xml_tree);
		$row_tag = array_keys($xml_tree);
		$row_tag = $row_tag[0];

		// get the columns for the row
		$xml_tree = $xml_tree[$row_tag];

		// $this->debug($xml_tree);
		$result = '';

		foreach ($xml_tree as $sub_tree) {
			$properties = $this->getDefaultRow();

			$properties["group"] = $group;
			$properties["tag"] = $row_tag;
			$properties["attributes"] = array();

			// get the attributes
			$attributes = $sub_tree["attrs"];
			if (is_array($attributes)) {
				// get valid definitions for tag
				$definitions = $this->tagDefinitions["row"]["tr"];
				$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

				// some attributes for the table are set
				foreach ($attributes as $name => $value) {
					if ($this->checkAttributeValue($definitions[$name], $value, true)) {
						$properties["attributes"][$name] = $value;
					}
				}
			}

			$row = array();
			$row["properties"] = $properties;

			// remove the attributes from xml_tree
			unset($sub_tree["attrs"]);
			// and get children (cols)
			$sub_tree = $sub_tree["children"];

			// cycle trough every type of cell in this row
			foreach ($sub_tree as $tag => $tag_fields) {
				// cycle trough every cell in this row
				foreach ($tag_fields as $tag_array) {
					$cell = $this->getDefaultCell();
					$cell["tag"] = $tag_array['tag'];
					// $cell['tag'] = $tag;
					$cell["content"] = $tag_array["values"][0];
					// debug($cell["content"]);
					$cell["content"] = str_replace('###[###', '<', $cell["content"]);
					$cell["content"] = str_replace('###]###', '>', $cell["content"]);
					// this was added at Jan. 13. 2005 Bugfix relating & in cellcontent
					$cell['content'] = str_replace('###AMP###', '&', $cell['content']);
					$cell["attributes"] = array();

					// get the attributes for the tag_fields
					if (is_array($tag_array["attrs"])) {
						$definitions = $this->tagDefinitions["cell"][$cell["tag"]];
						$definitions = array_merge((array)$definitions, (array)$this->tagDefinitions["all"]["universal"]);

						// some attributes for the cell are set
						foreach ($tag_array["attrs"] as $name => $value) {

							if ($this->checkAttributeValue($definitions[$name], $value, true)) {
								$cell["attributes"][$name] = $value;
							}
						}
					}

					$row[] = $cell;

					// insert some additional cells if colspan is set
					if ($cell["attributes"]["colspan"]) {
						$colspan = $cell["attributes"]["colspan"];
						for ($i = 0; $i < ($colspan -1); $i++) {
							$add_cell = $this->getDefaultCell();
							$add_cell["skip"] = 1;
							$row[] = $add_cell;
						}
					}
				}
			}

			$result[] = $row;
		}
		// debug($result);
		return $result;
	}
}
?>
