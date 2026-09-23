<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\DatePicker;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\FancyDate;

class ListeActesHTML
{
    private $allCollectivite;
    private $filtreAuthority;
    private $actionBox;
    private $fmin_submission_date;
    private $fmin_ack_date;
    private $fmax_submission_date;
    private $fmax_ack_date;
    private $transTypes;
    private $ftype;
    private $transNatures;
    private $fnature;
    private $status;
    private $fstatus;
    private $fnum;
    private $objet;
    private FancyDate $fancyDate;
    private string $sortWay = 'desc';

    /**
     * @param $allCollectivite
     * @param $filtreAuthority
     * @return void
     */
    public function addCollectivite($allCollectivite, $filtreAuthority): void
    {
        $this->allCollectivite = $allCollectivite;
        $this->filtreAuthority = $filtreAuthority;
    }

    /**
     * @return void
     */
    public function addActionBox(): void
    {
        $this->actionBox = true;
    }

    /**
     * @param $fmin_submission_date
     * @param $fmin_ack_date
     * @param $fmax_submission_date
     * @param $fmax_ack_date
     * @param \S2lowLegacy\Lib\FancyDate $fancyDate
     * @return void
     */
    public function setDate(
        $fmin_submission_date,
        $fmin_ack_date,
        $fmax_submission_date,
        $fmax_ack_date,
        FancyDate $fancyDate
    ): void {
        $this->fmin_submission_date = $fmin_submission_date;
        $this->fmin_ack_date = $fmin_ack_date;
        $this->fmax_submission_date = $fmax_submission_date;
        $this->fmax_ack_date = $fmax_ack_date;
        $this->fancyDate = $fancyDate;
    }

    /**
     * @param $transTypes
     * @param $ftype
     * @param $transNatures
     * @param $fnature
     * @param $status
     * @param $fstatus
     * @param $fnum
     * @param $objet
     * @return void
     */
    public function setCritere($transTypes, $ftype, $transNatures, $fnature, $status, $fstatus, $fnum, $objet): void
    {
        $this->transTypes = $transTypes;
        $this->ftype = $ftype;
        $this->transNatures = $transNatures;
        $this->fnature = $fnature;
        $this->status  = $status;
        $this->fstatus = $fstatus;
        $this->fnum = $fnum;
        $this->objet = $objet;
    }

    /**
     * @param $enveloppe
     * @return void
     */
    public function display($enveloppe): void
    {
        $this->displayForm();
        ?>
        <script type="text/javascript" src="<?php echo Helpers::getLink('/jsmodules/jquery.js')?>"></script>
        <script type="text/javascript" src="<?php echo Helpers::getLink('/jsmodules/select2.js')?>"></script>
        <script type="text/javascript" src="/javascript/zselect_s2low.js"></script>
        
        <h2>
                    Liste des enveloppes de transactions
        <?php
        if ($enveloppe) {
            ?>
                    <button id="expand-all" onclick="javascript:show_all();" class="toggle-action">Tout déplier<span class="hidden-info">les enveloppes de transactions</span></button>
                    <button id="collapse-all" onclick="javascript:hide_all()" class="toggle-action">Tout replier<span class="hidden-info">les enveloppes de transactions</span></button>
                </h2>
                    <?php $this->displayList($enveloppe);
        } else {
            ?>
                     </h2>       Pas de transaction trouvée correspondant aux critères de filtrage
                    <?php
        }
    }

    /**
     * @param string $sortWay
     * @return void
     */
    public function setSortWay(string $sortWay): void
    {
        $this->sortWay = $sortWay;
    }

