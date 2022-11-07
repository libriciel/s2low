#!/bin/bash

set -e -x

# Debian stuff

export DEBIAN_FRONTEND=noninteractive

apt-get update

apt-get install -y  --no-install-recommends php-xdebug vim nodejs npm

rm -r /var/lib/apt/lists/*

composer install

#Change cache location to a writable directory
npm config set cache var/.npm --global

# install npm packages
npm install
npx webpack --config webpack.config.js