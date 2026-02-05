<?php
/** @var string $returnMsg */
?>
<h1> Mail - Système de mail sécurisé</h1>

    <h2>Actions</h2>
    <div id="actions_area"> 
        <a href="index.php?command=create" class="btn btn-default">Nouveau message</a>
        <a href="index.php?command=lsit" class="btn btn-default">Messages envoyés</a>
    </div>
    <h2>L'envoi a echoué</h2> 
    
    <p class="alert alert-danger"><?php echo $returnMsg ?></p>