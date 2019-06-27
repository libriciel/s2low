
<h1>Statistiques envoi SAE</h1>
<p id="back-transaction-btn">
    <a href="<?php echo WEBSITE_SSL ?>/admin/authorities/admin_authority_sae.php?id=<?php hecho($authority_id) ?>" class="btn btn-default">Retour configuration SAE</a>
</p>

<h2>Actes</h2>

<table class="data-table table table-striped ">


    <tr class="<?php echo $actes_nb_en_retard?"danger":"success" ?>">
        <td>Actes en retard pour la transmission automatique</td>
        <td><span class="label label-<?php echo $actes_nb_en_retard?"danger":"success" ?>"><?php echo $actes_nb_en_retard ?></span></td>
        <td>
            &nbsp;
        </td>
        <td>
            &nbsp;
        </td>
    </tr>

    <tr class="<?php echo $actes_nb_en_attente_sae_4h?"danger":"success" ?>">
        <td>Actes en attente de transmission au SAE</td>
        <td><span class="label label-<?php echo $actes_nb_en_attente_sae_4h?"danger":"success" ?>"><?php echo $actes_nb_en_attente_sae_4h ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>&authority=<?php hecho($authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>
            &nbsp;
        </td>
    </tr>



    <tr class="<?php echo $actes_nb_envoye_sae_4h?"danger":"success" ?>">
        <td>Actes envoyé au SAE </td>
        <td><span class="label label-<?php echo $actes_nb_envoye_sae_4h?"danger":"success" ?>"><?php echo $actes_nb_envoye_sae_4h ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_ENVOYE_AU_SAE ?>&authority=<?php hecho($authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>
            &nbsp;
        </td>
    </tr>

    <tr class="<?php echo $actes_erreur_lors_de_lenvoi_sae?"danger":"success" ?>">
        <td>Actes erreur lors de l'envoi au SAE</td>
        <td><span class="label label-<?php echo $actes_erreur_lors_de_lenvoi_sae?"danger":"success" ?>"><?php echo $actes_erreur_lors_de_lenvoi_sae ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ?>&authority=<?php hecho($authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>
            <form action="/modules/actes/actes_transac_change_status_sae_bulk.php" method="post" onsubmit="return confirm('Voulez-vous vraiment modifier ces transactions')">
                <input type="hidden" name="authority_id" value="<?php echo $authority_id ?>" />
                <input type="hidden" name="status_id_from" value="<?php echo ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ?>" />
                <input type="hidden" name="status_id_to" value="<?php echo ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>" />
                <input type="submit" class="btn btn-warning" value="Relancer" />
            </form>
        </td>

    </tr>
    <tr class="<?php echo $actes_erreur_lors_de_larchivage?"danger":"success" ?>">
        <td>Actes erreur lors de l'archivage</td>
        <td><span class="label label-<?php echo $actes_erreur_lors_de_larchivage?"danger":"success" ?>"><?php echo $actes_erreur_lors_de_larchivage ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE ?>&authority=<?php hecho($authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>
            <?php if($actes_erreur_lors_de_larchivage > 0) : ?>
            <form action="/modules/actes/actes_transac_change_status_sae_bulk.php" method="post" onsubmit="return confirm('Voulez-vous vraiment modifier ces transactions')">
                <input type="hidden" name="authority_id" value="<?php echo $authority_id ?>" />
                <input type="hidden" name="status_id_from" value="<?php echo ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE ?>" />
                <input type="hidden" name="status_id_to" value="<?php echo ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>" />
                <input type="submit" class="btn btn-warning" value="Relancer" />
            </form>
            <?php endif; ?>
        </td>
    </tr>

</table>



<h2>Helios</h2>

<table class="data-table table table-striped ">


    <tr class="<?php echo $helios_nb_en_retard?"danger":"success" ?>">
        <td>Fichiers PES en retard pour la transmission automatique</td>
        <td><span class="label label-<?php echo $helios_nb_en_retard?"danger":"success" ?>"><?php echo $helios_nb_en_retard ?></span></td>
        <td>
            &nbsp;
        </td>
    </tr>

    <tr class="<?php echo $helios_nb_en_attente_sae_4h?"danger":"success" ?>">
        <td>Fichiers PES en attente de transmission au SAE</td>
        <td><span class="label label-<?php echo $helios_nb_en_attente_sae_4h?"danger":"success" ?>"><?php echo $helios_nb_en_attente_sae_4h ?></span></td>
        <td>
            <a href="/modules/helios/index.php?status=<?php echo HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>&authority=<?php hecho($authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
    </tr>
    <tr class="<?php echo $helios_nb_envoye_sae_4h?"danger":"success" ?>">
        <td>Fichiers PES envoyé au SAE </td>
        <td><span class="label label-<?php echo $helios_nb_envoye_sae_4h?"danger":"success" ?>"><?php echo $helios_nb_envoye_sae_4h ?></span></td>
        <td>
            <a href="/modules/helios/index.php?status=<?php echo HeliosStatusSQL::ENVOYER_AU_SAE ?>&authority=<?php hecho($authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
    </tr>

    <tr class="<?php echo $helios_erreur_lors_de_lenvoi_sae?"danger":"success" ?>">
        <td>Fichiers PES erreur lors de l'envoi au SAE</td>
        <td><span class="label label-<?php echo $helios_erreur_lors_de_lenvoi_sae?"danger":"success" ?>"><?php echo $helios_erreur_lors_de_lenvoi_sae ?></span></td>
        <td>
            <a href="/modules/helios/index.php?status=<?php echo HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ?>&authority=<?php hecho($authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
    </tr>


</table>