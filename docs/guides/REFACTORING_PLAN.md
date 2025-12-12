# 📋 Plan de Refactorisation - TemplateCompiler

## 🎯 Objectifs

1. **Séparer les responsabilités** (Single Responsibility Principle)
2. **Faciliter les tests unitaires** de chaque composant
3. **Améliorer la maintenabilité** et la lisibilité
4. **Permettre l'extension** sans modification (Open/Closed Principle)
5. **Faciliter le débogage** en isolant les problèmes

---

## 📊 Analyse des Responsabilités Actuelles

Le fichier `TemplateCompiler.php` (2441 lignes) gère actuellement :

1. **Orchestration** : Compilation globale, gestion du cache
2. **Structures de contrôle** : `if`, `foreach`, `while`, `for`, etc.
3. **Transformation de syntaxe** : Point (`.`) → Flèche (`->`)
4. **Transformation de variables** : Ajout de `$` aux variables
5. **Transformation dans les arguments** : Variables dans les tableaux/arguments
6. **Transformation dans les conditions** : Variables dans les conditions
7. **Protection des placeholders** : Gestion des chaînes et variables protégées
8. **Extraction d'arguments** : Parsing des arguments de fonctions
9. **Compilation d'expressions** : Transformation `{{ expression }}` → PHP

---

## 🏗️ Architecture Proposée

### Structure des Dossiers

```
ogan/View/Compiler/
├── TemplateCompiler.php          # Orchestrateur principal (réduit)
├── CompilerInterface.php         # Interface pour les compilateurs
├── Expression/
│   ├── ExpressionCompiler.php   # Compile les expressions {{ }}
│   ├── ExpressionParser.php      # Parse une expression
│   └── ExpressionTransformer.php # Transforme les expressions
├── Variable/
│   ├── VariableTransformer.php  # Transforme les variables (ajout de $)
│   ├── VariableProtector.php    # Protège les variables PHP existantes
│   └── VariableInContextTransformer.php # Variables dans contextes spécifiques
├── Syntax/
│   ├── DotSyntaxTransformer.php # Point → Flèche
│   └── MethodDetector.php       # Détecte les méthodes
├── Control/
│   ├── ControlStructureCompiler.php # Compile if, foreach, etc.
│   └── ConditionTransformer.php     # Transforme les conditions
├── Utility/
│   ├── PlaceholderManager.php   # Gère les placeholders
│   ├── ArgumentExtractor.php   # Extrait les arguments
│   ├── StringProtector.php      # Protège les chaînes
│   └── PhpKeywordChecker.php    # Vérifie les mots-clés PHP
└── Exception/
    └── CompilationException.php # Exceptions spécifiques
```

---

## 📝 Détail des Classes

### 1. **TemplateCompiler** (Orchestrateur)
**Responsabilité unique** : Orchestrer la compilation

```php
class TemplateCompiler
{
    private ControlStructureCompiler $controlCompiler;
    private ExpressionCompiler $expressionCompiler;
    private CacheManager $cacheManager;
    
    public function compile(string $templatePath): string
    {
        // 1. Vérifier le cache
        // 2. Lire le template
        // 3. Compiler les structures de contrôle
        // 4. Compiler les expressions
        // 5. Sauvegarder le résultat
    }
}
```

### 2. **ControlStructureCompiler**
**Responsabilité** : Compiler `{{ if }}`, `{{ foreach }}`, etc.

```php
class ControlStructureCompiler
{
    private ConditionTransformer $conditionTransformer;
    
    public function compile(string $content): string
    {
        // Transforme {{ if (condition) }} → <?php if (condition): ?>
    }
}
```

### 3. **ExpressionCompiler**
**Responsabilité** : Compiler les expressions `{{ expression }}`

```php
class ExpressionCompiler
{
    private ExpressionParser $parser;
    private ExpressionTransformer $transformer;
    
    public function compile(string $content): string
    {
        // Trouve {{ ... }} et les compile
    }
}
```

