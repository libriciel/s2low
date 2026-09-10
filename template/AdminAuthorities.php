<?php

use S2lowLegacy\Class\Helpers;

?>
    <h1><?php hecho($this->titre) ?></h1>

<div id="actions-area">
    <h2>Actions</h2>
    <a class="btn btn-primary bouton" href="admin_authority_edit.php">Ajouter une collectivité</a>
    <a class="btn btn-primary bouton" href="admin_authorities_export.php">Exporter la liste des collectivités</a>
</div>

<div id="filtering-area">
    <h2>Filtrage</h2>
    <form class="form-horizontal" action="admin_authorities.php" method="get" role="form">
        <div class="form-group">
            <label for="type" class="col-md-3 control-label">Type</label>
            <div class="col-md-3">
                <select name="type" class="form-control">
                    <option value="">Choisissez</option>
                    <?php foreach ($this->authority_types as $authority_type_libelle_info) : ?>
                        <option
                            <?php echo $this->ftype == $authority_type_libelle_info['id'] ? "selected='selected'" : '' ?>
                            value="<?php echo($authority_type_libelle_info['id']) ?>"
                        >
                            <?php
                            $str = "{$authority_type_libelle_info['id']} {$authority_type_libelle_info['description']}";
                            hecho(Helpers::chunkString($str, 40))
                            ?>
                        </option>
                    <?php endforeach;?>
                </select>
            </div>
            <label for="name-contain" class="col-md-3 control-label">Le nom contient</label>
            <div class="col-md-3">
                <input
                        id="name-contain"
                        class="form-control"
                        type="text"
                        name="name"
                        size="20"
                        maxlength="25"
                        value="<?php hecho($this->fname) ?>"
                />
            </div>
        </div>
            <div class="form-group">
                <?php if ($this->groupe_list) : ?>
                <label for="group" class="col-md-3 control-label">Groupe administrateur</label>
                <div class="col-md-3">
                    <select name="group" class="form-control">
                        <option value="">Choisissez</option>
                        <?php foreach ($this->groupe_list as $groupe_info) : ?>
                            <option
                                <?php echo $this->fgroup == $groupe_info['id'] ? "selected='selected'" : '' ?>
                                    value="<?php hecho($groupe_info['id']) ?>"
                            >
                                <?php hecho($groupe_info['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

            <label for="name-contain" class="col-md-3 control-label">Un SIRET contient</label>
            <div class="col-md-3">
                <input
                        id="siret-contain"
                        class="form-control"
                        type="text"
                        name="siret"
                        size="20"
                        maxlength="25"
                        value="<?php hecho($this->fsiret) ?>"
                />
            </div>
            </div>

        <div class="form-group">
            <label for="name-contain" class="col-md-3 control-label">SIREN</label>
            <div class="col-md-3">
                <input
                        id="siren"
                        class="form-control"
                        type="text"
                        name="siren"
                        size="20"
                        maxlength="25"
                        value="<?php hecho($this->fsiren) ?>"
                />
            </div>
        </div>

        <div class="form-group">
            <button class="btn btn-default col-md-offset-3 col-md-3" type="submit">Filtrer</button>
        </div>
    </form>
</div>
<br />

<h2 id="liste_coll_desc">Liste des collectivités</h2>
<div id="authority-list">
    <table class="data-table table table-striped" aria-describedby="liste_coll_desc">
        <thead>
        <tr>
            <th id="name">Nom</th>
            <th id="actes-group">Groupe Actes</th>
            <th id="helios-group">Groupe Helios</th>
            <th id="authority-type">Type de collectivité</th>
            <th id="address">Adresse</th>
            <th id="phone">Téléphone</th>
            <th id="fax">SIREN</th>
            <th id="actions">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($this->authorities as $authority_info) :?>
        <tr>
            <td headers="name"><?php hecho($authority_info['name']) ?></td>
            <td headers="actes-group"><?php hecho($authority_info['actes_group_name'] ?: 'Aucun') ?></td>
            <td headers="helios-group"><?php hecho($authority_info['helios_group_name'] ?: 'Aucun') ?></td>
            <td headers="authority-type"><?php hecho($authority_info['type_name']) ?></td>
            <td headers="address"  class="long_field">
                <?php echo nl2br(get_hecho($authority_info['address'])) ?>
                <br />
                <?php hecho($authority_info['postal_code']) ?>
                <?php hecho($authority_info['city']) ?>
            </td>
            <td headers="phone"><?php hecho($authority_info['telephone']) ?></td>
            <td headers="fax"><?php hecho($authority_info['siren']) ?></td>
            <td headers="actions">
                <a href="admin_authority_edit.php?id=<?php echo $authority_info['id'] ?>" class="icon">
                    <img
                            src="<?php echo Helpers::getLink('/custom/images/erreur.png'); ?>"
                            alt="image_modif"
                            title="Modifier"
                    />
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

