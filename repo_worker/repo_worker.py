#!/usr/bin/env python

import os, sys
import re
import SocketServer
import MySQLdb
import subprocess
import threading

# database connection information
DB_HOST   = 'localhost'
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
cursor = None
    
def synchronized(func):
    func.__lock__ = threading.Lock()
    def synced_func(*args, **kws):
        with func.__lock__:
            return func(*args, **kws)
    return synced_func

# current directory should be WORK_DIR
def cloneOrPullRepo(repoName, branch = "master"):
    if not os.path.isdir(repoName):
        subprocess.call(["git", "clone", repoAddress(repoName)])
        os.chdir(repoName)
    else:
        os.chdir(repoName)
        subprocess.call(["git", "reset", "--hard"])  # we don't need any local changes
        subprocess.call(["git", "fetch"])
        subprocess.call(["git", "pull", "-r", "origin", branch])

    subprocess.call(["git", "config", "user.name", "Check System"])
    subprocess.call(["git", "config", "user.email", "checksys@no-real-existing-host.com"])

def init():
    global cursor

    subprocess.call(["ssh-add", "-D"])
    subprocess.call(["ssh-add", id_rsa])

    if not os.path.isdir(WORK_DIR):
        os.makedirs(WORK_DIR)
    os.chdir(WORK_DIR)

    cloneOrPullRepo(GITOLITE_ADMIN_REPONAME)

    with open("conf/gitolite.conf", "r") as conf:
        confContents = conf.read()
    newConfContents = confContents

    # check that '@teachers' group exists
    if re.search(r'@teachers\s*=', confContents) == None:
        cursor.execute("SELECT id FROM users WHERE isTeacher = 1 ORDER BY id")
        result = cursor.fetchall()
        teachersList = [("u%d" % row[0]) for row in result]
        newConfContents = "@teachers = admin " + " ".join(teachersList) + "\n\n" + newConfContents
    
    # check that 'tasks' repository exists
    if re.search(r'repo\s+tasks[^\w]', confContents) == None:
        newConfContents += "\nrepo    tasks\n        RW+     =   @teachers\n"
    
    if confContents != newConfContents:
        with open("conf/gitolite.conf", "w") as conf:
            conf.write(newConfContents)
        subprocess.call(["git", "commit", "-am", "Fix config (@teachers group, tasks repo)"])
        subprocess.call(["git", "push", "origin", "master"])

def setOperationStatus(opId, status, message):
    global cursor
    cursor.execute("UPDATE repo_operations SET done = %d, repo_worker_message = \"%s\", processed = NOW() WHERE id = %d" % (status, message, opId))

def setOperationCompleted(opId, message):
    message = "DONE: " + message
    setOperationStatus(opId, 1, message)
    print message

def setOperationFailed(opId, message):
    message = "FAILED: " + message
    setOperationStatus(opId, 2, message)
    print message

def exceptionInfo():
     return ' [error: %s]' % sys.exc_info()[1]

@synchronized
def update():
    global cursor
    
    cursor.execute("SELECT id, command, for_user_id, param1, param2, param3, param4, param5 FROM repo_operations WHERE done = 0 ORDER BY id")
    result = cursor.fetchall()
    for row in result:
        id, command, for_user_id, param1, param2, param3, param4, param5 = row

        if command == "createrepo":
            # param1 - repo name
            # param2 - public key
            opMsg = "create repo for %s" % param1
            pubkeyFilename = "keydir/%s.pub" % param1
            if param1 != "gitolite-admin" and param1 != "testing" and param1 != "tasks" and (not os.path.isfile(pubkeyFilename)):
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
                    cursor.execute("UPDATE users SET git = \"%s\" WHERE id = %d" % (repoAddress(param1), for_user_id))
                    setOperationCompleted(id, opMsg)
                except:
                    setOperationFailed(id, opMsg + exceptionInfo())
            else:
                setOperationFailed(id, opMsg + ' [invalid repository name]')

        elif command == "newpubkey":
            # param1 - repo name
            # param2 - public key
            opMsg = "new public key for %s" % param1
            pubkeyFilename = "keydir/%s.pub" % param1
            try:
                if os.path.isfile(pubkeyFilename):
                    with open(pubkeyFilename, "r") as pubkey:
                        oldPubkey = pubkey.read()
                    if oldPubkey != param2:
                        with open(pubkeyFilename, "w") as pubkey:
                            pubkey.write(param2)
                else:
                    with open(pubkeyFilename, "w") as pubkey:
                        pubkey.write(param2)
                    subprocess.call(["git", "add", pubkeyFilename])
                subprocess.call(["git", "commit", "-am", "New public key for user [%s]" % param1])
                subprocess.call(["git", "push", "origin", "master"])
                setOperationCompleted(id, opMsg)
            except:
                setOperationFailed(id, opMsg + exceptionInfo())

        elif command == "newteacher":
            # param1 - teacher's git account
            opMsg = "add %s to @teachers" % param1
            try:
                with open("conf/gitolite.conf", "r") as conf:
                    confContents = conf.read()
                confContents = re.sub(r'(@teachers\s*=[^\n]*)', r'\1 %s' % param1, confContents)
                with open("conf/gitolite.conf", "w") as conf:
                    conf.write(confContents)
                subprocess.call(["git", "commit", "-am", "Add new teacher [%s] to @teachers group" % param1])
                subprocess.call(["git", "push", "origin", "master"])
                setOperationCompleted(id, opMsg)
            except:
                setOperationFailed(id, opMsg + exceptionInfo())

        elif command == "updatetasks":
            # no params
            opMsg = "update tasks"
            try:
                os.chdir('../')
                cloneOrPullRepo('tasks')
                l = os.listdir('.')
                tl = []
                for pth in l:
                    if os.path.isdir(pth + '/problem') and os.path.isdir(pth + '/solution') and os.path.isdir(pth + '/tests'):
                        cursor.execute("INSERT IGNORE INTO tasks (name) VALUES (\"%s\")" % pth)
                setOperationCompleted(id, opMsg)
            except:
                setOperationFailed(id, opMsg + exceptionInfo())
            os.chdir('../' + GITOLITE_ADMIN_REPONAME)

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
