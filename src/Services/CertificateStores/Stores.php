<?php

namespace S2low\Services\CertificateStores;

use S2low\Exceptions\MagasinDeCertificatsAbsentException;

class Stores
{
    /**
     * @var \S2low\Services\CertificateStores\Store[]
     */
    private array $stores;

    public function __construct(
        private readonly bool $only_use_validcargs,
        Store ...$stores
    ) {
        $this->stores = $stores;
    }
    public function getDefaultStorePath(): string
    {
        $extendedStoreType = Type::EXTENDED;
        if ($this->only_use_validcargs) {
            $extendedStoreType = Type::RGS;
        }
        return $this->getStorePath($extendedStoreType);
    }

    /**
     * @throws \S2low\Exceptions\MagasinDeCertificatsAbsentException
     */
    public function getStorePath(Type $type): string
    {
        foreach ($this->stores as $store) {
            if ($store->getType() === $type) {
                return $store->getPath();
            }
        }
        throw new MagasinDeCertificatsAbsentException('Magasin de certificats de type ' . $type->value . ' non trouvé');
    }
}
