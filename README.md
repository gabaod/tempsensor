# tempsensor
temp and humidity sensor with email function from an esp32

![humidityapp](https://github.com/user-attachments/assets/3dfdba2f-7410-49ab-a3b1-40c7f3ae436b)

mysql> describe sensor_data;
+-------------+----------+------+-----+-------------------+-------------------+
| Field       | Type     | Null | Key | Default           | Extra             |
+-------------+----------+------+-----+-------------------+-------------------+
| id          | int      | NO   | PRI | NULL              | auto_increment    |
| temperature | float    | NO   |     | NULL              |                   |
| humidity    | float    | NO   |     | NULL              |                   |
| vpd         | float    | NO   |     | NULL              |                   |
| timestamp   | datetime | YES  |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
+-------------+----------+------+-----+-------------------+-------------------+


mysql> describe trigger_status;
+-----------------+------------+------+-----+-------------------+-----------------------------------------------+
| Field           | Type       | Null | Key | Default           | Extra                                         |
+-----------------+------------+------+-----+-------------------+-----------------------------------------------+
| id              | int        | NO   | PRI | NULL              | auto_increment                                |
| last_trigger_id | int        | NO   |     | 0                 |                                               |
| is_triggered    | tinyint(1) | NO   |     | 0                 |                                               |
| updated_at      | timestamp  | YES  |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |
| manual_override | tinyint(1) | NO   |     | 0                 |                                               |
+-----------------+------------+------+-----+-------------------+-----------------------------------------------+

1. Edit humidity.ino and upload to your esp32
2. Run a webserver and mysql server - this currently is defined by ip 10.0.10.2
3. Upload all the .php files to your webserver
4. Edit 100credentials.php to your info
5. Create mysql tables with the info above.
6. goto http://10.0.10.2/100index.php to view your data
7. Click enable email if you would like email notifications when data is out of range
