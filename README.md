# merit card

![Logo](https://github.com/meritcard/meritcard/blob/master/doc/main_dialog.png)

The merit system keeps track of good deeds (merits) and events of undesired behaviour (demerits). It can be used for both, roleplaying (e. g. a school) and real work (e. g. sim maintainance).

# Development status

This is an early prototyp. It is useable in the sense that merits and demerits can be added and listed. 

But there are many questions to answer. Among them:

- Permissions
  - Who is allowed to add an entry?
  - Who is allowed to delete an entry?
  - Integration with existing management database to import roles?
- What shall the message and dialog boxes look like?
- Is the system actually helpful or hurting RP?
- What are usefule extensions? (RLV?, boarders?, extra chores?)

# Privacy

All the communication between the in world object and the backend is done through Second Life servers. Therefore IP address of an agent is never visible to the system.

The listen channel for dialog boxes and input boxes are randomly picked by the in-world object at the high end of the number range. The server cannot specify a channel and the script will never listen on the local chat channel 0.


# Technology

The system consists of two parts:
- an in-world object worn by participants
- a server backend written in PHP.


## Setup

Setup the in-world part:

- Create an object in Second Life and add the [merit.lsl](https://github.com/meritcard/meritcard/blob/master/lsl/merit.lsl) script to it
- At the beginning of the script, change the variables SERVER and SECRET
- Set the script in the object to not modifiable (this is required to protect the SECRET)
- Change the object to something nice. For example a badge worn on chest, or a transparent tube worn on head.

Setup the server backend:

- Copy the content of the [php folder](https://github.com/meritcard/meritcard/tree/master/php) to your website
- Create an empty database
- Import database.sql into the database to create the table structure
- Create a file config.php in your website directy and configure the database connection

``` php
$CONFIG_SECRET = 'changeme';

$CONFIG_DB_DSN = 'mysql:dbname=meritdb;host=127.0.0.1';
$CONFIG_DB_USER = 'merituser';
$CONFIG_DB_PASSWORD = 'meritpassword';
```
