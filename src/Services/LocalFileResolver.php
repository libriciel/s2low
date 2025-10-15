<?php

namespace S2low\Services;

/**
 * @description Classe permettant d'interagir avec un fichier metier en fonction de son relativePath.
 * Il existe different identifiant de cette classe. Chacun ayant un paramétrage spécifique le liant à un fichier metier spécifique.
 * - Pour adapter cette classe a un nouveau fichier metier il faut creer un nouvel identifiant dans le service yaml en fournissant le bon 'localPathPrefix'.
 *
 * - Pour autowire la classe avec le bon parametrage il faut utilise l'attribut Autowire tel que pour une enveloppe acte :
 *
 * #[Autowire(service: 'app.localFileManager.acte_enveloppe')
 * private readonly LocalBusinessFileManager $businessLocalFileManager
 *
 * Ou parametrer l'autowire directement comme ici :
 *
    app.cloudManager.acte_enveloppe:
        class: S2low\Services\FileManager\S3FileManager
        arguments:
            $businessFilePathProvider: '@S2lowLegacy\Class\actes\ActesEnvelopeSQL'
=>          $localFileManager: '@app.localFileManager.acte_enveloppe'                   <===
 */
class LocalFileResolver
{
    public function __construct(
        private readonly FileDataProvider $fileDataProvider,
        private readonly string $localPathPrefix
    ) {
    }

    public function getFullPath(string $transactionId): string
    {
        $filePath = $this->fileDataProvider->getRelativePath($transactionId);

        return $this->getFullPathFromFilePath($filePath);
    }

    public function getFullPathFromFilePath(string $filePath): string
    {
        return preg_replace('#/{2,}#', '/', $this->localPathPrefix . '/' . $filePath);
    }
}
