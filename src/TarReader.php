<?php
declare(strict_types=1);

namespace Genkgo\ArchiveStream;

use Genkgo\ArchiveStream\Exception\ContentWithoutDataException;
use Genkgo\ArchiveStream\Util\PackHelper;

final class TarReader implements ArchiveReader
{
    /**
     * @var Archive
     */
    private $archive;

    /**
     * @param Archive $archive
     */
    public function __construct(Archive $archive)
    {
        $this->archive = $archive;
    }

    /**
     * @param int $blockSize
     * @return \Generator<int, \SplTempFileObject>
     */
    public function read(int $blockSize): \Generator
    {
        foreach ($this->archive->getContents() as $content) {
            if ($content->getType() === ContentInterface::DIRECTORY) {
                $type = '5';
                $size = 0;
            } else {
                $stat = \fstat($content->getData());
                if ($stat === false) {
                    throw new \UnexpectedValueException(
                        'Failed to add ' . $content->getName() . ' to tar. The size of the file must be known while adding to archive.'
                    );
                }

                $type = '0';
                $size = $stat['size'];
            }

            $prefix = '';
            $name = $content->getName();
            $nameLength = \strlen($name);

            if ($nameLength > 100) {
                $dirname = \dirname($content->getName());
                $basename = \basename($content->getName());

                // Remove '.' from the current directory
                $dirname = $dirname === '.' ? '' : $dirname;

                // Remove trailing slash from directory name, because tar implies it.
                if (\substr($dirname, -1) === '/') {
                    $dirname = \substr($dirname, 0, -1);
                }

                if (\strlen($basename) > 100 || \strlen($dirname) > 155) {
                    yield $this->streamResourceHeader('././@LongLink', '', 'L', \strlen($name), 0);

                    for ($s = 0; $s < $nameLength; $s += 512) {
                        yield $this->streamResourceData(\substr($name, $s, 512));
                    }

                    $name = \substr($name, 0, 100);
                } else {
                    $name = $basename;
                    $prefix = $dirname;
                }
            }

            yield $this->streamResourceHeader(
                $name,
                $prefix,
                $type,
                $size,
                (int)$content->getModifiedAt()->format('U')
            );

            try {
                $resource = $content->getData();
                while ($data = \fread($resource, 512)) {
                    yield $this->streamResourceData($data);
                }

                \fclose($resource);
            } catch (ContentWithoutDataException $e) {
            }
        }

        yield $this->streamResourceData('');
        yield $this->streamResourceData('');
    }

    /**
     * Initialize a file stream.
     *
     * @param string $name
     * @param string $prefix
     * @param string $type
     * @param int $size
     * @param int $time
     * @return \SplTempFileObject
     */
    private function streamResourceHeader(string $name, string $prefix, string $type, int $size, int $time): \SplTempFileObject
    {
        $fieldsFirst = [
            ['a100', \substr($name, 0, 100)],
            ['a8',   \sprintf("%6s ", \decoct(33279))], //mode 0777
            ['a8',   \sprintf("%6s ", \decoct(0))], //uid
            ['a8',   \sprintf("%6s ", \decoct(0))], //gid
            ['a12',  \sprintf("%11s ", \decoct($size))],
            ['A12',  \sprintf("%11s ", \decoct($time))],
        ];

        $fieldsLast = [
            ['a1',   $type],
            ['a100', ''],
            ['a6',   'ustar'],
            ['a2',   ''],
            ['a32',  ''],
            ['a32',  ''],
            ['a8',   ''],
            ['a8',   ''],
            ['a155', \substr($prefix, 0, 155)],
            ['a12',  ''],
        ];

        // pack fields and calculate "total" length
        $headerFirst = PackHelper::packFields($fieldsFirst);
        $headerLast = PackHelper::packFields($fieldsLast);

        // Compute header checksum
        for ($i = 0, $checksum = 0; $i < 148; $i++) {
            $checksum += \ord($headerFirst[$i]);
        }

        for ($i = 156, $checksum += 256, $j = 0; $i < 512; $i++, $j++) {
            $checksum += \ord($headerLast[$j]);
        }

        $stream = new \SplTempFileObject();
        $stream->fwrite($headerFirst);
        $stream->fwrite(\pack('a8', \sprintf("%6s ", \decoct($checksum))));
        $stream->fwrite($headerLast);
        $stream->rewind();
        return $stream;
    }

    /**
     * @param string $data
     * @return \SplTempFileObject
     */
    private function streamResourceData(string $data): \SplTempFileObject
    {
        $stream = new \SplTempFileObject();
        $stream->fwrite(\pack("a512", $data));
        $stream->rewind();
        return $stream;
    }
}