    /**
     * @return void
     */
    public function displayForm(): void
    {
        $envelopeSubmissionEnabled = LegacyObjectsManager::getLegacyObjectInstancier()->getParameter('app.envelope_submission_enabled');
        ?>
        <?php if ($this->actionBox) : ?>
        <div id="actions_area">
            <h2>Actions</h2>
            <a href="<?php echo  Helpers::getLink('/modules/actes/actes_transac_add.php');?>" class="btn btn-primary">Créer une transaction</a>
            <?php if ($envelopeSubmissionEnabled) : ?>
                <a href="<?php echo  Helpers::getLink('/modules/actes/actes_transac_import.php');?>" class="btn btn-primary">Importer une enveloppe</a>
            <?php endif; ?>
            <a href="<?php echo  Helpers::getLink('/modules/actes/actes_batch_handle.php');?>" class="btn btn-primary">Traitement par lots</a>
        </div>
        <?php endif;?>
    <h2>
        <span>Filtrage</span>
        <button id="expand-all" onclick="javascript:expand_area('filtering-area');" class="toggle-action">Tout déplier<span class="hidden-info">le formulaire de filtrage</span></button>
        <button id="collapse-all" onclick="javascript:collapse_area('filtering-area');" class="toggle-action">Tout replier<span class="hidden-info">le formulaire de filtrage</span></button>
    </h2>
    <div id="filtering-area" >
    <form action="<?php echo Helpers::getLink('/modules/actes/index.php'); ?>" method="get" role="form" class="form-horizontal">
            <div class="form-group">
                <label for="type" class="col-md-3 control-label">Type de transaction</label>
                <div class="col-md-3">
                    <?php $this->getHTMLSelect('type', $this->transTypes, $this->ftype) ?>
                </div>
                <label for="nature" class="col-md-3 control-label">Nature d'actes</label>
                <div class="col-md-3">
                    <?php $this->getHTMLSelect('nature', $this->transNatures, $this->fnature) ?>
                </div>
            </div>
            <div class="form-group">
                <label for="status" class="col-md-3 control-label">État</label>
                <div class="col-md-3">
                    <?php $this->getHTMLSelect('status', $this->status, $this->fstatus) ?>
                </div>
                <label for="number" class="col-md-3 control-label">Le numéro contient</label>
                <div class="col-md-3">
                    <input id="number" class="form-control" type="text" name="num" size="20" maxlength="25" value="<?php hecho($this->fnum) ?>" />
                </div>
            </div>
            <div class="form-group">
                <label for="object" class="col-md-offset-6 col-md-3 control-label">L'objet contient</label>
                <div class="col-md-3">
                    <input id="object" class="form-control" type="text" name="objet" size="20" maxlength="25" value="<?php hecho($this->objet) ?>" />
                </div>
            </div>
            <div class="form-group">
                <label for="min_submission_date" class="col-md-3">Date de postage minimale</label>
                <div class="col-md-3">
                    <?php $this->datePicker($this->fmin_submission_date, 'min_submission_date') ?>
                </div>
                <label for="min_ack_date" class="col-md-3">Date d'acquittement minimale</label>
                <div class="col-md-3">
                    <?php $this->datePicker($this->fmin_ack_date, 'min_ack_date') ?>
                </div>
            </div>
            <div class="form-group">
                <label for="max_submission_date" class="col-md-3">Date de postage maximale</label>
                <div class="col-md-3">
                    <?php $this->datePicker($this->fmax_submission_date, 'max_submission_date') ?>
                </div>
                <label for="max_ack_date" class="col-md-3">Date d'acquittement maximale</label>
                <div class="col-md-3">
                    <?php $this->datePicker($this->fmax_ack_date, 'max_ack_date') ?>
                </div>
            </div>
            <?php if ($this->allCollectivite) : ?>
            <div class="form-group">
                <label for="authority" class="col-md-3 control-label">Collectivité</label>
                <div class="col-md-3">
                    <?php $this->getHTMLSelect('authority', $this->allCollectivite, $this->filtreAuthority, 'zselect_authorities') ?>
                </div>
            </div>
            <?php endif;?>
            <div class="form-group">
                <button type="submit" class="col-md-offset-3 col-md-3 btn btn-default">Filtrer</button>
                <a href="<?php echo Helpers::getLink('/modules/actes/index.php'); ?>" class="col-md-offset-3 col-md-3 btn btn-default">
                    Remise à zéro
                </a>
            </div>
    </form>
</div>


        <?php
    }

    /**
     * @param $name
     * @param $data
     * @param $selectedValue
     * @param $css_class
     * @return void
     */
    public function getHTMLSelect($name, $data, $selectedValue, $css_class = ''): void
    {
        ?>
        <select name="<?php echo $name ?>" id="<?php echo $name ?>" class="form-control <?php echo $css_class?>">
            <option value="">Choisissez</option>
            <?php foreach ($data ?? [] as $key => $val) : ?>
                <option value="<?php echo $key ?>" <?php echo (strcmp($key, $selectedValue) == 0) ? 'selected="selected"' : '';?>>
                    <?php hecho($val)?>
                </option>
            <?php endforeach;?>
        </select>
        <?php
    }

    /**
     * @param $date
     * @param $name
     * @return void
     */
    private function datePicker($date, $name): void
    {
        $datePicker = new DatePicker($name, $date);
        echo $datePicker->show();
    }

    /**
     * @param $envelopes
     * @return void
     */
    public function displayList($envelopes): void
    {
        ?>

                <div id="enveloppe-area">
                    <form id="div_chck" onsubmit="return afficheWarning()" action="<?php echo Helpers::getLink('/modules/actes/actes_transac_close.php'); ?>" method="post">
                        <div class="form-group">
                            <dl class="envelopes_list">
                            <?php foreach ($envelopes as $i => $envelope) : ?>
                                    <?php $this->displayEnvelope($envelope, $i);?>
                            <?php endforeach;?>
                            </dl>
                            <div id="selection-actions">
                                    <a href="#tedetis" onclick="GereChkbox('div_chck','1');" title="Tout sélectionner" class="btn btn-default">Tout sélectionner</a>
                                    <a href="#tedetis" onclick="GereChkbox('div_chck','0');" title="Tout désélectionner" class="btn btn-default">Tout desélectionner</a>
                                    <a href="#tedetis" onclick="GereChkbox('div_chck','2');" title="Inverser la sélection" class="btn btn-default">Inverser la sélection</a>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="change-status" class="col-md-5 control-label">Passer les transactions sélectionnées en état</label>
                            <div class="col-md-2">
                                <select id ="change-status" name="status" class="form-control">
                                    <option value="valid">Validé</option>
                                    <option value="invalid">Refusé</option>
                                    <option value="sae">En attente de transmission au SAE</option>
                                </select>
                                </div>
                            <button type="submit" class="btn btn-default col-md-offset-3 col-md-2">Exécuter</button>
                        </div>
                    </form>
                </div>
        <?php
    }

