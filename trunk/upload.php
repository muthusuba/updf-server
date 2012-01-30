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

require('sqlinc.php');
header("Content-type: text/xml");
print('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"); 
print('<updf>\n');
$dirprefix = '/tmp/updf/';
$ipaddress = $_SERVER['REMOTE_ADDR'];

function insertEntry($deviceid, $uniquekey, $ext) {
    $time = SQLite3::escapeString (date("d/M/Y H:i:s"));
    $deviceid = SQLite3::escapeString($deviceid);
    $ext = SQLite3::escapeString($ext);
    $status = "1";
    $query = "insert into updf(deviceid,uniquekey,ext,status,ipaddr, time) values ('".$deviceid."','".$uniquekey."','".$ext."',".$status.",'".$GLOBALS['ipaddress']."','".$time."');";
    getDB()->query($query);
}

function sendError($errno) {
    getDB()->close();
    $errs = array('Unknown Error',
                  'No File Provided',
                  'No Extension Provided',
                  'Error uploading file',
                  'DB Error');
    print("<error>".$errs[$errno]."</error>");
    print("\n</updf>");
    exit(0);
}
if(!isset($_POST['id'])) {
    //sendError(0);
}
//$deviceid=$_POST['id'];
$deviceid="1abcdef";
$deviceid=strtolower($deviceid);
if(preg_match('{[^0-9a-f]}', $deviceid) != 0)
    sendError(0);
if(!$sqliteDB)
    sendError(4);
if(isset($_FILES['userfile'])) {
    $file = $_FILES['userfile'];
    if(isset($file['tmp_name']) && isset($file['error']) && $file['error'] == UPLOAD_ERR_OK) {
        $paths = pathinfo($file['name']);
        $ext = $paths['extension'];
        if(empty($ext) || preg_match('{[^a-zA-Z0-9]}', $ext) != 0 || strlen($ext) > 5 )
            sendError(2);
        $name = uniqid("updf",true);
        mkdir('/tmp/updf',0777);
        if(move_uploaded_file($file['tmp_name'], $dirprefix.$name.".".$ext)) {
            //print("Uploaded to: /tmp/".$name);
            $name = SQLite3::escapeString($name);
            insertEntry($deviceid, $name, $ext);
            print("<fileid>".$name."</fileid>\n");
            print(getStatusXML($name));
        }
        else {
            sendError(3);
        }
    }
    else {
        sendError(3);
    }
}
else {
    sendError(1);
}
$sqliteDB->close();

?>
</updf>
