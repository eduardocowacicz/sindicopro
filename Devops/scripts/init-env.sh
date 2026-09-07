#!/usr/bin/env sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
DEVOPS_DIR=$(dirname -- "$SCRIPT_DIR")

exec "$DEVOPS_DIR/dev" init
