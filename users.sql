DROP TABLE IF EXISTS recoverpassword;
CREATE TABLE recoverpassword (
  idrecoverpassword bigint(20) NOT NULL,
  iduser bigint(20) NOT NULL,
  secret char(128) NOT NULL,
  time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS session;
CREATE TABLE session (
  idsesion bigint(20) NOT NULL,
  iduser bigint(20) NOT NULL,
  stringlogin char(128) NOT NULL,
  timestart timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  timeend timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS trysession;
CREATE TABLE trysession (
  iduser bigint(20) NOT NULL,
  time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS type_id;
CREATE TABLE type_id (
  idtype_id int(2) NOT NULL,
  type_id varchar(255) NOT NULL,
  status int(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO type_id (idtype_id, type_id, status) VALUES
(1, 'Cedula de ciudadania', 1),
(2, 'Cedula de extranjeria', 1);

DROP TABLE IF EXISTS user;
CREATE TABLE user (
  iduser bigint(20) NOT NULL,
  fname varchar(100) NOT NULL,
  lname varchar(100) NOT NULL,
  type_id int(2) NOT NULL,
  number_id bigint(20) NOT NULL,
  email varchar(100) NOT NULL,
  passdb varchar(40) NOT NULL,
  status int(1) NOT NULL DEFAULT '0',
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO user (iduser, fname, lname, type_id, number_id, email, passdb, status, created) VALUES
(1, 'Super', 'Admin', 1, 123456789, 'admin@mail.com', 'f7c3bc1d808e04732adf679965ccc34ca7ae3441', 1, '2019-01-01 00:00:00');

DROP TABLE IF EXISTS userinstitution;
CREATE TABLE userinstitution (
  iduserinstitution bigint(20) NOT NULL,
  iduser bigint(20) NOT NULL,
  institution varchar(255) NOT NULL,
  position varchar(255) NOT NULL,
  status int(1) NOT NULL DEFAULT '1',
  created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE recoverpassword
  ADD PRIMARY KEY (idrecoverpassword), ADD KEY iduser (iduser);

ALTER TABLE session
  ADD PRIMARY KEY (idsesion), ADD KEY iduser (iduser);

ALTER TABLE trysession
  ADD KEY iduser (iduser);

ALTER TABLE type_id
  ADD PRIMARY KEY (idtype_id);

ALTER TABLE user
  ADD PRIMARY KEY (iduser), ADD UNIQUE KEY email (email), ADD KEY type_id (type_id);

ALTER TABLE userinstitution
  ADD PRIMARY KEY (iduserinstitution), ADD KEY iduser (iduser);


ALTER TABLE recoverpassword
  MODIFY idrecoverpassword bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE session
  MODIFY idsesion bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE type_id
  MODIFY idtype_id int(2) NOT NULL AUTO_INCREMENT;
ALTER TABLE user
  MODIFY iduser bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE userinstitution
  MODIFY iduserinstitution bigint(20) NOT NULL AUTO_INCREMENT;

ALTER TABLE recoverpassword
ADD CONSTRAINT recuperarclave_ibfk_1 FOREIGN KEY (iduser) REFERENCES user (iduser);

ALTER TABLE session
ADD CONSTRAINT sesion_ibfk_1 FOREIGN KEY (iduser) REFERENCES user (iduser);

ALTER TABLE trysession
ADD CONSTRAINT intentossesion_ibfk_1 FOREIGN KEY (iduser) REFERENCES user (iduser);

ALTER TABLE user
ADD CONSTRAINT user_ibfk_1 FOREIGN KEY (type_id) REFERENCES type_id (idtype_id);

ALTER TABLE userinstitution
ADD CONSTRAINT userinstitution_ibfk_1 FOREIGN KEY (iduser) REFERENCES user (iduser);
