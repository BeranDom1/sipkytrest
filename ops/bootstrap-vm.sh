#!/bin/bash
set -euo pipefail

SOURCE_DIR=/home/codexagent/sipkytrest-staging
PROJECT_DIR=/opt/sipky-trest
PROJECT_GROUP=repo-sipky-trest
DEPLOY_SCRIPT=/usr/local/sbin/deploy-sipky-trest
SUDOERS_FILE=/etc/sudoers.d/codexagent-sipky-trest

if [[ ${EUID} -ne 0 ]]; then
    echo "Spustte skript pres sudo." >&2
    exit 1
fi

if [[ ! -d "${SOURCE_DIR}/.git" ]]; then
    echo "Chybi preneseny projekt v ${SOURCE_DIR}." >&2
    exit 1
fi

command -v docker >/dev/null
command -v setfacl >/dev/null || apt-get install -y acl
getent group "${PROJECT_GROUP}" >/dev/null || groupadd "${PROJECT_GROUP}"
usermod -aG "${PROJECT_GROUP}" beran
usermod -aG "${PROJECT_GROUP}" codexagent

install -d -o beran -g "${PROJECT_GROUP}" -m 2770 "${PROJECT_DIR}"
cp -a "${SOURCE_DIR}/." "${PROJECT_DIR}/"
rm -f "${PROJECT_DIR}/config/db.local.php" "${PROJECT_DIR}/config/db.production.php"

install -d -o root -g root -m 0750 "${PROJECT_DIR}/.vm"
openssl rand -base64 32 > "${PROJECT_DIR}/.vm/db_password"
openssl rand -base64 32 > "${PROJECT_DIR}/.vm/db_root_password"
chmod 0600 "${PROJECT_DIR}/.vm/db_password" "${PROJECT_DIR}/.vm/db_root_password"

db_password=$(cat "${PROJECT_DIR}/.vm/db_password")
cat > "${PROJECT_DIR}/.vm/db.local.php" <<PHP
<?php
return [
    'host' => 'db',
    'username' => 'sipky',
    'password' => '${db_password}',
    'database' => 'd377108_liga',
];
PHP
chmod 0600 "${PROJECT_DIR}/.vm/db.local.php"

chown -R beran:"${PROJECT_GROUP}" "${PROJECT_DIR}"
find "${PROJECT_DIR}" -type d -exec chmod g+rwx,g+s {} +
find "${PROJECT_DIR}" -type f -exec chmod g+rw {} +
setfacl -R -m "g:${PROJECT_GROUP}:rwx,m::rwx" "${PROJECT_DIR}"
setfacl -R -d -m "g:${PROJECT_GROUP}:rwx,m::rwx" "${PROJECT_DIR}"
chown -R root:root "${PROJECT_DIR}/.vm"
chmod 0750 "${PROJECT_DIR}/.vm"
chmod 0600 "${PROJECT_DIR}/.vm/"*
chown root:www-data "${PROJECT_DIR}/.vm/db.local.php"
chmod 0640 "${PROJECT_DIR}/.vm/db.local.php"

cat > "${DEPLOY_SCRIPT}" <<'SCRIPT'
#!/bin/bash
set -euo pipefail
cd /opt/sipky-trest
exec /usr/bin/docker compose up -d --build
SCRIPT
chown root:root "${DEPLOY_SCRIPT}"
chmod 0755 "${DEPLOY_SCRIPT}"

printf '%s\n' \
    'codexagent ALL=(root) NOPASSWD: /usr/local/sbin/deploy-sipky-trest' \
    > "${SUDOERS_FILE}"
chown root:root "${SUDOERS_FILE}"
chmod 0440 "${SUDOERS_FILE}"
visudo -c

sudo -u codexagent git config --global --add safe.directory "${PROJECT_DIR}"
"${DEPLOY_SCRIPT}"

echo "Sipky Trest bezi na http://192.168.0.11:8081"
