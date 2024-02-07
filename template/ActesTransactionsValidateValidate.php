<h1>Validation d'une archive acte</h1>
<p id="back-transaction-btn">
    <a class="btn btn-default" href='actes_transac_show.php?id=<?php echo $transaction_id ?>'>Retour à la transaction</a><br/>
</p>
<h2>Validation du fichier <?php echo $envelope_filename?></h2>


<?php if ($archive_is_valide) : ?>
    <div class="alert alert-success"><?php echo $validation_message?></div>
<?php else : ?>
    <div class="alert alert-danger"><?php echo $validation_message?></div>
<?php endif; ?>


<table class="table table-bordered">
    <tr>
        <th scope="col">Niveau</th>
        <th scope="col">Code</th>
        <th scope="col">Message</th>
        <th scope="col">Ligne</th>
        <th scope="col">Colonne</th>
    </tr>
    <?php foreach ($error_xml as $i => $info) : ?>
        <tr>
            <td><?php hecho($info->level)?></td>
            <td><?php hecho($info->code)?></td>
            <td><?php hecho($info->message)?></td>
            <td><?php hecho($info->line)?></td>
            <td><?php hecho($info->column)?></td>
        </tr>
    <?php endforeach; ?>
</table>


<h2>Validation de la signature PADES</h2>


<?php if ($pades_is_valide) : ?>
    <div class="alert alert-success">Les fichiers ne sont pas signés ou toutes les signatures sont valides</div>
<?php else : ?>
    <div class="alert alert-danger">Au moins un fichier a une signature invalide</div>
<?php endif; ?>

<table class="table table-bordered">
    <tr>
        <th scope="col">Fichier</th>
        <th scope="col">Signé ?</th>
        <th scope="col">Signature valide</th>
        <th scope="col">Message</th>
        <th scope="col">pades-valid</th>
    </tr>
    <?php foreach ($pades_result as $filename => $info) : ?>
        <tr>
            <td><?php hecho($filename)?></td>
            <td><?php echo $info['is_signed'] ? "OUI" : "NON"?></td>
            <td><?php echo $info['is_valid'] ? "OUI" : "NON" ?></td>
            <td><?php hecho($info['message']) ?></td>
            <td><?php hecho($info['last_result'])?></td>
        </tr>
    <?php endforeach; ?>
</table>

