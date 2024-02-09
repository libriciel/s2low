<div id="content">
    <h1>Journal - Requête sur l'historique</h1>
    <p id="back-user-btn">
        <a href="/common/logs_view.php" class="btn btn-default" title="">
            Retour au journal
        </a>
    </p>

    <div class="alert alert-warning">
        Votre demande concerne des transactions trop anciennes. Veuillez confirmer votre demande. Vous recevrez alors un email vous permettant de télécharger un fichier CSV contenant le résultat de la requête.
    </div>

    <h2 id = "faire_une_demande_desc">Faire une demande</h2>

    <form action="/common/logs_request.php" method="POST">
    <table class="data-table table table-striped" aria-describedby="faire_une_demande_desc">
        <tr>
            <th scope="row">Date de début</th>
            <td><?php echo $this->fancyDate->getDateFrancais($date_debut) ?>
            <input type="hidden" name="date_debut" value="<?php echo $date_debut ?>" ?>
            </td>
        </tr>
        <tr>
            <th scope="row">Date de fin</th>
            <td><?php echo $this->fancyDate->getDateFrancais($date_fin) ?>
                <input type="hidden" name="date_fin" value="<?php echo $date_fin ?>" ?>
            </td>
        </tr>

    </table>
        <?php if ($has_pending_logs_request) :  ?>
        <div class="alert alert-danger">Vous avez déjà une requête en cours. Veuillez attendre ou annuler la requete précédente.</div>
        <?php else : ?>
        <input type="submit" value="Confirmer la demande" class="btn btn-primary"/>
        <?php endif; ?>
    </form>


</div>

<h1></h1>