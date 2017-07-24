<?php

namespace cloudmark;

class dbinsert extends db {

    protected deleteLinkFromCats($l_id){


    }

    protected deleteLink($l_id){

        $this->deleteLinkFromCats($l_id);
    }

    protected addLinkToCats($l_id, array $l_cats){


    }


    /* ------------------ */

    public addLink($l_title, $l_url, array $l_cats){


        $l_id = "???"; // Until db stuff added, just to avoid error
        $this->addLinkToCats($l_id);
    }


    public updateLink($l_id, $l_title, $l_url, array $l_cats){

        $this->deleteLinkFromCats($l_id);
        $this->addLinkToCats($l_cats);
    }
}


?>
