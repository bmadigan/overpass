<?php

declare(strict_types=1);

namespace Bmadigan\Overpass\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use InvalidArgumentException;

/**
 * Bridge service connecting Laravel to Python AI processing capabilities.
 *
 * This class solves the fundamental challenge of integrating PHP with Python's
 * rich AI ecosystem. Rather than reimplementing complex libraries like OpenAI
 * and LangChain in PHP, we delegate AI operations to a Python subprocess.
 * 
 * The design emphasizes reliability over performance - we prioritize graceful
 * degradation and robust error handling since AI operations are inherently
 * unpredictable and external dependencies can fail.
 */
class PythonAiBridge
{
    private string $scriptPath;
    private int $timeout;
    private int $maxOutputLength;

    /**
     * Initialize the bridge with configuration-driven settings.
     *
     * We separate configuration from code to make deployment flexible
     * across different environments where Python paths and timeouts vary.
     */
    public function __construct()
    {
        $this->scriptPath = config('overpass.script_path');
        $this->timeout = config('overpass.timeout', 90);
        $this->maxOutputLength = config('overpass.max_output_length', 10000);
    }

    /**
     * Execute a generic Python AI operation.
     *
     * This method provides a flexible interface for any AI operation.
     * It showcases the core pattern: serialize data to JSON,
     * spawn a Python subprocess, and parse the response.
     */
    public function execute(string $operation, array $data = []): array
    {
        $this->validateScriptPath();

        $this->log('info', 'Calling Python AI bridge', [
            'operation' => $operation,
            'script_path' => $this->scriptPath,
            'data_keys' => array_keys($data),
        ]);

        $command = [
            'python3',
            $this->scriptPath,
            $operation,
            json_encode($data)
        ];

        try {
            $process = new Process($command);
            $process->setTimeout($this->timeout);

            // Forward API credentials securely through environment variables
            $process->setEnv($this->buildProcessEnvironment());

            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return $this->parseOutput($process->getOutput(), $operation);

        } catch (ProcessFailedException $e) {
            $this->log('error', 'Python AI bridge process failed', [
                'operation' => $operation,
                'command' => implode(' ', $command),
                'error_output' => $e->getProcess()->getErrorOutput(),
                'exit_code' => $e->getProcess()->getExitCode(),
            ]);

            throw $e;

        } catch (\Exception $e) {
            $this->log('error', 'Python AI bridge unexpected error', [
                'operation' => $operation,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Analyze data using AI capabilities.
     */
    public function analyzeData(array $data): array
    {
        return $this->execute('analyze_data', $data);
    }

    /**
     * Generate vector embeddings through OpenAI's text-embedding model.
     */
    public function generateEmbedding(string $text): array
    {
        $this->log('info', 'Generating embedding via Python AI bridge', [
            'text_length' => strlen($text),
        ]);

        $result = $this->execute('create_embeddings', ['texts' => [$text]]);

        // Navigate Python's serialization format for numpy arrays
        if (!isset($result['data']['embeddings']) || !is_array($result['data']['embeddings'])) {
            $this->log('error', 'Invalid embedding format in Python response', [
                'result_structure' => array_keys($result ?? []),
                'data_structure' => isset($result['data']) ? array_keys($result['data']) : 'data key missing',
            ]);
            throw new \RuntimeException('Invalid embedding format returned from Python');
        }

        // Extract the first embedding vector from the response
        $firstEmbedding = $result['data']['embeddings'][0] ?? null;
        if (!$firstEmbedding || !is_array($firstEmbedding)) {
            throw new \RuntimeException('No valid embedding found in Python response');
        }

        // Transform Python's object-like array format to a proper PHP array
        $embeddingArray = [];
        for ($i = 0; $i < count($firstEmbedding); $i++) {
            if (isset($firstEmbedding[(string)$i])) {
                $embeddingArray[] = $firstEmbedding[(string)$i];
            }
        }

        if (empty($embeddingArray)) {
            throw new \RuntimeException('Failed to convert embedding format');
        }

        $this->log('info', 'Embedding generated successfully', [
            'embedding_dimension' => count($embeddingArray),
            'model' => $result['data']['model'] ?? 'unknown'
        ]);

        return [
            'embedding' => $embeddingArray,
            'model' => $result['data']['model'] ?? 'text-embedding-3-small',
            'dimension' => count($embeddingArray)
        ];
    }

    /**
     * Perform semantic search using vector similarity.
     */
    public function vectorSearch(string $query, array $options = []): array
    {
        $this->log('info', 'Performing vector search via Python AI bridge', [
            'query_length' => strlen($query),
            'options' => $options
        ]);

        $result = $this->execute('search_documents', [
            'query' => $query,
            'options' => $options
        ]);

        $this->log('info', 'Vector search completed', [
            'results_count' => count($result['data']['results'] ?? [])
        ]);

        return $result['data'] ?? [];
    }

    /**
     * Handle conversational chat queries using AI.
     */
    public function chat(array $chatData): array
    {
        $this->log('info', 'Processing chat query via Python AI bridge', [
            'session_id' => $chatData['session_id'] ?? null,
            'message_length' => strlen($chatData['message'] ?? ''),
        ]);

        try {
            $result = $this->execute('chat_query', $chatData);

            $this->log('info', 'Chat query completed successfully', [
                'session_id' => $chatData['session_id'] ?? null,
                'response_length' => strlen($result['response'] ?? ''),
            ]);

            return [
                'response' => $result['response'] ?? 'I apologize, but I cannot provide a response at the moment.',
                'fallback' => false,
                'metadata' => $result['metadata'] ?? [],
            ];

        } catch (\Exception $e) {
            $this->log('error', 'Python chat bridge error', [
                'session_id' => $chatData['session_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'response' => "I'm experiencing technical difficulties. Please try your question again in a moment.",
                'fallback' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify that the Python AI environment is properly configured.
     */
    public function testConnection(): array
    {
        $this->log('info', 'Testing Python AI bridge connection');

        try {
            $result = $this->execute('health_check', []);
            $normalized = $this->normalizeHealthCheckResponse($result);
            $this->log('info', 'Python AI bridge health check completed', $normalized);
            return $normalized;

        } catch (\Exception $e) {
            $this->log('error', 'Python AI bridge health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'success' => false,
                'message' => $e->getMessage(),
                'components' => [],
                'config' => [],
                'raw' => null,
            ];
        }
    }

    /**
     * Parse Python output handling mixed content and JSON extraction.
     */
    private function parseOutput(string $output, string $operation): array
    {
        $output = trim($output);
        
        // Truncate output if too long
        if (strlen($output) > $this->maxOutputLength) {
            $this->log('warning', 'Python bridge output truncated', [
                'operation' => $operation,
                'original_length' => strlen($output),
                'max_length' => $this->maxOutputLength,
            ]);
            $output = substr($output, 0, $this->maxOutputLength);
        }

        $this->log('info', 'Python AI bridge output received', [
            'operation' => $operation,
            'output_length' => strlen($output),
        ]);

        // Handle simple JSON response
        $result = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }

        // Handle mixed output with JSON embedded
        $lines = explode("\n", $output);
        $jsonContent = '';
        $foundStart = false;
        $braceCount = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (!$foundStart && str_starts_with($line, '{')) {
                $foundStart = true;
                $jsonContent = $line;
                $braceCount = substr_count($line, '{') - substr_count($line, '}');
                
                if ($braceCount === 0) {
                    break;
                }
            } elseif ($foundStart) {
                $jsonContent .= "\n" . $line;
                $braceCount += substr_count($line, '{') - substr_count($line, '}');
                
                if ($braceCount === 0) {
                    break;
                }
            }
        }

        $result = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('error', 'Failed to decode Python bridge JSON response', [
                'operation' => $operation,
                'json_error' => json_last_error_msg(),
                'raw_output' => $output,
                'json_content' => $jsonContent,
            ]);

            throw new \RuntimeException('Invalid JSON response from Python: ' . json_last_error_msg());
        }

        return $result;
    }

    private function validateScriptPath(): void
    {
        if (empty($this->scriptPath) || !is_file($this->scriptPath) || !is_readable($this->scriptPath)) {
            $this->log('error', 'Python AI bridge script path is invalid', [
                'script_path' => $this->scriptPath,
            ]);

            throw new InvalidArgumentException('Configured Python script path is invalid or unreadable.');
        }
    }

    private function buildProcessEnvironment(): array
    {
        $baseEnvironment = [];

        foreach (array_merge($_SERVER, $_ENV) as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_scalar($value)) {
                $baseEnvironment[$key] = (string) $value;
            }
        }

        $overrides = array_filter([
            'OPENAI_API_KEY' => config('overpass.openai.api_key') ?: env('OPENAI_API_KEY'),
            'OPENAI_ORGANIZATION' => config('overpass.openai.organization') ?: env('OPENAI_ORGANIZATION'),
            'PATH' => $baseEnvironment['PATH'] ?? env('PATH', '/usr/local/bin:/usr/bin:/bin'),
            'PYTHONPATH' => dirname($this->scriptPath) ?: null,
        ], static fn ($value) => $value !== null);

        return array_merge($baseEnvironment, $overrides);
    }

    private function shouldLog(): bool
    {
        return (bool) config('overpass.logging.enabled', true);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }

        $channel = config('overpass.logging.log_channel', 'default');

        if ($channel !== 'default') {
            Log::channel($channel)->log($level, $message, $context);
            return;
        }

        Log::log($level, $message, $context);
    }

    private function normalizeHealthCheckResponse(array $response): array
    {
        $status = $response['status'] ?? Arr::get($response, 'data.status');
        $success = $response['success'] ?? null;

        if ($status === null) {
            if ($success === true) {
                $status = 'ok';
            } elseif ($success === false) {
                $status = 'error';
            }
        }

        $components = $response['components'] ?? Arr::get($response, 'data.components', []);
        if (!is_array($components)) {
            $components = [];
        }

        $config = $response['config'] ?? Arr::get($response, 'data.config', []);
        if (!is_array($config)) {
            $config = [];
        }

        $message = $response['message']
            ?? $response['error']
            ?? Arr::get($response, 'data.message');

        if ($success === null) {
            $success = $status !== null && $status !== 'error';
        }

        return [
            'status' => $status ?? 'unknown',
            'success' => (bool) $success,
            'message' => $message,
            'components' => $components,
            'config' => $config,
            'raw' => $response,
        ];
    }
}