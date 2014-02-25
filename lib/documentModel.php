<?php

/** @file documentModel.php 
 * 
 * Representation of a CorA document, used for converting between
 * formats (e.g., for DB import from XML).
 *
 * @author Marcel Bollmann
 * @date February 2013
 */

/** Exception when encountering illegal values. */
class CoraDocumentException extends Exception { }
class DocumentValueException extends CoraDocumentException { }
class DocumentImportException extends CoraDocumentException { }

class CoraDocument {
  private $sigle     = "";     /**< Document sigle */
  private $fullname  = "";     /**< Document name */
  private $header    = "";     /**< Document header (free-format text) */
  private $internal = array("fileid"  => null,
			    "currentmod_id" => null,
			    "tagsets" => array()
			    );			    

  private $pages     = array();
  private $columns   = array();
  private $lines     = array();
  private $shifttags = array();
  private $comments  = array();
  private $tokens    = array();
  private $dipls     = array();
  private $moderns   = array();

  function __construct($options) {
    if($options && $options != null) {
      $this->setMetadata($options);
    }
  }

  /** Construct a new document from a file stored in the database.
   *
   * @param string $fileid The ID of the file to be constructed
   * @param DBInterface $db Object to access the database
   *
   * @return A new @c CoraDocument object representing the file with
   * the supplied ID.
   */
  public static function fromDB($fileid, $db) {
    // File metadata
    $metadata = $db->openFile($fileid);
    if(!$metadata || !$metadata['success']) {
      throw new DocumentImportException("Couldn't open the file in the database.");
    }
    $instance = new self($metadata);
    $instance->setHeader($metadata['header']);

    // Tokens
    $data = $db->getAllTokens($fileid);
    list($toks, $dipls, $mods) = $data;
    $instance->setTokens($toks, $dipls, $mods);

    // Layout
    $data = $db->getLayoutInfo($fileid);
    list($pages, $cols, $lines) = $data;
    $instance->setLayoutInfo($pages, $cols, $lines);

    // Shifttags & Comments
    $data = $db->getShiftTags($fileid);
    $instance->setShiftTags($data);
    $data = $db->getComments($fileid);
    $instance->setComments($data);

    $db->unlockFile($fileid, $force=true);
    return $instance;
  }

  /** Add a comment.
   *
   * @param string $tok_id Database ID of the token to which comment is attached
   * @param string $xml_id XML ID of the token to which comment is attached
   * @param string $text Comment as string
   * @param string $type Comment type as a single letter (e.g. K, E)
   * @param string $subtok_id Modern ID of the token to which comment is attached
   */
  public function addComment($tok_id, $xml_id, $text, $type, $subtok_id=NULL) {
    $comment = array();
    $comment['parent_db_id']  = $tok_id;
    $comment['parent_xml_id'] = $xml_id;
    $comment['text']   = $text;
    $comment['type']   = $type;
    $comment['subtok_db_id']  = $subtok_id;
    $this->comments[] = $comment;
    return $this;
  }

  /** Update array with database IDs of pages.
   *
   * Takes the ID of the first page and calculates IDs of further
   * pages incrementally (as should be the case after inserting pages
   * with a single SQL query, for example).
   */
  public function fillPageIDs($first_id) {
    $id = intval($first_id);
    $xmltodb = array();
    foreach($this->pages as &$page) {
      $page['db_id'] = $id++;
      $xmltodb[$page['xml_id']] = $page['db_id'];
    }
    unset($page);
    // columns refer to pages, so update these too
    foreach($this->columns as &$column) {
      $column['parent_db_id'] = $xmltodb[$column['parent_xml_id']];
    }
    unset($column);
    return $this;
  }

  /** Update array with database IDs of columns.
   */
  public function fillColumnIDs($first_id) {
    $id = intval($first_id);
    $xmltodb = array();
    foreach($this->columns as &$column) {
      $column['db_id'] = $id++;
      $xmltodb[$column['xml_id']] = $column['db_id'];
    }
    unset($column);
    // lines refer to columns, so update these too
    foreach($this->lines as &$line) {
      $line['parent_db_id'] = $xmltodb[$line['parent_xml_id']];
    }
    unset($line);
    return $this;
  }

  /** Update array with database IDs of lines.
   */
  public function fillLineIDs($first_id) {
    $id = intval($first_id);
    $xmltodb = array();
    foreach($this->lines as &$line) {
      $line['db_id'] = $id++;
      $xmltodb[$line['xml_id']] = $line['db_id'];
    }
    unset($line);
    // dipls refer to lines, so update these too
    foreach($this->dipls as &$dipl) {
      $dipl['parent_line_db_id'] = $xmltodb[$dipl['parent_line_xml_id']];
    }
    unset($dipl);
    return $this;
  }

