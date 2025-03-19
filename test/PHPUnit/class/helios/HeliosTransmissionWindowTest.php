<?php

namespace PHPUnit\class\helios;

use Exception;
use S2lowLegacy\Class\helios\HeliosTransmissionWindow;
use S2lowTestCase;

class HeliosTransmissionWindowTest extends S2lowTestCase
{
    public function testEmpty(): void
    {
        $window = new HeliosTransmissionWindow();
        self::assertEmpty($window->getWindowsList());
    }

    /**
     * @throws Exception
     */
    public function testSaveOneWindow(): void
    {
        $window_start_stamp = HeliosTransmissionWindow::roundDate('2025-01-01', '00:00:00');
        $window_end_stamp = HeliosTransmissionWindow::roundDate('2025-01-02', '23:59:59');

        $window = new HeliosTransmissionWindow();
        $window->set('window_start_stamp', $window_start_stamp);
        $window->set('window_end_stamp', $window_end_stamp);
        $window->set('rate_limit', 29620);
        $window->save();

        $windowList = $window->getWindowsList();
        self::assertCount(1, $windowList);

        self::assertEquals('2025-01-01 00:00:00+01', $windowList[0]['start']);
        self::assertEquals('2025-01-02 23:59:59+01', $windowList[0]['end']);
        self::assertEquals(29620, $windowList[0]['rate_limit']);
    }

    /**
     * @throws Exception
     */
    public function testCollisionNoWindow(): void
    {
        $window = new HeliosTransmissionWindow();
        // Normalement ça devrait être false ...
        self::assertSame([], $window->hasCollision());
    }

    /**
     * @throws Exception
     */
    public function testCollisionOneWindow(): void
    {
        $window_start_stamp = HeliosTransmissionWindow::roundDate('2025-01-01', '00:00:00');
        $window_end_stamp = HeliosTransmissionWindow::roundDate('2025-01-02', '23:59:59');

        $window = new HeliosTransmissionWindow();
        $window->set('window_start_stamp', $window_start_stamp);
        $window->set('window_end_stamp', $window_end_stamp);
        $window->set('rate_limit', 29620);

        // Pas de collision avec une BDD Vide ...
        self::assertFalse($window->hasCollision());
        $window->save();
        // Ni avec une BDD ou la fenêtre elle-même est présente.
        self::assertFalse($window->hasCollision());
    }

    /**
     * @throws Exception
     * @dataProvider hourProvider
     */
    public function testCollisionTwoWindows(string $hour_end1, string $hour_begin2, bool $hasCollision): void
    {
        $window1_start_stamp = HeliosTransmissionWindow::roundDate('2025-01-01', '00:00:00');
        $window1_end_stamp = HeliosTransmissionWindow::roundDate('2025-01-02', $hour_end1);

        $window2_start_stamp = HeliosTransmissionWindow::roundDate('2025-01-02', $hour_begin2);
        $window2_end_stamp = HeliosTransmissionWindow::roundDate('2025-01-03', '23:59:59');

        $window1 = new HeliosTransmissionWindow();
        $window1->set('window_start_stamp', $window1_start_stamp);
        $window1->set('window_end_stamp', $window1_end_stamp);
        $window1->set('rate_limit', 29620);
        $window1->save();

        $window2 = new HeliosTransmissionWindow();
        $window2->set('window_start_stamp', $window2_start_stamp);
        $window2->set('window_end_stamp', $window2_end_stamp);
        $window2->set('rate_limit', 29620);
        $before = $window2->hasCollision();
        $window2->save();
        // L'éventuelle collision est détectée que la fenêtre soit sauvée ou non
        self::assertSame($before, $window2->hasCollision());
        if (!$hasCollision) {
            self::assertFalse($window2->hasCollision());
            return;
        }
        self::assertEquals(1, count($window2->hasCollision()));
    }

    public function hourProvider(): iterable
    {
        return [
            ['12:00:00','13:00:00',false],
            ['13:00:00','12:00:00',true]
        ];
    }
}
