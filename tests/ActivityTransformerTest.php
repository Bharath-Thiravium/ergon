<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/helpers/ActivityTransformer.php';

class ActivityTransformerTest extends TestCase
{
    public function testActivityTransformation()
    {
        $input = "running";
        $output = ActivityTransformer::transform($input);
        $this->assertEquals("RUNNING", $output);
    }
    
    public function testEmptyInput()
    {
        $this->assertEquals('', ActivityTransformer::transform(''));
        $this->assertEquals('', ActivityTransformer::transform(null));
    }
    
    public function testNormalization()
    {
        $this->assertEquals('Running', ActivityTransformer::normalize('RUNNING'));
        $this->assertEquals('Task completed', ActivityTransformer::normalize('TASK COMPLETED'));
    }
    
    public function testSanitization()
    {
        $maliciousInput = '<script>alert("xss")</script>running';
        $sanitized = ActivityTransformer::sanitize($maliciousInput);
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertEquals('scriptalert(xss)/scriptrunning', $sanitized);
    }
    
    public function testWhitespaceHandling()
    {
        $this->assertEquals('RUNNING', ActivityTransformer::transform('  running  '));
        $this->assertEquals('Running', ActivityTransformer::normalize('  running  '));
    }
}
