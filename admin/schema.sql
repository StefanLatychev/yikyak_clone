-- YikYak Clone Schema
-- built for Postgres database
DROP TABLE appuser CASCADE;
DROP TABLE appuser_passwords CASCADE;
DROP TABLE notes CASCADE;
DROP TABLE notes_reported CASCADE;

CREATE TABLE active_api_session_keys (
	-- Active API session keys representing authenticated api users
	session_key		VARCHAR(25),
	user_id			BIGINT,
	PRIMARY KEY(session_key);
);

CREATE TABLE appuser_passwords (
	-- User passwords
	user_id 		BIGINT,
	password 		VARCHAR(100) 	NOT NULL,
	PRIMARY KEY (userid)
);

CREATE TABLE appuser (
	-- User data
	id 			BIGSERIAL,
	admin			BOOLEAN		NOT NULL,
	email 			VARCHAR(50) 	NOT NULL UNIQUE, 
	phone_number		VARCHAR(15)	UNIQUE,
	join_date 		TIMESTAMP 	NOT NULL,
	validated 		BOOLEAN 	NOT NULL,
	last_login 		TIMESTAMP 	NOT NULL,
	PRIMARY KEY (id)
);

CREATE TABLE notes (
	-- Yak information
	id			BIGSERIAL,
	userid			BIGINT		NOT NULL,	--Posted by
	time			TIMESTAMP	NOT NULL,	--Time posted
	location_latitude	REAL		NOT NULL,
	location_longitude	REAL		NOT NULL,
	upvotes			INTEGER		NOT NULL,	--Note 'rating'
	message			TEXT		NOT NULL,
	PRIMARY KEY (id)
);

CREATE TABLE notes_reported (
	-- User reported notes
	id			BIGSERIAL,
	note_id			BIGINT		NOT NULL,
	user_id			BIGINT		NOT NULL,	--Reporter id
	reason			TEXT		NOT NULL,	--Report reason
	PRIMARY KEY (id)
);
