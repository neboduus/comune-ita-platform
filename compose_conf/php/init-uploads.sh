#!/bin/bash

if [[ ! -d "var/uploads" ]]; then
    mkdir var/uploads
else
    chown -R wodby:wodby var/uploads
fi
