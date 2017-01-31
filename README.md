Here is an invite and join page for use with PlexAuth. https://github.com/hjone72/PlexAuth

These are customized for my personal system. You will need to read through the code and find the places that you need to change.

You will also need to create yourself a DB of some kind and create some SQL functions.

At a later date I will probably come clean this all up. As ugly as it is, here you go.

My DB structure is as follows:

```
+-----------+-----------------+------+-----+---------+----------------+
|            TABLE: Invites                                           |
+-----------+-----------------+------+-----+---------+----------------+
| Field     | Type            | Null | Key | Default | Extra          |
+-----------+-----------------+------+-----+---------+----------------+
| id        | int(6) unsigned | NO   | PRI | NULL    | auto_increment |
| code      | varchar(6)      | NO   |     | NULL    |                |
| genDate   | varchar(10)     | NO   |     | NULL    |                |
| genUser   | varchar(50)     | NO   |     | NULL    |                |
| claimedBy | varchar(50)     | YES  |     | NULL    |                |
+-----------+-----------------+------+-----+---------+----------------+

+------------+-----------------+------+-----+---------+----------------+
|            TABLE: Users                                              |
+------------+-----------------+------+-----+---------+----------------+
| Field      | Type            | Null | Key | Default | Extra          |
+------------+-----------------+------+-----+---------+----------------+
| id         | int(6) unsigned | NO   | PRI | NULL    | auto_increment |
| username   | varchar(50)     | NO   |     | NULL    |                |
| end_date   | varchar(30)     | NO   |     | NULL    |                |
| info       | varchar(10)     | YES  |     | NULL    |                |
| invites    | int(3)          | YES  |     | NULL    |                |
+------------+-----------------+------+-----+---------+----------------+
```

Screenshots:
![alt tag](https://raw.githubusercontent.com/hjone72/PlexAuth_Pages/master/screenshots/gen_code.JPG)
![alt tag](https://raw.githubusercontent.com/hjone72/PlexAuth_Pages/master/screenshots/code.JPG)
![alt tag](https://raw.githubusercontent.com/hjone72/PlexAuth_Pages/master/screenshots/join1.JPG)
![alt tag](https://raw.githubusercontent.com/hjone72/PlexAuth_Pages/master/screenshots/join2.JPG)
![alt tag](https://raw.githubusercontent.com/hjone72/PlexAuth_Pages/master/screenshots/join3.JPG)

Thanks.
