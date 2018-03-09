<?php namespace Dossierdata\CsvReader;

use Dossierdata\CsvReader\Exceptions\Exception;
use GuzzleHttp\Psr7;
use Iterator;
use Psr\Http\Message\StreamInterface;

class CSVReader implements \Dossierdata\CsvReader\Contracts\CSVReader
{
    protected $delimiter = ',';
    protected $enclosure = '"';
    protected $escape = '\\';
    protected $handle = null;
    protected $sourceEncoding = 'UTF-8';
    protected $targetEncoding = 'UTF-8';
    protected $lowerCaseHeader = true;
    // if strict is true then if the file is invalid csv then it fails even if it can be read.
    protected $strict = false;
    // set if the file has a header or not
    protected $hasHeader = true;

    /**
     * @var StreamInterface
     */
    protected $stream;

    /**
     * @var int
     */
    protected $recordCount = -1;

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        if (-1 === $this->recordCount) {
            $this->recordCount = iterator_count($this->getRecords());
        }

        return $this->recordCount;
    }

    /**
     * @param $enclosure
     * @throws Exception
     */
    public function setEnclosure($enclosure)
    {
        if ($enclosure === null) {
            throw new Exception("No string enclosure is provided!");
        }

        if ($enclosure === '') {
            $this->enclosure = '"';
        }

        $this->enclosure = $enclosure;
    }

    /**
     * @param $delimiter
     * @throws Exception
     */
    public function setDelimiter($delimiter)
    {
        if ($delimiter === null) {
            throw new Exception("No delimiter is provided!");
        }

        if ($delimiter === "") {
            $this->delimiter = ',';
            return;
        }

        $this->delimiter = $delimiter;
    }

    /**
     * @param bool $lowerCaseHeader
     */
    public function setLowerCaseHeader($lowerCaseHeader)
    {
        $this->lowerCaseHeader = $lowerCaseHeader;
    }

    /**
     * @param string $sourceEncoding
     */
    public function setSourceEncoding($sourceEncoding)
    {
        $this->sourceEncoding = $sourceEncoding;
    }

    /**
     * @param string $targetEncoding
     */
    public function setTargetEncoding($targetEncoding)
    {
        $this->targetEncoding = $targetEncoding;
    }

    /**
     * Set the path to a file to read from
     *
     * @param $path
     */
    public function setPath($path)
    {
        $this->handle = fopen($path, 'r');
    }

    /**
     * Set the string content to read
     *
     * @param $string
     */
    public function setString(string $string)
    {
        $this->setStream(Psr7\stream_for($string));
    }

    /**
     * Get the records iterator
     *
     * @return Iterator
     */
    public function getRecords(): Iterator
    {
        return $this->getIterator();
    }

    /**
     * @param $header
     * @return array|bool
     * @throws Exception
     */
    public function getRow($header)
    {
        $row = [];

        if (!is_array($line = $this->readLine($header))) {
            return false;
        }

        foreach ($line as $index => $value) {
            if (!isset($header[$index])) {
                throw new Exception('Too many cols!');
            }
            $colName = $this->lowerCaseHeader ? strtolower($header[$index]) : $header[$index];
            $row[$colName] = $this->parseValue($value);
        }

        return $row;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAllRows()
    {
        $rows = [];
        $header = $this->getLine($this->handle,null);

        //maybe do while ?
        while (!feof($this->handle)) {
            $row = $this->getRow($header);
            if (($row) !== false) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Iterator
    {
        return $this->getAllRows();
//
//        $csvHeaderMapping = new CSVHeaderMapping($this->getHeader(), $this->delimiter);
//
//        return new MappingStreamIterator($this->getStream(), $csvHeaderMapping);
    }

    /**
     * @param array|null $header
     * @return array|false
     * @throws Exception
     */
    protected function readLine(array $header = null)
    {
//        var_dump($header);
        if ($header != null) {
            $columnCount = count($header);
        } else {
            $columnCount = null;
        }
        return $this->getLine($this->handle, $columnCount);
    }

    /**
     * @return StreamInterface
     * @throws Exception
     */
    protected function getStream(): StreamInterface
    {
        if (is_null($this->stream)) {
            throw new Exception('Stream not set');
        }
        return $this->stream;
    }

    /**
     * @param StreamInterface $stream
     */
    protected function setStream(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * @param $value
     * @return null|string
     */
    protected function parseValue($value)
    {
        if ($value == "\x00") {
            return null;
        }
        if ($this->sourceEncoding !== $this->targetEncoding) {
            $value = iconv($this->sourceEncoding, $this->targetEncoding . "//TRANSLIT", $value);
        }
        return $value;
    }

    /**
     * @param $index
     * @param $string
     * @param null $stringLength
     * @return bool
     */
    private function indexIsInsideString($index, $string, $stringLength = null)
    {
        if ($stringLength == null) {
            $stringLength = strlen($string);
        }
        return $index >= 0 && $index <= ($stringLength - 1);
    }

    /**
     * @param $value
     * @param $enclosure
     * @return bool|string
     */
    private function parseColValue($value, $enclosure)
    {
        $colLength = strlen($value);

        // Check if first and last character are the string enclosure character
        if (isset($value[0]) && $value[0] === $enclosure && isset($value[$colLength - 1]) && $value[$colLength - 1] === $enclosure) {
            $value = substr($value, 1, $colLength - (strlen($enclosure) * 2));
        }


        return $value;
    }

    /**
     * @param $handle
     * @param null $colCount
     * @return array|bool
     * @throws Exception
     */
    private function getLine($handle, $colCount = null)
    {
        $prevLine = '';

        $inString = false;
        $col = 0;
        $lineCount = 0;

        $currentFieldValue = "";
        $row = [];

//        $columnName = $header[count($row)];

        while (($line = fgets($handle)) !== false) {
            $lineCount++;

            $this->parseFieldsFromLine($line, $col, $colCount, $inString, $currentFieldValue, $row, $currentCharIndex);


            $this->appendLastFieldIfEndOfRow($currentFieldValue, $currentCharIndex, trim($line), $col, $colCount, $row);

            if($colCount == null){
                $colCount = $col;
            }

//            var_dump('$col == $colCount && !$inString:',$col == $colCount && !$inString);
            if ($col == $colCount && !$inString) {
                return $row;
            } elseif ($col > $colCount) {
                throw new Exception('Er zit een fout in het bestand op regel ' . $lineCount . ' cols(' . $col . ')');
            } elseif ($col + 1 == $colCount && !feof($handle)) {
                $row[] = "";
                return $row;
            } else {
                $prevLine .= $line;
            }
        }
        return false;
    }

    /**
     * @param $line
     * @param $col
     * @param $colCount
     * @param $inString
     * @param $currentFieldValue
     * @param $row
     * @param $currentCharIndex
     */
    private function parseFieldsFromLine(
        $line,
        &$col,
        $colCount,
        &$inString,
        &$currentFieldValue,
        &$row,
        &$currentCharIndex
    ) {
        $trimmedLine = trim($line);
        $lineLength = strlen($trimmedLine);

        for ($currentCharIndex = 0; $currentCharIndex < $lineLength; $currentCharIndex++) {
            $currentChar = $trimmedLine[$currentCharIndex];
            $currentFieldValue = $currentFieldValue . $currentChar;

            list($previousChar, $nextChar) = $this->getPreviousAndNextCharFromStringByIndex($currentCharIndex,
                $trimmedLine);
            $inString = $this->isInsideStringField($currentChar, $previousChar, $nextChar,
                $lineLength, $col, $colCount, $currentCharIndex, $inString);
            $this->appendFieldToRowIfFieldEnds($inString, $lineLength, $col, $colCount,
                $currentFieldValue, $currentChar, $currentCharIndex, $row);
        }
        $currentCharIndex--;
    }

    /**
     * @param $currentFieldValue
     * @param $currentCharIndex
     * @param $line
     * @param $col
     * @param $colCount
     * @param $row
     */
    private function appendLastFieldIfEndOfRow(&$currentFieldValue, $currentCharIndex, $line, $col, $colCount, &$row)
    {
        $currentFieldValue = $this->parseColValue($currentFieldValue, $this->enclosure);

        if ($line[$currentCharIndex] === $this->delimiter) {
            $row[] = $currentFieldValue;
        }
        elseif($currentCharIndex + 1 === strlen(trim($line))){
            if($colCount != null){
                if($col == $colCount){
                    $row[] = $currentFieldValue;
                }
            }else{
                $row[] = $currentFieldValue;
            }
        }
    }

    /**
     * @param $currentChar
     * @param $trimmedLine
     * @return array
     */
    private function getPreviousAndNextCharFromStringByIndex($currentChar, $trimmedLine)
    {
        if ($this->indexIsInsideString($currentChar - 1, $trimmedLine)) {
            $previousChar = $trimmedLine[$currentChar - 1];
        } else {
            $previousChar = null;
        }
        if ($this->indexIsInsideString($currentChar + 1, $trimmedLine)) {
            $nextChar = $trimmedLine[$currentChar + 1];
        } else {
            $nextChar = null;
        }

        return [
            $previousChar,
            $nextChar
        ];
    }

    /**
     * @param $currentChar
     * @param $previousChar
     * @param $nextChar
     * @param $lineLength
     * @param $col
     * @param $colCount
     * @param $currentLineChar
     * @param $inString
     * @return bool
     */
    private function isInsideStringField(
        $currentChar,
        $previousChar,
        $nextChar,
        $lineLength,
        $col,
        $colCount,
        $currentLineChar,
        $inString
    ) {
        // if current character is enclosure
        // and inside string
        // then the next character must be a delimiter
        // or it must be the end of the line and it must be the last column

        if ($currentChar === $this->enclosure
            && $inString === true
            && $previousChar !== $this->escape) {
            if($nextChar === $this->delimiter){
                return false;
            }
            elseif($colCount !=null){
                if($currentLineChar === $lineLength - 1 && $col == $colCount - 1){

                    return false;
                }
            } else {
                return $inString;
            }
        }
        // if current character is enclosure
        // and not inside string
        // the previous character must be a delimiter
        // or it must be the start of the line
        elseif ($currentChar === $this->enclosure
            && $inString === false
            && (
                $previousChar === $this->delimiter
                || $currentLineChar === 0
            )) {
            return true;
        }

        return $inString;
    }

    /**
     * @param $inString
     * @param $lineLength
     * @param $col
     * @param $colCount
     * @param $possibleField
     * @param $currentChar
     * @param $currentCharIndex
     * @param $row
     */
    private function appendFieldToRowIfFieldEnds(
        $inString,
        $lineLength,
        &$col,
        $colCount,
        &$possibleField,
        $currentChar,
        $currentCharIndex,
        &$row
    ) {
        if ($inString === false && $currentChar === $this->delimiter) {
//            if($colCount != null){
//                if ($currentCharIndex === $lineLength - 1 && $col == $colCount - 1) {
//                    $col++;
//                }
//
//            }else{
//                $col++;
//            }
//            if () {
                $col++;
                $possibleField = substr($possibleField, 0, strlen($possibleField) - 1);
                $possibleField = $this->parseColValue($possibleField, $this->enclosure);
                $row[] = $possibleField;
                $possibleField = "";
//            }
        } elseif ($currentCharIndex == $lineLength - 1) {
            $possibleField = $possibleField . "\n";
        }
    }

//    private function dd(...$values)
//    {
//        $this->dump(...$values);
//        die();
//    }
//
//    private function dump(...$values)
//    {
//        foreach ($values as $value) {
//            var_dump($value);
//        }
//    }
}

function dd(...$args)
{
    dump(...$args);
    die();
}
function dump(...$args)
{

    foreach ($args as $arg) {
        echo PHP_EOL;
        echo (gettype($arg). ' ');
        print_r($arg);
        echo PHP_EOL;
    }
}