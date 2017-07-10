BEGIN TRANSACTION;
INSERT INTO `links` (id,title,url) VALUES (1,'Example Link','http://www.example.com'),
 (2,'Google','https://www.google.com'),
 (3,'Yahoo','http://www.yahoo.com'),
 (4,'Github','https://github.com'),
 (5,'Wikipedia','https://en.wikipedia.org');
INSERT INTO `cats` (id,parent,title) VALUES (1,0,'Default Categoy'),
 (4,0,'Category #2'),
 (5,1,'Subcategory - Default'); INSERT INTO `cat_membership` (linkID,catID) VALUES (1,1),
 (2,1),
 (4,1),
 (5,1);
COMMIT;
