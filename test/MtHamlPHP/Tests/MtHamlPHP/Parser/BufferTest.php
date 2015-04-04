<?php

namespace MtHamlPHP\Tests\Parser;

use MtHaml\Parser\Buffer;
use MtHamlPHP\Dbg;

class BufferTest extends \PHPUnit_Framework_TestCase
{
	public function testInjectLines(){
		$buffer = new Buffer("    abc\n    def\nghi");
		$buffer->injectLines(["  jkl","  mop"],1);
		$this->assertTrue($buffer->nextLine()); //this skip magic comment

		$this->assertSame("  jkl", $buffer->getLine());
		$this->assertSame(1, $buffer->getLineno());
		$this->assertSame(1, $buffer->getColumn());

		$this->assertTrue($buffer->nextLine());
		$this->assertSame("  mop", $buffer->getLine());
		$this->assertSame(2, $buffer->getLineno());


		$this->assertTrue($buffer->nextLine());
		$this->assertSame("    abc", $buffer->getLine());
		$this->assertSame(3, $buffer->getLineno());


		$this->assertTrue($buffer->nextLine());
		$this->assertSame("    def", $buffer->getLine());
		$this->assertSame(4, $buffer->getLineno());

		$this->assertTrue($buffer->nextLine());
		$this->assertSame("ghi", $buffer->getLine());
		$this->assertSame(5, $buffer->getLineno());

		$this->assertFalse($buffer->nextLine());

	}

}
