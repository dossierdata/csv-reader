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
     * @param null $emptyStringDefault
     */
    public function setEmptyStringDefault($emptyStringDefault);

    /**
     * @param bool $defaultEmptyStrings
     */
    public function setDefaultEmptyStrings(bool $defaultEmptyStrings);
    /**
     * @param bool $hasHeader
     */
    public function setHasHeader(bool $hasHeader);

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
     * @throws Exception
     */
    public function getAllRows();

}