<?php

/**
 * Commandes Tailwind
 */
function registerTailwindCommands($app) {
    $app->addCommand('tailwind:init', function($args) {
        echo "🎨 Initialisation de Tailwind CSS...\n\n";
        
        $binDir = dirname(__DIR__);
        $projectRoot = dirname($binDir);
        $tailwindBinary = $binDir . '/tailwindcss';
        
        // Détecter l'OS
        $os = PHP_OS_FAMILY;
        $arch = php_uname('m');
        
        if ($os === 'Linux') {
            $platform = $arch === 'aarch64' ? 'linux-arm64' : 'linux-x64';
        } elseif ($os === 'Darwin') {
            $platform = $arch === 'arm64' ? 'macos-arm64' : 'macos-x64';
        } elseif ($os === 'Windows') {
            $platform = 'windows-x64.exe';
            $tailwindBinary .= '.exe';
        } else {
            echo "❌ OS non supporté : {$os}\n";
            return 1;
        }
        
        // Télécharger le binaire
        if (!file_exists($tailwindBinary)) {
            echo "📥 Téléchargement du binaire Tailwind CSS ({$platform})...\n";
            $url = "https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-{$platform}";
            
            $binary = file_get_contents($url);
            if ($binary === false) {
                echo "❌ Échec du téléchargement\n";
                return 1;
            }
            
            file_put_contents($tailwindBinary, $binary);
            chmod($tailwindBinary, 0755);
            echo "✅ Binaire téléchargé\n\n";
        } else {
            echo "✅ Binaire déjà présent\n\n";
        }
        
        // Créer tailwind.config.js
        $configPath = $projectRoot . '/tailwind.config.js';
        if (!file_exists($configPath)) {
            $config = <<<'JS'
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.{html,php,ogan}",
    "./src/**/*.php",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
JS;
            file_put_contents($configPath, $config);
            echo "✅ tailwind.config.js créé\n";
        } else {
            echo "✅ tailwind.config.js déjà présent\n";
        }
        
        // Créer assets/css/app.css
        $assetsDir = $projectRoot . '/assets/css';
        if (!is_dir($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }
        
        $appCssPath = $assetsDir . '/app.css';
        if (!file_exists($appCssPath)) {
            $appCss = <<<'CSS'
@import "tailwindcss";

/* Vos styles personnalisés ici */
CSS;
            file_put_contents($appCssPath, $appCss);
            echo "✅ assets/css/app.css créé\n";
        } else {
            echo "✅ assets/css/app.css déjà présent\n";
        }
        
        echo "\n✅ Tailwind CSS initialisé !\n";
        echo "💡 Lancez : php bin/console tailwind:build --watch\n\n";
        
        return 0;
    }, 'Télécharge le binaire Tailwind et crée la configuration');

    $app->addCommand('tailwind:build', function($args) {
        $binDir = dirname(__DIR__);
        $projectRoot = dirname($binDir);
        $tailwindBinary = $binDir . '/tailwindcss';
        
        if (PHP_OS_FAMILY === 'Windows') {
            $tailwindBinary .= '.exe';
        }
        
        if (!file_exists($tailwindBinary)) {
            echo "❌ Binaire Tailwind non trouvé. Lancez : php bin/console tailwind:init\n";
            return 1;
        }
        
        $input = $projectRoot . '/assets/css/app.css';
        $output = $projectRoot . '/public/assets/css/app.css';
        
        // Options
        $watch = in_array('--watch', $args) || in_array('-w', $args);
        $minify = in_array('--minify', $args) || in_array('-m', $args);
        
        $cmd = escapeshellarg($tailwindBinary) . ' -i ' . escapeshellarg($input) . ' -o ' . escapeshellarg($output);
        
        if ($watch) {
            $cmd .= ' --watch';
            echo "👀 Mode watch activé - Ctrl+C pour arrêter\n\n";
        }
        
        if ($minify) {
            $cmd .= ' --minify';
            echo "🗜️  Minification activée\n\n";
        }
        
        echo "🎨 Compilation de Tailwind CSS...\n";
        passthru($cmd, $exitCode);
        
        return $exitCode;
    }, 'Compile Tailwind CSS (--watch, --minify)');
}
