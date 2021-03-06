<?php

declare(strict_types=1);

namespace ParaTest\Logging\JUnit;

use PHPUnit\Framework\ExpectationFailedException;

class ReaderTest extends \TestBase
{
    protected $mixedPath;
    protected $mixed;
    protected $single;
    protected $empty;

    public function setUp()
    {
        $this->mixedPath = FIXTURES . DS . 'results' . DS . 'mixed-results.xml';
        $single = FIXTURES . DS . 'results' . DS . 'single-wfailure.xml';
        $this->mixed = new Reader($this->mixedPath);
        $this->single = new Reader($single);
        $empty = FIXTURES . DS . 'results' . DS . 'empty-test-suite.xml';
        $this->empty = new Reader($empty);
    }

    /**
     * @expectedException   \InvalidArgumentException
     */
    public function testInvalidPathThrowsException()
    {
        $reader = new Reader('/path/to/nowhere');
    }

    public function testIsSingleSuiteReturnsTrueForSingleSuite()
    {
        $this->assertTrue($this->single->isSingleSuite());
    }

    public function testIsSingleSuiteReturnsFalseForMultipleSuites()
    {
        $this->assertFalse($this->mixed->isSingleSuite());
    }

    public function testMixedSuiteShouldConstructRootSuite()
    {
        $suites = $this->mixed->getSuites();
        $this->assertEquals(1, count($suites));
        $this->assertEquals('test/fixtures/tests/', $suites[0]->name);
        $this->assertEquals('7', $suites[0]->tests);
        $this->assertEquals('6', $suites[0]->assertions);
        $this->assertEquals('2', $suites[0]->failures);
        $this->assertEquals('1', $suites[0]->errors);
        $this->assertEquals('0.007625', $suites[0]->time);

        return $suites[0];
    }

    /**
     * @depends testMixedSuiteShouldConstructRootSuite
     *
     * @param mixed $suite
     */
    public function testMixedSuiteConstructsChildSuites($suite)
    {
        $this->assertEquals(3, count($suite->suites));
        $first = $suite->suites[0];
        $this->assertEquals('UnitTestWithClassAnnotationTest', $first->name);
        $this->assertEquals('/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithClassAnnotationTest.php', $first->file);
        $this->assertEquals('3', $first->tests);
        $this->assertEquals('3', $first->assertions);
        $this->assertEquals('1', $first->failures);
        $this->assertEquals('0', $first->errors);
        $this->assertEquals('0.006109', $first->time);

        return $first;
    }

    /**
     * @depends testMixedSuiteConstructsChildSuites
     *
     * @param mixed $suite
     */
    public function testMixedSuiteConstructsTestCases($suite)
    {
        $this->assertEquals(3, count($suite->cases));
        $first = $suite->cases[0];
        $this->assertEquals('testTruth', $first->name);
        $this->assertEquals('UnitTestWithClassAnnotationTest', $first->class);
        $this->assertEquals('/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithClassAnnotationTest.php', $first->file);
        $this->assertEquals('10', $first->line);
        $this->assertEquals('1', $first->assertions);
        $this->assertEquals('0.001760', $first->time);
    }

    public function testMixedSuiteCasesLoadFailures()
    {
        $suites = $this->mixed->getSuites();
        $case = $suites[0]->suites[0]->cases[1];
        $this->assertEquals(1, count($case->failures));
        $failure = $case->failures[0];
        $this->assertEquals(ExpectationFailedException::class, $failure['type']);
        $this->assertEquals("UnitTestWithClassAnnotationTest::testFalsehood\nFailed asserting that true is false.\n\n/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithClassAnnotationTest.php:20", $failure['text']);
    }

    public function testMixedSuiteCasesLoadErrors()
    {
        $suites = $this->mixed->getSuites();
        $case = $suites[0]->suites[1]->cases[0];
        $this->assertEquals(1, count($case->errors));
        $error = $case->errors[0];
        $this->assertEquals('Exception', $error['type']);
        $this->assertEquals("UnitTestWithErrorTest::testTruth\nException: Error!!!\n\n/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithErrorTest.php:12", $error['text']);
    }

    public function testSingleSuiteShouldConstructRootSuite()
    {
        $suites = $this->single->getSuites();
        $this->assertEquals(1, count($suites));
        $this->assertEquals('UnitTestWithMethodAnnotationsTest', $suites[0]->name);
        $this->assertEquals('/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithMethodAnnotationsTest.php', $suites[0]->file);
        $this->assertEquals('3', $suites[0]->tests);
        $this->assertEquals('3', $suites[0]->assertions);
        $this->assertEquals('1', $suites[0]->failures);
        $this->assertEquals('0', $suites[0]->errors);
        $this->assertEquals('0.005895', $suites[0]->time);

        return $suites[0];
    }

    /**
     * @depends testSingleSuiteShouldConstructRootSuite
     *
     * @param mixed $suite
     */
    public function testSingleSuiteShouldHaveNoChildSuites($suite)
    {
        $this->assertEquals(0, count($suite->suites));
    }

