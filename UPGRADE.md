Upgrading From 1.x To 2.0
=========================

Version 2 is a major change introducing updates in file and directory structure, symfony framework and all dependencies of the project.

Steps required for updating:
- Update source code to version 2
- Run ``docker-compose up -d --build``
- Enter into container php
- Run ``./compose_conf/php/sync-metadata-db.sh``
- Run ``./compose_conf/php/migrate-db.sh``
