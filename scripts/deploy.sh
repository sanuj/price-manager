#!/usr/bin/env bash

# Build again.
yarn && yarn run production;

# Remove unwanted stuff.
rm -rf node_modules storage .env;

SHOULD_RESTART_WORKERS=$(git diff --name-only HEAD^1 | grep -c '.php');

# Deployment
# 1. Prepare
PROJECT=${APP_DEPLOYMENT_DIR:-exponent};
RELEASE=$(date +%Y%m%d%H%m%S);
# 2. Upload
tar -czf ~/${RELEASE}.tar.gz .
scp ~/${RELEASE}.tar.gz ${APP_SERVER_DSN}:~/${PROJECT}/releases
# 3. Deploy
ssh ${APP_SERVER_DSN} 'bash -se' << REMOTE_SCRIPT
  echo "Extract ${RELEASE}.tar.gz -> ${RELEASE}";
  mkdir ~/${PROJECT}/releases/${RELEASE};
  cd ~/${PROJECT}/releases/${RELEASE};
  tar -xzf ~/${PROJECT}/releases/${RELEASE}.tar.gz;
  rm ~/${PROJECT}/releases/${RELEASE}.tar.gz;

  echo "Link storage and environment";
  ln -s ~/${PROJECT}/storage ~/${PROJECT}/releases/${RELEASE}/storage;
  ln -s ~/${PROJECT}/.env ~/${PROJECT}/releases/${RELEASE}/.env;

  echo "Run post deployment application commands";
  php artisan migrate --env=production --force --no-interaction;
  php artisan view:clear;
  php artisan cache:clear;
  php artisan config:cache;
  php artisan optimize;

  echo "Link RELEASE to current";
  ln -nfs ~/${PROJECT}/releases/${RELEASE} ~/${PROJECT}/current;

  if [[ ${SHOULD_RESTART_WORKERS} -ge 0 ]]; then;
    echo "Restart Workers"
    sudo supervisorctl restart all;
  fi;

  sudo service php7.1-fpm restart;
  sudo service nginx restart;

  # Delete old release
  cd ~/${PROJECT}/releases;
  find . -maxdepth 1 -name "20*" -mmin +2880 | head -n 5 | xargs rm -Rf;
REMOTE_SCRIPT
