#!/usr/bin/env python

import os, sys
import re
import SocketServer
import MySQLdb
import subprocess
import threading

# database connection information
DB_HOST   = '178.162.101.7'
DB_USER   = 'checksys'
DB_PASSWD = 'eEG5XcsRV3CVtANQ'
DB_NAME   = 'checksys'

GIT_HOST = 'localhost'  # gitolite host
GITOLITE_ADMIN_REPONAME = 'gitolite-admin'

TCP_PORT = 10599  # port to listen

WORK_DIR = "data"  # where to store repositories

def repoAddress(repoName):
    return "git@" + GIT_HOST + ":" + repoName + ".git"

# private key of gitolite admin
id_rsa = "id_rsa"

# just initializing some global variables
db = None
cursor = None
    
def synchronized(func):
    func.__lock__ = threading.Lock()
    def synced_func(*args, **kws):
        with func.__lock__:
            return func(*args, **kws)
    return synced_func

def configRepo():
    subprocess.call(["git", "config", "user.name", "Check System"])
    subprocess.call(["git", "config", "user.email", "checksys@no-real-existing-host.com"])

def init():
    subprocess.call(["ssh-add", "-D"])
    subprocess.call(["ssh-add", id_rsa])

    if not os.path.isdir(WORK_DIR):
        os.makedirs(WORK_DIR)

    if not os.path.isdir(WORK_DIR + "/" + GITOLITE_ADMIN_REPONAME):
        os.chdir(WORK_DIR)
        subprocess.call(["git", "clone", repoAddress(GITOLITE_ADMIN_REPONAME)])
        os.chdir(GITOLITE_ADMIN_REPONAME)
    else:
        os.chdir(WORK_DIR + "/" + GITOLITE_ADMIN_REPONAME)
        subprocess.call(["git", "reset", "--hard"])  # we don't need any local changes

    configRepo()
    
    # check that '@teachers' group exists
    with open("conf/gitolite.conf", "r") as conf:
        confContents = conf.read()
    if re.search(r'@teachers\s*=', confContents) == None:
        with open("conf/gitolite.conf", "w") as conf:
            conf.write("@teachers = admin\n\n" + confContents)
        subprocess.call(["git", "commit", "-am", "Add @teachers group to config"])
        subprocess.call(["git", "push", "origin", "master"])

def setOperationCompleted(opId, message):
    global cursor
    message = "DONE: " + message
    cursor.execute("UPDATE repo_operations SET done = 1, repo_worker_message = \"%s\" WHERE id = %d" % (message, opId))
    print message

def setOperationFailed(opId, message):
    global cursor
    message = "FAILED: " + message
    cursor.execute("UPDATE repo_operations SET done = 2, repo_worker_message = \"%s\" WHERE id = %d" % (message, opId))
    print message

@synchronized
def update():
    global db
    global cursor
    
    cursor.execute("SELECT id, command, param1, param2, param3, param4, param5 FROM repo_operations WHERE done = 0 ORDER BY id")
    result = cursor.fetchall()
    for row in result:
        id, command, param1, param2, param3, param4, param5 = row

        if command == "createrepo":
            opMsg = "create repo for %s" % param1
            pubkeyFilename = "keydir/%s.pub" % param1
            if param1 != "gitolite-admin" and param1 != "testing" and (not os.path.isfile(pubkeyFilename)):
                try:
                    with open("conf/gitolite.conf", "r") as conf:
                        confContents = conf.read()
                    with open("conf/gitolite.conf", "w") as conf:
                        conf.write(confContents + "\nrepo    %s\n        RW+     =   @teachers\n        RW      =   %s\n" % (param1, param1))
                    with open(pubkeyFilename, "w") as pubkey:
                        pubkey.write(param2)
                    subprocess.call(["git", "add", pubkeyFilename])
                    subprocess.call(["git", "commit", "-am", "Create repo for user [%s] and put his public key" % param1])
                    subprocess.call(["git", "push", "origin", "master"])
                    setOperationCompleted(id, opMsg)
                except:
                    setOperationFailed(id, opMsg)
            else:
                setOperationFailed(id, opMsg)
        
        elif command == "newpubkey":
            opMsg = "new public key for %s" % param1
            pubkeyFilename = "keydir/%s.pub" % param1
            if os.path.isfile(pubkeyFilename):
                try:
                    with open(pubkeyFilename, "w") as pubkey:
                        pubkey.write(param2)
                    subprocess.call(["git", "commit", "-am", "New public key for user [%s]" % param1])
                    subprocess.call(["git", "push", "origin", "master"])
                    setOperationCompleted(id, opMsg)
                except:
                    setOperationFailed(id, opMsg)
            else:
                setOperationFailed(id, opMsg)

        else:
            setOperationFailed(id, "unknown operation")

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
