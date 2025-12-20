<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🌱 SEEDER GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Génère des fichiers seeder pour peupler la base de données.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator;

class SeederGenerator extends AbstractGenerator
{
    private string $seederName;
    private string $seederClass;

    public function generate(string $projectRoot, bool $force = false): array
    {
        throw new \InvalidArgumentException('Use generateSeeder() instead');
    }

    /**
     * Génère un fichier seeder
     */
    public function generateSeeder(string $projectRoot, string $name, bool $force = false): array
    {
        $this->seederName = ucfirst($name);
        $this->seederClass = $this->seederName . 'Seeder';

        $generated = [];
        $skipped = [];

        // Créer le répertoire seeders
        $seedersDir = $projectRoot . '/database/seeders';
        $this->ensureDirectory($seedersDir);

        $seederPath = $seedersDir . '/' . $this->seederClass . '.php';

        if (!$this->fileExists($seederPath) || $force) {
            $this->writeFile($seederPath, $this->getSeederContent());
            $generated[] = "database/seeders/{$this->seederClass}.php";
        } else {
            $skipped[] = "database/seeders/{$this->seederClass}.php (existe déjà)";
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    /**
     * Génère le contenu du seeder
     */
    private function getSeederContent(): string
    {
        $modelVar = lcfirst($this->seederName);

        return <<<PHP
<?php

namespace Database\\Seeders;

use App\\Model\\{$this->seederName};
use Ogan\\Database\\Seeder;

/**
 * Seeder pour {$this->seederName}
 * 
 * Exécuter : php bin/console db:seed {$this->seederClass}
 */
class {$this->seederClass} extends Seeder
{
    /**
     * Exécute le seeder
     */
    public function run(): void
    {
        \$this->info("Seeding {$this->seederName}s...");

        // Exemple de création d'enregistrements
        for (\$i = 1; \$i <= 10; \$i++) {
            \${$modelVar} = new {$this->seederName}();
            // Configurez les propriétés ici
            // \${$modelVar}->setName("Sample {\$i}");
            \${$modelVar}->save();
        }

        \$this->success("10 {$this->seederName}s créés.");
    }
}
PHP;
    }
}
