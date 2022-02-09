<?php

class PdfValid
{
    public function check(string $filepath) : bool
    {
        exec("pdfinfo $filepath", $output, $return_var);
        if ($return_var != 0) {
            mail(
                EMAIL_ADMIN_TECHNIQUE,
                "erreur fichier pdf",
                "Fichier pdf corrompu : ".basename($filepath)
            );
            throw new UnexpectedValueException("Fichier pdf corrompu dans l'archive : ".basename($filepath));
        }
        return true;
    }

}