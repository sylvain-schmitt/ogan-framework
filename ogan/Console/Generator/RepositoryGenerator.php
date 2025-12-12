<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 REPOSITORY GENERATOR - Générateur de repositories
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Génère automatiquement des repositories pour les modèles.
 * 
 * UTILISATION :
 * -------------
 * 
 * $generator = new RepositoryGenerator();
 * $generator->generate('User', 'src/Repository', 'App\\Model\\User');
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator;

class RepositoryGenerator extends AbstractGenerator
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER UN REPOSITORY
     * ═══════════════════════════════════════════════════════════════════
     * 
     * @param string $name Nom du repository (ex: "User" ou "UserRepository")
     * @param string $repositoriesPath Chemin vers le dossier des repositories
     * @param string $modelClass Classe complète du modèle (ex: "App\\Model\\User")
     * @param string $tableName Nom de la table (optionnel, déduit du modèle si non fourni)
     * @param bool $force Forcer la création même si le fichier existe
     * @return string Chemin du fichier créé
     * 
     * ═══════════════════════════════════════════════════════════════════
     */
    public function generate(string $name, string $repositoriesPath, string $modelClass, ?string $tableName = null, bool $force = false): string
    {
        // Normaliser le nom
        $className = $this->toClassName($name);
        if (!str_ends_with($className, 'Repository')) {
            $className .= 'Repository';
        }

        $filename = $this->toFileName($className);
        $filepath = rtrim($repositoriesPath, '/') . '/' . $filename;

        // Vérifier si le fichier existe
        if ($this->fileExists($filepath) && !$force) {
            throw new \RuntimeException("Le repository existe déjà : {$filename}");
        }

        // Créer le dossier s'il n'existe pas
        $this->ensureDirectory($repositoriesPath);

        // Déduire le nom de la table si non fourni
        if ($tableName === null) {
            $tableName = $this->deduceTableName($name);
        }

        // Générer le contenu
        $content = $this->generateRepositoryContent($className, $modelClass, $tableName);

        // Écrire le fichier
        $this->writeFile($filepath, $content);

        return $filepath;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * DÉDUIRE LE NOM DE LA TABLE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function deduceTableName(string $name): string
    {
        // Enlever "Repository" si présent
        $name = preg_replace('/Repository$/i', '', $name);
        
        // Convertir en snake_case et singulier
        // Exemple: "User" -> "user", "PostCategory" -> "post_category"
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $name);
        return strtolower($name);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * GÉNÉRER LE CONTENU DU REPOSITORY
     * ═══════════════════════════════════════════════════════════════════
     */
    private function generateRepositoryContent(string $className, string $modelClass, string $tableName): string
    {
        $modelName = basename(str_replace('\\', '/', $modelClass));

        return <<<PHP
<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📚 {$className} - Repository {$modelName}
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Ce repository a été généré automatiquement.
 * 
 * Le Repository Pattern sépare la logique métier de la persistance.
 * Utilisez ce repository pour des requêtes complexes ou pour
 * séparer la logique de requête de la logique métier.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace App\\Repository;

use Ogan\\Database\\AbstractRepository;
use {$modelClass};

class {$className} extends AbstractRepository
{
    /**
     * @var string Classe de l'entité
     */
    protected string \$entityClass = {$modelName}::class;

    /**
     * @var string Nom de la table
     */
    protected string \$table = '{$tableName}';

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TROUVER UN {$modelName} PAR EMAIL (Exemple)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Décommentez et adaptez selon vos besoins :
     * 
     * public function findByEmail(string \$email): ?{$modelName}
     * {
     *     return \$this->findOneBy(['email' => \$email]);
     * }
     */

    /**
     * ═══════════════════════════════════════════════════════════════════
     * TROUVER LES {$modelName}S ACTIFS (Exemple)
     * ═══════════════════════════════════════════════════════════════════
     * 
     * Décommentez et adaptez selon vos besoins :
     * 
     * public function findActive(): array
     * {
     *     return \$this->findBy(['active' => true]);
     * }
     */
}

PHP;
    }
}

