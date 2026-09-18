#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Controle typographique OQLF des champs d'une fiche de glossaire.

    php artisan tinker --execute="require 'dump.php';"   # ecrit le JSON des champs
    python3 Modules/Dictionary/tests/tools/controle-typographie-oqlf.py /tmp/fiche.json

POURQUOI CE FICHIER EXISTE, et pourquoi il vaut mieux que le grep du skill.
Le 2026-09-18, TROIS controles typographiques ecrits a la volee ont rendu un verdict FAUX dans la
meme session, toujours pour la meme raison : l'espace insecable etait ecrit LITTERALEMENT dans le
motif. Un caractere invisible ne se relit pas, et n'importe quelle etape intermediaire (heredoc,
copier-coller, echappement shell) peut en substituer un autre sans laisser de trace.
   1er controle : 9 defauts annonces, 0 reel (il lisait le CODE PHP, pas le contenu).
   2e controle  : 5 champs fautifs annonces, 0 reel (lookbehind perdu entre shell, PHP et PCRE).
   3e controle  : 5 champs fautifs annonces, 0 reel (motif contenant 0020 au lieu de 00a0).
Ici, tout caractere invisible est ecrit par son POINT DE CODE ( ), jamais en clair.

Deuxieme lecon incorporee : le controle porte sur les champs REELLEMENT STOCKES, jamais sur le
fichier source de la migration - sinon les operateurs ternaires et les messages d'echo du PHP
passent pour des fautes de francais.

Troisieme lecon : un controle qui ne sait annoncer que zero ne prouve rien. Les TEMOINS en fin de
sortie affichent une mesure dont on attend une valeur non nulle ; si un temoin tombe a zero, c'est
le controle qu'il faut soupconner, pas le texte.

Norme appliquee : Office quebecois de la langue francaise. Elle DIFFERE de l'usage francais sur un
point decisif - au Quebec, AUCUNE espace devant ; ! ? Appliquer le reflexe francais est une faute.
"""
import json
import re
import sys

NBSP = " "

CONTROLES = (
    ("espace ordinaire avant ':'", "(?<! ) :"),
    ("espace avant ; ! ?",         "[\\s ]+[;!?]"),
    ("tiret cadratin",             "[—–]"),
    ("entite &nbsp;",              "&nbsp;"),
    ("% sans insecable",           "(?<! )%"),
    ("$ sans insecable",           "(?<! )\\$"),
    ("guillemet ouvrant nu",       "«(?! )"),
    ("guillemet fermant nu",       "(?<! )»"),
)


def champs_de(fiche: dict) -> dict:
    """Aplatit la fiche en {nom: texte}, faq et sources comprises."""
    plats = {c: v for c, v in fiche.items() if isinstance(v, str)}
    for i, paire in enumerate(fiche.get("faq") or []):
        plats[f"faq{i}.question"] = paire.get("question", "")
        plats[f"faq{i}.answer"] = paire.get("answer", "")
    for i, source in enumerate(fiche.get("sources") or []):
        plats[f"sources{i}.label"] = source.get("label", "")
    return plats


def main() -> int:
    if len(sys.argv) < 2:
        print("usage : controle-typographie-oqlf.py <fiche.json>", file=sys.stderr)
        return 2

    with open(sys.argv[1], encoding="utf-8") as fichier:
        plats = champs_de(json.load(fichier))

    fautifs = 0
    for nom, texte in plats.items():
        defauts = [libelle for libelle, motif in CONTROLES if re.search(motif, texte)]
        if defauts:
            fautifs += 1
            print(f"KO   {nom} : {', '.join(defauts)}")

    total_nbsp = sum(t.count(NBSP) for t in plats.values())
    ordinaires = sum(len(re.findall(" [:%]", t)) for t in plats.values())

    print(f"{'OK' if fautifs == 0 else 'KO'}   {len(plats)} champs de contenu, {fautifs} fautif(s)")
    print(f"temoin insecables poses (doit etre > 0)        : {total_nbsp}")
    print(f"temoin espaces ordinaires avant : ou % (doit etre 0) : {ordinaires}")

    if total_nbsp == 0:
        print("ATTENTION : aucun insecable trouve. Soupconner le controle avant le texte.")
        return 1

    return 1 if fautifs else 0


if __name__ == "__main__":
    sys.exit(main())
