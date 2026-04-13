<?php

declare(strict_types=1);

namespace App\Tests\Integration;

require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

class DockerComposeTest
{
    private array $config = [];
    private array $errors = [];

    public function __construct()
    {
        $yamlFile = __DIR__ . '/../../docker/docker-compose.yml';
        if (file_exists($yamlFile)) {
            $this->config = Yaml::parseFile($yamlFile);
        }
    }

    public function run(): int
    {
        $this->testDockerComposeFileExists();
        $this->testDatabaseServiceExists();
        $this->testPhpServiceExists();
        $this->testNginxServiceExists();
        $this->testDatabaseHasHealthCheck();
        $this->testPhpDependsOnDatabase();
        $this->testDatabaseCredentialsConfigured();
        
        if (!empty($this->errors)) {
            echo "\n❌ FAILED - " . count($this->errors) . " error(s):\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
            return 1;
        }
        
        echo "\n✅ PASSED - All Docker Compose configuration tests passed!\n";
        return 0;
    }

    private function fail(string $message): void
    {
        $this->errors[] = $message;
    }

    public function testDockerComposeFileExists(): void
    {
        $yamlFile = __DIR__ . '/../../docker/docker-compose.yml';
        if (!file_exists($yamlFile)) {
            $this->fail("docker-compose.yml file not found");
        }
    }

    public function testDatabaseServiceExists(): void
    {
        if (empty($this->config['services']['db'] ?? [])) {
            $this->fail("'db' service not found in docker-compose.yml");
        }
    }

    public function testPhpServiceExists(): void
    {
        if (empty($this->config['services']['php'] ?? [])) {
            $this->fail("'php' service not found in docker-compose.yml");
        }
    }

    public function testNginxServiceExists(): void
    {
        if (empty($this->config['services']['nginx'] ?? [])) {
            $this->fail("'nginx' service not found in docker-compose.yml");
        }
    }

    public function testDatabaseHasHealthCheck(): void
    {
        $dbService = $this->config['services']['db'] ?? [];
        if (empty($dbService['healthcheck'])) {
            $this->fail("'db' service missing healthcheck configuration");
        }
    }

    public function testPhpDependsOnDatabase(): void
    {
        $phpService = $this->config['services']['php'] ?? [];
        $dependsOn = $phpService['depends_on'] ?? [];
        
        if (empty($dependsOn['db'])) {
            $this->fail("'php' service must depend on 'db'");
        }
        
        if (($dependsOn['db']['condition'] ?? '') !== 'service_healthy') {
            $this->fail("'php' service must wait for 'db' health (condition: service_healthy)");
        }
    }

    public function testDatabaseCredentialsConfigured(): void
    {
        $dbService = $this->config['services']['db'] ?? [];
        $env = $dbService['environment'] ?? [];
        
        if (empty($env['POSTGRES_USER'])) {
            $this->fail("'db' service missing POSTGRES_USER environment variable");
        }
        if (empty($env['POSTGRES_PASSWORD'])) {
            $this->fail("'db' service missing POSTGRES_PASSWORD environment variable");
        }
        if (empty($env['POSTGRES_DB'])) {
            $this->fail("'db' service missing POSTGRES_DB environment variable");
        }
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $test = new DockerComposeTest();
    exit($test->run());
}