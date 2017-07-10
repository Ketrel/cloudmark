<?php
class cloudmarksDB {
/*
    __construct(DBFILE, LOGGING(FALSE))
        Arguments
            DBFILE: valid path to a sqlite3 database file
            LOGGING: boolean value for enabling logging (currently unimplimented)
        Purpose
            Initializes the class and connects to the database
    ------------------------------------
    setCatPageLimit(LIMIT)
        Arguments
            LIMIT: a positive integer for how many categories per page/query
        Purpose
            Sets the amount of categories that should be queried at once
    ------------------------------------
    setLinkPageLimit(LIMIT)
        Arguments
            LIMIT: a positive integer for how many links per page/query
        Purpose
            Sets the amount of links that should be queried at once
    ------------------------------------
    getCatPageLimit
        Purpose
            Return the current category limit
        Returns
            Integer value
    ------------------------------------
    getLinkPageLimit
        Purpose
            Returns the current link limit
        Returns
            Integer value
    ------------------------------------
    getCats(CATID(0),PAGE(1))
        Arguments
            CATID: ID of category in DB (default = 0)
            PAGE: Limit offset+1
        Purpose
            Selects a list of the categories with a parentID = to CATID
            Offset by PAGE-1
        Returns
            Array of rows (as arrays)
    ------------------------------------
    getCatsAll
        Purpose
            Selects a list of all categories
        Returns
            Array of rows (as arrays)
    ------------------------------------
    getCatCount(CATID(0))
        Arguments
            CATID: ID of the cat from under which we want a count of subcategories
        Purpose
            Selects a count of categories with parentID = to CATID
            (if -1, omits the WHERE clause and returns count of all categories)
        Returns
            Integer value
    ------------------------------------
    getSingleCat(CATID)
        Arguments
            CATID
        Purpose
            Selects the caegory with the CATID specified
        Returns
            Array containing the row
    ------------------------------------
    getLinks(CATID(0),PAGE(1),SEARCHINFO(NULL))
        Arguments
            CATID: ID of the category to select links from
            PAGE: Limit offset+1
            SEARCHINFO: array with the following (utilized) keys
                location: 'global' is the only value we care about
                search: text used (wrapped in %) to filter with LIKE clause
        Purpose
            Selects the links with a parentID of CATID
            Offset by PAGE-1
            Filtered by the search key in SEARCHINFO if defined
        Returns
            Array of rows (as arrays)
    ------------------------------------
    getLinkCount(CATID(0),SEARCHINFO(NULL))
        Arguments
            CATID: ID of the category to count links from
            SEARCHINFO: array with the following (utilized) keys
                location: 'global' is the only value we care about
                search: text used (wrapped in %) to filter with LIKE clause
        Purpose
            Select the count of links with parentID = CATID
            (or all categories if defined in SEARCHINFO)
            Filtered by the search key in SEARCHINFO
        Returns
            Integer Value
    ------------------------------------
*/

    protected $db_db;
    protected $db_catPerPage = 30;
    protected $db_linkPerPage = 30;
    protected $db_searchWhereArray = [
                             'title'    =>"WHERE l.`title` LIKE :search",
                             'url'      =>"WHERE l.`url` LIKE :search",
                             'both'     =>"WHERE (l.`title` LIKE :search OR l.`url` LIKE :search)",
                            ];

    public function __construct($dbfile, $logging=false){
        //Will load the database
        if (!file_exists($dbfile)){
            throw new Exception('Database file does not exist');
        }
        try {
            $this->db_db = new PDO('sqlite:'.$dbfile);
        }
        catch (Exception $e) {
            throw new Exception('Error Connecting to DB: '.$e->getMessage()."\n");
        }
        //Set setAttribute(PDO::ATTR_TIMEOUT,6)
        $this->db_db->setAttribute(PDO::ATTR_TIMEOUT,6);
    }

    public function setCatPageLimit($limit){
        if (is_int($limit) && $limit > 0){
            $this->db_catPerPage = $limit;
        }
    }

    public function setLinkPageLimit($limit){
        if (is_int($limit) && $limit > 0){
            $this->db_linkPerPage = $limit;
        }
    }

    public function getCatPageLimit(){
        return $this->db_catPerPage;
    }

    public function getLinkPageLimit(){
        return $this->db_linkPerPage;
    }

    public function getCats($catID=0,$page=1){
        /* --- BEGIN BASE QUERY --- */
            $baseQuery = "SELECT `id`, `title`, `description`, `parent` FROM `cats` WHERE `parent` = :parent ORDER BY `title` LIMIT :limit OFFSET :offset";
        /* --- END BASE QUERY --- */
        if(!is_int($catID) || $catID < 0){
            throw new Exception('catID must be a postitive integer');
        }
        if(!is_int($page)){
            throw new Exception('If Page is specified, page must be an integer');
        }

        $query = $baseQuery;

        $selectCat = $this->db_db->prepare($query);
        $selectCat->bindValue(':parent', $catID, PDO::PARAM_INT);
        $selectCat->bindValue(':limit', $this->db_catPerPage, PDO::PARAM_INT);
        $selectCat->bindValue(':offset', ($page-1)*$this->db_catPerPage, PDO::PARAM_INT);
        $selectCat->execute();
        $cats = $selectCat->fetchAll(PDO::FETCH_ASSOC);

        if(!is_array($cats)){
            return [];
        }else{
            return $cats;
        }
    }

