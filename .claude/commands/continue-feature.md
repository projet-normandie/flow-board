Reprise de la feature : $ARGUMENTS

La migration de cette feature a déjà été jouée en local. Règles strictes pour cette session :

- Ne jamais exécuter de commande Git, quelle que soit la situation
- Ne PAS créer de nouvelle migration

Si le schéma doit changer :
1. Modifier l'entité
2. Down : `php bin/console doctrine:migrations:execute --down VERSION`
3. Modifier la migration existante (ou recréer avec `doctrine:migrations:diff`)
4. Up : `php bin/console doctrine:migrations:execute --up VERSION`

## Fichier de suivi

Au démarrage, lis le fichier `.claude/feature-status/[nom-feature].md` et fais un résumé de :
- L'objectif de la feature
- La migration en cours
- Les fichiers déjà modifiés
- Où on en était

Puis demande-moi ce qu'on attaque dans cette session.

Met à jour ce fichier au fil de la session à chaque fois qu'un fichier est modifié, et fais un point sur l'état en fin de session.
