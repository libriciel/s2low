<?php

namespace S2low\Entity;

class InformationMessage
{
    protected int $module_id;           // = $this->environnement->post()->getInt("module");
    protected int $authority_group_id;  // = $this->environnement->post()->getInt("authority_group_id");
    protected string $subject;             // = $this->environnement->post()->get("subject");
    protected string $body;                // = $this->environnement->post()->get("body");

    /**
     * @return int
     */
    public function getModuleId(): int
    {
        return $this->module_id;
    }

    /**
     * @param int $module_id
     */
    public function setModuleId(int $module_id): void
    {
        $this->module_id = $module_id;
    }

    /**
     * @return int
     */
    public function getAuthorityGroupId(): int
    {
        return $this->authority_group_id;
    }

    /**
     * @param int $authority_group_id
     */
    public function setAuthorityGroupId(int $authority_group_id): void
    {
        $this->authority_group_id = $authority_group_id;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}
