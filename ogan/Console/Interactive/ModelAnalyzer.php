<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * 🔍 MODEL ANALYZER - Analyse un modèle existant
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * RÔLE :
 * ------
 * Analyse un modèle existant pour extraire ses propriétés et relations.
 * 
 * ═══════════════════════════════════════════════════════════════════════
 */

namespace Ogan\Console\Interactive;

use ReflectionClass;
use ReflectionProperty;

class ModelAnalyzer
{
    /**
     * ═══════════════════════════════════════════════════════════════════
     * ANALYSER UN MODÈLE EXISTANT
     * ═══════════════════════════════════════════════════════════════════
     */
    public function analyze(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            throw new \RuntimeException("La classe {$modelClass} n'existe pas");
        }

        $reflection = new ReflectionClass($modelClass);
        $properties = $this->extractProperties($reflection);
        $relations = $this->extractRelations($reflection);

        return [
            'properties' => $properties,
            'relations' => $relations
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXTRAIRE LES PROPRIÉTÉS
     * ═══════════════════════════════════════════════════════════════════
     */
    private function extractProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        $reflectionProperties = $reflection->getProperties(ReflectionProperty::IS_PRIVATE);

        foreach ($reflectionProperties as $property) {
            $name = $property->getName();
            
            // Ignorer les propriétés spéciales
            if (in_array($name, ['attributes', 'exists'])) {
                continue;
            }
            
            // Ignorer id, createdAt, updatedAt (seront ajoutés automatiquement)
            if (in_array($name, ['id', 'createdAt', 'updatedAt'])) {
                continue;
            }

            // Récupérer le type
            $type = $this->getPropertyType($property);
            
            // Déterminer si nullable
            $nullable = $this->isPropertyNullable($property);
            
            // Récupérer le commentaire
            $comment = $this->getPropertyComment($property);

            $properties[] = [
                'name' => $name,
                'type' => $type,
                'nullable' => $nullable,
                'comment' => $comment
            ];
        }

        return $properties;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE TYPE D'UNE PROPRIÉTÉ
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getPropertyType(ReflectionProperty $property): string
    {
        // PHP 7.4+ : type déclaré
        if ($property->hasType()) {
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                // Enlever le namespace pour DateTime
                if ($typeName === 'DateTime' || $typeName === '\\DateTime') {
                    return 'datetime';
                }
                return $typeName;
            }
        }

        // Fallback : analyser le docblock
        $docComment = $property->getDocComment();
        if ($docComment && preg_match('/@var\s+(\S+)/', $docComment, $matches)) {
            $type = $matches[1];
            $type = preg_replace('/\|.*/', '', $type);
            $type = trim($type, '?');
            
            if (str_contains($type, 'DateTime')) {
                return 'datetime';
            }
            
            return $type;
        }

        return 'string';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * VÉRIFIER SI UNE PROPRIÉTÉ EST NULLABLE
     * ═══════════════════════════════════════════════════════════════════
     */
    private function isPropertyNullable(ReflectionProperty $property): bool
    {
        if ($property->hasType()) {
            $reflectionType = $property->getType();
            if ($reflectionType instanceof \ReflectionNamedType) {
                return $reflectionType->allowsNull();
            }
        }

        $docComment = $property->getDocComment();
        if ($docComment && preg_match('/@var\s+(\S+)/', $docComment, $matches)) {
            return str_contains($matches[1], 'null') || str_starts_with($matches[1], '?');
        }

        return true;
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * RÉCUPÉRER LE COMMENTAIRE D'UNE PROPRIÉTÉ
     * ═══════════════════════════════════════════════════════════════════
     */
    private function getPropertyComment(ReflectionProperty $property): string
    {
        $docComment = $property->getDocComment();
        if ($docComment && preg_match('/@var\s+\S+\s+(.+?)(?:\s*\*\/|\s*$)/s', $docComment, $matches)) {
            $comment = trim($matches[1]);
            // Nettoyer les caractères de fin de commentaire DocBlock
            $comment = rtrim($comment, '*/');
            $comment = trim($comment);
            return $comment;
        }
        return '';
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * EXTRAIRE LES RELATIONS (À implémenter si nécessaire)
     * ═══════════════════════════════════════════════════════════════════
     */
    private function extractRelations(ReflectionClass $reflection): array
    {
        // Pour l'instant, on ne peut pas extraire les relations automatiquement
        // car elles sont dans les méthodes, pas dans les propriétés
        // On retourne un tableau vide pour l'instant
        return [];
    }
}

