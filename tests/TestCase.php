<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    // Disable exception handling on all tests
    public function setUp(): void
    {
        parent::setUp();
        $this->withHeader('accept', 'application/json');
    }

    function failedJSONtemplate($field, $message)
    {
        return [
            'message' => $message,
            'errors' => [
                $field => [
                    $message
                ]
            ]
        ];
    }
}
