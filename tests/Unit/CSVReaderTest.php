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
     * Data provider for testDecomposeAddress
     * variables are in the order of
     * $original, $expected
     *
     * @return array
     */
    public function readSimpleCSVProvider()
    {
        return [
            [
                'tests/files/test1.csv',
                '',
                '',
                [
                    'field1' => 'vgeruyi',
                    'field2' => '456',
                    'field3' => 'tyvreu24658',

                ],
            ],
            [
                'tests/files/test2.csv',
                ';',
                '|',
                [
                    'field1' => 'vgeruyi',
                    'field2' => '456',
                    'field3' => 'tyvreu24658',
                ]
            ]
        ];
    }

    /**
     * Data provider for testDecomposeAddress
     * variables are in the order of
     * $original, $expected
     *
     * @return array
     */
    public function differentCaseIndexesCSVProvider()
    {
        return [
            [
                'tests/files/test3.csv',
                '',
                '',
                true,
                [
                    'field1' => 'test',
                    'field2' => 'test',
                    'field3' => 'test',

                ],
            ],
            [
                'tests/files/test3.csv',
                '',
                '',
                false,
                [
                    'Field1' => 'test',
                    'Field2' => 'test',
                    'Field3' => 'test',
                ]
            ],
            [
                'tests/files/test4.csv',
                '',
                '',
                true,
                [
                    'field1' => 'test',
                    'field2' => 'test',
                    'field3' => 'test',
                ]
            ],
            [
                'tests/files/test4.csv',
                '',
                '',
                false,
                [
                    'FIeld1' => 'test',
                    'FiEld2' => 'test',
                    'FielD3' => 'test',
                ]
            ]
        ];
    }

    /**
     * Test that the CSVReader can handle reading a simple row with only strings and numbers no enclosures.
     * @param $filename
     * @param $delimiter
     * @param $enclosure
     * @param $expected
     * @return void
     *
     * @throws Exception
     * @dataProvider readSimpleCSVProvider
     */
    public function testSimpleCSVFile($filename, $delimiter, $enclosure, $expected)
    {
        $reader = $this->getCSVReader();
        $reader->setPath($filename);
        $reader->setDelimiter($delimiter);
        $reader->setEnclosure($enclosure);


        try {
            $rows = $reader->getAllRows();
        } catch (Exception $e) {
            $rows = false;
        }

        $this->assertInternalType('array', $rows);
        $this->assertCount(1, $rows);
        foreach ($rows as $row) {
            $this->assertEquals($expected, $row);
        }

    }

    /**
     * Test that the CSVReader can handle both lower case and upper case indexes.
     * @param $filename
     * @param $delimiter
     * @param $enclosure
     * @param $lowerCaseHeader
     * @param $expected
     * @throws Exception
     * @dataProvider differentCaseIndexesCSVProvider
     */
    public function testIndexReading($filename, $delimiter, $enclosure, $lowerCaseHeader, $expected)
    {
        $reader = $this->getCSVReader();
        $reader->setPath($filename);
        $reader->setDelimiter($delimiter);
        $reader->setEnclosure($enclosure);
        $reader->setLowerCaseHeader($lowerCaseHeader);

        try {
            $rows = $reader->getAllRows();
        } catch (Exception $e) {
            $rows = false;
        }

        $this->assertInternalType('array', $rows);
        $this->assertCount(1, $rows);
        foreach ($rows as $row) {
            $this->assertEquals($expected, $row);
        }
    }

    /**
     * Test that the CSVReader can handle reading a row with multiple lines
     *
     * @return void
     */
    public function testMultiLineRead()
    {
        $reader = $this->getCSVReader();
        $reader->setPath('tests/files/test5.csv');
        $expected = [
            'field1' => 'test1',
            'field2' => 'test2',
            'field3' => "te\nst\n34",
            'fiedl4' => 'test4'
        ];

        try {
            $rows = $reader->getAllRows();
        } catch (Exception $e) {
            $rows = false;
        }

        $this->assertInternalType('array', $rows);
        $this->assertCount(1, $rows);

        foreach ($rows as $row) {
            $this->assertInternalType('array', $row);
            $this->assertCount(4, $row);
            $this->assertEquals($expected,$row);
        }
    }

    /**
     * Test that the CSVReader can handle reading a row with multiple lines and unescaped string enclosures
     *
     * @return void
     */
    public function testMultilineUnescapedStringEnclosure()
    {
        $reader = $this->getCSVReader();
        $reader->setPath('tests/files/test6.csv');
        $expected = [
            'field1' => 'test1',
            'field2' => 'test"2',
            'field3' => "\n".'"',
        ];


        try {
            $rows = $reader->getAllRows();
        } catch (Exception $e) {
            $rows = false;
        }

        $this->assertInternalType('array', $rows);
        $this->assertCount(1, $rows);

        foreach ($rows as $row) {
            $this->assertInternalType('array', $row);
            $this->assertCount(3, $row);
            $this->assertEquals($expected,$row);
        }
    }

    /**
     * Test that the CSVReader can handle a string enclosed column before an empty last column
     *
     * @return void
     */
    public function testStringColBeforeEmptyLastCol()
    {
        $reader = $this->getCSVReader();
        $reader->setPath('tests/files/test7.csv');
        $expected = [
            'field1' => 't1',
            'field2' => 't2',
            'field3' => '0,0',
            'field4' => '',
        ];

        try {
            $rows = $reader->getAllRows();
        } catch (Exception $e) {
            $rows = false;
        }

        $this->assertInternalType('array', $rows);
        $this->assertCount(1, $rows);

        foreach ($rows as $row) {
            $this->assertInternalType('array', $row);
            $this->assertCount(4, $row);
            $this->assertEquals($expected,$row);
        }
    }

    /**
     * Test that the CSVReader can ISO encoding and can transform it in UTF-8
     *
     */
    public function testEncoding()
    {

        $reader = $this->getCSVReader();
        $reader->setSourceEncoding('ISO-8859-1');
        $reader->setPath('tests/files/test8.csv');

        try {
            $rows = $reader->getAllRows();
        } catch (Exception $e) {
            $rows = false;
        }

        $this->assertInternalType('array', $rows);
        $this->assertCount(1, $rows);

        $row = array_shift($rows);

        $this->assertInternalType('array', $row);
        $this->assertCount(4, $row);
        $this->assertArrayHasKey('field4', $row);
        $this->assertEquals('André', $row['field4']);

    }
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