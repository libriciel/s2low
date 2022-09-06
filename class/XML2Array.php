<?php

namespace S2lowLegacy\Class;

class XML2Array
{
    private $namespaces;
    private $uniqueNode;
    private $remonteNode;

    public function __construct()
    {
        $this->uniqueNode = array();
        $this->remonteNode = array();
    }

    public function setUniqueNode(array $nodeName)
    {
        $this->uniqueNode = $nodeName;
    }

    public function setNode2Remonte(array $nodeName)
    {
        $this->remonteNode = $nodeName;
    }

    public function getArray($xml_content)
    {
        $root = simplexml_load_string($xml_content);
        $this->namespaces = $root->getDocNamespaces(true);
        $this->namespaces[''] = '';
        $result['type'] = $root->getName();
        $result += $this->getChildNode($root);
        return $result;
    }

    private function isUniqueNode($nodeName)
    {
        return in_array($nodeName, $this->uniqueNode) ;
    }

    private function getChildNode($node)
    {

        $result = false;
        foreach ($this->namespaces as $namespace => $namespaceUrl) {
            foreach ($node->attributes($namespaceUrl) as $a => $b) {
                $result[strval($a)] = strval($b);
            }
            foreach ($node->children($namespaceUrl) as $b) {
                if ($this->isUniqueNode($b->getName())) {
                    $result[$b->getName()] = $this->getChildNode($b);
                } else {
                    $result[$b->getName()][] = $this->getChildNode($b);
                }
            }
            if (trim(strval($node))) {
                $result = strval($node);
            }
        }
        foreach ($this->remonteNode as $node) {
            if (is_array($result) && isset($result[$node])) {
                $result = $result[$node];
            }
        }
        return $result;
    }
}
