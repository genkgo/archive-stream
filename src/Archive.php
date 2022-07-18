<?php

declare(strict_types=1);

namespace Genkgo\ArchiveStream;

final class Archive
{
    /**
     * @var \AppendIterator|ContentInterface[]
     */
    private $content;

    /**
     * @var string
     */
    private $comment = '';

    public function __construct()
    {
        $this->content = new \AppendIterator();
    }

    /**
     * @param ContentInterface $content
     * @return Archive
     */
    public function withContent(ContentInterface $content): Archive
    {
        $clone = clone $this;
        $clone->content->append(new \ArrayIterator([$content]));
        return $clone;
    }

    /**
     * @param \Iterator|ContentInterface[] $contents
     * @return Archive
     */
    public function withContents(\Iterator $contents): Archive
    {
        $clone = clone $this;
        $clone->content->append($contents);
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
        return $this->content;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
}
