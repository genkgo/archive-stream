<?php

declare(strict_types=1);

namespace Genkgo\ArchiveStream;

final class Archive
{
    /**
     * @var array<array<ContentInterface>|\Traversable<mixed, ContentInterface>>
     */
    private $contents = [];

    /**
     * @var string
     */
    private $comment = '';

    /**
     * @param ContentInterface $content
     * @return Archive
     */
    public function withContent(ContentInterface $content): Archive
    {
        $clone = clone $this;
        $clone->contents[] = new \ArrayIterator([$content]);
        return $clone;
    }

    /**
     * @param iterable|ContentInterface[] $contents
     * @return Archive
     */
    public function withContents(iterable $contents): Archive
    {
        if (\is_array($contents)) {
            $contents = new \ArrayIterator($contents);
        }

        $clone = clone $this;
        $clone->contents[] = $contents;
        return $clone;
    }

    /**
     * @param string $comment
     * @return Archive
     */
    public function withComment(string $comment): Archive
    {
        $clone = clone $this;
        $clone->comment = $comment;
        return $clone;
    }

    /**
     * @return array<int, ContentInterface>
     */
    public function getContents(): iterable
    {
        foreach ($this->contents as $contents) {
            foreach ($contents as $content) {
                yield $content;
            }
        }
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
}
