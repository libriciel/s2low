<?php

use S2lowLegacy\Class\DatePicker;
use S2lowLegacy\Class\Helpers;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

?>

<script type="text/javascript" src="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/jsmodules/jquery.js")?>"></script>
<script type="text/javascript" src="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/jsmodules/jqueryui.js")?>"></script>
<script type="text/javascript" src="<?php echo \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/jsmodules/select2.js")?>"></script>
<script type="text/javascript" src="/javascript/zselect_s2low.js"></script>

<?php $loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader); ?>

<h1><?php hecho($this->viewParameter['title']) ?></h1>

<div id="filtering-area">
    <h2>Filtrage</h2>
    <form class="form-horizontal" action="actes_export_handler.php">
        <?php if ($this->me->isGroupAdminOrSuper()) : ?>
            <div class="form-group">
                <label for="authority_id" class="col-md-3 control-label">Collectivité</label>
                <div class="col-md-3">
                    <select class="form-control zselect_authorities" name="authority_id" id="authority_id">
                        <option value="">Toutes</option>
                        <?php foreach ($this->authority_id_list as $key => $val) : ?>
                            <option value="<?php hecho($key) ?> " <?php echo (strcmp($key, $this->authority_id) == 0) ? " selected='selected'" : ""; ?> >
                                <?php hecho($val)?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php else : ?>
            <input type="hidden" name="authority_id" value="<?php hecho($this->authority_id) ?>"/>
        <?php endif;?>

        <div class="form-group">
            <label for="date_debut" class="col-md-3 control-label">Date de début</label>
            <div class="col-md-3">
            <?php
            $datePickerDebut = new DatePicker("date_debut", $this->date_debut);
            echo $datePickerDebut->show();
            ?>
            </div>
            <label for="date-fin" class="col-md-3 control-label">Date de fin</label>
            <div class="col-md-3">
            <?php
            $datePickerFin = new DatePicker("date_fin", $this->date_debut);
            echo $datePickerFin->show();
            ?>
            </div>
        </div>

        <div class="form-group">
            <button class="btn btn-primary col-md-offset-3 col-md-3" type="submit">Exporter</button>
        </div>
    </form>
</div>
