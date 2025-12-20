<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🌱 SEEDER BASE CLASS
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Classe de base pour tous les seeders.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Database;

abstract class Seeder
{
    /**
     * Exécute le seeder
     */
    abstract public function run(): void;

    /**
     * Affiche un message d'information
     */
    protected function info(string $message): void
    {
        echo "\033[34mℹ️  {$message}\033[0m\n";
    }

    /**
     * Affiche un message de succès
     */
    protected function success(string $message): void
    {
        echo "\033[32m✅ {$message}\033[0m\n";
    }

    /**
     * Affiche un message d'erreur
     */
    protected function error(string $message): void
    {
        echo "\033[31m❌ {$message}\033[0m\n";
    }

    /**
     * Affiche un avertissement
     */
    protected function warning(string $message): void
    {
        echo "\033[33m⚠️  {$message}\033[0m\n";
    }

    /**
     * Crée plusieurs enregistrements avec un factory-like pattern
     */
    protected function create(string $modelClass, array $data, int $count = 1): array
    {
        $created = [];
        
        for ($i = 0; $i < $count; $i++) {
            $model = new $modelClass();
            
            foreach ($data as $key => $value) {
                // Si la valeur est un callable, l'exécuter avec l'index
                if (is_callable($value)) {
                    $value = $value($i);
                }
                
                $setter = 'set' . ucfirst($key);
                if (method_exists($model, $setter)) {
                    $model->$setter($value);
                }
            }
            
            $model->save();
            $created[] = $model;
        }
        
        return $created;
    }
}
