<?php namespace Dossierdata\CsvReader\Interfaces;

use Countable;
use Iterator;
use IteratorAggregate;

interface ReaderInterface extends Countable, IteratorAggregate
{

    /**
     * Set the path to a file to read from
     *
     * @param $path
     */
    public function setPath($path);

    /**
     * Set the string content to read
     *
     * @param $string
     */
    public function setString(string $string);

    /**
     * @inheritdoc
     */
    public function count(): int;

    /**
     * Get the records iterator
     *
     * @return Iterator
     */
    public function getRecords(): Iterator;

    /**
     * @inheritdoc
     */
    public function getIterator(): Iterator;
}