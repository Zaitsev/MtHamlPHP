<?php

namespace MtHamlPHP\Tests;

use MtHaml\Parser;
use MtHaml\NodeVisitor\Printer;

require_once __DIR__ . '/TestCase.php';
//error_reporting(E_ALL);

class HamlPHPTest extends TestCase
{
    /** @dataProvider getEnvironmentTests */
    public function testEnvironment($file)
    {
        $parts = $this->parseTestFile($file);
        $describe=pathinfo($file);
        $describe="===== ".$describe['filename']." ====";
        echo "$describe\n";
        file_put_contents($file . '.haml', $parts['HAML']);
        file_put_contents($file . '.php', $parts['FILE']);
        file_put_contents($file . '.exp', $parts['EXPECT']);
        if (isset( $parts['SNIP'])) file_put_contents($file . '.snip', $parts['SNIP']);

        if (!empty($parts['SKIP'])) {
            $this->markTestIncomplete(
                $describe.':'.$parts['SKIP']
            );
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

        $this->assertSame($parts['EXPECT'], $out,$describe);

        if (isset($parts['EXECUTED'])) {
            ob_start();
            require $file . '.out';
            $compiled = ob_get_clean();
            $this->assertSame($parts['EXECUTED'], $compiled, $describe);
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

    public function getEnvironmentTests()
    {
        if (false !== $tests = getenv('ENV_TESTS')) {
            $files = explode(' ', $tests);
        } else {
            $files = glob(__DIR__ . '/fixtures/hamlphp/*.test');
        }
        return array_map(function($file) {
            return array($file);
        }, $files);
    }
}


