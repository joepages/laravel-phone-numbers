<?php

declare(strict_types=1);

namespace PhoneNumbers\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PhoneNumbers\PhoneNumbersServiceProvider;
use Tests\CreatesApplication;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ClassFileName.NoMatch

// @phpstan-ignore-next-line
if (trait_exists(CreatesApplication::class)) {
    abstract class UnitTestCaseBase extends BaseTestCase
    {
        use CreatesApplication;
    }
} else {
    // @codeCoverageIgnoreStart
    /** @noRector \Rector\CodingStyle\Rector\Stmt\UseClassKeywordForClassNameResolutionRector */
    abstract class UnitTestCaseBase extends \Orchestra\Testbench\TestCase {} // @codingStandardsIgnoreLine
    // @codeCoverageIgnoreEnd
}

/**
 * Base test case for pure unit tests that don't require database.
 */
abstract class UnitTestCase extends UnitTestCaseBase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PhoneNumbersServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('phone-numbers.types', ['mobile', 'home', 'work', 'fax', 'other']);
        $app['config']->set('phone-numbers.default_type', 'mobile');
    }
}
