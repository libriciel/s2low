#!/bin/bash

set -e -x

# Debian stuff

export DEBIAN_FRONTEND=noninteractive

apt-get update

apt-get install -y  --no-install-recommends php-xdebug vim

rm -r /var/lib/apt/lists/*

composer install

# install npm packages
npm install
npx webpack --config webpack.config.js