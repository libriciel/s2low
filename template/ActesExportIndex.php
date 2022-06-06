<script src="/javascript/date-picker.js" type="text/javascript"></script>
<script type="text/javascript" src="/javascript/jfu/js/jquery.min.js"></script>
<script type="text/javascript" src="/javascript/zselect.js"></script>
<script type="text/javascript" src="/javascript/zselect_s2low.js"></script>
<link rel="stylesheet" type="text/css" href="/custom/styles/date-picker.css" />


<h1><?php hecho($title) ?></h1>

<div id="filtering-area">
    <h2>Filtrage</h2>
    <form class="form-horizontal" action="actes_export_handler.php">
        <?php if ($me->isGroupAdminOrSuper()) : ?>
            <div class="form-group">
                <label for="authority_id" class="col-md-3 control-label">Collectivité</label>
                <div class="col-md-3">
                    <select class="form-control zselect_authorities" name="authority_id" id="authority_id">
                        <option value="">Toutes</option>
                        <?php foreach ($authority_id_list as $key => $val) : ?>
                            <option value="<?php hecho($key) ?> " <?php echo (strcmp($key, $authority_id) == 0) ? " selected='selected'" : ""; ?> >
                                <?php hecho($val)?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php else: ?>
            <input type="hidden" name="authority_id" value="<?php hecho($authority_id) ?>"/>
        <?php endif;?>

        <div class="form-group">
            <label for="date_debut" class="col-md-3 control-label">Date de début</label>
            <div class="col-md-3">
                <input id="date_debut" name="date_debut" type="hidden" value="<?php hecho($date_debut) ?>"/>
                <script type="text/javascript">
                    obj_date_debut = new DatePicker('date_debut', 'fr');
                </script>
                <a href="#datepicker" id="datepicker_date_debut_link" class="datepicker_link" onclick="javascript:obj_date_debut.toggleDatePicker(); return false;">
                    <?php if ($date_debut) :?>
                        <?php echo Helpers::TimestampToString(Helpers :: ansiDateToTimestamp($date_debut)); ?>
                    <?php else: ?>
                        Choisir une date
                    <?php endif; ?>
                </a>
                <div class="date_picker" style="display: none;" id="datepicker_date_debut_calendar">
                </div>

            </div>
            <label for="date-fin" class="col-md-3 control-label">Date de fin</label>
            <div class="col-md-3">
                <input id="date_fin" name="date_fin" type="hidden" value="<?php hecho($date_fin) ?>"/>
                <script type="text/javascript">
                    obj_date_fin = new DatePicker('date_fin', 'fr');
                </script>
                <a href="#datepicker" id="datepicker_date_fin_link" class="datepicker_link" onclick="javascript:obj_date_fin.toggleDatePicker(); return false;">
                    <?php if ($date_fin) :?>
                        <?php echo Helpers ::TimestampToString( Helpers :: ansiDateToTimestamp($date_fin)); ?>
                    <?php else: ?>
                        Choisir une date
                    <?php endif; ?>
                </a>
                <div class="date_picker" style="display: none;" id="datepicker_date_fin_calendar">
                </div>
            </div>
        </div>

        <div class="form-group">
            <button class="btn btn-primary col-md-offset-3 col-md-3" type="submit">Exporter</button>
        </div>
    </form>
</div>