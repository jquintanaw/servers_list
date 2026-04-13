<?php

declare(strict_types=1);

namespace App\Tests\Integration;

require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

class DoctrineConfigTest
{
    private array $errors = [];

    public function run(): int
    {
        $this->testDoctrineServerVersionConfigured();
        $this->testDatabaseUrlUsesCorrectHost();
        
        if (!empty($this->errors)) {
            echo "\n❌ FAILED - " . count($this->errors) . " error(s):\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
            return 1;
        }
        
        echo "\n✅ PASSED - All Doctrine configuration tests passed!\n";
        return 0;
    }

    private function fail(string $message): void
    {
        $this->errors[] = $message;
    }

    public function testDoctrineServerVersionConfigured(): void
    {
        $configFile = dirname(__DIR__, 2) . '/config/packages/doctrine.yaml';
        
        if (!file_exists($configFile)) {
            $this->fail("doctrine.yaml not found");
            return;
        }
        
        $config = Yaml::parseFile($configFile);
        $serverVersion = $config['doctrine']['dbal']['server_version'] ?? null;
        
        if (empty($serverVersion)) {
            $this->fail("doctrine.yaml missing 'server_version' in doctrine.dbal configuration");
        } elseif (!preg_match('/^\d+(\.\d+)?$/', $serverVersion)) {
            $this->fail("server_version must be in format '16' or '16.0', got: '$serverVersion'");
        }
    }

    public function testDatabaseUrlUsesCorrectHost(): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        
        if (!file_exists($envFile)) {
            $this->fail(".env file not found");
            return;
        }
        
        $envContent = file_get_contents($envFile);
        
        if (preg_match('/DATABASE_URL.*@([^:]+):5432/', $envContent, $matches)) {
            $host = $matches[1];
            
            if ($host === '127.0.0.1' || $host === 'localhost') {
                $this->fail("DATABASE_URL in .env points to '$host' - use 'db' service name for Docker");
            }
        }
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $test = new DoctrineConfigTest();
    exit($test->run());
}