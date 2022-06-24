#! /usr/bin/php
<?php

/**
 * Ce script permet de mettre à jour automatiquement S2low
 * Le script doit être copier en dehors du code, il est indépendant du reste du code
 * Le script se base sur le numéro de révision présent sur le SVN et celui présent à la fin du répertoire contenant le programme
 * Le script met à jour le numéro de révision dans le fichier revision.txt
 *
 */

//Supprimer la ligne suivante après avoir configuré le script
if (true) {
    exit;
}

//Indiquer le lien symbolique du Pastell
$symlink_to_current_pastell = "/var/www/tedetis";

//Indiquer ici la branche à partir de laquelle mettre à jour
$svn_pastell_branche_url = "https://scm.adullact.net/anonscm/svn/s2low/TedetisPHP/trunk/";


$libersign_path = "/var/www/libersign_1.6.2/";

//Fin de la configuration


echo "Lecture du numéro de révision courant sur $symlink_to_current_pastell\n";
$symlink_content = readlink($symlink_to_current_pastell);

if (! $symlink_content) {
    throw new Exception("Le lien $symlink_content ne pointe sur rien ou n'est pas un lien");
}

if ($symlink_content[0] != '/') {
    throw new Exception("Le lien $symlink_content ne pointe pas sur un chemin absolu");
}

echo "Répertoire réel de l'installation courante : $symlink_content\n";

preg_match("#^(.*)-rev(\d+)$#", $symlink_content, $matches);
if (empty($matches[2])) {
    throw new Exception("Le nom $symlink_content ne contient pas le numéro de révision à la fin XXXX-revYYYY");
}
$local_revision = $matches[2];
$path_start = $matches[1];
echo "Numéro de révision local: $local_revision\n";



echo "Récupération du numéro de révision sur le chemin SVN : $svn_pastell_branche_url\n";
$svnWrapper = new SVNWrapper();
$info = $svnWrapper->getInfo($svn_pastell_branche_url);
preg_match("#Revision: (\d+)#", $info, $matches);
if (empty($matches[1])) {
    throw new Exception("Impossible de trouver le numéro de révision du chemin SVN $svn_pastell_branche_url");
}
$svn_revision = $matches[1];
echo "Numéro de révision SVN : $svn_revision\n";

preg_match("#Last Changed Date: (.*)#", $info, $matches);
$svn_date = $matches[1];
echo "Date de la dernière révision: $svn_date\n";


if ($svn_revision < $local_revision) {
    throw new Exception("Le numéro de révision SVN est plus PETIT que le numéro de révision locale !!!!!????");
}
if ($svn_revision == $local_revision) {
    echo "Le numéro de revision du SVN correspond au numéro local : le logiciel est à jour\n";
    exit(0);
}

$new_path = $path_start . "-rev{$svn_revision}";

if (file_exists($new_path)) {
    echo ("Le répertoire $new_path existe déjà !\n");
} else {
    echo "Récuperation SVN de $svn_pastell_branche_url vers $new_path\n";
    $svnWrapper->export($svn_pastell_branche_url, $new_path);
    echo "Récuperation terminé\n";
}

echo "Copie du fichier LocalSettings.php\n";
if (! copy($symlink_content . "/config/LocalSettings.php", $new_path . "/config/LocalSettings.php")) {
    throw new Exception("Impossible de copier le fichier LocalSettings.php");
}


echo "Correction du fichier revision.txt\n";
$manifest_content = file_get_contents($new_path . "/revision.txt");

$new_manifest_content = preg_replace('#\$Rev: \d+ \$#', "\$Rev: $svn_revision \$", $manifest_content);
$new_manifest_content = preg_replace('#\$LastChangedDate: [^\$]+ \$#', "\$LastChangedDate: $svn_date \$", $new_manifest_content);
file_put_contents($new_path . "/revision.txt", $new_manifest_content);


symlink($new_path . "/public.ssl/custom", $new_path . "/public/custom");
symlink($new_path . "/public.ssl/javascript", $new_path . "/public/javascript");

symlink($libersign_path, $new_path . "/public.ssl/libersign");

echo "Mise à jour du lien symbolique\n";
unlink($symlink_to_current_pastell);
symlink($new_path, $symlink_to_current_pastell);
echo "Déploiement terminé\n";


class SVNWrapper
{
    private function exec($commande)
    {
        $commande .= " 2>&1";
        exec($commande, $out, $ret);

        if ($ret) {
            throw new Exception("La Commande >$commande< a échoué ; Résultat : " . implode("\n", $out));
        };

        return implode("\n", $out);
    }

    public function export($url, $path)
    {
        $result = $this->exec("svn export --non-interactive --no-auth-cache $url $path");
        return $result;
    }

    public function getInfo($url)
    {
        return $this->exec("export LANG=C; svn info --non-interactive --no-auth-cache $url");
    }
}
