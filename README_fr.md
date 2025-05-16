# Outil d'importation CourseFlow pour Moodle

Ce plugin Moodle permet d’importer du contenu de cours depuis CourseFlow vers un cours Moodle. Il vous permet de coller les données JSON générées par CourseFlow afin d’importer directement des leçons, des sections et des objectifs d’apprentissage dans votre cours Moodle.

## Fonctionnalités

- Importation de données JSON générées par CourseFlow dans un cours Moodle.
- Aperçu des leçons, sections et objectifs avant l’importation.
- Création automatique d’activités Moodle et de objectifs d’apprentissage.
- Intégration avec le carnet de notes et la structure du cours de Moodle.

## Installation

1. Clonez ou téléchargez ce dépôt dans le répertoire `local/` de votre installation Moodle :

   ```bash
   cd votre-répertoire-moodle/local
   git clone https://github.com/JChoquette/moodle-local_courseflowtool.git courseflowtool
   ```

2. Accédez à votre site Moodle dans un navigateur. Moodle détectera le nouveau plugin et vous demandera de compléter le processus d’installation.

### Prérequis

 - Moodle 4.x ou version ultérieure

 - Accès à un cours dans lequel vous avez les droits de modification

### Utilisation

1. Accédez au cours dans lequel vous souhaitez importer du contenu.

2. Dans les blocs d’administration situés dans la barre latérale gauche, vous verrez l’outil d’importation CourseFlow sous le menu «Plus».

3. Cliquez sur le lien pour ouvrir l’outil d’importation.

4. Collez les données JSON générées par CourseFlow dans la zone de texte.

5. Cliquez sur le bouton d’importation pour obtenir un aperçu de vos données.

6. Sélectionnez les éléments que vous souhaitez importer, puis cliquez sur « Confirmer et importer ».

7. Le plugin créera les sections, leçons et résultats d’apprentissage appropriés dans votre cours selon la structure du fichier JSON.

### Licence

Ce plugin est distribué sous la licence publique générale GNU version 3 ou ultérieure.

Copyright 2025 Jeremie Choquette