### 4. **ExpressionParser**
**Responsabilité** : Parser une expression unique

```php
class ExpressionParser
{
    private VariableTransformer $variableTransformer;
    private DotSyntaxTransformer $syntaxTransformer;
    private ArgumentExtractor $argumentExtractor;
    
    public function parse(string $expression): string
    {
        // Parse une expression et la transforme en PHP
    }
}
```

### 5. **VariableTransformer**
**Responsabilité** : Transformer les variables (ajout de `$`)

```php
class VariableTransformer
{
    private VariableProtector $protector;
    private PhpKeywordChecker $keywordChecker;
    
    public function transform(string $expression): string
    {
        // Transforme user → $user
        // Gère les ternaires, les chaînages, etc.
    }
}
```

### 6. **VariableProtector**
**Responsabilité** : Protéger les variables PHP existantes

```php
class VariableProtector
{
    private PlaceholderManager $placeholderManager;
    
    public function protect(string $expression): string
    {
        // Protège $user → ##VAR_PARSE_X##
        // Évite la double transformation
    }
    
    public function restore(string $expression): string
    {
        // Restaure les placeholders
    }
}
```

### 7. **DotSyntaxTransformer**
**Responsabilité** : Transformer `.` en `->`

```php
class DotSyntaxTransformer
{
    private MethodDetector $methodDetector;
    private PlaceholderManager $placeholderManager;
    
    public function transform(string $expression): string
    {
        // user.getId → user->getId()
        // user.name → user->name
    }
}
```

### 8. **PlaceholderManager**
**Responsabilité** : Gérer les placeholders (chaînes, variables)

```php
class PlaceholderManager
{
    public function protectString(string $content, string $string): string
    public function protectVariable(string $content, string $variable): string
    public function restore(string $content): string
}
```

### 9. **ArgumentExtractor**
**Responsabilité** : Extraire les arguments de fonctions

```php
class ArgumentExtractor
{
    public function extract(string $expression): ?array
    {
        // Extrait les arguments de function(arg1, arg2)
        // Gère les parenthèses imbriquées, chaînes, etc.
    }
}
```

### 10. **ConditionTransformer**
**Responsabilité** : Transformer les conditions

```php
class ConditionTransformer
{
    private VariableTransformer $variableTransformer;
    private DotSyntaxTransformer $syntaxTransformer;
    
    public function transform(string $condition): string
    {
        // Transforme les variables dans les conditions
    }
}
```

---

## 🔄 Flux de Compilation

```
TemplateCompiler::compile()
    ↓
ControlStructureCompiler::compile()
    ↓ (transforme {{ if }}, {{ foreach }}, etc.)
    ↓
ExpressionCompiler::compile()
    ↓ (trouve {{ expression }})
    ↓
ExpressionParser::parse()
    ↓
    ├─→ DotSyntaxTransformer::transform()
    ├─→ VariableProtector::protect()
    ├─→ VariableTransformer::transform()
    ├─→ ArgumentExtractor::extract()
    └─→ VariableProtector::restore()
```

---

## ✅ Avantages de cette Architecture

### 1. **Single Responsibility Principle (SRP)**
- Chaque classe a une seule responsabilité claire
- Facilite la compréhension et la maintenance

### 2. **Open/Closed Principle (OCP)**
- On peut ajouter de nouveaux transformers sans modifier le code existant
- Extension via interfaces

### 3. **Liskov Substitution Principle (LSP)**
- Les transformers implémentent des interfaces communes
- Substitution possible

### 4. **Interface Segregation Principle (ISP)**
- Interfaces spécifiques pour chaque type de transformation
- Pas de dépendances inutiles

### 5. **Dependency Inversion Principle (DIP)**
- Dépendances via interfaces/abstractions
- Injection de dépendances

---

## 🧪 Tests Unitaires

