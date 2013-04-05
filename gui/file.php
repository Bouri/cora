<?php
/** @file file.php
 * The document selection page.
 */

$filelist = $sh->getFiles(); 
$tagsets = $sh->getTagsetList();
// $projects = $sh->getProjectList();   defined in gui.php

?>

<div id="fileDiv" class="content" style="display: none;">

<?php
  if(empty($projects)):
?>
<div class="panel" id="noProjectGroups">
   <h3>Keine Projektgruppen verfügbar</h3>
   <p>Sie gehören zur Zeit keiner Projektgruppe an.</p>
   <p>Wenden Sie sich an einen Administrator, um zu einer Projektgruppe hinzuzufügt zu werden.</p>
</div>
<?php
    else:
?>

<div class="panel" id="fileImport">
	<h3>Datei importieren</h3>
  <p><button class="mform" id="importNewTransLink">Neues Dokument aus Transkriptionsdatei importieren...</button>
     <button class="mform" id="importNewXMLLink">Neues Dokument aus XML-Datei importieren...</button></p>
</div>

<div class="panel">
	<div class="fileViewRefresh">	
	<h3>Datei öffnen</h3>
		<img src="gui/images/View-refresh.svg" width="20px" height="20px"/>
	</div>

	<div id="files">
	</div>

</div>

<div class="templateHolder">
  <div id="transImportSpinner">
   <div id="transImportStatusContainer">
   <table>
   <tr id="tIS_upload"><td class="proc proc-running"></td><td>Datei übermitteln</td></tr>
   <tr id="tIS_check"><td class="proc" /></td><td>Transkription prüfen</td></tr>
   <tr id="tIS_convert"><td class="proc" /></td><td>Transkription analysieren</td></tr>
   <tr id="tIS_tag"><td class="proc" /></td><td>Automatisch vorannotieren</td></tr>
   <tr id="tIS_import"><td class="proc" /></td><td>Importieren abschließen</td></tr>
   </table>
   <div id="tIS_progress"></div>
   </div>
  </div>
   
  <div class="panel" id="fileGroup">
    <h4 class="projectname">Projektname</h4>
        <div>
		<table class="fileList">
			<tr class="fileTableHeadLine">
				<th></th>
				<th>Dateiname</th>
<!--				<th class="tagStatusPOS">POS</th>
				<th class="tagStatusMorph">Morph</th>
				<th class="tagStatusNorm">Norm.</th>
-->
				<th colspan="2">zuletzt bearbeitet am/von</th>
				<th colspan="2">erstellt am/von</th>
				<th></th>
				<th></th>
		    </tr>

		<!-- this table is filled by file.listFiles() @ file.js -->
		</table>
        </div>
  </div>

  <div id="fileExportPopup">
    <p>In welchem Format möchten Sie die Datei exportieren?</p>
		<p>
		<label for="exportformat">Exportformat: </label>
		<select id="fileExportFormat" name="exportformat" size="1">
			<option value="cora">CorA-XML</option>
                        <option value="hist" selected="selected">Neues CorA-XML (empfohlen)</option>
		</select>
		</p>
  <p></p>
  </div>

  <div id="fileImportPopup">
    <p></p>
    <p><textarea cols="80" rows="10" readonly="readonly"></textarea></p>
  </div>

  <div id="fileImportForm" class="limitedWidth">
	<form action="request.php" id="newFileImportForm" method="post" accept-charset="utf-8" enctype="multipart/form-data">
  <p class="error_text">Bitte wählen Sie eine Datei zum Importieren aus!</p>

		<p>
		<label for="xmlFile">Datei: </label>
		<input type="file" name="xmlFile" data-required="" />
		</p>

		<p>
		<label for="project">Projekt: </label>
		<select name="project" size="1">
			<?php foreach($projects as $set):?>
			<option value="<?php echo $set['id'];?>"><?php echo "{$set['name']}";?></option>
			<?php endforeach;?>
		</select>
		</p>

  <p>Die folgenden Felder müssen nicht ausgefüllt werden, falls die entsprechenden Informationen bereits in der XML-Datei enthalten sind.</p>

		<p>
		<label for="xmlName">Name: </label>
		<input type="text" name="xmlName" placeholder="(Dokumentname)" size="30" />
		</p>

		<p>
		<label for="sigle">Sigle: </label>
		<input type="text" name="sigle" placeholder="(Sigle &ndash; optional)" size="30" />
		</p>

		<p>
		<label for="tagset">Tagset: </label>
		<select name="tagset" size="1">
			<option value="">Angabe aus XML-Datei übernehmen</option>
			<?php foreach($tagsets as $set):?>
			<option value="<?php echo $set['shortname'];?>"><?php echo $set['longname'];?></option>
			<?php endforeach;?>
		</select>
		</p>


		<p><input type="hidden" name="action" value="importXMLFile" /></p>
		<p style="text-align:right;">
                  <input type="submit" value="Importieren &rarr;" />
                </p>
	</form>
  </div>

  <div id="fileImportTransForm" class="limitedWidth">
	<form action="request.php" id="newFileImportTransForm" method="post" accept-charset="utf-8" enctype="multipart/form-data">
  <p class="error_text">Bitte wählen Sie eine Datei zum Importieren aus!</p>

		<p>
		<label for="transFile">Datei: </label>
		<input type="file" name="transFile" data-required="" />
		</p>

		<p>
		<label for="fileEnc">Encoding: </label>
		<select name="fileEnc" size="1">
                   <option value="utf-8">UTF-8 (Unicode)</option>
                   <option value="iso-8859-1">ISO-8859-1 (Latin 1)</option>
                   <option value="IBM850">MS-DOS (IBM-850)</option>
		</select>
		</p>

		<p>
		<label for="project">Projekt: </label>
		<select name="project" size="1">
			<?php foreach($projects as $set):?>
			<option value="<?php echo $set['id'];?>"><?php echo "{$set['name']}";?></option>
			<?php endforeach;?>
		</select>
		</p>

		<p>
		<label for="transName">Name: </label>
		<input type="text" name="transName" placeholder="(Dokumentname)" size="30" data-required="" />
		</p>

		<p>
		<label for="sigle">Sigle: </label>
		<input type="text" name="sigle" placeholder="(Sigle &ndash; optional)" size="30" />
		</p>

		<p>
		<label for="tagset">Tagset: </label>
		<select name="tagset" size="1">
			<?php foreach($tagsets as $set):?>
			<option value="<?php echo $set['shortname'];?>"><?php echo $set['longname'];?></option>
			<?php endforeach;?>
		</select>
		</p>


		<p><input type="hidden" name="action" value="importTransFile" /></p>
		<p style="text-align:right;">
                  <input type="submit" value="Importieren &rarr;" />
                </p>
	</form>
  </div>

</div>

<?php
  endif;
?>
</div>