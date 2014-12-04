#!/usr/bin/env bash

rsync -av --delete --exclude '.git' --exclude '.idea' --exclude '.gitignore' --exclude 'sync.sh' ~/dev/third/sproutemail ~/dev/www/sandbox.dev/craft/plugins/
