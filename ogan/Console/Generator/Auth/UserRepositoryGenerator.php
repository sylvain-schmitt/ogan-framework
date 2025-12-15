<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 📦 USER REPOSITORY GENERATOR
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Generator\Auth;

use Ogan\Console\Generator\AbstractGenerator;

class UserRepositoryGenerator extends AbstractGenerator
{
    public function generate(string $projectRoot, bool $force = false): array
    {
        $generated = [];
        $skipped = [];

        $path = $projectRoot . '/src/Repository/UserRepository.php';
        $this->ensureDirectory(dirname($path));

        if (!$this->fileExists($path) || $force) {
            $this->writeFile($path, $this->getTemplate());
            $generated[] = 'src/Repository/UserRepository.php';
        } else {
            $skipped[] = 'src/Repository/UserRepository.php (existe déjà)';
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    private function getTemplate(): string
    {
        return <<<'PHP'
<?php

namespace App\Repository;

use App\Model\User;
use Ogan\Database\AbstractRepository;

class UserRepository extends AbstractRepository
{
    protected string $entityClass = User::class;
    protected string $table = 'users';

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }
}
PHP;
    }
}
