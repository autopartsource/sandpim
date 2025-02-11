<?php
/*
XLSXReader
Greg Neustaetter <gneustaetter@gmail.com>
Artistic License
XLSXReader is a heavily modified version of:
	SimpleXLSX php class v0.4 (Artistic License)
	Created by Sergey Schuchkin from http://www.sibvision.ru - professional php developers team 2010-2011
	Downloadable here: http://www.phpclasses.org/package/6279-PHP-Parse-and-retrieve-data-from-Excel-XLS-files.html
Key Changes include:
	Separation into two classes - one for the Workbook and one for Worksheets
	Access to sheets by name or sheet id
	Use of ZIP extension
	On-demand access of files inside zip
	On-demand access to sheet data
	No storage of XML objects or XML text
	When parsing rows, include empty rows and null cells so that data array has same number of elements for each row
	Configuration option for removing trailing empty rows
	Better handling of cells with style information but no value
	Change of class names and method names
	Removed rowsEx functionality including extraction of hyperlinks
*/

class XLSXReader {
	protected $sheets = array();
	protected $sharedstrings = array();
	protected $sheetInfo;
	protected $zip;
	public $config = array(
		'removeTrailingRows' => true
	);
	
	// XML schemas
	const SCHEMA_OFFICEDOCUMENT  =  'http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument';
	const SCHEMA_RELATIONSHIP  =  'http://schemas.openxmlformats.org/package/2006/relationships';
	const SCHEMA_OFFICEDOCUMENT_RELATIONSHIP = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
	const SCHEMA_SHAREDSTRINGS =  'http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings';
	const SCHEMA_WORKSHEETRELATION =  'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet';

	public function __construct($filePath, $config = array()) {
		$this->config = array_merge($this->config, $config);
		$this->zip = new ZipArchive();
		$status = $this->zip->open($filePath);
		if($status === true) {
			$this->parse();
		} else {
			throw new Exception("Failed to open $filePath with zip error code: $status");
		}
	}

	// get a file from the zip
	protected function getEntryData($name) {
		$data = $this->zip->getFromName($name);
		if($data === false) {
			throw new Exception("File $name does not exist in the Excel file");
		} else {
			return $data;
		}
	}

	// extract the shared string and the list of sheets
	protected function parse() {
		$sheets = array();
		$relationshipsXML = simplexml_load_string($this->getEntryData("_rels/.rels"));
		foreach($relationshipsXML->Relationship as $rel) {
			if($rel['Type'] == self::SCHEMA_OFFICEDOCUMENT) {
				$workbookDir = dirname($rel['Target']) . '/';
				$workbookXML = simplexml_load_string($this->getEntryData($rel['Target']));
				foreach($workbookXML->sheets->sheet as $sheet) {				
					$r = $sheet->attributes('r', true);
					$sheets[(string)$r->id] = array(
						'sheetId' => (int)$sheet['sheetId'],
						'name' => (string)$sheet['name']
					);
					
				}
				$workbookRelationsXML = simplexml_load_string($this->getEntryData($workbookDir . '_rels/' . basename($rel['Target']) . '.rels'));
				foreach($workbookRelationsXML->Relationship as $wrel) {
					switch($wrel['Type']) {
						case self::SCHEMA_WORKSHEETRELATION:
							$sheets[(string)$wrel['Id']]['path'] = $workbookDir . (string)$wrel['Target'];
							break;
						case self::SCHEMA_SHAREDSTRINGS:
							$sharedStringsXML = simplexml_load_string($this->getEntryData($workbookDir . (string)$wrel['Target']));
							foreach($sharedStringsXML->si as $val) {
								if(isset($val->t)) {
									$this->sharedStrings[] = (string)$val->t;
								} elseif(isset($val->r)) {
									$this->sharedStrings[] = XLSXWorksheet::parseRichText($val);
								}
							}
							break;
					}
				}
			}
		}
		$this->sheetInfo = array();
		foreach($sheets as $rid=>$info) {
			$this->sheetInfo[$info['name']] = array(
				'sheetId' => $info['sheetId'],
				'rid' => $rid,
				'path' => $info['path']
			);
		}
	}

	// returns an array of sheet names, indexed by sheetId
	public function getSheetNames() {
		$res = array();
		foreach($this->sheetInfo as $sheetName=>$info) {
			$res[$info['sheetId']] = $sheetName;
		}
		return $res;
	}

	public function getSheetCount() {
		return count($this->sheetInfo);
	}

	// instantiates a sheet object (if needed) and returns an array of its data
	public function getSheetData($sheetNameOrId) {
		$sheet = $this->getSheet($sheetNameOrId);
		return $sheet->getData();
	}

	// instantiates a sheet object (if needed) and returns the sheet object
	public function getSheet($sheet) {
		if(is_numeric($sheet)) {
			$sheet = $this->getSheetNameById($sheet);
		} elseif(!is_string($sheet)) {
			throw new Exception("Sheet must be a string or a sheet Id");
		}
		if(!array_key_exists($sheet, $this->sheets)) {
			$this->sheets[$sheet] = new XLSXWorksheet($this->getSheetXML($sheet), $sheet, $this);

		}
		return $this->sheets[$sheet];
	}

