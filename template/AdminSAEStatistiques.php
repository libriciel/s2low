<?php

use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Class\Helpers;

?>
<h1><?php hecho($this->title); ?></h1>
<p id="back-transaction-btn">
    <a href="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/admin/authorities/admin_authority_sae.php?id=$this->authority_id"); ?>" class="btn btn-default">Retour configuration SAE</a>
</p>

<h2>Actes <?php echo $this->pastellProperties->actes_send_auto ? "(mode automatique)" : ""?></h2>

<table class="data-table table table-striped " role="presentation">


    <tr class="<?php echo $this->actes_nb_en_retard ? "danger" : "success" ?>">
        <td>Actes à archiver (dans l'état acquittement reçu)</td>
        <td><span class="label label-<?php echo $this->actes_nb_en_retard ? "danger" : "success" ?>"><?php echo $this->actes_nb_en_retard ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_ACQUITTEMENT_RECU ?>&authority=<?php hecho($this->authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>
            &nbsp;
        </td>
    </tr>

    <tr class="<?php echo $this->actes_nb_en_attente_transmission_sae ? "danger" : "success" ?>">
        <td>Actes en attente de transmission au SAE</td>
        <td><span class="label label-<?php echo $this->actes_nb_en_attente_transmission_sae ? "danger" : "success" ?>"><?php echo $this->actes_nb_en_attente_transmission_sae ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>&authority=<?php hecho($this->authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>
            &nbsp;
        </td>
    </tr>

    <tr class="<?php echo $this->actes_nb_envoye_sae ? "danger" : "success" ?>">
        <td>Actes en attente d'acceptation par le SAE</td>
        <td><span class="label label-<?php echo $this->actes_nb_envoye_sae ? "danger" : "success" ?>"><?php echo $this->actes_nb_envoye_sae ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_ENVOYE_AU_SAE ?>&authority=<?php hecho($this->authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>
            &nbsp;
        </td>
    </tr>

    <tr class="<?php echo $this->actes_erreur_lors_de_lenvoi_sae ? "danger" : "success" ?>">
        <td>Actes erreur lors de l'envoi au SAE</td>
        <td><span class="label label-<?php echo $this->actes_erreur_lors_de_lenvoi_sae ? "danger" : "success" ?>"><?php echo $this->actes_erreur_lors_de_lenvoi_sae ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ?>&authority=<?php hecho($this->authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>
            <form action="/modules/actes/actes_transac_change_status_sae_bulk.php" method="post" onsubmit="return confirm('Voulez-vous vraiment modifier ces transactions ?')">
                <input type="hidden" name="authority_id" value="<?php echo $this->authority_id ?>" />
                <input type="hidden" name="status_id_from" value="<?php echo ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ?>" />
                <input type="hidden" name="status_id_to" value="<?php echo ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>" />
                <input type="submit" class="btn btn-warning" value="Relancer" />
            </form>
        </td>

    </tr>
    <tr class="<?php echo $this->actes_erreur_lors_de_larchivage ? "danger" : "success" ?>">
        <td>Actes erreur lors de l'archivage</td>
        <td><span class="label label-<?php echo $this->actes_erreur_lors_de_larchivage ? "danger" : "success" ?>"><?php echo $this->actes_erreur_lors_de_larchivage ?></span></td>
        <td>
            <a href="/modules/actes/index.php?status=<?php echo ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE ?>&authority=<?php hecho($this->authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>
            <?php if ($this->actes_erreur_lors_de_larchivage > 0) : ?>
            <form action="/modules/actes/actes_transac_change_status_sae_bulk.php" method="post" onsubmit="return confirm('Voulez-vous vraiment modifier ces transactions ?')">
                <input type="hidden" name="authority_id" value="<?php echo $this->authority_id ?>" />
                <input type="hidden" name="status_id_from" value="<?php echo ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ARCHIVAGE ?>" />
                <input type="hidden" name="status_id_to" value="<?php echo ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>" />
                <input type="submit" class="btn btn-warning" value="Relancer" />
            </form>
            <?php endif; ?>
        </td>
    </tr>

</table>



<h2>Helios <?php echo $this->pastellProperties->helios_send_auto ? "(mode automatique)" : ""?></h2>

<table class="data-table table table-striped " role="presentation">


    <tr class="<?php echo $this->helios_nb_en_retard ? "danger" : "success" ?>">
        <td>Fichiers PES à archiver (dans l'état information disponible)</td>
        <td><span class="label label-<?php echo $this->helios_nb_en_retard ? "danger" : "success" ?>"><?php echo $this->helios_nb_en_retard ?></span></td>
        <td>
            <a href="/modules/helios/index.php?status=<?php echo HeliosStatusSQL::INFORMATION_DISPONIBLE ?>&authority=<?php hecho($this->authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>&nbsp;</td>
    </tr>

    <tr class="<?php echo $this->helios_nb_en_attente_transmission_sae ? "danger" : "success" ?>">
        <td>Fichiers PES en attente de transmission au SAE</td>
        <td><span class="label label-<?php echo $this->helios_nb_en_attente_transmission_sae ? "danger" : "success" ?>"><?php echo $this->helios_nb_en_attente_transmission_sae ?></span></td>
        <td>
            <a href="/modules/helios/index.php?status=<?php echo HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>&authority=<?php hecho($this->authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>&nbsp;</td>
    </tr>
    <tr class="<?php echo $this->helios_nb_envoye_au_sae ? "danger" : "success" ?>">
        <td>Fichiers PES en attente d'acceptation par le SAE </td>
        <td><span class="label label-<?php echo $this->helios_nb_envoye_au_sae ? "danger" : "success" ?>"><?php echo $this->helios_nb_envoye_au_sae ?></span></td>
        <td>
            <a href="/modules/helios/index.php?status=<?php echo HeliosStatusSQL::ENVOYER_AU_SAE ?>&authority=<?php hecho($this->authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>&nbsp;</td>
    </tr>

    <tr class="<?php echo $this->helios_erreur_lors_de_lenvoi_sae ? "danger" : "success" ?>">
        <td>Fichiers PES erreur lors de l'envoi au SAE</td>
        <td><span class="label label-<?php echo $this->helios_erreur_lors_de_lenvoi_sae ? "danger" : "success" ?>"><?php echo $this->helios_erreur_lors_de_lenvoi_sae ?></span></td>
        <td>
            <a href="/modules/helios/index.php?status=<?php echo HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ?>&authority=<?php hecho($this->authority_id) ?>" class="icon">
                Liste
            </a>
        </td>
        <td>
        <form action="/modules/helios/helios_transac_change_status_sae_bulk.php" method="post" onsubmit="return confirm('Voulez-vous vraiment modifier ces transactions ? ')">
            <input type="hidden" name="authority_id" value="<?php echo $this->authority_id ?>" />
            <input type="hidden" name="status_id_from" value="<?php echo HeliosStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE ?>" />
            <input type="hidden" name="status_id_to" value="<?php echo ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE ?>" />
            <input type="submit" class="btn btn-warning" value="Relancer" />
        </form>
        </td>
    </tr>


</table>
