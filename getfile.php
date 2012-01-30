<?php
/*
Copyright (C) Muthu Subramanian K <muthusuba@gmail.com> 2011

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/
*/

// Get a file or file status
require('sqlinc.php');
if(!isset($_GET['id']) || !isset($_GET['fileid'])) {
    header("Content-type: text/xml");
    print('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
    print("<updf><error>Unknown Error</error></updf>\n");
    exit;
}

$deviceid = $_GET['id'];   // TODO: Use this to cross verify
$uniquekey = $_GET['fileid'];
$status = intval(getStatus($uniquekey));
$file = "/tmp/".$uniquekey.".pdf";
if($status == -1) { // Complete
    if(file_exists($file)) {
        header('Content-description: File Transfer');
        header('Content-type: application/octet-stream');
        header('Content-disposition: attachment; filename='.'converted.pdf');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        if(@readfile($file) != FALSE)
            exit;
    }
}

header("Content-type: text/xml");
print('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
if($status >= 0) // Still in Queue
    print("<updf><status>".$status."</status></updf>\n");
else if($status == -2)
    print("<updf><error>Conversion Error. Try another file</error></updf>");
else if($status == -1)
    print("<updf><error>Timeout Error. \nDid you take a long time to download your file?</error></updf>");
else
    print("<updf><error>Unknown Error.</error></updf>");
?>
