#TP BANK PRO

Installer
-

- docker-compose up -d 
- docker-compose exec web composer install
- docker-compose exec web d:s:u --force
- docker-compose exec web doctrine:fixtures:load --purge-with-truncate

Utiliser l'API
- 
- Documentation dispo sur http://localhost/api/doc