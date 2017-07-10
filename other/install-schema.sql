BEGIN TRANSACTION;
CREATE TABLE "links" (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title` TEXT NOT NULL,
    `url` TEXT NOT NULL );
CREATE TABLE `cats` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `parent` INTEGER NOT NULL DEFAULT 0,
    `title` INTEGER );
CREATE TABLE `cat_membership` (
    `linkID` INTEGER NOT NULL,
    `catID` INTEGER NOT NULL,
    PRIMARY KEY(`linkID`,`catID`) );
COMMIT;
