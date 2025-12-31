# EtatsCompta for [Dolibarr ERP CRM](https://www.dolibarr.org)

## Features

Cette ensemble de page à pour but d'ajouter certaines fonctionalités non encore intégré dans Dolibarr

- Génération balance et grand livre > fonctionalité déjà présente dans Dolibarr
- Génération de Compte de résultat et Bilan au format 2 colonnes avec configuration
des comptes de tiers pour le bilan
- Génération des écritures de cloture et d'A-nouveaux, reprennant les comptes
auxiliaire si ils existent ,
- Génération de rapport par compte en version détaillé (liste de compte auxiliaire,
balance par compte auxiliaire et grand livre par compte auxiliaire).
- Vérification de la clotûre sur une période (permet de détécter des écritures ajouté
par erreur sur des exercices déjà cloturer)

## Install

Install like any other modules.

1. Clone the repo inside Dolibarr's `htdocs/custom` folder (e.g. `htdocs/custom/etatscompta/` )
2. Copy `include/conf.inc.php.default` into a new `include/conf.inc.php` and enter the correct values

## Credits

Original code was published by Benoit Commenchail on a [GitLab repo](https://gitlab.com/BenoitCier/consultation-dolibarr-comptabilite) and in the french Dolibarr forums ([post](https://www.dolibarr.fr/forum/t/consultation-comptabilite-bilan-compte-de-resultat-ecriture-de-cloture-et-a-nouveaux/31762))

On that same post, Mathieu Brulaire suggested a module implementation of the files. Sources of this are available as an archive in [this GitHub repo](https://github.com/MathieuB19/etatscompta).

This repository (`cchanche/dolibarr-etatscompta`) is my attempt at cleaning up the contents of this archive to ease-up the process of maintaining a private instance of Dolibarr ERP which relies on this module/

<!--
![Screenshot etatscompta](img/screenshot_etatscompta.png?raw=true "EtatsCompta"){imgmd}
-->

## Translations

Translations can be define manually by editing files into directories *langs*.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more informations, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->

<!--

## Installation

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you there is no custom directory, check your setup is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

### From a GIT repository

- Clone the repository in ```$dolibarr_main_document_root_alt/etatscompta```

```sh
cd ....../custom
git clone git@github.com:gitlogin/etatscompta.git etatscompta
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

-->

