<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $compiledViewPath = $app->storagePath('framework/testing/views');

        if (! is_dir($compiledViewPath)) {
            mkdir($compiledViewPath, 0777, true);
        }

        $app['config']->set('view.compiled', $compiledViewPath);

        return $app;
    }
}
