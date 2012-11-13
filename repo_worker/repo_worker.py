#!/usr/bin/env python

import os, sys
import SocketServer
import MySQLdb
import subprocess
import threading

# database connection information
DB_HOST   = '178.162.101.7'
DB_USER   = 'checksys'
DB_PASSWD = 'eEG5XcsRV3CVtANQ'
DB_NAME   = 'checksys'

GIT_HOST = 'localhost'  # 

TCP_PORT = 10599  # port to listen

WORK_DIR = "data"  # where to store repositories

def repoAddress(repoName):
    return "git@localhost:" + repoName + ".git"

# just initializing some global variables
db = None
cursor = None
    
def synchronized(func):
    func.__lock__ = threading.Lock()
    def synced_func(*args, **kws):
        with func.__lock__:
            return func(*args, **kws)
    return synced_func

def init():
    if not os.path.isdir(WORK_DIR):
        os.makedirs(WORK_DIR)

    if not os.path.isdir(WORK_DIR + "/gitolite-admin"):
        os.chdir(WORK_DIR)
        subprocess.call(["ssh-add", "-D"])
        # now need to auth
        print subprocess.check_output(["git", "clone", repoAddress("gitolite-admin")])

@synchronized
def update():
    global db
    global cursor
    
    cursor.execute("""SELECT id, command, param1, param2, param3, param4, param5 FROM repo_operations WHERE done = 0 ORDER BY id""")
    result = cursor.fetchall()
    for row in result:
        id, command, param1, param2, param3, param4, param5 = row

        if command == "createrepo":
            pass

class MyTCPHandler(SocketServer.BaseRequestHandler):
    def handle(self):
        data = self.request.recv(1024)
        if data == "update":
            update()

if __name__ == "__main__":
    db = MySQLdb.connect(host = DB_HOST, user = DB_USER, passwd = DB_PASSWD, db = DB_NAME)
    cursor = db.cursor()

    init()
    update()

    server = SocketServer.TCPServer(('', TCP_PORT), MyTCPHandler)
    try:
        print "Started repo_worker"
        server.serve_forever()
    except KeyboardInterrupt:
        print "Halting repo_worker"

    db.close()
