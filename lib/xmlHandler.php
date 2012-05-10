<?php

/** @file xmlHandler.php
 * Import and export of files as XML data.
 *
 * @author Marcel Bollmann
 * @date May 2012
 */

class XMLHandler {

  private $db; /**< A DBInterface object. */
  private $output_suggestions; /**< Boolean indicating whether to output tagger suggestions. */
  private $xml_header_options; /**< Valid attributes for the XML <header> tag. */

  function __construct($db) {
    $this->db = $db;
    $this->output_suggestions = true;
    $this->xml_header_options = array('sigle','name','tagset','progress');
  }

  /****** FUNCTIONS RELATED TO DATA IMPORT ******/

  /** Process header information. */
  private function processXMLHeader(&$reader, &$options) {
    // any data before the header is skipped!
    while ($reader->read() && $reader->name !== 'header');
    $header = simplexml_import_dom($doc->importNode($reader->expand(), true));
    // get header attributes if they are not already set in $options
    foreach($this->xml_header_options as $key) {
      if (isset($header[$key]) && !isset($options[$key])) {
	$options[$key] = $header[$key];
      }
    }
  }

  /** Process XML data. */
  private function processXMLData(&$reader) {
    $data = array();

    while ($reader->read()) {
      // only handle opening tags
      if ($reader->nodeType!==XMLReader::ELEMENT) { continue; }

      if ($reader->name == 'token') {
	$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
	$token = array();
	// some of these can possibly be empty
	$token['form']    = $node->form['dipl'];
	$token['norm']    = $node->form['norm'];
	$token['lemma']   = $node->lemma['inst'];
	$token['pos']     = $node->pos['inst'];
	$token['morph']   = $node->infl['val'];
	$token['comment'] = $node->comment;
	$suggs = array();
	foreach($node->suggestions->pos as $sugg){
	  $suggs[] = array('type'=>'tag_POS',
			   'value'=>$sugg['inst'],
			   'score'=>$sugg['score']);
	}
	foreach($node->suggestions->infl as $sugg){
	  $suggs[] = array('type'=>'tag_POS',
			   'value'=>$sugg['val'],
			   'score'=>$sugg['score']);
	}
	$token['suggestions'] = $suggs;
	$data[] = $token;
      }
      // could add further 'if' statements to process <boundary>
      // elements, <page> groupings, etc.
    }

    return $data;
  }

  /** Import XML data into the database as a new document.
   *
   * Parses XML data and sends database queries to import the data.
   * Data will be imported as a new document; adding information to an
   * already existing document is not (yet) supported.
   *
   * @param string $xmlfile Name of a file containing XML data to
   * import; typically a temporary file generated from user-uploaded
   * data
   * @param array $options Array containing metadata (e.g. sigle,
   * name, tagset) for the document; if there is a conflict with
   * the same type of data being supplied in the XML file,
   * the @c $options array takes precedence
   */
  public function import($xmlfile, $options) {
    /* 1. Read data into memory.
     * 2. Possibly check it for integrity, empty fields, etc.
     * 2b. Also check if the document should be flagged as
     *     "POS-tagged", "Morph-tagged", etc.
     * 3. Construct SQL query strings and send them to the DB.
     */

    // assumes valid XML
    $doc = new DOMDocument();
    $reader = new XMLReader();
    $reader->open($xmlfile);
    $this->processXMLHeader($reader, $options);
    $data = $this->processXMLData($reader);
    $reader->close();

    // continue here:
    /* check data for integrity, producing warnings for certain empty
       fields, maybe even check tags against the tagset and produce
       warnings for any mismatching tags. */

    // @todo if(!fatal_error) { ... }
    $this->db->insertNewDocument($options, $data);

    // @todo return errors and/or warnings
  }

  /****** FUNCTIONS RELATED TO DATA EXPORT ******/

