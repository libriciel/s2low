<?php

class ParsedownExtended extends Parsedown
{
    /** @var int  */
    private $minimumLevel;

    public function __construct(int $minimumLevel){
        $this->minimumLevel = $minimumLevel;
    }

    #
    # Header

    protected function blockHeader($Line)
    {
        if (isset($Line['text'][1]))
        {
            $level=1;

            while (isset($Line['text'][$level]) and $Line['text'][$level] === '#')
            {
                $level ++;
            }

            if ($this->getHeaderLevel($level) > 6)
            {
                return;
            }

            $text = trim($Line['text'], '# ');

            $Block = array(
                'element' => array(
                    'name' => 'h' . min(6, $this->getHeaderLevel($level)),
                    'text' => $text,
                    'handler' => 'line',
                ),
            );

            return $Block;
        }
    }

    private function getHeaderLevel(int $level){
        return $level + $this->minimumLevel -1;
    }
}