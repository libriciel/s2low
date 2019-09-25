<?php

use \Symfony\Component\Filesystem\Filesystem;

class OpenStackSwiftWrapper {

	const OPENSTACK_SERVICE = 'swift';

	/** @var OpenStackFactory  */
    private $openStackFactory;


	private $cache_container = array();

	private $fileSystem;

    private $logger;

    public function __construct(
        OpenStackFactory $openStackFactory,
		Monolog\Logger $logger
    ){
        $this->openStackFactory = $openStackFactory;
        $this->fileSystem = new Filesystem();
		$this->logger = $logger;
	}

	/**
	 * Envoi un fichier dans les nuages
	 * @param string $container_name Le nom du container au sens swift
	 * @param string $filepath_local Le chemin local du fichier à envoyer dans les nuages
	 * @param string $filename_on_cloud Si présent l'emplacement sur le nuage, sinon, on prend le nom du fichier qu'on met directement sur le container
	 * @throws Exception
	 */
    public function sendFile($container_name,$filepath_local,$filename_on_cloud = ''){
    	if (! $filename_on_cloud){
			$filename_on_cloud = basename($filepath_local);
		}

        $container = $this->getContainer($container_name);
        $fileData = fopen($filepath_local, 'r+');
        if (! $fileData){
			$error = error_get_last();
			throw new Exception("Unable to retrieve $filepath_local : $error");
		}
		$this->logger->info("Upload $filepath_local to [$container_name]$filename_on_cloud");
        $container->uploadObject($filename_on_cloud, $fileData);
		$this->logger->info("Uploaded $filepath_local to [$container_name]$filename_on_cloud");
    }

	/**
	 * Récupère et copie le fichier depuis OpenStack vers le système de fichier local
	 * @param string $container_name Le nom du container au sens swift
	 * @param string $filepath_local Le chemin local du fichier à récupérer
	 * @param string $filepath_on_cloud l'emplacement sur le cloud, sinon on prend le nom du fichier local et on le cherche directemnet sur le container
	 * @throws Exception
	 * @return mixed
	 */
    public function retrieveFile($container_name, $filepath_local,$filepath_on_cloud = ''){
    	if (! $filepath_on_cloud){
    		$filepath_on_cloud = basename($filepath_local);
		}


        if ($this->fileSystem->exists($filepath_local)){
            return $filepath_local;
        }

        $dirname_local = dirname($filepath_local);

        if (! $this->fileSystem->exists($dirname_local)){
			$this->fileSystem->mkdir($dirname_local);
		}

		$container = $this->getContainer($container_name);

        $objectContent = $container->getObject($filepath_on_cloud)->getContent();
        $objectContent->rewind();
        $stream = $objectContent->getStream();
		$this->fileSystem->dumpFile($filepath_local,$stream);
		$this->logger->info("Retrieve [$container_name]$filepath_on_cloud to $filepath_local");
        return $filepath_local;
    }

	/**
	 * @param $container_name
	 * @param $filepath
	 * @throws UnrecoverableException
	 */
    public function deleteFile($container_name,$filepath){
        $filename = basename($filepath);
		$container = $this->getContainer($container_name);
        $container->deleteObject($filename);
		$this->logger->info("Delete [$container_name]$filepath");
    }

    public function fileExistsOnCloud($container_name,$filename){
    	try {
			$container = $this->getContainer($container_name);
			return $container->objectExists($filename);
		} catch (Exception $e){
    		return false;
		}
    }

	/**
	 * @param $container_name
	 * @return bool|mixed|\OpenCloud\ObjectStore\Resource\Container
	 * @throws UnrecoverableException
	 */
	public function getContainer($container_name){
		if (isset($this->cache_container[$container_name])){
			return $this->cache_container[$container_name];
		}
		$container_full_name = $this->openStackFactory->getOpenStackSwiftPrefix($container_name) . $container_name;

		$openStack = $this->openStackFactory->getInstance($container_name);

		$openStack->authenticate();

		$service = $openStack->objectStoreService(
			self::OPENSTACK_SERVICE,
			$this->openStackFactory->getOpenStackRegion($container_name)
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