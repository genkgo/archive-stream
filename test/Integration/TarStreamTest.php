<?php

declare(strict_types=1);

namespace Genkgo\TestArchiveStream\Integration;

use Genkgo\ArchiveStream\Archive;
use Genkgo\ArchiveStream\EmptyDirectory;
use Genkgo\ArchiveStream\ResourceContent;
use Genkgo\ArchiveStream\StringContent;
use Genkgo\ArchiveStream\TarReader;
use Genkgo\TestArchiveStream\AbstractTestCase;

final class TarStreamTest extends AbstractTestCase
{
    public function testCreateFileTar()
    {
        $archive = (new Archive())->withContent(new StringContent('test.txt', 'content'));

        $filename = \tempnam(\sys_get_temp_dir(), 'tar');
        $fileStream = \fopen($filename, 'r+');

        $archiveStream = new TarReader($archive);
        $generator = $archiveStream->read(1048576);
        foreach ($generator as $stream) {
            while ($stream->eof() === false) {
                $data = $stream->fread(9999);
                \fwrite($fileStream, $data);
            }
        }
        \fclose($fileStream);

        $extractDir = \sys_get_temp_dir() . '/extract' . \time() . '-' . \bin2hex(\random_bytes(4));
        \mkdir($extractDir);
        \exec('tar -xvf ' . $filename . ' -C ' . $extractDir);

        $this->assertTrue(\file_exists($extractDir));
        $this->assertEquals('content', \file_get_contents($extractDir . '/test.txt'));
    }

    public function testCreateEmptyDirectory()
    {
        $archive = (new Archive())->withContent(new EmptyDirectory('empty'));

        $filename = \tempnam(\sys_get_temp_dir(), 'tar');
        $fileStream = \fopen($filename, 'r+');

        $archiveStream = new TarReader($archive);
        $generator = $archiveStream->read(1048576);
        foreach ($generator as $stream) {
            while ($stream->eof() === false) {
                $data = $stream->fread(9999);
                \fwrite($fileStream, $data);
            }
        }
        \fclose($fileStream);

        $extractDir = \sys_get_temp_dir() . '/extract' . \time() . '-' . \bin2hex(\random_bytes(4));
        \mkdir($extractDir);
        \exec('tar -xvf ' . $filename . ' -C ' . $extractDir);

        $this->assertTrue(\file_exists($extractDir));
        $this->assertTrue(\is_dir($extractDir . '/empty'));
    }

    public function testCreateFileEmptyDirectory()
    {
        $archive = (new Archive())
            ->withContent(new EmptyDirectory('directory'))
            ->withContent(new StringContent('other/file.txt', 'data'));

        $filename = \tempnam(\sys_get_temp_dir(), 'tar');
        $fileStream = \fopen($filename, 'r+');

        $archiveStream = new TarReader($archive);
        $generator = $archiveStream->read(1048576);
        foreach ($generator as $stream) {
            while ($stream->eof() === false) {
                $data = $stream->fread(9999);
                \fwrite($fileStream, $data);
            }
        }
        \fclose($fileStream);

        $extractDir = \sys_get_temp_dir() . '/extract' . \time() . '-' . \bin2hex(\random_bytes(4));
        \mkdir($extractDir);
        \exec('tar -xvf ' . $filename . ' -C ' . $extractDir);

        $this->assertTrue(\file_exists($extractDir));
        $this->assertTrue(\is_dir($extractDir . '/directory'));
        $this->assertTrue(\is_file($extractDir . '/other/file.txt'));
        $this->assertEquals('data', \file_get_contents($extractDir . '/other/file.txt'));
    }

    public function testOneMbTar()
    {
        $fh = \fopen('php://memory', 'w');
        $size = 1024 * 1024 * 2;
        $chunk = 1024;
        while ($size > 0) {
            \fputs($fh, \str_pad('', \min($chunk, $size)));
            $size -= $chunk;
        }
        \rewind($fh);
        $content = \stream_get_contents($fh);
        \rewind($fh);

        $archive = (new Archive())->withContent(new ResourceContent('test.txt', $fh));

        $filename = \tempnam(\sys_get_temp_dir(), 'tar');
        $fileStream = \fopen($filename, 'r+');

        $archiveStream = new TarReader($archive);
        $generator = $archiveStream->read(1048576);
        foreach ($generator as $stream) {
            while ($stream->eof() === false) {
                $data = $stream->fread(9999);
                \fwrite($fileStream, $data);
            }
        }
        \fclose($fileStream);

        $extractDir = \sys_get_temp_dir() . '/extract' . \time() . '-' . \bin2hex(\random_bytes(4));
        \mkdir($extractDir);
        \exec('tar -xvf ' . $filename . ' -C ' . $extractDir);

        $this->assertTrue(\file_exists($extractDir));
        $this->assertEquals($content, \file_get_contents($extractDir . '/test.txt'));
    }
}
