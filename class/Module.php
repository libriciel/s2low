<?php

namespace S2lowLegacy\Class;

class Module
{
    public const ACTES = 1;
    public const HELIOS = 2;
    public const MAIL = 3;

    public array $statusTypes = [
        0 => "Désactivé",
        1 => "Activé"
    ];

    public function __construct(
        public $id = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $menu_entry = null,
        public ?int $status = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function get($name)
    {
        return $this->$name ?? null;
    }

    public function set($name, $val): void
    {
        $this->$name = $val;
    }

    public function isActive(): bool
    {
        return $this->status === 1;
    }

    public function isNew(): bool
    {
        return $this->id === null;
    }

    public function getValidationTrio(): string
    {
        $args = func_get_args();
        $dbFields = [
            "name" => ["descr" => "Nom du module", "type" => "isString", "mandatory" => true],
            "description" => ["descr" => "Description", "type" => "isString", "mandatory" => true],
            "menu_entry" => ["descr" => "Entrée du menu", "type" => "isString", "mandatory" => true],
            "status" => ["descr" => "État", "type" => "isInt", "mandatory" => true]
        ];
        $ret = [];
        foreach ($args as $arg) {
            if (isset($dbFields[$arg])) {
                $str = "'" . addslashes($arg) . "', '" . addslashes($dbFields[$arg]["descr"]) . "', '";
                if ($dbFields[$arg]["mandatory"]) {
                    $str .= "R";
                }
                $str .= addslashes($dbFields[$arg]["type"]);
                $str .= "'";
                $ret[] = $str;
            }
        }
        return implode(',', $ret);
    }
}
