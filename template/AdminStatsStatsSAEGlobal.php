<h1>Statistiques d'envoi au SAE</h1>

<h2>@ctes</h2>

<table class="data-table table table-striped ">
    <tr class="<?php echo $actes_nb_en_attente_sae_4h?"danger":"success" ?>">
        <td>Actes en attente de transmission au SAE</td>
        <td><span class="label label-<?php echo $actes_nb_en_attente_sae_4h?"danger":"success" ?>"><?php echo $actes_nb_en_attente_sae_4h ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>" class="icon">
                Liste
            </a>
        </td>
    </tr>
    <tr class="<?php echo $actes_nb_en_attente_auto?"danger":"success" ?>">
        <td>Actes en attente de transmission au SAE (mode automatique)</td>
        <td><span class="label label-<?php echo $actes_nb_en_attente_auto?"danger":"success" ?>"><?php echo $actes_nb_en_attente_auto ?></span></td>
        <td>&nbsp;</td>
    </tr>

    <tr class="<?php echo $actes_nb_envoye_sae_4h?"danger":"success" ?>">
        <td>Actes envoyé au SAE </td>
        <td><span class="label label-<?php echo $actes_nb_envoye_sae_4h?"danger":"success" ?>"><?php echo $actes_nb_envoye_sae_4h ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_ENVOYE_AU_SAE ?>" class="icon">
                Liste
            </a>
        </td>
    </tr>

    <tr class="<?php echo $actes_nb_envoye_auto?"danger":"success" ?>">
        <td>Actes envoyé au SAE (mode automatique) </td>
        <td><span class="label label-<?php echo $actes_nb_envoye_auto?"danger":"success" ?>"><?php echo $actes_nb_envoye_auto ?></span></td>
        <td>&nbsp;</td>
    </tr>


    <tr class="<?php echo $actes_erreur_lors_de_lenvoi_sae?"danger":"success" ?>">
        <td>Actes erreur lors de l'envoi au SAE</td>
        <td><span class="label label-<?php echo $actes_erreur_lors_de_lenvoi_sae?"danger":"success" ?>"><?php echo $actes_erreur_lors_de_lenvoi_sae ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ?>" class="icon">
                Liste
            </a>
        </td>
    </tr>

    <tr class="<?php echo $actes_erreur_lors_de_larchivage?"danger":"success" ?>">
        <td>Actes erreur lors de l'archivage</td>
        <td><span class="label label-<?php echo $actes_erreur_lors_de_larchivage?"danger":"success" ?>"><?php echo $actes_erreur_lors_de_larchivage ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE ?>" class="icon">
                Liste
            </a>
        </td>
    </tr>

</table>


<h2>PES</h2>

<table class="data-table table table-striped ">
    <tr class="<?php echo $helios_nb_en_attente_sae_4h?"danger":"success" ?>">
        <td>PES ALLER en attente de transmission au SAE</td>
        <td><span class="label label-<?php echo $helios_nb_en_attente_sae_4h?"danger":"success" ?>"><?php echo $helios_nb_en_attente_sae_4h ?></span></td>
        <td>
            <a href="/modules/helios/index.php?status=<?php echo HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>" class="icon">
                Liste
            </a>
        </td>
    </tr>
    <tr class="<?php echo $helios_nb_envoye_sae_4h?"danger":"success" ?>">
        <td>PES ALLER envoyés au SAE </td>
        <td><span class="label label-<?php echo $helios_nb_envoye_sae_4h?"danger":"success" ?>"><?php echo $helios_nb_envoye_sae_4h ?></span></td>
        <td>
            <a href="/modules/helios/index.php?status=<?php echo HeliosStatusSQL::ENVOYER_AU_SAE ?>" class="icon">
                Liste
            </a>
        </td>
    </tr>

    <tr class="<?php echo $helios_erreur_lors_de_larchivage?"danger":"success" ?>">
        <td>PES ALLER en erreur lors de l'envoi au SAE</td>
        <td><span class="label label-<?php echo $helios_erreur_lors_de_larchivage?"danger":"success" ?>"><?php echo $helios_erreur_lors_de_larchivage ?></span></td>
        <td>
            <a href="/modules/helios/index.php?status=<?php echo HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ?>" class="icon">
                Liste
            </a>
        </td>
    </tr>

</table>
