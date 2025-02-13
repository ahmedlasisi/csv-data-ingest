CREATE DATABASE IF NOT EXISTS brokerdb_one;
CREATE USER IF NOT EXISTS 'brokerdb_one'@'%' IDENTIFIED BY 'brokerdb_one';
GRANT ALL PRIVILEGES ON brokerdb_one.* TO 'brokerdb_one'@'%';
FLUSH PRIVILEGES;
