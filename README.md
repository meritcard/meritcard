# merit

![Logo](https://github.com/jonnwilliam/merit/blob/master/doc/main_dialog.png)

The merit system keeps track of good deeds (merits) and events of undesired behaviour (demerits). It can be used for both, roleplaying (e. g. a school) and real work (e. g. sim maintainance).

The system consists of two parts:
- an in-world object worn by participants
- a server backend written in PHP.


## Setup

Setup the in-world part:

- Create an object in Second Life and add the [merit.lsl](https://github.com/jonnwilliam/merit/blob/master/lsl/merit.lsl) script to it
- At the beginning of the script, change the variables SERVER and SECRET
- Set the script in the object to not modifiable (this is required to protect the SECRET)
- Change the object to something nice. For example a badge worn on chest, or a transparent tube worn on head.

Setup the server backend:

- Copy the content of the [php folder](https://github.com/jonnwilliam/merit/tree/master/php) to your website
- Create an empty database
- Create a file config.php in your website directy and configure the database connection