<?php

use S2lowLegacy\Class\actes\ActesStatusSQL;

?>
<h1>Statistiques d'envoi au SAE - Actes </h1>
<p id="back-transaction-btn">
    <a href="/admin/stats-sae.php" class="btn btn-default">Retour statistiques globale SAE</a>
</p>

<h2 id="det_desc">Détail par collectivité</h2>
<table class="data-table table table-striped " aria-describedby="det_desc">

    <tr>
        <th scope="col">Collectivités</th>
        <?php foreach ($this->status_list as $status_id) : ?>
            <th scope="col">
                <?php hecho(ActesStatusSQL::getStatusLibelle($status_id));?>
            </th>
        <?php endforeach; ?>
    </tr>
    <?php foreach ($this->info_list as $authority_id => $authority_info) :?>
    <tr>
        <th scope="col">
            <a href="/admin/authorities/admin_authority_sae_statistiques.php?id=<?php echo $authority_id?>">
                <?php hecho($authority_info['name']) ?>
            </a>
        </th>
        <?php foreach ($this->status_list as $status_id) : ?>
            <td>
                <span class="label label-<?php echo ($authority_info['status'][$status_id] ?? 0) ? 'danger' : 'success' ?>">
                    <?php echo $authority_info['status'][$status_id] ?? 0;?>
                </span>

            </td>
        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
</table>