Chaque classe peut être testée indépendamment :

```php
// Test VariableTransformer
$transformer = new VariableTransformer(...);
$this->assertEquals('$user', $transformer->transform('user'));

// Test DotSyntaxTransformer
$transformer = new DotSyntaxTransformer(...);
$this->assertEquals('user->getId()', $transformer->transform('user.getId'));

// Test ExpressionParser
$parser = new ExpressionParser(...);
$this->assertEquals('$user->getName()', $parser->parse('user.getName()'));
```

---

## 📋 Plan d'Implémentation (Étapes)

### Phase 1 : Infrastructure
1. ✅ Créer la structure de dossiers
2. ✅ Créer les interfaces de base
3. ✅ Créer `PlaceholderManager` (utilitaire réutilisable)
4. ✅ Créer `PhpKeywordChecker` (utilitaire réutilisable)

### Phase 2 : Transformers de Base
5. ✅ Créer `StringProtector`
6. ✅ Créer `VariableProtector`
7. ✅ Créer `DotSyntaxTransformer`
8. ✅ Créer `MethodDetector`

### Phase 3 : Transformers de Variables
9. ✅ Créer `VariableTransformer`
10. ✅ Créer `VariableInContextTransformer` (pour arguments, conditions)

### Phase 4 : Parsing
11. ✅ Créer `ArgumentExtractor`
12. ✅ Créer `ExpressionParser`
13. ✅ Créer `ExpressionTransformer`

### Phase 5 : Compilation
14. ✅ Créer `ConditionTransformer`
15. ✅ Créer `ControlStructureCompiler`
16. ✅ Créer `ExpressionCompiler`

### Phase 6 : Orchestration
17. ✅ Refactoriser `TemplateCompiler` pour utiliser les nouvelles classes
18. ✅ Tests d'intégration
19. ✅ Migration progressive (garder l'ancien code en commentaire)

### Phase 7 : Nettoyage
20. ✅ Supprimer l'ancien code
21. ✅ Documentation
22. ✅ Tests finaux

---

## 🚨 Points d'Attention

1. **Rétrocompatibilité** : Garder la même API publique de `TemplateCompiler`
2. **Performance** : Vérifier que la refactorisation n'impacte pas les performances
3. **Tests** : S'assurer que tous les cas de test passent
4. **Migration progressive** : Implémenter classe par classe et tester

---

## 📊 Métriques de Succès

- ✅ Réduction de la taille de `TemplateCompiler` : < 200 lignes
- ✅ Chaque classe : < 300 lignes
- ✅ Couverture de tests : > 80%
- ✅ Temps de compilation : identique ou meilleur
- ✅ Facilité de débogage : améliorée

---

## 🔍 Exemple de Code Refactorisé

### Avant (2441 lignes dans un seul fichier)
```php
class TemplateCompiler {
    private function parseExpression(string $expression): string {
        // 800 lignes de code complexe
    }
}
```

### Après (modulaire)
```php
class TemplateCompiler {
    private ExpressionCompiler $expressionCompiler;
    
    public function compile(string $templatePath): string {
        $content = file_get_contents($templatePath);
        $content = $this->controlCompiler->compile($content);
        $content = $this->expressionCompiler->compile($content);
        return $content;
    }
}

class ExpressionParser {
    public function parse(string $expression): string {
        $expression = $this->syntaxTransformer->transform($expression);
        $expression = $this->variableProtector->protect($expression);
        $expression = $this->variableTransformer->transform($expression);
        $expression = $this->variableProtector->restore($expression);
        return $expression;
    }
}
```

---

## 🎯 Prochaines Étapes

1. **Valider ce plan** avec vous
2. **Commencer par Phase 1** (infrastructure)
3. **Implémenter progressivement** en testant à chaque étape
4. **Résoudre les bugs** au fur et à mesure

Souhaitez-vous que je commence l'implémentation ?

