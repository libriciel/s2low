<?php


class AuthorityFactory
{
    public function get($authorityId){
        $author = new Authority($authorityId);
        $author->init();

        return $author;
    }
}