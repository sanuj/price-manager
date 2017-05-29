#!/usr/bin/env bash

# Build again.
# TODO: This could be removed!
yarn run production;

# Remove unwanted stuff.
php artisan clear-compiled;
rm -rf node_modules storage;

SHOULD_RESTART_WORKERS=$(git diff --name-only HEAD^1 | grep -c '.php')

# Deployment
# 1. Prepare
release=$(date +%Y%m%d%H%m%S)
ssh ${APP_SERVER_DSN} 'bash -se' << REMOTE_SCRIPT
  mkdir ~/exponent/releases/${release}
REMOTE_SCRIPT
# 2. Upload
scp -r ~/exponent ${APP_SERVER_DSN}:~/exponent/releases/${release}
# 3. Deploy
ssh ${APP_SERVER_DSN} 'bash -se' << REMOTE_SCRIPT
  # Link storage and environment
  ln -s ~/exponent/storage ~/exponent/releases/${release}/storage;
  ln -s ~/exponent/.env ~/exponent/releases/${release}/.env;

  # Run post deployment application commands.
  cd ~/exponent/releases/${release};
  php artisan migrate --env=production --force --no-interaction;
  php artisan view:clear --quiet;
  php artisan cache:clear --quiet;
  php artisan config:cache --quiet;
  php artisan optimize --quiet;

  # Link release to current.
  ln -nfs ~/exponent/releases/20170529173309 ~/exponent/current;

  # Restart services.
  if [[ ${SHOULD_RESTART_WORKERS} -ge 0 ]] then
    sudo supervisorctl restart all;
  fi;

  # Delete old release
  cd ~/exponent/releases;
  find . -maxdepth 1 -name "20*" -mmin +2880 | head -n 5 | xargs rm -Rf;
REMOTE_SCRIPT
