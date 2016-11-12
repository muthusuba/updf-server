#! /usr/bin/python
# Python script for converting documents to pdf
# using libreoffice/openoffice.org
#
# Copyright (C) Muthu Subramanian K <muthusuba@gmail.com> 2011-2016
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/
#
# DB: CREATE TABLE updf(id integer primary key autoincrement, deviceid, uniquekey, ext, status integer, ipaddr, time);
# status:
#       0: Uploading
#       1: Uploaded
#       2: Converting
#       3: Converted
#

import sys
import sqlite3
import os
import time
import signal
import stat

STATUS_UPLOADING    = 0
STATUS_UPLOADED     = 1
STATUS_CONVERTING   = 2
STATUS_CONVERTED    = 3
STATUS_FAILED       = 4

# Initializations
#libo="/usr/lib64/ooo3/program/soffice "
dbpath = "/tmp/updf.sql" #sys.argv[0][:sys.argv[0].rfind("/")]+"/updf.sql"
libo="./soffice.bin "
dirprefix = "/tmp/updf/"
ebook = "ebook-convert "
sqlconn = sqlite3.connect(dbpath)
sqlcur = sqlconn.cursor()
sleeplong = True
handlesignals = [ signal.SIGTERM, signal.SIGINT ] 

def handleClose(signum, frame):
    print "Closing Converter.Bye Bye."
    # Close Sql Connections
    sqlcur.close()
    sqlconn.close()
    exit(0)

def getNext():
    ret = []
    sqlcur.execute('select id, uniquekey,ext from updf where id in (select min(id) from updf where status = ?)',(str(STATUS_UPLOADED),))
    ret = sqlcur.fetchone()
    if ret:
        sqlcur.execute('update updf set status=? where id=?',(str(STATUS_CONVERTING), ret[0]))
    else:
        print "None found."
    sqlconn.commit()
    return ret

def setComplete(id, status):
    sqlcur.execute('update updf set status=? where id=?',(str(status), id))

def convertNext(row):
    ext = str(row[2])
    file = dirprefix+str(row[1])+"."+ext
    command = libo+"-convert-to pdf -outdir "+dirprefix+" "+file
    if ext == "epub" or ext == "mobi":
        command = ebook + file + " " + dirprefix + str(row[1]) + ".pdf --enable-heuristics"
    ret = os.system(command)
    try:
        os.chmod(file, stat.S_IWRITE|stat.S_IXGRP|stat.S_IRWXO)
        os.unlink(file)
    except:
        pass
    if not os.path.exists(dirprefix+str(row[1])+".pdf"):
        return STATUS_FAILED
    return STATUS_CONVERTED

idleCountForOldFiles = 0
def removeOldFiles(force):
    global idleCountForOldFiles
    idleCountForOldFiles = idleCountForOldFiles + 1
    if idleCountForOldFiles < 1000 and not force:
        return
    idleCountForOldFiles = 0
    files = os.listdir(dirprefix)
    for file in files:
        if file.find(".pdf") >= 0:
            file = dirprefix+file
            # Check time stamp
            if (time.time() - os.path.getctime(file)) > 600:
                try:
                    os.chmod(file, stat.S_IWRITE|stat.S_IXGRP|stat.S_IRWXO)
                    os.unlink(file)
                except:
                    pass

# Main Functions ---
for sig in handlesignals:
    signal.signal(sig, handleClose)
while True:
    next = getNext()
    if not next:
        # time.sleep(30+sleeplong*300)
        time.sleep(1+sleeplong*3) # For debug
        sleeplong = True
        removeOldFiles(True)
        continue
    setComplete(next[0], convertNext(next))
    sleeplong = False
    removeOldFiles(False)