    public function getCatsAll(){
        /* --- BEGIN BASE QUERY --- */
            $baseQuery = "SELECT `id`, `title`, `description`, `parent` FROM `cats` ORDER BY `title`,`id`";
        /* --- END BASE QUERY --- */

        $query = $baseQuery;

        $selectCat = $this->db_db->prepare($query);
        $selectCat->execute();
        $cats = $selectCat->fetchAll(PDO::FETCH_ASSOC);

        if(!is_array($cats)){
            return [];
        }else{
            return $cats;
        }
    }

    public function getCatCount(int $catID=0){
        /* --- BEGIN BASE QUERY --- */
            $baseQuery = "SELECT COUNT(`id`) AS 'count' FROM `cats`";
        /* --- END BASE QUERY --- */

        $query = $baseQuery;

        if($catID == -1){
            $getCount = $this->db_db->prepare($query);
        }elseif($catID < 0){
            throw new Exception("Error: catID cannot be a negative value other than -1 which has special handling");
        }else{
            $query = $query." WHERE `parent` = :cat";
            $getCount = $this->db_db->prepare($query);
            $getCount->bindValue(':cat', $catID, PDO::PARAM_INT);
        }

        $getCount->execute();
        $count = $getCount->fetch(PDO::FETCH_ASSOC);
        return $count['count'];
    }

    public function getSingleCat($catID=0){
        /* --- BEGIN BASE QUERY --- */
            $baseQuery = "SELECT `id`, `title`, `description`, `parent` FROM `cats` WHERE `id` = :cat";
        /* --- END BASE QUERY --- */
        if(!is_int($catID) || $catID < 0){
            throw new Exception('catID must be a postitive integer');
        }
        if($catID == 0){
            return [];
        }

        $query = $baseQuery;

        $selectSingleCat = $this->db_db->prepare($query);
        $selectSingleCat->bindValue(':cat',$catID,PDO::PARAM_INT);
        $selectSingleCat->execute();


        $singleCat = $selectSingleCat->fetch(PDO::FETCH_ASSOC);

        return $singleCat;
    }

    public function getLinks($catID=0,$page=1,$searchInfo=null){
        /* --- BEGIN BASE QUERY --- */
            $baseQuery = "SELECT l.`id`, l.`title`, l.`description`, l.`url` FROM `links` AS l JOIN `cat_membership` AS c ON c.`linkID` = l.`id` AND c.`catID` = :cat";
        /* --- END BASE QUERY --- */

        $searchInfo = (is_array($searchInfo)) ? $searchInfo : null;

        if($catID == 0 && (!is_null($searchInfo) && $searchInfo['location'] != "global")){
            return [];
        }

        if(!is_int($catID) || $catID < 0){
            throw new Exception('catID must be a postitive integer');
        }
        if(!is_int($page)){
            throw new Exception('If Page is specified, page must be an integer');
        }

        $query = $baseQuery;

        if(!is_null($searchInfo)){
            $query = $query.' '.$this->db_searchWhereArray[$searchInfo['type']];
            if($searchInfo['location'] == "global"){
                $query = str_replace(" AND c.`catID` = :cat","",$query);
                $query = $query." GROUP BY l.`id`";
            }
        }
        $query = $query." ORDER BY `title` LIMIT :limit OFFSET :offset";


        $selectLinks = $this->db_db->prepare($query);
        if(!is_null($searchInfo)){
            $selectLinks->bindValue(':search','%'.$searchInfo['search'].'%',PDO::PARAM_STR);
        }
        if(is_null($searchInfo) || $searchInfo['location'] != "global"){
            $selectLinks->bindValue(':cat', $catID, PDO::PARAM_INT);
        }
        $selectLinks->bindValue(':limit', $this->db_linkPerPage, PDO::PARAM_INT);
        $selectLinks->bindValue(':offset', ($page-1)*$this->db_linkPerPage, PDO::PARAM_INT);
        $selectLinks->execute();
        $links = $selectLinks->fetchAll(PDO::FETCH_ASSOC);

        if(!is_array($links)){
            return [];
        }else{
            return $links;
        }
    }

    public function getLinkCount(int $catID=0,$searchInfo=null){
        /* --- BEGIN BASE QUERY --- */
            $baseQuery = "SELECT COUNT(l.`id`) AS 'count' FROM `links` AS l";
        /* --- END BASE QUERY --- */

        $query = $baseQuery;

        $searchInfo = (is_array($searchInfo)) ? $searchInfo : null;

        $globalSearch = (!is_null($searchInfo) && $searchInfo['location'] == "global");

        if($catID >= 0 && !$globalSearch){
            $query = $query." JOIN `cat_membership` AS c ON c.`linkID` = l.`id` AND c.`catID` = :cat";
        }elseif($catID < -1){
            throw new Exception("Error: catID cannot be a negative value other than -1 which has special handling");
        }

        if(!is_null($searchInfo)){
            $query = $query." ".$this->db_searchWhereArray[$searchInfo['type']];
        }

        $getCount = $this->db_db->prepare($query);

        if($catID >=0 && !$globalSearch){
            $getCount->bindValue(':cat', $catID, PDO::PARAM_INT);
        }

        if(!is_null($searchInfo)){
            $getCount->bindValue(':search','%'.$searchInfo['search'].'%',PDO::PARAM_STR);
        }

        $getCount->execute();
        $count = $getCount->fetch(PDO::FETCH_ASSOC);
        return $count['count'];
    }


}

?>
