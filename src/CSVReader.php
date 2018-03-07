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
    protected $header = null;
    protected $handle = null;
    protected $sourceEncoding = 'UTF-8';
    protected $targetEncoding = 'UTF-8';
    protected $lowerCaseHeader = true;
    // if strict is true then if the file is invalid csv then it fails even if it can be read.
    protected $strict = false;

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
        if($enclosure === null) {
            throw new Exception("No string enclosure is provided!");
        }

        if($enclosure === ''){
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
        if($delimiter === null){
            throw new Exception("No delimiter is provided!");
        }

        if($delimiter === ""){
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
     * @param array|null $header
     * @return array|false
     * @throws Exception
     */
    protected function readLine(array $header = null)
    {
        $header = $this->getHeader();
        if (isset($header)) {
            $columnCount = count($header);
        } else {
            $columnCount = null;
        }
        return $this->getLine($this->handle, $columnCount, $header);
//
//        if ($row === false) {
//            return $row;
//        }
//
//        return $row; //$this->parseCSVString($string, $this->delimiter, $this->enclosure, $this->escape);
    }

    /**
     * @param $line
     * @param $delimiter
     * @param $enclosure
     * @param $escape
     * @return array
     */
    private function parseCSVString($line, $delimiter, $enclosure, $escape)
    {
        $row = [];

        $inString = false;
        $lastColIndex = -1;

        $trimmedLine = trim($line);
        $lineLength = strlen($trimmedLine);

        for($i=0; $i < $lineLength; $i++) {
            $currentChar = $trimmedLine[$i];

            if ($this->indexIsInsideString($i - 1, $trimmedLine, $lineLength)) {
                $previousChar = $trimmedLine[$i - 1];
            } else {
                $previousChar = null;
            }

            if ($this->indexIsInsideString($i + 1, $trimmedLine, $lineLength)) {
                $nextChar = $trimmedLine[$i + 1];
            } else {
                $nextChar = null;
            }

            // if current character is enclosure
            // and inside string
            // then the next character must be a delimiter
            // or it must be the end of the line
            if($currentChar === $this->enclosure
                && $inString === true
                && (
                    $nextChar === $this->delimiter
                    || ($i === $lineLength - 1 && (!isset($this->header) || count($row) == count($this->header)))
                ) && $previousChar !== $escape) {
                $inString = false;
            }

            // if current character is enclosure
            // and not inside string
            // the previous character must be a delimiter
            // or it must be the start of the line
            elseif($currentChar === $this->enclosure
                && $inString === false
                && (
                    $previousChar === $this->delimiter
                    || $i == 0
                )) {
                $inString = true;
            }

            if($currentChar === $delimiter && $inString === false) {
                $col = substr($trimmedLine, $lastColIndex + 1, $i - $lastColIndex - 1);
                $row[] = $this->parseColValue($col, $enclosure);
//                var_dump($row);
                $lastColIndex = $i;
            }

        }

        $col = substr($trimmedLine, $lastColIndex + 1);
        $row[] = $this->parseColValue($col, $enclosure);


        return $row;
    }

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

//    private function getLine($handle, $colCount = null, array $header = null) {
    private function getLine($handle,$colCount) {
        $prevLine = '';

        $inString = false;
        $col = 0;
        $lineCount = 0;

        $testVar = "";
        $row = [];

        while (($line = fgets($handle)) !== false) {
            $lineCount++;
            $trimmedLine = trim($line);
            $lineLength = strlen($trimmedLine);
            for($i=0; $i < $lineLength; $i++) {
                $currentChar = $trimmedLine[$i];
                $testVar = $testVar.$currentChar;
                if ($this->indexIsInsideString($i - 1, $trimmedLine)) {
                    $previousChar = $trimmedLine[$i - 1];
                } else {
                    $previousChar = null;
                }
                if ($this->indexIsInsideString($i + 1, $trimmedLine)) {
                    $nextChar = $trimmedLine[$i + 1];
                } else {
                    $nextChar = null;
                }
//                if (strlen($prevLine)> 0 && $i > 0) {
//                    $this->dd((
//                        $nextChar === $this->delimiter
//                        || ($i === $lineLength - 1 && $col == $colCount -1)
//                    ));
//                }
                // if current character is enclosure
                // and inside string
                // then the next character must be a delimiter
                // or it must be the end of the line and it must be the last column
                if($currentChar === $this->enclosure
                    && $inString === true
                    && (
                        $nextChar === $this->delimiter
                        || ($i === $lineLength - 1 && $col == $colCount - 1)
                    )
                    && $previousChar !== $this->escape) {
                    $inString = false;
                }
                // if current character is enclosure
                // and not inside string
                // the previous character must be a delimiter
                // or it must be the start of the line
                elseif($currentChar === $this->enclosure
                    && $inString === false
                    && (
                        $previousChar === $this->delimiter
                        || $i === 0
                    )) {
                    $inString = true;
                }

                if ($inString === false && $i === $lineLength - 1 && $col == $colCount - 1) {
                    $col++;
                }

                if($inString === false && $trimmedLine[$i] === $this->delimiter) {
                    $col++;
                    $testVar = substr($testVar,0,strlen($testVar)-1);
                    $testVar = $this->parseColValue($testVar,$this->enclosure);
                    $row[] = $testVar;
                    $testVar = "";
                }

                if($i == $lineLength-1 && $inString === true){
                    $testVar = $testVar."\n";
                }

            }

            $testVar = $this->parseColValue($testVar,$this->enclosure);

            if(
                $trimmedLine[$i] === $this->delimiter
                || ($i === $lineLength && $col == $colCount)
            ) {
                $row[] = $testVar;
            }


            if($col == $colCount && !$inString) {
                return $row;
            }
            elseif($col > $colCount) {
                throw new Exception('Er zit een fout in het bestand op regel '.$lineCount.' cols('.$col.')');
            }
            else {
                $prevLine .= $line;
            }
        }
        return false;
    }

    /**
     * @param StreamInterface $stream
     */
    protected function setStream(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Set the path to a file to read from
     *
     * @param $path
     */
    public function setPath($path)
    {
        $this->header = null;
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
     * Get the records iterator
     *
     * @return Iterator
     */
    public function getRecords(): Iterator
    {
        return $this->getIterator();
    }

    /**
     * @return array
     */
    public function getHeader()
    {
        if ($this->header == null) {
            $prevLine = '';

            $inString = false;
            $col = 1;

            while (($line = fgets($this->handle)) !== false) {
                for ($i = 0; $i < strlen($line); $i++) {
                    if ($line[$i] === $this->enclosure) {
                        $inString = !$inString;
                    }
                    if ($line[$i] === $this->delimiter && $inString === false) {
                        $col++;
                    }
                }

                if (!$inString) {
                    $line = $prevLine . $line;

                    if ($this->lowerCaseHeader) {
                        $line = strtolower($line);
                    }

                    return $this->header = $this->parseCSVString($line, $this->delimiter, $this->enclosure, $this->escape);
                } else {
                    $prevLine .= $line;
                }
            }

            return false;
        } else {
            return $this->header;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getRow()
    {
        $header = $this->getHeader();

        $row = [];

        if (!is_array($line = $this->readLine($header))) {
            return false;
        }

        foreach ($line as $index => $value) {
            if (!isset($header[$index])) {
                throw new \App\Import\Exceptions\Exception('Too many cols!');
            }
            $colName = $this->lowerCaseHeader ? strtolower($header[$index]) : $header[$index];
            $row[$colName] = $this->parseValue($value);
        }

        return $row;
    }

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
     * @return array
     * @throws Exception
     */
    public function getAllRows()
    {
        $rows = [];

        while(!feof($this->handle)) {
            $row = $this->getRow();
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
     * @inheritdoc
     */
    public function getMappingIterator(MappingInterface $mapping): Iterator
    {
        return new MappingStreamIterator($this->getStream(), $mapping);
    }

    /**
     * @inheritdoc
     */
    public function getAssociativeMappingIterator(MappingInterface $mapping): Iterator
    {
        return new MappingAssociativeArrayIterator($this->getAllRows(), $mapping);
    }

    private function dd(...$values)
    {
        $this->dump(...$values);
        die();
    }

    private function dump(...$values)
    {
        foreach ($values as $value) {
            var_dump($value);
        }
    }
}