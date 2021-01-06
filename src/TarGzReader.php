<?php
declare(strict_types=1);

namespace Genkgo\ArchiveStream;

final class TarGzReader implements ArchiveReader
{
    /**
     * @var TarReader
     */
    private $tarReader;

    /**
     * @var int
     */
    private $level;

    /**
     * @param TarReader $tarReader
     * @param int $level
     */
    public function __construct(TarReader $tarReader, int $level = -1)
    {
        if ($level > 9 || $level < -1) {
            throw new \InvalidArgumentException('Level must be between -1 and 9');
        }

        $this->tarReader = $tarReader;
        $this->level = $level;
    }

    /**
     * @param int $blockSize
     * @return \Generator<int, \SplTempFileObject>
     */
    public function read(int $blockSize): \Generator
    {
        $generator = $this->tarReader->read($blockSize);
        foreach ($generator as $stream) {
            while ($stream->eof() === false) {
                $data = $stream->fread($blockSize);
                if ($data === false) {
                    throw new \UnexpectedValueException('Failed to read tar stream');
                }

                $encoded = \gzencode($data, $this->level);
                if (!$encoded) {
                    throw new \UnexpectedValueException('Failed to encode tar data');
                }

                $stream = new \SplTempFileObject();
                $stream->fwrite($encoded);
                $stream->rewind();
                yield $stream;
            }
        }
    }
}
