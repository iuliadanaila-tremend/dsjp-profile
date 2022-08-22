Install DSJP Profile

docker-compose up -d

docker-compose exec web composer install

Make sure the profile is installed:

docker-compose exec web composer require digit/dsjp --update-with-dependencies

docker-compose exec web ./vendor/bin/run toolkit:build-dev

docker-compose exec web ./vendor/bin/drush site-install dsjp_profile
