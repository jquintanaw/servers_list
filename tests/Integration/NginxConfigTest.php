<?php

declare(strict_types=1);

namespace App\Tests\Integration;

require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

class NginxConfigTest
{
    private array $errors = [];

    public function run(): int
    {
        $this->testNginxConfigFileExists();
        $this->testNginxHasFastCgiPass();
        $this->testNginxHasHealthEndpoint();
        $this->testDockerfileExists();
        $this->testDockerfileHasPhpExtensions();
        
        if (!empty($this->errors)) {
            echo "\n❌ FAILED - " . count($this->errors) . " error(s):\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
            return 1;
        }
        
        echo "\n✅ PASSED - All Nginx and Dockerfile configuration tests passed!\n";
        return 0;
    }

    private function fail(string $message): void
    {
        $this->errors[] = $message;
    }

    public function testNginxConfigFileExists(): void
    {
        $configFile = dirname(__DIR__, 2) . '/docker/nginx/default.conf';
        if (!file_exists($configFile)) {
            $this->fail("nginx/default.conf not found");
        }
    }

    public function testNginxHasFastCgiPass(): void
    {
        $configFile = dirname(__DIR__, 2) . '/docker/nginx/default.conf';
        if (!file_exists($configFile)) {
            $this->fail("nginx/default.conf not found");
            return;
        }
        
        $content = file_get_contents($configFile);
        
        if (strpos($content, 'fastcgi_pass') === false) {
            $this->fail("nginx/default.conf missing fastcgi_pass directive");
        }
        
        if (strpos($content, 'location ~ ^/index\\.php') === false) {
            $this->fail("nginx/default.conf missing PHP location block (location ~ ^/index\\.php)");
        }
    }

    public function testNginxHasHealthEndpoint(): void
    {
        $configFile = dirname(__DIR__, 2) . '/docker/nginx/default.conf';
        if (!file_exists($configFile)) {
            return;
        }
        
        $content = file_get_contents($configFile);
        
        if (strpos($content, '/_health') === false) {
            $this->fail("nginx/default.conf missing /_health endpoint");
        }
    }

    public function testDockerfileExists(): void
    {
        $dockerfile = dirname(__DIR__, 2) . '/docker/Dockerfile';
        if (!file_exists($dockerfile)) {
            $this->fail("Dockerfile not found in docker/");
        }
    }

    public function testDockerfileHasPhpExtensions(): void
    {
        $dockerfile = dirname(__DIR__, 2) . '/docker/Dockerfile';
        if (!file_exists($dockerfile)) {
            return;
        }
        
        $content = file_get_contents($dockerfile);
        
        if (strpos($content, 'pdo_pgsql') === false && strpos($content, 'docker-php-ext-install') === false) {
            $this->fail("Dockerfile missing PostgreSQL PHP extensions");
        }
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $test = new NginxConfigTest();
    exit($test->run());
}