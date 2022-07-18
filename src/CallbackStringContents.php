<?php

declare(strict_types=1);

namespace Genkgo\ArchiveStream;

/**
 * @implements \IteratorAggregate<int, string>
 */
final class CallbackStringContents implements \IteratorAggregate
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function getIterator(): \Iterator
    {
        $iterator = \call_user_func($this->callback);
        if ($iterator instanceof \Iterator === false) {
            throw new \UnexpectedValueException('Callback should return an iterator');
        }

        return $iterator;
    }
}
