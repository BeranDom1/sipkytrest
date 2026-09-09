#!/bin/bash
set -euo pipefail

PROJECT_DIR=/opt/sipky-trest
TOOL=/usr/local/sbin/sipky-db
SUDOERS_FILE=/etc/sudoers.d/codexagent-sipky-db

if [[ ${EUID} -ne 0 ]]; then
    echo "Spustte skript pres sudo." >&2
    exit 1
fi

install -o root -g root -m 0755 "${PROJECT_DIR}/ops/sipky-db" "${TOOL}"
printf '%s\n' \
    'codexagent ALL=(root) NOPASSWD: /usr/local/sbin/sipky-db *' \
    > "${SUDOERS_FILE}"
chown root:root "${SUDOERS_FILE}"
chmod 0440 "${SUDOERS_FILE}"
visudo -c

echo "Databazovy nastroj je nainstalovany."
