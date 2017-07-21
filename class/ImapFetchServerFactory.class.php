<?php

class ImapFetchServerFactory {

    public function getInstance($server,$port){
        return new \Fetch\Server($server,$port);
    }

}