    /**
     * @param $envelope
     * @param $i
     * @return void
     */
    public function displayEnvelope($envelope, $i): void
    {
        ?>
            <dl><dt>
                <a href="#tedetis" onclick="toggle_envelope_content(<?php echo $i ?>);" id="expander_<?php echo $i?>" class="expander btn btn-default btn-xs">-</a>
                1 transaction de l'enveloppe n°<a href="<?php echo Helpers::getURLWithParam(['order' => 'id', 'sortway' => $this->sortWay == 'asc' ? 'desc' : 'asc']) ?>"
                                title="Trier par identifiant"><?php echo $envelope['envelope_id'] ?></a>
                déposée le <a href="<?php echo Helpers::getURLWithParam(['order' => 'submission_date', 'sortway' => $this->sortWay == 'asc' ? 'desc' : 'asc']) ?>"
                                title="Trier par date de dépôt"><?php echo Helpers :: getDateFromBDDDate($envelope['submission_date'], true) ?></a>
                <?php if ($this->allCollectivite) : ?>
                    de la collectivité <?php hecho($envelope['authority_name']) ?>
                <?php endif;?>
                </dt></dl>
            <dd id="envelope_content_<?php echo $i ?>" class="envelope_content" style="display: block">
                <table id="transactions-list" class="data-table table table-bordered">
                                    <caption>Liste des transactions en fonction des choix de filtrage</caption>
                                    <thead>
                    <tr class="active">
                                            <th id="selection">Sél.</th>
                                            <th id="transaction-type">Type de transaction</th>
                                            <th id="act-number">Numéro de l'acte</th>
                                            <th id="act-internal-number">Numéro Interne de l'acte</th>
                                            <th id="object">Objet</th>
                                            <th id="nature">Nature</th>
                                            <th id="status">Etat</th>
                                            <th id="mail">courrier ministère</th>
                                            <th id="follower">Suivie par</th>
                                            <th id="actions">Actions</th>
                    </tr>
                                    </thead>
                                    <tbody>
                    <tr>
                                            <td headers="selection">
                                                <?php if ($envelope['type'] == 1 && (in_array($envelope['current_status'], [4,5,14,18,20]))) : ?>
                                                    <input type="checkbox" 
                                                                    name="liste_id[]" 
                                                                    value="<?php hecho($envelope['transaction_id']) ;?>" 
                                                                    id="checkbox<?php hecho($envelope['transaction_id']) ;?>" />
                                                <?php else : ?>
                                                    &nbsp;
                                                <?php endif; ?>
                                            </td>
                                            <td headers="transaction-type"><?php echo $envelope['type_str'] ?></td>
                                            <td headers="act-number"><?php echo $envelope['transaction_id'] ?></td>
                                            <td headers="act-internal-number"><?php hecho($envelope['number']) ?></td>
                                            <td headers="object" class="long_field"><?php echo nl2br(get_hecho(Helpers :: truncateString($envelope['subject']))) ?></td>
                                            <td headers="nature"><?php echo $envelope['nature_descr'] ?></td>
                                            <td headers="status"><?php echo $envelope['current_status_name'] ?></td>
                                            <td headers="mail">
                                                    <?php
                                                    foreach ($envelope['courrier_info'] as $id => $info) : ?>
                                                            <a href="<?php echo Helpers::getLink("/modules/actes/actes_transac_show.php?id=$id"); ?>">
                                                                    <?php echo $info['type_str'] ?>
                                                                    (<?php echo isset($info['sens']) ? $info['sens'] : 'envoyé' ?>)
                                                            </a>
                                                            <br/>
                                                    <?php endforeach; ?>
                                            </td>
                                            <td headers="follower"><?php echo $envelope['givenname'] . ' ' . $envelope['name'] ?></td>
                                            <td headers="actions">
                                                    <a href="<?php echo Helpers::getLink('/modules/actes/actes_transac_show.php?id=' . $envelope['transaction_id']);?>"
                                                                    class="icon">
                                                            <img src="<?php echo Helpers::getLink('/custom/images/erreur.png'); ?>"
                                                                            alt="image_modif" title="Afficher le détail" />
                                                    </a>
                                                    <?php if ($envelope['archive_url']) : ?>
                                                                <a href="<?php echo $envelope['archive_url'] ?>"
                                                                    class="icon">
                                                                    <img src="<?php echo Helpers::getLink('/custom/images/icone_archivage.png'); ?>"
                                                                                    alt="image_archivage" title="Accéder à  l'archivage de cette transaction" />
                                                                </a>
                                                    <?php endif;?>
                                            </td>
                    </tr>
                                    </tbody>
                </table>
            </dd>
        <?php
    }
}


