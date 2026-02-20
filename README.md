# FlowBoard

FlowBoard est une application de gestion de tâches Kanban privée, développée avec Symfony 8 / Twig. Elle offre un board global multi-projets, un système d'étiquettes, de priorités et une gestion fine des utilisateurs.

---

## Fonctionnalités V0

- **Application privée** : accès réservé aux utilisateurs authentifiés (Symfony Security)
- **Board Kanban global** : vue unique de toutes les colonnes et cartes, tous projets confondus
- **Filtrage par projet** : filtrer les cartes par projet via un paramètre GET
- **Drag & drop** : réorganisation des cartes dans une colonne et entre colonnes (SortableJS)
- **Priorités sur les cartes** : enum Low / Medium / High / Critical avec indicateur visuel discret (bordure gauche colorée)
- **Multi-projets** : chaque carte est rattachée à un projet identifié par un nom et une couleur
- **Badge projet** : chaque carte affiche le nom et la couleur de son projet
- **Labels** : étiquettes globales (nom + couleur de fond), plusieurs labels par carte
- **Assignation** : plusieurs utilisateurs assignables par carte
- **Rôles** : `ROLE_USER` (accès board), `ROLE_ADMIN` (gestion complète), `ROLE_SUPER_ADMIN` (gestion des utilisateurs uniquement)
- **CreatedAt / UpdatedAt** : sur toutes les entités via Doctrine Lifecycle
- **Audit complet** : traçabilité de toutes les modifications via `damienharper/auditor-bundle`

---

## Conventions de nommage

| Contexte          | Valeur          |
|-------------------|-----------------|
| Repo GitHub       | `flow-board`    |
| Namespace Symfony | `FlowBoard\`   |
| Base de données   | `flow_board`    |
| App name          | `FlowBoard`     |

---

## Stack technique

- PHP 8.4+
- Symfony 8
- Twig
- Doctrine ORM
- Symfony Security (firewall, voters)
- `damienharper/auditor-bundle` (audit trail)
- `gedmo/doctrine-extensions` (soft delete)
- SortableJS (drag & drop)
- Stimulus.js (controllers JS)

---

## Architecture des entités

> Toutes les entités possèdent les champs `createdAt` et `updatedAt` gérés automatiquement par Doctrine Lifecycle Callbacks.

### User

| Champ     | Type              | Description                             |
|-----------|-------------------|-----------------------------------------|
| id        | int               | Clé primaire                            |
| email     | string            | Identifiant de connexion (unique)       |
| password  | string            | Mot de passe hashé                      |
| roles     | json                | `["ROLE_USER"]`, `["ROLE_ADMIN"]`, `["ROLE_SUPER_ADMIN"]` |
| fullName  | string            | Nom affiché sur les cartes              |
| jobTitle  | JobTitle (nullable) | Métier : Developer, Tester, SysAdmin, Product Owner   |
| enabled   | boolean             | Compte actif ou désactivé (défaut: `true`)            |
| cards     | ManyToMany → Card | Cartes auxquelles il est assigné        |
| createdAt | datetime          | Date de création                        |
| updatedAt | datetime          | Date de dernière modification           |

### Project

| Champ     | Type      | Description                   |
|-----------|-----------|-------------------------------|
| id        | int       | Clé primaire                  |
| name      | string    | Nom du projet                 |
| color     | string(7) | Couleur hex (ex: `#3b82f6`)   |
| createdAt | datetime  | Date de création              |
| updatedAt | datetime  | Date de dernière modification |

### Column

| Champ     | Type     | Description                         |
|-----------|----------|-------------------------------------|
| id        | int      | Clé primaire                        |
| name      | string   | Nom de la colonne                   |
| position  | int      | Ordre d'affichage (espacé par 1000) |
| createdAt | datetime | Date de création                    |
| updatedAt | datetime | Date de dernière modification       |

### Label

| Champ     | Type      | Description                         |
|-----------|-----------|-------------------------------------|
| id        | int       | Clé primaire                        |
| name      | string    | Nom du label (ex: "Bug", "Feature") |
| color     | string(7) | Couleur de fond hex (ex: `#22c55e`) |
| createdAt | datetime  | Date de création                    |
| updatedAt | datetime  | Date de dernière modification       |

### Card

