<?php

# Database Connection Information
$db_server = 'localhost';
$db_user   = 'checksys';
$db_passwd = 'eEG5XcsRV3CVtANQ';
$db_name   = 'checksys';

# (!) Default admin can authorize in checksys web client with login equal to $db_user and password equal to $db_passwd.
# (!) Default admin has rights to create groups and make teachers.

# Upload Settings
$max_upload_file_size = 10 * 1024 * 1024;

$repo_worker_host = gethostbyname("localhost");
$repo_worker_port = 10599;

?>
