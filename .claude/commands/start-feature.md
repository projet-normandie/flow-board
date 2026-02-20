Nouvelle feature démarrée : $ARGUMENTS

On part de zéro sur cette feature. Règles pour toute la session :

- Ne jamais exécuter de commande Git, quelle que soit la situation
- Si une migration est créée, elle n'est pas encore jouée
- Ne pas créer plusieurs migrations, modifier toujours la dernière
- Avant toute action sur le schéma : `php bin/console doctrine:migrations:status`

Si le schéma doit évoluer pendant le dev :
1. Modifier l'entité
2. Modifier la migration existante (pas en créer une nouvelle)
3. Up : `php bin/console doctrine:migrations:execute --up VERSION`

## Fichier de suivi

Au démarrage, demande-moi :
- L'objectif de la feature (ce qu'on cherche à faire)
- Le nom de la migration en cours si elle existe déjà

Puis crée le fichier `.claude/feature-status/[nom-feature].md` avec la structure suivante :

```markdown
# Feature : [nom]

## Objectif
[description de ce qu'on cherche à faire]

## Migration en cours
[VERSION ou "aucune pour l'instant"]

## Fichiers modifiés
- [liste au fur et à mesure]

## État
[ce qui est fait, ce qui reste à faire]
```

Met à jour ce fichier au fil de la session à chaque fois qu'un fichier est modifié, et fais un point sur l'état en fin de session.
