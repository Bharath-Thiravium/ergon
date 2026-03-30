<?php
use PHPUnit\Framework\TestCase;

class TransformerTest extends TestCase
{
    public function testBasicTransformation()
    {
        $input = "hello";
        $output = strtoupper($input);

        $this->assertEquals("HELLO", $output);
    }
}
