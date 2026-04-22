<?php

namespace S2lowLegacy\Lib;

use S2lowLegacy\Class\Helpers;

class Recuperateur
{
    private array $tableauInput;
    /**
     * @var string[]
     */
    private array $thingsToDo;

    public function __construct(array $tableauInput, bool $forceConversionFromIso = false)
    {
        $this->tableauInput = $tableauInput;
        $this->thingsToDo = ["trim"];
        if ($this->get("api") || $forceConversionFromIso) {
            $this->thingsToDo = ["utf8_encode","trim"];
        }
    }

    public function getInt($name, $default = 0)
    {
        return $this->doSomethingOnValueOrArray('intval', $this->get($name, $default));
    }

    public function getDate(string $name): ?string
    {
        return \S2lowLegacy\Class\Helpers\RequestHelper::checkDate(
            $this->get($name, null),
            true,
            $name
        );
    }

    public function get($name, $default = false)
    {
        if (empty($this->tableauInput[$name])) {
            return $default;
        }
        $value = $this->tableauInput[$name];

        return $this->doThingsOnValueOrArray($this->thingsToDo, $value);
    }

    public function set($key, $value): void
    {
        $this->tableauInput[$key] = $value;
    }

    private function doThingsOnValueOrArray(array $things, $valueOrArray)
    {
        foreach ($things as $something) {
            $valueOrArray = $this->doSomethingOnValueOrArray($something, $valueOrArray);
        }
        return $valueOrArray;
    }

    private function doSomethingOnValueOrArray($something, $valueOrArray)
    {
        if (is_array($valueOrArray)) {
            return array_map($something, $valueOrArray);
        }
        return $something($valueOrArray);
    }
}