	public function getSheetNameById($sheetId) {
		foreach($this->sheetInfo as $sheetName=>$sheetInfo) {
			if($sheetInfo['sheetId'] === $sheetId) {
				return $sheetName;
			}
		}
		throw new Exception("Sheet ID $sheetId does not exist in the Excel file");
	}

	protected function getSheetXML($name) {
		return simplexml_load_string($this->getEntryData($this->sheetInfo[$name]['path']));
	}

	// converts an Excel date field (a number) to a unix timestamp (granularity: seconds)
	public static function toUnixTimeStamp($excelDateTime) {
		if(!is_numeric($excelDateTime)) {
			return $excelDateTime;
		}
		$d = floor($excelDateTime); // seconds since 1900
		$t = $excelDateTime - $d;
		return ($d > 0) ? ( $d - 25569 ) * 86400 + $t * 86400 : $t * 86400;
	}

}

class XLSXWorksheet {

	protected $workbook;
	public $sheetName;
	protected $data;
	public $colCount;
	public $rowCount;
	protected $config;

	public function __construct($xml, $sheetName, XLSXReader $workbook) {
		$this->config = $workbook->config;
		$this->sheetName = $sheetName;
		$this->workbook = $workbook;
		$this->parse($xml);
	}

	// returns an array of the data from the sheet
	public function getData() {
		return $this->data;
	}

	protected function parse($xml) {
		$this->parseDimensions($xml->dimension);
		$this->parseData($xml->sheetData);
	}

	protected function parseDimensions($dimensions) {
		$range = (string) $dimensions['ref'];
		$cells = explode(':', $range);
                $maxValues = $this->getColumnIndex($cells[1]);
                $this->colCount = $maxValues[0] + 1;
                $this->rowCount = $maxValues[1] + 1;
	}

	protected function parseData($sheetData) {
		$rows = array();
		$curR = 0;
		$lastDataRow = -1;
		foreach ($sheetData->row as $row) {
			$rowNum = (int)$row['r'];
			if($rowNum != ($curR + 1)) {
				$missingRows = $rowNum - ($curR + 1);
				for($i=0; $i < $missingRows; $i++) {
					$rows[$curR] = array_pad(array(),$this->colCount,null);
					$curR++;
				}
			}
			$curC = 0;
			$rowData = array();
			foreach ($row->c as $c) {
				list($cellIndex,) = $this->getColumnIndex((string) $c['r']);
				if($cellIndex !== $curC) {
					$missingCols = $cellIndex - $curC;
					for($i=0;$i<$missingCols;$i++) {
						$rowData[$curC] = null;
						$curC++;
					}
				}
				$val = $this->parseCellValue($c);
				if(!is_null($val)) {
					$lastDataRow = $curR;
				}
				$rowData[$curC] = $val;
				$curC++;
			}
			$rows[$curR] = array_pad($rowData, $this->colCount, null);
			$curR++;
		}
		if($this->config['removeTrailingRows']) {
			$this->data = array_slice($rows, 0, $lastDataRow + 1);
			$this->rowCount = count($this->data);
		} else {
			$this->data = $rows;
		}
	}

	protected function getColumnIndex($cell = 'A1') {
		if (preg_match("/([A-Z]+)(\d+)/", $cell, $matches)) {
			
			$col = $matches[1];
			$row = $matches[2];
			$colLen = strlen($col);
			$index = 0;

			for ($i = $colLen-1; $i >= 0; $i--) {
// LPS - depricated array access {}				$index += (ord($col{$i}) - 64) * pow(26, $colLen-$i-1);
				$index += (ord($col[$i]) - 64) * pow(26, $colLen-$i-1);
			}
			return array($index-1, $row-1);
		}
		throw new Exception("Invalid cell index");
	}
	
	protected function parseCellValue($cell) {
		// $cell['t'] is the cell type
		switch ((string)$cell["t"]) {
			case "s": // Value is a shared string
				if ((string)$cell->v != '') {
					$value = $this->workbook->sharedStrings[intval($cell->v)];
				} else {
					$value = '';
				}
				break;
			case "b": // Value is boolean
				$value = (string)$cell->v;
				if ($value == '0') {
					$value = false;
				} else if ($value == '1') {
					$value = true;
				} else {
					$value = (bool)$cell->v;
				}
				break;
			case "inlineStr": // Value is rich text inline
				$value = self::parseRichText($cell->is);
				break;
			case "e": // Value is an error message
				if ((string)$cell->v != '') {
					$value = (string)$cell->v;
				} else {
					$value = '';
				}
				break;
			default:
				if(!isset($cell->v)) {
					return null;
				}
				$value = (string)$cell->v;

				// Check for numeric values
				if (is_numeric($value)) {
					if ($value == (int)$value) $value = (int)$value;
					elseif ($value == (float)$value) $value = (float)$value;
					elseif ($value == (double)$value) $value = (double)$value;
				}
		}
		return $value;
	}

