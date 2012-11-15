repo_worker.py выполняет операции с репозиториями, которые записаны в БД в таблице repo_operations.
Настройки подключения к БД и прочее в самом коде скрипта.

update.secondary - хук для gitolite. В нём тоже надо прописать настройки подключения к БД, затем положить
в /home/<gituser>/.gitolite/hooks/common, сделать на него chmod +x и выполнить gl-setup под <gituser>.

Как по-простому ставить gitolite на ubuntu:
http://asmodeus.com.ua/library/programing/git/gitolite.html