| Champ       | Type                    | Description                             |
|-------------|-------------------------|-----------------------------------------|
| id          | int                     | Clé primaire                            |
| title       | string                  | Titre de la carte                       |
| description | text (nullable)         | Description                             |
| position    | int                     | Ordre dans la colonne (espacé par 1000) |
| dueDate     | datetime (nullable)     | Date d'échéance                         |
| priority    | CardPriority (nullable) | Enum de priorité                        |
| column      | ManyToOne → Column      | Colonne parente                         |
| project     | ManyToOne → Project     | Projet rattaché                         |
| labels      | ManyToMany → Label      | Labels associés                         |
| assignees   | ManyToMany → User       | Utilisateurs assignés                   |
| createdAt   | datetime                | Date de création                        |
| updatedAt   | datetime                | Date de dernière modification           |
| deletedAt   | datetime (nullable)     | Date d'archivage (soft delete Gedmo)    |

---

## Tables de jointure

| Table        | Entités reliées |
|--------------|-----------------|
| `card_label` | Card ↔ Label    |
| `card_user`  | Card ↔ User     |

---

## CreatedAt / UpdatedAt

Un trait partagé sur toutes les entités :

```php
trait TimestampableTrait
{
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

Toutes les entités utilisent ce trait et l'annotation `#[ORM\HasLifecycleCallbacks]`.

---

## Audit Trail — damienharper/auditor-bundle

Toutes les modifications sur les entités sont tracées automatiquement.

### Installation

```bash
composer require damienharper/auditor-bundle
php bin/console doctrine:migrations:migrate
```

### Configuration

```php
// config/packages/auditor.php
return [
    'auditor' => [
        'user_provider' => 'auditor.user.provider.symfony',
        'entities' => [
            Card::class    => ['audit' => true],
            Project::class => ['audit' => true],
            Column::class  => ['audit' => true],
            Label::class   => ['audit' => true],
            User::class    => ['audit' => true],
        ],
    ],
];
```

Le bundle génère automatiquement des tables `card_audit`, `project_audit`, etc. avec l'historique complet : qui a modifié quoi, quand, et quelle valeur avant/après.

---

## Enum JobTitle

```php
enum JobTitle: string
{
    case DEVELOPER = 'developer'; // Développeur
    case TESTER    = 'tester';    // Testeur
    case SYS_ADMIN = 'sys_admin'; // Administrateur Système
    case PRODUCT_OWNER = 'product_owner'; // Product Owner
}
```

Affiché sur l'avatar/profil de l'utilisateur et dans le formulaire d'assignation des cartes.

---

## Enum CardPriority

```php
enum CardPriority: string
{
    case LOW      = 'low';      // Gris   #6b7280
    case MEDIUM   = 'medium';   // Bleu   #3b82f6
    case HIGH     = 'high';     // Orange #f97316
    case CRITICAL = 'critical'; // Rouge  #ef4444
}
```

Indicateur visuel : bordure gauche colorée sur la carte :

```css
.card[data-priority="critical"] { border-left: 3px solid #ef4444; }
.card[data-priority="high"]     { border-left: 3px solid #f97316; }
.card[data-priority="medium"]   { border-left: 3px solid #3b82f6; }
.card[data-priority="low"]      { border-left: 3px solid #6b7280; }
```

---

## Archivage des Cards (Soft Delete)

L'archivage utilise le filtre `SoftDeleteable` de Gedmo. La card n'est pas supprimée en base, seul `deletedAt` est renseigné.

```php
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Card
{
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;
}
```

Le filtre Gedmo est actif globalement — le board n'affiche que les cards actives sans modifier le `CardRepository`.

Pour la page d'archives, on désactive le filtre ponctuellement :

```php
$em->getFilters()->disable('softdeleteable');
$archivedCards = $cardRepository->findArchivedCards();
$em->getFilters()->enable('softdeleteable');
```

```php
// CardRepository
public function findArchivedCards(): array
{
    return $this->createQueryBuilder('c')
        ->where('c.deletedAt IS NOT NULL')
        ->orderBy('c.deletedAt', 'DESC')
        ->leftJoin('c.project', 'p')->addSelect('p')
        ->leftJoin('c.assignees', 'u')->addSelect('u')
        ->getQuery()
        ->getResult();
}
```

Pour restaurer une card archivée, il suffit de remettre `deletedAt` à `null`.

---

## Sécurité

### Firewall

