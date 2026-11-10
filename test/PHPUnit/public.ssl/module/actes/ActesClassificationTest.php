<?php

class ActesClassificationTest extends S2lowTestCase
{
    public function testGetLastRevisionDateUsesTheLastFetchedClassification()
    {
        $this->insertClassificationRequest('2017-08-28');
        $this->insertClassificationRequest('2004-04-01');

        $this->assertSame('2004-04-01', ActesClassification::getLastRevisionDate(1));
    }

    public function testGetLastRevisionDateIgnoresRequestsWithoutClassification()
    {
        $this->insertClassificationRequest('2017-08-28');
        $this->getSQLQuery()->query(
            "INSERT INTO actes_classification_requests(request_date, requested_by, version_date, xml_data)" .
            " VALUES (now(), 1, NULL, NULL)"
        );

        $this->assertSame('2017-08-28', ActesClassification::getLastRevisionDate(1));
    }

    private function insertClassificationRequest(string $version_date): void
    {
        $this->getSQLQuery()->query(
            "INSERT INTO actes_classification_requests(request_date, requested_by, version_date, xml_data)" .
            " VALUES (now(), 1, ?, '<RetourClassification/>')",
            $version_date
        );
    }
}
