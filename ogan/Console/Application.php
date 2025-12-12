<?php

namespace Ogan\Console;

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🎮 APPLICATION CONSOLE
 * ═══════════════════════════════════════════════════════════════════════
 */
class Application
{
    private array $commands = [];
    
    public function __construct(
        private string $name,
        private string $version
    ) {}
    
    /**
     * Enregistrer une commande
     */
    public function addCommand(string $name, callable $handler, string $description = ''): void
    {
        $this->commands[$name] = [
            'handler' => $handler,
            'description' => $description
        ];
    }
    
    /**
     * Exécuter l'application
     */
    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? null;
        $args = array_slice($argv, 2);
        
        // Liste des commandes si aucune commande spécifiée
        if (!$commandName) {
            $this->showHelp();
            return 0;
        }
        
        // Exécuter la commande
        if (isset($this->commands[$commandName])) {
            try {
                $handler = $this->commands[$commandName]['handler'];
                return $handler($args) ?? 0;
            } catch (\Exception $e) {
                echo "❌ Erreur : " . $e->getMessage() . "\n";
                return 1;
            }
        }
        
        echo "❌ Commande inconnue : {$commandName}\n\n";
        $this->showHelp();
        return 1;
    }
    
    /**
     * Afficher l'aide
     */
    private function showHelp(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════╗\n";
        echo "║  {$this->name} v{$this->version}                                      ║\n";
        echo "╚══════════════════════════════════════════════════════════════╝\n\n";
        
        echo "Usage:\n";
        echo "  php bin/console [command] [options]\n\n";
        
        if (empty($this->commands)) {
            echo "Aucune commande disponible.\n";
            return;
        }
        
        echo "Commandes disponibles:\n\n";
        
        $maxLength = max(array_map('strlen', array_keys($this->commands)));
        
        foreach ($this->commands as $name => $command) {
            $padding = str_repeat(' ', $maxLength - strlen($name) + 2);
            echo "  \033[32m{$name}\033[0m{$padding}{$command['description']}\n";
        }
        
        echo "\n";
    }
}
