# Bibliothèque de planches de BD — standard « visionneur de BD »

Convention **zéro-code** pour associer une bande dessinée pédagogique (personnage
Octopus) à un terme du glossaire (`/glossaire/{slug}`). Aucune modification de code
n'est requise pour ajouter une nouvelle BD : il suffit de déposer un dossier.

## Ajouter une BD à un terme

1. Créer le dossier `public/bd/{term-slug}/` (le `term-slug` est le slug du terme,
   identique à l'URL `/glossaire/{term-slug}`).
2. Y déposer les fichiers web (versions optimisées, **pas** les originaux/print/source) :
   - `…-site.avif`, `…-site.webp`, `…-site.jpg` (pleine résolution web)
   - `…-site-1024.avif`, `…-site-1024.webp` (variante 1024 px)
   - `…-thumb.jpg` (vignette)
3. Créer `public/bd/{term-slug}/manifest.json` :

```json
{
  "term_slug": "cheval-de-troie",
  "title": "Le piège du cheval de Troie",
  "alt": "Bande dessinée : Octopus explique le cheval de Troie — …",
  "planches": [
    {
      "avif": "bd-…-site.avif",
      "webp": "bd-…-site.webp",
      "jpg": "bd-…-site.jpg",
      "avif_1024": "bd-…-site-1024.avif",
      "webp_1024": "bd-…-site-1024.webp",
      "thumb": "bd-…-thumb.jpg",
      "width": 1600,
      "height": 2448
    }
  ]
}
```

C'est tout. Au prochain affichage :

- la **fiche** `/glossaire/{slug}` montre un bouton **« 🐙 Lire la BD »** (au-dessus du
  pli, sous la phrase-réponse) qui ouvre le **visionneur lightbox** (Alpine maison,
  zoom/pan, Échap + clic fond pour fermer, lien « télécharger », a11y AA) ;
- la **grille** `/glossaire` affiche un **picto Octopus** discret en coin de carte
  (`aria-label="Bande dessinée disponible"`).

## Notes

- `alt` doit être **descriptif et non vide** (accessibilité + SEO).
- `planches` est un **tableau** : le multi-planches est déjà supporté (un panneau =
  un objet ; le 1er sert de vignette et de lien « télécharger »).
- Détection automatique : présence de `manifest.json` = ce terme a une BD
  (`Modules\Dictionary\Support\ComicLibrary::hasComic()` / `forSlug()`).
- Les fichiers sont servis **statiquement** depuis `public/bd/` (suivi git, déployé
  par rsync). Aucune dépendance JS externe.

## Composants impliqués

- Helper : `Modules/Dictionary/app/Support/ComicLibrary.php`
- Visionneur : `Modules/Dictionary/resources/views/components/comic-viewer.blade.php`
  (`<x-dictionary::comic-viewer :comic="$comic" />`)
- Indicateur grille : `Modules/Dictionary/resources/views/public/index.blade.php`
- Fiche : `Modules/Dictionary/resources/views/public/show.blade.php`

---

MEMORA solutions · https://memora.solutions · info@memora.ca
