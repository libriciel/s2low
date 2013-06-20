<?php 

$dom = new DomDocument();
$dom->load(__DIR__."/message.xml"); 


$sender_node = $dom->getElementsByTagNameNS("http://finances.gouv.fr/dgme/pec/message/v1","Sender")->item(0);
$recipient_node = $dom->getElementsByTagNameNS("http://finances.gouv.fr/dgme/pec/message/v1","Recipient")->item(0);

$new_recipient = $dom->createElementNS("http://finances.gouv.fr/dgme/pec/message/v1", "Recipient");

foreach($sender_node->childNodes as $child){
	$new_recipient->appendChild($child->cloneNode(true));
}

$new_sender = $dom->createElementNS("http://finances.gouv.fr/dgme/pec/message/v1", "Sender");
foreach($recipient_node->childNodes as $child){
	$new_sender->appendChild($child->cloneNode(true));
}


$recipient_node->parentNode->replaceChild($new_recipient,$recipient_node);
$sender_node->parentNode->replaceChild($new_sender,$sender_node);

echo $dom->saveXML();



/*
$xml = simplexml_load_file(__DIR__."/message.xml");



$namespaces = $xml->getNameSpaces(true);
$pec = $xml->children($namespaces['pec']);

$sender = $pec->Header->Routing->Sender;
$recipient = $pec->Header->Routing->Recipients->Recipient;

$sender_dom = dom_import_simplexml($sender);
$recipient_dom  = dom_import_simplexml($recipient);

//$sender_dom->appendChild($recipient_dom->cloneNode(true));

$pec->Header->Routing->Recipients->Recipient = simplexml_import_dom($sender_dom);
//$new_node  = simplexml_import_dom($dom_new);

echo $xml->asXML();*/