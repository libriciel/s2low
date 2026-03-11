<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Mail\MailLayout;

class HTMLLayoutFactory
{
    public function __construct(
        private readonly MenuHTML $menuHTML,
    ) {
    }
    public function createLayout($template = false): HTMLLayout
    {
        return new HTMLLayout($this->menuHTML, $template);
    }

    public function createMailLayout($template = false): MailLayout
    {
        return new MailLayout($this->menuHTML, $template);
    }
}
