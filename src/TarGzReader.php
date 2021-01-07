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
        $deflateContext = \deflate_init(\ZLIB_ENCODING_GZIP, ['level' => $this->level]);
        if ($deflateContext === false) {
            throw new \UnexpectedValueException('Cannot open deflate context');
        }

        $generator = $this->tarReader->read($blockSize);
        foreach ($generator as $stream) {
            while ($stream->eof() === false) {
                $data = $stream->fread($blockSize);
                if ($data === false) {
                    throw new \UnexpectedValueException('Failed to read tar stream');
                }

                $encoded = \deflate_add($deflateContext, $data, \ZLIB_NO_FLUSH);
                if ($encoded === false) {
                    throw new \UnexpectedValueException('Failed to encode tar data');
                }

                if ($encoded !== '') {
                    $stream = new \SplTempFileObject();
                    $stream->fwrite($encoded);
                    $stream->rewind();
                    yield $stream;
                }
            }
        }

        $encoded = \deflate_add($deflateContext, '', \ZLIB_FINISH);
        if ($encoded === false) {
            throw new \UnexpectedValueException('Failed to encode last chunk of tar data');
        }

        $stream = new \SplTempFileObject();
        $stream->fwrite($encoded);
        $stream->rewind();
        yield $stream;
    }
}
