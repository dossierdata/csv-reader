<?php namespace Dossierdata\CsvReader\Contracts;

use Dossierdata\CsvReader\Exceptions\Exception;
use Dossierdata\CsvReader\Interfaces\ReaderInterface;

interface CSVReader extends ReaderInterface
{

    /**
     * @param $enclosure
     */
    public function setEnclosure($enclosure);

    /**
     * @param $delimiter
     */
    public function setDelimiter($delimiter);

    /**
     * @param bool $lowerCaseHeader
     */
    public function setLowerCaseHeader($lowerCaseHeader);

    /**
     * @param string $sourceEncoding
     */
    public function setSourceEncoding($sourceEncoding);

    /**
     * @param string $targetEncoding
     */
    public function setTargetEncoding($targetEncoding);

    /**
     * @return array
     */
    public function getHeader();

    /**
     * @return array
     * @throws Exception
     */
    public function getAllRows();

}