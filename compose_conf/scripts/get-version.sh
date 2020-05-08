#!/bin/bash
if [[ -n $CI_COMMIT_TAG ]]; then
  echo ${CI_COMMIT_TAG} @${CI_COMMIT_SHORT_SHA}
else
  echo ${CI_COMMIT_REF_NAME} @${CI_COMMIT_SHORT_SHA}
fi
