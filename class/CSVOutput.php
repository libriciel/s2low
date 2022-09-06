<?php

namespace S2lowLegacy\Class;

class CSVOutput
{
    private const DEFAULT_OUTPUT_FILE = "php://output";

    private $enableHeader;
    private $outputFile;

    private $outputStream;


    public function __construct()
    {
        $this->enableHeader = true;
        $this->setOutputFile(self::DEFAULT_OUTPUT_FILE);
    }

    public function disableHeader()
    {
        $this->enableHeader = false;
    }

    public function setOutputFile($outputFile)
    {
        $this->outputFile = $outputFile;
    }

    public function sendAttachment($file_name, array $info)
    {
        $this->displayHTTPHeader($file_name);
        $this->display($info);
    }

    public function display(array $info)
    {
        $this->begin();
        foreach ($info as $line) {
            $this->displayLine($line);
        }
        $this->end();
    }

    public function send($filename, array $info)
    {
        $this->displayHTTPHeader($filename);
        $this->display($info);
    }


    public function begin()
    {
        $this->outputStream = fopen($this->outputFile, 'w');
    }

    public function displayLine($line)
    {
        fputcsv($this->outputStream, $line);
    }

    public function end()
    {
        fclose($this->outputStream);
    }

    public function displayHTTPHeader($file_name)
    {
        if (! $this->enableHeader) {
            return;
        }
        header_wrapper("Content-type: text/csv; charset=iso-8859-1");
        header_wrapper("Content-disposition: attachment; filename=$file_name");
        header_wrapper("Expires: 0");
        header_wrapper("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header_wrapper("Pragma: public");
    }
}