  /** Update array with database IDs of tokens.
   */
  public function fillTokenIDs($first_id) {
    $id = intval($first_id);
    $xmltodb = array();
    foreach($this->tokens as &$token) {
      $token['db_id'] = $id++;
      $xmltodb[$token['xml_id']] = $token['db_id'];
    }
    unset($token);
    // dipls refer to tokens
    foreach($this->dipls as &$dipl) {
      $dipl['parent_tok_db_id'] = $xmltodb[$dipl['parent_tok_xml_id']];
    }
    unset($dipl);
    // moderns refer to tokens
    foreach($this->moderns as &$mod) {
      $mod['parent_db_id'] = $xmltodb[$mod['parent_xml_id']];
    }
    unset($mod);
    // shifttags refer to tokens
    foreach($this->shifttags as &$shtag) {
      $shtag['db_range'] = array($xmltodb[$shtag['range'][0]],
				 $xmltodb[$shtag['range'][1]]);
    }
    unset($shtag);
    // comments refer to tokens
    foreach($this->comments as &$comment) {
      $comment['parent_db_id'] = $xmltodb[$comment['parent_xml_id']];
    }
    unset($comment);
    return $this;
  }

  /** Update array with database IDs of dipls.
   */
  public function fillDiplIDs($first_id) {
    $id = intval($first_id);
    $xmltodb = array();
    foreach($this->dipls as &$dipl) {
      $dipl['db_id'] = $id++;
      $xmltodb[$dipl['xml_id']] = $dipl['db_id'];
    }
    unset($dipl);
    return $this;
  }

  /** Update array with database IDs of moderns.
   */
  public function fillModernIDs($first_id) {
    $id = intval($first_id);
    foreach($this->moderns as &$mod) {
      $mod['db_id'] = $id++;
    }
    unset($mod);
    return $this;
  }


  /** Translate ranges (as found in the XML) to ID references (as
      found in the database). */
  public function mapRangesToIDs() {
    // map lines to columns and columns to pages
    $currentcol_idx  = 0;
    $currentline_idx = 0;
    $currentcol  = $this->columns[0];
    $currentline = $this->lines[0];
    foreach($this->pages as &$currentpage) {
      list($pagestart, $pageend) = $currentpage['range'];
      if ($currentcol_idx >= count($this->columns)) {
	throw new DocumentValueException("Out of columns for page '" . $currentpage['xml_id'] . "'.");
      }
      $currentcol = $this->columns[$currentcol_idx];
      if($currentcol['xml_id'] !== $pagestart) {
	throw new DocumentValueException("Expected column '{$pagestart}' for page '" . $currentpage['xml_id']
					 ."', but found column '" . $currentcol['xml_id'] . "'.");
      }
      do {
        if ($currentcol_idx >= count($this->columns)) {
            throw new DocumentValueException("Out of columns for page '" . $currentpage['xml_id'] . "'.");
        }
        $currentcol = $this->columns[$currentcol_idx];
	$this->columns[$currentcol_idx]['parent_xml_id'] = $currentpage['xml_id'];
	list($colstart, $colend) = $currentcol['range'];
	if ($currentline_idx >= count($this->lines)) {
	  throw new DocumentValueException("Out of lines for column '" . $currentcol['xml_id'] . "'.");
	}
	$currentline = $this->lines[$currentline_idx];
	if($currentline['xml_id'] !== $colstart) {
	  throw new DocumentValueException("Expected line '{$colstart}' for column '" . $currentcol['xml_id']
					   ."', but found line '" . $currentline['xml_id'] . "'.");
	}
	do {
          if ($currentline_idx >= count($this->lines)) {
            throw new DocumentValueException("Out of lines for column '" . $currentcol['xml_id'] . "'.");
          }
          $currentline = $this->lines[$currentline_idx];
	  $this->lines[$currentline_idx]['parent_xml_id'] = $currentcol['xml_id'];
	  $lastlineid  = $currentline['xml_id'];
          ++$currentline_idx;
	} while($lastlineid !== $colend);
	$lastcolid  = $currentcol['xml_id'];
	++$currentcol_idx;
      } while($lastcolid !== $pageend);
    }
    unset($currentpage);
    if($currentcol_idx < count($this->columns)) {
      throw new DocumentValueException("No pages left at column '" . $currentcol['xml_id'] . "'.");
    }
    if($currentline_idx < count($this->lines)) {
      throw new DocumentValueException("No columns left at line '" . $currentline['xml_id'] . "'.");
    }

    // map diplomatic tokens to lines (done separately mainly for legibility)
    $currentdipl_idx = 0;
    foreach($this->lines as &$currentline) {
      $currentdipl = $this->dipls[$currentdipl_idx];
      list($linestart, $lineend) = $currentline['range'];
      if($currentdipl['xml_id'] !== $linestart) {
	throw new DocumentValueException("Expected dipl '{$linestart}' for line '" . $currentline['xml_id']
					 ."', but found dipl '" . $currentdipl['xml_id'] . "'.");
      }
      do {
        if ($currentdipl_idx >= count($this->dipls)) {
          throw new DocumentValueException("Out of diplomatic tokens for line '" . $currentline['xml_id'] . "'.");
        }
        $currentdipl = $this->dipls[$currentdipl_idx];
	$this->dipls[$currentdipl_idx]['parent_line_xml_id'] = $currentline['xml_id'];
	$lastdiplid  = $currentdipl['xml_id'];
	++$currentdipl_idx;
      } while($lastdiplid !== $lineend);
    }
    unset($currentline);
    if($currentdipl_idx < count($this->dipls)) {
      throw new DocumentValueException("No lines left at diplomatic token '" . $currentdipl['xml_id'] . "'.");
    }
    return $this;
  }

