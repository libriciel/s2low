<?php

namespace S2low\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * ActesTransactions
 *
 * @ORM\Table(name="actes_transactions", uniqueConstraints={@ORM\UniqueConstraint(name="actes_transactions_unique_id", columns={"unique_id", "type", "id"}), @ORM\UniqueConstraint(name="actes_transactions_last_status_id_id", columns={"last_status_id", "id"})}, indexes={@ORM\Index(name="at_user_id_index", columns={"user_id", "last_status_id", "id"}), @ORM\Index(name="at_related_id", columns={"related_transaction_id"}), @ORM\Index(name="at_n", columns={"number"}), @ORM\Index(name="at_lsi_ac", columns={"last_status_id", "antivirus_check"}), @ORM\Index(name="at_enveloppe_id", columns={"envelope_id"}), @ORM\Index(name="actes_transactions_user_id_last_status_id_idx", columns={"user_id", "last_status_id"}), @ORM\Index(name="actes_transactions_auto_broadcasted_last_status_id_type_idx", columns={"auto_broadcasted", "last_status_id", "type"}), @ORM\Index(name="actes_transactions_authority_id_last_status_id_idx", columns={"authority_id", "last_status_id"})})
 * @ORM\Entity
 */
class ActesTransactions
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="actes_transactions_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="type", type="string", length=5, nullable=true)
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nature_code", type="string", length=10, nullable=true)
     */
    private $natureCode;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nature_descr", type="string", length=128, nullable=true)
     */
    private $natureDescr;

    /**
     * @var string|null
     *
     * @ORM\Column(name="title", type="string", length=128, nullable=true)
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="subject", type="string", length=512, nullable=true)
     */
    private $subject;

    /**
     * @var string|null
     *
     * @ORM\Column(name="number", type="string", length=20, nullable=true)
     */
    private $number;

    /**
     * @var string|null
     *
     * @ORM\Column(name="classification", type="string", length=128, nullable=true)
     */
    private $classification;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="classification_date", type="datetimetz", nullable=true)
     */
    private $classificationDate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="decision_date", type="datetimetz", nullable=true)
     */
    private $decisionDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="unique_id", type="string", length=128, nullable=true)
     */
    private $uniqueId;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="auto_broadcasted", type="boolean", nullable=true)
     */
    private $autoBroadcasted = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="archive_url", type="string", length=1024, nullable=true)
     */
    private $archiveUrl;

    /**
     * @var string|null
     *
     * @ORM\Column(name="broadcast_emails", type="text", nullable=true)
     */
    private $broadcastEmails;

    /**
     * @var int|null
     *
     * @ORM\Column(name="broadcast_send_sources", type="integer", nullable=true)
     */
    private $broadcastSendSources;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="broadcasted", type="boolean", nullable=true)
     */
    private $broadcasted = false;

    /**
     * @var int|null
     *
     * @ORM\Column(name="type_reponse", type="integer", nullable=true)
     */
    private $typeReponse;

    /**
     * @var int|null
     *
     * @ORM\Column(name="last_status_id", type="integer", nullable=true)
     */
    private $lastStatusId;

    /**
     * @var int|null
     *
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $userId;

    /**
     * @var int|null
     *
     * @ORM\Column(name="authority_id", type="integer", nullable=true)
     */
    private $authorityId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="sae_transfer_identifier", type="string", length=256, nullable=true)
     */
    private $saeTransferIdentifier;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="antivirus_check", type="boolean", nullable=true)
     */
    private $antivirusCheck = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="classification_string", type="string", length=256, nullable=true)
     */
    private $classificationString;

    /**
     * @var bool
     *
     * @ORM\Column(name="document_papier", type="boolean", nullable=false)
     */
    private $documentPapier = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="lu", type="boolean", nullable=false)
     */
    private $lu = false;

    /**
     * @var ActesEnvelopes
     *
     * @ORM\ManyToOne(targetEntity="ActesEnvelopes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="envelope_id", referencedColumnName="id")
     * })
     */
    private $envelope;

    /**
     * @var ActesTransactions
     *
     * @ORM\ManyToOne(targetEntity="ActesTransactions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="related_transaction_id", referencedColumnName="id")
     * })
     */
    private $relatedTransaction;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getNatureCode(): ?string
    {
        return $this->natureCode;
    }

    public function setNatureCode(?string $natureCode): static
    {
        $this->natureCode = $natureCode;

        return $this;
    }

    public function getNatureDescr(): ?string
    {
        return $this->natureDescr;
    }

    public function setNatureDescr(?string $natureDescr): static
    {
        $this->natureDescr = $natureDescr;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getClassification(): ?string
    {
        return $this->classification;
    }

    public function setClassification(?string $classification): static
    {
        $this->classification = $classification;

        return $this;
    }

    public function getClassificationDate(): ?\DateTimeInterface
    {
        return $this->classificationDate;
    }

    public function setClassificationDate(?\DateTimeInterface $classificationDate): static
    {
        $this->classificationDate = $classificationDate;

        return $this;
    }

    public function getDecisionDate(): ?\DateTimeInterface
    {
        return $this->decisionDate;
    }

    public function setDecisionDate(?\DateTimeInterface $decisionDate): static
    {
        $this->decisionDate = $decisionDate;

        return $this;
    }

    public function getUniqueId(): ?string
    {
        return $this->uniqueId;
    }

    public function setUniqueId(?string $uniqueId): static
    {
        $this->uniqueId = $uniqueId;

        return $this;
    }

    public function isAutoBroadcasted(): ?bool
    {
        return $this->autoBroadcasted;
    }

    public function setAutoBroadcasted(?bool $autoBroadcasted): static
    {
        $this->autoBroadcasted = $autoBroadcasted;

        return $this;
    }

    public function getArchiveUrl(): ?string
    {
        return $this->archiveUrl;
    }

    public function setArchiveUrl(?string $archiveUrl): static
    {
        $this->archiveUrl = $archiveUrl;

        return $this;
    }

    public function getBroadcastEmails(): ?string
    {
        return $this->broadcastEmails;
    }

    public function setBroadcastEmails(?string $broadcastEmails): static
    {
        $this->broadcastEmails = $broadcastEmails;

        return $this;
    }

    public function getBroadcastSendSources(): ?int
    {
        return $this->broadcastSendSources;
    }

    public function setBroadcastSendSources(?int $broadcastSendSources): static
    {
        $this->broadcastSendSources = $broadcastSendSources;

        return $this;
    }

    public function isBroadcasted(): ?bool
    {
        return $this->broadcasted;
    }

    public function setBroadcasted(?bool $broadcasted): static
    {
        $this->broadcasted = $broadcasted;

        return $this;
    }

    public function getTypeReponse(): ?int
    {
        return $this->typeReponse;
    }

    public function setTypeReponse(?int $typeReponse): static
    {
        $this->typeReponse = $typeReponse;

        return $this;
    }

    public function getLastStatusId(): ?int
    {
        return $this->lastStatusId;
    }

    public function setLastStatusId(?int $lastStatusId): static
    {
        $this->lastStatusId = $lastStatusId;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getAuthorityId(): ?int
    {
        return $this->authorityId;
    }

    public function setAuthorityId(?int $authorityId): static
    {
        $this->authorityId = $authorityId;

        return $this;
    }

    public function getSaeTransferIdentifier(): ?string
    {
        return $this->saeTransferIdentifier;
    }

    public function setSaeTransferIdentifier(?string $saeTransferIdentifier): static
    {
        $this->saeTransferIdentifier = $saeTransferIdentifier;

        return $this;
    }

    public function isAntivirusCheck(): ?bool
    {
        return $this->antivirusCheck;
    }

    public function setAntivirusCheck(?bool $antivirusCheck): static
    {
        $this->antivirusCheck = $antivirusCheck;

        return $this;
    }

    public function getClassificationString(): ?string
    {
        return $this->classificationString;
    }

    public function setClassificationString(?string $classificationString): static
    {
        $this->classificationString = $classificationString;

        return $this;
    }

    public function isDocumentPapier(): ?bool
    {
        return $this->documentPapier;
    }

    public function setDocumentPapier(bool $documentPapier): static
    {
        $this->documentPapier = $documentPapier;

        return $this;
    }

    public function isLu(): ?bool
    {
        return $this->lu;
    }

    public function setLu(bool $lu): static
    {
        $this->lu = $lu;

        return $this;
    }

    public function getEnvelope(): ?ActesEnvelopes
    {
        return $this->envelope;
    }

    public function setEnvelope(?ActesEnvelopes $envelope): static
    {
        $this->envelope = $envelope;

        return $this;
    }

    public function getRelatedTransaction(): ?self
    {
        return $this->relatedTransaction;
    }

    public function setRelatedTransaction(?self $relatedTransaction): static
    {
        $this->relatedTransaction = $relatedTransaction;

        return $this;
    }


}
