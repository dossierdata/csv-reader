<?php namespace Dossierdata\CsvReader\Tests\Unit;

use Dossierdata\CsvReader\CSVReader;
use Dossierdata\CsvReader\Exceptions\Exception;
use PHPUnit_Framework_TestCase;

class CSVReaderTest extends PHPUnit_Framework_TestCase
{


    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * @return CSVReader
     */
    private function getCSVReader()
    {
        /** @var CSVReader $reader */
        $reader = new CSVReader();

        return $reader;
    }

    /**
     * Test that the CSVReader can handle reading a row with multiple lines and unescaped string enclosures.
     *
     * @return void
     */
    public function testMultiLineReadWithUnescapedStringEnclosures()
    {
        $reader = $this->getCSVReader();
        $reader->setDelimiter(';');
        $reader->setPath('tests/files/csv_multi_line_read_test.csv');

        try {
            $rows = $reader->getAllRows();
        } catch (Exception $e) {
            $rows = false;
        }

        $this->assertInternalType('array', $rows);
        $this->assertCount(13, $rows);

        foreach ($rows as $row) {
            $this->assertInternalType('array', $row);
            $this->assertCount(120, $row);
        }
    }

    /**
     * Test that the CSVReader can handle reading a row with multiple lines and unescaped string enclosures.
     *
     * @return void
     */
    public function testEncoding()
    {
        $reader = $this->getCSVReader();
        $reader->setDelimiter(';');
        $reader->setSourceEncoding('ISO-8859-1');
        $reader->setPath('tests/files/csv_iso_8859_1_encoding_read_test.csv');

        try {
            $rows = $reader->getAllRows();
        } catch (Exception $e) {
            $rows = false;
        }

        $this->assertInternalType('array', $rows);
        $this->assertCount(1, $rows);

        $row = array_shift($rows);

        $this->assertInternalType('array', $row);
        $this->assertCount(120, $row);
        $this->assertArrayHasKey('voornaam', $row);
        $this->assertEquals('André', $row['voornaam']);
    }

    /**
     * Test that the CSVReader can handle a string enclosed column before an empty last column
     *
     * @return void
     */
    public function testColumnCount()
    {
        $reader = $this->getCSVReader();
        $reader->setDelimiter(',');
        $reader->setPath('tests/files/csv_string_col_before_empty_last_col_test.csv');

        try {
            $rows = $reader->getAllRows();
        } catch (Exception $e) {
            $rows = false;
        }

        $this->assertInternalType('array', $rows);
        $this->assertCount(2, $rows);

        foreach ($rows as $row) {
            $this->assertInternalType('array', $row);
            $this->assertCount(32, $row);
        }
    }
}