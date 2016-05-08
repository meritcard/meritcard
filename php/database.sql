-- agent (avatar) information
CREATE TABLE IF NOT EXISTS agent (
  id INTEGER NOT NULL AUTO_INCREMENT,
  username VARCHAR(100),
  displayname VARCHAR(100),
  keyid VARCHAR(50),
  role  VARCHAR(50),
  PRIMARY KEY(id),
  UNIQUE INDEX(keyid),
  UNIQUE INDEX(username)
) ENGINE=INNODB;

INSERT IGNORE INTO agent(id, username, displayname, keyid) VALUES (1, 'system', 'system', 'system');


-- transaction are used as identifier for changes (e. g. creating or deleting merits)
CREATE TABLE IF NOT EXISTS transaction (
  id INTEGER NOT NULL AUTO_INCREMENT,
  agent_id INTEGER  NOT NULL,
  transaction_timestamp DATETIME,
  PRIMARY KEY(id),
  FOREIGN KEY(agent_id) REFERENCES agent(id)
) ENGINE=INNODB;
INSERT IGNORE INTO transaction(id, agent_id, transaction_timestamp) VALUES (1, 1, '1000-01-01 00:00:01');
INSERT IGNORE INTO transaction(id, agent_id, transaction_timestamp) VALUES (2, 1, '9999-12-31 23:59:59');


-- merits and demerits
CREATE TABLE IF NOT EXISTS merit (
  id INTEGER NOT NULL AUTO_INCREMENT,
  target_agent_id INTEGER NOT NULL,
  created_transaction_id INTEGER NOT NULL,
  deleted_transaction_id INTEGER NOT NULL,
  merit VARCHAR(10),
  message VARCHAR(1000),
  PRIMARY KEY(id),
  FOREIGN KEY(target_agent_id) REFERENCES agent(id), 
  FOREIGN KEY(created_transaction_id) REFERENCES transaction(id), 
  FOREIGN KEY(deleted_transaction_id) REFERENCES transaction(id) 
) ENGINE=INNODB;


-- session keep track of multi step interactions (e. g. opening a dialog box and processing a reply with a textbox)
CREATE TABLE IF NOT EXISTS session (
  id INTEGER NOT NULL AUTO_INCREMENT,
  target_agent_id INTEGER,
  acting_agent_id INTEGER,
  identifier INTEGER,
  status VARCHAR(255),
  details VARCHAR(1000),
  PRIMARY KEY(id),
  UNIQUE INDEX(identifier),
  FOREIGN KEY(target_agent_id) REFERENCES agent(id),
  FOREIGN KEY(acting_agent_id) REFERENCES agent(id) 
) ENGINE=INNODB;



-- group
-- permission
-- permission_group

-- pending(account, command, params)
-- events(account, region, command, params)
