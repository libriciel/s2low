<?php

/**
 * \class MailLayout.class.php
 * \brief layout pour module mail.
 *          appelé par index.
 * \author TH ,JMontiel
 * \date :23-04-2008
 *
 *
 * cette class permet de définir la style de mail systeme
 * hérité par HTMLayout.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

namespace S2lowLegacy\Mail;

use S2lowLegacy\Class\HTMLLayout;

class MailLayout extends HTMLLayout
{
    public function DisplayHead()
    {
        $this->includeErrors();
        if ($this->template) {
            require_once(HTML_TEMPLATE_PATH . '/' . $this->template);
        } else {
            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
            echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\">\n";
            echo "<head>\n";
            echo "<title>" . $this->title . "</title>\n";
            echo $this->header . "\n";
            echo "</head>\n";
            echo "<body>\n";
            echo $this->body . "\n";
        }
    }
    public function closeDiv()
    {
        echo "</div>\n";
    }

    public function DisplayFoot()
    {
        $this->buildFooter(true);
        echo "</body>\n";
    }
}
