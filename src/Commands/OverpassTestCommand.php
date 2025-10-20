<?php

declare(strict_types=1);

namespace Bmadigan\Overpass\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Bmadigan\Overpass\Services\PythonAiBridge;

class OverpassTestCommand extends Command
{
    public $signature = 'overpass:test
                        {--timeout=30 : Test timeout in seconds}
                        {--verbose : Show detailed output}';

    public $description = 'Test the Overpass bridge connection and configuration';

    public function handle(): int
    {
        $this->info('🐍 Testing Overpass Bridge Connection...');
        $this->newLine();

        try {
            $bridge = app(PythonAiBridge::class);
            
            $this->line('📋 Configuration Check:');
            $this->line('  Script Path: ' . config('overpass.script_path'));
            $this->line('  Timeout: ' . config('overpass.timeout') . 's');
            $this->line('  Max Output: ' . config('overpass.max_output_length') . ' chars');
            $this->newLine();

            $this->line('🔍 Testing connection...');
            
            $startTime = microtime(true);
            $result = $bridge->testConnection();
            $status = $result['status'] ?? 'unknown';
            $success = $result['success'] ?? ($status !== 'error');
            $message = $result['message'] ?? 'An unknown error occurred';
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if (!$success || $status === 'error') {
                $this->error('❌ Connection test failed!');
                $this->error('Error: ' . $message);
                return self::FAILURE;
            }

            $this->info("✅ Connection successful! ({$duration}ms)");

            if ($this->option('verbose')) {
                $this->newLine();
                $this->line('📊 Bridge Health Details:');
                $this->table(
                    ['Component', 'Status', 'Details'],
                    collect(Arr::get($result, 'components', []))->map(function ($details, $component) {
                        return [
                            $component,
                            $details['status'] ?? 'unknown',
                            isset($details['error']) ? $details['error'] : 'OK'
                        ];
                    })
                );

                $config = Arr::get($result, 'config', []);
                if (!empty($config)) {
                    $this->newLine();
                    $this->line('⚙️  Python Configuration:');
                    foreach ($config as $key => $value) {
                        $this->line("  {$key}: {$value}");
                    }
                }
            }

            $this->newLine();
            $this->info('🎉 Overpass is working correctly!');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Connection test failed with exception!');
            $this->error('Error: ' . $e->getMessage());

            if ($this->option('verbose')) {
                $this->newLine();
                $this->line('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            $this->newLine();
            $this->line('💡 Troubleshooting tips:');
            $this->line('  • Check if Python 3 is installed and accessible');
            $this->line('  • Verify the script path in config/overpass.php');
            $this->line('  • Ensure Python dependencies are installed');
            $this->line('  • Check your OpenAI API key configuration');

            return self::FAILURE;
        }
    }
}