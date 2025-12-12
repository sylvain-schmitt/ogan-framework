# Configuration VS Code recommandée pour Ogan Framework

Pour une meilleure expérience de développement avec les templates `.ogan`, ajoutez ces paramètres à votre configuration VS Code.

## Configuration du workspace

Créez un fichier `.vscode/settings.json` dans votre projet avec :

```json
{
    "files.associations": {
        "*.ogan": "twig"
    },
    "emmet.includeLanguages": {
        "twig": "html"
    },
    "[twig]": {
        "editor.formatOnSave": false,
        "editor.tabSize": 4,
        "editor.insertSpaces": true
    }
}
```

## Extensions recommandées

Installez ces extensions VS Code pour une meilleure coloration syntaxique :

1. **Twig Language 2** par mblode
   - ID: `mblode.twig-language-2`
   - Coloration syntaxique pour `{{ }}`

2. **PHP Intelephense** par Ben Mewburn
   - ID: `bmewburn.vscode-intelephense-client`
   - Support PHP dans les templates

3. **EditorConfig for VS Code**
   - ID: `editorconfig.editorconfig`
   - Applique les règles du `.editorconfig`

## Installation rapide

```bash
code --install-extension mblode.twig-language-2
code --install-extension editorconfig.editorconfig
```

## Configuration globale (optionnel)

Pour appliquer ces paramètres à tous vos projets Ogan, ajoutez à vos paramètres utilisateur (`Ctrl+,` → Settings JSON) :

```json
{
    "files.associations": {
        "*.ogan": "twig"
    }
}
```
