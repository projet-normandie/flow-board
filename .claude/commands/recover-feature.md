Récupération du contexte d'une feature en cours : $ARGUMENTS

Je n'ai pas de fichier de suivi pour cette feature. Règles strictes pour toute la session :

- Ne jamais exécuter de commande Git, quelle que soit la situation
- Ne PAS créer de nouvelle migration sans vérification préalable
- Avant toute action sur le schéma : `php bin/console doctrine:migrations:status`

## Reconstruction du contexte

Analyse le projet pour reconstituer l'état de la feature :

1. Vérifie le statut des migrations : `php bin/console doctrine:migrations:status`
2. Identifie les fichiers récemment modifiés (entités, repositories, controllers, templates, services...)
3. Déduis ce qui a déjà été fait

Puis demande-moi :
- L'objectif de la feature (ce qu'on cherche à faire)
- Confirmation des fichiers que tu as identifiés comme modifiés
- Si la migration en cours a déjà été jouée ou non

Ensuite crée le fichier `.claude/feature-status/[nom-feature].md` avec la structure suivante :

```markdown
# Feature : [nom]

## Objectif
[description de ce qu'on cherche à faire]

## Migration en cours
[VERSION ou "aucune pour l'instant"] — [jouée / pas encore jouée]

## Fichiers modifiés
- [liste reconstituée]

## État
[ce qui est fait, ce qui reste à faire]
```

Met à jour ce fichier au fil de la session à chaque fois qu'un fichier est modifié, et fais un point sur l'état en fin de session.
