<?php

declare(strict_types=1);

namespace Bmadigan\Overpass\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OverpassInstallCommand extends Command
{
    public $signature = 'overpass:install
                        {--force : Overwrite existing files}
                        {--with-examples : Include example Python scripts}';

    public $description = 'Install and set up the Overpass package';

    public function handle(): int
    {
        $this->info('🚀 Installing Overpass...');
        $this->newLine();

        // Publish configuration
        $this->line('📋 Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'overpass-config',
            '--force' => $this->option('force'),
        ]);

        // Create Python directory structure
        $this->line('📁 Creating Python directory structure...');
        $pythonDir = base_path('overpass-ai');
        
        if (!File::exists($pythonDir)) {
            File::makeDirectory($pythonDir, 0755, true);
            $this->line("  Created: {$pythonDir}");
        }

        // Create example Python script if requested
        if ($this->option('with-examples')) {
            $this->line('🐍 Creating example Python scripts...');
            $this->createExamplePythonScript($pythonDir);
            $this->createRequirementsFile($pythonDir);
        }

        // Check Python environment
        $this->line('🔍 Checking Python environment...');
        $this->checkPythonEnvironment();

        $this->newLine();
        $this->info('✅ Installation completed successfully!');
        $this->newLine();

        $this->line('🎯 Next Steps:');
        $this->line('  1. Configure your OpenAI API key in .env:');
        $this->line('     OPENAI_API_KEY=your-api-key-here');
        $this->newLine();
        
        if ($this->option('with-examples')) {
            $this->line('  2. Install Python dependencies:');
            $this->line('     cd overpass-ai && pip install -r requirements.txt');
            $this->newLine();
        }
        
        $this->line('  3. Test the bridge connection:');
        $this->line('     php artisan overpass:test');
        $this->newLine();

        return self::SUCCESS;
    }

    private function createExamplePythonScript(string $pythonDir): void
    {
        $scriptPath = $pythonDir . '/main.py';
        
        if (File::exists($scriptPath) && !$this->option('force')) {
            $this->line("  Skipped: {$scriptPath} (already exists)");
            return;
        }

        $scriptContent = <<<'PYTHON'
#!/usr/bin/env python3
"""
Example Python AI Bridge Script for Laravel

This is a basic example showing how to structure your Python script
to work with the Overpass Laravel package.
"""

import sys
import json
import os
from typing import Dict, Any

def health_check(data: Dict[str, Any]) -> Dict[str, Any]:
    """Basic health check endpoint"""
    return {
        'success': True,
        'data': {
            'status': 'healthy',
            'python_version': sys.version,
            'components': {
                'python': {'status': 'healthy'},
                'environment': {'status': 'healthy'}
            },
            'config': {
                'has_openai_key': bool(os.getenv('OPENAI_API_KEY')),
            }
        }
    }

def analyze_data(data: Dict[str, Any]) -> Dict[str, Any]:
    """Example data analysis endpoint"""
    return {
        'success': True,
        'data': {
            'analysis': f"Analyzed {len(data)} data points",
            'summary': 'This is an example analysis result',
            'confidence': 0.85
        }
    }

def create_embeddings(data: Dict[str, Any]) -> Dict[str, Any]:
    """Example embedding generation (requires OpenAI)"""
    texts = data.get('texts', [])
    
    # This is a placeholder - you would integrate with OpenAI here
    mock_embeddings = []
    for text in texts:
        # Mock embedding (1536 dimensions like OpenAI's text-embedding-3-small)
        embedding = [0.1] * 1536
        mock_embeddings.append({str(i): val for i, val in enumerate(embedding)})
    
    return {
        'success': True,
        'data': {
            'embeddings': mock_embeddings,
            'model': 'mock-embedding-model',
            'usage': {'total_tokens': sum(len(text.split()) for text in texts)}
        }
    }

def main():
    """Main entry point for the bridge"""
    if len(sys.argv) < 2:
        print(json.dumps({'success': False, 'error': 'No operation specified'}))
        sys.exit(1)
    
    operation = sys.argv[1]
    data = {}
    
    if len(sys.argv) > 2:
        try:
            data = json.loads(sys.argv[2])
        except json.JSONDecodeError as e:
            print(json.dumps({'success': False, 'error': f'Invalid JSON: {e}'}))
            sys.exit(1)
    
    operations = {
        'health_check': health_check,
        'analyze_data': analyze_data,
        'create_embeddings': create_embeddings,
    }
    
    if operation not in operations:
        print(json.dumps({'success': False, 'error': f'Unknown operation: {operation}'}))
        sys.exit(1)
    
    try:
        result = operations[operation](data)
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({'success': False, 'error': str(e)}))
        sys.exit(1)

if __name__ == '__main__':
    main()
PYTHON;

        File::put($scriptPath, $scriptContent);
        $this->line("  Created: {$scriptPath}");
    }

    private function createRequirementsFile(string $pythonDir): void
    {
        $requirementsPath = $pythonDir . '/requirements.txt';
        
        if (File::exists($requirementsPath) && !$this->option('force')) {
            $this->line("  Skipped: {$requirementsPath} (already exists)");
            return;
        }

        $requirements = <<<'REQUIREMENTS'
# Overpass Laravel Package - Example Requirements
# Install with: pip install -r requirements.txt

# Core dependencies
openai>=1.0.0
python-dotenv>=1.0.0

# Optional: Enhanced AI capabilities
# langchain>=0.1.0
# langchain-openai>=0.1.0
# numpy>=1.24.0
# pandas>=2.0.0

# Optional: Vector operations
# faiss-cpu>=1.7.4
# pgvector>=0.2.0
# psycopg2-binary>=2.9.0

# Development and testing
pytest>=7.4.0
black>=23.7.0
REQUIREMENTS;

        File::put($requirementsPath, $requirements);
        $this->line("  Created: {$requirementsPath}");
    }

    private function checkPythonEnvironment(): void
    {
        // Check if Python is available
        $pythonVersionCommand = 'python3 --version 2>&1';
        $pythonVersion = shell_exec($pythonVersionCommand);
        
        if ($pythonVersion) {
            $this->line("  ✅ Python found: " . trim($pythonVersion));
        } else {
            $this->warn("  ⚠️  Python 3 not found in PATH");
            $this->line("     Make sure Python 3 is installed and accessible");
        }

        // Check if pip is available
        $pipVersionCommand = 'pip3 --version 2>&1';
        $pipVersion = shell_exec($pipVersionCommand);
        
        if ($pipVersion) {
            $this->line("  ✅ pip found: " . trim(explode(' ', $pipVersion)[1] ?? ''));
        } else {
            $this->warn("  ⚠️  pip3 not found in PATH");
        }
    }
}