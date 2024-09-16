<?php

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\DatePicker;

?>

<script type="text/javascript" src="<?php echo Helpers::getLink('/jsmodules/jquery.js');?>"></script>
<script type="text/javascript" src="<?php echo Helpers::getLink('/jsmodules/jqueryui.js');?>"></script>


<h1><?php echo $h1_title ?></h1>

<?php if ($has_logs_request) : ?>
<div id="actions-area">
    <h2>Action</h2>
    <a class="btn btn-primary bouton" href="/common/logs_request_view.php" >Demandes de journal</a>
</div>
<?php endif; ?>

<div id="filtering_area">
    <h2>Filtrage</h2>
    <form action="logs_view.php" method="get" role="form">
        <div class="form-group row">
            <label for="module" class="col-md-3 control-label">Module</label>
            <div class="col-md-3">
                <select name="module" class="form-control">
                    <option value="">Choisissez</option>
                    <?php foreach ($module_list as $module_info) : ?>
                        <option
                                value="<?php hecho($module_info['name']) ?>"
                            <?php echo $fmodule == $module_info['name'] ? 'selected="selected"' : '' ?>
                        >
                            <?php hecho($module_info['name'])?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label for="severity-choice" class="col-md-3 control-label">Sévérité</label>
            <div class="col-md-3">
                <select name="severity" class="form-control">
                    <option value="-1" <?php  echo $fseverity == -1 ? 'selected="selected"' : ''?>>Choisissez</option>
                    <?php foreach ($loglevel_list as $loglevel_id => $loglevel_libelle) : ?>
                        <option
                                value="<?php echo $loglevel_id?>"
                            <?php  echo $fseverity == $loglevel_id ? 'selected="selected"' : ''?>
                        >
                            <?php echo $loglevel_libelle ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label for="date_debut" class="col-md-3 control-label">Date de début</label>
            <div class="col-md-3">
                <?php $datePickerDebut = new DatePicker('date_debut', $date_debut);
                echo $datePickerDebut->show(); ?>
            </div>
            <label for="date-fin" class="col-md-3 control-label">Date de fin</label>
            <div class="col-md-3">
                <?php $datePickerFin = new DatePicker('date_fin', $date_fin);
                echo $datePickerFin->show(); ?>
            </div>
        </div>
        <div class="form-group row">
            <label for="msg-contain" class="col-md-3 control-label">Message contient</label>
            <div class="col-md-3">
                <input
                        id="msg-contain"
                        class="form-control"
                        type="text"
                        name="message"
                        size="20"
                        maxlength="25"
                        value="<?php hecho($fmessage) ?>"
                />
            </div>
        </div>
        <?php if ($this->me->isAdmin()) : ?>
            <div class="form-group row">
                <?php   if ($this->me->isGroupAdminOrSuper()) : ?>
                    <label for="collectivity-choice" class="col-md-3 control-label">Collectivité</label>
                    <div class="col-md-3">
                        <select name="authority" class="form-control">
                            <option value="">Choisissez</option>
                            <?php foreach ($authorities_list as $authority_id => $authority_name) : ?>
                                <option
                                        value="<?php hecho($authority_id) ?>"
                                    <?php echo $fauthority == $authority_id ? 'selected="selected"' : '' ?>
                                >
                                    <?php hecho($authority_name) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                <?php endif; ?>
                <label for="username-contain" class="col-md-3 control-label">Nom utilisateur contient</label>
                <div class="col-md-3">
                    <input id="username-contain" class="form-control" type="text" name="user" size="20" maxlength="25"  value="<?php hecho($fuser) ?>" />
                </div>
            </div>
        <?php endif; ?>
        <div class="form-group row">
            <button type="submit" class="col-md-offset-3 col-md-3 btn btn-default">Filtrer</button>
        </div>
    </form>
</div>
<h2>Entrées du journal</h2>
<div id="journal_area">
    <?php if (! $logs_list) : ?>
        Aucune entrée du journal ne correspond au filtrage spécifié.
    <?php else : ?>
        <table class="logs data-table table table-striped">
            <caption>Liste des événements du journal en fonction des choix de filtrage</caption>
            <thead>
                <tr>
                    <th id="date" class="data">Date</th>
                    <th id="author" class="data">Créé par</th>
                    <th id="severity" class="data">Sévérité</th>
                    <th id="module" class="data">Module</th>
                    <th id="user" class="data">Utilisateur</th>
                    <th id="message" class="data">Message</th>
                    <th id="timestamp" class="data">Horodatage</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($logs_list as $logEntry) : ?>
                <tr>
                    <td headers="date">
                        <?php echo Helpers::getDateFromBDDDate($logEntry['date'], true) ?>
                    </td>
                    <td headers="author">
                        <?php hecho($logEntry['issuer']) ?>
                    </td>
                    <td headers="severity">
                        <?php hecho($loglevel_list[$logEntry['severity']]) ?>
                    </td>
                    <td headers="module">
                        <?php hecho($logEntry['module']) ?>
                    </td>
                    <td headers="user">
                        <?php
                        hecho($userSQL->getPrettyName($logEntry['name'], $logEntry['givenname'], $logEntry['login']))
                        ?>
                    </td>

                    <td class="long_field" headers="message">
                        <?php echo nl2br(get_hecho($logEntry['message'])) ?>
                    </td>
                    <td headers="timestamp">
                            <a href="<?php
                            echo Helpers::getLink('/common/logs_get_timestamp.php?id=' . $logEntry['id']);
                            ?>"
                               title="Télécharger une archive contenant l'entrée de journal n°<?php
                                echo $logEntry['id'] ?> et sa signature"
                               class="icon">
                                <img src="<?php echo Helpers::getLink('/custom/images/timestamping_icon.png'); ?>"
                                     alt="timestamp"
                                />
                            </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>