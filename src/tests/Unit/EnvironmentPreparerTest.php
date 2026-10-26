<?php

namespace Tests\Unit;

use App\Support\EnvironmentPreparer;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnvironmentPreparerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/ecodata-environment-'.bin2hex(random_bytes(12));
        self::assertTrue(mkdir($this->directory, 0700));
        file_put_contents($this->directory.'/.env.example', "APP_NAME=Example\nAPP_KEY=\n");
    }

    protected function tearDown(): void
    {
        foreach (['.env', '.env.example'] as $file) {
            if (is_file($this->directory.'/'.$file)) {
                unlink($this->directory.'/'.$file);
            }
        }
        rmdir($this->directory);
        parent::tearDown();
    }

    public static function existingKeys(): array
    {
        $key = 'base64:'.base64_encode(str_repeat('k', 32));

        return [
            'plain LF' => ['APP_KEY='.$key."\n"],
            'plain CRLF' => ['APP_KEY='.$key."\r\n"],
            'spaces and double quotes' => ['APP_KEY = "'.$key.'" # conservar'."\n"],
            'export and single quotes' => ["export APP_KEY = '".$key."'\r\n"],
            'quoted name' => ['"APP_KEY" = "'.$key.'"'."\n"],
            'interpolation' => ['INITIAL_KEY="'.$key.'"'."\n".'APP_KEY="${INITIAL_KEY}"'."\n"],
        ];
    }

    #[DataProvider('existingKeys')]
    public function test_existing_keys_and_all_configuration_bytes_are_preserved(string $assignment): void
    {
        $contents = "# Configuración existente\nAPP_NAME=Keep\n".$assignment."CUSTOM=untouched\n";
        file_put_contents($this->directory.'/.env', $contents);
        EnvironmentPreparer::prepare($this->directory);
        EnvironmentPreparer::prepare($this->directory);
        self::assertSame($contents, file_get_contents($this->directory.'/.env'));
    }

    public static function emptyKeys(): array
    {
        return [
            'plain LF' => ["APP_KEY=\n"],
            'plain CRLF' => ["APP_KEY=\r\n"],
            'empty double quotes' => ['APP_KEY=""'."\n"],
            'empty single quotes' => ["APP_KEY=''\r\n"],
            'spaces export and comment' => ['export APP_KEY = "" # generar'."\r\n"],
            'quoted name' => ['"APP_KEY" = ""'."\n"],
            'unset key' => ["APP_KEY\n"],
            'missing key' => [''],
        ];
    }

    #[DataProvider('emptyKeys')]
    public function test_empty_key_is_generated_once_and_other_settings_are_preserved(string $assignment): void
    {
        file_put_contents($this->directory.'/.env', "APP_NAME=Keep\n".$assignment."CUSTOM=untouched\n");
        EnvironmentPreparer::prepare($this->directory);
        $contents = file_get_contents($this->directory.'/.env');
        $values = Dotenv::parse($contents);

        self::assertSame('Keep', $values['APP_NAME']);
        self::assertSame('untouched', $values['CUSTOM']);
        self::assertStringStartsWith('base64:', $values['APP_KEY']);
        self::assertSame(32, strlen(base64_decode(substr($values['APP_KEY'], 7), true)));

        EnvironmentPreparer::prepare($this->directory);
        self::assertSame($contents, file_get_contents($this->directory.'/.env'));
    }

    public function test_missing_environment_is_initialized_from_example_without_overwriting_example(): void
    {
        $example = "APP_NAME=New\r\nAPP_KEY=\r\nCUSTOM=from-example\r\n";
        file_put_contents($this->directory.'/.env.example', $example);
        EnvironmentPreparer::prepare($this->directory);
        $contents = file_get_contents($this->directory.'/.env');
        $values = Dotenv::parse($contents);
        self::assertSame('New', $values['APP_NAME']);
        self::assertSame('from-example', $values['CUSTOM']);
        self::assertStringContainsString("\r\n", $contents);
        self::assertStringNotContainsString("\n", str_replace("\r\n", '', $contents));
        self::assertSame($example, file_get_contents($this->directory.'/.env.example'));
    }

    public function test_malformed_existing_configuration_is_rejected_without_changing_it(): void
    {
        $contents = "APP_KEY=\nBROKEN=unquoted value\n";
        file_put_contents($this->directory.'/.env', $contents);
        try {
            EnvironmentPreparer::prepare($this->directory);
            self::fail('La configuración inválida debe rechazarse.');
        } catch (InvalidFileException) {
            self::assertSame($contents, file_get_contents($this->directory.'/.env'));
        }
    }

    public function test_existing_key_from_example_is_copied_without_rotation(): void
    {
        $example = "APP_NAME=New\nAPP_KEY=base64:".base64_encode(str_repeat('k', 32))."\n";
        file_put_contents($this->directory.'/.env.example', $example);
        EnvironmentPreparer::prepare($this->directory);
        self::assertSame($example, file_get_contents($this->directory.'/.env'));
    }
}
