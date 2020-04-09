<?php


class UserFactory
{
    public function getUserByEnvelopeId($envelopeId){
        $envelope = new ActesEnvelope($envelopeId);
        $envelope->init();

        $user = new User($envelope->get("user_id"));
        $user->init();

        return $user;
    }
}