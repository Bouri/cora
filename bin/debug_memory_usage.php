<?php 
/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */ ?>
<?php
$CORA_DIR = include 'cora_config_webdir.php';
$DEVNULL = fopen("/dev/null", "a");
require_once "{$CORA_DIR}/lib/cfg.php";
require_once "{$CORA_DIR}/lib/connect.php";
require_once "{$CORA_DIR}/lib/exporter.php";
$dbi = new DBInterface(Cfg::get('dbinfo'));
$exp = new Exporter($dbi);

memprof_enable();
//$data = $dbi->openFile(149);
//$exp->export(149, ExportType::CoraXML, array(), $DEVNULL);
$data = $dbi->getAllModerns(149);
memprof_dump_pprof(STDOUT);

fwrite(STDERR, "Memory usage:      ".memory_get_usage()."\n");
fwrite(STDERR, "Memory peak usage: ".memory_get_peak_usage()."\n");

?>
