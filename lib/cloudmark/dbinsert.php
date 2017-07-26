<?php

namespace cloudmark;

class dbinsert extends db {

    protected function deleteLinkFromCats($linkID){


    }

    protected function deleteLink($linkID){

        $this->deleteLinkFromCats($linkID);
    }

    protected function addLinkToCats($linkID, array $linkCats){
        /* --- BEGIN BASE QUERY --- */
            $baseQuery = "";
        /* --- END BASE QUERY --- */

        $query = $baseQuery;
        $l_insert = $this->db_db->prepare($query);

        try {
            // Execute Here //
        } catch(Exception $e) {
            throw new Exception("Error Adding Link To Category/Categories");
        }

    }


    /* ------------------ */

    public function addLink($linkTitle, $linkURL, array $linkCats, $linkDescription=null){
        /* --- BEGIN BASE QUERY --- */
            $baseQuery = "INSERT INTO `links` (`title`,`url`) values(:title,:url)";
        /* --- END BASE QUERY --- */

        if(!is_null($linkDescription)){
            $query = str_replace(["`url`",":url"],["`url`,`description`",":url,:description"],$baseQuery);
        }else{
            $query = $baseQuery;
        }

        $l_insert = $this->db_db->prepare($query);

        try{
            $this->db_db->beginTransaction();
            $l_insert->execute();
            $linkID = $this->db_db->lastInsertId();
            $this->addLinkToCats($linkID);
            $this->db_db->commit();
        } catch(Exception $e) {
            $this->db_db->rollback();
            throw new Exception("Error Inserting Link To DB");
        }


    }


    public function updateLink($linkID, $linkTitle, $linkURL, array $linkCats){
        /* --- BEGIN BASE QUERY --- */
            $baseQuery = "";
        /* --- END BASE QUERY --- */




        $this->deleteLinkFromCats($linkID);
        $this->addLinkToCats($linkCats);
    }
}


?>