  /** Translate ID references (as found in the database) to ranges (as
      found in the XML). */
  public function mapIDsToRanges() {
      // danger, spaghetti code ahead
      // collect ranges for lines
      $lines_by_id = array();
      foreach($this->dipls as $currentdipl) {
          $lnid = $currentdipl["parent_line_db_id"];
          $dpid = $currentdipl["db_id"];
          if(!array_key_exists($lnid, $lines_by_id)) {
              // this dipl is the start (and currently-known end) 
              // of the span
              $lines_by_id[$lnid] = array($dpid, $dpid);
          }
          else {
              // this dipl becomes the new end of the span
              $lines_by_id[$lnid][1] = $dpid;
          }
      }
      // consolidate & collect ranges for columns
      $col_by_id = array();
      foreach($this->lines as &$currentline) {
          $lnid = $currentline["db_id"];
          $clid = $currentline["parent_db_id"];
          if(!array_key_exists($lnid, $lines_by_id)) {
              // line without any token ... this shouldn't happen;
              // throwing exceptions would prevent faulty texts from
              // being exported at all, though ...
          }
          else {
              $currentline["range"] = $lines_by_id[$lnid];
          }
          
          if(!array_key_exists($clid, $col_by_id)) {
              $col_by_id[$clid] = array($lnid, $lnid);
          }
          else {
              $col_by_id[$clid][1] = $lnid;
          }
      }
      // consolidate & collect ranges for pages
      $pages_by_id = array();
      foreach($this->columns as &$currentcol) {
          $clid = $currentcol["db_id"];
          $pgid = $currentcol["parent_db_id"];
          if(!array_key_exists($clid, $col_by_id)) {
              // same as above
          }
          else {
              $currentcol["range"] = $col_by_id[$clid];
          }

          if(!array_key_exists($pgid, $pages_by_id)) {
              $pages_by_id[$pgid] = array($clid, $clid);
          }
          else {
              $pages_by_id[$pgid][1] = $clid;
          }
      }
      // consolidate once more
      foreach($this->pages as &$currentpage) {
          $pgid = $currentpage["db_id"];
          if(!array_key_exists($pgid, $pages_by_id)) {
              // same as above
          }
          else {
              $currentpage["range"] = $pages_by_id[$pgid];
          }
      }
      // done!
      return $this;
  }


  /* GETTERS AND SETTERS */

  function setHeader($value) {
    $this->header = $value;
    return $this;
  }

  function getHeader() {
    return $this->header;
  }

  public function setMetadata($options) {
    if(isset($options['sigle']) && !empty($options['sigle'])) {
      $this->sigle = $options['sigle'];
    }
    if(isset($options['name']) && !empty($options['name'])) {
      $this->fullname = $options['name'];
    }
    if(isset($options['currentmod_id']) && !empty($options['currentmod_id'])) {
      $this->internal['currentmod_id'] = $options['currentmod_id'];
    }
    if(isset($options['id']) && !empty($options['id'])) {
      $this->internal['fileid'] = $options['id'];
    }
    if(isset($options['tagsets']) && !empty($options['tagsets'])) {
      $this->internal['tagsets'] = $options['tagsets'];
    }
  }

  /** Set layout information directly.
   */
  public function setLayoutInfo($pages="", $columns="", $lines="") {
    if(!empty($pages)) {
      $this->pages = $pages;
    }
    if(!empty($columns)) {
      $this->columns = $columns;
    }
    if(!empty($lines)) {
      $this->lines = $lines;
    }
    return $this;
  }

  public function getPages() {
    return $this->pages;
  }
  public function getColumns() {
    return $this->columns;
  }
  public function getLines() {
    return $this->lines;
  }
  public function getTokens() {
    return $this->tokens;
  }
  public function getDipls() {
    return $this->dipls;
  }
  public function getModerns() {
    return $this->moderns;
  }
  public function getShifttags() {
    return $this->shifttags;
  }
  public function getComments() {
    return $this->comments;
  }

  /** Set shift tag information directly.
   */
  public function setShiftTags($shifttags) {
    $this->shifttags = $shifttags;
    return $this;
  }

  /** Set comments directly.
   */
  public function setComments($comments) {
    $this->comments = $comments;
    return $this;
  }

  /** Set token arrays directly.
   */
  public function setTokens($toks, $dipls, $mods) {
    $this->tokens  = $toks;
    $this->dipls   = $dipls;
    $this->moderns = $mods;
    return $this;
  }

}

?>