```php
// config/packages/security.php
'firewalls' => [
    'main' => [
        'lazy' => true,
        'form_login' => [
            'login_path' => 'app_login',
            'check_path' => 'app_login',
        ],
        'logout' => ['path' => 'app_logout'],
    ],
],
'access_control' => [
    ['path' => '^/login', 'roles' => 'PUBLIC_ACCESS'],
    ['path' => '^/admin', 'roles' => 'ROLE_ADMIN'],
    ['path' => '^/',      'roles' => 'ROLE_USER'],
],
```

### Rôles

| Rôle         | Accès                                                   |
|--------------|---------------------------------------------------------|
| `ROLE_USER`         | Consulter le board, voir ses cartes assignées                |
| `ROLE_ADMIN`        | CRUD projets, colonnes, cartes, labels                       |
| `ROLE_SUPER_ADMIN`  | Gestion des utilisateurs (créer, modifier, activer/désactiver) |

### Blocage des comptes désactivés

L'entité `User` implémente `AdvancedUserInterface` via la méthode `isEnabled()` :

```php
public function isEnabled(): bool
{
    return $this->enabled;
}
```

Symfony Security bloquera automatiquement la connexion d'un user avec `enabled = false`, sans besoin de voter supplémentaire.

---

### CardVoter

```php
class CardVoter extends Voter
{
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return $subject->getAssignees()->contains($user);
    }
}
```

---

## Board et filtrage

- `/board` → toutes les cartes, tous projets
- `/board?project=1` → cartes du projet 1 uniquement

### CardRepository

```php
public function findWithFilters(?Project $project = null, ?CardPriority $priority = null): array
{
    $qb = $this->createQueryBuilder('c')
        ->leftJoin('c.labels', 'l')->addSelect('l')
        ->leftJoin('c.project', 'p')->addSelect('p')
        ->leftJoin('c.assignees', 'u')->addSelect('u');

    if ($project) {
        $qb->andWhere('c.project = :project')
           ->setParameter('project', $project);
    }

    if ($priority) {
        $qb->andWhere('c.priority = :priority')
           ->setParameter('priority', $priority);
    }

    return $qb->orderBy('c.position', 'ASC')
              ->getQuery()
              ->getResult();
}
```

---

## Drag & Drop

Position espacée de 1000 entre chaque carte. Lors d'un déplacement, la nouvelle position est la moyenne des voisins. Si l'espace devient insuffisant (< 1), renumérotage complet de la colonne.

Endpoint : `PATCH /card/{id}/position`

---

## Installation

```bash
git clone git@github.com:<your-username>/flow-board.git
cd flow-board
composer install
cp .env .env.local
# Configurer DATABASE_URL dans .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console app:create-admin admin@example.com motdepasse
php bin/console server:start
```

---

## Roadmap

### V0 (MVP)
- [ ] Trait `TimestampableTrait` (createdAt / updatedAt)
- [ ] Entité User + Symfony Security (firewall, login form)
- [ ] Entités : Project, Column, Card, Label
- [ ] Tables de jointure `card_label`, `card_user`
- [ ] Migrations Doctrine
- [ ] Installation et configuration `damienharper/auditor-bundle`
- [ ] CardVoter
- [ ] BoardController avec filtrage projet
- [ ] CRUD Columns et Cards
- [ ] CRUD Labels (admin)
- [ ] CRUD Projects (admin)
- [ ] Gestion des Users (admin)
- [ ] Drag & drop SortableJS + endpoint PATCH position
- [ ] Formulaire Card : EnumType priorité, EntityType labels, EntityType assignees
- [ ] Badge projet (nom + couleur) sur les cartes
- [ ] Indicateur visuel priorité (bordure gauche CSS)
- [ ] Badges labels sur les cartes
- [ ] Avatars assignés sur les cartes
- [ ] Due date avec alerte visuelle (rouge si dépassée, orange si proche)
- [ ] Commande `app:create-admin`
- [ ] Archivage des Cards (soft delete Gedmo) + page d'archives
- [ ] Restauration d'une Card archivée

### V1
- [ ] User ↔ Project : table `project_user`, un user ne voit que ses projets autorisés
- [ ] Adapter BoardController et CardVoter pour les permissions par projet
- [ ] Commentaires sur les Cards (entité `Comment` : auteur, contenu, date)
- [ ] Notifications : alerte quand un user est assigné à une carte

### V2
- [ ] Historique d'activité sur les Cards (déplacement, changement de priorité, assignation)
- [ ] Pièces jointes sur les Cards (VichUploaderBundle)
- [ ] Recherche globale full-text sur les cartes
- [ ] Export CSV/PDF des cartes par projet
- [ ] API REST
