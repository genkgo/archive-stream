<?php

declare(strict_types=1);

namespace Genkgo\TestArchiveStream\Integration;

use Genkgo\ArchiveStream\Archive;
use Genkgo\ArchiveStream\Psr7Stream;
use Genkgo\ArchiveStream\StringContent;
use Genkgo\ArchiveStream\ZipReader;
use Genkgo\TestArchiveStream\AbstractTestCase;

final class Psr7Test extends AbstractTestCase
{
    public function testStreamGetContents()
    {
        $archive = (new Archive())->withContent(new StringContent('test.txt', 'content'));

        $filename = \tempnam(\sys_get_temp_dir(), 'zip');
        $psr7Stream = new Psr7Stream(new ZipReader($archive));
        \file_put_contents($filename, $psr7Stream->getContents());

        $zip = new \ZipArchive();
        $result = $zip->open($filename);

        $this->assertTrue($result);
        $this->assertEquals(1, $zip->numFiles);
        $this->assertEquals('test.txt', $zip->getNameIndex(0));
        $this->assertEquals('content', $zip->getFromIndex(0));
    }

    public function testStreamRead()
    {
        $archive = new Archive();
        $archive = (new Archive())->withContent(new StringContent('test.txt', 'content'));

        $filename = \tempnam(\sys_get_temp_dir(), 'zip');
        $fileStream = \fopen($filename, 'r+');
        $psr7Stream = new Psr7Stream(new ZipReader($archive));

        while (!$psr7Stream->eof()) {
            \fwrite($fileStream, $psr7Stream->read(1));
        }
        \fclose($fileStream);

        $zip = new \ZipArchive();
        $result = $zip->open($filename);

        $this->assertTrue($result);
        $this->assertEquals(1, $zip->numFiles);
        $this->assertEquals('test.txt', $zip->getNameIndex(0));
        $this->assertEquals('content', $zip->getFromIndex(0));
    }
}
