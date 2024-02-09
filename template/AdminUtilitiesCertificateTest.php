<h1>Tester un certificat</h1>

<p id="back-transaction-btn">
    <a class="btn btn-default" href="/admin/utilities/certificate_list.php">Revenir à la page précédente
    </a><br>
</p>

<h2 id="certificat_a_tester_desc">Certificat à tester</h2>

<form action="/admin/utilities/do-test-certificate.php" method="POST" enctype="multipart/form-data">
    <table class="data-table table table-striped" aria-describedby="certificat_a_tester_desc">
        <tr>
            <th scope="row"><label for="titre">Certificat (format PEM)</label></th>
            <td>
                <input type="file" id="certificat" name="certificat" class="form-control"  />
            </td>
        </tr>
        <tr>
            <th scope="row">&nbsp;</th>
            <td><input type="submit" value="Tester" class="btn btn-primary"/></td>
        </tr>

    </table>

</form>


<?php if ($certificate_info) : ?>
<h2 id="analyse_cert_desc">Analyse du certificat</h2>

    <table class="data-table table table-striped" aria-describedby="analyse_cert_desc">
        <tr>
            <th scope="row">Certificat RGS</th>
            <td>
                <?php if ($certificate_info['is_rgs']) :?>
                    <div class="alert-success alert">Ce certificat est RGS</div>
                <?php else :?>
                    <div class="alert-danger alert">Ce certificat n'est pas RGS</div>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">Certificat de connexion</th>
            <td>
                <?php if ($certificate_info['is_extended']) :?>
                    <div class="alert-success alert">Ce certificat est reconnu</div>
                <?php else :?>
                    <div class="alert-danger alert">Ce certificat n'est pas reconnu</div>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">Utilisateur(s)</th>
            <td>
                <?php if ($certificate_info['nb_users']) :?>
                    <div class="alert-success alert">
                        <?php echo $certificate_info['nb_users']; ?> utilisateur(s) correspond(ent) à ce certificat
                        <a class="btn btn-primary" href="/admin/users/admin_user_list.php?user_id=<?php echo $certificate_info['user_id'] ?>">Voir</a>
                    </div>
                <?php else :?>
                    <div class="alert-danger alert">Ce certificat n'est pas utilisé</div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

<h2 id="info_compl_desc">Informations complémentaires</h2>

    <table class="data-table table table-striped" aria-describedby="info_compl_desc">
        <?php foreach ($certificate_info['certificate_info'] as $key => $value) : ?>
        <tr>
            <th scope="row"><?php hecho($key)?></th>
            <td>
                <?php if (is_array($value)) :?>
                    <?php echo json_encode($value) ?>
                <?php else :?>
                    <?php hecho($value) ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

<?php endif; ?>

