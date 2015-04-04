<?php

namespace MtHamlPHP\Tests;

use MtHaml\Parser;
use MtHaml\NodeVisitor\Printer;
use MtHamlPHP\Dbg;

require_once __DIR__ . './TestCase.php';
//error_reporting(E_ALL);
/**
 * Class HamlPHPTest
 * @package MtHamlPHP\Tests
 */
class HamlPHPTest extends TestCase
{
    /** @dataProvider getHamlPHPTests */
    public function testHamlPHP($file)
    {
        $parts = $this->parseTestFile($file);
        file_put_contents($file . '.haml', $parts['HAML']);
        file_put_contents($file . '.php', $parts['FILE']);
        file_put_contents($file . '.exp', $parts['EXPECT']);
        if (isset( $parts['SNIP'])) file_put_contents($file . '.snip', $parts['SNIP']);

        if (!empty($parts['SKIP'])) {
            $this->markTestIncomplete('skipped');
            return;
        }

        try {
            ob_start();
            require $file . '.php';
            $out = ob_get_clean();
        } catch(\Exception $e) {
            $this->assertException($parts, $e);
            $this->cleanup($file);
            return;
        }
        $this->assertException($parts);

        file_put_contents($file . '.out', $out);

        $this->assertSame($parts['EXPECT'], $out);

        if (isset($parts['EXECUTED'])) {
            ob_start();
            require $file . '.out';
            $compiled = ob_get_clean();
            $this->assertSame($parts['EXECUTED'], $compiled);
        }
        $this->cleanup($file);
    }

    protected function cleanup($file)
    {
        if (file_exists($file . '.out')) {
            unlink($file . '.out');
        }
        unlink($file . '.haml');
        unlink($file . '.php');
        unlink($file . '.exp');
        if (file_exists($file . '.snip')) {
            unlink($file . '.snip');
        }
    }

    public function getHamlPHPTests()
    {
        if (false !== $tests = getenv('ENV_TESTS')) {
            $files = explode(' ', $tests);
        } else {
            $files = glob(__DIR__ . '/MtHamlPHP/fixtures/environment/*.test');
        }
	    $t = array();
	    foreach ($files as $file){
		    $t[pathinfo($file)['filename']] = array($file);
	    }
	    return $t;
    }
}


