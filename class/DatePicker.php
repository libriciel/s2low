<?php

namespace S2lowLegacy\Class;

class DatePicker
{
    private string $name;
    private array $datePickerOptions = ["altFormat" => 'yy-mm-dd',"dateFormat" => 'dd MM yy'];
    private string $inputDefaultValue = "";

    private string $hidenInputDefaultValue = "";


    public function __construct(string $name, ?string $ansiDate = "", string $class = "form-control")
    {
        $dateInLetters = "";
        if ($ansiDate) {
            $dateInLetters = strftime("%d %B %Y", Helpers :: ansiDateToTimestamp($ansiDate));
        }

        $this->class = $class;

        $this->name = $name;
        $this->datePickerOptions["altField"] =  "#$name";
        if ($ansiDate) {
            $this->inputDefaultValue = "value = \"$dateInLetters\"";
            $this->hidenInputDefaultValue = "value = \"$ansiDate\"";
            $this->datePickerOptions["defaultDate"] = "new Date($ansiDate)";
            $this->datePickerOptions["gotoCurrent"] = true;
        } else {
            $this->inputDefaultValue = "value = \"Choisir une date\"";
        }
    }

    public function show(): string
    {
        $options = json_encode($this->datePickerOptions);
        $datePickerName = "datepicker_$this->name";
        $html = "<script>$(function() {\$(\"#$datePickerName\").datepicker($options);});</script>";
        $html .= "<input type=\"text\" class=\"$this->class\" $this->inputDefaultValue name=\"$datePickerName\" id=\"$datePickerName\">";
        $html .= "<input type=\"hidden\" id=\"$this->name\" name=\"$this->name\" $this->hidenInputDefaultValue />\n";
        return $html;
    }
}
