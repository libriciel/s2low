<?php

namespace S2lowLegacy\Class;

class DatePicker
{
    private string $name;
    private array $datePickerOptions = ["altFormat" => 'yy-mm-dd',"dateFormat" => 'dd MM yy'];
    private string $inputDefaultValue = "";


    public function __construct(string $name, string $defaultDate = null)
    {
        $this->name = $name;
        $this->datePickerOptions["altField"] =  "#$name";
        if ($defaultDate) {
            $this->datePickerOptions["defaultDate"] = "new Date($defaultDate)";
            $this->datePickerOptions["gotoCurrent"] = "true";
        } else {
            $this->inputDefaultValue = "value = \"Choisir une date\"";
        }
    }

    public function show(): string
    {
        $options = json_encode($this->datePickerOptions);
        $datePickerName = "datepicker_$this->name";
        $html = "<script>$(function() {\$(\"#$datePickerName\").datepicker($options);});</script>";
        $html .= "<input type=\"text\" class=\"form-control\" $this->inputDefaultValue name=\"$datePickerName\" id=\"$datePickerName\">";
        $html .= "<input type=\"hidden\" id=\"$this->name\" name=\"$this->name\" />\n";
        return $html;
    }
}
