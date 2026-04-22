<?php

namespace S2lowLegacy\Class;

use IntlDateFormatter;
use JsonException;

class DatePicker
{
    public const FORMAT = 'dd MMMM yyyy';
    private array $datePickerOptions = ['altFormat' => 'yy-mm-dd', 'dateFormat' => 'dd MM yy'];
    private string $inputDefaultValue;

    private string $hidenInputDefaultValue = '';


    public function __construct(
        private readonly string $name,
        ?string $ansiDate = '',
        private readonly string $class = 'form-control',
    ) {
        $dateInLetters = '';
        if ($ansiDate !== null && $ansiDate !== '') {
            $dateInLetters = $this->getFormattedDate($ansiDate, self::FORMAT);
        }

        $this->datePickerOptions['altField'] =  "#$name";
        if ($ansiDate) {
            $this->inputDefaultValue = "value = \"$dateInLetters\"";
            $this->hidenInputDefaultValue = "value = \"$ansiDate\"";
            $this->datePickerOptions['defaultDate'] = "new Date($ansiDate)";
            $this->datePickerOptions['gotoCurrent'] = true;
        } else {
            $this->inputDefaultValue = "value = \"Choisir une date\"";
        }
    }

    /**
     * @throws JsonException
     */
    public function show(): string
    {
        $options = json_encode($this->datePickerOptions, JSON_THROW_ON_ERROR);
        $datePickerName = "datepicker_$this->name";
        $html = "<script>$(function() {\$(\"#$datePickerName\").datepicker($options);});</script>";
        $html .= "<input type=\"text\" class=\"$this->class\" $this->inputDefaultValue name=\"$datePickerName\" id=\"$datePickerName\">";
        $html .= "<input type=\"hidden\" id=\"$this->name\" name=\"$this->name\" $this->hidenInputDefaultValue />\n";
        return $html;
    }

    public function getFormattedDate(string $ansiDate, string $format): string
    {
        $formatter = new IntlDateFormatter(
            setlocale(LC_TIME, '0'),
        );
        $formatter->setPattern($format);

        return $formatter->format(\S2lowLegacy\Class\Helpers\DateHelper::ansiDateToTimestamp($ansiDate));
    }
}
