<?php

namespace Tests\Unit\Rules;

use App\Rules\SecureFileUpload;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecureFileUploadTest extends TestCase
{
    public function test_validates_allowed_file_types()
    {
        $rule = new SecureFileUpload();
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        
        $fail = false;
        $rule->validate('file', $file, function() use (&$fail) {
            $fail = true;
        });
        
        $this->assertFalse($fail);
    }

    public function test_rejects_disallowed_file_types()
    {
        $rule = new SecureFileUpload();
        $file = UploadedFile::fake()->create('test.exe', 100, 'application/octet-stream');
        
        $fail = false;
        $rule->validate('file', $file, function() use (&$fail) {
            $fail = true;
        });
        
        $this->assertTrue($fail);
    }

    public function test_rejects_oversized_files()
    {
        $rule = new SecureFileUpload(['image/jpeg'], 1); // 1KB limit
        $file = UploadedFile::fake()->create('large.jpg', 2048, 'image/jpeg'); // 2MB file
        
        $fail = false;
        $rule->validate('file', $file, function() use (&$fail) {
            $fail = true;
        });
        
        $this->assertTrue($fail);
    }

    public function test_rejects_executable_extensions()
    {
        $rule = new SecureFileUpload();
        $file = UploadedFile::fake()->create('malicious.php', 100, 'text/plain');
        
        $fail = false;
        $rule->validate('file', $file, function() use (&$fail) {
            $fail = true;
        });
        
        $this->assertTrue($fail);
    }
}