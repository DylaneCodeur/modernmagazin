# ModernMagazin 🛒

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange)](https://www.mysql.com/)

**ModernMagazin** est une plateforme e-commerce moderne, responsive et intuitive, développée avec PHP, MySQL, AJAX, jQuery et Tailwind CSS. Cette solution complète offre une expérience utilisateur fluide et des fonctionnalités riches, le tout avec un design élégant et contemporain.

![ModernMagazin Preview](https://picsum.photos/id/3/800/400)

## 🌟 Fonctionnalités

### 🛍️ Fonctionnalités Générales
- **Design Ultra Moderne** avec Tailwind CSS
- **Interface Entièrement Responsive** adaptée à tous les appareils
- **Navigation Fluide et Intuitive** sans rechargement de page
- **Animations et Transitions** élégantes
- **Notifications Toast** en temps réel

### 🏠 Page d'Accueil / Catalogue
- Bannière Hero attractive et dynamique
- Affichage des catégories principales avec design distinctif
- Produits vedettes sur la page d'accueil
- Catalogue complet avec filtres par catégorie
- Fonction de recherche avancée
- Tri des produits (prix, popularité, nouveautés)
- Pagination dynamique en AJAX

### 📦 Fonctionnalités Produit
- **Quick View Modal** pour aperçu rapide en AJAX
- **Système de Panier** en temps réel avec gestion AJAX
- **Système de Favoris** avec mise à jour instantanée
- Affichage des prix avec gestion des remises
- Système de notation avec étoiles

### 🔐 Authentification et Sécurité
- Système de connexion complet et sécurisé
- Inscription avec validation des données
- Récupération de mot de passe par email
- Protection contre les attaques CSRF
- Hachage sécurisé des mots de passe

### ⚙️ Administration
- Tableau de bord avec statistiques en temps réel
- Gestion complète des produits (CRUD)
- Ajout/édition de produits avec prévisualisation d'image
- Gestion des utilisateurs avec différents niveaux d'accès
- Modifications en temps réel sans rechargement

## 🚀 Installation

### Prérequis
- PHP 7.4 ou supérieur
- MySQL 8.0 ou supérieur
- Serveur web (Apache, Nginx, etc.)
- Composer (recommandé pour les dépendances futures)

### Étapes d'installation
1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/DylaneCodeur/modernmagazin.git
   cd modernmagazin
   ```

2. **Configurer la base de données**
   - Créez une base de données MySQL
   - Importez le fichier `database_setup.sql` dans votre base de données
   ```bash
   mysql -u username -p database_name < database_setup.sql
   ```

3. **Configurer l'application**
   - Modifiez le fichier `config.php` avec vos informations de connexion à la base de données
   ```php
   $servername = "localhost";
   $username = "your_username";
   $password = "your_password";
   $dbname = "your_database";
   ```

4. **Démarrer l'application**
   - Déplacez le dossier dans votre répertoire web (htdocs pour XAMPP, www pour WAMP, etc.)
   - Accédez à l'application via votre navigateur: `http://localhost/modernmagazin`

## 📁 Structure du Projet

Le projet est organisé de manière modulaire avec une séparation claire des responsabilités :

```
modernmagazin/
├── config.php                # Configuration globale et fonctions utilitaires
├── database_setup.sql        # Script de création et initialisation de la BD
├── index.php                 # Page d'accueil et catalogue de produits
├── login.php                 # Page de connexion utilisateur
├── register.php              # Page d'inscription
├── admin.php                 # Panneau d'administration
├── favorites.php             # Page de gestion des favoris
├── logout.php                # Script de déconnexion
├── ajax_handler.php          # Gestionnaire des requêtes AJAX
├── assets/                   # Ressources statiques
│   ├── css/                  # Styles CSS personnalisés
│   ├── js/                   # Scripts JavaScript
│   └── img/                  # Images du site
└── uploads/                  # Dossier pour les images produits uploadées
```

## 💾 Structure de la Base de Données

Le schéma de la base de données est conçu pour être à la fois performant et extensible :

### Tables Principales
- **users** - Stocke les utilisateurs avec niveaux d'accès
- **products** - Catalogue complet des produits
- **user_favorites** - Relations entre utilisateurs et produits favoris
- **password_resets** - Jetons pour la réinitialisation de mot de passe
- **orders** - Commandes des utilisateurs
- **order_items** - Détails des commandes (produits, quantités, prix)

## 🔧 Technologies Utilisées

- **Backend**: PHP pour la logique serveur
- **Frontend**: HTML5, CSS3, JavaScript
- **Bibliothèques JS**: jQuery pour les manipulations DOM et AJAX
- **Styles**: Tailwind CSS pour le design responsive
- **Base de données**: MySQL avec transactions sécurisées
- **Interaction**: AJAX pour les interactions en temps réel

## 👥 Exemples d'Utilisation

### Connexion Administrateur
```
URL: http://localhost/modernmagazin/login.php
Identifiant: admin
Mot de passe: admin123
```

### Compte Utilisateur Test
```
URL: http://localhost/modernmagazin/login.php
Identifiant: user
Mot de passe: user123
```

## 🛠️ Personnalisation

Le système est conçu pour être facilement personnalisable :

1. **Apparence** - Modifiez les variables CSS dans index.php pour adapter le thème à votre marque
2. **Configuration** - Ajustez les paramètres dans config.php selon vos besoins
3. **Catégories** - Ajoutez facilement de nouvelles catégories via le panneau d'administration

## 📈 Roadmap et Améliorations Futures

- [ ] Intégration de passerelles de paiement (Stripe, PayPal)
- [ ] Système de commentaires et d'avis clients
- [ ] Optimisation SEO avancée
- [ ] Tableau de bord analytique avec graphiques
- [ ] Système de coupons et de réductions
- [ ] Support multilingue

## 🤝 Contribuer

Les contributions sont les bienvenues! Pour contribuer :

1. Forkez le projet
2. Créez votre branche de fonctionnalité (`git checkout -b feature/amazing-feature`)
3. Committez vos changements (`git commit -m 'Add some amazing feature'`)
4. Poussez vers la branche (`git push origin feature/amazing-feature`)
5. Ouvrez une Pull Request

Veuillez vous assurer que votre code respecte les standards de codage du projet.

## 📝 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🙏 Remerciements

- [Tailwind CSS](https://tailwindcss.com/) pour le framework CSS utilitaire
- [Font Awesome](https://fontawesome.com/) pour les icônes
- [jQuery](https://jquery.com/) pour la bibliothèque JavaScript
- Tous les contributeurs qui ont participé à ce projet

---

Développé avec ❤️ par [DylaneCodeur](https://github.com/DylaneCodeur)
