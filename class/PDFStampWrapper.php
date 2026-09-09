<?php

namespace S2lowLegacy\Class;

use CURLStringFile;
use Exception;
use S2low\Enum\PdfStampPositionOrigin;

class PDFStampWrapper
{
    private const STAMP_ADD_PATH = '/pdf-stamp/v3/stamp/add';
    private const LOGO_IMAGE_REF = 's2low-stamp.png';

    private CurlWrapperFactory $curlWrapperFactory;

    public function __construct(
        private string $pdfStampUrl,
        private string $logoFilePath,
        private PdfStampMessages $pdfStampMessages
    ) {
        $this->curlWrapperFactory = new CurlWrapperFactory();
    }

    public function setCurlWrapperFactory(CurlWrapperFactory $curlWrapperFactory): void
    {
        $this->curlWrapperFactory = $curlWrapperFactory;
    }

    public function stamp(string $pdfFilePath, PDFStampData $pdfStampData): string
    {
        $curlWrapper = $this->curlWrapperFactory->getNewInstance();
        $curlWrapper->addPostFile('pdfSource', $pdfFilePath);
        $curlWrapper->addPostFile('images', $this->logoFilePath, self::LOGO_IMAGE_REF);
        $curlWrapper->addPostData('stampRequest', $this->buildStampRequestPart($pdfStampData));

        $result = $curlWrapper->get($this->getStampAddUrl());
        if (!$result) {
            throw new Exception($curlWrapper->getLastError() . " " . $curlWrapper->getLastOutput());
        }
        return $result;
    }

    private function buildStampRequestPart(PDFStampData $pdfStampData): CURLStringFile
    {
        // pdf-stamp répond 415 si la partie n'est pas déclarée en application/json
        return new CURLStringFile(
            json_encode($this->buildStampRequest($pdfStampData)),
            'stampRequest.json',
            'application/json'
        );
    }

    private function buildStampRequest(PDFStampData $pdfStampData): array
    {
        return [
            'stampList' => [
                [
                    'page' => 1,
                    'opacity' => 0.8,
                    'fontSize' => 7,
                    'position' => [
                        'width' => 190,
                        'height' => 55,
                        'x' => 10,
                        'y' => 10,
                        'origin' => PdfStampPositionOrigin::HautDroite->value,
                    ],
                    'rows' => $this->buildRows($pdfStampData),
                ],
            ],
        ];
    }

    private function buildRows(PDFStampData $pdfStampData): array
    {
        return [
            [
                'title' => $this->pdfStampMessages->getMessageEnvoi(),
                'value' => $this->formatDateFr($pdfStampData->envoi_prefecture_date),
            ],
            [
                'title' => $this->pdfStampMessages->getMessageReception(),
                'value' => $this->formatDateFr($pdfStampData->recu_prefecture_date),
            ],
            [
                'title' => $this->pdfStampMessages->getMessagePublication(),
                'value' => $this->formatDateAffichage($pdfStampData),
                'logo' => [
                    'imageRef' => self::LOGO_IMAGE_REF,
                    'width' => 60,
                    'marginRight' => 20,
                ],
            ],
            [
                'title' => 'ID :',
                'value' => $pdfStampData->identifiant_unique,
            ],
        ];
    }

    private function formatDateAffichage(PDFStampData $pdfStampData): string
    {
        if (!$pdfStampData->affichage_date) {
            return "";
        }
        return $this->formatDateFr($pdfStampData->affichage_date);
    }

    private function getStampAddUrl(): string
    {
        return rtrim($this->pdfStampUrl, '/') . self::STAMP_ADD_PATH;
    }

    private function formatDateFr(?string $date): string
    {
        if (is_null($date)) {
            return date('d/m/Y');
        }
        return date('d/m/Y', strtotime($date));
    }
}
