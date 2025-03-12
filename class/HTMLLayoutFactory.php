<?php

namespace S2lowLegacy\Class;

use Legacy\MailLayout;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class HTMLLayoutFactory
{
    /**
     * @var \Twig\Environment
     */
    private Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../templates');
        $this->twig = new Environment($loader);
    }

    public function createHTMLLayout($template = false): HTMLLayout
    {
        return new HTMLLayout($this->twig, $template);
    }

    public function createMailLayout($template = false): MailLayout
    {
        return new MailLayout($this->twig, $template);
    }
}