	// returns the text content from a rich text or inline string field
    public static function parseRichText($is = null) {
        $value = array();
        if (isset($is->t)) {
            $value[] = (string)$is->t;
        } else {
            foreach ($is->r as $run) {
                $value[] = (string)$run->t;
            }
        }
        return implode(' ', $value);
    }
}
/*
 * Artistic License
================
Preamble

The intent of this document is to state the conditions under which a
Package may be copied, such that the Copyright Holder maintains some
semblance of artistic control over the development of the package,
while giving the users of the package the right to use and distribute
the Package in a more-or-less customary fashion, plus the right to make
reasonable modifications.

Definitions:

	"Package" refers to the collection of files distributed by the
	Copyright Holder, and derivatives of that collection of files
	created through textual modification.

	"Standard Version" refers to such a Package if it has not been
	modified, or has been modified in accordance with the wishes
	of the Copyright Holder as specified below.

	"Copyright Holder" is whoever is named in the copyright or
	copyrights for the package.

	"You" is you, if you're thinking about copying or distributing
	this Package.

	"Reasonable copying fee" is whatever you can justify on the
	basis of media cost, duplication charges, time of people involved,
	and so on.  (You will not be required to justify it to the
	Copyright Holder, but only to the computing community at large
	as a market that must bear the fee.)

	"Freely Available" means that no fee is charged for the item
	itself, though there may be fees involved in handling the item.
	It also means that recipients of the item may redistribute it
	under the same conditions they received it.

1. You may make and give away verbatim copies of the source form of the
Standard Version of this Package without restriction, provided that you
duplicate all of the original copyright notices and associated disclaimers.

2. You may apply bug fixes, portability fixes and other modifications
derived from the Public Domain or from the Copyright Holder.  A Package
modified in such a way shall still be considered the Standard Version.

3. You may otherwise modify your copy of this Package in any way, provided
that you insert a prominent notice in each changed file stating how and
when you changed that file, and provided that you do at least ONE of the
following:

    a) place your modifications in the Public Domain or otherwise make them
    Freely Available, such as by posting said modifications to Usenet or
    an equivalent medium, or placing the modifications on a major archive
    site such as uunet.uu.net, or by allowing the Copyright Holder to include
    your modifications in the Standard Version of the Package.

    b) use the modified Package only within your corporation or organization.

    c) rename any non-standard executables so the names do not conflict
    with standard executables, which must also be provided, and provide
    a separate manual page for each non-standard executable that clearly
    documents how it differs from the Standard Version.

    d) make other distribution arrangements with the Copyright Holder.

4. You may distribute the programs of this Package in object code or
executable form, provided that you do at least ONE of the following:

    a) distribute a Standard Version of the executables and library files,
    together with instructions (in the manual page or equivalent) on where
    to get the Standard Version.

    b) accompany the distribution with the machine-readable source of
    the Package with your modifications.

    c) give non-standard executables non-standard names, and clearly
    document the differences in manual pages (or equivalent), together
    with instructions on where to get the Standard Version.

    d) make other distribution arrangements with the Copyright Holder.

5. You may charge a reasonable copying fee for any distribution of this
Package.  You may charge any fee you choose for support of this
Package.  You may not charge a fee for this Package itself.  However,
you may distribute this Package in aggregate with other (possibly
commercial) programs as part of a larger (possibly commercial) software
distribution provided that you do not advertise this Package as a
product of your own.  You may embed this Package's interpreter within
an executable of yours (by linking); this shall be construed as a mere
form of aggregation, provided that the complete Standard Version of the
interpreter is so embedded.

6. The scripts and library files supplied as input to or produced as
output from the programs of this Package do not automatically fall
under the copyright of this Package, but belong to whoever generated
them, and may be sold commercially, and may be aggregated with this
Package.  If such scripts or library files are aggregated with this
Package via the so-called "undump" or "unexec" methods of producing a
binary executable image, then distribution of such an image shall
neither be construed as a distribution of this Package nor shall it
fall under the restrictions of Paragraphs 3 and 4, provided that you do
not represent such an executable image as a Standard Version of this
Package.

7. C subroutines (or comparably compiled subroutines in other
languages) supplied by you and linked into this Package in order to
emulate subroutines and variables of the language defined by this
Package shall not be considered part of this Package, but are the
equivalent of input as in Paragraph 6, provided these subroutines do
not change the language in any way that would cause it to fail the
regression tests for the language.

8. Aggregation of this Package with a commercial distribution is always
permitted provided that the use of this Package is embedded; that is,
when no overt attempt is made to make this Package's interfaces visible
to the end user of the commercial distribution.  Such use shall not be
construed as a distribution of this Package.

9. The name of the Copyright Holder may not be used to endorse or promote
products derived from this software without specific prior written permission.

10. THIS PACKAGE IS PROVIDED "AS IS" AND WITHOUT ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, WITHOUT LIMITATION, THE IMPLIED
WARRANTIES OF MERCHANTIBILITY AND FITNESS FOR A PARTICULAR PURPOSE.

				The End
 */