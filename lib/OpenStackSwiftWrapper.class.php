<?php

class OpenStackSwiftWrapper {

    const OPENSTACK_SERVICE = 'swift';

    private $openstack_region;
    private $openstack_swift_container_prefix;

    private $openStackFactory;

    private $cache_container = array();

    public function __construct(
        OpenStackFactory $openStackFactory,
        $openstack_region,
        $openstack_swift_container_prefix
    ){
        $this->openStackFactory = $openStackFactory;
        $this->openstack_region = $openstack_region;
        $this->openstack_swift_container_prefix = $openstack_swift_container_prefix;
    }

    public function sendFile($container_name,$filename){
        $container = $this->getContainer($container_name);
        $fileData = fopen($filename, 'r+');
        $container->uploadObject(basename($filename), $fileData);
    }

    /* TODO Il faut permettre de dépose des fichiers avec un arborescence pour les Actes
    public function sendFile2($container_name,$filepath,$filename){
        $container = $this->getContainer($container_name);
        $fileData = fopen($filepath, 'r+');
        $container->uploadObject($filename, $fileData);
    }

    */

    /*
     * Récupère et copie le fichier depuis OpenStack vers le système de fichier local
     */
    public function retrieveFile($container_name, $filepath){
        if (file_exists($filepath)){
            return $filepath;
        };
        $container = $this->getContainer($container_name);

        $objectContent = $container->getObject(basename($filepath))->getContent();
        $objectContent->rewind();
        $stream = $objectContent->getStream();
        file_put_contents($filepath, $stream);
        return $filepath;
    }

    /* TODO La récupération semble plus complexe quand on est dans un répertoire
    public function retrieveFile2($container_name, $filepath_on_cloud,$filepath_local){
        if (file_exists($filepath_local)){
            return $filepath_local;
        };
        $container = $this->getContainer($container_name);

        $objectContent = $container->getObject($filepath_on_cloud)->getContent();
        $objectContent->rewind();
        $stream = $objectContent->getStream();
        file_put_contents($filepath_local, $stream);
        return $filepath_local;
    }
    */


    public function deleteFile($container_name,$filepath){
        $filename = basename($filepath);
        $container = $this->getContainer($container_name);
        $container->deleteObject($filename);
    }

    public function fileExistsOnCloud($container_name,$filename){
        $container = $this->getContainer($container_name);
        return $container->objectExists($filename);
    }


    private function getContainer($container_name){
        if (isset($this->cache_container[$container_name])){
            return $this->cache_container[$container_name];
        }
        $container_full_name = $this->openstack_swift_container_prefix . $container_name;

        $openStack = $this->openStackFactory->getInstance();

        $openStack->authenticate();

        $service = $openStack->objectStoreService(
            self::OPENSTACK_SERVICE,
            $this->openstack_region
        );

        try {
            $container = $service->getContainer(
                $container_full_name
            );
        } catch(Guzzle\Http\Exception\ClientErrorResponseException $e){
            if ($e->getResponse()->getStatusCode() != 404){
                throw $e;
            }
            $container = $service->createContainer(
                $container_full_name
            );
        }
        $this->cache_container[$container_name] = $container;
        return $container;
    }
}