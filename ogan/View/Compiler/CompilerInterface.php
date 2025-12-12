<?php

namespace Ogan\View\Compiler;

/**
 * Interface pour les compilateurs de templates
 */
interface CompilerInterface
{
    /**
     * Compile le contenu d'un template
     * 
     * @param string $content Contenu du template à compiler
     * @return string Contenu compilé en PHP
     */
    public function compile(string $content): string;
}