  /** Output the HTTP header for an XML file. */
  private function outputXMLHeader($filename) {
    header("Cache-Control: public");
    header("Content-Type: text/xml");
    // header("Content-Transfer-Encoding: Binary");
    // header("Content-Length:".filesize($attachment_location));
    header("Content-Disposition: attachment; filename=".$filename);
  }

  /** Output metadata in XML format. */
  private function outputMetadataAsXML($writer, $fileid) {
    // this does nothing but fetch file metadata:
    $metadata = $this->db->openFile($fileid);
    if($metadata['success']) {
      $writer->startElement('header');
      $writer->writeAttribute('sigle', $metadata['data']['sigle']);
      $writer->writeAttribute('name', $metadata['data']['file_name']);
      $writer->writeAttribute('tagset', $metadata['data']['tagset']);
      $writer->writeAttribute('progress', $metadata['lastEditedRow']);
      $writer->endElement(); // 'header'
    }
    else {
      throw new Exception("File metadata could not be retrieved from the database.");
    }
  }

  /** Output lines in XML format. */
  private function outputLinesAsXML($writer, $fileid) {
    $count = 1;
    foreach($this->db->getAllLines($fileid) as $line){
      $writer->startElement('token');
      $writer->writeAttribute('id', "t_{$line['line_id']}");
      $writer->writeAttribute('count', $count++);
      // form
      $writer->startElement('form');
      $writer->writeAttribute('dipl', $line['token']);
      if($line['tag_norm']!==null && $line['tag_norm']!==''){
	$writer->writeAttribute('norm', $line['tag_norm']);
      }
      $writer->endElement();
      // lemma
      if($line['lemma']!==null && $line['lemma']!==''){
	$writer->startElement('lemma');
	$writer->writeAttribute('inst', $line['lemma']);
	$writer->endElement();
      }
      // pos
      if($line['tag_POS']!==null && $line['tag_POS']!==''){
	$writer->startElement('pos');
	$writer->writeAttribute('inst', $line['tag_POS']);
	$writer->endElement();
      }
      // morph
      if($line['tag_morph']!==null && $line['tag_morph']!==''){
	$writer->startElement('infl');
	$writer->writeAttribute('val', $line['tag_morph']);
	$writer->endElement();
      }
      // suggestions
      if($this->output_suggestions){
	$this->outputSuggestionsAsXML($writer, $fileid, $line['line_id']);
      }
      // comment
      if($line['comment']!==null && $line['comment']!=='') {
	$writer->writeElement('comment', $line['comment']);
      }
      // closing
      $writer->endElement(); // 'token'
    }
  }

  /** Output tagger suggestions in XML format. */
  private function outputSuggestionsAsXML($writer, $fileid, $lineid) {
    $writer->startElement('suggestions');
    foreach($this->db->getAllSuggestions($fileid,$lineid) as $sugg){
      if($sugg['tagtype']=='pos') {
	$writer->startElement('pos');
	$writer->writeAttribute('inst', $sugg['tag_name']);
	$writer->writeAttribute('score', $sugg['tag_probability']);
	$writer->endElement();
      }
      else if($sugg['tagtype']=='morph') {
	$writer->startElement('infl');
	$writer->writeAttribute('val', $sugg['tag_name']);
	$writer->writeAttribute('score', $sugg['tag_probability']);
	$writer->endElement();
      }
    }
    $writer->endElement(); // 'suggestions'
  }

  /** Export a document from the database in XML format.
   *
   * Retrieves all data for a given document from the database and
   * writes the corresponding XML data to the PHP output stream.
   *
   * @param string $fileid Internal ID of the document to export
   */
  public function export($fileid) {
    $this->outputXMLHeader($fileid.".xml");

    $writer = new XMLWriter();
    $writer->openURI('php://output');
    $writer->startDocument('1.0', 'UTF-8'); 
    $writer->startElement('cora');

    $this->outputMetadataAsXML($writer, $fileid);
    $this->outputLinesAsXML($writer, $fileid);

    $writer->endElement(); // 'cora'
    $writer->endDocument();
    $writer->flush();
  }


}

?>