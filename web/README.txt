Check this variables for files (they are stored in mysql):

1. In php.ini:

; Maximum amount of memory a script may consume (128MB)
; http://php.net/memory-limit
memory_limit = 256M

; Maximum size of POST data that PHP will accept.
; http://php.net/post-max-size
post_max_size = 200M

; Maximum allowed size for uploaded files.
; http://php.net/upload-max-filesize
upload_max_filesize = 200M

; Maximum number of files that can be uploaded via a single request
max_file_uploads = 20

2. In my.cnf (section [mysqld]):

max_allowed_packet          = 200M
