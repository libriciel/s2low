<?php

use Psr\Http\Message\ResponseInterface;
use \Symfony\Component\Filesystem\Filesystem;
use GuzzleHttp\Psr7\Stream;

class OpenStackSwiftWrapper {

	const OPENSTACK_SERVICE = 'swift';

	/** @var OpenStackContainerStore  */
    private $openStackContainersStore;

	private $fileSystem;

    private $logger;

    public function __construct(
        OpenStackContainerStore $openStackContainersStore,
        Monolog\Logger $logger
    ){
        $this->openStackContainersStore = $openStackContainersStore;
        $this->fileSystem = new Filesystem();
		$this->logger = $logger;
	}

    /**
     * Envoi un fichier dans les nuages
     * @param string $container_name Le nom du container au sens swift
     * @param string $filepath_local Le chemin local du fichier à envoyer dans les nuages
     * @param string $filename_on_cloud Si présent l'emplacement sur le nuage, sinon, on prend le nom du fichier qu'on met directement sur le container
     * @throws CloudStorageException|UnrecoverableException
     * @throws Exception
     */
    public function sendFile($container_name,$filepath_local,$filename_on_cloud = ''){
    	if (! $filename_on_cloud){
			$filename_on_cloud = basename($filepath_local);
		}

    	try {
            $fileData = fopen($filepath_local, 'r+');
        }
        catch(Exception $e){
            throw new CloudStorageException("Unable to retrieve $filepath_local : ".$e->getMessage());
        }
        if (! $fileData){
			$error = error_get_last();
			throw new CloudStorageException("Unable to retrieve $filepath_local : $error");
		}
        $stream = new Stream($fileData);
		$this->logger->info("Upload $filepath_local to [$container_name]$filename_on_cloud");
		$fileProperties = [
            'name'=>$filename_on_cloud,
            'stream'=>$stream
        ];

        $containerWrapper = $this->openStackContainersStore->getContainerWrapper($container_name);
        $containerWrapper->createObject($fileProperties);

		$this->logger->info("Uploaded $filepath_local to [$container_name]$filename_on_cloud");
    }

    /**
     * @param $container_name
     * @param $filepath_local
     * @param string $filepath_on_cloud
     * @throws UnrecoverableException
     * @throws Exception
     */

    private function retrieveFileFromCloud($container_name, $filepath_local,$filepath_on_cloud = ''){
        $dirname_local = dirname($filepath_local);

        if (! $this->fileSystem->exists($dirname_local)){
            $this->fileSystem->mkdir($dirname_local);
        }

        if (! $filepath_on_cloud){
            $filepath_on_cloud = basename($filepath_local);
        }

        $containerWrapper = $this->openStackContainersStore->getContainerWrapper($container_name);
        if(preg_match('#//+#',$filepath_on_cloud) && ! $containerWrapper->objectExists($filepath_on_cloud)){
            $filepath_on_cloud = preg_replace('#/+#','/',$filepath_on_cloud);
            if(!$containerWrapper->objectExists($filepath_on_cloud)){
                throw new CloudStorageException("$filepath_on_cloud non trouvé dans $container_name");
            }
        }

        $stream = $containerWrapper->download($filepath_on_cloud);

        $this->fileSystem->dumpFile($filepath_local,$stream);

        $this->logger->info("Retrieve [$container_name] $filepath_on_cloud to $filepath_local");
    }

    /**
     * Si nécessaire, récupère et copie le fichier depuis OpenStack vers le système de fichier local
     * @param $container_name : Le nom du container au sens swift
     * @param $filepath_local : Le chemin local du fichier à récupérer
     * @param string $filepath_on_cloud l'emplacement sur le cloud, sinon on prend le nom du fichier local et on le cherche directemnet sur le container
     * @return mixed
     * @throws UnrecoverableException
     */

    public function retrieveFile($container_name, $filepath_local,$filepath_on_cloud = ''){
        if (!$this->fileSystem->exists($filepath_local)){
            $this->retrieveFileFromCloud($container_name, $filepath_local,$filepath_on_cloud);
        }
        return $filepath_local;
    }

    /**
     * @param $container_name
     * @param $filepath
     * @throws UnrecoverableException
     * @throws Exception
     */

    public function deleteFile($container_name,$filepath){
        $filename = basename($filepath);
		$containerWrapper = $this->openStackContainersStore->getContainerWrapper($container_name);
        $containerWrapper->delete($filename);
		$this->logger->info("Delete [$container_name]$filepath");
    }

    /**
     * @param $container_name
     * @param $filename
     * @return bool|ResponseInterface
     */

    public function fileExistsOnCloud($container_name,$filename){
    	try {
            $containerWrapper = $this->openStackContainersStore->getContainerWrapper($container_name);
			return $containerWrapper->objectExists($filename);
		} catch (Exception $e){
    		return false;
		}
    }
}