    /**
     * @depends testSingleSuiteShouldConstructRootSuite
     *
     * @param mixed $suite
     */
    public function testSingleSuiteConstructsTestCases($suite)
    {
        $this->assertEquals(3, count($suite->cases));
        $first = $suite->cases[0];
        $this->assertEquals('testTruth', $first->name);
        $this->assertEquals('UnitTestWithMethodAnnotationsTest', $first->class);
        $this->assertEquals('/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithMethodAnnotationsTest.php', $first->file);
        $this->assertEquals('7', $first->line);
        $this->assertEquals('1', $first->assertions);
        $this->assertEquals('0.001632', $first->time);
    }

    public function testSingleSuiteCasesLoadFailures()
    {
        $suites = $this->single->getSuites();
        $case = $suites[0]->cases[1];
        $this->assertEquals(1, count($case->failures));
        $failure = $case->failures[0];
        $this->assertEquals(ExpectationFailedException::class, $failure['type']);
        $this->assertEquals("UnitTestWithMethodAnnotationsTest::testFalsehood\nFailed asserting that true is false.\n\n/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithMethodAnnotationsTest.php:18", $failure['text']);
    }

    public function testEmptySuiteConstructsTestCase()
    {
        $suites = $this->empty->getSuites();
        $this->assertEquals(1, count($suites));

        $suite = $suites[0];
        $this->assertEquals('', $suite->name);
        $this->assertEquals('', $suite->file);
        $this->assertEquals(0, $suite->tests);
        $this->assertEquals(0, $suite->assertions);
        $this->assertEquals(0, $suite->failures);
        $this->assertEquals(0, $suite->errors);
        $this->assertEquals(0, $suite->time);
    }

    public function testMixedGetTotals()
    {
        $this->assertEquals(7, $this->mixed->getTotalTests());
        $this->assertEquals(6, $this->mixed->getTotalAssertions());
        $this->assertEquals(2, $this->mixed->getTotalFailures());
        $this->assertEquals(1, $this->mixed->getTotalErrors());
        $this->assertEquals(0.007625, $this->mixed->getTotalTime());
    }

    public function testSingleGetTotals()
    {
        $this->assertEquals(3, $this->single->getTotalTests());
        $this->assertEquals(3, $this->single->getTotalAssertions());
        $this->assertEquals(1, $this->single->getTotalFailures());
        $this->assertEquals(0, $this->single->getTotalErrors());
        $this->assertEquals(0.005895, $this->single->getTotalTime());
    }

    public function testMixedGetFailureMessages()
    {
        $failures = $this->mixed->getFailures();
        $this->assertEquals(2, count($failures));
        $this->assertEquals("UnitTestWithClassAnnotationTest::testFalsehood\nFailed asserting that true is false.\n\n/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithClassAnnotationTest.php:20", $failures[0]);
        $this->assertEquals("UnitTestWithMethodAnnotationsTest::testFalsehood\nFailed asserting that true is false.\n\n/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithMethodAnnotationsTest.php:18", $failures[1]);
    }

    public function testMixedGetErrorMessages()
    {
        $errors = $this->mixed->getErrors();
        $this->assertEquals(1, count($errors));
        $this->assertEquals("UnitTestWithErrorTest::testTruth\nException: Error!!!\n\n/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithErrorTest.php:12", $errors[0]);
    }

    public function testSingleGetMessages()
    {
        $failures = $this->single->getFailures();
        $this->assertEquals(1, count($failures));
        $this->assertEquals("UnitTestWithMethodAnnotationsTest::testFalsehood\nFailed asserting that true is false.\n\n/home/brian/Projects/parallel-phpunit/test/fixtures/tests/UnitTestWithMethodAnnotationsTest.php:18", $failures[0]);
    }

    public function testMixedGetFeedback()
    {
        $totalCases = 7;
        $casesProcessed = 0;
        $feedback = $this->mixed->getFeedback($totalCases, $casesProcessed);
        $this->assertEquals(['.', 'F', '.', 'E', '.', 'F', '.'], $feedback);
    }

    public function testRemoveLog()
    {
        $contents = file_get_contents($this->mixedPath);
        $tmp = FIXTURES . DS . 'results' . DS . 'dummy.xml';
        file_put_contents($tmp, $contents);
        $reader = new Reader($tmp);
        $reader->removeLog();
        $this->assertFileNotExists($tmp);
    }

    /**
     * Extraction of log from xml file to use in test of validation "SystemOut" result.
     *
     * @return \stdClass $log
     */
    public static function extractLog()
    {
        $log = new \stdClass();
        $result = FIXTURES . DS . 'results' . DS . 'mixed-results-with-system-out.xml';
        $node = new Reader($result);
        $log->failure = $node->getSuites()[0]->suites[0]->cases[1]->failures[0]['text'];
        $log->error = $node->getSuites()[0]->suites[1]->cases[0]->errors[0]['text'];

        return $log;
    }

    public function testResultWithSystemOut()
    {
        $customLog = "\nCustom error log on result test with ";
        $result = FIXTURES . DS . 'results' . DS . 'mixed-results-with-system-out.xml';
        $failLog = self::extractLog()->failure . $customLog . 'failure!';
        $errorLog = self::extractLog()->error . $customLog . 'error!';
        $node = new Reader($result);
        $resultFail = $node->getSuites()[0]->suites[2]->cases[1]->failures[0]['text'];
        $resultError = $node->getSuites()[0]->suites[1]->cases[1]->errors[0]['text'];

        $this->assertEquals($failLog, $resultFail);
        $this->assertEquals($errorLog, $resultError);
    }
}
