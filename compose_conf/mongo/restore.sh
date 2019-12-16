#!/usr/bin/env bash
echo "Restoring"
mongorestore --drop --db formmanager /dump/formmanager
echo "Restored"
