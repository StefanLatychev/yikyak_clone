DROP TABLE appuser CASCADE;
DROP TABLE appuser_passwords CASCADE;
DROP TABLE yaks CASCADE;
DROP TABLE yaks_reported CASCADE;


CREATE TABLE appuser_passwords (
	user_id 		BIGINT,
	password 		VARCHAR(50) 	NOT NULL,
	PRIMARY KEY (userid)
);

CREATE TABLE appuser (
	id 			BIGSERIAL,
	email 			VARCHAR(50) 	NOT NULL UNIQUE, 
	phone_number		VARCHAR(15)	UNIQUE,
	joindate 		TIMESTAMP 	NOT NULL,
	validated 		BOOLEAN 	NOT NULL,
	lastlogin 		TIMESTAMP 	NOT NULL,
	PRIMARY KEY (id)
);

CREATE TABLE yaks (
	id			BIGSERIAL,
	userid			BIGINT		NOT NULL,	--Posted by
	time			TIMESTAMP	NOT NULL,	--Time posted
	location_latitude	REAL		NOT NULL,
	location_longitude	REAL		NOT NULL,
	upvotes			INTEGER		NOT NULL,	--Yak 'rating'
	message			TEXT		NOT NULL,
	PRIMARY KEY (id)
);

CREATE TABLE yaks_reported (
	id			BIGSERIAL,
	yak_id			BIGINT		NOT NULL,
	user_id			BIGINT		NOT NULL,	--Reporter id
	reason			TEST		NOT NULL,	--Report reason
	PRIMARY KEY (id)
);
