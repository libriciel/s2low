<?php



class HeliosFtpConnectionFactory
{
public function __construct(){

}
public function get(){
    return new FTPService();
}
}