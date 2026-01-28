<?php

namespace S2low\Infrastructure\CertificateStores;

use S2low\Exceptions\UndefinedCertificateStore;

class Stores
{
    /**
     * @var Store[]
     */
    private array $stores;

    public function __construct(
        private readonly bool $only_use_validcargs,
        Store ...$stores
    ) {
        $this->stores = $stores;
    }

    /**
     * @throws \S2low\Exceptions\UndefinedCertificateStore
     */
    public function getStorePath($extendedStoreType = Type::DEFAULT): string
    {
        if ($extendedStoreType === Type::DEFAULT) {
            $extendedStoreType = Type::EXTENDED;
            if ($this->only_use_validcargs) {
                $extendedStoreType = Type::RGS;
            }
        }
        return $this->getStorePathByGivenType($extendedStoreType);
    }

    /**
     * @throws \S2low\Exceptions\UndefinedCertificateStore
     */
    private function getStorePathByGivenType(Type $type): string
    {
        foreach ($this->stores as $store) {
            if ($store->getType() === $type) {
                return $store->getPath();
            }
        }
        throw new UndefinedCertificateStore('Magasin de certificats de type ' . $type->value . ' non trouvé');
    }
}
