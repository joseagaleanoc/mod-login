CREATE TABLE IF NOT EXISTS trysession (
  iduser bigint(20) NOT NULL,
  time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS recoverpassword (
  iduser bigint(20) NOT NULL,
  secret char(128) NOT NULL,
  time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS session (
  idsesion bigint(20) NOT NULL,
  iduser bigint(20) NOT NULL,
  stringlogin char(128) NOT NULL,
  timestart timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  timeend timestamp NULL DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS user (
  iduser bigint(20) NOT NULL,
  fname varchar(100) NOT NULL,
  lname varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  clave varchar(40) NOT NULL,
  estado int(1) NOT NULL,
  rol int(1) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;


ALTER TABLE trysession
  ADD KEY iduser (iduser);

ALTER TABLE recoverpassword
  ADD KEY iduser (iduser);

ALTER TABLE session
  ADD PRIMARY KEY (idsesion), ADD KEY iduser (iduser);

ALTER TABLE user
  ADD PRIMARY KEY (iduser), ADD UNIQUE KEY email (email);


ALTER TABLE session
  MODIFY idsesion bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=29;
ALTER TABLE user
  MODIFY iduser bigint(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;

ALTER TABLE trysession
ADD CONSTRAINT intentossesion_ibfk_1 FOREIGN KEY (iduser) REFERENCES user (iduser);

ALTER TABLE recoverpassword
ADD CONSTRAINT recuperarclave_ibfk_1 FOREIGN KEY (iduser) REFERENCES user (iduser);

ALTER TABLE session
ADD CONSTRAINT sesion_ibfk_1 FOREIGN KEY (iduser) REFERENCES user (iduser);

INSERT INTO user (iduser, fname, lname, email, clave, estado, rol) VALUES
(1, 'Super', 'Admin', 'admin@mail.com', 'f7c3bc1d808e04732adf679965ccc34ca7ae3441', 1, 1);