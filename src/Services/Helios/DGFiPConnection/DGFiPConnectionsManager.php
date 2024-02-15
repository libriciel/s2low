<?php

namespace S2low\Services\Helios\DGFiPConnection;

/**
 * Gère les deux configurations de connections possibles :
 * 1/ la configuration non PASSTRANS
 * 2/ la configuration PASSTRANS
 */
class DGFiPConnectionsManager
{
    /**
     * @var \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionBuilder
     */
    private DGFiPConnectionBuilder $DGFiPConnectionBuilder;

    /**
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration $passtransConnection
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionConfiguration $gatewayConnection
     * @param \S2low\Services\Helios\DGFiPConnection\DGFiPConnectionBuilder $DGFiPConnectionBuilder
     */
    public function __construct(
        DGFiPConnectionConfiguration $passtransConnection,
        DGFiPConnectionConfiguration $gatewayConnection,
        DGFiPConnectionBuilder $DGFiPConnectionBuilder
    ) {
        $this->passtransConnection = $passtransConnection;
        $this->gatewayConnection = $gatewayConnection;
        $this->DGFiPConnectionBuilder = $DGFiPConnectionBuilder;
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
