<?php

namespace S2low\Services\Helios\DGFiPConnection;

/**
 * Gère les deux configurations de connections possibles :
 * 1/ la configuration non PASSTRANS
 * 2/ la configuration PASSTRANS
 */
class DGFiPConnectionsManager
{
    public function __construct(
        private readonly DGFiPConnectionConfiguration $passtransConnection,
        private readonly DGFiPConnectionConfiguration $gatewayConnection,
        private readonly DGFiPConnectionBuilder $DGFiPConnectionBuilder,
    ) {
    }

    /**
     * @param bool $usePasstrans
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration
     */
    public function getDGFipConnectionConfiguration(bool $usePasstrans): DGFiPConnectionConfiguration
    {
        if (! $usePasstrans) {
            return $this->gatewayConnection;
        }
        return $this->passtransConnection;
    }

    /**
     * @param bool $usePasstrans
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnection
     */
    public function get(bool $usePasstrans): DGFiPConnection
    {
        return $this->getFromConfiguration(
            $this->getDGFipConnectionConfiguration($usePasstrans)
        );
    }

    /**
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration $configuration
     * @return \S2low\Services\Helios\DGFiPConnection\DGFiPConnection
     */
    public function getFromConfiguration(DGFiPConnectionConfiguration $configuration): DGFiPConnection
    {
        return $this->DGFiPConnectionBuilder->get($configuration);
    }
}
