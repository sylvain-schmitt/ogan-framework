# 📝 Manipulation de Texte

> Filtres de templates et classe Text pour extraits, troncature, temps de lecture

## Filtres de templates

Ces filtres sont disponibles directement dans les templates :

### `|excerpt(length)`

Génère un extrait de texte (supprime le HTML, tronque proprement) :

```html
<!-- Extrait de 150 caractères (défaut) -->
<p>{{ article.content|excerpt }}</p>

<!-- Extrait de 250 caractères -->
<p>{{ article.content|excerpt(250) }}</p>
```

### `|words(count)`

Limite à un nombre de mots :

```html
<!-- 20 mots max (défaut) -->
<p>{{ article.content|words }}</p>

<!-- 30 mots -->
<p>{{ article.content|words(30) }}</p>
```

### `|truncate(length)`

Tronque à une longueur exacte (peut couper les mots) :

```html
<p>{{ article.title|truncate(50) }}</p>
```

### `|reading_time`

Affiche le temps de lecture estimé :

```html
<span>{{ article.content|reading_time }}</span>
<!-- → "5 min de lecture" -->
```

### `|word_count`

Compte les mots :

```html
<span>{{ article.content|word_count }} mots</span>
```

### `|strip_html`

Supprime les tags HTML :

```html
<p>{{ article.content|strip_html }}</p>
```

---

## Classe Text (PHP)

Pour utiliser ces fonctions côté PHP :

```php
use Ogan\Util\Text;

// Extrait
$excerpt = Text::excerpt($article->getContent(), 150);

// Limiter aux mots
$preview = Text::words($article->getContent(), 20);

// Tronquer
$short = Text::truncate($title, 50);

// Temps de lecture
$time = Text::readingTime($content);        // → 5 (int)
$timeStr = Text::readingTimeFormatted($content);  // → "5 min de lecture"

// Compter les mots
$count = Text::wordCount($content);

// Supprimer HTML
$plain = Text::stripHtml($htmlContent);
```

---

## Ajouter un getter au modèle

Pour un accès plus simple dans les templates :

```php
// Dans Article.php
public function getExcerpt(int $length = 150): string
{
    return \Ogan\Util\Text::excerpt($this->getContent(), $length);
}

public function getReadingTime(): string
{
    return \Ogan\Util\Text::readingTimeFormatted($this->getContent());
}
```

Puis dans le template :

```html
<p>{{ article.excerpt }}</p>
<span>{{ article.readingTime }}</span>
```

---

## Exemples pratiques

### Liste d'articles avec aperçu

```html
{% for article in articles %}
<article class="card">
    <h2>{{ article.title }}</h2>
    <div class="meta">
        <span>{{ article.createdAt|date('d M Y') }}</span>
        <span>{{ article.content|reading_time }}</span>
    </div>
    <p>{{ article.content|excerpt(200) }}</p>
    <a href="{{ path('article_show', {slug: article.slug}) }}">Lire la suite</a>
</article>
{% endfor %}
```

### Meta description SEO

```html
<meta name="description" content="{{ article.content|excerpt(160) }}">
```
