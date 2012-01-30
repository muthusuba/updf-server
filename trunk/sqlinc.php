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

$sqliteDB = new SQLite3('/tmp/updf.sql');

function getDB() {
    return $GLOBALS['sqliteDB'];
}

// 0: Processing
// -1: Complete
// -2: Error in conversion
// -3: Unknown Error
// positive number: In queue slot
function getStatus($uniquekey) {
    global $sqliteDB;
    $ret = -2;
    $query = "select id,status from updf where uniquekey='".SQLite3::escapeString($uniquekey)."';";
    $res = $sqliteDB->query($query);
    $res = $res->fetchArray();
    if(!$res)
        return -3;
    $qid = intval($res[0]);
    $status = intval($res[1]);
    if($status == 1) { // Waiting
        $query = "select min(id) from updf where status=1;";
        $res = $sqliteDB->querySingle($query);
        if(!$res)
            return -3;
        return $qid - intval($res);
    }
    else if($status == 2) // Converting
        return 0;
    else if($status == 4) // Failed
        return -2;
    return -1;  // Success: status = 3
}

function getStatusXML($uniquekey) {
    return "<status>".getStatus($uniquekey)."</status>";
}
?>
