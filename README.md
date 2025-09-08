# Overpass

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bmadigan/overpass.svg?style=flat-square)](https://packagist.org/packages/bmadigan/overpass)
[![Total Downloads](https://img.shields.io/packagist/dt/bmadigan/overpass.svg?style=flat-square)](https://packagist.org/packages/bmadigan/overpass)

A robust Laravel package for integrating Python AI capabilities through a secure subprocess bridge. Instead of reimplementing complex AI libraries in PHP, this package provides a clean interface to delegate AI operations to Python's rich ecosystem (OpenAI, LangChain, scikit-learn, etc.).

## 🎬 Demo Application

Check out **[Ask-My-Doc](https://github.com/bmadigan/ask-my-doc)** - a sophisticated document Q&A system built with Overpass that showcases real-world AI integration:

[![Ask-My-Doc Demo](https://github.com/bmadigan/ask-my-doc/raw/main/public/screenshot.png)](https://github.com/bmadigan/ask-my-doc)

**Ask-My-Doc** demonstrates Overpass in action with:
- 📄 Document ingestion and intelligent chunking
- 🔍 Semantic search using OpenAI embeddings
- 💬 AI-powered Q&A with source citations
- ⚡ Real-time Python bridge status monitoring
- 🎨 Beautiful dark-themed UI inspired by Linear

> **Note**: This package is **not affiliated** with the existing [decodelabs/overpass](https://github.com/decodelabs/overpass) package, which provides a PHP bridge to Node.js. Our "Overpass" package serves a different purpose - bridging between PHP and Python for AI operations.

## 🚀 Features

- **🐍 Python Integration**: Seamlessly execute Python AI scripts from Laravel
- **🔒 Secure**: API keys passed via environment variables, never in command lines
- **⚡ Async Ready**: Built for Laravel queues and background processing
- **🛡️ Robust Error Handling**: Graceful degradation and comprehensive logging
- **🧪 Production Tested**: Battle-tested patterns for reliability
- **🎯 Laravel Native**: Follows Laravel conventions with facades, service providers, and Artisan commands

## 📦 Installation

You can install the package via composer:

```bash
composer require bmadigan/overpass
```

Install the package with example Python scripts:

```bash
php artisan overpass:install --with-examples
```

Publish the config file:

```bash
php artisan vendor:publish --tag="overpass-config"
```

## ⚙️ Configuration

Add your OpenAI API key to your `.env` file:

```env
OPENAI_API_KEY=your-openai-api-key
OVERPASS_SCRIPT_PATH=/path/to/your/python/script.py
```

Configure the package in `config/overpass.php`:

```php
return [
    'script_path' => env('OVERPASS_SCRIPT_PATH', base_path('overpass-ai/main.py')),
    'timeout' => env('OVERPASS_TIMEOUT', 90),
    'max_output_length' => env('OVERPASS_MAX_OUTPUT', 10000),
    // ... more options
];
```

## 🎯 Usage

### Basic Usage

```php
use Bmadigan\Overpass\Facades\Overpass;

// Test the connection
$healthCheck = Overpass::testConnection();

// Generate embeddings
$embedding = Overpass::generateEmbedding('Hello, world!');

// Execute custom operations
$result = Overpass::execute('custom_operation', ['data' => 'value']);
```

### Chat Operations

```php
$response = Overpass::chat([
    'message' => 'What is machine learning?',
    'session_id' => 'user-123',
    'context' => ['previous' => 'conversation']
]);

echo $response['response']; // AI-generated response
```

### Vector Search

```php
$results = Overpass::vectorSearch('machine learning concepts', [
    'limit' => 10,
    'threshold' => 0.8
]);
```

### Queue Integration

```php
use Bmadigan\Overpass\Services\PythonAiBridge;

// In a Laravel job
class ProcessAiTask implements ShouldQueue
{
    public function handle(PythonAiBridge $bridge)
    {
        $result = $bridge->analyzeData($this->data);
        // Process result...
    }
}
```

## 🐍 Python Script Structure

Your Python script should follow this pattern:

```python
import sys
import json

def health_check(data):
    return {
        'success': True,
        'data': {'status': 'healthy'}
    }

def analyze_data(data):
    # Your AI logic here
    return {
        'success': True,
        'data': {'result': 'analysis complete'}
    }

def main():
    operation = sys.argv[1]
    data = json.loads(sys.argv[2]) if len(sys.argv) > 2 else {}

    operations = {
        'health_check': health_check,
        'analyze_data': analyze_data,
    }

    if operation in operations:
        result = operations[operation](data)
        print(json.dumps(result))
    else:
        print(json.dumps({'success': False, 'error': 'Unknown operation'}))

if __name__ == '__main__':
    main()
```

## 🧪 Testing

```bash
# Test the bridge connection
php artisan overpass:test

# Run package tests
composer test

# Run tests with coverage
composer test-coverage
```

## 📚 Advanced Usage

### Custom Operations

```php
// Define custom operations in your Python script
$result = Overpass::execute('sentiment_analysis', [
    'text' => 'This product is amazing!',
    'options' => ['return_confidence' => true]
]);
```

### Error Handling

```php
try {
    $result = Overpass::generateEmbedding($text);
} catch (\Exception $e) {
    Log::error('Embedding generation failed', ['error' => $e->getMessage()]);
    // Implement fallback logic
}
```

### Dependency Injection

```php
class MyService
{
    public function __construct(
        private PythonAiBridge $bridge
    ) {}

    public function processData(array $data): array
    {
        return $this->bridge->execute('process', $data);
    }
}
```

## 🔧 Artisan Commands

```bash
# Install and setup the package
php artisan overpass:install --with-examples

# Test the bridge connection
php artisan overpass:test --verbose

# Check configuration
php artisan overpass:test --timeout=60
```

## 🚨 Troubleshooting

**Connection Failures:**
- Ensure Python 3 is installed and accessible
- Check that your Python script path is correct
- Verify Python dependencies are installed
- Test your OpenAI API key

**Performance Issues:**
- Increase timeout for complex operations
- Use Laravel queues for long-running tasks
- Consider output length limits

**Memory Issues:**
- Set appropriate `max_output_length` in config
- Use streaming for large datasets
- Monitor Python process memory usage

## 📊 Performance Considerations

- **Timeouts**: Set appropriate timeouts based on operation complexity
- **Memory**: Monitor both PHP and Python memory usage
- **Queues**: Use Laravel queues for expensive AI operations
- **Caching**: Cache embedding results and AI responses when appropriate

## 🔒 Security

- API keys are passed via environment variables, never command line arguments
- All input data is JSON-encoded to prevent injection attacks
- Process isolation ensures Python failures don't crash Laravel
- Comprehensive logging for audit trails

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🐛 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 📄 Credits

- [Your Name](https://github.com/your-username)
- [All Contributors](../../contributors)

## 📜 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
